<!-- my favorites -->

{if !empty($listings)}

	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}
	
	{rlHook name='favouriteBeforeListings'}
	
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl'}
	
	<!-- paging block -->
	{paging calc=$pInfo.calc total=$listings current=$pInfo.current per_page=$config.listings_per_page controller=$pages.my_favorites}
	<!-- paging block end -->

{else}
	<div class="info">{$lang.no_favorite}</div>
{/if}

<!-- my favorites end -->