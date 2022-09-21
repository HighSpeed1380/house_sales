<!-- accounts tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplAccountsNavBar'}

    {if !$smarty.get.action}
        <a href="javascript:void(0)" onclick="show('search')" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>

        {if $aRights.$cKey.add}
            <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_account}</span><span class="right"></span></a>
        {/if}
    {/if}

    {if $smarty.get.action == 'view' && $seller_info && $aRights.$cKey.edit == 'edit'}
        <a href="{$rlBase}index.php?controller=accounts&amp;action=edit&amp;account={$seller_info.ID}" class="button_bar"><span class="left"></span><span class="center_edit">{$lang.edit_account}</span><span class="right"></span></a>
    {/if}

    {if ($smarty.get.action == 'view' || $smarty.get.action == 'edit') && $aRights.$cKey.delete == 'delete'}
        <a href="javascript:void(0)" onclick="xajax_prepareDeleting('{if $smarty.get.userid}{$smarty.get.userid}{else}{$smarty.get.account}{/if}')" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.delete_account}</span><span class="right"></span></a>
    {/if}

    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.accounts_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<!-- search -->
{if !$smarty.get.action}
    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
        <table>
        <tr>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.username}</td>
                    <td class="field">
                        <input type="text" id="username" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.first_name}</td>
                    <td>
                        <input type="text" id="first_name" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.last_name}</td>
                    <td class="field">
                        <input type="text" id="last_name" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.mail}</td>
                    <td class="field">
                        <input type="text" id="email" maxlength="60" />
                    </td>
                </tr>

                {rlHook name='apTplAccountsSearch1'}

                <tr>
                    <td></td>
                    <td class="field">
                        <input id="search_button" type="submit" value="{$lang.search}" />
                        <input type="button" value="{$lang.reset}" id="reset_filter_button" />

                        <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
                    </td>
                </tr>
                </table>
            </td>
            <td style="width: 50px;"></td>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.account_type}</td>
                    <td class="field">
                        <select id="account_type" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            {foreach from=$account_types item='type'}
                                <option value="{$type.Key}" {if $sPost.profile.type == $type.Key}selected="selected"{/if}>{$type.name}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.status}</td>
                    <td class="field">
                        <select id="search_status" style="width: 200px;">
                            <option value="">- {$lang.all} -</option>
                            {foreach from=$statuses item='user_status'}
                                <option value="{$user_status}" {if $user_status == $smarty.get.status}selected="selected"{/if}>{$lang.$user_status}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.join_date}</td>
                    <td class="field" style="white-space: nowrap;">
                        <input class="date-calendar"
                            type="text"
                            value="{$smarty.post.date_from}"
                            size="12"
                            maxlength="10"
                            id="date_from"
                            autocomplete="off" />
                        <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                        <input class="date-calendar"
                            type="text"
                            value="{$smarty.post.date_to}"
                            size="12"
                            maxlength="10"
                            id="date_to"
                            autocomplete="off" />
                    </td>
                </tr>

                {rlHook name='apTplAccountsSearch2'}

                </table>
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript">
    {literal}

    var sFields = new Array('username', 'first_name', 'last_name', 'email', 'account_type', 'search_status', 'date_from', 'date_to');
    var cookie_filters = new Array();

    $(document).ready(function(){
        $(function(){
            $('#date_from').datepicker({
                showOn: 'both',
                buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                buttonImageOnly: true,
                dateFormat     : 'yy-mm-dd',
                changeMonth    : true,
                changeYear     : true,
                yearRange      : '-100:+30'
            }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

            $('#date_to').datepicker({
                showOn: 'both',
                buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                buttonImageOnly: true,
                dateFormat     : 'yy-mm-dd',
                changeMonth    : true,
                changeYear     : true,
                yearRange      : '-100:+30'
            }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
        });

        if ( readCookie('accounts_sc') )
        {
            $('#search').show();
            cookie_filters = readCookie('accounts_sc').split(',');

            for (var i in cookie_filters)
            {
                if ( typeof(cookie_filters[i]) == 'string' )
                {
                    var item = cookie_filters[i].split('||');
                    $('#'+item[0]).selectOptions(item[1]);
                }
            }

            cookie_filters.push(new Array('search', 1));
        }

        $('#search_button').click(function(){
            var sValues = new Array();
            var filters = new Array();
            var save_cookies = new Array();

            for(var si = 0; si < sFields.length; si++)
            {
                sValues[si] = $('#'+sFields[si]).val();
                filters[si] = new Array(sFields[si], $('#'+sFields[si]).val());
                save_cookies[si] = sFields[si]+'||'+$('#'+sFields[si]).val();
            }

            // save search criteria
            createCookie('accounts_sc', save_cookies, 1);

            filters.push(new Array('search', 1));

            accountsGrid.filters = filters;
            accountsGrid.reload();
        });

        $('#reset_filter_button').click(function(){
            eraseCookie('accounts_sc');
            accountsGrid.reset();

            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
        });

        /* autocomplete js */
        $('#username').rlAutoComplete();
    });

    {/literal}

    {if $smarty.get.status}
        cookie_filters = new Array();
        cookie_filters[0] = new Array('search_status', '{$smarty.get.status}');
        cookie_filters.push(new Array('search', 1));
    {/if}

    {if $smarty.get.account_type}
        cookie_filters = new Array();
        cookie_filters[0] = new Array('account_type', '{$smarty.get.account_type}');
        cookie_filters.push(new Array('search', 1));
    {/if}

    {rlHook name='apTplAccountsSearchJS'}

    </script>
{/if}
<!-- search end -->

<!-- delete account block -->
<div id="delete_block" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.delete_account}
        <div id="delete_container">
            {$lang.detecting}
        </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    <script type="text/javascript">//<![CDATA[
    {if $config.trash}
        var delete_conform_phrase = "{$lang.notice_drop_empty_account}";
    {else}
        var delete_conform_phrase = "{$lang.notice_delete_empty_account}";
    {/if}

    {literal}

    function delete_chooser(method, id, username)
    {
        if (method == 'delete')
        {
            rlPrompt(delete_conform_phrase.replace('{username}', username), 'xajax_deleteAccount', id);
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
<!-- delete account block end -->

{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}
    {assign var='sPost' value=$smarty.post}
    {assign var='selected_atype' value=''}

    {if $account_types && $sPost.profile}
        {foreach from=$account_types item='a_type'}
            {if $sPost.profile.type == $a_type.ID || $sPost.profile.type == $a_type.Key}
                {assign var='selected_atype' value=$a_type.Key}
            {/if}
        {/foreach}
    {/if}

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.textareaCounter.js"></script>

    <!-- add/edit account -->
    <form name="account_reg_form"
          action="{$rlBaseC}action={$smarty.get.action}{if $smarty.get.action == 'edit'}&account={$smarty.get.account}{/if}"
          method="post"
          enctype="multipart/form-data">

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.profile_information}

    <input type="hidden" name="form_submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
    {/if}

    <table class="form">
    <tr>
        <td class="name">{$lang.account_type} <span class="red">*</span></td>
        <td class="field">
            <select name="profile[type]" id="type_selector">
                <option value="">{$lang.select}</option>
                {foreach from=$account_types item='type'}
                    <option value="{$type.ID}" {if $sPost.profile.type == $type.ID || $sPost.profile.type == $type.Key}selected="selected"{/if}>{$type.name}</option>
                {/foreach}
            </select>
            <span id="type_change_loading" class="loader"></span>

            <script type="text/javascript">
            var account_types = new Array();

            {foreach from=$account_types item='account_type'}
                account_types[{$account_type.ID}] = new Array();
                account_types[{$account_type.ID}]['Own_location'] = {if $account_type.Own_location}1{else}0{/if};
                account_types[{$account_type.ID}]['Key'] = '{$account_type.Key}';
            {/foreach}
            </script>

            {if $smarty.get.action == 'edit'}
                <script type="text/javascript">
                var account_id = {if $aInfo.ID}{$aInfo.ID}{else}false{/if};
                var account_type_id = {if $aInfo.Account_type_ID}{$aInfo.Account_type_ID}{else}false{/if};
                var change_type_notice = "{$lang.admin_change_account_type_notice}";
                {literal}

                $(document).ready(function(){
                    $('select#type_selector').change(function(){
                        var id = $(this).val();
                        var at_key = id ? account_types[id]['Key'] : '';

                        if ( id != '' )
                        {
                            rlConfirm( change_type_notice, "type_change", id, 'type_change_loading', false, 'type_revert');
                            {/literal}{if $config.membership_module}{literal}
                            // handle membership plans
                            handleMembershipPlans(account_types[id]['Key']);
                            {/literal}{/if}{literal}
                        }

                        agreementFieldsHandler(at_key);
                    });
                });

                var type_revert = function()
                {
                    $('#type_selector option[value='+ account_type_id +']').attr('selected', true);
                }

                var type_change = function(id)
                {
                    if ( account_types[id]['Own_location'] )
                    {
                        $('#personal_address_field').slideDown();
                    }
                    else
                    {
                        $('#personal_address_field').slideUp();
                    }
                    xajax_updateAccountFields(id, account_id);
                }

                {/literal}
                </script>
            {else}
                <script type="text/javascript">
                {literal}

                $(document).ready(
                    function (){
                        $('#type_selector').change(
                            function(){
                                var id     = $(this).val();
                                var at_key = id ? account_types[id]['Key'] : '';

                                if (id != '' && account_types[id]['Own_location']) {
                                    $('#personal_address_field').slideDown();
                                } else {
                                    $('#personal_address_field').slideUp();
                                }

                                // reload additional fields block
                                $('#reg_step2').fadeOut('slow', function(){
                                    $('#additional_fields').html('');
                                });

                                // show next button
                                $('#next1').slideDown('normal');

                                {/literal}{if $config.membership_module}{literal}
                                // handle membership plans
                                handleMembershipPlans(id ? account_types[id]['Key'] : '');
                                {/literal}{/if}{literal}

                                agreementFieldsHandler(at_key);
                            }
                        );
                    }
                );
                {/literal}
                </script>
            {/if}
        </td>
    </tr>
    </table>

    <script type="text/javascript">{literal}
    /**
     * Show/hide related agreement fields
     * @param  {sting} at_key - Key of selected account type
     */
    var agreementFieldsHandler = function(at_key) {
        var $agFields = $('tr.ag_fields');

        $agFields.find('input').attr('disabled', true);
        $agFields.addClass('hide');

        if (at_key) {
            $agFields.each(function(){
                var at_types = $(this).data('types');

                if (at_types.indexOf(at_key) != -1 || at_types == '') {
                    $(this).removeClass('hide');
                    $(this).find('input').removeAttr('disabled');
                }
            });
        }
    }
    {/literal}</script>

    {if $smarty.get.action == 'add'}
        <div id="personal_address_field"{if !$account_types[$sPost.profile.type].Own_location} class="hide"{/if}>
            <table class="form" style="margin: 5px 0;">
            <tr>
                <td valign="top" style="padding-top: 8px;" class="name">{$lang.personal_address} <span class="red">*</span></td>
                <td class="field">
                    {if $config.account_wildcard}
                        {$scheme}://<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />.{$domain|replace:'www.':''}
                    {else}
                        {$scheme}://{$domain}/{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR}{/if}<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />/
                    {/if}
                    <div class="notice_message">{$lang.latin_characters_only}</div>
                </td>
            </tr>
            </table>
        </div>
    {else}
        <div id="personal_address_field"{if !$account_types[$sPost.profile.type].Own_location} class="hide"{/if}>
            <table class="form" {if !$aInfo.Own_address}style="margin: 5px 0;"{/if}>
            <tr>
                <td valign="top" style="padding-top: 8px;" class="name">{$lang.personal_address} <span class="red">*</span></td>
                <td class="field">
                    {if $aInfo.Own_address}
                        <div id="current_location">
                            <a target="_blank" href="{$scheme}://{if $config.account_wildcard}{$aInfo.Own_address}.{$domain|replace:'www.':''}{else}{$domain}/{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR}{/if}{$aInfo.Own_address}{/if}/">
                                {$scheme}://{if $config.account_wildcard}{$aInfo.Own_address}.{$domain|replace:'www.':''}{else}{$domain}/{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR}{/if}{$aInfo.Own_address}{/if}/</a>
                            <img onclick="$('#current_location').hide();$('#edit_location').show();" class="edit middle" alt="" src="{$rlTplBase}img/blank.gif" />
                        </div>
                    {/if}

                    <div id="edit_location" {if $aInfo.Own_address}class="hide"{/if}>
                        {if $config.account_wildcard}
                            {$scheme}://<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" value="{if $smarty.post.profile.location}{$smarty.post.profile.location}{else}{$aInfo.Own_address}{/if}" />.{$domain|replace:'www.':''}
                        {else}
                            {$scheme}://{$domain}/{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR}{/if}<input type="text" style="width: 90px;" maxlength="30" name="profile[location]" value="{if $smarty.post.profile.location}{$smarty.post.profile.location}{else}{$aInfo.Own_address}{/if}" />/
                        {/if}
                        <div class="notice_message">{$lang.latin_characters_only}</div>
                    </div>
                </td>
            </tr>
            </table>
        </div>
    {/if}

    <!-- membership plans -->
    {if $config.membership_module}
    <fieldset class="light">
        <legend id="legend_plans" class="up" onclick="fieldset_action('plans');">{$lang.plan}</legend>
        {if !empty($plans)}
            <div id="plans"{if !$smarty.post.profile.type} class="hide"{/if}>
                {if $smarty.get.action == 'add'}
                <!-- undefine plan -->
                <div class="plan_item" plan-id="0" plan-available="">
                    <table class="sTable">
                        <tr>
                            <td align="center" style="width: 30px"><input accesskey="" style="margin: 0 10px 0 0;" id="plan_0" type="radio" name="profile[plan]" value="0" {if !$smarty.post.profile.plan}checked="checked"{/if} /></td>
                            <td>
                                <label for="plan_0" class="blue_11_normal">
                                    {$lang.select_plan_later}
                                </label>
                                <div class="desc"></div>
                            </td>
                        </tr>
                    </table>
                </div>
                <!-- end undefine plan -->
                {/if}
                {foreach from=$plans item='plan' name='fPlan'}
                    <div class="plan_item{if $smarty.get.action == 'edit' && $plan.ID != $smarty.post.profile.plan} hide{/if}" plan-id="{$plan.ID}" plan-available="{$plan.Allow_for}">
                        <table class="sTable">
                        <tr>
                            <td align="center" style="width: 30px"><input accesskey="{$plan.Cross}" style="margin: 0 10px 0 0;" id="plan_{$plan.ID}" type="radio" name="profile[plan]" value="{$plan.ID}" {if $plan.ID == $smarty.post.profile.plan}checked="checked"{/if} /></td>
                            <td>
                                <label for="plan_{$plan.ID}" class="blue_11_normal">
                                    {assign var='l_type' value=$plan.Type|cat:'_plan'}
                                    {$plan.name} - <b>{if $plan.Price > 0}{$config.system_currency}{$plan.Price}{else}{$lang.free}{/if}</b>
                                </label>
                                <div class="desc">{$plan.des}</div>
                            </td>
                        </tr>
                        </table>
                    </div>
                {/foreach}

                {if $smarty.get.action == 'edit' && ($plans|@count > 1 || !$smarty.post.profile.plan)}
                    <input id="manage_plans" type="button" value="{$lang.manage}" />
                {/if}
            </div>
            <div id="plans-notice" {if $smarty.post.profile.type} class="hide"{/if}>{$lang.need_select_account_type}</div>
        {else}
            <div id="plans-notice">
                {assign var='replace' value='<a href="'|cat:$add_plan_link|cat:'">$1</a>'}
                {$lang.not_membership_plans|regex_replace:'/\[(.*)\]/':$replace}
            </div>
        {/if}
        {if $smarty.get.action == 'edit'}
            <script type="text/javascript">
            {literal}
            var plans_expand = false;
            $(document).ready(function(){
                $('#manage_plans').click(function() {
                    if (plans_expand) {
                        plans_expand = false;
                        $('div#plans div.plan_item').each(function() {
                            var is_checked = $(this).find('input[type="radio"]').is(':checked');
                            if (!is_checked) {
                                $(this).fadeOut();
                            }
                        });
                        $(this).val('{/literal}{$lang.manage}{literal}');
                    } else {
                        plans_expand = true;
                        $(this).val('{/literal}{$lang.apply}{literal}');

                        var type_id = $('#type_selector option:selected').val();
                        handleMembershipPlans(type_id ? account_types[type_id]['Key'] : '');
                    }
                });
            });
            {/literal}
            </script>
        {/if}
    </fieldset>
    <script type="text/javascript">
    {literal}
    var plans_expand = false;
    $(document).ready(function(){
        {/literal}{if $smarty.get.action == 'add'}{literal}
        if ($('#type_selector option:selected').val() != '') {
            handleMembershipPlans($('#type_selector option:selected').val());
        }
        {/literal}{/if}{literal}
    });

    var handleMembershipPlans = function(type) {
        if (account_types[type]) {
            type = account_types[type]['Key'];
        }
        if (type) {
            $('#plans').removeClass('hide');
            $('#plans-notice').addClass('hide');
        } else {
            if (!$('#plans').hasClass('hide')) {
                $('#plans').addClass('hide');
            }
            $('#plans-notice').removeClass('hide');
        }
        var plan_count = 0;
        $('div#plans div.plan_item').each(function() {
            if ($(this).attr('plan-available').indexOf(type) >= 0 || $(this).attr('plan-available') == '') {
                $(this).fadeIn();
                plan_count++;
            } else {
                $(this).fadeOut();
                $(this).find('input[type="radio"]').prop('checked', false);
            }
        });
        if (plan_count == 0) {
            $('#plans-notice').removeClass('hide');
        }
    }
    {/literal}
    </script>
    {/if}
    <!-- end membership plans -->

    <table class="form">
    {if $config.account_login_mode == 'email'}
        <tr>
            <td class="name">{$lang.mail} <span class="red">*</span></td>
            <td class="field">
                <input type="text" name="profile[mail]" value="{$sPost.profile.mail}" style="width: 250px;" maxlength="50" />
            </td>
        </tr>
    {else}
        <tr>
            <td class="name">{$lang.username} <span class="red">*</span></td>
            <td class="field">
                <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="profile[username]" type="text" style="width: 150px;" value="{$sPost.profile.username}" maxlength="30" />
            </td>
        </tr>
    {/if}
    <tr>
        <td class="name">
            <div style="margin-left: 10px;">{if $smarty.get.action == 'edit'}{$lang.new_password}{else}{$lang.password}{/if} <span class="red">*</span></div>
        </td>
        <td class="field">
            <input type="password" name="profile[password]" value="{$sPost.profile.password}" style="width: 250px;" maxlength="50" />
        </td>
    </tr>
    <tr>
        <td class="name">
            <div style="margin-left: 10px;">{$lang.password_repeat} <span class="red">*</span></div>
        </td>
        <td class="field">
            <input type="password" name="profile[password_repeat]" value="{$sPost.profile.password_repeat}" style="width: 250px;" maxlength="50" />
        </td>
    </tr>
    {if $config.account_login_mode != 'email'}
        <tr>
            <td class="name">{$lang.mail} <span class="red">*</span></td>
            <td class="field">
                <input type="text" name="profile[mail]" value="{$sPost.profile.mail}" style="width: 250px;" maxlength="50" />
            </td>
        </tr>
    {/if}
    {if $allLangs|@count > 1}
    <tr>
        <td class="name">{$lang.profile_lang} </td>
        <td class="field">
            <select name="profile[lang]">
                {foreach from=$allLangs item="lang_item"}
                    <option {if $lang_item.Code == $sPost.profile.lang}selected="selected"{/if} value="{$lang_item.Code}">{$lang_item.name}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    {/if}
    <tr>
        <td></td>
        <td class="field" {if $smarty.get.action != 'add'}style="height: 36px;"{/if} valign="top">
            <label><input value="1" type="checkbox" {if $sPost.profile.display_email}checked="checked"{/if} name="profile[display_email]" /> {$lang.display_email}</label>
        </td>
    </tr>

    <!-- Agreement fields -->
    {foreach from=$agreement_fields item='ag_field'}
        <tr class="ag_fields {strip}
            {if !$selected_atype
                || ($ag_field.Values && $selected_atype && $ag_field.Values|strstr:$selected_atype == false)}
                hide
            {/if}{/strip}"
            data-types="{$ag_field.Values}">
            <td></td>
            <td class="field" valign="top">
                <label>
                    <input value="1"
                        type="checkbox"
                        name="profile[accept][{$ag_field.Key}]"
                        {if $sPost.profile.accept[$ag_field.Key]}checked="checked"{/if}
                        {if $smarty.get.action == 'edit'}disabled="disabled"{/if} />
                    &nbsp;{$lang.agree}

                    <a target="_blank" href="{pageUrl key=$ag_field.Default}">
                        {phrase key='pages+name+'|cat:$ag_field.Default}
                    </a>
                </label>
            </td>
        </tr>
    {/foreach}
    <!-- Agreement fields end -->

    {if $smarty.get.action == 'edit'}
        <tr>
            <td class="name">{$lang.preview_image}</td>
            <td class="field">
                {if $aInfo.Photo}
                    <div style="margin: 0 0 5px 0;" id="photo_object">
                        <img align="middle" title="{$lang.your_photo}" alt="{$lang.your_photo}" src="{$smarty.const.RL_FILES_URL}{$aInfo.Photo}" /><br />
                        <span onclick='rlConfirm( "{$lang.delete_confirm}", "xajax_delAccountFile", Array("\"Photo\"", {$smarty.get.account}, "\"photo_object\"" ), "photo_loading", "smarty" );' class="label" title="{$lang.delete}" style="text-transform: capitalize; text-decoration: underline;">
                            {$lang.delete}</span><span class="label"> | </span>
                        <span onclick="show('thumbnail');" class="label" title="{$lang.manage}" style="text-transform: capitalize; text-decoration: underline;">{$lang.manage}</span><br />
                        <span class="loading" id="photo_loading">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                    </div>
                {/if}
                <div id="thumbnail" {if !empty($aInfo.Photo)}class="hide"{/if}>
                    <input class="" type="file" name="thumbnail" size="1" />
                </div>
            </td>
        </tr>
    {/if}
    </table>

    {rlHook name='apTplAccountsForm'}

    {if $smarty.get.action == 'edit'}
    <table class="form">
    <tr>
        <td class="name">{$lang.status} <span class="red">*</span></td>
        <td class="field">
            <select name="profile[status]">
                <option value="active" {if $sPost.profile.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.profile.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                {if $sPost.profile.status == 'pending'}<option value="pending" selected="selected">{$lang.pending}</option>{/if}
                {if $sPost.profile.status == 'incomplete'}<option value="incomplete" selected="selected">{$lang.incomplete}</option>{/if}
                {if $sPost.profile.status == 'expired'}<option value="expired" selected="selected">{$lang.expired}</option>{/if}
            </select>
        </td>
    </tr>
    </table>
    {/if}

    <div id="next1" {if !empty($fields)}class="hide"{/if}>
    {if $smarty.get.action == 'add'}
        <table class="form">
        <tr>
            <td class="name no_divider"></td>
            <td class="field">
                <input type="button" onclick="xajax_getAccountFields($('#type_selector').val()); $('#step1_loading').fadeIn('normal');" value="{$lang.next}" />
                <span class="loader" id="step1_loading"></span>
            </td>
        </tr>
        </table>
        <script>
        {literal}

        $(function(){
            $('form[name=account_reg_form]').submit(function(e){
                if ($('#next1').is(':visible')) {
                    $('#next1 input[type=button]').trigger('click');

                    return false;
                } else {
                    return submitHandler();
                }
            });
        });

        {/literal}
        </script>
    {elseif $smarty.get.action == 'edit'}
        <table class="form">
        <tr>
            <td class="name no_divider"></td>
            <td class="field"><input type="submit" value="{$lang.save}" /></td>
        </tr>
        </table>
    {/if}
    </div>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    <div id="account_field_area" {if empty($fields)}class="hide"{/if}>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.account_information}

            <div id="additional_fields">
                {if !empty($fields)}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'account_field.tpl' fields=$fields}
                {/if}
            </div>

            <table class="form">
            <tr>
                <td class="name no_divider"></td>
                <td class="field"><input type="submit" value="{$lang.save}" /></td>
            </tr>
            </table>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    </form>

    <!-- qtips randerer -->
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.qtip.js"></script>
    <script type="text/javascript">
    {literal}
    $(document).ready(function(){
        $('.qtip').each(function(){
            $(this).qtip({
                content: $(this).attr('title'),
                show: 'mouseover',
                hide: 'mouseout',
                position: {
                    corner: {
                        target: 'topRight',
                        tooltip: 'bottomLeft'
                    }
                },
                style: {
                    width: 150,
                    background: '#8e8e8e',
                    color: 'white',
                    border: {
                        width: 7,
                        radius: 5,
                        color: '#8e8e8e'
                    },
                    tip: 'bottomLeft'
                }
            });
        }).attr('title', '');
    });
    {/literal}
    </script>
    <!-- qtips randerer end -->

    <!-- add/edit account end -->

{elseif $smarty.get.action == 'view' && (!empty($smarty.get.username) || !empty($smarty.get.userid)) && $seller_info}

    <ul class="tabs" style="margin-top: 15px;">
        {foreach from=$tabs item='tab' name='tabsF'}
        <li lang="{$tab.key}" {if $smarty.foreach.tabsF.first}class="active"{/if}>{$tab.name}</li>
        {/foreach}
    </ul>

    <div class="tab_area seller listing_details">
        <table class="sTable">
        <tr>
            <td valign="top" style="width: 170px;text-align: right;padding-right: 20px;">
                <a title="{$lang.visit_owner_page}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$seller_info.ID}">
                    <img style="{strip}
                        display:inline;
                        width: {if $seller_info.Thumb_width}{$seller_info.Thumb_width}{else}110{/if}px;{/strip}"
                        {if !empty($seller_info.Photo)}class="thumbnail"{/if}
                        alt="{$lang.seller_thumbnail}"
                        src="{if !empty($seller_info.Photo)}{$smarty.const.RL_FILES_URL}{$seller_info.Photo}{else}{$rlTplBase}img/no-account.png{/if}" />
                </a>

                <ul class="info">
                    {if $config.messages_module}<li><input id="contact_owner" type="button" value="{$lang.contact_owner}" /></li>{/if}
                    {if $seller_info.Own_page}
                        <li><a title="{$lang.other_owner_listings}" onclick="$('ul.tabs li[lang=listings]').trigger('click');" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$seller_info.ID}#listings">{$lang.account_listings}</a> <span class="counter">({$seller_info.Listings_count})</span></li>
                    {/if}
                </ul>
            </td>
            <td valign="top">
                <div class="username">{$seller_info.Full_name}</div>

                <table class="list" style="margin-bottom: 20px;">
                    <tr id="si_field_username">
                        <td class="name">{$lang.username}:</td>
                        <td class="value first">{$seller_info.Username}</td>
                    </tr>
                    <tr id="si_field_date">
                        <td class="name">{$lang.join_date}:</td>
                        <td class="value">{$seller_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                    </tr>
                    <tr id="si_field_email">
                        <td class="name">{$lang.mail}:</td>
                        <td class="value"><a href="mailto:{$seller_info.Mail}">{$seller_info.Mail}</a></td>
                    </tr>

                    {if $seller_info.Personal_address}
                        <tr id="si_field_personal_address">
                            <td class="name">{$lang.personal_address}:</td>
                            <td class="value">
                                <a target="_blank" href="{$seller_info.Personal_address}">
                                    {$seller_info.Personal_address}
                                </a>
                            </td>
                        </tr>
                    {/if}

                    {if $seller_info.Agency_Info}
                        <tr id="si_field_agency">
                            <td class="name">{$lang.agency}:</td>
                            <td class="value">
                                <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$seller_info.Agency_Info.ID}">
                                    {$seller_info.Agency_Info.Full_name}
                                </a>
                            </td>
                        </tr>
                    {/if}

                    {rlHook name='apTplAccountsUserInfoField'}
                </table>

                {if $seller_info.Fields}
                    <table class="list">
                    {foreach from=$seller_info.Fields item='item' name='sellerF'}
                        {if !empty($item.value)}
                        <tr id="si_field_{$item.Key}">
                            <td class="name">{$item.name}:</td>
                            <td class="value">{$item.value}</td>
                        </tr>
                        {/if}
                    {/foreach}
                    </table>
                {/if}

                {if $config.map_module && $location.direct}
                    <fieldset class="light" style="margin-top: 20px;">
                        <legend id="legend_group_map_system" class="up" onclick="fieldset_action('group_map_system');">{$lang.map}</legend>
                        <div id="group_map_system" class="tree">
                            <div id="map_container" style="height: 30vw;"></div>
                        </div>
                    </fieldset>

                    {mapsAPI}

                    <script>
                    {literal}

                    var isMap   = false;
                    var initMap = function(){
                        if (isMap) {
                            return;
                        }

                        flMap.init($('#map_container'), {
                            zoom: {/literal}{$config.map_default_zoom}{literal},
                            addresses: [{
                                latLng: '{/literal}{$location.direct}',
                                content: '{$location.show|escape:'quotes'}{literal}'
                            }]
                        });

                        isMap = true;
                    }

                    $(function(){
                        if (flynax.getHash() != 'listings') {
                            initMap();
                        }

                        $('ul.tabs li[lang=seller]').click(function(){
                            initMap();
                        });
                    });

                    {/literal}
                    </script>
                {/if}
            </td>
        </tr>
        </table>

        <script type="text/javascript">
        var owner_id = {if $seller_info.ID}{$seller_info.ID}{else}false{/if}
        {literal}

        $(document).ready(function(){
            $('#contact_owner').click(function(){
                rlPrompt('{/literal}{$lang.contact_owner}{literal}', 'xajax_contactOwner', owner_id, true);
            });
        });

        {/literal}
        </script>
    </div>

    <div class="tab_area listings listing_details hide">
        <script type="text/javascript">//<![CDATA[
        // collect plans
        var listing_plans = [
            {foreach from=$plans item='plan' name='plans_f'}
                ['{$plan.ID}', '{$plan.name}']{if !$smarty.foreach.plans_f.last},{/if}
            {/foreach}
        ];

        var ui = typeof( rl_ui ) != 'undefined' ? '&ui='+rl_ui : '';
        var ui_cat_id = typeof( cur_cat_id ) != 'undefined' ? '&cat_id='+cur_cat_id : '';

        //]]>
        </script>

        <!-- listings grid create -->
        <div id="grid"></div>
        <script type="text/javascript">//<![CDATA[
        var account_username = '{$seller_info.Username|escape:"quotes"}';
        var account_id       = {$seller_info.ID};
        var mass_actions     = [
            [lang['ext_activate'], 'activate'],
            [lang['ext_suspend'], 'approve'],
            [lang['ext_renew'], 'renew'],
            {if 'delete'|in_array:$aRights.listings}[lang['ext_delete'], 'delete'],{/if}
            [lang['ext_move'], 'move'],
            [lang['ext_make_featured'], 'featured'],
            [lang['ext_annul_featured'], 'annul_featured']
        ];

        {literal}

        var grid_subtract_width = 72;//because the grid placed in a custom area (div>div)
        var listingsGrid;
        $(document).ready(function(){
            if (flynax.getHash() === 'listings') {
                setTimeout(function() {
                    $('ul.tabs li[lang=listings]').trigger('click');
                }, 1);
            }

            listingsGrid = new gridObj({
                key: 'accountListings',
                id: 'grid',
                ajaxUrl: rlUrlHome + 'controllers/listings.inc.php?q=ext&f_Account='+ account_username,
                defaultSortField: 'Date',
                defaultSortType: 'DESC',
                remoteSortable: false,
                checkbox: true,
                actions: mass_actions,
                filtersPrefix: true,
                title: lang['ext_listings_manager'],
                expander: true,
                expanderTpl: '<div style="margin: 0 5px 5px 83px"> \
                    <table> \
                    <tr> \
                    <td>{thumbnail}</td> \
                    <td>{fields}</td> \
                    </tr> \
                    </table> \
                    <div> \
                ',
                affectedObjects: '#make_featured,#move_area',
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'title', mapping: 'title', type: 'string'},
                    {name: 'Username', mapping: 'Username', type: 'string'},
                    {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                    {name: 'Type', mapping: 'Type'},
                    {name: 'Type_key', mapping: 'Type_key'},
                    {name: 'Plan_name', mapping: 'Plan_name'},
                    {name: 'Plan_ID', mapping: 'Plan_name'},
                    {name: 'Plan_type', mapping: 'Plan_type'},
                    {name: 'Featured_ID', mapping: 'Featured_ID', type: 'int'},
                    {name: 'Plan_info', mapping: 'Plan_info'},
                    {name: 'Cat_ID', mapping: 'Cat_ID', type: 'int'},
                    {name: 'Cat_custom', mapping: 'Cat_custom', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Pay_date', mapping: 'Pay_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Expired_date', mapping: 'Expired_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Featured_expired_date', mapping: 'Featured_expired_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                    {name: 'fields', mapping: 'fields', type: 'string'},
                    {name: 'data', mapping: 'data', type: 'string'},
                    {name: 'Status_value', mapping: 'Status_value'},
                    {name: 'Allow_photo', mapping: 'Allow_photo', type: 'int'},
                    {name: 'Allow_video', mapping: 'Allow_video', type: 'int'}
                ],
                columns: [
                    {
                        header: lang['ext_id'],
                        dataIndex: 'ID',
                        width: 50,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    },{
                        header: lang['ext_title'],
                        dataIndex: 'title',
                        width: 23,
                        renderer: function(val, ext, row){
                            var out = '<a href="'+rlUrlHome+'index.php?controller=listings&action=view&id='+row.data.ID+'">'+val+'</a>';
                            return out;
                        }
                    },{
                        header: lang['ext_owner'],
                        dataIndex: 'Username',
                        width: 120,
                        fixed: true,
                        id: 'rlExt_item_bold',
                        renderer: function(username, ext, row){
                            return "<a target='_blank' ext:qtip='" + lang['ext_click_to_view_details']
                                + "' href='" + rlUrlHome
                                + "index.php?controller=accounts&action=view&userid=" + row.data.Account_ID
                                + "'>" + username + "</a>";
                        }
                    },{
                        header: `${lang.ext_type}/${lang.ext_category}`,
                        dataIndex: 'Type',
                        width: 14
                    },{
                        header: lang['ext_add_date'],
                        dataIndex: 'Date',
                        width: 10,
                        hidden: true,
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                    },{
                        header: lang['ext_payed'],
                        dataIndex: 'Pay_date',
                        width: 90,
                        fixed: true,
                        renderer: function(val){
                            if (!val) {
                                return '<span class="delete" ext:qtip="'+lang['ext_click_to_set_pay']+'">'+lang['ext_not_payed']+'</span>';
                            } else {
                                let date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                                return '<span class="build" ext:qtip="'+lang['ext_click_to_edit']+'">'+date+'</span>';
                            }
                        },
                        editor: new Ext.form.DateField({
                            format: 'Y-m-d H:i:s'
                        })
                    },{
                        header: '{/literal}{$lang.active_till}{literal}',
                        dataIndex: 'Expired_date',
                        width: 90,
                        fixed: true,
                        renderer: function(date, row, store) {
                            if (store.json.Pay_date === store.json.Expired_date) {
                                return '{/literal}{$lang.unlimited}{literal}';
                            } else {
                                return date
                                    ? '<span ext:qtip="' + lang.deny_change_ex_date + '">'
                                        + Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(date)
                                        + '</span>'
                                    : lang.ext_not_available;
                            }
                        }
                    },{
                        header: '{/literal}{$lang.featured_till}{literal}',
                        dataIndex: 'Featured_expired_date',
                        width: 90,
                        fixed: true,
                        renderer: function(date, row, store) {
                            if (store.json.Featured_date === store.json.Featured_expired_date) {
                                return '{/literal}{$lang.unlimited}{literal}';
                            } else {
                                return date
                                    ? '<span ext:qtip="' + lang.deny_change_ex_date + '">'
                                        + Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(date)
                                        + '</span>'
                                    : lang.ext_not_available;
                            }
                        }
                    },{
                        header: lang['ext_plan'],
                        dataIndex: 'Plan_ID',
                        width: 140,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: listing_plans,
                            mode: 'local',
                            triggerAction: 'all'
                        }),
                        renderer: function (val, obj, row){
                            if (row.data.Plan_type == 'account') {
                                var qtip = lang['ext_uneditable'];
                            } else {
                                var qtip = lang['ext_click_to_edit'];
                            }
                            if (val != '') {
                                var f_class = row.data.Featured_ID > 0 ? ' featured' : '';
                                return '<img class="info'+f_class+'" ext:qtip="'+row.data.Plan_info+'" alt="" src="'+rlUrlHome+'img/blank.gif" />&nbsp;&nbsp;<span ext:qtip="'+qtip+'">'+val+'</span>';
                            } else {
                                return '<span class="delete" ext:qtip="'+qtip+'" style="margin-left: 21px;">'+lang['ext_no_plan_set']+'</span>';
                            }
                        }
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 100,
                        fixed: true,
                        editor: rights[cKey].indexOf('edit') >= 0 ? new Ext.form.ComboBox({
                            store: [
                                ['active', lang.active],
                                ['approval', lang.approval]
                            ],
                            mode: 'local',
                            typeAhead: true,
                            triggerAction: 'all',
                            selectOnFocus: true
                        }) : false
                    },{
                        header: lang['ext_actions'],
                        width: 120,
                        fixed: true,
                        dataIndex: 'data',
                        sortable: false,
                        resizeable: false,
                        renderer: function(id, obj, row){
                            var out = "<div style='text-align: right'>";
                            var splitter = false;

                            if ( cKey == 'browse' )
                            {
                                cKey = 'listings';
                            }

                            if ( rights[cKey].indexOf('edit') >= 0 )
                            {
                                if ( row.data.Allow_photo )
                                {
                                    out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=photos&id="+id+"'><img class='photo' ext:qtip='"+lang['ext_manage_photo']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                                }
                                if ( row.data.Allow_video )
                                {
                                    out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=video&id="+id+"'><img class='video' ext:qtip='"+lang['ext_manage_video']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                                }
                            }
                            out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=view&id="+id+"'><img class='view' ext:qtip='"+lang['ext_view_details']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            if ( rights[cKey].indexOf('edit') >= 0 )
                            {
                                out += "<a href=\""+rlUrlHome+"index.php?controller=listings&action=edit&id="+id+ui+ui_cat_id+"\"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            }
                            if ( rights[cKey].indexOf('delete') >= 0 )
                            {
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlPrompt( \""+lang['ext_notice_'+delete_mod]+"\",  \"xajax_deleteListing\", \""+id+"\" )' />";
                            }
                            out += "</div>";

                            return out;
                        }
                    }
                ]
            });

            var grid_exist = false;

            $('ul.tabs li[lang=listings]').click(function() {
                if (!grid_exist) {
                    {/literal}{rlHook name='apTplAccountsListingsGrid'}{literal}

                    $('div.listings').show();

                    listingsGrid.init();
                    grid.push(listingsGrid.grid);
                    grid_exist = true;

                    // actions listener
                    listingsGrid.actionButton.addListener('click', function() {
                        var sel_obj = listingsGrid.checkboxColumn.getSelections();
                        var action = listingsGrid.actionsDropDown.getValue();

                        if (!action) {
                            return false;
                        }

                        for (var i = 0; i < sel_obj.length; i++) {
                            listingsGrid.ids += sel_obj[i].id;
                            if (sel_obj.length != i + 1) {
                                listingsGrid.ids += '|';
                            }
                        }

                        if (action == 'delete') {
                            Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn) {
                                if (btn == 'yes') {
                                    xajax_massActions(listingsGrid.ids, action);
                                    listingsGrid.store.reload();
                                }
                            });
                        } else if (action == 'featured') {
                            $('#make_featured').fadeIn('slow');
                            return false;
                        } else if (action == 'annul_featured') {
                            $('#mass_areas div.scroll').fadeOut('fast');
                            Ext.MessageBox.confirm('Confirm', lang['ext_annul_featued_notice'], function(btn) {
                                if (btn == 'yes') {
                                    xajax_annulFeatured(listingsGrid.ids);
                                }
                            });

                            return false;
                        } else if (action == 'move') {
                            $('#mass_areas div.scroll').fadeOut('fast');
                            $('#move_area').fadeIn('slow');
                            return false;
                        } else {
                            $('#make_featured,#move_area').fadeOut('fast');
                            xajax_massActions( listingsGrid.ids, action );
                            listingsGrid.store.reload();
                        }

                        listingsGrid.checkboxColumn.clearSelections();
                        listingsGrid.actionsDropDown.setVisible(false);
                        listingsGrid.actionButton.setVisible(false);
                    });

                    listingsGrid.grid.addListener('beforeedit', function(editEvent) {
                        if (editEvent.field == 'Plan_ID' && editEvent.record.data.Plan_type == 'account') {
                            return false;
                        }
                    });

                    listingsGrid.grid.addListener('afteredit', function(editEvent) {
                        if (editEvent.field == 'Plan_ID') {
                            listingsGrid.reload();
                        }
                    });
                }
            });
        });
        {/literal}
        //]]>
        </script>
    </div>

    {if isset($seller_info.Agents_count)}
        <div class="tab_area agents listing_details hide">
            <!-- agents grid -->
            <div id="agents-grid"></div>
            <script>{literal}
                $(function () {
                    if (flynax.getHash() === 'agents') {
                        setTimeout(function () {
                            $('ul.tabs li[lang=agents]').trigger('click');
                        }, 1);
                    }

                    let agentsGrid = new gridObj({
                        key    : 'agents',
                        id     : 'agents-grid',
                        ajaxUrl: rlUrlHome
                                    + 'controllers/accounts.inc.php?q=ext&agency='
                                    + '{/literal}{$seller_info.ID}{literal}'
                                    + '&search=1',
                        defaultSortField: 'Date',
                        title           : lang.ext_accounts_manager,
                        expander        : true,
                        expanderTpl     : '<div style="margin: 0 5px 5px 83px">'
                            + '<table><tr><td>{thumbnail}</td><td>{fields}</td></tr></table><div>',
                        remoteSortable: true,
                        fields: [
                            {name: 'Username', mapping: 'Username', type: 'string'},
                            {name: 'Name', mapping: 'Name', type: 'string'},
                            {name: 'Mail', mapping: 'Mail', type: 'string'},
                            {name: 'Status', mapping: 'Status'},
                            {name: 'Type', mapping: 'Type', type: 'string'},
                            {name: 'Type_name', mapping: 'Type_name', type: 'string'},
                            {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                            {name: 'fields', mapping: 'fields', type: 'string'},
                            {name: 'ID', mapping: 'ID', type: 'int'},
                            {name: 'Plan_ID', mapping: 'Plan_ID', type: 'int'},
                            {name: 'Plan_name', mapping: 'Plan_name', type: 'string'},
                            {name: 'Plan_info', mapping: 'Plan_info'},
                            {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
                        ],
                        columns: [{
                                header: lang.ext_id,
                                dataIndex: 'ID',
                                width: 40,
                                fixed: true,
                                id: 'rlExt_black_bold'
                            },{
                                header: lang.ext_username,
                                dataIndex: 'Username',
                                width: 13
                            },{
                                header: lang.ext_name,
                                dataIndex: 'Name',
                                width: 15,
                                id: 'rlExt_item_bold',
                                renderer: function(val, ext, row){
                                    return '<a href="' + rlUrlHome + 'index.php?controller=accounts&action=view&userid='
                                        + row.data.ID
                                        + '">'+ val +'</a>';
                                }
                            },{/literal}{if $config.membership_module}{literal}{
                                header: lang.ext_plan,
                                dataIndex: 'Plan_name',
                                width: 15,
                                renderer: function (val, obj, row){
                                    if (val != '') {
                                        return '<img class="info" ext:qtip="' + row.data.Plan_info
                                            + '" alt="" src="' + rlUrlHome
                                            + 'img/blank.gif" />&nbsp;&nbsp;<span>' + val + '</span>';
                                    } else {
                                        return '<span class="delete" style="margin-left: 25px;">'
                                            + lang.ext_no_plan_set + '</span>';
                                    }
                                }
                            },{/literal}{/if}{literal}{
                                header: lang.ext_email,
                                dataIndex: 'Mail',
                                width: 22,
                                id: 'rlExt_item',
                                editor: new Ext.form.TextField({allowBlank: false, inputType: 'mail'}),
                                renderer: function(val) {
                                    return '<span ext:qtip="' + lang.ext_click_to_edit + '">' + val + '</span>';
                                }
                            },{
                                header: lang.ext_type,
                                dataIndex: 'Type_name',
                                width: 13
                            },{
                                header: lang.ext_join_date,
                                dataIndex: 'Date',
                                width: 13,
                                renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                            },{
                                header: lang.ext_status,
                                dataIndex: 'Status',
                                width: 100,
                                fixed: true,
                                editor: new Ext.form.ComboBox({
                                    store: [['active', lang.active], ['approval', lang.approval]],
                                    displayField: 'value',
                                    valueField: 'key',
                                    typeAhead: true,
                                    mode: 'local',
                                    triggerAction: 'all',
                                    selectOnFocus: true
                                })
                            },{
                                header: lang.ext_actions,
                                width: 100,
                                fixed: true,
                                dataIndex: 'ID',
                                sortable: false,
                                renderer: function(data, ext, row) {
                                    let out = "<center>"
                                        + "<a href='" + rlUrlHome + "index.php?controller="
                                        + controller + "&action=view&userid=" + data
                                        + "'><img class='view' ext:qtip='" + lang.ext_view
                                        + "' src='"+rlUrlHome+"img/blank.gif' /></a>";

                                    if (rights[cKey].indexOf('edit') >= 0) {
                                        out += "<a href='" + rlUrlHome + "index.php?controller="
                                            + controller+"&action=edit&account=" + data
                                            + "'><img class='edit' ext:qtip='" + lang.ext_edit
                                            + "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                                    }
                                    if (rights[cKey].indexOf('delete') >= 0) {
                                        out += "<img class='remove' ext:qtip='" + lang.ext_delete + "' src='"
                                            + rlUrlHome + "img/blank.gif' onclick='xajax_prepareDeleting("
                                            + row.data.ID + ")' />";
                                    }
                                    out += "</center>";

                                    return out;
                                }
                            }
                        ]
                    });

                    let agentsGridInit = false;
                    $('ul.tabs li[lang=agents]').click(function () {
                        if (agentsGridInit) {
                            return;
                        }

                        {/literal}{rlHook name='apTplAgentsGrid'}{literal}

                        $('div.agents').show();
                        agentsGrid.init();
                        grid.push(agentsGrid.grid);
                        agentsGridInit = true;

                        agentsGrid.grid.on('validateedit', function(e) {
                            if (e.field === 'Mail' && e.originalValue != e.value) {
                                let data = {'lookIn': 'accounts', 'byField': 'Mail', 'value': e.value};

                                flynax.sendAjaxRequest('isUserExist', data, function() {
                                    Ext.Ajax.request({
                                        url   : agentsGrid.ajaxUrl,
                                        method: agentsGrid.ajaxMethod,
                                        params: {'action': 'update', 'id': e.record.id, 'field': 'Mail', 'value': e.value}
                                    });

                                    agentsGrid.reload();
                                });

                                return false;
                            }
                        });
                    });
                });
            {/literal}</script>
            <!-- agents grid end -->
        </div>

        <div class="tab_area invites listing_details hide">
            <!-- invites grid -->
            <div id="invites-grid"></div>
            <script>{literal}
                $(function () {
                    if (flynax.getHash() === 'invites') {
                        setTimeout(function () {
                            $('ul.tabs li[lang=invites]').trigger('click');
                        }, 1);
                    }

                    let invitesGrid = new gridObj({
                        key    : 'invites',
                        id     : 'invites-grid',
                        ajaxUrl: rlUrlHome
                                    + 'controllers/accounts.inc.php?q=ext&agency='
                                    + '{/literal}{$seller_info.ID}{literal}'
                                    + '&search=1&invites=1',
                        title: '{/literal}{$lang.invites}{literal}',
                        fields: [
                            {name: 'ID', mapping: 'ID', type: 'int'},
                            {name: 'Name_Email', mapping: 'Agent_Email', type: 'string'},
                            {name: 'Agent', mapping: 'Agent', type: 'array'},
                            {name: 'Created_Date', mapping: 'Created_Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                            {name: 'Accepted_Date', mapping: 'Accepted_Declined_Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                            {name: 'Status', mapping: 'Status', type: 'string'},
                        ],
                        columns: [{
                                header: lang.ext_id,
                                dataIndex: 'ID',
                                width: 40,
                                fixed: true,
                                id: 'rlExt_black_bold'
                            },{
                                header: '{/literal}{$lang.name}/{$lang.mail}{literal}',
                                dataIndex: 'Name_Email',
                                width: 13,
                                renderer: function (email, row, store) {
                                    return store.data.Agent && store.data.Agent.Full_name
                                        ? (`<a href="${rlUrlController}`
                                            + `&action=view&userid=${store.data.Agent.ID}">`
                                            + `${store.data.Agent.Full_name}</a>`
                                        )
                                        : email;
                                }
                            },{
                                header: lang.ext_join_date,
                                dataIndex: 'Created_Date',
                                width: 13,
                                renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                            },{
                                header: lang.accepted_date + '/' + lang.declined_date,
                                dataIndex: 'Accepted_Declined_Date',
                                width: 13,
                                renderer: function(date, row, store) {
                                    if (store.json.Status === 'accepted' && store.json.Accepted_Date) {
                                        return Ext.util.Format.dateRenderer(
                                            rlDateFormat.replace(/%/g, '').replace('b', 'M')
                                        )(store.json.Accepted_Date);
                                    } else if (store.json.Status === 'declined' && store.json.Declined_Date) {
                                        return Ext.util.Format.dateRenderer(
                                            rlDateFormat.replace(/%/g, '').replace('b', 'M')
                                        )(store.json.Declined_Date);
                                    } else {
                                        return lang.ext_not_available;
                                    }
                                },
                            },{
                                header: lang.ext_status,
                                dataIndex: 'Status',
                                width: 100,
                                fixed: true,
                                renderer: function(status, param1) {
                                    if (status === 'accepted') {
                                        param1.style += 'background: #d2e798;';
                                    } else if (status === 'declined') {
                                        param1.style += 'background: #fbc4c4;';
                                    } else if (status === 'pending') {
                                        param1.style += 'background: #c0ecee;';
                                    }

                                    return lang[status];
                                },
                            },
                        ]
                    });

                    let invitesGridInit = false;
                    $('ul.tabs li[lang=invites]').click(function () {
                        if (invitesGridInit) {
                            return;
                        }

                        {/literal}{rlHook name='apTplInvitesGrid'}{literal}

                        $('div.invites').show();
                        invitesGrid.init();
                        grid.push(invitesGrid.grid);
                        invitesGridInit = true;
                    });
                });
            {/literal}</script>
            <!-- agents grid end -->
        </div>
    {/if}

    {rlHook name='apTplAccountsTabsArea'}

    <div id="mass_areas">

        <!-- make featured -->
        <div id="make_featured" style="margin-top: 10px;" class="hide scroll">

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.make_featured}
            <table class="form">
            <tr>
                <td class="name w130"><span class="red">*</span>{$lang.plan}</td>
                <td class="field">
                    <select id="featured_plan">
                        <option value="0">{$lang.select}</option>
                        {foreach from=$featured_plans item='featured_plan'}
                            <option value="{$featured_plan.ID}">{$featured_plan.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="w130"></td>
                <td class="field">
                    <input type="button" onclick="xajax_makeFeatured(listingsGrid.ids, $('#featured_plan').val());" value="{$lang.save}" />
                    <a class="cancel" href="javascript:void(0)" onclick="$('#make_featured').fadeOut();">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        </div>
        <!-- make featured end -->

        <!-- move listing block -->
        <div id="move_area" style="margin-top: 10px;" class="hide scroll">
            <script type="text/javascript">
            var move_category_id = 0;

            {literal}
            function moveOnCategorySelect(id, name) {
                move_category_id = id;
            }

            function moveOnButtonClick() {
                if (listingsGrid.ids.length > 0 && move_category_id > 0) {
                    $('div.namespace-move a.button').text(lang['loading']);
                    xajax_moveListing(listingsGrid.ids, move_category_id);
                }
            }
            {/literal}
            </script>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.move_listings}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' namespace='move' button=$lang.move}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
        <!-- move listing block end -->

    </div>

{else}
    <!-- accounts grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var mass_actions = [
        [lang['ext_activate'], 'activate'],
        [lang['ext_suspend'], 'approve'],
        ["{$lang.resend_activation_link}", 'resend_link']
    ];
    var listingGroupsGrid;

    {literal}
    $(document).ready(function(){
        accountsGrid = new gridObj({
            key: 'accounts',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/accounts.inc.php?q=ext',
            defaultSortField: 'Date',
            title: lang['ext_accounts_manager'],
            checkbox: true,
            actions: mass_actions,
            expander: true,
            expanderTpl: '<div style="margin: 0 5px 5px 83px"> \
                <table> \
                <tr> \
                <td>{thumbnail}</td> \
                <td>{fields}</td> \
                </tr> \
                </table> \
                <div> \
            ',
            remoteSortable: true,
            filters: cookie_filters,
            fields: [
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Name', mapping: 'Name', type: 'string'},
                {name: 'Mail', mapping: 'Mail', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Type', mapping: 'Type', type: 'string'},
                {name: 'Type_name', mapping: 'Type_name', type: 'string'},
                {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                {name: 'fields', mapping: 'fields', type: 'string'},
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Plan_ID', mapping: 'Plan_ID', type: 'int'},
                {name: 'Plan_name', mapping: 'Plan_name', type: 'string'},
                {name: 'Plan_info', mapping: 'Plan_info'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_username'],
                    dataIndex: 'Username',
                    width: 13
                },{
                    header: lang['ext_name'],
                    dataIndex: 'Name',
                    width: 15,
                    id: 'rlExt_item_bold',
                    renderer: function(val, ext, row){
                        return '<a href="'+rlUrlHome+'index.php?controller=accounts&action=view&userid='+row.data.ID+'">'+ val +'</a>';
                    }
                },{/literal}{if $config.membership_module}{literal}{
                    header: lang['ext_plan'],
                    dataIndex: 'Plan_name',
                    width: 15,
                    renderer: function (val, obj, row){
                        if (val != '')
                        {
                            return '<img class="info" ext:qtip="'+row.data.Plan_info+'" alt="" src="'+rlUrlHome+'img/blank.gif" />&nbsp;&nbsp;<span>'+val+'</span>';
                        }
                        else
                        {
                            return '<span class="delete" style="margin-left: 25px;">'+lang['ext_no_plan_set']+'</span>';
                        }
                    }
                },{/literal}{/if}{literal}{
                    header: lang['ext_email'],
                    dataIndex: 'Mail',
                    width: 22,
                    id: 'rlExt_item',
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        inputType: 'mail'
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type_name',
                    width: 13/*,
                    renderer: function(val, obj, row){
                        var out = '<a target="_blank" ext:qtip="'+lang['ext_click_to_edit']+'" href="'+rlUrlHome+'index.php?controller=account_types&action=edit&type='+row.data.Type+'">'+val+'</a>';
                        return out;
                    }*/
                },{
                    header: lang['ext_join_date'],
                    dataIndex: 'Date',
                    width: 13,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
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
                    width: 100,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data, ext, row) {
                        var out = "<center>";
                        var splitter = false;

                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=view&userid="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&account="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
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

        {/literal}{rlHook name='apTplAccountsGrid'}{literal}

        accountsGrid.init();
        grid.push(accountsGrid.grid);

        accountsGrid.grid.on('validateedit', function(e) {
            if (e.field == 'Mail' && e.originalValue !== e.value) {
                var data = {
                    'lookIn': 'accounts',
                    'byField': 'Mail',
                    'value': e.value
                };

                flynax.sendAjaxRequest('isUserExist', data, function() {
                    Ext.Ajax.request({
                        url: accountsGrid.ajaxUrl,
                        method: accountsGrid.ajaxMethod,
                        params: {
                            'action': 'update',
                            'id': e.record.id,
                            'field': 'Mail',
                            'value': e.value
                        }
                    });

                    accountsGrid.reload();
                });

                return false;
            }
        });

        // actions listener
        accountsGrid.actionButton.addListener('click', function()
        {
            var sel_obj = accountsGrid.checkboxColumn.getSelections();
            var action = accountsGrid.actionsDropDown.getValue();

            if (!action)
            {
                return false;
            }

            for( var i = 0; i < sel_obj.length; i++ )
            {
                accountsGrid.ids += sel_obj[i].id;
                if ( sel_obj.length != i+1 )
                {
                    accountsGrid.ids += '|';
                }
            }

            $('#make_featured,#move_area').fadeOut('fast');
            xajax_massActions( accountsGrid.ids, action );
            accountsGrid.store.reload();

            accountsGrid.checkboxColumn.clearSelections();
            accountsGrid.actionsDropDown.setVisible(false);
            accountsGrid.actionButton.setVisible(false);
        });

    });
    {/literal}
    //]]>
    </script>
    <!-- accounts grid end -->

    {rlHook name='apTplAccountsBottom'}

{/if}

<!-- accounts tpl end -->
