<!-- pagination tpl -->

<ul class="pagination">
    {if $pagination.current > 1}
        <li class="navigator ls">
            <a title="{$lang.prev_page}" class="button" href="{if $pagination.current == 2}{$pagination.first_url}{else}{$pagination.tpl_url|replace:'[pg]':$pagination.current-1}{/if}">&lsaquo;</a>
        </li>
    {/if}
    <li class="transit">
        <span>{$lang.page} </span>
        <input maxlength="{$pagination.pages|@count_characters}" type="text" size="{$pagination.pages|@count_characters}" value="{$pagination.current}">
        <input type="hidden" name="stats" value="{$pagination.current}|{$pagination.pages}">
        <input type="hidden" name="pattern" value="{$pagination.tpl_url}">
        <input type="hidden" name="first" value="{$pagination.first_url}">
        <span> {$lang.of} {$pagination.pages}</span>
    </li>

    {if $pagination.current < $pagination.pages}
        <li class="navigator rs">
            <a title="{$lang.next_page}" class="button" href="{$pagination.tpl_url|replace:'[pg]':$pagination.current+1}">&rsaquo;</a>
        </li>
    {/if}
</ul>

<script class="fl-js-dynamic">{literal}
    flUtil.loadScript(rlConfig.tpl_base + 'components/pagination/_pagination.js', function() {
        flPaginationHandler($('ul.pagination'));
    });
{/literal}</script>

<!-- pagination tpl end -->
