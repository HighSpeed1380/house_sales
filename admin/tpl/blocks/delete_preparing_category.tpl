<!-- category deleting -->

{assign var='replace' value=`$smarty.ldelim`category`$smarty.rdelim`}
<div>{$lang.delete_category_conditions|replace:$replace:$category.name}</div>

<table class="list" style="margin: 0 0 15px 10px;">
{if !empty($delete_info.categories)}
    <tr>
        <td class="name" style="width: 80px">{$lang.subcategories}:</td>
        <td class="value"><b>{$delete_info.categories}</b></td>
    </tr>
{/if}
{if !empty($delete_info.listings)}
    <tr>
        <td class="name" style="width: 80px">{$lang.listings}:</td>
        <td class="value"><b>{$delete_info.listings}</b></td>
    </tr>
{/if}
</table>

{$lang.choose_removal_method}
<div style="margin: 5px 10px">
    <div style="padding: 2px 0;"><label><input type="radio" value="delete" name="del_method" onclick="$('div#replace_content:visible').slideUp();$('#top_buttons').slideDown();$('#bottom_buttons').slideUp();" /> {if $config.trash}{$lang.full_category_drop}{else}{$lang.full_category_delete}{/if}</label></div>
    <div style="padding: 2px 0;"><label><input type="radio" value="replace" name="del_method" /> {$lang.replace_parent_category}</label></div>
    
    <div style="margin: 5px 0;">
        <div id="top_buttons">
            <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('input[name=del_method]:checked').val(), '{$category.Key}', '{$category.name}')" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
        
        <div id="replace_content" style="margin: 10px 0;" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' button=$lang.go}
        </div>
    </div>
</div>

<!-- category deleting end -->
