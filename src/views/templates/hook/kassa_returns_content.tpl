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

    <div class="tab-content panel">
        <div class="tab-pane active" id="kassa_return">
            {if isset($return_success) && $return_success}<p class='alert alert-success'>{$text_success|escape:'htmlall':'UTF-8'}</p>{/if}
            {if isset($return_errors) && $return_errors|count > 0}
                {foreach $return_errors as $ke}
                    <p class='alert alert-danger'>{$ke|escape:'htmlall':'UTF-8'}</p>
                {/foreach}
            {/if}

            {if $payment != null}
            {if $refundableAmount > 0}
            <form class="form-horizontal" method='post' action="">
            {/if}
                <table class="table table-bordered">
                    <tr>
                        <td>{l s='Transaction\'s number in YooKassa' mod='yoomoneymodule'}</td>
                        <td>{$payment->getId()|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    <tr>
                        <td>{l s='Order number' mod='yoomoneymodule'}</td>
                        <td>{$orderId|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    <tr>
                        <td>{l s='Payment method' mod='yoomoneymodule'}</td>
                        <td>
                            {l s=$paymentType mod='yoomoneymodule'}
                            {if $additionalPaymentInfo}
                                ({$additionalPaymentInfo|escape:'htmlall':'UTF-8'})
                            {/if}
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='Order amount' mod='yoomoneymodule'}</td>
                        <td>
                            {displayPrice price=$payment->getAmount()->getValue()}&nbsp;
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='Refunded' mod='yoomoneymodule'}</td>
                        <td>{$returnTotal|escape:'htmlall':'UTF-8'}</td>
                    </tr>
                    {if $refundableAmount > 0}
                    <tr>
                        <td>{l s='Refund amount' mod='yoomoneymodule'}</td>
                        <td style="width: 350px;">
                            <div class="input-group">
                                <span class="input-group-addon"> руб</span>
                                <input type="text" name="return_amount_text" class="control-form return_amount" value="{$refundableAmount|escape:'htmlall':'UTF-8'}" id="return-amount-text" disabled />
                                <input type="hidden" name="return_amount" value="{$refundableAmount|escape:'htmlall':'UTF-8'}" id="return-amount" />
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>{l s='Comment to the refund' mod='yoomoneymodule'}</td>
                        <td><textarea class="control-form" name="return_comment"></textarea></td>
                    </tr>
                    <tr>
                        <td colspan='2'><button type="submit" class="btn btn-success">{l s='Make the refund' mod='yoomoneymodule'}</button></td>
                    </tr>
                    {/if}
                </table>
            {if $refundableAmount > 0}
            </form>
            {/if}
            {else}
                <p>{l s='Не найден платёж' mod='yoomoneymodule'}</p>
            {/if}
        </div>
        <div class="tab-pane" id="kassa_return_table">
            <div id="history"></div>
            <br />
            <legend>{l s='List of the refunds' mod='yoomoneymodule'}</legend>
            <form class="form-horizontal">
                <div class="form-group">
                    <div class="col-lg-12">
                        <table class='table'>
                            <tr>
                                <td>{l s='Date of the refund' mod='yoomoneymodule'}</td>
                                <td>{l s='Refund amount' mod='yoomoneymodule'}</td>
                                <td>{l s='Status' mod='yoomoneymodule'}</td>
                                <td>{l s='Comment to the refund' mod='yoomoneymodule'}</td>
                            </tr>
                            {if $refunds}
                                {foreach $refunds as $refund}
                                    <tr>
                                        <td>{$refund['created_at']|escape:'htmlall':'UTF-8'}</td>
                                        <td>{displayPrice price=$refund['amount']}&nbsp;</td>
                                        <td>{$refund['status']|escape:'htmlall':'UTF-8'}</td>
                                        <td>{$refund['comment']|escape:'htmlall':'UTF-8'}</td>
                                    </tr>
                                {/foreach}
                            {else}
                                <tr>
                                    <td colspan='3'><div class='alert alert-danger'>{l s='No successful refunds for this payment' mod='yoomoneymodule'}</div></td>
                                </tr>
                            {/if}
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>