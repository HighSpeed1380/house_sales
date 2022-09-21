<!-- flynax news DOM -->

{if $rss_content}
    <table class="sTable">
    {foreach from=$rss_content item='rss'}
    <tr>
        <td class="list-date">
            {$rss.date|date_format:'%d'}
            <div>{$rss.date|date_format:'%b'}</div>
        </td>
            
        <td class="list-body">
            <a target="_blank" class="green_14" href="{$rss.link}" title="{$rss.title}">{$rss.title}</a>
            <div class="grey_13">
                {$rss.description|strip_tags|truncate:"140":false}{if $rss.description|strlen > 140}...{/if}
            </div>
        </td>
    </tr>
    {/foreach}
    </table>
{else}
    <div class="box-center purple_13">{$lang.flynax_connect_fail}</div>
{/if}

<!-- flynax news DOM end -->
