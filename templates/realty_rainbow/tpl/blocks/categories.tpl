<!-- categories block tpl -->

{if $pageInfo.Controller != 'listing_type'}
	<div class="text-notice">{$lang.lt_categories_box_failed}</div>
{else}
	{if !empty($categories)}
	
		{rlHook name='browsePreCategories'}
		{assign var='show_cat_divider' value=false}

		<span class="expander"></span>
		
		<div class="cat-tree-cont limit-height">
			<ul class="row cat-tree">{strip}
			{foreach from=$categories item='cat' name='fCats'}
				<li class="{if $block.Side == 'left'}col-md-12{elseif $block.Side == 'middle_left' || $block.Side == 'middle_right'}col-md-{if $side_bar_exists}12{else}6{/if}{else}col-md-{if $side_bar_exists}4{else}3{/if}{/if} col-sm-4">
					{*rlHook name='tplPreCategory'*}
					
					{if $listing_type.Cat_show_subcats}
					<span class="toggle">
						{if !empty($cat.sub_categories)}+{/if}
					</span>
					{/if}
					
					<a title="{if $lang[$cat.pTitle]}{$lang[$cat.pTitle]}{else}{$cat.name}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$cat.Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pageInfo.Path}&category={$cat.ID}{/if}">{$cat.name}</a>
					{if $listing_type.Cat_listing_counter}
						&nbsp;
						<span class="counter">({$cat.Count|number_format})</span>
					{/if}

					{rlHook name='tplPostCategory'}

					{if !empty($cat.sub_categories) && $listing_type.Cat_show_subcats}
					<ul class="sub-cats">
						{foreach from=$cat.sub_categories item='sub_cat' name='subCatF'}
							<li>
								{rlHook name='tplPreSubCategory'}
								<a title="{if $lang[$sub_cat.pTitle]}{$lang[$sub_cat.pTitle]}{else}{$sub_cat.name}{/if}" href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$sub_cat.Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pageInfo.Path}&category={$sub_cat.ID}{/if}">{$sub_cat.name}</a>
								{if $listing_type.Cat_listing_counter}
									&nbsp;
									<span class="counter">({$sub_cat.Count|number_format})</span>
								{/if}
							</li>
						{/foreach}
					</ul>
					{/if}
				</li>
			{/foreach}
			{/strip}</ul>

			<div class="cat-toggle hide">...</div>
		</div>
		
		{rlHook name='browsePostCategories'}
	
	{elseif empty($categories) && !$category.ID}
		<div class="info">{$lang.listing_type_no_categories}</div>
	{elseif empty($categories)}
		{math assign='bc_count' equation='count-2' count=$bread_crumbs|@count}
		<a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}{if $bread_crumbs[$bc_count].Path}/{$bread_crumbs[$bc_count].Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}.html{/if}{else}?page={$pageInfo.Path}{if $bread_crumbs[$bc_count].ID}&category={$bread_crumbs[$bc_count].ID}{/if}{/if}">&larr; {$bread_crumbs[$bc_count].name}</a>
	{/if}
{/if}

<!-- categories block tpl -->