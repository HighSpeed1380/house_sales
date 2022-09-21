<!-- account type deleting -->

{assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
<div>{$lang.pre_account_type_delete_notice|replace:$replace:$account_type.name}</div>

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
    <div style="padding: 2px 0;"><label><input type="radio" value="delete" name="del_action" onclick="$('div#replace_content:visible').slideUp();$('#top_buttons').slideDown();$('#bottom_buttons').slideUp();" /> {if $config.trash}{$lang.full_account_drop}{else}{$lang.full_account_delete}{/if}</label></div>
    <div style="padding: 2px 0;"><label><input type="radio" value="replace" name="del_action" /> {$lang.replace_another_account_type}</label></div>
    
    <div style="margin: 5px 0;">
        <div id="top_buttons">
            <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('input[name=del_action]:checked').val(), '{$account_type.Key}', '{$account_type.name}')" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
        
        <div id="replace_content" style="margin: 10px 0;" class="hide">
            {$lang.account_type}: 
            <select name="new_type">
                <option value="">{$lang.select}</option>
                {foreach from=$available_account_types item='available_account_type'}
                    {if $available_account_type.Key != $account_type.Key}
                        <option value="{$available_account_type.Key}">{$available_account_type.name}</option>
                    {/if}
                {/foreach}
            </select>
        </div>
        
        <div id="bottom_buttons" class="hide">
            {assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
            
            {if $config.trash}
                {assign var='notice_phrase' value=$lang.notice_drop_empty_account_type|replace:$replace:$account_type.name}
            {else}
                {assign var='notice_phrase' value=$lang.notice_delete_empty_account_type|replace:$replace:$account_type.name}
            {/if}
        
            <input class="simple" type="button" value="{$lang.go}" onclick="rlPrompt('{$notice_phrase}', 'xajax_deleteAccountType', new Array('{$account_type.Key}', $('select[name=new_type]').val()));" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
    </div>
</div>

<!-- account type deleting end -->
