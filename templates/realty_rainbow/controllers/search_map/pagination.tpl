<script id="tmplPagination" type="text/x-jquery-tmpl">
{literal}

<ul class="pagination">
    <li class="navigator ls"><a title="{/literal}{$lang.previous_page}{literal}" class="button" href="javascript://">‹</a></li>
    <li class="transit">
        <span>{/literal}{$lang.page}{literal} </span>
        <input maxlength="4" type="text" value="1" size="3" />
        <span>{/literal}{$lang.of}{literal} ${pages}</span>
    </li>
    <li class="navigator rs"><a title="{/literal}{$lang.next_page}{literal}" class="button" href="javascript://">›</a></li>
</ul>

{/literal}
</script>
