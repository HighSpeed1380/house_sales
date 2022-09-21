<!-- listings tpl -->

<!-- tabs -->
{if $listing_types|@count > 1}
	<ul class="tabs tabs-hash">
		{foreach from=$listing_types item='tab' key='lt_key' name='tabsF'}
			<li class="{if $requested_type == $lt_key}active{/if}" lang="{$lt_key}" id="tab_{$lt_key|replace:'_':''}">
                <a href="#{$lt_key|replace:'_':''}" data-target="{$lt_key|replace:'_':''}">{$tab.name}</a>
            </li>
		{/foreach}
	</ul>
	
	<script class="fl-js-dynamic">
	{literal}
	
	$(document).ready(function(){

        $('.tab_area').data('loaded', false);

		$('ul.tabs li').click(function(){
			var key = $(this).attr('lang');
            var $area = $('#area_'+key);

            if ( $area.find('#listings').length == 0 && !$area.data('loaded') ) {
                $area.data('loaded', true);
				xajax_loadRecentlyAdded(key);
			}
		});
		
		if ( flynax.getHash() ) {

			$('ul.tabs li#tab_' + flynax.getHash().replace('_tab', '')).trigger('click');
		}
	});
	
	{/literal}
	</script>
{/if}
<!-- tabs end -->

{foreach from=$listing_types item='tab' key='lt_key' name='tabsF'}
	<div class="tab_area{if $requested_type != $lt_key} hide{/if}" id="area_{$lt_key|replace:'_':''}">
		{if $requested_type == $lt_key}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'recently.tpl'}
		{elseif $requested_type != $lt_key}
			<span class="text-notice">{$lang.loading}</span>
		{/if}
	</div>
{/foreach}

<!-- listings tpl end -->