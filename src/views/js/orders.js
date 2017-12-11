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
	$('#btn_save').live('click', function(){
		var text = $(this).parent().find('textarea').val();
		var th = this;
		var id_order = $(this).parent().find('textarea').attr('name').replace('comment_', '');
		$.post('', {message: text, id_order: id_order, save_comment: 1}).done(function(data){
				alert('Сообщение успешно добавлено!');
				$(th).parent().find('textarea').val('');
		});
	});
	
	$('#open_map_orders').live('click', function(){
		if($(this).attr('checked'))
			$('#map_orders').slideDown('slow');
		else
			$('#map_orders').slideUp('slow');
	});
});