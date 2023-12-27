<?php
class OlhiMercadoPagoSplitGateway extends WC_Payment_Gateway
{
   // Configurações do método de pagamento
   public function __construct() {
    $this->id = "olhi-mercado-pago-split";
    $this->method_title = "Olhi - Mercado Pago Split de Pagamento";
    $this->method_description = "Este é um método de pagamento personalizado.";

    // Configurações de taxas
    $this->supports = array(
      "products",
      "refunds",
    );
    $this->has_fields = false;

    // Configurações de URL
    // $this->notify_url = "";
    // $this->return_url = "";

    // Inicialize o método de pagamento
    // parent::__construct();
  }

  // Processe o pagamento
  public function process_payment( $order_id ) {
    // Colete as informações de pagamento do cliente
    $order = wc_get_order( $order_id );
    $amount = $order->get_total();

    // Processe o pagamento
    // ...

    // Atualize o status do pedido
    $order->update_status( "completed" );

    // Redirecione o cliente para a página de sucesso
    wp_redirect( wc_get_page_permalink( "thankyou" ) );
    exit;
  }
}