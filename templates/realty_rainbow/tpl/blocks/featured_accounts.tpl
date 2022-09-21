<!-- featured accounts block -->

{if $accounts}

	<ul class="row featured accounts with-pictures">{strip}
	{foreach from=$accounts item='featured_account' key='key' name='accountF'}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_account_item.tpl'}
	{/foreach}
	{/strip}</ul>

{else}

    {pageUrl key='registration' assign='link'}
    {assign var='link' value='<a href="'|cat:$link|cat:'">$1</a>'}
    
    {$lang.no_accounts_created|regex_replace:'/\[(.+)\]/':$link}

{/if}

<!-- featured accounts block end -->