<?php

function olhiPS_consultApplication($clientId) {
    $curl = curl_init();

    global $bearer, $client_secret, $client_id, $redirect_uri;

    curl_setopt_array($curl, [
      CURLOPT_URL => "https://api.pagseguro.com/oauth2/application/" . $clientId . "",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Authorization: Bearer ". $bearer,
        "accept: application/json"
      ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    
    curl_close($curl);
    
    if ($err) {
      echo "cURL Error #:" . $err;
    } else {
      echo $response;
    }
    
    die("executei");
}

/**
 * API: https://dev.pagbank.uol.com.br/v2.2/reference/solicitar-autorizacao-via-connect-authorization
 */
function olhiPS_getSellerConnectionLink($clientId) {
    $options = get_option("woocommerce_olhips_cc_settings");
    $pagbank_teste = $options["pagbank_teste"];

    if($pagbank_teste == "yes") {
        $url = "https://connect.sandbox.pagseguro.uol.com.br/oauth2/authorize?client_id=".$clientId;
        $redirect_uri = $options["redirect_uri_teste"];
    }
    else {
        $url = "https://connect.pagseguro.uol.com.br/oauth2/authorize?client_id=".$clientId;
        $redirect_uri = $options["redirect_uri"];
    }
    
    // scopes: https://dev.pagbank.uol.com.br/reference/solicitar-autorizacao-via-connect-authorization

    return $url."&response_type=code&redirect_uri=" . $redirect_uri . "&scope=payments.read payments.create payments.refund accounts.read payments.split.read";
}

function olhiPS_getSellerAccessToken($code) {

    $options = get_option("woocommerce_olhips_cc_settings");
    $pagbank_teste = $options["pagbank_teste"];
    $client_id = $options["client_id"];
    $client_secret = $options["client_secret"];

    if($pagbank_teste == "yes") {
        $url = "https://sandbox.api.pagseguro.com/oauth2/token";
        $bearer = $options["token_teste"];
        $redirect_uri = $options["redirect_uri_teste"];
    }
    else {
        $url = "https://api.pagseguro.com/oauth2/token";
        $bearer = $options["token"];
        $redirect_uri = $options["redirect_uri"];
    }

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer ". $bearer,
            "accept: application/json",
            "content-type: application/json",
            "X_CLIENT_ID: " . $client_id,
            "X_CLIENT_SECRET: " . $client_secret
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri,
        ]),
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return $err;
    }

    return $response;
}
