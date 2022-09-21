<!-- side bar search form -->

{if $search_forms && $pageInfo.Key == 'home' || $pageInfo.Controller == 'listing_type'}
    {assign var='self_type_form' value=false}

    {if $pageInfo.Key == 'home' && !(bool) preg_match('/\_tab[0-9]$/', $search_forms|reset|@key)}
        {assign var='self_type_form' value=$block.Key|replace:'ltpb_':''}
    {/if}

	<section class="side_block_search light-inputs">
		{if $search_forms|@count > 1 && !$self_type_form}
			<!-- tabs -->
			<ul class="tabs tabs-hash search_tabs">
				{foreach from=$search_forms item='search_form' key='sf_key' name='stabsF'}{assign var='zindex' value=20}
                    <li id="tab_{$sf_key}" class="{if $smarty.foreach.stabsF.first}active{/if}">
                        <a href="#{$sf_key}" data-target="{$sf_key}">{$search_form.name}</a>
                    </li>
                {/foreach}
			</ul>
			<!-- tabs end -->
		{/if}

		{assign var='items_count' value=10}
	
		<div class="search-block-content{if $search_forms|@count == 1} no-tabs{/if}">
			{foreach from=$search_forms item='search_form' key='sf_key' name='sformsF'}
                {if $self_type_form && $search_form.listing_type != $self_type_form}
                    {continue}
                {/if}

				{assign var='spage_key' value=$listing_types[$search_form.listing_type].Page_key}
				{assign var='post_form_key' value=$sf_key}
				
				{if $search_forms|@count > 1}
					<div id="area_{$sf_key}" class="search_tab_area{if !$smarty.foreach.sformsF.first} hide{/if}">
				{/if}
				
				<form method="{$listing_types[$search_form.listing_type].Submit_method}" action="{$rlBase}{if $config.mod_rewrite}{$pages.$spage_key}{if $category.ID > 0}/{$category.Path}{/if}/{$search_results_url}.html{else}?page={$pages.$spage_key}&{$search_results_url}{if $category.ID > 0}&category={$category.ID}{/if}{/if}">
					<input type="hidden" name="action" value="search" />
					<input type="hidden" name="post_form_key" value="{$sf_key}" />
					
					<div class="scroller{if $block.Side == 'top' || $block.Side == 'bottom'} row{/if}">
						{if $search_form.arrange_field}
							<input type="hidden" name="f[{$search_form.arrange_field}]" value="{$search_form.arrange_value}" />
						{/if}
						
						{strip}
							{foreach from=$search_form.data item='group' name='qsearchF'}
                                {if $block.Side == 'top' || $block.Side == 'bottom'}
                                <div class="col-md-{if $side_bar_exists}4{else}3{/if} mb-3">
                                {/if}
								{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                                {if $block.Side == 'top' || $block.Side == 'bottom'}
                                </div>
                                {/if}
							{/foreach}
						{/strip}
						
						{if $group.With_picture}
						<div class="search-item{if $block.Side == 'top' || $block.Side == 'bottom'} col-md-{if $side_bar_exists}4{else}3{/if} mt-2{/if}">
							<label>
								<input name="f[with_photo]" type="checkbox" value="true" />
								{$lang.with_photos_only}
							</label>
						</div>
						{/if}

                        {strip}
                        <div class="search-button{if $block.Side == 'top' || $block.Side == 'bottom'} col-md-{if $side_bar_exists}4{else}3{/if} pt-0 ml-auto{/if}">
                            <input type="submit" name="search" value="{$lang.search}" />
                            {if !$in_category_search && $listing_types[$search_form.listing_type].Advanced_search && $listing_types[$search_form.listing_type].Advanced_search_availability}
                                <a title="{$lang.advanced_search}" href="{$rlBase}{if $config.mod_rewrite}{$pages.$spage_key}/{$advanced_search_url}.html{else}?page={$pages.$spage_key}&amp;{$advanced_search_url}{/if}">{$lang.advanced_search}</a>
                            {/if}
                        </div>
                        {/strip}
					</div>
				</form>
				
				{if $search_forms|@count > 1}
					</div>
				{/if}
			{/foreach}
		</div>
	</section>
{/if}

<!-- side bar search form end -->
