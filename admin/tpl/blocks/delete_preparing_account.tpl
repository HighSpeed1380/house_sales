<!-- account deleting -->

{assign var='replace' value=`$smarty.ldelim`username`$smarty.rdelim`}
<div>{$lang.delete_account_conditions|replace:$replace:$account_details.Username}</div>

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
    <div style="padding: 2px 0;"><label><input type="radio" value="delete" name="del_action" onclick="$('div#replace_content:visible').slideUp();$('#top_buttons').slideDown();$('#bottom_buttons').slideUp();" /> {if $config.trash}{$lang.full_username_drop}{else}{$lang.full_username_delete}{/if}</label></div>
    <div style="padding: 2px 0;"><label><input type="radio" value="replace" name="del_action" /> {$lang.replace_account}</label></div>
    
    <div style="margin: 5px 0;">
        <div id="top_buttons">
            <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('input[name=del_action]:checked').val(), {$account_details.ID}, '{$account_details.Username}')" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
        
        <div id="replace_content" style="margin: 10px 0;" class="hide">
            {$lang.username}: <input type="text" name="new_account" />
        </div>
        
        <div id="bottom_buttons" class="hide">
            {assign var='replace' value=`$smarty.ldelim`username`$smarty.rdelim`}
            
            {if $config.trash}
                {assign var='notice_phrase' value=$lang.notice_drop_empty_account|replace:$replace:$account_details.Username}
            {else}
                {assign var='notice_phrase' value=$lang.notice_delete_empty_account|replace:$replace:$account_details.Username}
            {/if}
        
            <input class="simple" type="button" value="{$lang.go}" onclick="rlPrompt('{$notice_phrase}', 'xajax_deleteAccount', new Array({$account_details.ID}, $('input[name=new_account]').val()));" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
    </div>
</div>

<!-- account deleting end -->
