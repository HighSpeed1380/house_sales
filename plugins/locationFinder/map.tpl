<!-- location finder map -->

<svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    {include file='../img/svg/userLocation.svg'}
</svg>

{addJS file=$smarty.const.RL_LIBS_URL|cat:'maps/geocoder.js'}

{mapsAPI}

<div id="lf_container"{if $config.locationFinder_position != 'top'} class="hide"{/if}>
    {if $config.locationFinder_position == 'top' || $config.locationFinder_position == 'bottom'}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='lf_fieldset' name=$lang.locationFinder_fieldset_caption}
    {/if}

    <div class="submit-cell">
        <div class="name">{$lang.locationFinder_location} <img src="{$rlTplBase}img/blank.gif" class="qtip" title="{$lang.locationFinder_hint}" /></div>
        <div class="field">
            <div id="lf_map" style="height: 400px;"></div>

            <input id="lf_lat" name="f[lf][lat]" type="hidden" value="{$smarty.post.f.lf.lat}" />
            <input id="lf_lng" name="f[lf][lng]" type="hidden" value="{$smarty.post.f.lf.lng}" />
            <input id="lf_zoom" name="f[lf][zoom]" type="hidden" value="{$smarty.post.f.lf.zoom}" />
        </div>
    </div>

    {if $config.locationFinder_position == 'top' || $config.locationFinder_position == 'bottom'}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
    {/if}
</div>

<script class="fl-js-dynamic">
lang['locationFinder_address_hint'] = "{$lang.locationFinder_address_hint}";
lang['locationFinder_drag_notice'] = "{$lang.locationFinder_drag_notice}";

{literal}

$(function(){
    {/literal}
    var position    = '{$config.locationFinder_position}';
    var group       = '{$config.locationFinder_group}';
    var append_type = '{$config.locationFinder_type}';
    var options     = {literal} { {/literal}
        postLat: {if $smarty.post.f.lf.lat}{$smarty.post.f.lf.lat}{else}false{/if},
        postLng: {if $smarty.post.f.lf.lng}{$smarty.post.f.lf.lng}{else}false{/if},
        postZoom: {if $smarty.post.f.lf.zoom}{$smarty.post.f.lf.zoom}{else}false{/if},
        defaultLocation: '{$config.locationFinder_default_location}',
        containerID: '#lf_container',
        mapElementID: '#lf_map',
        zoom: {$config.locationFinder_map_zoom},
        useVisitorLocation: {if $config.locationFinder_use_location}true{else}false{/if},
        useNeighborhood: {if $config.locationFinder_use_neighborhood}true{else}false{/if},
        ipLocation: "{$smarty.session.GEOLocationData->Country_name}, {$smarty.session.GEOLocationData->Region}, {$smarty.session.GEOLocationData->City}",
        mapping: {if $config.locationFinder_mapping}true{else}false{/if},
        mappingCountry: {if $config.locationFinder_mapping_country}'{$config.locationFinder_mapping_country}'{else}false{/if},
        mappingState: {if $config.locationFinder_mapping_state}'{$config.locationFinder_mapping_state}'{else}false{/if},
        mappingCity: {if $config.locationFinder_mapping_city}'{$config.locationFinder_mapping_city}'{else}false{/if},
        geocoding: {if !isset($config.geocoding_provider) || $config.geocoding_provider == 'google'}true{else}false{/if},
        mfFields: {if $geo_filter_data.location_listing_fields}JSON.parse('{$geo_filter_data.location_listing_fields|@array_keys|@json_encode}'){else}false{/if}
    {literal}
    };

    var $container  = $('#lf_container');
    var $form       = $('#controller_area form');

    // Assign map container
    if (position == 'bottom'){
        $form.find('.fieldset, .submit-cell').last().after($container);
    } else if (position != 'top'){
        $('div#fs_' + group + ' > div.body')[append_type]($container);
    }

    // Create class object
    var locationFinder = new locationFinderClass();

    // Init plugin depending on "account address" option
    var $account_address = $('input[name="f[account_address_on_map]"]');

    if (!$account_address.length
        || parseInt($account_address.filter(':checked').val()) == 0
    ){
        locationFinder.init(options);
    }

    $account_address.change(function(){
        if (parseInt($(this).val()) == 0){
            locationFinder.init(options);
        } else {
            locationFinder.destroy();
        }
    });
});

{/literal}
</script>

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'locationFinder/static/lib.js'}

<!-- location finder map end -->
