<?php
require_once("PagBank/PB_CreateOrderRequest.php");
require_once(WC_OLHI_PS_BASE_DIR . "/includes/utils.php");

// // Adiciona o hook para capturar a solicitação e a resposta
// add_action( 'http_api_debug', 'mostrar_solicitacao_http', 10, 5 );

// function mostrar_solicitacao_http( $response, $type, $class, $args, $url ) {
//     if ( 'POST' === $args['method'] && 'http' === parse_url( $url, PHP_URL_SCHEME ) ) {
//         echo "Solicitação POST enviada para: $url \n";
//         echo "Corpo da solicitação: " . wp_json_encode( $args['body'] ) . "\n";

//         echo "Resposta recebida: " . wp_json_encode( $response ) . "\n";
//     }
// }

class OlhiPS_Gateway_Common extends WC_Payment_Gateway {

    public function __construct() {
        $this->supports = [
            'products',
            'refunds',
            'default_credit_card_form',
		];

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function payment_fields() {
		$this->form();
	}

    public function field_name( $name ) {
		return ' name="' . esc_attr( $this->id . '-' . $name ) . '" ';
	}

    protected function olhiPS_createOrder($pedido, $order_id) {

        $bearer = $this->getToken();
    
        $url = $this->buildEndpoint("/orders");
    
        $headers = array(
            "Authorization" => "Bearer " . $bearer,
            "accept" => "application/json",
            'Content-Type' => 'application/json',
            "x-idempotency-key" => generate_random_string(10) . '-' . $order_id // TODO: need to verify this key. Should be unique for same call
        );
    
        $args = array(
            'headers' => $headers,
            'body' => json_encode($pedido),
            'timeout' => 20
        );

        $response = wp_remote_post($url, $args);

        if(is_wp_error($response)) {
            $error_message = $response->get_error_message();
            return $error_message;
        }

        $response_body = wp_remote_retrieve_body($response);
        return $response_body;
    }

    protected function olhiPS_createOrderObject($order) {
        $pbOrder = new PB_CreateOrderRequest();
        $pbOrder->reference_id = $order->id;
        $pbOrder->customer = $this->buildCustomer($order);
        $pbOrder->items = $this->buildItems($order);
        $pbOrder->charges = $this->buildCharges($order);
        $pbOrder->notification_urls = [getWebHookUrl()];
        return $pbOrder;
    }

    protected function buildCustomer($order) {
        $customer_id = $order->get_customer_id();
        $user = get_user_by('ID', $customer_id);

        $pbCustomer = new PB_Customer();
        // $pbCustomer->name = $user->display_name;

        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();

        $pbCustomer->name = $billing_first_name . " " .$billing_last_name;
        $pbCustomer->email  = $this->getUserEmail($user->user_email);

        $cpf = get_user_meta($user->ID, 'billing_cpf', true);
        $pbCustomer->tax_id = preg_replace("/[^0-9]/", "", $cpf);

        return $pbCustomer;
    }

    protected function buildItems($order) {
        $pb_items = [];

        $items = $order->get_items();
        foreach ($items as $item_id => $item_data) {
            array_push($pb_items, $this->buildItem($item_data));
        }

        return $pb_items;
    }

    protected function buildItem($item) {
        $pbItem = new PB_item();

        $pbItem->name = $item->get_name();
        $pbItem->unit_amount = $item->get_subtotal();
        $pbItem->quantity = $item->get_quantity();

        // $product_id = $item_data->get_product_id();

        return $pbItem;
    }

    protected function buildCharges($order) {
       
        $order->add_meta_data($this->id.'-card-encrypted', filter_input(INPUT_POST, $this->id.'-card-encrypted', FILTER_SANITIZE_STRING), true);
        $order->add_meta_data('_pagbank_card_exp_month', $response['charges'][0]['payment_method']['card']['exp_month'] ?? null);
        $order->add_meta_data('_pagbank_card_exp_year', $response['charges'][0]['payment_method']['card']['exp_year'] ?? null);
        $order->add_meta_data($this->id.'_card_installments', filter_input(INPUT_POST, $this->id.'-card-installments', FILTER_SANITIZE_NUMBER_INT), true);
        
        $pb_Card = new PB_Card();
        $pb_Card->encrypted = $order->get_meta($this->id.'-card-encrypted');
        $pb_Card->store = false;
        
        $pb_Amount = new PB_Amount();
        $pb_Amount->value = $this->handleValue($order->get_total());
        $pb_Amount->currency = "BRL";

        $pb_paymentMethod = new PB_PaymentMethod();
        $pb_paymentMethod->card = $pb_Card;
        $pb_paymentMethod->type = 'CREDIT_CARD';
        // $pb_paymentMethod->installments = intval($order->get_meta($this->id.'_card_installments'));
        $pb_paymentMethod->installments = 1;
        $pb_paymentMethod->capture = true;
        $pb_paymentMethod->soft_descriptor = "Olhi";
        
        $pb_charge = new PB_Charge();
        $pb_charge->amount = $pb_Amount;
        $pb_charge->payment_method = $pb_paymentMethod;
        $pb_charge->reference_id;
        $pb_charge->description;

        if($this->isSplitEnabled()) {
            $pb_charge->splits = $this->buildSplits($order);
        }

        return [$pb_charge];
    }

    protected function buildQRCode($order) {
        $pb_qrCode = new PB_QRCodes();

        $pb_amount = new PB_AmountValue();
        $pb_amount->value = $this->handleValue($order->get_total());

        $pb_qrCode->amount = $pb_amount;

        if($this->isSplitEnabled()) {
            $pb_qrCode->splits = $this->buildSplits($order);
        }

        return [$pb_qrCode];
    }

    protected function buildSplits($order) {

        $splits = [];

        $olhi_value = 0;

        $items = $order->get_items();
        foreach ($items as $item_id => $item_data) {
            $product_id = $item_data->get_product_id();
            $product_price = $item_data->get_product()->get_price();

            // $instrutor = get_post($product_id);
            $instrutor_id = get_post_meta($product_id, '_custom_product_user_field', true);
            $user = get_user_by('ID', $instrutor_id);
            // $user_meta = get_user_meta($instrutor_id);
            // print_r($user_meta);

            $subscription_plan = get_user_meta($instrutor_id, 'subscription_plan', true);
            $user_value = calculateUserValue($subscription_plan, $this->handleValue($product_price));
            $olhi_value = "0330"/ //.$this->handleValue($product_price) - $this->handleValue($user_value)."";

            $pb_amountValue = new PB_AmountValue();
            $pb_amountValue->value = $this->handleValue($user_value);
    
            $pb_account = new PB_Account();
            $pb_account->id = get_user_meta($instrutor_id, "pagseguro_account_id", true);

            $pb_receiver = new PB_Receiver();
            $pb_receiver->account = $pb_account;
            $pb_receiver->amount = $pb_amountValue;

            $pb_receiver->reason = "Valor referente ao atendimento codigoDoAtendimentoAqui";

            array_push($splits, $pb_receiver);
        }

        array_push($splits, $this->buildOlhiSeller($olhi_value));

        $pb_Splits = new PB_Splits();
        $pb_Splits->method = "FIXED";
        $pb_Splits->receivers = $splits;
        
        return $pb_Splits;
    }

    protected function buildOlhiSeller($value) {
        $pb_amountValue = new PB_AmountValue();
        $pb_amountValue->value = $value;

        $pb_account = new PB_Account();
        $pb_account->id = $this->get_option('account_id');

        $pb_receiver = new PB_Receiver();
        $pb_receiver->account = $pb_account;
        $pb_receiver->amount = $pb_amountValue;

        return $pb_receiver;
    }

    protected function buildEndpoint($endpoint) {
        if($this->isSandbox()) {
            $url = "https://sandbox.api.pagseguro.com";
        }
        else {
            $url = 'https://api.pagseguro.com';
        }
        
        return $url . $endpoint;
    }

    protected function getToken() {
        if($this->isSandbox()) {
            return $this->get_option('token_teste');
        }

        return $this->get_option('token');
    }

    protected function getRedirectURL() {
        if($this->isSandbox()) {
            return $this->get_option('redirect_uri_teste');
        }

        return $this->get_option('redirect_uri');
    }

    protected function getUserEmail($email) {
        $pagbank_teste = $this->get_option('comprador_teste');
        if($pagbank_teste === "yes") {
            return $this->get_option('comprador_teste');
        }

        return $email;
    }

    protected function handleValue($value) {
        // TODO: Vamos ter problemas quando vier um numero do tipo "1.81" ou "330" (3.30 e não 3300)
        $onlyNumber = preg_replace("/[^0-9]/", "", $value);
        return intval(str_pad($onlyNumber, 4, '0', STR_PAD_RIGHT));
    }

    protected function generatePublicKey() {
        $url = $this->buildEndpoint("/public-keys");
        $token = $this->getToken();

        $body = array(
            "type" => 'card'
        );

        $args = array(
            "method" => "POST",
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body)
        );
            
        $response = wp_remote_request($url, $args);

        $responseBody = wp_remote_retrieve_body( $response );

        $json = json_decode($responseBody);

        $this->update_option($this->isSandbox() ? 'ps_public_key_teste' : 'ps_public_key', $json->public_key);
    }

