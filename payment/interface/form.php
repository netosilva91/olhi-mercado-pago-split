<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);
?>

<!--Initialize PagSeguro payment form fieldset with tabs-->
<fieldset id="ps-connect-payment-cc" class="ps_connect_method" style="<?php esc_attr_e($style['cc'], 'pagbank-connect');?>">
    <input type="hidden" name="ps_connect_method" value="cc"/>
    <?php require_once WC_OLHI_PS_BASE_DIR . "/payment/interface/creditcard.php"; ?>
</fieldset>
