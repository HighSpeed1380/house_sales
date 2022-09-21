<!-- grid navigation bar -->

{assign var='grid_mode' value=$smarty.cookies.grid_mode}

{if !$grid_mode}
	{assign var='grid_mode' value='list'}
{/if}

{if $listing_type && !$listing_type.Photo}
	{assign var='grid_mode' value='list'}
{/if}

{if $grid_mode == 'map' && !$config.map_module}
    {assign var='grid_mode' value='grid'}
{/if}

{php}
	$types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
	$sort = array('price', 'number', 'date', 'mixed'); $this -> assign('sf_types', $sort);
{/php}

<div class="grid_navbar listings-area">
	<div class="switcher">{strip}
		<div class="hook">{rlHook name='browseGridNavBar'}</div>
		<div class="buttons">
			<div data-type="list" class="list{if $grid_mode == 'list'} active{/if}" title="{$lang.list_view}"><div><span></span><span></span><span></span><span></span><span></span><span></span></div></div>
			{if $listing_type && !$listing_type.Photo}{else}
				<div data-type="grid" class="grid{if $grid_mode == 'grid'} active{/if}" title="{$lang.gallery_view}"><div><span></span><span></span><span></span><span></span></div></div>
			{/if}
            {if $config.map_module}
			<div data-type="map" class="map{if $grid_mode == 'map'} active{/if}" title="{$lang.map}"><div><span></span></div></div>
            {/if}
		{/strip}</div>
	</div>

    <script class="fl-js-dynamic">
    var default_grid_view = '{$tpl_settings.default_listing_grid_mode}';
    {literal}

    $(function(){
        var $buttons  = $('div.switcher > div.buttons > div');
        var $sorting  = $('div.grid_navbar > div.sorting > div.current');
        var $listings = $('#listings');
        var $map      = $('#listings_map');
        var view      = readCookie('grid_mode');

        $buttons.click(function(){
            $buttons.filter('.active').removeClass('active');

            var view         = $(this).data('type');
            var currentClass = $listings.attr('class').split(' ')[0];

            createCookie('grid_mode', view, 365);

            $(this).addClass('active');

            $listings.attr('class', $listings.attr('class').replace(currentClass, view));
            $listings[view == 'map' ? 'hide' : 'show']();
            $map[view == 'map' ? 'show' : 'hide']();
            $sorting[view == 'map' ? 'addClass' : 'removeClass']('disabled');

            if (view == 'map') {
                if ($map.find('> *').length > 0
                    || typeof listings_map_data == 'undefined'
                    || !listings_map_data.length
                ) {
                    return;
                }

                flUtil.loadStyle(rlConfig['map_api_css']);
                flUtil.loadScript(rlConfig['map_api_js'], function(){
                    flMap.init($map, {
                        addresses: listings_map_data,
                        zoom: rlConfig['map_default_zoom'],
                        markerCluster: true
                    });
                });
            }
        });

        if (typeof listings_map_data == 'undefined' || listings_map_data.length <= 0) {
            $buttons.filter('.map').remove();

            if (view == 'map') {
                $buttons.filter('.list').trigger('click');
            }
        } else if (view == 'map') {
            $buttons.filter('.map').trigger('click');
        }

        if (media_query == 'mobile' && view != 'map') {
            $buttons.filter('.' + default_grid_view).trigger('click');
        }
    });

    {/literal}
    </script>

	{if $sorting}
		<div class="sorting">
			<div class="current{if $grid_mode == 'map'} disabled{/if}">
				{$lang.sort_by}:
				<span class="link">{$sorting[$sort_by].name}</span>
				<span class="arrow"></span>
			</div>
			<ul class="fields">
			{foreach from=$sorting item='field_item' key='sort_key' name='fSorting'}
				{if isset($field_item.Details_page) && $field_item.Details_page == '0' || $field_item.Type == 'checkbox'}{continue}{/if}

				{if $field_item.Type|in_array:$sf_types}
					{foreach from=$sort_types key='st_key' item='st'}
						<li><a rel="nofollow" {if $sort_by == $sort_key && $sort_type == $st_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
					{/foreach}
				{else}
					<li><a rel="nofollow" {if $sort_by == $sort_key}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
				{/if}
			{/foreach}
			{rlHook name='browseAfterSorting'}
			</ul>
		</div>
	{/if}
</div>

<!-- grid navigation bar end -->
