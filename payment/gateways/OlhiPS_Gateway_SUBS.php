<?php
require_once("OlhiPS_Gateway_Common.php");
// require_once("helpers/OlhiPS_Gateway_Common_Subs.php");
require_once(WC_OLHI_PS_BASE_DIR . "/includes/utils.php");

class OlhiPS_Gateway_SUBS extends OlhiPS_Gateway_Common {

    public function __construct() {
        $this->id = 'olhips_subs';
        $this->icon = ''; // URL de um ícone que você deseja usar
        $this->has_fields = true; // false se você não tem campos personalizados
        $this->method_title = 'Olhi - Método de Pagamento A';
        $this->method_description = 'Método de Pagamento da Olhi A';

        // Load the settings.
        $this->init_settings();
        $this->init_form_fields();

        // Define user set variables
        $this->title = "Cartão de crédito";
        $this->description = "descriptionOlhi";

        $this->supports = [
            'products',
            'refunds',
            'default_credit_card_form',
		];

        if (!$this->has_valid_product_type()) {
            $this->enabled = false;
        }

        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    private function has_valid_product_type() {
        if (!is_admin()) {
            $cart = WC()->cart;
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['data']->get_type() === 'plano') {
                    return true;
                }
            }
            return false;
        }
    }

    public function form() {
        include WC_OLHI_PS_BASE_DIR . '/payment/interface/form.php';
    }

    public function init_form_fields() {

        $application = $this->get_option('application-connected');

        echo "<pre>";var_dump($application);echo "</pre>";

        if($application === true) {
            $this->form_fields = array(
                'split_enabled' => array(
                    'title' => 'Habilitar Split de Pagamento',
                    'type' => 'checkbox',
                    'label' => 'Habilitar Split de Pagamento',
                    'default' => 'yes'
                ),
                'desvincular' => array(
                    'title' => 'Desvincular PagBank',
                    'type' => 'checkbox',
                    'label' => 'Desvincular aplicação do PagSeguro',
                    'default' => 'no'
                ),
            );

            return;
        }

        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Habilitar/Desabilitar',
                'type' => 'checkbox',
                'label' => 'Habilitar Meu Método de Pagamento',
                'default' => 'yes'
            ),
            'pagbank_teste' => array(
                'title' => 'Ambiente de teste',
                'type' => 'checkbox',
                'label' => 'Sandbox',
                'default' => 'yes'
            ),
            'token_teste' => array(
                'title' => 'Token SandBox',
                'type' => 'text',
                'description' => 'Token informado no sandbox: https://sandbox.pagseguro.uol.com.br/vendedor/configuracoes.html',
            ),
            'redirect_uri_teste' => array(
                'title' => 'Página de Redirecionamento para o Sandbox',
                'type' => 'text',
                'description' => 'Página do seu site para onde o PagBank vai direcionar o usuário.',
            ),
            'comprador_teste' => array(
                'title' => 'Comprador SandBox',
                'type' => 'text',
                'description' => 'Comprador de teste informado no sandobox: https://sandbox.pagseguro.uol.com.br/comprador-de-testes.html',
            ),
            'comprador_senha_teste' => array(
                'title' => 'Senha Comprador SandBox',
                'type' => 'text',
                'description' => 'Senha Comprador de teste informado no sandobox: https://sandbox.pagseguro.uol.com.br/comprador-de-testes.html',
            ),
            'token' => array(
                'title' => 'Token PagBank',
                'type' => 'text',
                'description' => 'Token informado na sua conta do PagBank',
            ),
            'redirect_uri' => array(
                'title' => 'Página de Redirecionamento',
                'type' => 'text',
                'description' => 'Página do seu site para onde o PagBank vai direcionar o usuário.',
            ),
            'logo' => array (
                'title' => 'Logo',
                'type' => 'text',
                'description' => 'URL do logo que será exibido para o Vendedor nas telas do PagBank',
            )
        );

    }

    public function generate_settings_html( $form_fields = array(), $echo = true ) {
        $application = $this->get_option('application-connected');

        if($application === true) {

            $pagbank_teste = $this->get_option('pagbank_teste');

            ?>
            <table>
                <tr valign="top">
                    <td colspan="2"><span style="color: green; font-weight: bold; font-size: 2rem;">Aplicação conectada ao PagBank!</span></td>
                </tr>

                <?php if($this->isSandbox()) { ?>

                    <tr valign="top">
                        <td colspan="2"><br><br></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><span style="color: red; font-weight: bold; font-size: 1rem;">Ambiente de teste (SANDBOX)</span></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><br><br></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Token:</b> <?php echo $this->getToken(); ?></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Public Key:</b> <?php echo $this->getPublicKey(); ?></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Client id:</b> <?php echo $this->get_option('client_id'); ?></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Client Secret:</b> <?php echo $this->get_option('client_secret'); ?></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Account ID:</b> <?php echo $this->get_option('account_id'); ?></td>
                    </tr>

                <?php } else { ?>

                    <tr valign="top">
                        <td colspan="2"><span style="color: orange; font-weight: bold; font-size: 1rem;">Ambiente de PRODUÇÃO</span></td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Token:</b> <?php echo substr($this->getToken(), 0, 10); ?> (apenas inicio do token)</td>
                    </tr>

                    <tr valign="top">
                        <td colspan="2"><b>Client id:</b> <?php echo $this->get_option('client_id'); ?></td>
                    </tr>

                <?php } ?>

            </table>
            <?php
        }

        return parent::generate_settings_html( $form_fields, false );
    }

    public function process_admin_options() {
        parent::process_admin_options();

        $application = $this->get_option('application-connected');

        if($application !== true) {
            $token = $this->getToken();
            $redirect_uri = $this->getRedirectURL();
            $logo = $this->get_option('logo');

            if($token && $redirect_uri && $logo) {
                $response = $this->olhiPS_createApplication();
                $responseJson = json_decode($response, true);
                $this->update_option('application-connected', true);
                $this->update_option('client_id', $responseJson["client_id"]);
                $this->update_option('client_secret', $responseJson["client_secret"]);
                $this->update_option('account_id', $responseJson["account_id"]);
            }
        }
        else {
            $desvincular = $this->get_option('desvincular');
            // TODO: ONLY ON SANDBOX, OTHERWISE, A NEW APPLICATION WILL BE CREATED ON PROD

            if($desvincular === "yes") {
                $this->update_option('application-connected', null);
                $this->update_option('client_id', null);
                $this->update_option('client_secret', null);
                $this->update_option('account_id', null);
                $this->update_option('desvincular', null);
                $this->update_option('split_enabled',  "yes");

                $this->cleanPSUser();
            }
            else {
                // When saving the configuration, a new public key will bee generated
                $this->generatePublicKey();
            }
        }
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $pedido = $this->olhiPS_createOrderObject($order);

        $response = $this->olhiPS_createOrder($pedido, $order_id);

        add_post_meta($order_id, $this->id."_payment_result", json_encode($response), false);

        // die($response);

        // $response["charges"]

        $order->update_status( 'completed' );

        $order->payment_complete();

        // Redireciona para a página de agradecimento
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }

    private function olhiPS_createApplication() {

        $token = $this->getToken();
        $redirect_uri = $this->getRedirectURL();
        $logo = $this->get_option('logo');
    
        // URL da API do PagSeguro
        $url = $this->buildEndpoint("/oauth2/application");
    
        // Dados a serem enviados
        $data = json_encode(array(
            "name" => "Olhi - Integração",
            "description" => "Plataforma de atendimento",
            "site" => "https://desenvolvimento.olhi.com.br",
            "redirect_uri" => $redirect_uri,
            "logo" => $logo
        ));
    
        // Inicializa cURL
        $ch = curl_init($url);
    
        // Configurações do cURL para POST
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
        // Configura os cabeçalhos
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token, // Substitua <token> pelo seu token
            'Accept: application/json',
            'Content-Type: application/json'
        ));
    
        $response = curl_exec($ch);
    
        if(curl_errno($ch)) {
            echo 'Erro na requisição: ' . curl_error($ch);
        }
    
        curl_close($ch);
    
        return $response;
    }

}
?>
 