<!-- home page content tpl -->

{if $home_slides}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'content_slider.tpl'}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'horizontal_search.tpl'}

<!-- home page content tpl end -->
