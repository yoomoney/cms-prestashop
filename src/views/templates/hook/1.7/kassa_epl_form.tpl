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
<style type="text/css">

    .yoomoney-pay-button {
        font-family: YandexSansTextApp-Regular, Arial, Helvetica, sans-serif;
        text-align: center;
        height: 60px;
        width: 155px;
        border-radius: 4px;
        transition: 0.1s ease-out 0s;
        color: #000;
        box-sizing: border-box;
        outline: 0;
        border: 0;
        background: #FFDB4D;
        cursor: pointer;
        font-size: 12px;
    }

    .yoomoney-pay-button:hover, .yoomoney-pay-button:active {
        background: #f2c200;
    }

    .yoomoney-pay-button span {
        display: block;
        font-size: 20px;
        line-height: 20px;
    }

    .yoomoney-pay-button_type_fly {
        box-shadow: 0 1px 0 0 rgba(0, 0, 0, 0.12), 0 5px 10px -3px rgba(0, 0, 0, 0.3);
    }
</style>

<div class="row">
    <div class="col-xs-6">
        {if $model->getShowInstallmentsButton() && $isInstallmentsEnabled}
            <div id="installment-wrapper" class="installment-wrapper" style="float: right"></div>
        {/if}
    </div>
    <div class="col-xs-6">
        <form method="post" action="{$action|escape:'htmlall':'UTF-8'}" id="yoomoney-form">
            <input type="hidden" class="form-check-input" name="payment_method" id="yoomoney-form-payment-type"
                   value=""/>
        </form>
    </div>
</div>


{if $model->getShowInstallmentsButton()}
    <script src="https://static.yandex.net/kassa/pay-in-parts/ui/v1/"></script>

    <script>
        const yamoneyCheckoutCreditUI = YandexCheckoutCreditUI({
            shopId: '{$model->getShopId()}',
            sum: '{$amount}',
            language: 'ru'
        });
        const yamoneyCheckoutCreditButton = yamoneyCheckoutCreditUI({
            type: 'button',
            theme: 'default',
            domSelector: '.installment-wrapper'
        });
    </script>
    <script>
        document.querySelector('.installment-wrapper button').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            document.querySelector('#yoomoney-form-payment-type').value = 'installments';
            document.querySelector('#yoomoney-form').submit();
        });
    </script>
{/if}
