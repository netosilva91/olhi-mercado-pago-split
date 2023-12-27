<?php 
/*
Plugin Name: Olhi - Mercado Pago Split de Pagamento
Description: Adiciona a funcionalidade de split de pagamento.
Requires at least: 1.0
Requires PHP: 7.0
Requires: woocommerce\woocommerce.php
Version: 1.0
Author: José Neto da Costa Silva
*/

defined('ABSPATH') || exit;

add_action('plugins_loaded', function () {
  include_once __DIR__ . "/includes/olhi-mercado-pago-split-gateway.php";
});

add_filter('woocommerce_payment_gateways', function ($methods) {
  $methods[] = 'OlhiMercadoPagoSplitGateway';
  return $methods;
});