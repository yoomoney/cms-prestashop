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

function marketEditOnHandler(event) {
    event.stopPropagation();
    event.preventDefault();
    const parent = $(this).closest('.yandex-money-market-js-editable');
    parent.find('.yandex-money-market-edit-on-button').hide();
    parent.find('.yandex-money-market-js-editable-view').hide();
    parent.find('.yandex-money-market-js-editable-edit').show();
    $(this).hide();
}

function marketJsEditableEditFinishHandler(parent) {
    parent.find('.yandex-money-market-js-editable-edit').hide();
    parent.find('.yandex-money-market-edit-on-button').css('display', '');
    parent.find('.yandex-money-market-js-editable-view').show();
}

function marketCurrencyUpdateViewValues(parent) {
    const plus = parent.find('.yandex-money-market-currency-plus').val();
    const rateOption = parent.find('.yandex-money-market-currency-rate option:selected');
    parent.find('.yandex-money-market-currency-view-plus-value').text(plus);
    parent.find('.yandex-money-market-currency-view-rate').text(rateOption.text());
}

function marketCurrencyEditFinishHandler() {
    const parent = $(this).closest('.yandex-money-market-js-editable');
    marketJsEditableEditFinishHandler(parent);
    marketCurrencyUpdateViewValues(parent);
}

function marketSetPrevValues(elements) {
    elements.each(function () {
        let el = $(this);
        if (el.attr('type') === 'checkbox' || el.attr('type') === 'radio') {
            el.prop('checked', el.val() === el.data('value'));
        } else {
            el.val(el.data('value'));
        }
    });
}

function marketCurrencyEditFinishResetHandler() {
    const parent = $(this).closest('.yandex-money-market-js-editable');
    const elements = parent.find('.yandex-money-market-js-editable-edit').find('select, input[type!=button]');
    marketSetPrevValues(elements);
    marketJsEditableEditFinishHandler(parent);
    marketCurrencyUpdateViewValues(parent);
}

function marketAllCategoriesChangeHandler() {
    if ($(this).val()) {
        $(this).closest('.yandex-money-market-category-tree-container').find('.yandex-money-market-category-tree').slideUp();
    } else {
        $(this).closest('.yandex-money-market-category-tree-container').find('.yandex-money-market-category-tree').slideDown();
    }
}

function marketShowCatAll() {
    $(this).closest('.yandex-money-market-category-tree').find("ul.yandex-money-market-category-tree-branch").each(function () {
        $(this).slideDown();
    });
}

function marketHideCatAll() {
    $(this).closest('.yandex-money-market-category-tree').find("ul.yandex-money-market-category-tree-branch").each(function () {
        $(this).slideUp();
    });
}

function marketCheckAll() {
    $(this).closest('.yandex-money-market-category-tree').find(":input[type=checkbox]").each(function () {
        $(this).prop("checked", true);
    });
}

function marketUncheckAll() {
    $(this).closest('.yandex-money-market-category-tree').find(":input[type=checkbox]").each(function () {
        $(this).prop("checked", false);
    });
}

function marketCategoryClickHandler() {
    $(this).closest('li').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
}

function marketDeliveryEditFinishHandler() {
    const parent = $(this).closest('.yandex-money-market-js-editable');
    marketJsEditableEditFinishHandler(parent);
    marketDeliveryUpdateViewValues(parent)
}

function marketDeliveryEditFinishResetHandler() {
    const parent = $(this).closest('.yandex-money-market-js-editable');
    const elements = parent.find('.yandex-money-market-js-editable-edit').find('select, input[type!=button]');
    marketSetPrevValues(elements);
    marketJsEditableEditFinishHandler(parent);
    marketDeliveryUpdateViewValues(parent);
}

function marketDeliveryUpdateViewValues(parent) {
    let edit = parent.find('.yandex-money-market-js-editable-edit');
    let cost = edit.find('.yandex-money-market-delivery-cost').val();
    let daysFrom = edit.find('.yandex-money-market-delivery-days-from').val();
    let daysTo = edit.find('.yandex-money-market-delivery-days-to').val();
    let orderBeforeOption = edit.find('.yandex-money-market-delivery-order-before option:selected');
    let orderBeforeText = +orderBeforeOption.val()
        ? orderBeforeOption.text()
        : '13:00 (по умолчанию для Маркета)';
    let days = !daysTo || daysFrom === daysTo ? +daysFrom : daysFrom + '-' + daysTo;

    let view = parent.find('.yandex-money-market-js-editable-view');
    view.find('.yandex-money-market-delivery-cost').text(+cost);
    view.find('.delivery_days').text(days);
    view.find('.yandex-money-market-delivery-order-before').text(orderBeforeText);
}

