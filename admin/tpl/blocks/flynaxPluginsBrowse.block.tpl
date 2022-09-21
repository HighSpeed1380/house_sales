<!-- browse plugins block tpl -->

<ul class="browse_plugins">
{foreach from=$plugins item='plugin'}
    <li>
        <table class="sTable">
        <tr>
            <td class="list-date">
                {$plugin.date|date_format:'%d'}
                <div>{$plugin.date|date_format:'%b'}</div>
            </td>
                
            <td class="list-body">
                <div class="changelog_item">
                    <a target="_blank" class="green_14" href="https://www.flynax.com/plugins/{$plugin.path|lower}.html" title="{$lang.learn_more_about} {$log_item.name}">{$plugin.name}</a>
                    <span class="dark_13" style="padding: 0 0 0 10px;">{$plugin.version}</span> 
                    
                    <div>
                        {if isset($plugin.compatible) && $plugin.compatible|version_compare:$config.rl_version > 0}
                            <span class="not-compatible">{$lang.plugin_not_compatible}</span>
                        {else}
                            {if $plugin.paid}
                                <a title="{$lang.buy_plugin_title}" name="{$plugin.key}" href="javascript:void(0)" class="buy_icon">{$lang.buy_plugin}</a>
                            {else}
                                <a name="{$plugin.key}" href="javascript:void(0)" class="install_icon remote_install"><span></span>{$lang.install}</a>
                            {/if}
                        {/if}
                    </div>
                </div>
            </td>
        </tr>
        </table>
    </li>
{/foreach}
</ul>
<div class="clear"></div>

<!-- browse plugins block tpl end -->
