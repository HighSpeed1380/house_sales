<!-- account types tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.caret.js"></script>

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplAccountTypesNavBar'}

    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_type}</span><span class="right"></span></a>
    {/if}
    {if $aRights.$cKey.edit && $smarty.get.action == 'build'}
        {if $smarty.get.form != 'reg_form'}
            <a href="{$rlBase}index.php?controller=account_types&amp;action=build&amp;form=reg_form&amp;key={$category_info.Key}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.registration_form}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.form != 'search_form'}
            <a href="{$rlBase}index.php?controller=account_types&amp;action=build&amp;form=search_form&amp;key={$category_info.Key}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.search_form}</span><span class="right"></span></a>
        {/if}
        {if $smarty.get.form != 'short_form'}
            <a href="{$rlBase}index.php?controller=account_types&amp;action=build&amp;form=short_form&amp;key={$category_info.Key}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.short_form}</span><span class="right"></span></a>
        {/if}
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.types_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

    {assign var='sPost' value=$smarty.post}

    <!-- add/edit new type -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;type={$smarty.get.type}{/if}" method="post">
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
        <td class="name">
            <span class="red">*</span>{$lang.name}
        </td>
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
        <td class="field ckeditor">
            {if $allLangs|@count > 1}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>
            {/if}

            {foreach from=$allLangs item='language' name='langF'}
                {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                {assign var='dCode' value='description_'|cat:$language.Code}
                {fckEditor name='description_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                {if $allLangs|@count > 1}</div>{/if}
            {/foreach}
        </td>
    </tr>

    {if $smarty.get.action == 'edit'}
        {foreach from=$meta_fields item='meta_field'}
        <tr>
            <td class="name">{$lang.$meta_field}</td>
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
                        {if $meta_field == 'account_meta_description'}
                            <textarea cols="50" rows="2" name="{$meta_field}[{$language.Code}]">{$sPost[$meta_field][$language.Code]}</textarea>
                        {else}
                            <input type="text" name="{$meta_field}[{$language.Code}]" value="{$sPost[$meta_field][$language.Code]}" size="80" class="wauto" />
                        {/if}
                        <div>
                        <select>
                            <option value="0">{$lang.select}</option>
                            {foreach from=$fields item="field"}
                                <option value="{$field.Key}">{$field.name}</option>
                            {/foreach}
                        </select>
                        <input type="button" class="add_variable_button" value="{$lang.add}"/>
                        </div>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        {/foreach}
    {/if}

    <tr>
        <td class="name">{$lang.alphabetic_field}</td>
        <td class="field">
            <select name="alphabetic_field" {if !$alphabetic_fields}class="disabled" disabled="disabled"{/if}>
                <option value="" {if !$sPost.alphabetic_field}selected="selected"{/if}>{$lang.username}</option>

                {foreach from=$alphabetic_fields item='alphabetic_field' key='alphabetic_field_key'}
                    {assign var="accont_field_name" value='account_fields+name+'|cat:$alphabetic_field_key}
                    <option value="{$alphabetic_field_key}" {if $sPost.alphabetic_field == $alphabetic_field_key}selected="selected"{/if}>{$lang.$accont_field_name}</option>
                {/foreach}
            </select>

            <span class="field_description">{$lang.alphabetic_field_desc}</span>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.abilities}</td>
        <td class="field">
            <fieldset class="light" style="margin-top: 5px;">
                <legend id="legend_account_abb" class="up" onclick="fieldset_action('account_abb');">{$lang.abilities}</legend>
                <div id="account_abb">
                    {foreach from=$listing_types item='l_type'}
                        {if $smarty.get.action == 'edit' && !$l_type.Deny_uncheck_ability}
                            <input checked="checked" type="hidden" name="abilities[]" value="{$l_type.Key}" />
                        {/if}

                        <div class="option_padding">
                            {assign var='replace' value=`$smarty.ldelim`type`$smarty.rdelim`}
                            <label>
                                <input {if $sPost.abilities && $l_type.Key|in_array:$sPost.abilities}checked="checked"{/if}
                                       type="checkbox"
                                       name="abilities[]"
                                       value="{$l_type.Key}"
                                        {if $smarty.get.action == 'edit' && !$l_type.Deny_uncheck_ability}class="disabled" disabled="disabled"{/if}
                                /> {$lang.ability_to_add|replace:$replace:$l_type.name}
                            </label>
                        </div>
                    {/foreach}

                    {if $smarty.get.type != 'visitor'}
                        <table cellpadding="0" cellspacing="0">
                        <tr>
                            <td>
                                <div class="option_padding">
                                    <label><input {if $sPost.page}checked="checked"{/if} type="checkbox" name="page" value="1" /> {$lang.account_type_custom_page}</label>
                                </div>
                            </td>
                            {if $smarty.get.action == 'edit' && $sPost.page}
                                {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=pages&amp;action=edit&amp;page=at_'|cat:$smarty.get.type|cat:'">$1</a>'}
                                <td><span class="field_description">{$lang.individual_page_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                            {/if}
                        </tr>
                        </table>

                        <div class="option_padding">
                            {if $config.account_wildcard}
                                {assign var='replace' value=$lang.sub_domain}
                            {else}
                                {assign var='replace' value=$lang.sub_directory}
                            {/if}
                            {assign var='s_type' value=`$smarty.ldelim`type`$smarty.rdelim`}
                            <label><input {if $sPost.own_location}checked="checked"{/if} type="checkbox" name="own_location" value="1" /> {$lang.account_type_own_location|replace:$s_type:$replace}</label>
                        </div>

                        <div class="option_padding">
                            {if !$allow_change_quick_registration}
                                <input type="hidden" name="quick_registration" value="1" />
                            {/if}

                            <label><input {if $sPost.quick_registration || $smarty.get.action == 'add'}checked="checked"{/if} type="checkbox" name="quick_registration" value="1" {if $smarty.get.action == 'edit' && !$allow_change_quick_registration}class="disabled" disabled="disabled"{/if} /> {$lang.atype_quick_registration_option}</label>
                        </div>

                        <div class="option_padding">
                            <label{if $sPost.agent
                                    || $config.membership_module
                                    || (isset($sPost.isAllowDisableAgency) && $sPost.isAllowDisableAgency === false)} class="disabled"{/if}
                            >
                                <input checked="checked" type="hidden" name="agency" value="{$sPost.agency}" />
                                <input {if $sPost.agency && !$config.membership_module}checked="checked"{/if}
                                       {if $sPost.agent
                                            || $config.membership_module
                                            || (isset($sPost.isAllowDisableAgency) && $sPost.isAllowDisableAgency === false)}disabled="disabled" class="disabled"{/if}
                                       type="checkbox"
                                       name="agency"
                                       value="1"
                                />&nbsp;{$lang.atype_agency_option}
                            </label>

                            {if $config.membership_module}
                                {assign var='replace' value='<a class="static" href="'|cat:$rlBase|cat:'index.php?controller=membership_services">$1</a>'}
                                <span class="field_description">{$lang.atype_agency_option_membership|regex_replace:'/\[(.*)\]/':$replace}</span>
                            {/if}
                        </div>

                        <div class="option_padding">
                            <label{if $sPost.agency && !$config.membership_module
                                    || (isset($sPost.isAllowDisableAgent) && $sPost.isAllowDisableAgent === false)} class="disabled"{/if}>
                                <input checked="checked" type="hidden" name="agent" value="{$sPost.agent}" />
                                <input {if $sPost.agent}checked="checked"{/if}
                                       {if $sPost.agency && !$config.membership_module
                                            || (isset($sPost.isAllowDisableAgent) && $sPost.isAllowDisableAgent === false)}disabled="disabled" class="disabled"{/if}
                                       type="checkbox"
                                       name="agent"
                                       value="1"
                                />&nbsp;{$lang.atype_agent_option}
                            </label>
                        </div>

                        <script>
                            let isAllowDisableAgency = {if isset($sPost.isAllowDisableAgency) && $sPost.isAllowDisableAgency === false}false{else}true{/if};
                            let isAllowDisableAgent  = {if isset($sPost.isAllowDisableAgent) && $sPost.isAllowDisableAgent === false}false{else}true{/if};

                            {literal}
                            $(function(){
                                $('input[name=page]').click(function(){
                                    ownPageControl();
                                });

                                $('[name=agency][type=checkbox],[name=agent][type=checkbox]').click(function () {
                                    agencyControl();
                                });

                                ownPageControl();
                                agencyControl();
                            });

                            const ownPageControl = function() {
                                if ($('input[name=page]:checked').length>0) {
                                    $('input[name=own_location]').attr('disabled', false).parent().removeClass('disabled');
                                } else {
                                    $('input[name=own_location]').attr('checked', false).attr('disabled', true).parent().addClass('disabled');
                                }
                            }

                            const agencyControl = function () {
                                if (rlConfig.membershipModule
                                    || isAllowDisableAgency === false
                                    || isAllowDisableAgent === false
                                ) {
                                    return;
                                }

                                let $agency       = $('[name=agency][type=checkbox]');
                                let $agencyHidden = $('[name=agency][type=hidden]');
                                let $agent        = $('[name=agent][type=checkbox]');
                                let $agentHidden  = $('[name=agent][type=hidden]');

                                $agent.prop('disabled', $agency.is(':checked'));
                                $agentHidden.val($agent.is(':checked') ? '1' : '0');
                                $agent[$agency.is(':checked') ? 'addClass' : 'removeClass']('disabled');
                                $agent.closest('label')[$agency.is(':checked') ? 'addClass' : 'removeClass']('disabled');

                                $agency.prop('disabled', $agent.is(':checked'));
                                $agencyHidden.val($agency.is(':checked') ? '1' : '0');
                                $agency[$agent.is(':checked') ? 'addClass' : 'removeClass']('disabled');
                                $agency.closest('label')[$agent.is(':checked') ? 'addClass' : 'removeClass']('disabled');
                            }
                        {/literal}</script>
                    {/if}
                </div>
            </fieldset>

            <div class="grey_area" style="margin-bottom: 10px;">
                <span onclick="aTypeAbilitiesHandler(true);" class="green_10">{$lang.check_all}</span>
                <span class="divider"> | </span>
                <span onclick="aTypeAbilitiesHandler(false);" class="green_10">{$lang.uncheck_all}</span>
            </div>

            <script>{literal}
                const aTypeAbilitiesHandler = function(isCheck) {
                    $('#account_abb input').each(function () {
                        if (!$(this).is(':disabled')) {
                            $(this).prop('checked', isCheck);

                            ownPageControl();
                            agencyControl();
                        } else {
                            if (isCheck === false) {
                                $(this).prop('checked', isCheck);
                            }
                        }
                    });
                }
            {/literal}</script>
        </td>
    </tr>

    {if $smarty.get.type != 'visitor'}
    <tr>
        <td class="name">{$lang.reg_settings}</td>
        <td class="field">
            <fieldset class="light" style="margin-top: 5px;">
                <legend id="legend_account_settings" class="up" onclick="fieldset_action('account_settings');">{$lang.reg_settings}</legend>
                <div id="account_settings">
                    {foreach from=$account_settings item='account_setting'}
                        <div class="option_padding">
                            <label><input {if $sPost[$account_setting.key]}checked="checked"{/if} type="checkbox" name="{$account_setting.key}" value="1" /> {$account_setting.name}</label>
                        </div>
                    {/foreach}
                </div>
            </fieldset>

            <script type="text/javascript">{literal}
            $(document).ready(function(){
                $('#account_settings input').click(function(){
                    ATsettingsControl();
                });

                ATsettingsControl();

                $('input.add_variable_button').click(function(){
                    var variable = $(this).prev().val();
                    if (variable != '0' && variable) {
                        var text_obj = $(this).parent().parent().find('input[type=text]:visible,textarea:visible');

                        var text = text_obj.val();

                        var caret = text_obj.getSelection();
                        var new_text = text.substring(0, caret.start) + '{' + variable + '}' + text.substring(caret.end, text.length);

                        text_obj.val(new_text).focus();
                        text_obj.setCursorPosition(caret.start+variable.length+2);
                    }
                });
            });

            var ATsettingsControl = function(){
                if ($('#account_settings input[name=admin_confirmation]:checked').length > 0
                    || $('#account_settings input[name=email_confirmation]:checked').length > 0
                ) {
                    $('#account_settings input[name=auto_login]').attr('checked', false).attr('disabled', true).parent().addClass('disabled');
                } else {
                    $('#account_settings input[name=auto_login]').attr('disabled', false).parent().removeClass('disabled');
                }
            }
            {/literal}</script>
        </td>
    </tr>

    <tr>
        {assign var="dimensions_config_name" value='config+name+account_thumb_divider'}
        {assign var="dimensions_width_name" value='config+name+account_thumb_width'}
        {assign var="dimensions_height_name" value='config+name+account_thumb_height'}

        <td class="name">{$lang.$dimensions_config_name}</td>
        <td class="field">
            <fieldset class="light" style="margin-top: 5px;">
                <legend id="legend_dimensions_settings" class="up" onclick="fieldset_action('dimensions_settings');">
                    {$lang.$dimensions_config_name}
                </legend>

                <div id="dimensions_settings">
                    <table class="form">
                        <tbody>
                            <tr>
                                <td class="name">{$lang.$dimensions_width_name}</td>
                                <td class="field">
                                    <input class="numeric" name="dimensions[width]" type="text" style="width: 25px;"
                                        value="{if $sPost.dimensions.width}{$sPost.dimensions.width}{else}110{/if}"
                                        maxlength="3" />
                                    <span class="field_description">{$lang.thumb_width_desc}</span>
                                </td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.$dimensions_height_name}</td>
                                <td class="field">
                                    <input class="numeric" name="dimensions[height]" type="text" style="width: 25px;"
                                        value="{if $sPost.dimensions.height}{$sPost.dimensions.height}{else}100{/if}"
                                        maxlength="3" />
                                    <span class="field_description">{$lang.thumb_height_desc}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </fieldset>
        </td>
    </tr>
    {/if}
    {if $config.membership_module}
    <tr>
        <td class="name">{$lang.featured_settings}</td>
        <td class="field">
            <fieldset class="light">
                <legend id="legend_featured_settings" class="up" onclick="fieldset_action('featured_settings');">{$lang.featured_settings}</legend>
                <div id="featured_settings">

                    <table class="form wide">
                        <tr>
                            <td class="name">{$lang.featured_blocks}</td>
                            <td class="field">
                                {assign var='checkbox_field' value='featured_blocks'}

                                {if $sPost.$checkbox_field == '1'}
                                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                                {elseif $sPost.$checkbox_field == '0'}
                                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                {else}
                                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                                {/if}

                                <table>
                                    <tr>
                                        <td>
                                            <input {$featured_blocks_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                                            <input {$featured_blocks_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                                        </td>
                                        {if $smarty.get.action == 'edit' && $sPost.$checkbox_field}
                                            {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=blocks&amp;action=edit&amp;block=atfb_'|cat:$smarty.get.type|cat:'">$1</a>'}
                                            <td><span class="field_description">{$lang.featured_blocks_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                                        {/if}
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>
            </fieldset>
        </td>
    </tr>
    {/if}
    {rlHook name='apTplAccountTypesForm'}

    <tr>
        <td class="name"><span class="red">*</span>{$lang.status}</td>
        <td class="field">
            {if !$allow_change_quick_registration}
                <input type="hidden" name="status" value="{$sPost.status}" />
            {/if}

            <select name="status" {if !$allow_change_quick_registration}class="disabled" disabled="disabled"{/if}>
                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>

            {if !$allow_change_quick_registration}
                <span class="field_description">{$lang.account_type_disabling_warning}</span>
            {/if}
        </td>
    </tr>

    <tr>
        <td></td>
        <td class="field">
            <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
        </td>
    </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add/edit type end -->

{elseif $smarty.get.action == 'build'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'builder.tpl' no_groups=true}

{else}

    <!-- build reqest -->
    {if $smarty.get.request == 'build'}
        <script type="text/javascript">
        var request_type_key = '{$smarty.get.key}';
        var request_account_notice = "{$lang.suggest_account_type_building}";
        {literal}

        $(document).ready(function(){
            rlConfirm(request_account_notice, 'requestRedirect', null, null, null, 'cancelRedirect');
        });

        var requestRedirect = function(){
            location.href = rlUrlHome+'index.php?controller='+controller+'&action=build&form=reg_form&key='+request_type_key;
        };

        var cancelRedirect = function(){
            window.history.pushState(null, null, rlUrlHome+'index.php?controller='+controller);
        };

        {/literal}
        </script>
    {/if}
    <!-- build reqest end -->

    <!-- rebuild avatars -->
    {if $smarty.get.rebuild_pictures}
        <script>
        lang['rebuild_avatars_promt'] = "{$lang.rebuild_avatars_promt}";
        lang['resize_in_progress'] = "{$lang.resize_in_progress}";
        lang['resize_completed'] = "{$lang.resize_completed}";
        rlConfig['rebuild_avatars_type'] = "{$smarty.get.rebuild_pictures}";
        {literal}

        $(function(){
            rlConfirm(lang['rebuild_avatars_promt'], 'flynax.initRebuildAccountPictures');
        });

        {/literal}
        </script>
    {/if}
    <!-- rebuild avatars end -->

    <!-- pre delete block -->
    <div id="delete_block" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.delete_account_type}
            <div id="delete_container">{$lang.detecting}</div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <script type="text/javascript">//<![CDATA[
        {if $config.trash}
            var delete_conform_phrase = "{$lang.notice_drop_empty_account_type}";
        {else}
            var delete_conform_phrase = "{$lang.notice_delete_empty_account_type}";
        {/if}

        {literal}
        function delete_chooser(method, id, type)
        {
            if (method == 'delete')
            {
                rlPrompt(delete_conform_phrase.replace('{type}', type), 'xajax_deleteAccountType', id);
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
    <!-- pre delete block end -->

    <!-- account types grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var accountTypesGrid;
    {literal}

    var list = [
        {
            text: lang['ext_build_reg_form'],
            href: rlUrlHome+"index.php?controller="+controller+"&amp;action=build&amp;form=reg_form&amp;key={key}"
        },
        {
            text: lang['ext_build_short_form'],
            href: rlUrlHome+"index.php?controller="+controller+"&amp;action=build&amp;form=short_form&amp;key={key}"
        },
        {
            text: lang['ext_search_form'],
            href: rlUrlHome+"index.php?controller="+controller+"&amp;action=build&amp;form=search_form&amp;key={key}"
        }
    ];

    $(document).ready(function(){
        accountTypesGrid = new gridObj({
            key: 'accountTypes',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/account_types.inc.php?q=ext',
            defaultSortField: 'name',
            remoteSortable: false,
            title: lang['ext_account_types_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Accounts_count', mapping: 'Accounts_count', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Quick_registration', mapping: 'Quick_registration'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    fixed: true,
                    width: 25
                },
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_accounts'],
                    dataIndex: 'Accounts_count',
                    width: 8,
                    renderer: function(val, ext, row){
                        //return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        if ( val )
                        {
                            var out = '<a ext:qtip="'+lang['ext_click_to_view_details']+'" target="_blank" href="'+rlUrlHome+'index.php?controller=accounts&account_type='+row.data.Key+'"><b>'+val+'</b> ('+lang['ext_view']+')</a>';
                        }
                        else
                        {
                            var out = val;
                        }

                        return out;
                    }
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 8,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
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
                        selectOnFocus:true,
                        listeners: {
                            beforeselect: function(combo, record){
                                var index  = combo.gridEditor.row;
                                var row    = accountTypesGrid.grid.store.data.items
                                && accountTypesGrid.grid.store.data.items[index]
                                    ? accountTypesGrid.grid.store.data.items[index]
                                    : null;
                                var atype  = row && row.data ? row.data : null;

                                // show popup for admin with notice about active accounts/listings uses this type
                                if (atype
                                    && record.data.field1 == 'approval'
                                    && row.data.Status != '{/literal}{$lang.approval}{literal}'
                                    && parseInt(atype.Accounts_count) > 0
                                    && atype.Key != 'visitor'
                                ) {
                                    Ext.MessageBox.confirm(
                                        '{/literal}{$lang.warning}{literal}',
                                        '{/literal}{$lang.confirm_deactivate_account_type}{literal}',
                                        function(btn){
                                            if (btn == 'yes') {
                                                $.getJSON(
                                                    rlConfig['ajax_url'],
                                                    {item: 'accountTypeDeactivation', key: atype.Key},
                                                    function(response) {
                                                        if (response && response.status && response.message) {
                                                            var message = response.message;
                                                            var type    = '';

                                                            if (response.status == 'OK') {
                                                                accountTypesGrid.reload();
                                                                type = 'notice';
                                                            } else {
                                                                type = 'error';
                                                            }

                                                            printMessage(type, message);
                                                        }
                                                    }
                                                );

                                            }
                                        }
                                    );

                                    return false;
                                }
                            }
                        }
                    })
                },{
                    header: lang['ext_actions'],
                    width: 90,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data, ext, row) {
                        var out = "<center>";
                        var splitter = false;

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            if ( row.data.Key != 'visitor' )
                            {
                                out += '<img onclick="flynax.extModal(this, \''+data+'\');" class="build" ext:qtip="'+lang['ext_build']+'" src="'+rlUrlHome+'img/blank.gif" />';
                            }
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&type="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 && row.data.Key != 'visitor' )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='";
                            out += rlUrlHome + "img/blank.gif' onclick='";

                            if (checkAbilityDisablingType(row.data.Key)) {
                                out += "xajax_preAccountTypeDelete(\"" + row.data.Key + "\") ";
                            } else {
                                out += "printMessage(\"error\", \"{/literal}{$lang.account_type_disabling_warning}{literal}\") ";
                            }

                            out += "' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplAccountTypesGrid'}{literal}

        accountTypesGrid.init();
        grid.push(accountTypesGrid.grid);

        // disallow to disable/remove last account type with enabled "Quick registration" option
        accountTypesGrid.grid.addListener('beforeedit', function(editEvent) {
            if (editEvent.field == 'Status' && editEvent.value == lang.active) {
                if (!checkAbilityDisablingType(editEvent.record.data.Key)) {
                    printMessage('error', '{/literal}{$lang.account_type_disabling_warning}{literal}');
                    return false;
                }
            }
        });

        /**
         * Checking value of option "Sign-Up" in other account types to diallow disable/remove current type
         *
         * @param {object} key - Key of current account type
         */
        var checkAbilityDisablingType = function(key){
            if (accountTypesGrid.store.data.items) {
                var count_active_types = 0;
                var quick_type_key     = '';

                for (var i = accountTypesGrid.store.data.items.length - 1; i >= 0; i--) {
                    if (accountTypesGrid.store.data.items[i].data.Quick_registration ==  '1') {
                        quick_type_key = accountTypesGrid.store.data.items[i].data.Key;
                        count_active_types++;
                    }
                }

                if (count_active_types == 1 && key == quick_type_key) {
                    return false;
                } else {
                    return true;
                }
            }
        }
    });
    {/literal}
    //]]>
    </script>
    <!-- account types grid end -->

{/if}

<!-- account types end tpl -->
