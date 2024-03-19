function PS_getMonth() {
    const expire = document.querySelector('#olhips_cc-card-expiry').value
    return onlyNumber(expire.split("/")[0]);
}

function PS_getYear() {
    const expire = document.querySelector('#olhips_cc-card-expiry').value
    const expireNumber = "20" + onlyNumber(expire.split("/")[1]);
    return expireNumber;
}

function PS_getCardNumber() {
    const cardNumber = document.querySelector('#olhips_cc-card-number').value;
    return onlyNumber(cardNumber);
}

function onlyNumber(value) {
    return value.replace(/\s/g, '').replace(/[^\d]/g, '');
}

function encryptCard() {

    var card = PagSeguro.encryptCard({
        publicKey: document.querySelector('#olhiPK').value,
        holder: document.querySelector('#olhips_cc-card-holder-name').value,
        number: PS_getCardNumber(),
        expMonth: PS_getMonth(),
        expYear: PS_getYear(),
        securityCode: document.querySelector('#olhips_cc-card-cvc').value
    });
    
    const encrypted = card.encryptedCard;
    const hasErrors = card.hasErrors;
    const errors = card.errors;


    // TODO: Handle in case "errors"
    document.querySelector('#olhips_cc-card-encrypted').value = encrypted;
}

document.body.addEventListener('click', function(event) {
    if (event.target && event.target.id === 'place_order') {
        encryptCard();
    }
});
