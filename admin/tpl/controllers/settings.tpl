<!-- settings tpl -->

<form method="post" action="{$rlBase}index.php?controller={$cInfo.Controller}" onsubmit="return submitHandler();">
    <input name="group_id" value="{$smarty.get.group}" type="hidden" />
    <table class="sTable" id="settings_anchor">
    <tr>
        <td style="width: 108px; border-right: 1px #667835 solid;" align="right" valign="top">
            {foreach from=$configGroups item='group' name='fGroups'}
                {if empty($group.Plugin_status) || $group.Plugin_status == 'active'}
                    <div id="ltab_{$group.ID}" title="{$group.name}" class="ltab {if $smarty.foreach.fGroups.first}tabs_active{else}tabs_inactive{/if}" {if $smarty.foreach.fGroups.first}style="margin: 0;"{/if}><div>{$group.name}</div></div>
                    {if $smarty.get.group == $group.ID || $smarty.foreach.fGroups.first}
                        <script type="text/javascript">
                        var sKey = '{$group.ID}';
                        </script>
                    {/if}
                {/if}
            {/foreach}
        </td>
        <td valign="top" style="width: 1px; border-right: 1px #667835 solid;"></td>
        <td valign="top">
            {foreach from=$configGroups item='group' name='fGroups'}
                <div id="larea_{$group.ID}" class="larea hide">
                    {assign var='replace_group' value=`$smarty.ldelim`group`$smarty.rdelim`}

                    <div class="ltab_block_header clear"><div>{$lang.configs_caption|replace:$replace_group:$group.name}</div></div>
                    {assign var='store' value=$group.ID}
                    {if !empty($configs.$store)}
                        <div style="padding: {if $configs.$store.0.Type != 'divider'}10px{else}0{/if} 10px 0;">
                            <input type="hidden" name="a_config" value="update"/>
                            <table class="form">
                            {foreach from=$configs.$store item='configItem' name='configF'}
                            <tr class="{if $smarty.foreach.configF.iteration%2 != 0 && $configItem.Type != 'divider'}highlight{/if}{if $configItem.Key == 'base_listing_plan'} hide base-listing-plan{/if}">
                                {if $configItem.Type == 'divider'}
                                    <td class="divider_line" colspan="2">
                                        <div class="inner">{$configItem.name}</div>
                                    </td>
                                {else}
                                    <td class="name" style="width: 210px;">{$configItem.name}</td>
                                    <td class="field">
                                        <div class="inner_margin">
                                            {if $configItem.Data_type == 'int'}<input class="text" type="hidden" name="post_config[{$configItem.Key}][d_type]" value="{$configItem.Data_type}" />{/if}
                                            <input class="text" type="hidden" name="post_config[{$configItem.Key}][value]" value="{$configItem.Default|escape}" />

                                            {if $configItem.Type == 'text'}
                                                <input class="text {if $configItem.Data_type == 'int'}numeric{/if}" type="text" name="post_config[{$configItem.Key}][value]" value="{$configItem.Default|escape}" />
                                            {elseif $configItem.Type == 'textarea'}
                                                <textarea cols="5" rows="5" class="{if $configItem.Data_type == 'int'}numeric{/if}" name="post_config[{$configItem.Key}][value]">{$configItem.Default|replace:'\r\n':$smarty.const.PHP_EOL}</textarea>
                                            {elseif $configItem.Type == 'bool'}
                                                <input {if $configItem.Default == 1}checked="checked"{/if} type="radio" id="{$configItem.Key}_1" name="post_config[{$configItem.Key}][value]" value="1" />
                                                <label for="{$configItem.Key}_1">{$lang.enabled}</label>

                                                <input {if $configItem.Default == 0}checked="checked"{/if} type="radio" id="{$configItem.Key}_0" name="post_config[{$configItem.Key}][value]" value="0" />
                                                <label for="{$configItem.Key}_0">{$lang.disabled}</label>
                                            {elseif $configItem.Type == 'select'}
                                                <select {if $configItem.Key == 'timezone'}class="w350"{/if} style="width: 204px;" name="post_config[{$configItem.Key}][value]"
                                                    {foreach from=$configItem.Values item='sValue' name='sForeach'}
                                                        {if $smarty.foreach.sForeach.first}
                                                            {if ($smarty.foreach.sForeach.total == 1 && !is_array($sValue) && $configItem.Key != 'template') || ($smarty.foreach.sForeach.total == 1 && $configItem.Key == 'template' && $sValue == $config.template)} class="disabled" disabled="disabled"{/if}
                                                        >
                                                            {if is_array($sValue)
                                                                && !in_array($configItem.Key, $systemSelects)
                                                            }
                                                                <option value="">{$lang.select}</option>
                                                            {/if}
                                                        {/if}
                                                        <option value="{if is_array($sValue)}{$sValue.ID}{else}{$sValue}{/if}" {if is_array($sValue)}{if $configItem.Default == $sValue.ID}selected="selected"{/if}{else}{if $sValue == $configItem.Default}selected="selected"{/if}{/if}>{if is_array($sValue)}{$sValue.name}{else}{$sValue}{/if}</option>
                                                    {/foreach}
                                                </select>
                                            {elseif $configItem.Type == 'radio'}
                                                {assign var='displayItem' value=$configItem.Display}
                                                {foreach from=$configItem.Values item='rValue' name='rForeach' key='rKey'}
                                                    <input id="radio_{$configItem.Key}_{$rKey}" {if $rValue == $configItem.Default}checked="checked"{/if} type="radio" value="{$rValue}" name="post_config[{$configItem.Key}][value]" /><label for="radio_{$configItem.Key}_{$rKey}">&nbsp;{$displayItem.$rKey}&nbsp;&nbsp;</label>
                                                {/foreach}
                                            {elseif $configItem.Type == 'color'} {* @since 4.8.2 *}
                                                <div id="{$configItem.Key}ColorSelector" class="colorSelector">
                                                    <div style="background-color: rgb({if $configItem.Default|escape}{$configItem.Default|escape}{else}255,255,255{/if})"></div>
                                                </div>

                                                <script>{literal}
                                                $(function() {
                                                    flUtil.loadScript(rlConfig.libs_url + 'jquery/colorpicker/js/colorpicker.js', function() {
                                                        $('#{/literal}{$configItem.Key}{literal}ColorSelector').ColorPicker({
                                                            color: '{/literal}{if $configItem.Default|escape}{$configItem.Default|escape}{else}255,255,255{/if}{literal}',
                                                            onShow: function (colorPicker) {
                                                                $(colorPicker).fadeIn(500);
                                                                return false;
                                                            },
                                                            onHide: function (colorPicker) {
                                                                $(colorPicker).fadeOut(500);
                                                                return false;
                                                            },
                                                            onChange: function (hsb, hex, rgb) {
                                                                rgb = rgb.r + ',' + rgb.g + ',' + rgb.b;
                                                                $('#{/literal}{$configItem.Key}{literal}ColorSelector div').css('background-color', 'rgb(' + rgb + ')');
                                                                $('[name="post_config[{/literal}{$configItem.Key}{literal}][value]"]').val(rgb);
                                                            }
                                                        });
                                                    });
                                                });
                                                {/literal}</script>
                                            {else}
                                                {$configItem.Default}
                                            {/if}
                                            {if $configItem.des != ''}
                                                <span style="{if $configItem.Type == 'textarea'}line-height: 10px;{elseif $configItem.Type == 'bool'}line-height: 14px;margin: 0 10px;{/if}" class="settings_desc">{$configItem.des}</span>
                                            {/if}
                                        </div>
                                    </td>
                                {/if}
                            </tr>
                            {/foreach}

                            {if $smtpDebug}
                                <td class="divider_line" colspan="2">
                                    <div class="inner">{$lang.smtp_error_log}</div>
                                </td>
                                <tr>
                                    <td class="code" colspan="2">
                                        <pre class="code"><code>{$smtpDebug}</code></pre>
                                    </td>
                                </tr>
                            {/if}

                            <tr>
                                <td></td>
                                <td><input style="margin: 10px 0 0 0;" type="submit" class="button" value="{$lang.save}" /></td>
                            </tr>
                            </table>
                        </div>
                    {else}
                        <div style="margin: 10px 20px" class="static">{$lang.confog_group_empty}</div>
                    {/if}
                </div>
            {/foreach}
        </td>
    </tr>
    </table>
