<!-- map search tpl -->

<svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    {include file='../img/svg/userLocation.svg'}
</svg>

<div class="search-map-container">
    <div id="map_area">
        <div id="map_listings" class="map-listings-container">
            <div id="listings_area">
                <div id="search_area">
                    {if $search_forms}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'horizontal_search.tpl'}
                    {else}
                        {$lang.search_form_empty}
                    {/if}
                </div>

                <div id="listings_cont">
                    <header class="progress">
                        <div class="caption"></div>
                        <div class="loading">{$lang.loading}</div>
                    </header>
                    <div class="wrapper">
                        <div class="clearfix"></div>
                        <footer>
                            {include file='footer_data.tpl' no_rss=true}
                        </footer>
                    </div>
                </div>
            </div>

            <div class="control btn"></div>
        </div>
        <div class="map-search">
            <div id="map_container"></div>
            <span class="loading-container"><span class="loading-spinner"></span></span>
        </div>
        <div class="mobile-navigation hide"><div class="search"></div><div class="list"></div><div class="map active"></div></div>
    </div>
</div>

{include file=$controllerDir|cat:'search_map/listing.tpl'}
{include file=$controllerDir|cat:'search_map/pagination.tpl'}

{mapsAPI}

{addJS file=$rlTplBase|cat:'controllers/search_map/search_map.js'}

<script class="fl-js-dynamic">
var default_map_location = '{$default_map_location|escape:'quotes'}';
var default_map_coordinates = [{if $smarty.post.loc_lat && $smarty.post.loc_lng}{$smarty.post.loc_lat},{$smarty.post.loc_lng}{else}{$config.search_map_location}{/if}];
var default_map_zoom = {if $config.search_map_location_zoom}{$config.search_map_location_zoom}{else}14{/if};
var listings_limit_desktop = {if $config.map_search_listings_limit}{$config.map_search_listings_limit}{else}500{/if};
var listings_limit_mobile = {if $config.map_search_listings_limit_mobile}{$config.map_search_listings_limit_mobile}{else}75{/if};

lang['count_properties'] = '{$lang.count_properties}';
lang['number_property_found'] = '{$lang.number_property_found}';
lang['no_properties_found'] = '{$lang.no_properties_found}';
lang['map_listings_request_empty'] = '{$lang.map_listings_request_empty}';
lang['enter_a_location'] = '{$lang.enter_a_location}';
lang['short_price_k'] = '{$lang.short_price_k}';
lang['short_price_m'] = '{$lang.short_price_m}';
lang['short_price_b'] = '{$lang.short_price_b}';

{literal}
mapSearch.init({
    mapContainer: $('#map_container'),
    mapCenter: default_map_coordinates,
    mapAltLocation: default_map_location,
    mapZoom: default_map_zoom,
    listingGrid: $('#map_listings'),
    searchForm: $('.search-block-content'),
    tabBar: $('.tabs'),
    desktopLimit: listings_limit_desktop,
    mobileLimit: listings_limit_mobile
});
{/literal}
</script>

<!-- map search tpl end -->
