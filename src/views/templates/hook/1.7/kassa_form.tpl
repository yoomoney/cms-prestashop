{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    YooMoney <cms@yoomoney.ru>
* @copyright © 2020 "YooMoney", NBСO LLC
* @license   https://yoomoney.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   YooMoney Payment Solution
*
* @var KassaModel $model
*}
<form method="post" action="{$action|escape:'htmlall':'UTF-8'}" id="yoomoney-form">
    <script src="https://yookassa.ru/checkout-ui/v2.js"></script>
    <fieldset class="form-group">
        <legend>{$label|escape:'htmlall':'UTF-8'}</legend>
        {foreach from=$payment_methods item=method}
            <div class="form-check">
                <label for="{$method.id|escape:'htmlall':'UTF-8'}" class="form-check-label">
                    <input type="radio" class="form-check-input" name="payment_method" id="{$method.id|escape:'htmlall':'UTF-8'}" value="{$method.value|escape:'htmlall':'UTF-8'}" onchange="onChangePaymentMethod();" />
                    <img src="{$image_dir|escape:'htmlall':'UTF-8'}{$method.value|escape:'htmlall':'UTF-8'}.png" />
                    {$method.name|escape:'htmlall':'UTF-8'}
                </label>
            </div>
            {if $method.value == 'alfabank'}
                <div id="alfa-login-container" class="form-group additional-fields" style="display: none;">
                    <label for="alfa-login">{l s='Specify the login, and we\'ll send the bill in Alfa-Click. All you have do after that is confirm the payment online at the bank\'s website.' mod='yoomoneymodule'}</label>
                    <input type="text" name="alfaLogin" id="alfa-login" value="" />
                </div>
            {/if}
            {if $method.value == 'qiwi'}
                <div id="qiwi-phone-container" class="form-group additional-fields" style="display: none;">
                    <label for="qiwi-phone">{l s='Phone number linked to QIWI Wallet' mod='yoomoneymodule'}</label>
                    <input type="text" name="qiwiPhone" id="qiwi-phone" value="" />
                </div>
            {/if}
        {/foreach}
    </fieldset>
    <div id="payment-form-widget"></div>
</form>
<script type="text/javascript">

var form = document.getElementById('yoomoney-form');
function onChangePaymentMethod() {
    var alfa = document.getElementById('alfa-login-container');
    var qiwi = document.getElementById('qiwi-phone-container');

    var paymentMethod = form.payment_method.value;

    if (alfa) {
        alfa.style.display = 'none';
    }
    if (qiwi) {
        qiwi.style.display = 'none';
    }

    if (paymentMethod === 'qiwi') {
        qiwi.style.display = 'block';
    }

    if (paymentMethod === 'alfabank') {
        alfa.style.display = 'block';
    }
}

form.onsubmit = function (e) {
    var paymentMethod = form.payment_method.value;
    if (paymentMethod !== 'widget') {
        return;
    }
    e.preventDefault();
    var request = new XMLHttpRequest();
    request.open("POST", "{$action|escape:'htmlall':'UTF-8'}", true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.responseType = "json";
    request.addEventListener("readystatechange", () => {
        if (request.readyState === 4 && request.status === 200) {
            let response = request.response;
            initWidget(response);
        }
    });
    request.send('payment_method=' + paymentMethod);
}

function initWidget(data) {
    console.log(data);
    const checkout = new window.YooMoneyCheckoutWidget({
        confirmation_token: data.confirmation_token,
        return_url: data.return_url,
        embedded_3ds: true,
        newDesign: true,
        error_callback(error) {
            console.log(error);
            window.location.redirect(data.return_url);
        }
    });

    checkout.render('payment-form-widget');
}
</script>