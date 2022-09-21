{strip}{php}
global $page_info;

$block = $this -> get_template_vars('block');
$side_bar_exists = $this -> get_template_vars('side_bar_exists');
$class = 'col-lg-3 col-md-4 col-sm-6 ';

if (in_array($block['Side'], array('middle', 'bottom', 'top'))) {
    $class = $side_bar_exists ? 'col-sm-6 col-md-4' : 'col-lg-3 col-md-4 col-sm-6';
} elseif (in_array($block['Side'], array('middle_left', 'middle_right'))) {
    $class = 'col-sm-6';
}

$this -> assign('box_item_class', $class);
{/php}

{rlHook name='featuredItemTop'}

<li {if $featured_listing.ID}id="fli_{$featured_listing.ID}"{/if} class="{$box_item_class} d-flex flex-column{if !$featured_listing.Main_photo} no-picture{/if}">
	{if $listing_types.$type.Photo}
        <div class="picture{if !$featured_listing.Main_photo} no-picture{/if}">
    		<a title="{$featured_listing.listing_title}" {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
                {if $featured_listing.Photos_count > 1 && $block.Side != 'left'}
                <div data-id="{$featured_listing.ID}" class="listing-picture-slider">
                    <span class="listing-picture-slider__navbar d-flex h-100 relative">
                    {section start=0 loop=$featured_listing.Photos_count step=1 max=5 name='pics'}
                        <span class="flex-fill">
                            {if $smarty.section.pics.first}
                            <img src="{if $featured_listing.Main_photo}{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                                {if $featured_listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo_x2} 2x"{/if}
                                alt="{$featured_listing.listing_title}" />
                            {else}
                                <img class="pic-empty-{$smarty.section.pics.iteration} d-none" src="{$rlTplBase}img/blank_10x7.gif" alt="{$featured_listing.listing_title}" />
                                {if $smarty.section.pics.last && $featured_listing.Photos_count > 5}
                                <span class="justify-content-center align-items-center text-center flex-column">
                                    <svg viewBox="0 0 54 46">
                                        <use xlink:href="#photo-cam-icon"></use>
                                    </svg>
                                    {math equation='count - 5' count=$featured_listing.Photos_count assign='more_count'}
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
                    <img src="{if $featured_listing.Main_photo}{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                    {if $featured_listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo_x2} 2x"{/if}
                    alt="{$featured_listing.listing_title}" />
                {/if}
                {rlHook name='tplFeaturedItemPhoto'}
    		</a>

            <span id="fav_{$featured_listing.ID}" class="favorite add" title="{$lang.add_to_favorites}">
                <svg viewBox="0 0 14 12" class="icon">
                    <use xlink:href="#favorite-icon"></use>
                </svg>
            </span>
        </div>
	{/if}

	<ul class="ad-info flex-fill">
		<li class="title" title="{$featured_listing.fields.title.value|strip_tags}">
			<a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}">
				{$featured_listing.listing_title}
			</a>
		</li>

        {if $featured_listing.fields.bedrooms.value || $featured_listing.fields.bathrooms.value || $featured_listing.fields.square_feet.value}
            <li class="services">{strip}
                {if $featured_listing.fields.bedrooms.value}
                    <span title="{$featured_listing.fields.bedrooms.name}" class="badrooms">{$featured_listing.fields.bedrooms.value}</span>
                {/if}
                {if $featured_listing.fields.bathrooms.value}
                    <span title="{$featured_listing.fields.bathrooms.name}" class="bathrooms">{$featured_listing.fields.bathrooms.value}</span>
                {/if}
                {if $featured_listing.fields.square_feet.value}
                    <span title="{$featured_listing.fields.square_feet.name}" class="square_feet">{$featured_listing.fields.square_feet.value}</span>
                {/if}
            {/strip}</li>
        {/if}

		<li class="fields">
			{foreach from=$featured_listing.fields item='item' key='field' name='fieldsF'}
				{if empty($item.value) || !$item.Details_page || ($item.Key == $config.price_tag_field || $item.Key|in_array:$tpl_settings.listing_grid_except_fields)}{continue}{/if}

				<span id="flf_{$featured_listing.ID}_{$item.Key}">{$item.value}</span>
			{/foreach}
		</li>

		<li class="two-inline price_tag">
			<nav class="icons">
                {rlHook name='tplFeaturedItemIcon'}

                {if $config.show_call_owner_button}
                <span data-listing-id="{$featured_listing.ID}" class="call-owner">
                    <svg viewBox="0 0 14 14" class="icon grid-icon-fill">
                        <use xlink:href="#contact-icon"></use>
                    </svg>
                </span>
                {/if}
            </nav>

			{if $featured_listing.fields[$config.price_tag_field].value}
				<div>
					<span>{$featured_listing.fields[$config.price_tag_field].value}</span>
					{if $featured_listing.sale_rent == 2 && $featured_listing.fields.time_frame.value}
                        &nbsp;/ {$featured_listing.fields.time_frame.value}
                    {/if}
				</div>
			{/if}
		</li>

		{rlHook name='tplFeaturedItemAdInfo'}
	</ul>
</li>

{rlHook name='featuredItemBottom'}

{/strip}
