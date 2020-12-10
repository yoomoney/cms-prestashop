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
{if $update_status}
    <div class="alert alert-warning">{l s='Your module version is outdated. You can ' mod='yoomoneymodule'}
        <a target='_blank' href='https://github.com/yoomoney/yoomoney-ycms-v2-prestashop/releases'>
            {l s='download and install ' mod='yoomoneymodule'}</a> {l s='a new one' mod='yoomoneymodule'}
        {$update_status|escape:'htmlall':'UTF-8'}</div>
{/if}
<div id="tabs" class="yoomoney_tabs">
    <p>{l s='By using this module, you automatically agree with' mod='yoomoneymodule'}
        <a href="https://yoomoney.ru/doc.xml?id=527052" target="_blank">{l s='its terms and conditions of use' mod='yoomoneymodule'}</a>.</p>
    <p>{l s='Module version' mod='yoomoneymodule'} <span id='yoomoney_version'>{$yoomoney_version|escape:'htmlall':'UTF-8'}</span></p>
    <ul>
        <li><a href="#moneyorg">{l s='YooKassa' mod='yoomoneymodule'}</a></li>
        <li><a href="#money">{l s='YooMoney' mod='yoomoneymodule'}</a></li>
    </ul>
    <div id="money">
        <div class="errors">{$p2p_status|escape:'quotes':'UTF-8'}</div>
        <p>{l s='To operate this module, you need to' mod='yoomoneymodule'} <a href='https://yoomoney.ru/new' target='_blank'>{l s='create a YooMoney wallet' mod='yoomoneymodule'}</a></p>
        {$money_p2p}
    </div>
    <div id="moneyorg">
        <div class="errors">{$org_status|escape:'quotes':'UTF-8'}</div>
        <div class="yoomoney_nps_block">{$nps_block}</div>
        <p>{l s='To operate this module, you need to connect your store' mod='yoomoneymodule'} <a target="_blank" href="https://yookassa.ru/">{l s='to YooKassa' mod='yoomoneymodule'}</a>.</p>
        {$money_org}
    </div>
</div>
<style>
    .yoomoney_tabs a {
        color: #00aff0;
    }
</style>
<script type="text/javascript">
        jQuery(document).ready(function () {
            var options = {
                YOOMONEY_WALLET_ACTIVE: {},
                YOOMONEY_KASSA_ACTIVE: {}
            };

            var trueInputs = [];
            var falseInputs = [];
            for (var name in options) {
                var radio = jQuery('input[name="' + name + '"]');
                for (var i = 0; i < radio.length; i++) {
                    if (radio[i].value == '1') {
                        trueInputs.push(radio[i]);
                    } else {
                        falseInputs.push(radio[i]);
                    }
                }
                radio.bind('change', function (e) {
                    if (e.target.value == '1') {
                        for (var i = 0; i < trueInputs.length; i++) {
                            if (trueInputs[i] != e.target) {
                                trueInputs[i].checked = false;
                                falseInputs[i].checked = true;
                            }
                        }
                    }
                });
            }
        });
</script>
