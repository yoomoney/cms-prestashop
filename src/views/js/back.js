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


$(document).ready(function()
{
    $('.changec_submit').live('click', function(){
        var id_carrier = $('.sel_delivery').val();
        var pr_incl = $('input#price_incl').val();
        var pr_excl = $('input#price_excl').val();
        $.ajax({
            url: ajaxurl + 'actions.php',
            type: 'POST',
            data: {pr_incl :pr_incl, pr_excl : pr_excl, new_carrier : id_carrier, id_o : id_order, action : 'change_order', tkn: tkn, idm: idm},
            cache: false,
            dataType: 'json',
            beforeSend: function() {
                $('.change_carr').append('<div id="fade"></div>');
                $('#fade').css({'filter' : 'alpha(opacity=80)'}).fadeIn();
                $('#circularG').fadeIn();
            },
            success: function(data, textStatus, jqXHR)
            {
                console.log(data);
                if(!data.status && data.error != ''){
                    $('#circularG').fadeOut(function(){
                        $('.change_carr #fade').fadeOut(function(){
                            $(this).remove();
                            alert(data.error);
                        });
                    });
                }else{
                    $('#circularG').fadeOut(function(){
                        $('.change_carr #fade').fadeOut(function(){
                            $(this).remove();
                            location.reload();
                        });
                    });
                }
            },
            error: function(jqXHR, textStatus, errorThrown){
                console.log('ERRORS: ' + textStatus);
            }
        });
    });
    
    $('.sel_delivery').live('change', function(){
        var id_carrier = $(this).val();
        if(id_carrier == 0)
        {
            alert(notselc);
            $('input#price_incl, input#price_excl').val('');
        }
        else
        {
            $.ajax({
                url: ajaxurl + 'actions.php',
                type: 'POST',
                data: {new_carrier : id_carrier, id_o : id_order, action : 'load_price', tkn: tkn, idm: idm},
                cache: false,
                dataType: 'json',
                beforeSend: function() {
                    $('.change_carr').append('<div id="fade"></div>');
                    $('#fade').css({'filter' : 'alpha(opacity=80)'}).fadeIn();
                    $('#circularG').fadeIn();
                },
                success: function(data, textStatus, jqXHR)
                {
                    console.log(data);
                    if(data.error){
                        $('input#price_incl, input#price_excl').val('');
                        $('#circularG').fadeOut(function(){
                            $('.change_carr #fade').fadeOut(function(){
                                $(this).remove();
                                alert(data.error);
                            });
                        });
                    }else{
                        $('input#price_incl').val(data.price_with_tax);
                        $('input#price_excl').val(data.price_without_tax);
                        $('#circularG').fadeOut(function(){
                            $('.change_carr #fade').fadeOut(function(){
                                $(this).remove();
                            });
                        });
                    }
                },
                error: function(jqXHR, textStatus, errorThrown){
                    console.log('ERRORS: ' + textStatus);
                }
            });
        }
    });
});