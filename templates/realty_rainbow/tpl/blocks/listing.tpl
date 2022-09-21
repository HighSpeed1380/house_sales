<!-- listing item -->

{rlHook name='listingTop'}

{if $listing.Listing_type}
	{assign var='listing_type' value=$listing_types[$listing.Listing_type]}
{/if}

<article class="item{if $listing.Featured} featured{/if}{if !$listing_type.Photo} no-image{/if} two-inline col-sm-4{if !$side_bar_exists} col-md-3{/if} {rlHook name='tplListingItemClass'}">
	<div class="navigation-column">
		<div class="before-nav">{rlHook name='listingBeforeStats'}</div>

		<ul class="nav-column d-flex justify-content-end {if !$listing.fields[$config.price_tag_field].value} stick-top{/if}">
            {rlHook name='listingNavIcons'}

            {if $config.show_call_owner_button}
            <li data-listing-id="{$listing.ID}" class="call-owner">
                <svg viewBox="0 0 14 14" class="icon grid-icon-fill">
                    <use xlink:href="#contact-icon"></use>
                </svg>
            </li>
            {/if}
		</ul>

		<span class="category-info hide">
            <a href="{$rlBase}{if $config.mod_rewrite}{$pages[$listing_type.Page_key]}/{$listing.Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages[$listing_type.Page_key]}&category={$listing.Category_ID}{/if}">
                {$listing.name}
            </a>
		</span>
	</div>

	<div class="main-column clearfix">
		{if $listing_type.Photo}
			<div class="picture{if !$listing.Main_photo} no-picture{/if}">
                <a title="{$listing.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">
                    {if $listing.Photos_count > 1}
                    <div data-id="{$listing.ID}" class="listing-picture-slider">
                        <span class="listing-picture-slider__navbar d-flex h-100 relative">
                        {section start=0 loop=$listing.Photos_count step=1 max=5 name='pics'}
                            <span class="flex-fill">
                                {if $smarty.section.pics.first}
                                <img src="{if $listing.Main_photo}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                                    {if $listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$listing.Main_photo_x2} 2x"{/if}
                                    alt="{$listing.listing_title}" />
                                {else}
                                    <img class="pic-empty-{$smarty.section.pics.iteration} d-none" src="{$rlTplBase}img/blank_10x7.gif" alt="{$listing.listing_title}" />
                                    {if $smarty.section.pics.last && $listing.Photos_count > 5}
                                    <span class="justify-content-center align-items-center text-center flex-column">
                                        <svg viewBox="0 0 54 46">
                                            <use xlink:href="#photo-cam-icon"></use>
                                        </svg>
                                        {math equation='count - 5' count=$listing.Photos_count assign='more_count'}
                                        {assign var='count_replace' value=`$smarty.ldelim`count`$smarty.rdelim`}
                                        {$lang.count_more_pictures|replace:$count_replace:$more_count}
                                    </span>
                                    {/if}
                                {/if}
                            </span>
                        {/section}
                        </span>
                    </div>
                    {else}
                        <img src="{if $listing.Main_photo}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                        {if $listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$listing.Main_photo_x2} 2x"{/if}
                        alt="{$listing.listing_title}" />
                    {/if}

                    {rlHook name='tplListingItemPhoto'}

                    {if $listing.Featured}<div class="label" title="{$lang.featured}">{$lang.featured}</div>{/if}
                </a>

                <span id="fav_{$listing.ID}" class="favorite add" title="{$lang.add_to_favorites}">
                    <svg viewBox="0 0 14 12" class="icon">
                        <use xlink:href="#favorite-icon"></use>
                    </svg>
                </span>
			</div>
		{/if}

		<ul class="ad-info{if $config.sf_display_fields} with-names{/if}">
			<li class="title">
				<a class="link-large" 
                    title="{$listing.listing_title}" 
                    {if $config.view_details_new_window}target="_blank"{/if} 
                    href="{$listing.url}">
                    {$listing.listing_title}
                </a>
			</li>
			
            {if $listing.fields.bedrooms.value || $listing.fields.bathrooms.value || $listing.fields.square_feet.value}
                <li class="services">{strip}
                    {if $listing.fields.bedrooms.value}
                        <span title="{$listing.fields.bedrooms.name}" class="badrooms">{$listing.fields.bedrooms.value}</span>
                    {/if}
                    {if $listing.fields.bathrooms.value}
                        <span title="{$listing.fields.bathrooms.name}" class="bathrooms">{$listing.fields.bathrooms.value}</span>
                    {/if}
                    {if $listing.fields.square_feet.value}
                        <span title="{$listing.fields.square_feet.name}" class="square_feet">{$listing.fields.square_feet.value}</span>
                    {/if}
                {/strip}</li>
            {/if}
            
			<li class="fields">{strip}
				{assign var='short_form_fields' value=0}
				{foreach from=$listing.fields item='item' key='field' name='fListings'}
					{if empty($item.value) || !$item.Details_page || ($item.Key == $config.price_tag_field || $item.Key|in_array:$tpl_settings.listing_grid_except_fields)}{continue}{/if}

					{if $config.sf_display_fields}
						<div class="table-cell small clearfix">
							<div class="name">{$item.name}</div>
							<div class="value">{$item.value}</div>
						</div>
					{else}
					<span>{$item.value}</span>
					{/if}

					{assign var='short_form_fields' value=$short_form_fields+1}
				{/foreach}

				{rlHook name='listingAfterFields'}
			{/strip}</li>

			<li class="system">
				{if $listing.fields[$config.price_tag_field].value}
					<span class="price-tag">
						<span>{$listing.fields[$config.price_tag_field].value}</span>
						{if $listing.sale_rent == 2 && $listing.fields.time_frame.value}
                            / {$listing.fields.time_frame.value}
                        {/if}
					</span>
				{/if}

				{if $config.sf_display_fields && $short_form_fields > 2}
					<div class="stat-line">{rlHook name='listingAfterStats'}</div>
				{/if}
			</li>

			{if !$config.sf_display_fields || $config.sf_display_fields && $short_form_fields <= 2}
				<ol>
					<div class="stat-line">{rlHook name='listingAfterStats'}</div>
				</ol>
			{/if}
		</ul>
	</div>
</article>

<!-- listing item end -->
