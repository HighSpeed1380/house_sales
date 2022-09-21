<!-- grid navigation bar for accounts -->

{assign var='grid_mode' value=$smarty.cookies.grid_mode_account}
{if !$grid_mode}
	{assign var='grid_mode' value='grid'}
{/if}

{php}
	$types = array('asc' => 'ascending', 'desc' => 'descending'); $this -> assign('sort_types', $types);
	$sort = array('price', 'number', 'custom', 'date'); $this -> assign('sf_types', $sort);
{/php}

<div class="grid_navbar">
	<div class="switcher">
		<div class="hook">{rlHook name='accountGridNavBar'}</div>
		<div class="buttons">{strip}
			<div data-type="grid" class="grid{if $grid_mode == 'grid'} active{/if}" title="{$lang.gallery_view}"><div><span></span><span></span><span></span><span></span></div></div>
            {if $config.map_module}
                <div data-type="map" class="map{if $grid_mode == 'map'} active{/if}" title="{$lang.map}"><div><span></span></div></div>
            {/if}
		{/strip}</div>
	</div>

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        var $buttons  = $('div.switcher > div.buttons > div');
        var $sorting  = $('div.grid_navbar > div.sorting > div.current');
        var $accounts = $('#accounts');
        var $map      = $('#accounts_map');
        var view      = readCookie('grid_mode_account');

        $buttons.click(function(){
            $buttons.filter('.active').removeClass('active');

            var view         = $(this).data('type');
            var currentClass = $accounts.attr('class').split(' ')[0];

            createCookie('grid_mode_account', view, 365);

            $(this).addClass('active');

            $accounts.attr('class', $accounts.attr('class').replace(currentClass, view));
            $accounts[view == 'map' ? 'hide' : 'show']();
            $map[view == 'map' ? 'show' : 'hide']();
            $sorting[view == 'map' ? 'addClass' : 'removeClass']('disabled');

            if (view == 'map') {
                if ($map.find('> *').length > 0
                    || typeof accounts_map_data == 'undefined'
                    || !accounts_map_data.length
                ) {
                    return;
                }

                flUtil.loadStyle(rlConfig['map_api_css']);
                flUtil.loadScript(rlConfig['map_api_js'], function(){
                    flMap.init($map, {
                        addresses: accounts_map_data,
                        zoom: rlConfig['map_default_zoom'],
                        markerCluster: true
                    });
                });
            }
        });

        if (typeof accounts_map_data == 'undefined' || accounts_map_data.length <= 0) {
            $buttons.filter('.map').remove();

            if (view == 'map') {
                $buttons.filter('.list').trigger('click');
            }
        } else if (view == 'map') {
            $buttons.filter('.map').trigger('click');
        }
    });

    {/literal}
    </script>

	{if $sorting}
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
						<li><a rel="nofollow" {if ($sort_by == $sort_key && $sort_type == $st_key) || ($field_item.default && !$sort_by && $st_key == 'asc')}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name} ({$lang[$st]})" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type={$st_key}">{$field_item.name} ({$lang[$st]})</a></li>
					{/foreach}
				{else}
					<li><a rel="nofollow" {if $sort_by == $sort_key || ($field_item.default && !$sort_by)}class="active"{/if} title="{$lang.sort_listings_by} {$field_item.name}" href="{if $config.mod_rewrite}?{else}index.php?{$pageInfo.query_string}&{/if}sort_by={$sort_key}&sort_type=asc">{$field_item.name}</a></li>
				{/if}
			{/foreach}
			{rlHook name='accountAfterSorting'}
			</ul>
		</div>
	{/if}
</div>

<!-- grid navigation bar for accounts end -->
