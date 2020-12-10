{**
* Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
*
* @author    YooMoney <cms@yoomoney.ru>
* @copyright © 2020 "YooMoney", NBСO LLC
* @license   https://yoomoney.ru/doc.xml?id=527052
*
* @category  Front Office Features
* @package   YooMoney Payment Solution
*}

<form method="post" action="{$action|escape:'htmlall':'UTF-8'}">
    <input type="hidden" name="code" value="1" />
    <input type="hidden" name="cnf" value="1" />
    <fieldset class="form-group">
        <legend>{$label|escape:'htmlall':'UTF-8'}</legend>
        <div class="form-check">
            <label for="yoomoney_wallet" class="form-check-label">
                <input type="radio" class="form-check-input" name="type" id="yoomoney_wallet" value="wallet" />
                <img src="{$image_dir|escape:'htmlall':'UTF-8'}yoo_money.png" />
                {l s='YooMoney' mod='yoomoneymodule'}
            </label><br />
            <label for="yoomoney_card" class="form-check-label">
                <input type="radio" class="form-check-input" name="type" id="yoomoney_card" value="card" />
                <img src="{$image_dir|escape:'htmlall':'UTF-8'}bank_card.png" />
                {l s='Bank cards' mod='yoomoneymodule'}
            </label>
        </div>
    </fieldset>
</form>