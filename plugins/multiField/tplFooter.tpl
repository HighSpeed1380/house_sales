{if $multi_format_keys}
    <script class="fl-js-dynamic">
    var mf_prefix = '{$mf_form_prefix}';
    {literal}
    $(function(){
        for (var i in mfFields) {
            (function(fields, values, index){
                var $form = null;

                if (index.indexOf('|') >= 0) {
                    var form_key = index.split('|')[1];
                    $form = $('#area_' + form_key).find('form');
                    $form = $form.length ? $form : null;
                }

                var mfHandler = new mfHandlerClass();
                mfHandler.init(mf_prefix, fields, values, $form);
            })(mfFields[i], mfFieldVals[i], i);
        }
    });
    {/literal}
    </script>
{/if}

{if $config.mf_select_interface == 'usernavbar'}
    <div class="hide d-none" id="gf_tmp">
        <div class="gf-root flex-column">
            {include file=$smarty.const.RL_PLUGINS|cat:'multiField'|cat:$smarty.const.RL_DS|cat:'autocomplete.tpl'}

            <div class="gf-cities-hint font-size-sm">{$lang.mf_select_city_hint}</div>
            <div class="gf-cities flex-fill"></div>
            {if $geo_filter_data.applied_location}
                <div class="gf-navbar">
                    <a href="javascript://" data-link="{$geo_filter_data.location.0.Parent_link}" class="nowrap text-overflow button w-100 align-center gf-ajax">{$lang.mf_reset_location}<span class="d-inline{if !$mf_is_flatty && !$mf_hide_name} d-md-none{/if}"> ({$geo_filter_data.applied_location.name})</span></a>
                </div>
            {/if}
        </div>
    </div>

    <script id="gf_city_item" type="text/x-jsrender">
        <li class="col-md-4">
            <div class="gf-city">
                <a title="[%:name%]"
                    {if $geo_filter_data.is_location_url}
                        href="[%:Link%]" class="text-overflow"
                    {else}
                        href="javascript://" class="gf-ajax text-overflow"
                    {/if}
                   data-path="[%:Path%]" data-key="[%:Key%]">[%:name%]</a>
            </div>
        </li>
    </script>
{/if}

{if $geo_filter_data && $geo_filter_data.is_filtering}
<script class="fl-js-dynamic">
{literal}

if (typeof flUtil.modifyDataFunctions != 'undefined') {
    flUtil.modifyDataFunctions.push(function(data){
        if (data.mode == 'novaLoadMoreListings') {
            data.mf_filtering = 1;
        }
    });
}

{/literal}
</script>
{/if}
