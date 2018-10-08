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
{if $update_status}
    <div class="alert alert-warning">{l s='Your module version is outdated. You can ' mod='yandexmodule'}
        <a target='_blank' href='https://github.com/yandex-money/yandex-money-cms-prestashop/releases'>
            {l s='download and install ' mod='yandexmodule'}</a> {l s='a new one' mod='yandexmodule'}
        {$update_status|escape:'htmlall':'UTF-8'}</div>
{/if}
<div id="tabs" class="yan_tabs">
    <p>{l s='By using this module, you automatically agree with' mod='yandexmodule'}
        <a href="https://money.yandex.ru/doc.xml?id=527052" target="_blank">{l s='its terms and conditions of use' mod='yandexmodule'}</a>.</p>
    <p>{l s='Module version' mod='yandexmodule'} <span id='ya_version'>{$ya_version|escape:'htmlall':'UTF-8'}</span></p>
    <ul>
        <li><a href="#moneyorg">{l s='Yandex.Kassa' mod='yandexmodule'}</a></li>
        <li><a href="#money">{l s='Yandex.Money' mod='yandexmodule'}</a></li>
        <li><a href="#billing">{l s='Yandex.Billing' mod='yandexmodule'}</a></li>
        <li><a href="#metrika">{l s='Yandex.Metrics' mod='yandexmodule'}</a></li>
        <li><a href="#market">{l s='Yandex.Market' mod='yandexmodule'}</a></li>
    </ul>
    <div id="money">
        <div class="errors">{$p2p_status|escape:'quotes':'UTF-8'}</div>
        <p>{l s='To operate this module, you need to' mod='yandexmodule'} <a href='https://money.yandex.ru/new' target='_blank'>{l s='create a Yandex.Money wallet' mod='yandexmodule'}</a></p>
        {$money_p2p}
    </div>
    <div id="billing">
        <div class="errors">{$billing_status|escape:'quotes':'UTF-8'}</div>
        <p>{l s='This is a payment form for your site. It allows for accepting payments to your company account from cards and Yandex.Money e-wallets without a contract. To set it up, you need to provide the Yandex.Billing identifier: we will send it via email after you' mod='yandexmodule'} <a href="https://money.yandex.ru/fastpay/">{l s='create a form in construction kit' mod='yandexmodule'}</a></p>
        {$billing_form}
    </div>
    <div id="moneyorg">
        <div class="errors">{$org_status|escape:'quotes':'UTF-8'}</div>
        <div class="ya_nps_block">{$nps_block}</div>
        <p>{l s='To operate this module, you need to connect your store' mod='yandexmodule'} <a target="_blank" href="https://kassa.yandex.ru/">{l s='to Yandex.Checkout' mod='yandexmodule'}</a>.</p>
        {$money_org}
    </div>
    <div id="metrika">
        <div class="errors">{$metrika_status|escape:'quotes':'UTF-8'}</div>
        {$money_metrika}
        <div id="iframe_container"></div>
    </div>
    <div id="market">
        <div class="errors">{$market_status|escape:'quotes':'UTF-8'}</div>
        {$money_market}
    </div>
</div>
{literal}
    <script type="text/javascript">
        (function (d, w, c) {
            (w[c] = w[c] || []).push(function () {
                try {
                    w.yaCounter27737730 = new Ya.Metrika({id: 27737730});
                } catch (e) {
                }
            });
            var n = d.getElementsByTagName("script")[0], s = d.createElement("script"), f = function () {
                n.parentNode.insertBefore(s, n);
            };
            s.type = "text/javascript";
            s.async = true;
            s.src = (d.location.protocol == "https:" ? "https:" : "http:") + "//mc.yandex.ru/metrika/watch.js";
            if (w.opera == "[object Opera]") {
                d.addEventListener("DOMContentLoaded", f, false);
            } else {
                f();
            }
        })(document, window, "yandex_metrika_callbacks");</script>
    <noscript>
        <div><img src="//mc.yandex.ru/watch/27737730" style="position:absolute; left:-9999px;" alt=""/></div>
    </noscript>
{/literal}
<style>
    .yan_tabs a {
        color: #00aff0;
    }
</style>
<script type="text/javascript">
        jQuery(document).ready(function () {
            var options = {
                YA_BILLING_ACTIVE: {},
                YA_WALLET_ACTIVE: {},
                YA_KASSA_ACTIVE: {}
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
