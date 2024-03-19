<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

add_action('plugins_loaded', 'init_olhips_pagamento', 999);

function init_olhips_pagamento() {
    require_once(WC_OLHI_PS_BASE_DIR . "/payment/gateways/OlhiPS_Gateway_CC.php");
    // require_once(WC_OLHI_PS_BASE_DIR . "/payment/gateways/OlhiPS_Gateway_SUBS.php");
    require_once(WC_OLHI_PS_BASE_DIR . "/payment/gateways/OlhiPS_Gateway_PIX.php");
}

add_filter('woocommerce_payment_gateways', 'add_olhips_pagamento');

function add_olhips_pagamento($methods) {
    $methods[] = 'OlhiPS_Gateway_CC';
    $methods[] = 'OlhiPS_Gateway_PIX';
    // $methods[] = 'OlhiPS_Gateway_SUBS';
    return $methods;
}
?>