function marketOfferTypeClickHandler() {
    if ($('input[name="YA_MARKET_OFFER_TYPE_SIMPLE"]:checked').val()) {
        $('.yandex-money-market-offer-name-template').show();
    } else {
        $('.yandex-money-market-offer-name-template').hide();
    }
}

function marketAvailableEditFinishHandler() {
    let parent = $(this).closest('.yandex-money-market-js-editable');
    marketJsEditableEditFinishHandler(parent);
    marketAvailableUpdateViewValues(parent)
}

function marketAvailableEditFinishResetHandler() {
    let parent = $(this).closest('.yandex-money-market-js-editable');
    const elements = parent.find('.yandex-money-market-js-editable-edit').find('select, input[type!=button]');
    marketSetPrevValues(elements);
    marketJsEditableEditFinishHandler(parent);
    marketAvailableUpdateViewValues(parent)
}

function marketAvailableUpdateViewValues(parent) {
    let edit = parent.find('.yandex-money-market-js-editable-edit');
    let view = parent.find('.yandex-money-market-js-editable-view');

    let delivery = edit.find('.yandex-money-market-available-delivery').is(':checked');
    let pickup = edit.find('.yandex-money-market-available-pickup').is(':checked');
    let store = edit.find('.yandex-money-market-available-store').is(':checked');

    let available = edit.find('select option:selected').val();
    if (available === 'none') {
        view.find('.available_dont_upload').show();
        view.find('.available_will_upload').hide();
    } else {
        view.find('.available_dont_upload').hide();
        view.find('.available_will_upload').show();
        if (available === 'true') {
            view.find('.yandex-money-market-available-with-ready').show();
            view.find('.yandex-money-market-available-with-to-order').hide();
        } else {
            view.find('.yandex-money-market-available-with-ready').hide();
            view.find('.yandex-money-market-available-with-to-order').show();
        }
        if (delivery || pickup || store) {
            view.find('.yandex-money-market-available-view-available-list').show();
            if (delivery) {
                let el = view.find('.yandex-money-market-available-delivery');
                el.show();
                if (pickup || store) {
                    el.removeClass('last');
                } else {
                    el.addClass('last');
                }
            } else {
                view.find('.yandex-money-market-available-delivery').hide();
            }
            if (pickup) {
                let el = view.find('.yandex-money-market-available-pickup');
                el.show();
                if (store) {
                    el.removeClass('last');
                } else {
                    el.addClass('last');
                }
            } else {
                view.find('.yandex-money-market-available-pickup').hide();
            }
            if (store) {
                view.find('.yandex-money-market-available-store').show();
            } else {
                view.find('.yandex-money-market-available-store').hide();
            }
        } else {
            view.find('.yandex-money-market-available-view-available-list').hide();
        }
    }
}

function marketAddNewAdditionalCondition() {
    let index = $(this).data('index');
    let nextIndex = index + 1;
    $(this).data('index', nextIndex);
    let list = $('.yandex-money-market-additional-condition-list');
    let template = list.find('.yandex-money-market-additional-condition-template');
    let newForm = template.clone();
    newForm.removeClass('yandex-money-market-additional-condition-template');
    template.before(newForm);
    newForm.find('.yandex-money-market-edit-on-button').click();
    newForm.find('select, input[type!=button]').each(function () {
        $(this).attr('name', $(this).data('name').replace(/\[\]/, '[' + index + ']'));
    });
}

function marketAdditionalConditionEditFinishHandler() {
    let parent = $(this).closest('.yandex-money-market-additional-condition');
    marketJsEditableEditFinishHandler(parent);
    marketAdditionalConditionUpdateViewValues(parent)
}

function marketAdditionalConditionEditFinishResetHandler() {
    let parent = $(this).closest('.yandex-money-market-additional-condition');
    const elements = parent.find('.yandex-money-market-js-editable-edit').find('select, input[type!=button]');
    marketSetPrevValues(elements);
    marketJsEditableEditFinishHandler(parent);
    marketAdditionalConditionUpdateViewValues(parent)
}

function marketAdditionalConditionDeleteHandler() {
    $(this).closest('.yandex-money-market-additional-condition').detach();
}

