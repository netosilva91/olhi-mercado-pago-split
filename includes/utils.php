<?php
function getPlanTax($plano) {

    $taxa = 0;

    return 15;

    switch ($plano) {
        case 'basico':
            $taxa = 20;
            break;
        
        case 'premium':
            $taxa = 10;
            break;

        case 'olhi':
            $taxa = 50;
            break;

        default:
            $taxa = 0;
            break;
    }

    return $taxa;
}

function calculateUserValue($plano, $price) {
    $tax = getPlanTax($plano);
    return $price - $price * ($tax / 100);
}

// @see includes/webhook.php
function getWebHookUrl() {
    return "https://desenvolvimento.olhi.com.br/wp-json/pagbank/v1/webhook/";
}

function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';

    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $random_string;
}

?>
