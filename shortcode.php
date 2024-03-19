<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once(WC_OLHI_PS_BASE_DIR . "/includes/application.php");

function pagbank_link_func() {
    $options = get_option("woocommerce_olhips_cc_settings");
    $client_id = $options["client_id"];
    $pagbank_teste = $options["pagbank_teste"];
    $split_enabled = $options["split_enabled"];

    if($split_enabled != "yes") {
        return "";
    }

    // WTF?
    // if($pagbank_teste == "yes") {
        // update_user_meta($userId, "pagseguro_access_token", "729B7435C13D44C5A4F90071D8ED9AB9");
        // 1df4351f-8347-4c7f-bf17-551c8dc9b4ac71b90fb54bd2a637b366eeef73d3a487b305-dc4d-4119-96d9-7dda13b94484
    // }

    $userId = get_current_user_id();
    $access_token = get_user_meta($userId, "pagseguro_access_token", true);

    if($pagbank_teste == "yes" && $access_token) {

        echo "<br>";
        echo "access token: ". $access_token;
        echo "<br>";
        echo "<br>";

        $account_id = get_user_meta($userId, "pagseguro_account_id", true);
        echo "account_id: ".$account_id;
        echo "<br>";
        echo "<br>";
        return "Vinculado ao PagSeguro";
    }

    return "<a href='".olhiPS_getSellerConnectionLink($client_id)."'>Vincular PagSeguro</a>";
}

// Registre o shortcode
add_shortcode('pagbank_link', 'pagbank_link_func');


// Função para o shortcode
function ps_associateSellerApplication_func() {
    $userId = get_current_user_id();
    $code = get_user_meta($userId, "pagseguro_code", true);
    $responseObject = olhiPS_getSellerAccessToken($code);

    // TODO: Validar erros;    
    $response = json_decode($responseObject);

    $code = get_user_meta($userId, "pagseguro_code", true);
    delete_user_meta($userId, "pagseguro_code");

    update_user_meta($userId, "pagseguro_access_token", $response->access_token);
    update_user_meta($userId, "pagseguro_refresh_token_token", $response->refresh_token);
    update_user_meta($userId, "pagseguro_token_type", $response->token_type);
    update_user_meta($userId, "pagseguro_expires_in", $response->expires_in);
    update_user_meta($userId, "pagseguro_scope", $response->scope);
    update_user_meta($userId, "pagseguro_account_id", $response->account_id);
}

// Registre o shortcode
add_shortcode('pagbank_associate', 'ps_associateSellerApplication_func');



// Função para o shortcode
function ps_showQrCode_func($atts, $content = null) {

    $order = wc_get_order($atts["order"]);
	
    $order_status = $order->get_status();
    $payment_method = $order->get_payment_method();

    if($order_status == 'pending' && $payment_method == 'olhips_pix') {
        if(defined("WC_OLHI_PS_BASE_DIR")) {
            $meta = get_post_meta($order->get_id(), 'olhips_pix_payment_result');
            $lastUpdate = end($meta);
            $json = json_decode($lastUpdate);

            $text = $json->qr_codes[0]->text;
            $link = $json->qr_codes[0]->links[0]->href;
            ?>

            <style>
                .concluirCompra {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    padding: 1rem 1rem 0;
                    border-radius: 1rem;
                    color: #FE5735;
                    border: 1px solid #FE5735;
                    gap: 1rem;
                    margin-bottom: 2rem;
                    text-align: center;
                }

                .concluirCompra.text {
                    border: 1px solid #ccc !important;
                    background-color: #f5f5f5 !important;
                    font-size: 11px;
                    text-align: center;
                    margin-bottom: 1rem;
                }

                .concluirCompra img {
                    width: 300px;
                }
            </style>

            <div class='concluirCompra'>
                Realize o pagamento do QR Code abaixo para concluir a compra.

                <?php echo '<img src="' . $link . '" alt="QR Code" width="300px">'; ?>

                <hr>

                Ou copie o o código abaixo:

                <div class='concluirCompra text'>
                    <?php echo $text; ?>
                </div>
            </div>

            <?php
        }
    }

}

// Registre o shortcode
add_shortcode('ps_showQrCode', 'ps_showQrCode_func');

?>
