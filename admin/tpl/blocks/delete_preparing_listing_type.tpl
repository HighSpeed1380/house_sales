<!-- listing type deleting -->

{assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
<div>{$lang.pre_listing_type_delete_notice|replace:$replace:$type_info.name}</div>

<table class="list" style="margin: 0 0 15px 10px;">
{foreach from=$delete_details item='del_item'}
{if $del_item.items}
<tr>
    <td class="name" style="width: 115px">{$del_item.name}:</td>
    <td class="value">{if $del_item.link}<a target="_blank" href="{$del_item.link}">{/if}<b>{$del_item.items}</b>{if $del_item.link}</a>{/if}</td>
</tr>
{/if}
{/foreach}
</table>

{$lang.choose_removal_method}
<div style="margin: 5px 10px">
    <div style="padding: 2px 0;"><label><input type="radio" value="delete" name="del_action" onclick="$('div#replace_content:visible').slideUp();$('#top_buttons').slideDown();$('#bottom_buttons').slideUp();" /> {if $config.trash}{$lang.full_listing_type_drop}{else}{$lang.full_listing_type_delete}{/if}</label></div>
    <div style="padding: 2px 0;"><label><input type="radio" value="replace" name="del_action" /> {$lang.replace_another_listing_type}</label></div>
    
    <div style="margin: 5px 0;">
        <div id="top_buttons">
            <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('input[name=del_action]:checked').val(), '{$type_info.Key}', '{$type_info.name}')" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
        
        <div id="replace_content" style="margin: 10px 0;" class="hide">
            {$lang.listing_type}: 
            <select name="new_type">
                <option value="">{$lang.select}</option>
                {foreach from=$listing_types item='available_listing_type'}
                    {if $available_listing_type.Key != $type_info.Key}
                        <option value="{$available_listing_type.Key}">{$available_listing_type.name}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
        
        <div id="bottom_buttons" class="hide">
            {assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
            
            {if $config.trash}
                {assign var='notice_phrase' value=$lang.notice_drop_empty_listing_type|replace:$replace:$type_info.name}
            {else}
                {assign var='notice_phrase' value=$lang.notice_delete_empty_listing_type|replace:$replace:$type_info.name}
            {/if}
        
            <input class="simple" type="button" value="{$lang.go}" onclick="rlPrompt('{$notice_phrase}', 'xajax_deleteListingType', new Array('{$type_info.Key}', $('select[name=new_type]').val()));" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
    </div>
</div>

<!-- listing type deleting end -->
