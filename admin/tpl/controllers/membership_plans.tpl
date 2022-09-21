<!-- membership plans tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplMembershipPlansNavBar'}

    {if $aRights.$cKey.add}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_membership_plan}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.membership_plans_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/colorpicker/js/colorpicker.js"></script>

    {assign var='sPost' value=$smarty.post}

    <!-- add/edit new plan -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;plan={$smarty.get.plan}{/if}" method="post">
    <input type="hidden" name="submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
    {/if}
    <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.name}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea rows="" cols="" name="description[{$language.Code}]">{$sPost.description[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.enable_for}</td>
            <td class="field">
                <fieldset class="light">
                    <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('accounts_tab_area');">{$lang.account_type}</legend>
                    <div id="accounts_tab_area" style="padding: 0 10px 10px 10px;">
                        <table>
                        <tr>
                            <td>
                                <table>
                                <tr>
                                {assign var='ms_add_listing' value=false}
                                {foreach from=$membership_services item='service' name='ms_services'}
                                    {if $sPost.services && $service.ID|in_array:$sPost.services && $service.Key == 'add_listing'}
                                        {assign var='ms_add_listing' value=true}
                                    {/if}
                                {/foreach}
                                {foreach from=$account_types item='a_type' name='ac_type'}
                                    <td{if $ms_add_listing && !isset($available_account_types[$a_type.ID])} class="hide"{/if}>
                                        <div style="margin: 0 20px 0 0;">
                                            <input {if $sPost.account_type && $a_type.Key|in_array:$sPost.account_type}checked="checked"{/if}
                                                   style="margin-bottom: 0px;"
                                                   type="checkbox"
                                                   id="account_type_{$a_type.ID}"
                                                   value="{$a_type.Key}"
                                                   name="account_type[]"
                                                   data-agent="{$a_type.Agent}"
                                            /> <label for="account_type_{$a_type.ID}">{$a_type.name}</label>
                                        </div>
                                    </td>

                                {if $smarty.foreach.ac_type.iteration%3 == 0 && !$smarty.foreach.ac_type.last}
                                </tr>
                                <tr>
                                {/if}

                                {/foreach}
                                </tr>
                                </table>
                            </td>
                            <td>
                                <span class="field_description">{$lang.info_account_type_mplans}</span>
                            </td>
                        </tr>
                        </table>

                        <div class="grey_area" style="margin: 8px 0 0;">
                            <span onclick="$('#accounts_tab_area input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                            <span class="divider"> | </span>
                            <span onclick="$('#accounts_tab_area input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                        </div>
                    </div>
                </fieldset>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.label_bg_color}</td>
            <td class="field">
                <div style="padding: 0 0 5px 0;">
                    <input type="hidden" name="color" value="{$sPost.color}" />
                    <div id="colorSelector" class="colorSelector"><div style="background-color: #{if $sPost.color}{$sPost.color}{else}d8cfc4{/if}"></div></div>
                </div>

                <script type="text/javascript">
                var bg_color = '{if $sPost.color}{$sPost.color}{else}d8cfc4{/if}';
                {literal}

                $(document).ready(function(){

                    $('#colorSelector').ColorPicker({
                        color: '#'+bg_color,
                        onShow: function (colpkr) {
                            $(colpkr).fadeIn(500);
                            return false;
                        },
                        onHide: function (colpkr) {
                            $(colpkr).fadeOut(500);
                            return false;
                        },
                        onChange: function (hsb, hex, rgb) {
                            $('#colorSelector div').css('backgroundColor', '#' + hex);
                            $('input[name=color]').val(hex);
                        }
                    });

                });

                {/literal}
                </script>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.price}</td>
            <td class="field">
                <input type="text" name="price" value="{$sPost.price}" class="numeric" style="width: 50px; text-align: center;" /> <span class="field_description_noicon">&nbsp;{$config.system_currency}</span>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.membership_plan_live_for}</td>
            <td class="field">
                <input type="text" name="plan_period" value="{$sPost.plan_period}" class="numeric" style="width: 50px; text-align: center;" />
                <span class="field_description_noicon">{$lang.days}</span>
                <span class="field_description">{$lang.membership_plan_live_for_hint}</span>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.limit_use_of_ms_plan}</td>
            <td class="field">
                <input type="text" name="limit" value="{$sPost.limit}" class="numeric" style="width: 50px; text-align: center;" />
                <span class="field_description_noicon">{$lang.times}</span>
                <span class="field_description">{$lang.limit_use_of_ms_plan_hint}</span>
            </td>
        </tr>
    </table>

    <div id="services_area">
        <table class="form">
            <tr>
                <td class="name">{$lang.membership_services}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_services_settings" class="up" onclick="fieldset_action('services_settings');">{$lang.settings}</legend>
                        <div id="services_settings" style="padding: 0 10px 10px 10px;">
                            <table>
                                <tr>
                                    <td>
                                        <table>
                                            <tr>
                                            {foreach from=$membership_services item='service' name='ms_services'}
                                                <td>
                                                    <div style="padding: 4px 8px 4px 0;">
                                                         <label>
                                                             <input id="ms_{$service.Key}"
                                                                    {if $sPost.services && $service.ID|in_array:$sPost.services}checked="checked"{/if}
                                                                    style="margin-bottom: 0px;"
                                                                    type="checkbox"
                                                                    value="{$service.ID}"
                                                                    item-data="{$service.Key}"
                                                                    name="services[]"
                                                             /> {$service.name}
                                                         </label>
                                                    </div>
                                                </td>

                                            {if $smarty.foreach.ms_services.iteration%1 == 0 && !$smarty.foreach.ms_services.last}
                                            </tr>
                                            <tr>
                                            {/if}

                                            {/foreach}
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div class="grey_area" style="margin: 8px 0 0;">
                                <span onclick="$('#services_settings input').attr('checked', true); checkAccountTypes(true);" class="green_10">{$lang.check_all}</span>
                                <span class="divider"> | </span>
                                <span onclick="$('#services_settings input').attr('checked', false); checkAccountTypes(false);" class="green_10">{$lang.uncheck_all}</span>
                            </div>
                        </div>
                    </fieldset>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            {literal}
            $(document).ready(function(){
                listingAreaControl();
                agentHandler($('#ms_agency').is(':checked'));

                $('#services_settings input[type="checkbox"]').click(function() {
                    if ($(this).attr('item-data') == 'add_listing') {
                        checkAccountTypes($(this).is(':checked'));
                    }

                    if ($(this).attr('item-data') === 'agency') {
                        agentHandler($(this).is(':checked'));
                    }
                });
            });

            /**
             * Controller is activate/deactivate account types which cannot have ability to have subaccounts.
             * They already have enabled "Agent" option.
             *
             * @since 4.9.0
             *
             * @param isChecked
             */
            let agentHandler = function (isChecked) {
                let $accountTypes = $('[name="account_type[]"]');

                if (isChecked) {
                    $accountTypes.each(function () {
                        if ($(this).data('agent') === 1) {
                            $(this).prop('checked', false).prop('disabled', true).addClass('disabled');
                        }
                    });
                } else {
                    $accountTypes.each(function () {
                        if ($(this).is(':disabled')) {
                            $(this).prop('checked', false).prop('disabled', false).removeClass('disabled');
                        }
                    });
                }
            }

            var checkAccountTypes = function(is_checked) {
                if (is_checked) {
                    $.getJSON(rlConfig['ajax_url'], {item: 'checkAccountTypes'}, function(response){
                        if (response) {
                            if (response.count > 0) {
                                var not_allowed = '';
                                $('#accounts_tab_area input[type="checkbox"]').each(function() {
                                    var allowed = false;
                                    for (var i = 0; i < response.data.length; i++) {
                                        if ($(this).val() == response.data[i]['Key']) {
                                            allowed = true;
                                        }
                                    }
                                    if (allowed) {
                                        $(this).parent().parent().removeClass('hide');
                                    } else {
                                        if ($(this).is(':checked')) {
                                            not_allowed = not_allowed ? ', ' + $(this).next('label').text() : $(this).next('label').text();
                                        }
                                        $(this).prop('checked', false);
                                        $(this).parent().parent().addClass('hide');
                                    }
                                });
                                if (not_allowed) {
                                    not_allowed_message = '{/literal}{$lang.account_type_not_allow_add_listing}{literal}';
                                    printMessage('alert', not_allowed_message.replace('{type}', not_allowed));
                                }
                            } else {
                                $('#ms_add_listing').prop('checked', false);
                                printMessage('error', response.message);

                            }
                            listingAreaControl();
                        }
                    });
                } else {
                    $('#accounts_tab_area input[type="checkbox"]').each(function() {
                        $(this).parent().parent().removeClass('hide');
                    });
                    listingAreaControl();
                }
            }

            var listingAreaControl = function() {
                if ($('#ms_add_listing').is(':checked')) {
                    $('#listings_area').removeClass('hide');
                } else {
                    $('#listings_area').addClass('hide');
                }
            }
            {/literal}
        </script>
    </div>

    <div id="listings_area">
        <table class="form">
            <tr>
                <td class="name">{$lang.listing_number}</td>
                <td class="field">
                    <div id="listing_number_area">
                        <input type="text" name="listing_number" value="{$sPost.listing_number}" class="numeric" style="width: 50px; text-align: center;" />
                        <span class="field_description">{$lang.featured_option_advanced_hint}</span>
                        &nbsp;<a id="featured_advanced_switcher" href="javascript:void(0)" class="static hide">{$lang.advanced_mode}</a>
                        <input type="hidden" name="advanced_mode" value="{if $sPost.advanced_mode}{$sPost.advanced_mode}{else}0{/if}" />
                    </div>
                </td>
            </tr>
        </table>

        <div id="featured_advanced_area" class="hide">
            <table class="form">
                <tr>
                    <td class="name">{$lang.featured_type_standard} <span class="red">*</span></td>
                    <td class="field">
                        <input type="text" name="standard_listings" value="{$sPost.standard_listings}" class="numeric margin" style="width: 50px; text-align: center;" />
                        <span class="field_description">{$lang.featured_option_advanced_hint}</span>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.featured_type_featured} <span class="red">*</span></td>
                    <td class="field">
                        <input type="text" name="featured_listings" value="{$sPost.featured_listings}" class="numeric margin" style="width: 50px; text-align: center;" />
                        <span class="field_description">{$lang.featured_option_advanced_hint}</span>
                    </td>
                </tr>
            </table>
        </div>
        <table class="form">
            <tr>
                <td class="name">{$lang.listing_options}</td>
                <td class="field">
                    <fieldset class="light" style="margin-top: 5px;">
                        <legend id="legend_listing_settings" class="up" onclick="fieldset_action('listing_settings');">{$lang.options_per_listing}</legend>
                        <div id="listing_settings" style="padding: 2px 10px 5px;">
                            <table class="form wide">
                                <tr>
                                    <td class="name">{$lang.featured_option}</td>
                                    <td class="field">
                                        <div id="featured_settings">
                                            {assign var='checkbox_field' value='featured_listing'}

                                            {if $sPost.$checkbox_field == '1'}
                                                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                                            {elseif $sPost.$checkbox_field == '0'}
                                                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                            {else}
                                                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                            {/if}

                                            <input {$featured_listing_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                                            <input {$featured_listing_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                                        </div>

                                        <script type="text/javascript">
                                        var plans_featured_advanced = {if $sPost.advanced_mode}true{else}false{/if};
                                        var phrase_set_unlimited = "{$lang.set_unlimited}";
                                        var phrase_unset_unlimited = "{$lang.unset_unlimited}";

                                        {literal}

                                        $(document).ready(function(){
                                            plans_handler();
                                            featuredAdvancedNav();
                                            featuredAdvancedSwitcher();
                                            featuredAdvancedCounter();
                                        });

                                        var plans_handler = function(){
                                            $('input[name=featured_listing]').click(function(){
                                                featuredAdvancedNav();
                                            });

                                            $('#featured_advanced_switcher').click(function(){
                                                featuredAdvancedArea();
                                            });

                                            $('#featured_advanced_area input').keyup(function(){
                                                featuredAdvancedCounter();
                                            });
                                        }

                                        var featuredAdvancedNav = function(){
                                            var option = parseInt($('input[name=featured_listing]:checked').val());

                                            if (option) {
                                                $('#featured_advanced_switcher').fadeIn('normal');
                                                featuredAdvancedSwitcher('show');
                                            } else {
                                                $('#featured_advanced_switcher').fadeOut('normal')
                                                featuredAdvancedSwitcher('hide');
                                            }

                                            $('#using_limit').slideUp('normal');
                                            $('#plan_live, #not_featured, #featured_area').slideDown('normal');
                                        };

                                        var featuredAdvancedArea = function() {
                                            if ( plans_featured_advanced )
                                            {
                                                $('#featured_advanced_switcher').css('font-weight', 'normal');
                                                plans_featured_advanced = false;
                                                $('input[name=advanced_mode]').val(0);
                                                $('#featured_advanced_area').slideUp('normal');
                                                $('input[name=listing_number]').attr('readonly', false).parent().attr('class', 'input');
                                            }
                                            else
                                            {
                                                $('#featured_advanced_switcher').css('font-weight', 'bold');
                                                plans_featured_advanced = true;
                                                $('input[name=advanced_mode]').val(1);
                                                $('#featured_advanced_area').slideDown('normal');
                                                $('input[name=listing_number]').attr('readonly', true).parent().attr('class', 'input_disabled');
                                            }
                                        };

                                        var featuredAdvancedSwitcher = function(mode) {
                                            if ( mode == 'hide' && plans_featured_advanced )
                                            {
                                                $('#featured_advanced_switcher').css('font-weight', 'normal');
                                                $('#featured_advanced_area').slideUp('normal');
                                                $('input[name=listing_number]').attr('readonly', false).parent().attr('class', 'input');
                                            }
                                            else if ( mode == 'show' && plans_featured_advanced )
                                            {
                                                $('#featured_advanced_switcher').css('font-weight', 'bold');
                                                $('#featured_advanced_area').slideDown('normal');
                                                $('input[name=listing_number]').attr('readonly', true).parent().attr('class', 'input_disabled');

                                                featuredAdvancedCounter();
                                            }
                                        };

                                        var featuredAdvancedCounter = function() {
                                            if ( plans_featured_advanced )
                                            {
                                                var standard = $('#featured_advanced_area input[name=standard_listings]').val();
                                                var featured = $('#featured_advanced_area input[name=featured_listings]').val();

                                                standard = standard != '' ? parseInt(standard) : 0;
                                                featured = featured != '' ? parseInt(featured) : 0;

                                                var total = standard + featured;
                                                $('input[name=listing_number]').val(total);
                                            }
                                        }

                                        {/literal}
                                        </script>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.images_number}</td>
                                    <td class="field">
                                        <table class="infinity">
                                        <tr>
                                            <td><input accesskey="{$sPost.images}" type="text" name="images" value="{$sPost.images}" class="numeric" style="width: 50px; text-align: center;" /></td>
                                            <td>
                                                <span title="{if $sPost.images_unlimited}{$lang.unset_unlimited}{else}{$lang.set_unlimited}{/if}" class="{if $sPost.images_unlimited}active{else}inactive{/if}"></span>
                                                <input name="images_unlimited" type="hidden" value="{if $sPost.images_unlimited}1{else}0{/if}" />
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="name">{$lang.number_of_videos}</td>
                                    <td class="field">
                                        <table class="infinity">
                                        <tr>
                                            <td><input accesskey="{$sPost.video}" type="text" name="video" value="{$sPost.video}" class="numeric" style="width: 50px; text-align: center;" /></td>
                                            <td>
                                                <span title="{if $sPost.video_unlimited}{$lang.unset_unlimited}{else}{$lang.set_unlimited}{/if}" class="{if $sPost.video_unlimited}active{else}inactive{/if}"></span>
                                                <input name="video_unlimited" type="hidden" value="{if $sPost.video_unlimited}1{else}0{/if}" />
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.cross_listing}</td>
                                    <td>
                                        <input type="text" name="cross" value="{$sPost.cross}" class="numeric" style="width: 50px; text-align: center;" />
                                        <span class="field_description">{$lang.cross_listing_hint}</span>

                                        {if $smarty.get.action == 'edit'}
                                        <script type="text/javascript">
                                        var current_cross_value = {if $plan_info.Cross}{$plan_info.Cross}{else}false{/if};
                                        var remove_cross_value_notice = "{$lang.remove_plan_crossed_option_notice}";
                                        {literal}

                                        $(document).ready(function(){
                                            if ( !current_cross_value )
                                                return;

                                            $('input[name=cross]').keyup(function(){
                                                if (parseInt($(this).val()) == 0 && current_cross_value) {
                                                    rlConfirm(remove_cross_value_notice, function(){}, false, false, false, 'crossedValueHandler');
                                                }
                                            });
                                        });

                                        var crossedValueHandler = function(){
                                            $('input[name=cross]').val(current_cross_value);
                                        }

                                        {/literal}
                                        </script>
                                        {/if}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </fieldset>
                </td>
            </tr>
        </table>
    </div>

    <!-- subscription options -->
    <div id="subscription_area">
        <table class="form">
            <tr>
                <td class="name">{$lang.subscription_box}</td>
                <td class="field">
                    <fieldset class="light" style="margin-top: 5px;">
                        <legend id="legend_subscription_settings" class="up" onclick="fieldset_action('subscription_settings');">{$lang.settings}</legend>
                        <div id="subscription_settings" style="padding: 2px 10px 5px;">
                            <table class="form wide">
                                <tr>
                                    <td class="name">{$lang.subscription_enable}</td>
                                    <td class="field">
                                        {assign var='checkbox_field' value='subscription'}

                                        {if $sPost.$checkbox_field == '1'}
                                            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                                        {elseif $sPost.$checkbox_field == '0'}
                                            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                        {else}
                                            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                        {/if}

                                        <input {$subscription_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" class="subscription-status" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                                        <input {$subscription_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" class="subscription-status" /> <label for="{$checkbox_field}_no">{$lang.no}</label>

                                        <div class="clearfix hide" id="subscribers_area" style="padding: 15px 0;"></div>
                                    </td>
                                </tr>
                            </table>
                            <table id="subscription_settings_list" class="form wide{if !$sPost.$checkbox_field} hide{/if}">
                                <tr>
                                    <td class="name">{$lang.subscription_period}</td>
                                    <td class="field">
                                        <select name="period" id="subscription_period">
                                            <option value="">{$lang.select}</option>
                                            {foreach from=$subscription_periods item='period' key='key'}
                                                <option value="{$key}" {if $sPost.period == $key}selected="selected"{/if}>{$period}</option>
                                            {/foreach}
                                        </select>
                                    </td>
                                </tr>
                                <tr id="subscription_period_total">
                                    <td class="name">{$lang.subscription_period_total}</td>
                                    <td class="field">
                                        <select name="period_total" id="period_total">
                                            <option value="">{$lang.select}</option>
                                        </select>
                                        <span class="field_description">{$lang.subscription_period_total_hint}</span>
                                        <script type="text/javascript">
                                            var subscription_period = $('#subscription_period option:selected').val();
                                            var period_total = '{$sPost.period_total}';
                                            {literal}

                                            var getListIterationsByPeriod = function(period) {
                                                var list = new Array();

                                                if (!period) {
                                                    return list;
                                                }

                                                switch(period) {
                                                    case 'day' :
                                                        list = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30];
                                                        break;

                                                    case 'week' :
                                                        list = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15];
                                                        break;

                                                    case 'month' :
                                                        list = [1, 3, 6, 12, 18, 24];
                                                        break;

                                                    case 'year' :
                                                        list = [1, 2, 3, 4, 5];
                                                        break;
                                                }

                                                $('#period_total').empty();
                                                $('#period_total').append($('<option value="">{/literal}{$lang.select}{literal}</option>'));
                                                for (var i = 0; i < list.length; i++) {
                                                    if (list[i] == period_total) {
                                                        $('#period_total').append($('<option value="'+list[i]+'" selected="selected">'+list[i]+'</option>'));
                                                    } else {
                                                        $('#period_total').append($('<option value="'+list[i]+'">'+list[i]+'</option>'));
                                                    }
                                                }
                                            }

                                            if (subscription_period) {
                                                getListIterationsByPeriod(subscription_period);
                                            }

                                            $(document).ready(function(){
                                                $('#subscription_period').change(function() {
                                                    getListIterationsByPeriod($(this).val());
                                                });
                                            });
                                            {/literal}
                                        </script>
                                    </td>
                                </tr>
                                {foreach from=$subscription_options item='option'}
                                    {if $option.Type == 'text'}
                                        <tr>
                                            <td class="name">{$option.name}</td>
                                            <td class="field">
                                                <input type="text" name="sop[{$option.Key}]" value="{$sPost.sop[$option.Key]}" />
                                            </td>
                                        </tr>
                                    {elseif $option.Type == 'numeric'}
                                        <tr>
                                            <td class="name">{$option.name}</td>
                                            <td class="field">
                                                <input type="text" name="sop[{$option.Key}]" class="numeric" value="{$sPost.sop[$option.Key]}" style="width: 50px; text-align: center;" />
                                            </td>
                                        </tr>
                                    {elseif $option.Type == 'bool'}
                                        <tr>
                                            <td class="name">{$option.name}</td>
                                            <td class="field">
                                                {assign var='checkbox_field' value=$option.Key}

                                                {if $sPost.sop.$checkbox_field == '1'}
                                                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                                                {elseif $sPost.sop.$checkbox_field == '0'}
                                                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                                {else}
                                                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                                                {/if}

                                                <input {$subscription_yes} type="radio" id="{$checkbox_field}_yes" name="sop[{$checkbox_field}]" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                                                <input {$subscription_no} type="radio" id="{$checkbox_field}_no" name="sop[{$checkbox_field}]" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                                            </td>
                                        </tr>
                                    {elseif $option.Type == 'select'}
                                        <tr>
                                            <td class="name">{$option.name}</td>
                                            <td class="field">
                                                <select name="sop[{$option.Key}]">
                                                    {foreach from=$option.values item='ovalue'}
                                                        <option value="{$ovalue.key}" {if $sPost.sop[$option.Key] == $ovalue.key}selected="selected"{/if}>{$ovalue.name}</option>
                                                    {/foreach}
                                                </select>
                                            </td>
                                        </tr>
                                    {/if}
                                {/foreach}
                            </table>
                        </div>
                    </fieldset>
                    <script type="text/javascript">
                        {literal}
                        $(document).ready(function(){
                            $('input.subscription-status').change(function() {
                                if ($(this).val() == 0 && $(this).is(':checked')) {
                                    $('#subscription_settings_list').addClass('hide');
                                    {/literal}{if $smarty.get.action == 'edit' && $smarty.post.subscription}{literal}
                                    $('#subscription_no').next().after('<img src="{/literal}{$rlTplBase}{literal}img/loader.gif" />');
                                    flynax.getSubscribersByPlan('{/literal}{$plan_info.ID}{literal}', 'membership');
                                    {/literal}{/if}{literal}
                                } else {
                                    $('#subscription_settings_list').removeClass('hide');
                                }
                            });
                        });
                        {/literal}
                    </script>
                </td>
            </tr>
        </table>
    </div>
    <!-- subscription options end -->

    {rlHook name='apTplMembershipPlansForm'}

    <table class="form">
    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select name="status">
                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>
    </table>

    <table class="form">
        <tr>
            <td class="no_divider"></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add/edit new plan end -->

