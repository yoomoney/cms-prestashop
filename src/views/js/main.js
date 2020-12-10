/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    YooMoney <cms@yoomoney.ru>
 * @copyright © 2020 "YooMoney", NBСO LLC
 * @license   https://yoomoney.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   YooMoney Payment Solution
 */

$(document).ready(function () {
    $('#tabs').tabs();
    var view = $.totalStorage('tab_yoomoney');
    if (view == null)
        $.totalStorage('tab_yoomoney', 'money');
    else
        $('.ui-tabs-nav li a[href="#' + view + '"]').click();

    $('.ui-tabs-nav li').live('click', function () {
        var view = $(this).find('a').first().attr('href').replace('#', '');
        $.totalStorage('tab_yoomoney', view);
    });

    var tmp = jQuery('#payment_mode_kassa');
    if (tmp.length) {
        bindModeTrigger(tmp.parents('.form-group'));
    }

    tmp = jQuery('#kassa_send_receipt_enable');
    if (tmp.length) {
        bindReceiptTrigger(tmp.parents('.form-group'));
    }
    $('.yoomoney_nps_link').on('click', yoomoney_nps_goto);
    $('.yoomoney_nps_block button.close').on('click', yoomoney_nps_close);
});

function yoomoney_nps_close() {
    const link = $('.yoomoney_nps_link');
    $.post('ajax-tab.php', {
        ajax: '1',
        token: link.data('token'),
        controller: link.data('controller'),
        action: 'voteNps',
    }).done(function () {
        $('.yoomoney_nps_block').slideUp();
    });
}

function yoomoney_nps_goto() {
    window.open('https://yandex.ru/poll/97ptquHpcjXryy3SyRNkug');
    yoomoney_nps_close();
}

function bindModeTrigger(root) {
    const paymentMethodWrapper = jQuery('.payment-mode-shop').parents('.form-group');
    const eplButtonWrapper = jQuery('#YOOMONEY_KASSA_PAY_LOGO_ON').parents('.form-group');
    const installmentsButtonWrapper = jQuery('#YOOMONEY_KASSA_INSTALLMENTS_BUTTON_ON').parents('.form-group');
    const input = jQuery('input[name=YOOMONEY_KASSA_PAYMENT_MODE]');
    input.change(function () {
        triggerMode(this.value);
    });
    if (input[0].checked) {
        triggerMode(input[0].value);
    } else if (input[1].checked) {
        triggerMode(input[1].value);
    } else {
        eplButtonWrapper.hide();
        installmentsButtonWrapper.hide();
        paymentMethodWrapper.show();
    }
    function triggerMode(value) {
        if (value == 'kassa') {
            jQuery(eplButtonWrapper).show();
            jQuery(installmentsButtonWrapper).show();
            jQuery(paymentMethodWrapper).hide();
        } else {
            jQuery(eplButtonWrapper).hide();
            jQuery(installmentsButtonWrapper).hide();
            jQuery(paymentMethodWrapper).show();
        }
    }

    const holdModeSettings = jQuery('.enable-hold-mode').parents('.form-group');
    function toggleEnableHoldMode() {
        if (jQuery('#YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON').is(':checked')) {
            holdModeSettings.slideDown();
        } else {
            holdModeSettings.slideUp();
        }
    }
    jQuery('#YOOMONEY_KASSA_ENABLE_HOLD_MODE_ON').on('change', toggleEnableHoldMode);
    toggleEnableHoldMode();
}

function bindReceiptTrigger(root) {
    var input = jQuery('input[name=YOOMONEY_KASSA_SEND_RECEIPT]');
    var wrappers = jQuery('.kassa_tax_rate').parents('.form-group');
    input.change(function () {
        triggerMode(this.value);
    });
    if (input[0].checked) {
        triggerMode(input[0].value);
    } else {
        triggerMode(input[1].value);
    }
    function triggerMode(value) {
        if (value == '0') {
            root.next().slideUp();
            root.next().next().slideUp();
            root.next().next().next().slideUp();
            root.next().next().next().next().slideUp();
            wrappers.slideUp();
        } else {
            root.next().slideDown();
            root.next().next().slideDown();
            root.next().next().next().slideDown();
            root.next().next().next().next().slideDown();
            wrappers.slideDown();
        }
    }
}
