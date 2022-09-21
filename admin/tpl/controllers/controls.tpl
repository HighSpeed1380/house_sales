<!-- controls tpl -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
<div style="padding: 10px;">
    <table class="lTable">
        <tr class="body">
            <td class="list_td_light">{$lang.recount_text}</td>
            <td style="width: 5px;" rowspan="100"></td>
            <td class="list_td_light" align="center" style="width: 200px;">
                <input id="listing_recount" type="button" onclick="xajax_recountListings('#listing_recount');$(this).val('{$lang.loading}');" value="{$lang.recount}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>
        
        <tr class="body">
            <td class="list_td">{$lang.rebuild_cat_levels}</td>
            <td align="center" class="list_td">
                <input id="cat_levels" type="button" onclick="xajax_rebuildCatLevels(true, '#cat_levels');$(this).val('{$lang.loading}');" value="{$lang.rebuild}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>
        
        <tr class="body">
            <td class="list_td_light">{$lang.reorder_fields_positions}</td>
            <td class="list_td_light" align="center">
                <input id="reorder_fields" type="button" onclick="xajax_reorderFields(true, '#reorder_fields');$(this).val('{$lang.loading}');" value="{$lang.reorder}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>
        
        <tr class="body">
            <td class="list_td">{$lang.resize_images}</td>
            <td class="list_td" align="center">
                <input id="resize_images" type="button" value="{$lang.update}" data-default-value="{$lang.update}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>

        <tr class="body">
            <td class="list_td_light">{$lang.refresh_coordinates}</td>
            <td class="list_td_light" align="center">
                <input id="refresh_listing_location" type="button" value="{$lang.rebuild}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>

        <tr class="body">
            <td class="list_td">{$lang.refresh_account_coordinates}</td>
            <td class="list_td" align="center">
                <input id="refresh_account_location" type="button" value="{$lang.rebuild}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>

        {if $config.cache}
        <tr class="body">
            <td class="list_td">{$lang.update_cache}</td>
            <td class="list_td" align="center">
                <input id="update_cache" type="button" onclick="xajax_updateCache(true, '#update_cache');$(this).val('{$lang.loading}');" value="{$lang.update}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>
        {/if}
        
        {if $config.membership_module}
        <tr class="body">
            <td class="list_td">{$lang.listing_statistic_mp_recount}</td>
            <td class="list_td" align="center">
                <input id="listing_mp_recount" type="button" onclick="xajax_recountListingsMP('#listing_mp_recount'); $(this).val('{$lang.loading}');" value="{$lang.recount}" />
            </td>
        </tr>
        <tr>
            <td style="height: 5px;" colspan="3"></td>
        </tr>
        {/if}
        
        {rlHook name='apTplControlsForm'}
        
    </table>
</div>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script>
lang.resize_in_progress          = '{$lang.resize_in_progress}';
lang.resize_completed            = '{$lang.resize_completed}';
lang.confirm_notice              = '{$lang.confirm_notice}';
lang.refresh_listing_pictures    = '{$lang.refresh_listing_pictures}';
lang.refresh_account_pictures    = '{$lang.refresh_account_pictures}';
lang.refresh_account_coordinates = '{$lang.refresh_account_coordinates}';
lang.rebuild                     = '{$lang.rebuild}';

{literal}
$(function(){
    $('#resize_images').click(function(){
        rlConfirm(lang.confirm_notice, 'flynax.initRebuildPictures');
    });

    // fix wrong coloring of background in rows
    $('.lTable tr.body').each(function(index){
        $columns = $(this).find('td').length == 3 ? $(this).find('td:even') : $(this).find('td');
        $columns.attr('class', index % 2 == 0 ? 'list_td_light' : 'list_td');
    });

    function refreshLocations() {
        return function($button, mode) {
            if (!$button) {
                return;
            }

            $button.click(function() {
                $button.addClass('disabled').prop('disabled', true).val(lang.loading);                
                refreshLocationsRequest($button);
            });

            var refreshLocationsRequest = function($button, start){
                flynax.sendAjaxRequest(
                    'refreshLocations',
                    {start: (start ? start : 0), mode: mode},
                    function(response) {
                        if (response.start) {
                            refreshLocationsRequest($button, response.start);
                        } else {
                            $button.removeClass('disabled').prop('disabled', false).val(lang.rebuild);
                        }
                    }
                );
            };
        };
    }

    var refreshLocationsHandler = refreshLocations();
    refreshLocationsHandler($('#refresh_account_location'), 'accounts');
    refreshLocationsHandler($('#refresh_listing_location'), 'listings');
});
{/literal}</script>

<!-- controls tpl end -->
