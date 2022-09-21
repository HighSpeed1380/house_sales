<!-- my listings -->

{if !empty($listings)}

	{if $sorting}

	{php}
		$types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
		$sort = array('price', 'number', 'date'); $this -> assign('sf_types', $sort);
	{/php}
	
	<div class="grid_navbar">
		<div class="sorting">
			<div class="current{if $grid_mode == 'map'} disabled{/if}">
				{$lang.sort_by}: 
				<span class="link">{if $sort_by}{$sorting[$sort_by].name}{else}{$lang.date}{/if}</span>
				<span class="arrow"></span>
			</div>
			<ul class="fields">
			{foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
				{if $field_item.Type|in_array:$sf_types}
					{foreach from=$sort_types key='st_key' item='st'}
						<li><a rel="nofollow" {if $sort_by == $sort_key && $sort_type == $st_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
					{/foreach}
				{else}
					<li><a rel="nofollow" {if $sort_by == $sort_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
				{/if}
			{/foreach}
			{rlHook name='myListingsAfterSorting'}
			</ul>
		</div>
	</div>
	{/if}
	
	{rlHook name='myListingsBeforeListings'}
	
	<section id="listings" class="my-listings list">
	{foreach from=$listings item='listing' key='key'}
        {if $listing.Subscription_ID}
            {assign var='hasSubscriptions' value=true}
        {/if}
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'my_listing.tpl'}
	{/foreach}
	</section>

	<!-- paging block -->
    {if $search_results_mode && $refine_search_form}
        {assign var='myads_paging_url' value=$search_results_url}
    {else}
        {assign var='myads_paging_url' value=false}
    {/if}
    {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$myads_paging_url method=$listing_type.Submit_method}
	<!-- paging block end -->

	<script class="fl-js-dynamic">{literal}
	$(document).ready(function(){
		$('.my-listings .delete').each(function(){
			$(this).flModal({
				caption: '{/literal}{$lang.warning}{literal}',
				content: '{/literal}{$lang.notice_delete_listing}{literal}',
				prompt: 'xajax_deleteListing('+ $(this).attr('id').split('_')[2] +')',
				width: 'auto',
				height: 'auto'
			});
		});

        {/literal}{if $hasSubscriptions}{literal}
    		$('.my-listings .unsubscription').each(function() {
    			$(this).flModal({
    				caption: '',
    				content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
    				prompt: 'flSubscription.cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +', false)',
    				width: 'auto',
    				height: 'auto'
    			});
    		});
        {/literal}{/if}{literal}

		$('label.switcher-status input[type="checkbox"]').change(function() {
			var element = $(this);
			var id = $(this).val();
			var status = $(this).is(':checked') ? 'active' : 'approval';

			$.getJSON(
                rlConfig['ajax_url'],
                {mode: 'changeListingStatus', item: id, value: status, lang: rlLang},
                function(response) {
                    if (response) {
                    	if (response.status == 'ok') {
    						printMessage('notice', response.message_text);
                    	} else {
    						printMessage('error', response.message_text);
    						element.prop('checked', false);
                    	}
                    }
                }
            );
		});

		$('label.switcher-featured input[type="checkbox"]').change(function() {
			var element = $(this);
			var id = $(this).val();
			var status = $(this).is(':checked') ? 'featured': 'standard';

			$.getJSON(
                rlConfig['ajax_url'],
                {mode: 'changeListingFeaturedStatus', item: id, value: status, lang: rlLang},
                function(response) {
                    if (response) {
                    	if (response.status == 'ok') {
                    		if (status == 'featured') {
                    			$('article#listing_' + id).addClass('featured');
                    			$('article#listing_'+ id +' div.nav div.info .picture').prepend('<div class="label"><div title="{/literal}{$lang.featured}{literal}">{/literal}{$lang.featured}{literal}</div></div></div>');
    						} else {
                    			$('article#listing_'+ id +' div.nav div.info .picture').find('div.label').remove();
                    			$('article#listing_' + id).removeClass('featured');
    						}
    						printMessage('notice', response.message_text);
                    	} else {
    						printMessage('error', response.message_text);
    						if (element.is(':checked')) {
    							element.prop('checked', false);
    						} else {
    							element.prop('checked', 'checked');
    						}
                    	}
                    }
                }
            );
		});
	});
    {/literal}
    </script>
{else}
	<div class="info">
		{assign var='link' value='<a href="'|cat:$add_listing_href|cat:'">$1</a>'}
		{$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
	</div>
{/if}

{rlHook name='myListingsBottom'}

{if $hasSubscriptions}
    {addJS file=$rlTplBase|cat:'js/subscription.js' id='subscription'}
{/if}

<!-- my listings end -->
