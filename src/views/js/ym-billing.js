/**
 * Module is prohibited to sales! Violation of this condition leads to the deprivation of the license!
 *
 * @author    Yandex.Money <cms@yamoney.ru>
 * @copyright © 2015-2017 NBCO Yandex.Money LLC
 * @license   https://money.yandex.ru/doc.xml?id=527052
 *
 * @category  Front Office Features
 * @package   Yandex Payment Solution
 */

$(document).ready(function() {
    var field = $("#ym-billing-fio");
    var valid = false;

    function validateField() {
        var parts = field.val().trim().split(/\s+/);
        if (parts.length != 3) {
            field.parent().removeClass("form-ok");
            field.parent().addClass("form-error");
            valid = false;
        } else {
            field.parent().removeClass("form-error");
            field.parent().addClass("form-ok");
            valid = true;
            field.val(parts.join(" "));
        }
    }

    $("#ym-billing-confirm-payment").click(function () {
        validateField();
        if (valid) {
            $('#ym-billing-form')[0].submit();
        }
    });
    field.blur(validateField);
});