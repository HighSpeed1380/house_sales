<!-- multifield header tpl -->

{if $multi_format_keys}
<script>
    var mfFields = new Array();
    var mfFieldVals = new Array();
    lang['select'] = "{$lang.select}";
    lang['not_available'] = "{$lang.not_available}";
</script>
{/if}

<script>
{literal}

var mfGeoFields = new Array();

var gfAjaxClick = function(key, path, redirect){
    flUtil.ajax({
        mode: 'mfApplyLocation',
        item: path,
        key: key
    }, function(response, status) {
        if (status == 'success' && response.status == 'OK') {
            if (rlPageInfo['key'] === '404') {
                location.href = rlConfig['seo_url'];
            } else {
                if (location.href.indexOf('?reset_location') > 0) {
                    location.href = location.href.replace('?reset_location', '');
                } else {
                    if (redirect) {
                        location.href = redirect;
                    } else {
                        location.reload();
                    }
                }
            }
        } else {
            printMessage('error', lang['system_error']);
        }
    });
}

{/literal}
</script>

{if $config.mf_select_interface == 'usernavbar'}
{assign var='navbar_icon_size' value=16}
{if $mf_is_nova}
    {assign var='navbar_icon_size' value=14}
{/if}
<style>
{literal}
/*** GEO LOCATION IN NAVBAR */
.circle #mf-location-selector {
    vertical-align: top;
    display: inline-block;
}
#mf-location-selector + .popover {
    color: initial;
    /*min-width: auto;*/
}
#mf-location-selector .default:before,
#mf-location-selector .default:after {
    display: none;
}
#mf-location-selector .default {
    max-width: 170px;
    {/literal}
    {if $tpl_settings.name == 'auto_brand_wide' || $tpl_settings.name == 'boats_seaman_wide'}
    display: flex;
    {elseif $tpl_settings.name != 'escort_sun_cocktails_wide'}
    vertical-align: top;
    {/if}
    {literal}
    white-space: nowrap;
}
#mf-location-selector .default > span {
    display: inline-block;
    min-width: 0;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}
{/literal}
{if $mf_is_flatty || $mf_hide_name}
{literal}
#mf-location-selector .default > span {
    display: none;
}
svg.mf-location-icon {
    margin: 0 !important;
}
{/literal}
{/if}
{literal}
@media screen and (max-width: 767px) {
    #mf-location-selector .default > span {
        display: none;
    }
    svg.mf-location-icon {
        margin: 0 !important;
    }
}

.popup .gf-root {
    width: 500px;
    display: flex;
    height: {/literal}{if $geo_filter_data.applied_location}285{else}255{/if}{literal}px;
}
.gf-cities {
    overflow: hidden;
}
.gf-cities .gf-city {
    padding: 4px 0;
}
.gf-cities .gf-city a {
    display: block;
}
.gf-cities-hint {
    padding-bottom: 10px;
}
svg.mf-location-icon {
    {/literal}
    width: {$navbar_icon_size}px;
    height: {$navbar_icon_size}px;
    {if $mf_is_nova}
    flex-shrink: 0;
    {else}
    vertical-align: middle;
    margin-top: -1px;
    {/if}
    {literal}
}
#mf-location-selector:hover svg.mf-location-icon {
    opacity: .8;
}
@media screen and (max-width: 767px) {
    .popup .gf-root {
        height: 85vh;
        min-width: 1px;
    }
}
@media screen and (min-width: 768px) and (max-width: 991px) {
    .header-contacts .contacts__email {
        display: none;
    }
}

/* TODO: Remove once bootstrap4 will be updated in the template core */
.d-inline {
  display: inline !important;
}
@media (min-width: 768px) {
  .d-md-none {
    display: none !important;
  }
}
.gf-root .w-100 {
    width: 100%;
}
.flex-column {
    flex-direction: column;
}
.flex-fill {
    flex: 1;
}
.mr-2 {
    margin-right: 0.5rem;
}
.align-self-center {
    align-self: center;
}
body[dir=rtl] .mr-2 {
    margin-right: 0;
    margin-left: 0.5rem;
}
/* TODO end */
{/literal}
</style>
{elseif $blocks.geo_filter_box || $home_page_special_block.Key == 'geo_filter_box'}
<style>
{literal}
/*** GEO LOCATION BOX */
.gf-box.gf-has-levels ul.gf-current {
    padding-bottom: 10px;
}
.gf-box ul.gf-current > li {
    padding: 3px 0;
}
.gf-box ul.gf-current span {
    display: inline-block;
    margin: 0 5px 1px 3px;
}
.gf-box ul.gf-current span:before {
    content: '';
    display: block;
    width: 5px;
    height: 9px;
    border-style: solid;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
body[dir=rtl] .gf-box ul.gf-current span {
    margin: 0 3px 1px 5px;
}

.special-block .gf-root {
    display: flex;
    flex-direction: column;
    width: 100%;
}
.special-block .gf-root .gf-box {
    flex: 1;
    overflow: hidden;
}
.special-block .multiField .clearfix {
    display: flex;
}
.special-block .gf-box .gf-container {
    max-height: none;
}

.gf-box .gf-container {
    max-height: 250px;
    overflow: hidden;
}
.gf-box .gf-container li > a {
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    padding: 3px 0;
    display: inline-block;
    width: 100%;
}
@media screen and (max-width: 767px) {
    .gf-box .gf-container li > a {
        padding: 6px 0;
    }
}
{/literal}
</style>
{/if}

{if $blocks.geo_filter_box || $home_page_special_block.Key == 'geo_filter_box' || $config.mf_select_interface == 'usernavbar'}
<style>
{literal}
.mf-autocomplete {
    padding-bottom: 15px;
    position: relative;
}
.mf-autocomplete-dropdown {
    width: 100%;
    height: auto;
    max-height: 185px;
    position: absolute;
    overflow-y: auto;
    background: white;
    z-index: 500;
    margin: 0 !important;
    box-shadow: 0px 3px 5px rgba(0,0,0, 0.2);
}
.mf-autocomplete-dropdown > a {
    display: block;
    padding: 9px 10px;
    margin: 0;
}
.mf-autocomplete-dropdown > a:hover,
.mf-autocomplete-dropdown > a.active {
    background: #eeeeee;
}

.gf-current a > img {
    background-image: url({/literal}{$rlTplBase}{literal}img/gallery.png);
}
@media only screen and (-webkit-min-device-pixel-ratio: 1.5),
only screen and (min--moz-device-pixel-ratio: 1.5),
only screen and (min-device-pixel-ratio: 1.5),
only screen and (min-resolution: 144dpi) {
    .gf-current a > img {
        background-image: url({/literal}{$rlTplBase}{literal}img/@2x/gallery2.png) !important;
    }
}
{/literal}
</style>
{/if}

{if $config.mf_show_nearby_listings}
<style>
{literal}
.mf-nearby-wrapper header {
    font-size: 1.125em !important;
}
{/literal}
</style>
{/if}

<!-- multifield header tpl end -->
