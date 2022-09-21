<!-- pages tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplPagesNavBar'}

    {if !isset($smarty.get.action)}
        {strip}<a id="search_button_bar" href="#" class="button_bar">
            <span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span>
        </a>{/strip}
    {/if}

    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_page}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.pages_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<div id="action_blocks">
    {if !isset($smarty.get.action)}
        <!-- search -->
        <div id="search" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}

            <form method="post" id="search_form" action="">
                <table class="form">
                <tr>
                    <td class="name">{$lang.name}</td>
                    <td class="field">
                        <input type="text" id="search_name" />
                    </td>
                </tr>

                <tr>
                    <td class="name">{$lang.page_type}</td>
                    <td class="field">
                        <select id="search_page_type" class="w200">
                            <option value="">- {$lang.all} -</option>

                            {foreach from=$l_page_types item='pType' key='pKey'}
                                <option value="{$pKey}">{$pType}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>

                {rlHook name='apTplPagesSearch'}

                <tr>
                    <td class="name">{$lang.status}</td>
                    <td class="field">
                        <select id="search_status" class="w200">
                            <option value="">- {$lang.all} -</option>
                            <option value="active">{$lang.active}</option>
                            <option value="approval">{$lang.approval}</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td></td>
                    <td class="field">
                        <input type="submit" class="button" value="{$lang.search}" id="search_button" />
                        <input type="button" class="button" value="{$lang.reset}" id="reset_search_button" />
                        <input type="reset" id="reset_search_input" class="hide">

                        <a id="cancel_button" class="cancel" href="#">{$lang.cancel}</a>
                    </td>
                </tr>
                </table>
            </form>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>

        <script type="text/javascript">{literal}
        var search         =  new Array();
        var cookie_filters = readCookie('pages_sc') ? readCookie('pages_sc').split(',') : new Array();

        $(document).ready(function(){
            // fill search form
            if (cookie_filters.length > 0) {
                show('search');

                for (var i in cookie_filters) {
                    if (typeof(cookie_filters[i]) == 'string') {
                        var item = cookie_filters[i].split('||');

                        if (item[0] != 'undefined' && item[0] != '') {
                            $('#search_' + item[0].toLowerCase()).val(item[1]);
                        }
                    }
                }
            }

            // search pages by selected criteria
            $('#search_form').submit(function(event){
                event.preventDefault();

                search = new Array();
                search.push(new Array('action', 'search'));
                search.push(new Array('Name', $('#search_name').val()));
                search.push(new Array('Page_type', $('#search_page_type').val()));
                search.push(new Array('Status', $('#search_status').val()));

                {/literal}{rlHook name='apTplPagesSearchJS'}{literal}

                // save search criteria
                var save_search = new Array();
                for (var i in search) {
                    if (typeof(search[i][1]) != 'undefined' && search[i][1] != '') {
                        save_search.push(search[i][0] + '||' + search[i][1]);
                    }
                }
                createCookie('pages_sc', save_search, 1);

                pagesGrid.filters = search;
                pagesGrid.reload();
            });

            // show search form
            $('#search_button_bar').on('click', function(){
                if ($('#search').is(':visible')) {
                    eraseCookie('pages_sc');
                }

                show('search', '#action_blocks div');
            });

            // close search form
            $('#cancel_button').on('click', function(){
                show('search');
                eraseCookie('pages_sc');
            });

            // reset search form and reload grid
            $('#reset_search_button').on('click', function(){
                eraseCookie('pages_sc');
                pagesGrid.reset();
                $('#reset_search_input').trigger('click');
            });
        });
        {/literal}</script>
        <!-- search end -->
    {/if}
