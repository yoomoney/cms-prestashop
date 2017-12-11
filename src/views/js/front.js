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

$(document).ready(function(){
    if(typeof celi_wishlist != 'undefined' && celi_wishlist && typeof WishlistCart != 'undefined') {
        WishlistCart = function (id, action, id_product, id_product_attribute, quantity, id_wishlist) {
            old_WishlistCart(id, action, id_product, id_product_attribute, quantity, id_wishlist);
            $.ajax({
                type: 'POST',
                url: baseDir + 'modules/yandexmodule/action.php?rand=' + new Date().getTime(),
                headers: { "cache-control": "no-cache" },
                async: true,
                cache: false,
                dataType : "json",
                data: 'action=add_wishlist&id_product=' + id_product + '&quantity=' + quantity + '&token=' + static_token + '&id_product_attribute=' + id_product_attribute,
                success: function(data) {
                    metrikaReach('metrikaWishlist', data.params);
                }
            });
        }
    }
    if(typeof celi_cart != 'undefined' && celi_cart && typeof ajaxCart != 'undefined' && ajaxCart.add) {
        var old_addCart = ajaxCart.add;
        ajaxCart.add = function (idProduct, idCombination, addedFromProductPage, callerElement, quantity, wishlist) {
            old_addCart(idProduct, idCombination, addedFromProductPage, callerElement, quantity, wishlist);
            $.ajax({
                type: 'POST',
                url: baseDir + 'modules/yandexmodule/action.php?rand=' + new Date().getTime(),
                headers: { "cache-control": "no-cache" },
                async: true,
                cache: false,
                dataType : "json",
                data: 'action=add_cart&id_product=' + idProduct + '&quantity=' + quantity + '&token=' + static_token + '&id_product_attribute=' + idCombination,
                success: function(data) {
                    metrikaReach('metrikaCart', data.params);
                }
            });
        }
    }
});

function metrikaReach(goal_name, params) {
    for (var i in window) {
        if (/^yaCounter\d+/.test(i)) {
            window[i].reachGoal(goal_name, params);
        }
    }
}