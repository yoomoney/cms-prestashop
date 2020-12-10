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
    if (typeof WishlistCart !== 'undefined') {
        var prestaWishlistCart = WishlistCart;
        WishlistCart = function (id, action, id_product, id_product_attribute, quantity, id_wishlist) {
            prestaWishlistCart(id, action, id_product, id_product_attribute, quantity, id_wishlist);
        };
    }

    if (typeof ajaxCart !== 'undefined') {
        var prestaAddCart = ajaxCart.add;
        ajaxCart.add = function (idProduct, idCombination, addedFromProductPage, callerElement, quantity, wishlist) {
            prestaAddCart(idProduct, idCombination, addedFromProductPage, callerElement, quantity, wishlist);
        };
    }

    if (typeof prestashop !== 'undefined') {
        prestashop.on('updateCart', function (event) {
            if (!event.reason
                || !event.reason.linkAction
                || event.reason.linkAction !== 'add-to-cart'
            ) {
                return;
            }
        });
    }
});
