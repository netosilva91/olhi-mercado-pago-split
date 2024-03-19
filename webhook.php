<?php

function pagbank_webhook( $data ) {

    $conteudo = $data->get_body();

    $nome_arquivo = date( 'Y-m-d_H-i-s' ) . '.json';

    $caminho_arquivo = ABSPATH . 'wp-content/uploads/logs/'.$nome_arquivo;
    
    // Crie o arquivo no servidor
    if(!empty( $conteudo ) ) {
        file_put_contents( $caminho_arquivo, $conteudo );

        $resp = json_decode($conteudo);
        if($resp->reference_id) {
            $order_id = $resp->reference_id;
            $order = wc_get_order($order_id);

            if($order) {

                $payment_method = $order->get_payment_method();

                add_post_meta($order_id, $payment_method."_payment_result", json_encode($response), false);

                $status = $resp->charges[0]->status;

                if($status == 'DECLINED') {
                    $order->update_status( 'failed' );
                }
                else if($status == 'AUTHORIZED' || $status == 'PAID') {
                    $order->update_status( 'completed' );
                    $order->payment_complete();
                }
                else if($status == 'IN_ANALYSIS') {
                    $order->update_status( 'pending_payment' );
                }
            }
        }

    }
    else {
        file_put_contents( $caminho_arquivo, "sem nada" );
    }

    return rest_ensure_response( null, 200 );
}

function pagbank_webhook_test( $data ) {
    return rest_ensure_response( "webhook test", 200 );
}

// Enviar esta url ao criar um novo pedido.
// https://desenvolvimento.olhi.com.br/wp-json/pagbank/v1/webhook/
add_action( 'rest_api_init', function () {
    register_rest_route( 'pagbank/v1', '/webhook', array(
        'methods'  => 'POST',
        'callback' => 'pagbank_webhook',
        'permission_callback' => '__return_true',
    ));

    register_rest_route( 'pagbank/v1', '/webhook', array(
        'methods'  => 'GET',
        'callback' => 'pagbank_webhook_test',
        'permission_callback' => '__return_true',
    ));
});

?>
