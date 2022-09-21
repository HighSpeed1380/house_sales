
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

$(function(){
    var crop_data  = [];
    var media_id   = false;

    var $thumbnail = false;
    var $crop_icon = false;
    var $container = $('#crop_obj');
    var $crop_box  = $('#crop_block');
    var $cancel    = $('#crop_cancel');

    $('#photos_list').on('click', 'img.crop', function(){
        $crop_icon = $(this);
        $thumbnail = $(this).closest('.item').find('img.thumbnail');
        $rotate_icon = $(this).closest('.photo_navbar').find('img.rotate');

        $rotate_icon.hide()
        $crop_icon.hide();

        media_id = $(this).attr('id').split('_')[2];
        var src = rlConfig.ajax_url + '?item=tmpRotate&media_id=' + media_id;

        $container.append(
            $('<img>')
                .addClass('crop-image')
                .attr('src', src)
                .on('load', function(){
                    var $img = $(this);

                    $crop_box.fadeIn('slow', function(){
                        flynax.slideTo('#crop_block');

                        var aspect_ratio = 1;
                        var aspectRatio = rlConfig['img_crop_thumbnail'] || rlConfig['img_crop_module']
                            ? rlConfig['pg_upload_thumbnail_width'] / rlConfig['pg_upload_thumbnail_height']
                            : null;

                        $img.cropper({
                            aspectRatio: aspectRatio,
                            autoCropArea: 0.9,
                            zoomable: false,
                            crop: function(e) {
                                crop_data = e;
                            }
                        });
                    });
                })
        );
    });

    $cancel.click(function(){
        $crop_box.slideUp('slow');
        $crop_icon.show();
        $container.empty();
        $rotate_icon.show()
    });

    $('#crop_accept').click(function(){
        $(this).val(lang['loading']);

        var $cropAccept = $(this);

        var nat_width   = crop_data.target.naturalWidth;
        var nat_height  = crop_data.target.naturalHeight;
        var crop        = crop_data.detail;

        var x      = crop.x < 0 ? 0 : crop.x;
        var y      = crop.y < 0 ? 0 : crop.y;
        var x_diff = crop.x < 0 ? Math.abs(crop.x) : 0;
        var y_diff = crop.y < 0 ? Math.abs(crop.y) : 0;
        var width  = crop.width + crop.x > nat_width ? nat_width - x : crop.width - x_diff;
        var height = crop.height + crop.y > nat_height ? nat_height - y : crop.height - y_diff;

        var data = {
            listing_id: rlConfig['current_listing_id'],
            account_id: rlConfig['current_listing_account_id'],
            media_id: media_id,
            data: {
                x: x,
                y: y,
                width: width,
                height: height,
            }
        };

        flynax.sendAjaxRequest('cropListingPicture', data, function(response){
            $thumbnail.attr('src', response.results.Thumbnail);
            printMessage('notice', lang['crop_completed']);

            $cropAccept.val($cropAccept.data('default-phrase'));
            $crop_box.slideUp('slow');
            $crop_icon.show();
            $container.empty();
            $rotate_icon.show();
        });
    });
});
