<?php
require_once("OlhiPS_Gateway_Common.php");
require_once("PagBank/PB_CreateOrderRequest.php");
require_once(WC_OLHI_PS_BASE_DIR . "/includes/utils.php");

class OlhiPS_Gateway_PIX extends OlhiPS_Gateway_Common {

    public function __construct() {
        $this->id = 'olhips_pix';
        $this->icon = ''; // URL de um ícone que você deseja usar
        $this->has_fields = false; // false se você não tem campos personalizados
        $this->method_title = 'Olhi - Método de Pagamento';
        $this->method_description = 'PIX';

        $this->title = "Pix";
        $this->description = "descriptionOlhi";

        $this->supports = [
            'products',
            'refunds',
            'default_credit_card_form',
		];

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function form() {
        include WC_OLHI_PS_BASE_DIR . '/payment/interface/pix.php';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $pedido = $this->olhiPS_createOrderObject($order);

        $response = $this->olhiPS_createOrder($pedido, $order_id);

        add_post_meta($order_id, $this->id."_payment_result", $response, false);

        $order->update_status( 'awaiting_payment' );

        // Redireciona para a página de agradecimento
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    protected function olhiPS_createOrderObject($order) {
        $pbOrder = new PB_CreateOrderRequest();
        $pbOrder->customer = $this->buildCustomer($order);
        $pbOrder->items = $this->buildItems($order);
        $pbOrder->qr_codes = $this->buildQRCode($order);
        $pbOrder->notification_urls = [getWebHookUrl()];
        return $pbOrder;
    }

}
?>
 