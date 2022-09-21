<!-- listing plans tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplListingPlansNavBar'}

    {if $aRights.$cKey.add}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_plan}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.plans_list}</span><span class="right"></span></a>
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
        <td class="name"><span class="red">*</span>{$lang.key}</td>
        <td class="field">
            <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
        </td>
    </tr>

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
        <td class="name">{$lang.show_in_categories}</td>
        <td class="field">
            {include file="blocks"|cat:$smarty.const.RL_DS|cat:"categories_tree.tpl"}
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
                            {foreach from=$account_types item='a_type' name='ac_type'}
                                <td>
                                    <div style="margin: 0 20px 0 0;">
                                        <input {if $sPost.account_type && $a_type.Key|in_array:$sPost.account_type}checked="checked"{/if}
                                               style="margin-bottom: 0px;"
                                               type="checkbox"
                                               id="account_type_{$a_type.ID}"
                                               value="{$a_type.Key}"
                                               name="account_type[]" /> <label for="account_type_{$a_type.ID}">{$a_type.name}</label>
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
                            <span class="field_description">{$lang.info_account_type_plans}</span>
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
        <td class="name">
            <div><span class="red">*</span>{$lang.plan_type}</div>
        </td>
        <td class="field">
            <select name="type">
            <option value="">{$lang.select}</option>
            {foreach from=$l_plan_types item='type' key='pKey'}
                <option value="{$pKey}" {if $pKey == $sPost.type}selected="selected"{/if}>{$type}</option>
            {/foreach}
            </select>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.price}</td>
        <td class="field">
            <input type="text" name="price" value="{$sPost.price}" class="numeric" style="width: 50px; text-align: center;" /> <span class="field_description_noicon">&nbsp;{$config.system_currency}</span>
        </td>
    </tr>
    </table>

    <div id="featured_area">
    <table class="form">
    <tr>
        <td class="name">{$lang.featured_option}</td>
        <td class="field">
            <fieldset class="light" style="margin-top: 5px;">
                <legend id="legend_featured_settings" class="up" onclick="fieldset_action('featured_settings');">{$lang.settings}</legend>
                <div id="featured_settings" style="padding: 2px 10px 5px;">
                    {assign var='checkbox_field' value='featured'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}

                    <input {$featured_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$featured_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                    <a id="featured_advanced_switcher" href="javascript:void(0)" class="static hide">{$lang.advanced_mode}</a>
                    <input type="hidden" name="advanced_mode" value="{if $sPost.advanced_mode}{$sPost.advanced_mode}{else}0{/if}" />

                    <div id="featured_advanced_area" class="hide">
                        <table>
                        <tr>
                            <td>
                                <div style="padding: 10px 10px 0 0;">
                                    <input type="text" name="fa_standard" value="{$sPost.fa_standard}" class="numeric margin" style="width: 50px; text-align: center;" /> <span class="field_description_noicon">{$lang.featured_type_standard}</span> <span class="red">*</span>
                                    <div class="clear">
                                        <input type="text" name="fa_featured" value="{$sPost.fa_featured}" class="numeric margin" style="width: 50px; text-align: center;" /> <span class="field_description_noicon">{$lang.featured_type_featured}</span> <span class="red">*</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="field_description">{$lang.featured_option_advanced_hint}</span>
                            </td>
                        </tr>
                        </table>
                    </div>
                </div>
            </fieldset>
        </td>
    </tr>
    </table>
    </div>

    <script type="text/javascript">
    var plans_featured_advanced = {if $sPost.advanced_mode}true{else}false{/if};
    var phrase_set_unlimited = "{$lang.set_unlimited}";
    var phrase_unset_unlimited = "{$lang.unset_unlimited}";

    {literal}

    $(document).ready(function(){
        plans_handler();
        {/literal}{if $sPost.type}{literal}
        featuredAdvancedNav();
        featuredAdvancedSwitcher();
        featuredAdvancedCounter();
        {/literal}{/if}{literal}
    });

    var plans_handler = function(){
        $('select[name=type]').change(function(){
            featuredAdvancedNav();
        });

        $('input[name=featured]').click(function(){
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
        var type = $('select[name=type]').val();
        var option = parseInt($('input[name=featured]:checked').val());

        if ( type == 'package' && option )
        {
            $('#featured_advanced_switcher').fadeIn('normal');
            featuredAdvancedSwitcher('show');
        }
        else
        {
            $('#featured_advanced_switcher').fadeOut('normal')
            featuredAdvancedSwitcher('hide');
        }

        if ( type == 'package' )
        {
            $('#using_limit').slideUp('normal');
            $('#package, #plan_live, #not_featured, #featured_area').slideDown('normal');
        }
        else
        {
            $('#package, #plan_live').slideUp('normal');
            $('#using_limit').slideDown('normal');

            if ( type == 'featured' )
            {
                $('#not_featured, #featured_area').slideUp('normal');
            }
            else
            {
                $('#not_featured, #featured_area').slideDown('normal');
            }
        }
    };

    var featuredAdvancedArea = function(){
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

    var featuredAdvancedSwitcher = function(mode){
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

    var featuredAdvancedCounter = function(){
        if ( plans_featured_advanced )
        {
            var standard = $('#featured_advanced_area input[name=fa_standard]').val();
            var featured = $('#featured_advanced_area input[name=fa_featured]').val();

            standard = standard != '' ? parseInt(standard) : 0;
            featured = featured != '' ? parseInt(featured) : 0;

            var total = standard + featured;
            $('input[name=listing_number]').val(total);
        }
    }

    {/literal}
    </script>

    <!-- select category action -->
    <script type="text/javascript">

    {literal}
    function cat_chooser(cat_id){
        return true;
    }
    {/literal}

    {if $smarty.post.parent_id}
        cat_chooser('{$smarty.post.parent_id}');
    {elseif $smarty.get.parent_id}
        cat_chooser('{$smarty.get.parent_id}');
    {/if}

    </script>
    <!-- select category action end -->

    <!-- additional options -->
    <div id="additional_options">

    <!-- listing number -->
    <div id="package" class="hide">
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.listing_number}</td>
        <td class="field">
            <input type="text" name="listing_number" value="{$sPost.listing_number}" class="numeric" style="width: 50px; text-align: center;" />
            <span class="field_description">{$lang.featured_option_advanced_hint}</span>
        </td>
    </tr>
    </table>
    </div>
    <!-- listing number end -->

    </div>

    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.listing_live_for}</td>
        <td class="field">
            <input type="text" name="listing_period" value="{$sPost.listing_period}" class="numeric" style="width: 50px; text-align: center;" />
            <span class="field_description_noicon">{$lang.days}</span>
            <span class="field_description">{$lang.listing_live_for_hint}</span>
        </td>
    </tr>
    </table>

    <div id="plan_live" class="hide">
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.plan_live_for}</td>
        <td class="field">
            <input type="text" name="plan_period" value="{$sPost.plan_period}" class="numeric" style="width: 50px; text-align: center;" />
            <span class="field_description_noicon">{$lang.days}</span>
            <span class="field_description">{$lang.plan_live_for_hint}</span>
        </td>
    </tr>
    </table>
    </div>

    <div id="using_limit">
    <table class="form">
    <tr>
        <td class="name">{$lang.limit_use_of_plan}</td>
        <td class="field">
            <input type="text" name="limit" value="{$sPost.limit}" class="numeric" style="width: 50px; text-align: center;" />
            <span class="field_description_noicon">{$lang.times}</span>
            <span class="field_description">{$lang.limit_use_of_plan_hint}</span>
        </td>
    </tr>
    </table>
    </div>

    <div id="not_featured">
    <table class="form">
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
                    if ( parseInt($(this).val()) == 0 && current_cross_value )
                    {
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
                                    flynax.getSubscribersByPlan('{/literal}{$plan_info.ID}{literal}', 'listing');
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

    {rlHook name='apTplListingPlansForm'}

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
            var delete_confirm_phrase = "{$lang.notice_drop_plan}";
        {else}
            var delete_confirm_phrase = "{$lang.notice_delete_plan}";
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
    var listingPlansGrid;

    {literal}
    $(document).ready(function(){

        listingPlansGrid = new gridObj({
            key: 'listingPlans',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/listing_plans.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_listing_plans_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Type_name', mapping: 'Type_name'},
                {name: 'Featured', mapping: 'Featured'},
                {name: 'Limit', mapping: 'Limit'},
                {name: 'Price', mapping: 'Price', type: 'float'},
                {name: 'Listing_period', mapping: 'Listing_period'},
                {name: 'Plan_period', mapping: 'Plan_period'},
                {name: 'Image', mapping: 'Image'},
                {name: 'Image_unlim', mapping: 'Image_unlim'},
                {name: 'Cross', mapping: 'Cross'},
                {name: 'Video', mapping: 'Video'},
                {name: 'Video_unlim', mapping: 'Video_unlim'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Subscription', mapping: 'Subscription'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 17,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type_name',
                    width: 12,
                    id: 'rlExt_item'
                },{
                    header: '{/literal}{$lang.featured_option}{literal}',
                    dataIndex: 'Featured',
                    width: 8,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        emptyText: lang['ext_not_available'],
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    })
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
                    header: '{/literal}{$lang.listing_live_for}{literal}',
                    dataIndex: 'Listing_period',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        val = val == 0 ? '{/literal}{$lang.unlimited}{literal}' : val;
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: '{/literal}{$lang.plan_live_for}{literal}',
                    dataIndex: 'Plan_period',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val, obj, row){
                        if ( row.data.Type == 'package' )
                        {
                            val = val == 0 ? '{/literal}{$lang.unlimited}{literal}' : val;
                        }
                        else
                        {
                            val = '{/literal}{$lang.not_available}{literal}';
                        }
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: '{/literal}{$lang.limit_use_of_plan}{literal}',
                    dataIndex: 'Limit',
                    width: 10,
                    hidden: true,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        val = val == 0 ? '{/literal}{$lang.unlimited}{literal}' : val;
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
                        val = row.data.Image_unlim == '1' && row.data.Image == 0 ? '{/literal}{$lang.unlimited}{literal}' : val;
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
                        val = row.data.Video_unlim == '1' && row.data.Video == 0  ? '{/literal}{$lang.unlimited}{literal}' : val;
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
                    width: 10,
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

        {/literal}{rlHook name='apTplListingPlansGrid'}{literal}

        listingPlansGrid.init();
        grid.push(listingPlansGrid.grid);

    });
    {/literal}
    //]]>
    </script>
    <!-- listing plans grid end -->

    {rlHook name='apTplListingPlansBottom'}

{/if}

<!-- listing plans tpl end -->
