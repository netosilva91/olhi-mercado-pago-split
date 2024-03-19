<?php
function enqueue_scripts() {
    wp_enqueue_script(
        'pagseguro-sdk',
        'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js',
        array(),
        '1.0',
        true
    );

    wp_enqueue_script(
        'olhi_ps',
        plugins_url( 'public/olhi_ps.js', __FILE__ ),
        array( 'pagseguro-sdk' ),
        '1.0',
        true
    );
}

add_action('wp_enqueue_scripts', 'enqueue_scripts', 9999);
?>
