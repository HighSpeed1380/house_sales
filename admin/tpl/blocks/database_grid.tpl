<!-- database results grid -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.query_results}

<table class="lTable">
    <tr class="header">
        {foreach from=$fields item='field' name="fieldsF"}
        <td style="height: 24px;">
            <div>{$field}</div>
        </td>
        {if !$smarty.foreach.fieldsF.last}<td class="clear" style="width: 3px;"></td>{/if}
        {/foreach}
    </tr>
    <tr>
        <td colspan="3" class="height3"></td>
    </tr>
    
    {assign var='zIndex' value=50000}
    {foreach from=$out item='row' name='bodyF' key='key'}
    <tr class="body">
        {assign var='zIndex' value=$zIndex-1}
        {foreach from=$row item='column' name='columnF'}
        <td class="{if $smarty.foreach.bodyF.iteration%2 != 0}list_td{else}list_td_light{/if}"{if $column|strlen > 25} valign="top"{/if}>
            <div {if $column|strlen > 25}onclick="{literal}$(this).css('overflow', 'scroll').animate({width: 500, height: 200}){/literal}" onmouseout="{literal}$(this).css('overflow', 'hidden').animate({width: 150, height: 18}){/literal}"{/if} style="{if $column|strlen > 25}background: #eef4de;border: 1px #d2e798 solid;padding: 3px 5px;width: 150px; height: 18px;position: absolute; overflow: hidden;z-index: {$zIndex};{/if}">{$column}</div>
        </td>
        {if !$smarty.foreach.columnF.last}<td></td>{/if}
        {/foreach}
    </tr>
    {/foreach}
</table>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<!-- database results grid end -->
