<!-- category deleting -->

== THE DELETE PREPARING FILE ==
LET JOHN KNOW IF YOU SEE IT

{if empty($delete_info.categories) && empty($delete_info.listings)}
    {if $config.trash}{$lang.notice_drop_empty_category|replace:'[category]':$category.name}{else}{$lang.notice_delete_empty_category|replace:'[category]':$category.name}{/if}
    <input type="button" value="{$lang.yes}" onclick="xajax_deleteCategory('{$category.Key}')" />
    <input type="button" value="{$lang.no}" onclick="show('del_cat_block');" />
{else}
    {assign var='replace_category' value=`$smarty.ldelim`category`$smarty.rdelim`}

    <div>{$lang.delete_conditions|replace:$replace_category:$category.name}</div>
    <table style="margin: 5px 10px">
    {if !empty($delete_info.categories)}
        <tr>
            <td>{$lang.categories}:</td>
            <td><b>{$delete_info.categories}</b></td>
        </tr>
    {/if}
    {if !empty($delete_info.listings)}
        <tr>
            <td>{$lang.listings}:</td>
            <td><b>{$delete_info.listings}</b></td>
        </tr>
    {/if}
    </table>
    {$lang.choose_removal_method}
    <div style="margin: 5px 10px">
        <div><input onclick="$('#selected_method').val($(this).val());" type="radio" value="delete" name="del_action" id="delete_act" /> <label for="delete_act">{if $config.trash}{$lang.full_category_drop}{else}{$lang.full_category_delete}{/if}</label></div>
        <div><input onclick="$('#selected_method').val($(this).val());" type="radio" value="replace" name="del_action" id="replace_act" /> <label for="replace_act">{$lang.replace_parent_category}</label></div>
        
        <input type="hidden" id="selected_method" value="0" />
        <div style="margin: 5px 0;">
            <div id="top_buttons">
                <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('#selected_method').val())" />
                <input class="simple" type="button" value="{$lang.cancel}" onclick="show('del_cat_block');" />
            </div>
            
            <div id="categories" style="margin: 10px 0;" class="hide">
            {foreach from=$sections item='section'}
                <fieldset class="light">
                <legend id="legend_section_{$section.ID}" class="up" onclick="fieldset_action('section_{$section.ID}');">{$section.name}</legend>
                <div id="section_{$section.ID}" style="margin: 10px 15px;">
                    {if !empty($section.Categories)}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level.tpl' categories=$section.Categories}
                    {else}
                        <div style="margin-left: 10px;" class="blue_middle">{$lang.no_items_in_sections}</div>
                    {/if}
                </div>
                </fieldset>
            {/foreach}
            </div>
            
            <div id="bottom_buttons" class="hide">
                <input class="simple" type="button" value="{$lang.go}" onclick="xajax_deleteCategory('{$category.Key}', $('#replace_category').val())" />
                <input class="simple" type="button" value="{$lang.cancel}" onclick="show('del_cat_block');" />
            </div>
        </div>
        <input type="hidden" value="" id="replace_category" />
    </div>
{/if}


<!-- category deleting end -->
