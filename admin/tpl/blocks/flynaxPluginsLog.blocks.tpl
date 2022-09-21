<!-- flynax plugins log DOM -->

{if $change_log_content}
    <table class="sTable">
    {assign var='update_from' value=`$smarty.ldelim`from`$smarty.rdelim`}
    {assign var='update_to' value=`$smarty.ldelim`to`$smarty.rdelim`}
    {foreach from=$change_log_content item='log_item' key='pLog_key'}
    <tr>
        <td class="list-date">
            {$log_item.date|date_format:'%d'}
            <div>{$log_item.date|date_format:'%b'}</div>
        </td>
            
        <td class="list-body changelog_{$log_item.status}">
            <div class="changelog_item" id="pChangelog_{$pLog_key}">
                <a class="green_14" target="_blank" href="https://www.flynax.com/plugins/{$log_item.path}.html#changelog" title="{$lang.learn_more_about} {$log_item.name}">{$log_item.name}</a>
                <span class="dark_13">&rarr; {$log_item.version}</span>
                {if $log_item.status == 'current'}
                    <span class="gray-border">{$lang.current_version}</span>
                {else}
                    {if $log_item.status != 'no'}
                        {if $log_item.compatible}
                            <a {if $log_item.status == 'update'}title="{$lang.update_from_to|replace:$update_from:$log_item.current|replace:$update_to:$log_item.version}"{/if} name="{$log_item.key}" href="javascript:void(0)" class="{if $log_item.paid}buy_icon{else}{$log_item.status}_icon remote_{$log_item.status}{/if}">
                                {if ($log_item.version.0 > $log_item.current.0) && $log_item.current.0}
                                    {$lang.upgrade}
                                {else}
                                    {if $log_item.paid}{$lang.buy_plugin}{else}{$lang[$log_item.status]}{/if}
                                {/if}
                            </a>
                        {else}
                            <span class="not-compatible">{$lang.plugin_not_compatible}</span>
                        {/if}
                    {else}
                        <span class="gray-border">{$lang.you_use}: {$log_item.current}</span>
                    {/if}
                {/if}
                
                <div class="grey_13">
                    {$log_item.comment}
                </div>
            </div>
        </td>
    </tr>
    {/foreach}
    </table>
{else}
    <div class="box-center purple_13">{$lang.flynax_connect_fail}</div>
{/if}

<!-- flynax plugins log DOM end -->
