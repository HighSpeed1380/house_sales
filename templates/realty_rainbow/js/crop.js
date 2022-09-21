
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CROP.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

$(document).ready(function(){
	crop_handler();
});

var coefficient;
var crop_photo_id;

function crop_handler()
{
	$('#crop_accept').unbind('click');
	$('img.crop').unbind('click');
	$('#crop_cancel').unbind('click');

	$('img.crop').click(function(){
		var add_style = '';
		crop_photo_id = $(this).attr('id').split('_')[2];
		var img_source = $(this).attr('dir');

		$('#fileupload img.delete,#fileupload img.crop').show();
		
		flynax.slideTo('#crop_block');
		
		$(this).hide();
		$(this).parent().parent().find('img.delete').hide();
		$(this).parent().find('span.primary').hide().after('<span class="loading">'+lang['loading']+'</span>');
		var self = this;
		
		var img_obj = new Image();
		img_obj.onload = function(){
			build_interface(img_obj, add_style, img_source);
			$(self).parent().find('span.primary').show();
			$(self).parent().find('span.loading').remove();
		}
		img_obj.src = img_source;
	});
	
	function build_interface(img_obj, add_style, img_source)
	{
		coefficient = 1;
		
		var area_width = $('#width_detect').width();
		if ( img_obj.width >= area_width )
		{
			coefficient = img_obj.width/area_width;
			add_style = 'width: '+area_width+'px;';
		}
		
		var html = '<img style="'+add_style+'" src="'+img_source+'" />';
		$('#crop_obj').html(html);
		
		$('#crop_block').fadeIn('slow', function(){
			var crop = $.Jcrop('#crop_block img',{
				bgOpacity: .7,
				aspectRatio: photo_width / photo_height,
				onChange: showCoords,
				onSelect: showCoords,
				keySupport: false
			});
			
			var aspectX = Math.floor(img_obj.width / coefficient);
			var aspectY = Math.floor(img_obj.height / coefficient);
			if ( aspectX > aspectY )
			{
				aspectX = Math.floor(aspectY * photo_width / photo_height);
			}
			else
			{
				aspectY = Math.floor(aspectX * photo_height / photo_width);
			}
			
			crop.animateTo([0, 0, aspectX, aspectY]);
		});
	}
	
	$('#crop_cancel').click(function(){
		$('#crop_block').slideUp('slow');
		$('#navbar_'+crop_photo_id+' img.crop').show();
		$('#navbar_'+crop_photo_id).parent().find('img.delete').show();
	});
	
	function showCoords(c)
	{
		cx = Math.floor(c.x*coefficient);
		cy = Math.floor(c.y*coefficient);
		cx2 = Math.floor(c.x2*coefficient);
		cy2 = Math.floor(c.y2*coefficient);
		cw = Math.floor(c.w*coefficient);
		ch = Math.floor(c.h*coefficient);
	}
	
	$('#crop_accept').click(function(){
		crop();
	});
	
	this.crop = function()
	{
		if ( !cw || !ch )
		{
			error_text = ph_empty_error;
			error = true;
		}
		else
		{
			if ( cw < photo_width)
			{
				error_text = ph_too_small_error;
				error = true;
			}
			else
			{
				error = false;
			}
		}
		
		if ( error )
		{
			printMessage('error', error_text);
		}
		else
		{
			$('#crop_cancel').fadeOut('fast');
			$('#crop_accept').val(lang['loading']);
			xajax_crop(new Array(cx, cy, cx2, cy2, cw, ch), crop_photo_id);
		}
	}
}