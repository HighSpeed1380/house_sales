<!-- location finder tpl -->

<svg class="hide" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
    {include file=$smarty.const.RL_ROOT|cat:'templates/'|cat:$config.template|cat:'/img/svg/userLocation.svg'}
</svg>

<script src="{$smarty.const.RL_LIBS_URL}maps/geocoder.js"></script>

{mapsAPI}

<tr id="lf_container"{if $config.locationFinder_position != 'top'} class="hide"{/if}>
    <td class="name">
        {$lang.locationFinder_location} <img src="{$rlTplBase}img/blank.gif" class="qtip" title="{$lang.locationFinder_hint}" />
    </td>
    <td class="field">
        <div id="lf_map" style="height: 400px;"></div>

        <input id="lf_lat" name="f[lf][lat]" type="hidden" value="{$smarty.post.f.lf.lat}" />
        <input id="lf_lng" name="f[lf][lng]" type="hidden" value="{$smarty.post.f.lf.lng}" />
        <input id="lf_zoom" name="f[lf][zoom]" type="hidden" value="{$smarty.post.f.lf.zoom}" />
    </td>
</tr>

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
        mapping: {if $config.locationFinder_mapping}true{else}false{/if},
        mappingCountry: {if $config.locationFinder_mapping_country}'{$config.locationFinder_mapping_country}'{else}false{/if},
        mappingState: {if $config.locationFinder_mapping_state}'{$config.locationFinder_mapping_state}'{else}false{/if},
        mappingCity: {if $config.locationFinder_mapping_city}'{$config.locationFinder_mapping_city}'{else}false{/if},
        geocoding: {if !isset($config.geocoding_provider) || $config.geocoding_provider == 'google'}true{else}false{/if},
        mfFields: {if $geo_fields}JSON.parse('{$geo_fields|@json_encode}'){else}false{/if}
    {literal}
    };

    var $container  = $('#lf_container');
    var $form       = $('#controller_area form');

    // Assign map container
    if (position == 'bottom'){
        $form.find('.fieldset, .submit-cell').last().after($container);
    } else if (position != 'top'){
        $('#group_' + group).find('> table > tbody')[append_type]($container);
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
<script type="text/javascript" src="{$smarty.const.RL_PLUGINS_URL}locationFinder/static/lib.js"></script>

<!-- location finder tpl end -->
