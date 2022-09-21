<!-- account search -->

<span class="expander"></span>

<form class="light-inputs" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$search_results_url}.html{else}?page={$pageInfo.Path}&amp;{$search_results_url}{/if}">
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl'}

	<input type="hidden" name="search" value="true" />
	<input type="submit" name="search" value="{$lang.search}" />
</form>

<!-- account search end -->