</form>

{assign var='base_listing_plan_des' value='config+des+base_listing_plan'}

{if isset($config.search_map_location_name)}
    {geoAutocompleteAPI}
{/if}

<script>
lang['resize_images_prompt'] = "{$lang.resize_images_prompt}";
lang['resize_in_progress'] = "{$lang.resize_in_progress}";
lang['resize_completed'] = "{$lang.resize_completed}";
lang['refresh_listing_pictures'] = "{$lang.refresh_listing_pictures}";
lang['refresh_account_pictures'] = "{$lang.refresh_account_pictures}";

rlConfig['refreshListingImages'] = {if $smarty.get.refreshListingImages}true{else}false{/if};
rlConfig['refreshAccountImages'] = {if $smarty.get.refreshAccountImages}true{else}false{/if};
rlConfig['default_coordinates'] = '{$config.search_map_location}';

var popupListingPlan;
{literal}

$(function() {
    if ($('input[name="post_config[membership_module][value]"]').is(':checked') && $('input[name="post_config[membership_module][value]"]').val() == 0) {
        $('input[name="post_config[membership_module][value]"]').parent().parent().parent('tr').next('tr').addClass('hide');
    }
    $('input[name="post_config[membership_module][value]"]').change(function() {
        if ($(this).is(':checked') && $(this).val() == 0) {
            $(this).parent().parent().parent('tr').next('tr').addClass('hide');
            $.getJSON(rlConfig['ajax_url'], {item: 'checkListingsByMembership'}, function(response){
                if (response) {
                    if (response.count > 0) {
                        assignListingsToPlan(response.message);
                    }
                }
            });
        } else {
            $('input[name="post_config[base_listing_plan][value]"]').val('');
            $(this).parent().parent().parent('tr').next('tr').removeClass('hide');
        }
    });
    $(document).on('click', '.apply-plan', function(event) {
        if ($('#listing_plans option:selected').val() > 0) {
            $('input[name="post_config[base_listing_plan][value]"]').val($('#listing_plans option:selected').val());
            $('#settings_anchor').parent('form').submit();
        } else {
            printMessage('error', '{/literal}{$lang.mp_not_selected_package}{literal}');
            $('#membership_module_1').prop('checked', 'checked');
            $('#membership_module_1').closest('tr').next('tr').removeClass('hide');
            $('input[name="post_config[base_listing_plan][value]"]').val('');
            flynax.slideTo('body');
        }
        popupListingPlan.close();
    });
    $(document).on('click', '.cancel-plan', function(event) {
        $('#membership_module_1').prop('checked', 'checked');
        $('#membership_module_1').parent().parent().parent('tr').next('tr').removeClass('hide')
        $('input[name="post_config[base_listing_plan][value]"]').val('');
        popupListingPlan.close();
    });
    $('form').submit(function(){
        $('input[type=text],[type=hidden]').each(function(){
            if( $(this).val().match(/http(s)?/) && !$(this).val().match(/\[\[http/))
            {
                var new_val = $(this).val().replace(/http(s)?/g, '[[http$1_pref]]');
                $(this).val(new_val);
            }
        });
    });

    $('#larea_'+sKey).show();

    $('.ltab').each(function(){
        if ( $(this).attr('class').split(' ')[1] == 'tabs_active' )
        {
            $(this).removeClass('tabs_active').addClass('tabs_inactive');
        }
    });
    $('#ltab_'+sKey).removeClass('tabs_inactive').addClass('tabs_active');

    $('.larea').hide();

    $('#larea_'+sKey).show();

    $('.ltab').click(function(){

        var yScroll;
        if (self.pageYOffset)
            yScroll = self.pageYOffset;
        else if (document.documentElement && document.documentElement.scrollTop)
            yScroll = document.documentElement.scrollTop;// Explorer 6 Strict
        else if (document.body)
            yScroll = document.body.scrollTop;// all other Explorers

        var pos = $('#settings_anchor').position();

        $('html, body').stop();

        if ( yScroll > pos.top )
        {
            $('html, body').animate({scrollTop:pos.top-40}, 'slow');
        }

        var cid = $(this).attr('id').split('_')[1];
        $('input[name=group_id]').val(cid);

        $('.ltab').each(function(){
            if ( $(this).attr('class').split(' ')[1] == 'tabs_active' )
            {
                $(this).removeClass('tabs_active').addClass('tabs_inactive');
            }
        });
        $('#ltab_'+cid).removeClass('tabs_inactive').addClass('tabs_active');

        $('.larea').hide();
        $('#larea_'+cid).show();
    });

    // Refresh listing/account pictures
    var refresh_method = '';
    if (rlConfig['refreshListingImages'] && rlConfig['refreshAccountImages']) {
        refresh_method = 'flynax.initRebuildPictures';
    } else if (rlConfig['refreshListingImages']) {
        refresh_method = 'flynax.initRebuildListingPictures';
    } else if (rlConfig['refreshAccountImages']) {
        refresh_method = 'flynax.initRebuildAccountPictures';
    }

    if (refresh_method) {
        rlConfirm(lang['resize_images_prompt'], refresh_method);
    }

    // Static Maps
    $('select[name="post_config[static_map_provider][value]"]').on('change', function(){
        $('input[name="post_config[google_map_key][value]"]').closest('tr')[
            $(this).val() == 'google' ? 'removeClass' : 'addClass'
        ]('hide');
    }).trigger('change');

    // Geocoding
    $('select[name="post_config[geocoding_provider][value]"]').on('change', function(){
        $('input[name="post_config[google_server_map_key][value]"]').closest('tr')[
            $(this).val() == 'google' ? 'removeClass' : 'addClass'
        ]('hide');
    }).trigger('change');

    // Default map location autocomplete
    var $configInput =  $('input[type=text][name="post_config[search_map_location_name][value]"]')
    if ($configInput.length) {
        var defaultLat = '';
        var defaultLng = '';

        if (rlConfig['default_coordinates'].indexOf(',') > 0) {
            var defaultCoordinates = rlConfig['default_coordinates'].split(',');
            defaultLat = defaultCoordinates[0];
            defaultLng = defaultCoordinates[1];
        }

        $configInput.after('<input type="hidden" name="search_map_default[lat]" value="'+defaultLat+'" />');
        $configInput.after('<input type="hidden" name="search_map_default[lng]" value="'+defaultLng+'" />');

        $configInput.geoAutocomplete({
            onSelect: function(name, lat, lng){
                $('input[name="search_map_default[lat]"]').val(lat);
                $('input[name="search_map_default[lng]"]').val(lng);
            }
        });
    }

    {/literal}{if $allLangs|@count < 2}
        $('[name="post_config[multilingual_paths][value]"]').prop('disabled', true);
    {/if}{literal}

    var $bannerGridOption = $('select[name="post_config[banner_in_grid_position][value]"]');

    {/literal}{if $config.banner_in_grid_position_option === '0'}
        $bannerGridOption.attr('disabled', true);
    {/if}{literal}

    $('input[name="post_config[banner_in_grid_position_option][value]"]').change(function() {
        $bannerGridOption.attr(
            'disabled',
            Number($(this).val()) === 0 ? true : false
        );
    });
});

var assignListingsToPlan = function(message) {
    var popup_content = '\
        <div class="x-hidden" id="select_listing_plan_area">\
            <div class="x-window-header">' + lang['ext_confirm'] + '</div>\
            <div class="x-window-body" style="padding: 10px 15px;">\
                <div>'+ message +'</div>\
                <table class="form">\
                    <tr>\
                        <td class="name w130">{/literal}{$lang.listing_package}{literal}</td>\
                        <td class="field">\
                            <select id="listing_plans">\
                                <option value="0">{/literal}{$lang.select}{literal}</option>\
                                {/literal}{foreach from=$listing_plans item='plan'}{literal}<option value="{/literal}{$plan.ID}{literal}">{/literal}{$plan.name}{literal}</option>{/literal}{/foreach}{literal}\
                            </select>\
                        </td>\
                    </tr>\
                    <tr>\
                        <td></td>\
                        <td><input style="margin: 10px 0 0 0;" type="button" class="button apply-plan" value="{/literal}{$lang.apply}{literal}" />&nbsp;<input style="margin: 10px 0 0 0;" type="button" class="button cancel-plan" value="{/literal}{$lang.cancel}{literal}" /></td>\
                    </tr>\
                </table>\
            </div>\
        </div>';

    $('body').after(popup_content);

    popupListingPlan = new Ext.Window({
        title: lang['ext_confirm'],
        applyTo: 'select_listing_plan_area',
        layout: 'fit',
        width: 500,
        height: 'auto',
        plain: true,
        modal: true,
        closable: false
    });

    popupListingPlan.show();
}

$(function () {
    let $watermarkOption = $('[name="post_config[watermark_using][value]"]'),
        $watermarkType   = $('select[name="post_config[watermark_type][value]"]');

    watermarkOptionHandler();

    $watermarkOption.change(function () {
        watermarkOptionHandler();
    });

    function watermarkOptionHandler () {
        $('[name^="post_config[watermark_"]').closest('tr').each(function () {
            if ($(this).find('input').attr('name') === 'post_config[watermark_using][value]') {
                return;
            }

            $(this).css('display', $watermarkOption.filter(':checked').val() === '0' ? 'none' : '');
        });

        watermarkTypeHandler();
    }

    $watermarkType.change(function () {
        watermarkTypeHandler();
    });

    function watermarkTypeHandler () {
        if ($watermarkOption.filter(':checked').val() === '0') {
            return;
        }

        if ($watermarkType.val() === 'text') {
            $('[name="post_config[watermark_image_url][value]"]').closest('tr').hide();
            $('[name="post_config[watermark_image_width][value]"]').closest('tr').hide();

            $('[name="post_config[watermark_text][value]"]').closest('tr').show();
            $('[name="post_config[watermark_text_font][value]"]').closest('tr').show();
            $('[name="post_config[watermark_text_size][value]"]').closest('tr').show();
            $('[name="post_config[watermark_text_color][value]"]').closest('tr').show();
        } else if ($watermarkType.val() === 'image') {
            $('[name="post_config[watermark_text][value]"]').closest('tr').hide();
            $('[name="post_config[watermark_text_font][value]"]').closest('tr').hide();
            $('[name="post_config[watermark_text_size][value]"]').closest('tr').hide();
            $('[name="post_config[watermark_text_color][value]"]').closest('tr').hide();

            $('[name="post_config[watermark_image_url][value]"]').closest('tr').show();
            $('[name="post_config[watermark_image_width][value]"]').closest('tr').show();
        }
    }
});
{/literal}

{if $mfSubdomainsDenied}
    printMessage('error', '{$lang.mf_subdomain_denied}');
{elseif $cacheMethodDenied}
    printMessage('error', '{$lang.cache_method_denied}');
{/if}

</script>
<!-- settings tpl end -->
