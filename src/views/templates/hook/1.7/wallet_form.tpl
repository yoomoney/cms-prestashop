{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    Yandex.Money <cms@yamoney.ru>
* @copyright © 2015-2017 NBCO Yandex.Money LLC
* @license   https://money.yandex.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   Yandex Payment Solution
*}

<form method="post" action="{$action|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="code" value="1" />
    <input type="hidden" name="cnf" value="1" />
    <fieldset class="form-group">
        <legend>{$label|escape:'htmlall':'UTF-8'}</legend>
        <div class="form-check">
            <label for="yandex_money_wallet" class="form-check-label">
                <input type="radio" class="form-check-input" name="payment_method" id="yandex_money_wallet" value="yandex_money_wallet" />
                <img src="{$image_dir|escape:'htmlall':'UTF-8'}yandex_money.png" />
                {l s='Оплата через Яндекс кошелёк' mod='yandexmodule'}
            </label><br />
            <label for="yandex_money_card" class="form-check-label">
                <input type="radio" class="form-check-input" name="payment_method" id="yandex_money_card" value="yandex_money_card" />
                <img src="{$image_dir|escape:'htmlall':'UTF-8'}bank_card.png" />
                {l s='Оплата банковской картой' mod='yandexmodule'}
            </label>
        </div>
    </fieldset>
</form>