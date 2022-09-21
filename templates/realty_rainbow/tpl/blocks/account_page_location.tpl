<!-- contact person location tpl -->

{if $contact}
{assign var='account' value=$contact}
{/if}

<div class="location-cont clearfix">
	<div class="location-info">
		{foreach from=$account.Fields item='item' name='fListings'}
			{if $item.Map && !empty($item.value) && $item.Details_page}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' small=true}
				{assign var='map_fields' value=true}
			{/if}
		{/foreach}
	</div>

	{if $config.map_module && $location.direct}
		<div title="{$lang.expand_map}" class="map-capture">
			<img alt="{$lang.expand_map}" 
                 src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180}" 
                 srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180 scale=2} 2x" />
			<span class="media-enlarge"><span></span></span>
		</div>

        {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}

        {mapsAPI assign='mapAPI'}

        <script class="fl-js-dynamic">
        rlConfig['mapAPI'] = [];
        rlConfig['mapAPI']['css'] = JSON.parse('{$mapAPI.css|@json_encode}');
        rlConfig['mapAPI']['js']  = JSON.parse('{$mapAPI.js|@json_encode}');
        {literal}

        // Static map handler
        flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
            $('.map-capture').popup({
                fillEdge: true,
                scroll: false,
                content: '<div id="map_fullscreen"></div>',
                onShow: function(){
                    flUtil.loadStyle(rlConfig['mapAPI']['css']);
                    flUtil.loadScript(rlConfig['mapAPI']['js'], function(){
                        window.location.hash = 'map-fullscreen';

                        var accountMap = new mapClass();

                        accountMap.init($('#map_fullscreen'), {
                            control: 'topleft',
                            zoom: {/literal}{$config.map_default_zoom}{literal},
                            addresses: [{
                                latLng: '{/literal}{$location.direct}',
                                content: '{$location.show}{literal}'
                            }]
                        });
                    });
                },
                onClose: function(){
                    history.pushState('', document.title, window.location.pathname + window.location.search);
                    this.destroy();
                }
            });

            $(window).on('hashchange', function(e){
                var oe = e.originalEvent;

                if (oe.oldURL.indexOf('#map-fullscreen') >= 0 && oe.newURL.indexOf('#map-fullscreen') < 0) {
                    $('.popup .close').trigger('click');
                }
            });
        });

        {/literal}
        </script>
	{else}
		{if !$map_fields}
			<div title="{$lang.expand_map}">{$lang.no_account_location}</div>
		{/if}
	{/if}
</div>

<!-- contact person location tpl end -->
