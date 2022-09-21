<!-- featured listings block -->

{rlHook name='featuredTop'}

{assign var='page_key' value=$listing_types.$type.Page_key}

{if !empty($listings)}
    {if !$type}
        {assign var='direct_type' value=true}
    {/if}

	<ul class="row featured clearfix{if !$type || $listing_types.$type.Photo} with-pictures{else} list{/if}">{strip}
	{foreach from=$listings item='featured_listing' key='key' name='listingsF'}
        {if $direct_type}
            {assign var='type' value=$featured_listing.Listing_type}
        {/if}

		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'featured_item.tpl'}
	{/foreach}
	{/strip}</ul>
{else}
	{if $config.mod_rewrite}
		{assign var='href' value=$rlBase|cat:$pages.add_listing|cat:'.html'}
	{else}
		{assign var='href' value=$rlBase|cat:'?page='|cat:$pages.add_listing}
	{/if}

	{assign var='link' value='<a href="'|cat:$href|cat:'">$1</a>'}
	{$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}	
{/if}

<!-- featured listings block end -->
