<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

/**
 * Plugin Name: Olhi PagSeguro
 * Description: Plugin de Pagamento (checkout transparent + split)
 * Version: 1.0
 * Author: Olhi / Natanael / Neto
 */

defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

define( 'WC_OLHI_PS_BASE_DIR', __DIR__ );

require_once("scripts.php");
require_once("wc-payment.php");
require_once("webhook.php");
require_once("subscription/olhi-plan.php");
require_once("shortcode.php");