function marketAdditionalConditionUpdateViewValues(parent) {
    let edit = parent.find('.yandex-money-market-js-editable-edit');
    let name = edit.find('input[name^=YA_MARKET_ADDITIONAL_CONDITION_NAME]').val();
    let tag = edit.find('input[name^=YA_MARKET_ADDITIONAL_CONDITION_TAG]').val();
    let typeValue = edit.find('input[name^=YA_MARKET_ADDITIONAL_CONDITION_TYPE_VALUE]:checked').val();
    let staticValue = edit.find('input[name^=YA_MARKET_ADDITIONAL_CONDITION_STATIC_VALUE]').val();
    let dataValueOption = edit.find('select option:selected');
    let forAllCat = edit.find('input[name^=YA_MARKET_ADDITIONAL_CONDITION_FOR_ALL_CAT]:checked').val();
    let valueText = typeValue === 'static' ? staticValue : dataValueOption.text();

    let view = parent.find('.yandex-money-market-js-editable-view');
    view.find('.yandex-money-market-additional-condition-name').text(name);
    view.find('.yandex-money-market-additional-condition-tag').text(tag);
    view.find('.yandex-money-market-additional-condition-value').text(valueText);
    let forAllCatText = forAllCat ? 'всех категорий' : 'выбранных категорий';
    view.find('.yandex-money-market-additional-condition-category-list').text(forAllCatText);
}

function marketVatClickHandler() {
    if ($('input[name="YA_MARKET_VAT_ENABLED"]').is(':checked')) {
        $('.yandex-money-market-val-list').show();
    } else {
        $('.yandex-money-market-val-list').hide();
    }
}

function clearSelection() {
    if (window.getSelection) {
        if (window.getSelection().empty) {
            window.getSelection().empty();
        } else if (window.getSelection().removeAllRanges) {
            window.getSelection().removeAllRanges();
        }
    } else if (document.selection) {
        document.selection.empty();
    }
}

function marketCopyUrlToClipboard() {
    let el = $('.yandex-money-market-export-link-url');
    el.prop('disabled', false);
    el.select();
    document.execCommand("copy");
    el.prop('disabled', true);
    clearSelection();
    alert(el.data('message'));
}


$(document).ready(function () {
    $('div#market').on('change', '.yandex-money-market-category-tree-switcher', marketAllCategoriesChangeHandler)
        .on('change', '.yandex-money-market-category-tree input[type="checkbox"]', marketCategoryClickHandler)
        .on('click', '.market-expand-all-category-box', marketShowCatAll)
        .on('click', '.market-collapse-all-category-box', marketHideCatAll)
        .on('click', '.market-check-all-category-box', marketCheckAll)
        .on('click', '.market-uncheck-all-category-box', marketUncheckAll)
        .on('click', '.yandex-money-market-edit-on-button', marketEditOnHandler);
    $('.yandex-money-market-currency-edit .edit_finish').on('click', marketCurrencyEditFinishHandler);
    $('.yandex-money-market-currency-edit .edit_finish_reset').on('click', marketCurrencyEditFinishResetHandler);
    $('.yandex-money-market-delivery-edit .edit_finish').on('click', marketDeliveryEditFinishHandler);
    $('.yandex-money-market-delivery-edit .edit_finish_reset').on('click', marketDeliveryEditFinishResetHandler);
    marketOfferTypeClickHandler();
    $('input[name="YA_MARKET_OFFER_TYPE_SIMPLE"]').on('change', marketOfferTypeClickHandler);
    $('.yandex-money-market-available').each(marketAvailableEditFinishHandler);
    $('.yandex-money-market-available-edit .edit_finish').on('click', marketAvailableEditFinishHandler);
    $('.yandex-money-market-available-edit .edit_finish_reset').on('click', marketAvailableEditFinishResetHandler);
    marketVatClickHandler();
    $('input[name="YA_MARKET_VAT_ENABLED"]').on('change', marketVatClickHandler);
    $('.yandex-money-market-additional-condition-more').on('click', marketAddNewAdditionalCondition);
    $('.yandex-money-market-additional-condition-container')
        .on('click', '.edit_finish', marketAdditionalConditionEditFinishHandler)
        .on('click', '.edit_finish_reset', marketAdditionalConditionEditFinishResetHandler)
        .on('click', '.edit_finish_delete', marketAdditionalConditionDeleteHandler);
    $('.yandex-money-market-copy-url').on('click', marketCopyUrlToClipboard);
});
