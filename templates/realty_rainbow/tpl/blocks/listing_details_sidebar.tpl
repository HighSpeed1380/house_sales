<!-- listing details sidebar -->

{rlHook name='listing_details_sidebar'}

<!-- seller info -->
{if !$pageInfo.Listing_details_inactive}
<section class="side_block no-header seller-short{if !$seller_info.Photo} no-picture{/if}">
	<div>
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_seller.tpl' sidebar=true}
	</div>
</section>
{/if}
<!-- seller info end -->

<!-- map -->
{if !$listing.location && $config.map_module && $location.direct && (!$listing_type.Photo || !$photos)}
	<section title="{$lang.expand_map}" class="side_block no-style map-capture">
		<img alt="{$lang.expand_map}" 
             src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180}" 
             srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180 scale=2} 2x" />
		<span class="media-enlarge"><span></span></span>
	</section>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_static_map.tpl'}
{/if}

<!-- listing details sidebar end -->