</div>

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add/edit new page -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()"
        action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit{/if}&page={$smarty.get.page}"
        method="post">
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
        <td class="name">
            {$lang.h1_heading}
        </td>
        <td class="field">
            <div{if !$config.home_page_h1 && $smarty.get.page == 'home'} id="h1_hidden_content" class="hide"{/if}>
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="h1_heading[{$language.Code}]" value="{$sPost.h1_heading[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.h1_heading} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </div>

            {if !$config.home_page_h1 && $smarty.get.page == 'home'}
                <div id="h1_hint">
                    <div style="padding: 2px 0 4px;">
                        <a href="javascript://" class="button" style="margin: 0;text-transform: capitalize;">{$lang.enabled}</a> <span class="field_description">{$lang.h1_heading_restriction_note}</span>
                    </div>
                </div>

                <script>
                {literal}

                $(document).ready(function(){
                    $('#h1_hint a.button').click(function(){
                        $.post(rlConfig['ajax_url'], {item: 'configUpdate', key: 'home_page_h1', value: 1}, function(response){
                            if (response.status == 'OK') {
                                $('#h1_hint').slideUp('normal', function(){
                                    $('#h1_hidden_content').slideDown();
                                });
                            } else {
                                printMessage('error', lang['system_error']);
                            }
                        }, 'json');
                    });
                });

                {/literal}
                </script>
            {/if}
        </td>
    </tr>

    <tr>
        <td class="name">
            <span class="red">*</span>{$lang.title}
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
                <input type="text" name="title[{$language.Code}]" value="{$sPost.title[$language.Code]}" maxlength="350" class="w350" />
                {if $allLangs|@count > 1}
                        <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                    </div>
                {/if}
            {/foreach}
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.meta_description}</td>
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
                <textarea cols="" rows="" name="meta_description[{$language.Code}]">{$sPost.meta_description[$language.Code]}</textarea>
                {if $allLangs|@count > 1}</div>{/if}
            {/foreach}
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.meta_keywords}</td>
        <td>
            {if $allLangs|@count > 1}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>
            {/if}

            {foreach from=$allLangs item='language' name='langF'}
                {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                <textarea cols="" rows="" name="meta_keywords[{$language.Code}]">{$sPost.meta_keywords[$language.Code]}</textarea>
                {if $allLangs|@count > 1}</div>{/if}
            {/foreach}
        </td>
    </tr>

    <tr {if $sPost.key == 'home' && $smarty.get.action == 'edit'}class="hide"{/if}>
        <td class="name"><span class="red">*</span>{$lang.page_type}</td>
        <td class="field">
            <select name="page_type" id="page_types">
            <option value="">{$lang.select}</option>
            {foreach from=$l_page_types item='pType' key='pKey'}
                <option value="{$pKey}" {if $sPost.page_type== $pKey}selected="selected"{/if}>{$pType}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    </table>

    <div id="ptypes">
        <div class="hide" id="ptype_static">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.page_content}</td>
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
                        {assign var='dCode' value='content_'|cat:$language.Code}
                        {fckEditor name='content_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                        {if $allLangs|@count > 1}</div>{/if}
                    {/foreach}
                </td>
            </tr>
            </table>
        </div>

        <div class="hide" id="ptype_system">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.page_controller}</td>
                <td class="field">
                    <input name="controller" type="text" style="width: 150px;" value="{$sPost.controller}" maxlength="25" />
                    {if $smarty.get.action == 'edit'}
                        <span class="field_description">{$lang.change_controller_notice}</span>
                    {/if}
                </td>
            </tr>
            </table>
        </div>

        <div class="hide" id="ptype_external">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.external_url}</td>
                <td class="field">
                    <input name="external_url" type="text" style="width: 250px;" value="{if $sPost.external_url}{$sPost.external_url}{else}http://{/if}" />
                </td>
            </tr>
            </table>
        </div>
    </div>

    <div id="page_url">
        <table class="form">
        <tr {if $sPost.key == 'home' && $smarty.get.action == 'edit'}class="hide"{/if}>
            <td class="name"><span class="red">*</span>{$lang.page_url}</td>
            <td class="field">
                <table>
                <tr>
                    {if $allLangs|@count > 1
                        && $config.multilingual_paths
                        && !in_array($sPost.key, $nonMultilingualPages)
                    }
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}

                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1
                            && $config.multilingual_paths
                            && !in_array($sPost.key, $nonMultilingualPages)
                        }
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}

                        <span style="padding: 0 5px 0 0;" class="field_description_noicon">
                            {$smarty.const.RL_URL_HOME}{if $language.Code !== $config.lang}{$language.Code}/{/if}
                        </span>
                        <input name="path[{$language.Code}]" type="text" value="{$sPost.path[$language.Code]}" maxlength="40" />
                        <span class="field_description_noicon">.html</span>

                        {if $config.multilingual_paths && in_array($sPost.key, $nonMultilingualPages)}
                            <span class="field_description">{$lang.multilingual_path_denied}</span>
                        {/if}

                        {if $allLangs|@count > 1
                            && $config.multilingual_paths
                            && !in_array($sPost.key, $nonMultilingualPages)
                        }
                            </div>
                        {/if}

                        {if !$config.multilingual_paths || in_array($sPost.key, $nonMultilingualPages)}
                            {break}
                        {/if}
                    {/foreach}
                </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.login_only}</td>
            <td class="field">
                {if $sPost.login == '1'}
                    {assign var='login_yes' value='checked="checked"'}
                {elseif $sPost.login == '0'}
                    {assign var='login_no' value='checked="checked"'}
                {else}
                    {assign var='login_no' value='checked="checked"'}
                {/if}
                <label><input {$login_yes} class="lang_add" type="radio" name="login" value="1" /> {$lang.yes}</label>
                <label><input {$login_no} class="lang_add" type="radio" name="login" value="0" /> {$lang.no}</label>
            </td>
        </tr>
        </table>
    </div>

    <table class="form">
    <tr {if $smarty.get.action == 'edit'
            && in_array($smarty.get.page, ','|@explode:'add_listing,edit_listing')}class="hide"{/if}>
        <td class="name">{$lang.deny_access_for}</td>
        <td class="field">
            <fieldset class="light">
                {assign var='account_types_phrase' value='admin_controllers+name+account_types'}
                <legend id="legend_account_types" class="up" onclick="fieldset_action('account_types');">{$lang.$account_types_phrase}</legend>
                <div id="account_types">
                {foreach from=$account_types item='a_type' name='ac_type'}
                    {if $a_type.Key != 'visitor'}
                        <div style="padding: 2px 0 2px 5px;">
                            <input {if $sPost.deny && $a_type.ID|in_array:$sPost.deny}checked="checked"{/if}
                                   style="margin-bottom: 0px;"
                                   type="checkbox"
                                   id="account_type_{$a_type.ID}"
                                   value="{$a_type.ID}"
                                   name="deny[]"
                            /> <label for="account_type_{$a_type.ID}">{$a_type.name}</label>
                        </div>
                    {/if}
                {/foreach}
                </div>
            </fieldset>

            <div class="grey_area" style="margin-bottom: 10px;">
                <span onclick="$('#account_types input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                <span class="divider"> | </span>
                <span onclick="$('#account_types input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
            </div>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.no_follow}</td>
        <td class="field">
            {if $sPost.no_follow == '1'}
                {assign var='no_follow_yes' value='checked="checked"'}
            {elseif $sPost.no_follow == '0'}
                {assign var='no_follow_no' value='checked="checked"'}
            {else}
                {assign var='no_follow_no' value='checked="checked"'}
            {/if}
            <label><input {$no_follow_yes} class="lang_add" type="radio" name="no_follow" value="1" /> {$lang.yes}</label>
            <label><input {$no_follow_no} class="lang_add" type="radio" name="no_follow" value="0" /> {$lang.no}</label>
        </td>
    </tr>
    </table>

    <div id="tpl_cont">
        <table class="form">
        <tr>
            <td class="name">{$lang.use_tpl}</td>
            <td class="field">
                {if $sPost.tpl == '1'}
                    {assign var='tpl_yes' value='checked="checked"'}
                {elseif $sPost.tpl == '0'}
                    {assign var='tpl_no' value='checked="checked"'}
                {else}
                    {assign var='tpl_yes' value='checked="checked"'}
                {/if}
                <label><input {$tpl_yes} class="lang_add" type="radio" name="tpl" value="1" /> {$lang.yes}</label>
                <label><input {$tpl_no} class="lang_add" type="radio" name="tpl" value="0" /> {$lang.no}</label>
            </td>
        </tr>
        </table>
    </div>

    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.show_in_menu}</td>
        <td class="field">
            <fieldset class="light">
                <legend id="legend_menus" class="up" onclick="fieldset_action('menus');">{$lang.menus}</legend>
                <div id="menus" class="menus">
                    {assign var='cMenu' value=$sPost.menus}
                    {foreach from=$l_menu_types item='menu' key='mType'}
                        <div style="padding: 2px 0 2px 5px;">
                            <input {if $mType == $cMenu.$mType}checked="checked"{/if} class="lang_add" type="checkbox" name="menus[{$mType}]" value="{$mType}" id="m_{$mType}" /> <label for="m_{$mType}">{$menu}</label><br />
                        </div>
                    {/foreach}
                </div>
            </fieldset>

            {rlHook name='apTplPagesMenus'}

            <div class="grey_area" style="margin-bottom: 10px;">
                <span onclick="$('.menus input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                <span class="divider"> | </span>
                <span onclick="$('.menus input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
            </div>
        </td>
    </tr>


    {rlHook name='apTplPagesForm'}

    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select name="status" {if $sPost.key|strpos:'lt' === 0 && $smarty.get.action == 'edit'}disabled class="disabled"{/if}>
                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>

            {if $sPost.key|strpos:'lt' === 0 && $smarty.get.action == 'edit'}
                <input type="hidden" name="status" value="{$sPost.status}" />
                {assign var='replace' value='<a target="_blank" href="'|cat:$rlBase|cat:'index.php?controller=listing_types">$1</a>'}
                <span class="field_description">{$lang.prevent_disabling_lt_page|regex_replace:'/\[(.*)\]/':$replace} </span>
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
    <!-- add new page end -->

    <!-- additional JS -->
    {if $smarty.post.page_type}
    <script type="text/javascript">
    {literal}
    $(document).ready(function(){
        show('ptype_{/literal}{$smarty.post.page_type}{literal}', '#ptypes div');
    });
    {/literal}
    </script>
    {/if}

    <script type="text/javascript">
    {literal}
    var page_type_change = function(val){
        if (val == 'external') {
            $('#page_url').slideUp('slow');
        } else {
            $('#page_url').slideDown('slow');
        }

        if (val == 'system') {
            $('#tpl_cont').slideUp('slow');
        } else {
            $('#tpl_cont').slideDown('slow');
        }
    }
    $('#page_types').change(function(){
        show('ptype_'+$(this).val(), '#ptypes div');

        page_type_change($(this).val());
    });
    page_type_change($('#page_types').val());
    {/literal}
    </script>
    <!-- additional JS end -->

    {rlHook name='apTplPagesAction'}

