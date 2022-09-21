<!-- photo block -->

{if empty($photos)}
    <div style="text-align: center; font-weight: bold;">{$lang.no_photos_uploaded}</div>
{else}
    {assign var='crop_margin' value=$config.pg_upload_thumbnail_width-3}
    
    <div class="photos">
    {foreach from=$photos item='photo' name='photosF' key='index'}
    {if !empty($photo.Thumbnail)}
        <div class="item">
            <div class="inner">

                <a {if $photo.Status != 'approval'}rel="group" {/if}id="img_title_{$photo.ID}" class="gallery_item{if $photo.Status == 'approval'} disabled{/if}" href="{if $photo.Status == 'approval'}javascript:void(0);{else}{$smarty.const.RL_FILES_URL}{$photo.Photo}{/if}" name="{$smarty.const.RL_FILES_URL}{$photo.Original}" title="{$photo.Description}">
                    <img alt="" src="{$smarty.const.RL_FILES_URL}{$photo.Thumbnail}" />
                    {if $photo.Status == 'approval'}
                        <div id="crop_photo_{$photo.ID}" title="{$lang.photo_needs_crop}" class="needs_crop"></div>
                    {/if}
                </a>

                {assign var='textarea_width' value=$config.pg_upload_thumbnail_width-12}
                <div id="img_des_{$photo.ID}" class="hide" style="text-align: center;">
                    <textarea style="width: {$textarea_width}px;" id="item_des_{$photo.ID}">{$photo.Description}</textarea><br />
                    <input onclick="xajax_editDesc('{$photo.ID}', $('#item_des_{$photo.ID}').val());" type="submit" value="{$lang.save}" style="font-size: 9px;" class="deny" />
                </div>
    
                <table class="sTable" style="height: 16px;width: {$config.pg_upload_thumbnail_width}px;">
                <tr>
                    <td align="left">
                    {if $photo.Status == 'active'}
                        {if $photo.Type == 'main'}
                            <span class="gray_small" style="font-size: 9px;"><b>{$lang.main_photo|replace:' ':'&nbsp;'}</b></span>
                        {else}
                            <span onclick="xajax_makeMain('{$listing.ID}', '{$photo.ID}');$('#photos_loading').slideDown('normal');" class="gray_small" style="text-decoration: underline;cursor: pointer;font-size: 9px;">{$lang.make_main}</span>
                        {/if}
                    {/if}
                    </td>
                    <td align="right">
                        <div style="width: {if $config.img_crop_interface}40{else}25{/if}px;">
                            <div class="remove" id="photo_remove_{$photo.ID}" title="{$lang.delete}" onclick="rlConfirm( '{$lang.delete_confirm}', 'xajax_deletePhoto', Array('{$listing.ID}', '{$photo.ID}'), 'photos_loading', 'smarty' );"></div>
                            <div class="remove_disabled hide" id="photo_hint_{$photo.ID}" title="{$lang.crop_protected}"></div>
                            <div class="edit" title="{$lang.edit}" onclick="show('img_des_{$photo.ID}');"></div>
                            {if $config.img_crop_interface && $photo.Status != 'approval' && !empty($photo.Original)}
                                <div id="crop_photo_{$photo.ID}" class="crop" title="{$lang.crop_photo}" />
                            {/if}
                        </div>
                    </td>
                </tr>
                </table>
            
    
                {*if !$smarty.foreach.photosF.last}
                
                    {assign var='nIndex' value=$index+1}
                    <div title="{$lang.reorder}" class="photo_reorder" onclick="xajax_reorderPhoto('{$listing.ID}', '{$photo.ID}', '{$photos.$nIndex.ID}');$(this).removeClass('photo_reorder').addClass('loading').css('margin-left', '2px').show();"></div>
                
                {/if*}
            </div>
        </div>
    {/if}
    {/foreach}
    </dir>
    
    <div class="loading" id="photos_loading" style="width: 100%;"></div>
{/if}

<!-- photo block end -->