{else}

    <!-- delete plan block -->
    <div id="delete_block" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.delete_plan}
            <div id="delete_container">
                {$lang.detecting}
            </div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <script type="text/javascript">//<![CDATA[
        {if $config.trash}
            var delete_confirm_phrase = "{$lang.notice_dro_membershipp_plan}";
        {else}
            var delete_confirm_phrase = "{$lang.notice_delete_membership_plan}";
        {/if}

        {literal}

        function delete_chooser(method, key, username)
        {
            if (method == 'delete')
            {
                rlPrompt(delete_confirm_phrase.replace('{username}', username), 'xajax_deletePlan', key);
            }
            else if (method == 'replace')
            {
                $('#top_buttons').slideUp('slow');
                $('#bottom_buttons').slideDown('slow');
                $('#replace_content').slideDown('slow');
            }
        }

        {/literal}
        //]]>
        </script>
    </div>
    <!-- delete plan block end -->

    <!-- listing plans grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var MembershipPlansGrid;

    {literal}
    $(document).ready(function(){

        MembershipPlansGrid = new gridObj({
            key: 'MembershipPlans',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/membership_plans.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_membership_plans_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Key', mapping: 'Key', type: 'string'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Price', mapping: 'Price', type: 'float'},
                {name: 'Plan_period', mapping: 'Plan_period', type: 'int'},
                {name: 'Image', mapping: 'Image'},
                {name: 'Image_unlim', mapping: 'Image_unlim'},
                {name: 'Cross', mapping: 'Cross'},
                {name: 'Video', mapping: 'Video'},
                {name: 'Video_unlim', mapping: 'Video_unlim'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Subscription', mapping: 'Subscription'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 17,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_price']+' ('+rlCurrency+')',
                    dataIndex: 'Price',
                    width: 7,
                    css: 'font-weight: bold;',
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: '{/literal}{$lang.plan_live_for} ({$lang.days}){literal}',
                    dataIndex: 'Plan_period',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val, obj, row){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: '{/literal}{$lang.images_number}{literal}',
                    dataIndex: 'Image',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val, obj, row){
                        val = row.data.Image_unlim == '1' && row.data.Image == 0  ? '{/literal}{$lang.unlimited}{literal}' : val;
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: '{/literal}{$lang.number_of_videos}{literal}',
                    dataIndex: 'Video',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val, obj, row){
                        val = row.data.Video_unlim == '1' && row.data.Video == 0 ? '{/literal}{$lang.unlimited}{literal}' : val;
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 6,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_subscription'],
                    dataIndex: 'Subscription',
                    width: 6,
                    renderer: function(val) {
                        return '<span>'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang.active],
                            ['approval', lang.approval]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data, ext, row) {
                        var out = "<center>";

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&plan="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }

                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='xajax_prepareDeleting("+row.data.ID+")' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplMembershipPlansGrid'}{literal}

        MembershipPlansGrid.init();
        grid.push(MembershipPlansGrid.grid);

    });
    {/literal}
    //]]>
    </script>
    <!-- listing plans grid end -->

    {rlHook name='apTplMembershipPlansBottom'}

{/if}
<!-- listing plans tpl end -->
