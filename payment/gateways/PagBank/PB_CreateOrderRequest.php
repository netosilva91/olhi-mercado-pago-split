<?php

Class PB_CreateOrderRequest {
    public $customer;
    public $reference_id;
    public $items;
    public $charges; // array
    public $qr_codes; // array
    public $notification_urls; // array
}

class PB_Customer { 
    public $name;
    public $email;
    public $tax_id; // cpf
}

class PB_item {
    public $name;
    public $unit_amount;
    public $quantity;
}

class PB_Card_Holder {
    public $name;
}

class PB_Card {
    public $holder;
    // public $encrypted; // verificar se devemos usar
    public $number;
    public $exp_month;
    public $exp_year;
    public $security_code;
    public $store;
}

class PB_Amount {
    public $value;
    public $currency;
}

class PB_PaymentMethod {
    public $card;
    public $type;
    public $installments;
    public $capture; // Parâmetro que indica se uma transação de cartão de crédito deve ser apenas pré-autorizada
    public $soft_descriptor; // Nome na Fatura do cliente. ⚠️ Aplicável no momento apenas para Cartão de crédito
}

class PB_Charge {
    public $amount;
    public $payment_method;
    public $reference_id;
    public $description;
    public $splits;
}

class PB_QRCodes {
    public $amount;
    // public $expiration_date; @default is 24h
    public $splits;
}

class PB_Splits {
    public $method;
    public $receivers;
}

class PB_Receiver {
    public $account;
    public $amount;
    public $reason;
}

class PB_Account {
    public $id;
}

class PB_AmountValue {
    public $value;
}


?>
