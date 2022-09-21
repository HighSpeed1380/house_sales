<div id="subscribers">
    <div class="clearfix"><span class="field_description">{$count_subscribers}</span></div>
    <table class="table">
        <tr class="header"> 
            <td>{$lang.item}</td>
            <td class="divider"></td>
            <td width="100">{$lang.username}</td>
            <td class="divider"></td>
            <td width="70">{$lang.price}</td>
            <td class="divider"></td>
            <td width="110" class="text-overflow">{$lang.payment_gateway}</td>
            <td class="divider"></td>
            <td width="100">{$lang.date}</td>
        </tr>
        {foreach from=$subscribers item='subscriber'}
            <tr class="body">
                <td class="value">{$subscriber.Item_name}</td>
                <td class="divider"></td>
                <td class="value"><a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$subscriber.Account_ID}">{$subscriber.Full_name}</a></td>
                <td class="divider"></td>
                <td class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$subscriber.Total|number_format:2:'.':','} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</td>
                <td class="divider"></td>
                <td class="value">{$subscriber.Gateway}</td>
                <td class="divider"></td>
                <td class="value">{$subscriber.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
            </tr>   
        {/foreach}
    </table>
    {if $total_subscribers > $subscribers|@count}
        <div align="right"><a href="{$rlBase}index.php?controller=subscriptions">{$lang.see_all_subscribers}</a></div>
    {/if}
</div>
