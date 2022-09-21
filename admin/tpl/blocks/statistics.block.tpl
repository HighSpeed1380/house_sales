<!-- statistics block -->

{assign var="allow_listings" value=true}
{assign var="allow_custom_categories" value=true}
{assign var="allow_accounts" value=true}
{assign var="allow_messages" value=true}

{if $smarty.session.sessAdmin.type == 'limited'}
    {if !$aRights.listings}
        {assign var="allow_listings" value=false}
    {/if}

    {if !$aRights.custom_categories}
        {assign var="allow_custom_categories" value=false}
    {/if}

    {if !$aRights.all_accounts}
        {assign var="allow_accounts" value=false}
    {/if}

    {if !$aRights.contacts}
        {assign var="allow_messages" value=false}
    {/if}
{/if}

<table class="fixed">
<tr>
    {if $allow_listings || $allow_custom_categories}
        <td>
            {if $allow_listings}
                <table class="stat">
                <tr class="header">
                    <td colspan="3">{$content_stat.listings.name}</td>
                </tr>
                <tr>
                    <td class="line"><div><img class="color total" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=listings">{$lang.total}</a></div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=listings">{$content_stat.listings.total}</a></td>
                </tr>
                {assign var='replace_days' value=`$smarty.ldelim`days`$smarty.rdelim`}
                {foreach from=$content_stat.listings.items item='count' key='stat_key'}
                <tr>
                    <td class="line"><div><img class="color {$stat_key}" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=listings&amp;status={$stat_key}">{$lang.$stat_key}</a>{if $stat_key == 'new'}<span> | {$lang.over_days|replace:$replace_days:$config.new_period}</span>{/if}</div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=listings&amp;status={$stat_key}">{if $count}{$count}{else}0{/if}</a></td>
                </tr>
                {/foreach}
                </table>
            {/if}

            {if $allow_custom_categories}
                <table class="stat">
                <tr class="header">
                    <td colspan="3">{$content_stat.categories.name}</td>
                </tr>
                <tr>
                    <td class="line"><div><img class="color new" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=custom_categories">{$lang.new}</a></div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=custom_categories">{$content_stat.categories.new}</a></td>
                </tr>
                </table>
            {/if}
        </td>
        <td class="divider"></td>
    {/if}

    {if $allow_accounts || $allow_messages}
        <td>
            {if $allow_accounts}
                <table class="stat">
                <tr class="header">
                    <td colspan="3">{$content_stat.accounts.name}</td>
                </tr>
                <tr>
                    <td class="line"><div><img class="color total" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=accounts">{$lang.total}</a></div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=accounts">{$content_stat.accounts.total}</a></td>
                </tr>
                {assign var='replace_days' value=`$smarty.ldelim`days`$smarty.rdelim`}
                {foreach from=$content_stat.accounts.items item='count' key='stat_key'}
                <tr>
                    <td class="line"><div><img class="color {$stat_key}" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=accounts&amp;status={$stat_key}">{$lang.$stat_key}</a>{if $stat_key == 'new'}<span> | {$lang.over_days|replace:$replace_days:$config.new_period}</span>{/if}</div></td>
                    <td class="counter {$stat_key}"><a href="{$rlBase}index.php?controller=accounts&amp;status={$stat_key}">{if $count}{$count}{else}0{/if}</a></td>
                </tr>
                {/foreach}
                </table>
            {/if}

            {if $allow_messages}
                <table class="stat">
                <tr class="header">
                    <td colspan="3">{$content_stat.contacts.name}</td>
                </tr>
                <tr>
                    <td class="line"><div><img class="color total" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=contacts">{$lang.total}</a></div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=contacts">{$content_stat.contacts.total}</a></td>
                </tr>
                <tr>
                    <td class="line"><div><img class="color new" src="{$rlBase}img/blank.gif" alt="" /><a href="{$rlBase}index.php?controller=contacts&amp;status=new">{$lang.new}</a></div></td>
                    <td class="counter"><a href="{$rlBase}index.php?controller=contacts&amp;status=new">{if $content_stat.contacts.new}{$content_stat.contacts.new}{else}0{/if}</a></td>
                </tr>
                </table>
            {/if}
        </td>
    {/if}
</tr>
</table>

{if $plugin_statistics}
    <table class="fixed">
    <tr>
        {foreach from=$plugin_statistics item='plg_stat' name='plg_statF'}
            <td>
                <table class="stat">
                <tr class="header">
                    <td colspan="3">{$plg_stat.name}</td>
                </tr>
                {foreach from=$plg_stat.items item='plg_item'}
                <tr>
                    <td class="line"><div><a href="{$plg_item.link}">{$plg_item.name}</a>{if $plg_item.note}<span>{$plg_item.note}</span>{/if}</div></td>
                    <td class="counter"><a href="{$plg_item.link}">{$plg_item.count}</a></td>
                </tr>
                {/foreach}
                </table>
            </td>
            {if $smarty.foreach.plg_statF.iteration%2 == 0}
                {if !$smarty.foreach.plg_statF.last}</tr><tr>{/if}
            {else}
                <td class="divider"></td>
            {/if}
        {/foreach}
        {if $smarty.foreach.plg_statF.total%2 != 0}
            <td></td>
        {/if}
    </tr>
    </table>
{/if}

<!-- statistics block end -->