{else}

    <!-- pages grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var pagesGrid;

    {literal}
    $(document).ready(function(){

        pagesGrid = new gridObj({
            key: 'pages',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/pages.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_pages_manager'],
            filters: cookie_filters,
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Page_type', mapping: 'Page_type'},
                {name: 'Login', mapping: 'Login'},
                {name: 'Key', mapping: 'Key'},
                {name: 'No_follow', mapping: 'No_follow'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    id: 'rlExt_item_bold',
                    width: 50
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Page_type',
                    id: 'rlExt_item',
                    width: 15
                },{
                    header: lang['ext_need_login'],
                    dataIndex: 'Login',
                    width: 10,
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
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_no_follow'],
                    dataIndex: 'No_follow',
                    width: 10,
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
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 10,
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
                    width: 15,
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
                            beforeselect: function(combo){
                                // prevent of the disabling system listing type pages
                                if (combo.gridEditor.record.data.Key.indexOf('lt_') === 0) {
                                    var notice = '{/literal}{$lang.prevent_disabling_lt_page}{literal}';
                                    var url    = rlUrlHome + 'index.php?controller=listing_types';

                                    printMessage(
                                        'error',
                                        notice.replace(/\[(.*)\]/i, '<a target="_blank" href="' + url + '">$1</a>')
                                    );
                                    return false;
                                }
                            }
                        }
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        var splitter = false;

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&page="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deletePage\", \""+Array(data)+"\", \"page_load\" )' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplPagesGrid'}{literal}

        pagesGrid.init();
        grid.push(pagesGrid.grid);

    });
    {/literal}
    //]]>
    </script>
    <!-- pages grid end -->

    {rlHook name='apTplPagesBottom'}

{/if}

<!-- pages end -->
