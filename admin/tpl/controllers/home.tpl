<!-- home tpl -->

{rlHook name='apTplHomeBeforeBlocks'}

<table class="home_grag_drop" cellpadding="0" cellspacing="0">
<tr>
    <td valign="top" class="column1">
        <div class="sortable">
            {foreach from=$ap_blocks.column1 item='block'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'homeDragDrop_block.tpl'}
            {/foreach}
        </div>
    </td>
    <td class="vspace"></td>
    <td valign="top" class="column2">
        <div class="sortable">
            {foreach from=$ap_blocks.column2 item='block'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'homeDragDrop_block.tpl'}
            {/foreach}
        </div>
    </td>
</tr>

<tr>
    <td colspan="3" valign="top" class="column3">
        <div class="sortable">
            {foreach from=$ap_blocks.column3 item='block'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'homeDragDrop_block.tpl'}
            {/foreach}
        </div>
    </td>
</tr>

<tr>
    <td valign="top" class="column4">
        <div class="sortable">
            {foreach from=$ap_blocks.column4 item='block'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'homeDragDrop_block.tpl'}
            {/foreach}
        </div>
    </td>
    <td class="vspace"></td>
    <td valign="top" class="column5">
        <div class="sortable">
            {foreach from=$ap_blocks.column5 item='block'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'homeDragDrop_block.tpl'}
            {/foreach}
        </div>
    </td>
</tr>
</table>

{rlHook name='apTplHomeAfterBlocks'}

<div class="hide" id="tmp_dom_blocks_store"></div>

<script type="text/javascript">
var notifications = '';
{if $notifications|@count >1}
    notifications += '<ul>';
    {foreach from=$notifications item="notification"}
        notifications += '<li style="list-style:initial">{$notification|escape:quotes}</li>';
    {/foreach}
    notifications += '</ul>';
{elseif $notifications}
    notifications += '{$notifications.0|escape:quotes}';
{/if}

//<![CDATA[
{literal}

$(document).ready(function(){
    $('.home_grag_drop div.sortable').sortable({
        placeholder: 'ui-state-highlight',
        connectWith: '.sortable',
        handle: '.move',
        cursor: 'move',
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.4,
        stop: function(event, ui){
            var column = $(ui.item).parent().parent().attr('class');
            var items = '';
            var key = $(ui.item).attr('id').split(':')[1];
            
            $(ui.item).parent().find('div.block').each(function(){
                items += $(this).attr('id').split(':')[1] +'|'+ $(this).index() +',';
            });
            
            items = items != '' ? rtrim(items, ',') : items;
            
            /* save new arrangement */
            createCookie('ap_arrangement_'+column, items, 365);

            /* fix all other entries */
            for ( var i=1; i<=5; i++ )
            {
                if ( column != 'column'+i )
                {
                    var cookie_line = readCookie('ap_arrangement_column'+i);
                    if ( cookie_line && cookie_line.indexOf(key) >= 0 )
                    {
                        var exp_line = cookie_line.split(',');
                        
                        for ( var j=0; j<exp_line.length; j++ )
                        {
                            var found_index = exp_line[j].indexOf(key);
                            if ( found_index >= 0 )
                            {
                                exp_line.splice(j, 1);
                            }
                        }
                        
                        eraseCookie('ap_arrangement_column'+i);
                        if ( exp_line.length > 0 )
                        {
                            createCookie('ap_arrangement_column'+i, exp_line.join(','), 365);
                        }
                    }
                }
            }
        }
    }).disableSelection();

    if (notifications) {
        printMessage('alert', notifications )
    }
});

{/literal}
//]]>
</script>

{rlHook name='apTplHomeBottom'}

<!-- home tpl end -->
