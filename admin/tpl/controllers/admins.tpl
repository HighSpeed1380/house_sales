<!-- admins tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplAdminsNavBar'}

    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_admin}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.admins_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;admin={$smarty.get.admin}{/if}" method="post">
        <!-- add/edit new admin -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.account_information}

        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.username}</td>
            <td class="field">
                <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled {/if}" name="login" type="text" style="width: 150px;" value="{$sPost.login}" maxlength="50" />
            </td>
        </tr>

        <tr>
            <td class="name"><span class="red">*</span>{$lang.password}</td>
            <td class="field">
                <input name="password" type="password" style="width: 150px;" maxlength="30" />
            </td>
        </tr>

        <tr>
            <td class="name"><span class="red">*</span>{$lang.password_repeat}</td>
            <td class="field">
                <input name="password_repeat" type="password" style="width: 150px;" maxlength="30" />
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.name}</td>
            <td class="field">
                <input name="name" type="text" style="width: 250px;" maxlength="100" value="{$sPost.name}" />
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.mail}</td>
            <td class="field">
                <input name="email" type="text" style="width: 250px;" maxlength="100" value="{$sPost.email}" />
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.account_type}</td>
            <td class="field">
                <input style="margin-left: 8px;" {if $sPost.type == 'super' || !isset($sPost.type)}checked="checked"{/if} class="mode" name="type" type="radio" id="type_super" value="super" /> <label for="type_super">{$lang.super_admin}</label>
                <input {if $sPost.type == 'limited'}checked="checked"{/if} class="mode" name="type" type="radio" id="type_limited" value="limited" /> <label for="type_limited">{$lang.limited_account}</label>
            </td>
        </tr>

        {rlHook name='apTplAdminsForm'}

        <tr>
            <td class="name"><span class="red">*</span>{$lang.status}</td>
            <td class="field">
                <select name="status">
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
        </table>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        <!-- add/edit admin end -->

        <!-- admin rules section -->
        <div id="super_area" {if $sPost.type == 'super' || !isset($sPost.type)}class="hide"{/if}>
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.account_rules}

            {foreach from=$mMenuItems item='nParent'}
                <fieldset style="margin: 5px 0;">
                    <legend id="legend_parent_{$nParent.ID}" class="up"><span onclick="fieldset_action('parent_{$nParent.ID}');">{$nParent.name}</span> (<span class="purple_12_cursor" onclick="$('#parent_{$nParent.ID} input').attr('checked', true);">{$lang.check_all}</span><label>|</label><span class="purple_12_cursor" onclick="$('#parent_{$nParent.ID} input').attr('checked', false);">{$lang.uncheck_all}</span>)</legend>
                    <div id="parent_{$nParent.ID}">
                    {assign var='pRights' value=$sPost.rights}

                    {foreach from=$nParent.child item='nChild'}
                        {if $nChild.Key != 'home' && empty($nChild.Vars)}
                            <div style="margin: 7px 10px;">
                                {assign var='childKey' value=$nChild.Key}
                                <input class="parent_input" {if isset($pRights.$childKey)}checked="checked"{/if} type="checkbox" name="rights[{$nChild.Key}]" id="child_{$nChild.Controller}" />
                                <label for="child_{$nChild.Controller}" style="font-size: 12px;">{$nChild.name}</label>

                                {if $childKey|in_array:$extended_sections}
                                    <div style="margin: 2px 20px;" class="rule">
                                        <div class="clear"></div>
                                        {foreach from=$extended_modes item='mode'}
                                            <div class="{$mode}"><input {if $pRights.$childKey.$mode == $mode}checked="checked"{/if} name="rights[{$nChild.Key}][{$mode}]" value="{$mode}" id="label__{$childKey}__{$mode}" type="checkbox" /><label for="label__{$childKey}__{$mode}" style="padding: 0;"> {$lang.$mode}</label></div>
                                        {/foreach}
                                        <div class="clear"></div>
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    {/foreach}
                    </div>
                </fieldset>
            {/foreach}

            {rlHook name='apTplAdminsRules'}

            <input style="margin-top: 10px;" type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
    </form>
    <!-- admin rules section end -->

    <script type="text/javascript">
    {literal}

    $(document).ready(function(){
        $('.mode').click(function(){
            if ( $(this).val() == 'super' )
            {
                $('#super_area').hide();
            }
            else
            {
                $('#super_area').show();
            }
        });

        $('.parent_input').click(function(){
            if ( $(this).attr('checked') )
            {
                $(this).next().next('div').children('div').each(function(){
                    $(this).children('input').attr('checked', true);
                });
            }
        });

        $('.rule input').click(function(){
            if ( $(this).attr('checked') == true )
            {
                var id = $(this).attr('id').split('__')[1];
                $('#child_'+id).attr('checked', true);
            }
        });
    });

    {/literal}
    </script>

{else}

    <!-- listing admins grid create -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var adminGrid;

    {literal}
    $(document).ready(function(){

        adminGrid = new gridObj({
            key: 'admins',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/admins.inc.php?q=ext',
            defaultSortField: 'User',
            title: lang['ext_admins_manager'],
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'User', mapping: 'User', type: 'string'},
                {name: 'Name', mapping: 'Name', type: 'string'},
                {name: 'Email', mapping: 'Email', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'}
            ],
            columns: [
                {
                    header: lang['ext_login'],
                    dataIndex: 'User',
                    id: 'rlExt_item_bold',
                    width: 50,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    id: 'rlExt_item',
                    header: lang['ext_name'],
                    dataIndex: 'Name',
                    width: 20,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_email'],
                    dataIndex: 'Email',
                    width: 20,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 13,
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
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        var splitter = false;

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href=\""+rlUrlHome+"index.php?controller="+controller+"&action=edit&admin="+data+"\"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            if (data == "{/literal}{$smarty.session.sessAdmin.user_id}{literal}") {
                                var remove_notice = lang['ext_notice_removing_current_admin'];
                            } else {
                                var remove_notice = lang['ext_notice_'+delete_mod];
                            }
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+remove_notice+"\", \"xajax_deleteAdmin\", \""+Array(data)+"\", \"admin_load\" )' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplAdminsGrid'}{literal}

        adminGrid.init();
        grid.push(adminGrid.grid);

        adminGrid.grid.on('validateedit', function(e) {
            if (e.field == 'Email' && e.originalValue !== e.value) {
                var data = {
                    'lookIn': 'admins',
                    'byField': 'Email',
                    'value': e.value
                };

                flynax.sendAjaxRequest('isUserExist', data, function() {
                    Ext.Ajax.request({
                        url: adminGrid.ajaxUrl,
                        method: adminGrid.ajaxMethod,
                        params: {
                            'action': 'update',
                            'id': e.record.id,
                            'field': 'Email',
                            'value': e.value
                        }
                    });

                    adminGrid.reload();
                });

                return false;
            }
        });

    });
    {/literal}
    //]]>
    </script>
    <!-- listing admins grid create end -->

{/if}

<!-- admin tpl end -->
