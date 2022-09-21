<!-- listing field output tpl -->

<tr id="df_field_{$item.Key}">
    <td class="name">{$item.name}:</td>
    <td class="value {if $smarty.foreach.fListings.first}first{/if}">
        {if $item.Type == 'checkbox'}
            {if $item.Opt1}
                {if $item.Opt2}
                    {assign var='col_num' value=$item.Opt2}
                {else}
                    {assign var='col_num' value=3}
                {/if}
                <table class="checkboxes{if $col_num > 2} fixed{/if}">
                <tr>
                {foreach from=$item.Values item='tile' name='checkboxF'}
                    <td>
                        {if !empty($item.Condition)}
                            {assign var='tile_source' value=$tile.Key}
                        {else}
                            {assign var='tile_source' value=$tile.ID}
                        {/if}
                        <div title="{$lang[$tile.pName]}" class="checkbox{if $tile_source|in_array:$item.source}_active{/if}">
                        {if $tile_source|in_array:$item.source}<img src="{$rlTplBase}img/blank.gif" alt="" />{/if}
                        {$lang[$tile.pName]}
                        </div>
                    </td>
                    {if $smarty.foreach.checkboxF.iteration%$col_num == 0 && !$smarty.foreach.checkboxF.last}
                    </tr>
                    <tr>
                    {/if}
                {/foreach}
                </tr>
                </table>
            {else}
                {$item.value}
            {/if}
        {else}
            {$item.value}
        {/if}
    </td>
</tr>

<!-- listing field output tpl end -->