    protected function getPublicKey() {
        if($this->isSandbox()) {
            $public_key = $this->get_option('ps_public_key_teste');
        }
        else {
            $public_key = $this->get_option('ps_public_key');
        }

        if($public_key) {
            return $public_key;
        }

        return "INVALID_PUBLIC_KEY";
    }

    protected function isSandbox() {
        return $this->get_option('pagbank_teste') === "yes";
    }
    
    protected function isSplitEnabled() {
        return $this->get_option('split_enabled') === "yes";
    }

    protected function cleanPSUser() {
        // Obtém todos os IDs de usuário
        $user_ids = get_users( array( 'fields' => 'ID' ) );
    
        if ( $user_ids ) {
            foreach ( $user_ids as $userId ) {
                delete_user_meta($userId, "pagseguro_access_token");
                delete_user_meta($userId, "pagseguro_refresh_token_token");
                delete_user_meta($userId, "pagseguro_token_type");
                delete_user_meta($userId, "pagseguro_expires_in");
                delete_user_meta($userId, "pagseguro_scope");
                delete_user_meta($userId, "pagseguro_account_id");
                delete_user_meta($userId, "pagseguro_code");
            }
        }
    }

    public function get_option($key) {
        if($this->id != 'olhips_cc') {
            $instance = new OlhiPS_Gateway_Common();
            $instance->id = 'olhips_cc';
            return $instance->get_option($key);
        }
        
        return parent::get_option($key);
    }

}
?>
 