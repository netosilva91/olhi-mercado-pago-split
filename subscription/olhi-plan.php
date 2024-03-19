<?php
// Registra o tipo de produto "Plano"
add_action('init', 'registrar_tipo_produto_plano');
function registrar_tipo_produto_plano() {
    class WC_Product_Plano extends WC_Product {
        public function __construct($product = 0) {
            $this->product_type = 'plano';
            parent::__construct($product);
        }
    }

    // Adiciona suporte ao tipo de produto "Plano"
    add_filter('woocommerce_product_class', 'adicionar_suporte_tipo_produto_plano', 10, 2);
    function adicionar_suporte_tipo_produto_plano($classname, $product_type) {
        if ($product_type === 'plano') {
            $classname = 'WC_Product_Plano';
        }
        return $classname;
    }
}

// Adiciona "Plano" ao seletor de tipos de produto
add_filter('product_type_selector', 'adicionar_tipo_produto_plano_selector');
function adicionar_tipo_produto_plano_selector($types) {
    // Adiciona "plano" como um tipo de produto
    $types['plano'] = __('Plano');
    return $types;
}

/**
 * Adicionar opções ao produto plano
 */
add_action('woocommerce_product_options_general_product_data', 'adicionar_opcoes_produto_plano');
function adicionar_opcoes_produto_plano() {
    woocommerce_wp_select([
        'id' => '_periodo_cobranca',
        'label' => __('Período de Cobrança', 'meu-plano-woocommerce'),
        'description' => __('Define o período de cobrança para este plano.', 'meu-plano-woocommerce'),
        'options' => [
            'semanal' => __('Semanal', 'meu-plano-woocommerce'),
            'mensal' => __('Mensal', 'meu-plano-woocommerce'),
            'anual' => __('Anual', 'meu-plano-woocommerce'),
        ],
        'desc_tip' => true
    ]);

    woocommerce_wp_text_input(
        array(
            'id'          => '_porcentagem_comissao',
            'label'       => __('Porcentagem Olhi', 'meu-plano-woocommerce'),
            'desc_tip'    => true,
            'description' => __('Define a porcentagem que a Olhi irá receber em cada venda.', 'meu-plano-woocommerce'),
            'type'        => 'number', // Define o tipo do campo como número
            'custom_attributes' => array(
                'pattern' => '\d*', // Permite apenas dígitos
            ),
        )
    );

    
}

function mostrar_esconder_campo_período_cobranca() {
  ?>
  <script type='text/javascript'>
      jQuery(document).ready(function($) {
            var mostrarEsconderCampo = function() {
                if ($('#product-type').val() == 'plano') {
                    $('._periodo_cobranca_field').show();
                    $('._porcentagem_comissao_field').show();
                    
                    $('.show_if_evento_product').hide();
                    $('._custom_product_calendar_field_field').hide();

                    setTimeout(() => {
                        $("#general_product_data .product_custom_field").hide();
                        $(".evento_tab ").hide();
                    }, 200);

                    $('#product_catdiv').hide();
                    $('#product_sobre_min').hide();
                    $('#product_ensinar_sobre').hide();
                    $('#product_quero_ajudar').hide();
                    $('#product_disponibilidade').hide();
                    $('#cpappb_woocommerce_metabox').hide();
                } else {
                    $('._periodo_cobranca_field').hide();
                    $('._porcentagem_comissao_field').hide();

                    $('.show_if_evento_product').show();
                    $('._custom_product_calendar_field_field').show();
                    $('#product_catdiv').show();
                    $('#product_sobre_min').show();
                    $('#product_ensinar_sobre').show();
                    $('#product_quero_ajudar').show();
                    $('#product_disponibilidade').show();
                    $('#cpappb_woocommerce_metabox').show();
                }
            };

            mostrarEsconderCampo();
            
            $('#product-type').change(mostrarEsconderCampo);
        });
  </script>
  <?php
}

add_action('admin_footer', 'mostrar_esconder_campo_período_cobranca');

/**
 * Salvar as opções personalizadas
 */
add_action('woocommerce_process_product_meta', 'salvar_opcoes_produto_plano');
function salvar_opcoes_produto_plano($post_id) {
    $periodo_cobranca = isset($_POST['_periodo_cobranca']) ? $_POST['_periodo_cobranca'] : '';
    $porcentagem_comissao = isset($_POST['_porcentagem_comissao']) ? $_POST['_porcentagem_comissao'] : '';
    update_post_meta($post_id, '_periodo_cobranca', sanitize_text_field($periodo_cobranca));
    update_post_meta($post_id, '_porcentagem_comissao', sanitize_text_field($porcentagem_comissao));    
}

/**
 * Manipular a exclusividade de compra do plano
 */
add_filter('woocommerce_add_cart_item_data', 'plano_compra_unica', 10, 2);
function plano_compra_unica($cart_item_data, $product_id) {
    if (has_term('plano', 'product_cat', $product_id)) {
        WC()->cart->empty_cart();
    }
    return $cart_item_data;
}

/**
 * Vincular plano ao usuário após a compra
 */
add_action('woocommerce_order_status_completed', 'vincular_plano_ao_usuario');
function vincular_plano_ao_usuario($order_id) {
    $order = wc_get_order($order_id);
    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        if (has_term('plano', 'product_cat', $product_id)) {
            update_user_meta($order->get_customer_id(), '_plano_comprado', $product_id);
        }
    }
}

/**
 * Shortcode para exibir o plano do usuário
 */
function exibir_trocar_plano_usuario() {
  $user_id = get_current_user_id();
  $plano_id = get_user_meta($user_id, '_plano_comprado', true);
  if ($plano_id) {
      $produto = wc_get_product($plano_id);
      $nome_plano = $produto->get_name();
      $periodo_cobranca = get_post_meta($plano_id, '_periodo_cobranca', true);
      return "Seu plano atual: $nome_plano - $periodo_cobranca";
  } else {
      return "Você não tem um plano ativo no momento.";
  }
}

add_shortcode('exibir_trocar_plano', 'exibir_trocar_plano_usuario');