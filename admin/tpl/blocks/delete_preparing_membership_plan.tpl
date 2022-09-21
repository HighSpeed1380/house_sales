<!-- account deleting -->

{assign var='replace' value=`$smarty.ldelim`plan`$smarty.rdelim`}
<div>{$lang.delete_plan_conditions|replace:$replace:$plan_details.name}</div>

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
    <div style="padding: 2px 0;"><label><input type="radio" value="delete" name="del_action" onclick="$('div#replace_content:visible').slideUp();$('#top_buttons').slideDown();$('#bottom_buttons').slideUp();" /> {if $config.trash}{$lang.full_plan_drop}{else}{$lang.full_plan_delete}{/if}</label></div>
    <div style="padding: 2px 0;"><label><input type="radio" value="replace" name="del_action" /> {$lang.replace_plan_option|replace:$replace:$plan_details.name}</label></div>
    
    <div style="margin: 5px 0;">
        <div id="top_buttons">
            <input class="simple" type="button" value="{$lang.go}" onclick="delete_chooser($('input[name=del_action]:checked').val(), '{$plan_details.Key}', '{$plan_details.name}')" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
        
        <div id="replace_content" style="margin: 10px 0;" class="hide">
            {$lang.plan}: 
            <select name="replace_id">
                {foreach from=$plans item='plan'}
                    {if $plan.ID != $plan_details.ID}<option value="{$plan.ID}">{$plan.name}</option>{/if}
                {/foreach}
            </select>
        </div>
        
        <div id="bottom_buttons" class="hide">          
            {if $config.trash}
                {assign var='notice_phrase' value=$lang.notice_drop_membership_plan}
            {else}
                {assign var='notice_phrase' value=$lang.notice_delete_membership_plan}
            {/if}
        
            <input class="simple" type="button" value="{$lang.go}" onclick="flynax.confirm('{$notice_phrase}', xajax_deletePlan, new Array('{$plan_details.Key}', $('select[name=replace_id]').val()));" />
            <a class="cancel" href="javascript:void(0)" onclick="$('#delete_block').fadeOut()">{$lang.cancel}</a>
        </div>
    </div>
</div>

<!-- account deleting end -->
