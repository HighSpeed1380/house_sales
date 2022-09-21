<!-- multifield location autocomplete tpl -->

<div class="mf-autocomplete kws-block">
    <input class="mf-autocomplete-input w-100" type="text" maxlength="64" placeholder="{$lang.mf_geo_type_location}" />
    <div class="mf-autocomplete-dropdown hide"></div>
</div>

<script class="fl-js-dynamic">
    var mf_script_loaded = false;
    var mf_current_key   = {if $geo_filter_data.location_keys}'{$geo_filter_data.location_keys|@end}'{else}null{/if};

    rlPageInfo['Geo_filter'] = {if $geo_filter_data.is_location_url}true{else}false{/if};

    {literal}
    $(function(){
        $('.mf-autocomplete-input').on('focus keyup', function(){
            if (!mf_script_loaded) {
                flUtil.loadScript(rlConfig['plugins_url'] + 'multiField/static/autocomplete.js');
                mf_script_loaded = true;
            }
        });
    });
    {/literal}
</script>

<!-- multifield location autocomplete tpl end -->
