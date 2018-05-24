{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    Yandex.Money <cms@yamoney.ru>
* @copyright © 2015-2017 NBCO Yandex.Money LLC
* @license   https://money.yandex.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   Yandex Payment Solution
*
* @var KassaModel $model
*}
<form method="post" action="{$action|escape:'htmlall':'UTF-8'}" id="ya-form">
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
                    <label for="alfa-login">{l s='Specify the login, and we\'ll send the bill in Alfa-Click. All you have do after that is confirm the payment online at the bank\'s website.' mod='yandexmodule'}</label>
                    <input type="text" name="alfaLogin" id="alfa-login" value="" />
                </div>
            {/if}
            {if $method.value == 'qiwi'}
                <div id="qiwi-phone-container" class="form-group additional-fields" style="display: none;">
                    <label for="qiwi-phone">{l s='Phone number linked to QIWI Wallet' mod='yandexmodule'}</label>
                    <input type="text" name="qiwiPhone" id="qiwi-phone" value="" />
                </div>
            {/if}
        {/foreach}
    </fieldset>
</form>

<script type="text/javascript">

function onChangePaymentMethod() {
    var form = document.getElementById('ya-form');
    var alfa = document.getElementById('alfa-login-container');
    var qiwi = document.getElementById('qiwi-phone-container');

    var paymentMethod = form.payment_method.value;

    alfa.style.display = 'none';
    qiwi.style.display = 'none';
    if (paymentMethod === 'qiwi') {
        qiwi.style.display = 'block';
    }
    if (paymentMethod === 'alfabank') {
        alfa.style.display = 'block';
    }
}

</script>