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

{foreach from=$payment_methods item=method}
<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class='yandex_money_{$method.value|escape:'htmlall':'UTF-8'} yandex_money_payment' href="javascript://" data-value="{$method.value|escape:'htmlall':'UTF-8'}" title="{l s='Yandex.Money' mod='yandexmodule'}">
                {$method.name|escape:'htmlall':'UTF-8'}
            </a>
        </p>
    </div>
</div>
{if $method.value == 'qiwi'}
<div class="row additional-fields" style="display: none;margin-bottom: 10px;" id="qiwi-phone-container">
    <div class="col-xs-12">
        <label for="qiwi-phone">{l s='Phone number linked to QIWI Wallet' mod='yandexmodule'}</label>
        <input type="text" id="qiwi-phone" value="" />
        <button type="button" data-value="{$method.value|escape:'htmlall':'UTF-8'}">{l s='Pay' mod='yandexmodule'}</button>
    </div>
</div>
{/if}
{if $method.value == 'alfabank'}
<div class="row additional-fields" style="display: none;margin-bottom: 10px;" id="alfa-login-container">
    <div class="col-xs-12">
        <label for="alfa-login">{l s='Specify the login, and we\'ll send the bill in Alfa-Click. All you have do after that is confirm the payment online at the bank\'s website.' mod='yandexmodule'}</label>
        <input type="text" id="alfa-login" value="" />
        <button type="button" data-value="{$method.value|escape:'htmlall':'UTF-8'}">{l s='Pay' mod='yandexmodule'}</button>
    </div>
</div>
{/if}
{/foreach}
<form method="post" action="{$action|escape:'htmlall':'UTF-8'}" style="display: none;" id="ym-form-payment">
    <input type="hidden" class="form-check-input" name="payment_method" id="ym-form-payment-type" value="" />
    <input type="hidden" class="form-check-input" name="qiwiPhone" id="ym-form-qiwi-phone" value="" />
    <input type="hidden" class="form-check-input" name="alfaLogin" id="ym-form-alfa-login" value="" />
</form>


<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery('.yandex_money_payment').bind('click', function () {
            if (this.dataset.value === 'qiwi') {
                jQuery('.additional-fields').css('display', 'none');
                jQuery('#qiwi-phone-container').css('display', 'block');
            } else if (this.dataset.value === 'alfabank') {
                jQuery('.additional-fields').css('display', 'none');
                jQuery('#alfa-login-container').css('display', 'block');
            } else {
                jQuery('#ym-form-payment-type').val(this.dataset.value);
                jQuery('#ym-form-payment')[0].submit();
            }
        });

        jQuery('.additional-fields button').click(function () {
            var form = document.getElementById('ym-form-payment');
            if (this.dataset.value === 'qiwi') {
                form.qiwiPhone.value = document.getElementById('qiwi-phone').value;
            }
            if (this.dataset.value === 'alfabank') {
                form.alfaLogin.value = document.getElementById('alfa-login').value;
            }
            form.payment_method.value = this.dataset.value;
            form.submit();
        });
    });
</script>