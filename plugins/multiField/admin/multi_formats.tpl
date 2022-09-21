<link href="{$smarty.const.RL_PLUGINS_URL}multiField/static/aStyle.css" type="text/css" rel="stylesheet" />

<!-- navigation bar -->
<div id="nav_bar">
    {if $aRights.$cKey.add}
        {if !$smarty.get.action && !$smarty.get.parent}
            <a href="{$rlBaseC}action=add" class="button_bar"><span class="center_add">{$lang.mf_add_item}</span></a>
        {elseif $smarty.get.parent}
            <a onclick="show('search');$('#add_item,#load_cont,#edit_item,#related_fields').slideUp('fast');" href="javascript:void(0)" class="button_bar"><span class="center_search">{$lang.search}</span></a>
            <a onclick="show('add_item');$('#search,#load_cont,#edit_item,#related_fields').slideUp('fast');" href="javascript:void(0)" class="button_bar"><span class="center_add">{$lang.add_item}</span></a>
            <a onclick="show('related_fields');$('#add_item,#search,#load_cont,#edit_item').slideUp('fast');" href="javascript:void(0)" class="button_bar"><span class="center_list">{$lang.mf_related_fields}</span></a>
            <a id="load_button" href="javascript:void(0)" class="button_bar"><span class="center_import">{$lang.mf_import_flsource}</span></a>
        {elseif $smarty.get.action == 'edit'}
            <a href="{$rlBaseC}parent={$item_info.ID}" class="button_bar"><span class="center_build">{$lang.mf_manage_items}</span></a>
        {/if}
    {/if}

    {if $smarty.get.action == 'add'}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.mf_formats_list}</span><span class="right"></span></a>
    {/if}
</div>
<!-- navigation bar end -->

{include file=$smarty.const.RL_PLUGINS|cat:'multiField'|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:'import_interface.tpl'}

<!-- load from server -->
<div id="load_cont" class="hide" style="margin-top:15px">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' key="fl_load" loading=1 fixed=0 navigation=false}
        <div class="white block_loading" style="height:57px" id="flsource_container"></div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
</div>
<!-- load from server end -->

{if $smarty.get.parent}

    <!-- search -->
    <div id="search" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
    
    <form method="post" onsubmit="return false;" id="search_form" action="">
        <table class="form">
        <tr>
            <td class="name">{$lang.mf_name}</td>
            <td class="field">
                <input type="text" id="search_name" />
                <label style="display:block;padding: 5px 0;"><input value="1" type="checkbox" id="search_all" /> {$lang.mf_search_all_levels}</label>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" class="button" value="{$lang.search}" id="search_button" />
                <input type="button" class="button" value="{$lang.reset}" id="reset_search_button" />
            
                <a class="cancel" href="javascript:void(0)" onclick="$('#search').slideUp('fast')">{$lang.cancel}</a>
            </td>
        </tr>
        </table>
    </form>
    
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    
    <script type="text/javascript">
    var remote_filters = new Array();
    {literal}
    
    var search = new Array();
    var cookie_filters = new Array();

    $(document).ready(function(){
        if (readCookie('mf_sc') || remote_filters.length > 0)
        {
            $('#search').show();
            cookie_filters = remote_filters.length > 0 ? remote_filters : readCookie('mf_sc').split(',');

            for (var i in cookie_filters)
            {
                if ( typeof(cookie_filters[i]) == 'string' )
                {
                    var item = cookie_filters[i].split('||');
                    if ( item[0] != 'undefined' && item[0] != '' )
                    {
                        $('#search_'+item[0].toLowerCase()).selectOptions(item[1]);
                    }
                }
            }
        }
        
        $('#search_form').submit(function(){
            search = new Array();
            search.push( new Array('action', 'search') );
            search.push( new Array('Name', $('#search_name').val()) );
            search.push( new Array('Parent', '{/literal}{$smarty.get.parent}{literal}'));
            if ($('#search_all:checked').val()) {
                search.push( new Array('Search_all_levels', '1'));
            }

            var save_search = new Array();
            for(var i in search)
            {
                if ( search[i][1] != '' && typeof(search[i][1]) != 'undefined'  )
                {
                    save_search.push(search[i][0]+'||'+search[i][1]);
                }
            }
            createCookie('mf_sc', save_search, 1);
            
            itemsGrid.filters = search;
            itemsGrid.reload();
        });
        
        $('#reset_search_button').click(function(){
            eraseCookie('mf_sc');
            itemsGrid.reset();
            
            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
            $("#search input").each(function(){
                if ( $(this).attr('type') == 'radio' )
                {
                    $(this).attr('checked', false);
                }
            });
        });
        
    });
    
    {/literal}
    </script>
    <!-- search end -->
{/if}

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}
    <!-- add/edit -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;item={$smarty.get.item}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}

        <div id="new_format_cont">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.key}</td>
                <td class="field">
                    <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.mf_name}</td>
                <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}
                        <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" style="width: 250px;" maxlength="50" />
                        {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span></div>
                        {/if}
                    {/foreach}
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.mf_order_type}</td>
                <td class="field">
                    <select name="order_type">
                        <option value="alphabetic" {if $sPost.order_type == 'alphabetic'}selected="selected"{/if}>{$lang.alphabetic_order}</option>
                        <option value="position" {if $sPost.order_type == 'position'}selected="selected"{/if}>{$lang.position_order}</option>
                    </select>
                </td>
            </tr>
            </table>
        </div>

        <table class="form">
        {if !$geo_format_data.Key || $geo_format_data.Key == $smarty.get.item}
        <tr>
            <td class="name"><span class="red">*</span>{$lang.mf_geofilter}</td>
            <td class="field">
                {if $smarty.get.action == 'edit' && $geo_format_data.Key == $smarty.get.item}
                    {assign var='disabled_tag' value='disabled="disabled"'}
                {/if}

                {if $sPost.geo_filter == '1'}
                    {assign var='geofilter_yes' value='checked="checked"'}
                {elseif $sPost.geo_filter == '0'}
                    {assign var='geofilter_no' value='checked="checked"'}
                {else}
                    {assign var='geofilter_no' value='checked="checked"'}
                {/if}

                <label><input {$geofilter_yes} {$disabled_tag} class="lang_add" type="radio" name="geo_filter" value="1" /> {$lang.enabled}</label>
                <label><input {$geofilter_no} {$disabled_tag} class="lang_add" type="radio" name="geo_filter" value="0" /> {$lang.disabled}</label>
            </td>
        </tr>
        {/if}
        <tr>
            <td class="name">{$lang.status}</td>
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
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add end -->
{else}
    <!-- add new item -->
    <div id="add_item" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}

        {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/manage_item.tpl' mode='add'}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <!-- edit item -->
    <div id="edit_item" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.edit_item}

        {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/manage_item.tpl' mode='edit'}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- edit item end -->

    <script>
    if (typeof rlLang == 'undefined') var rlLang = '{$smarty.const.RL_LANG_CODE}';
    var geo_format = {if $geo_format_data && $geo_format_data.Key == $head_level_data.Key}true{else}false{/if};
    var parent_key = '{$parent_info.Key}';
    var system_lang = '{$config.lang}';
    var level = {if is_numeric($level)}{$level}{else}null{/if};
    rlConfig['mf_geo_subdomains_type'] = '{$config.mf_geo_subdomains_type}';
    rlConfig['mf_multilingual_path'] = {if $config.mf_multilingual_path}true{else}false{/if};
    rlConfig['mf_geo_subdomains'] = {if $config.mf_geo_subdomains}true{else}false{/if};
    lang['notice_field_empty'] = "{$lang.notice_field_empty}";
    lang['item_added'] = "{$lang.item_added}";
    lang['item_edited'] = "{$lang.item_edited}";

    {literal}

    // Add/Edit item handler
    $(function(){
        var default_name = 'name_' + system_lang;

        var $form = $('form.manage-item-form');
        var $defaultName = $form.find('[name=' + default_name + ']');

        $defaultName.focus(function(){
            $(this).removeClass('error');
        });

        $form.on('submit', function(e){
            e.preventDefault();

            var errors = [];
            var form_mode = $(this).attr('name').replace('-item-form', '');
            var names = $(this).find('[name^="name_"]').serializeArray();

            var $submit = $(this).find('input[type=submit]');

            for (var i = 0; i < names.length; i++) {
                if (names[i].name == default_name && names[i].value.length <= 2) {
                    errors.push(lang['notice_field_empty'].replace('{field}', lang['ext_name']));
                    $defaultName.addClass('error');
                }
            }

            if (errors.length) {
                printMessage('error', errors);
            } else {
                $submit.val(lang['loading']).attr('disabled', true);

                var data = {
                    mode: form_mode == 'add' ? 'mfAddItem' : 'mfEditItem',
                    parentKey: parent_key,
                    status: $(this).find('[name=status]').val(),
                    key: $(this).find('[name=key]').val(),
                    names: names,
                    paths: $(this).find('[name^="path_"]').serializeArray(),
                    lat: $(this).find('[name=lat]').val(),
                    lng: $(this).find('[name=lng]').val()
                };

                $.post(rlConfig['ajax_url'], data, function(response, status){
                    if (status == 'success') {
                        if (response.status == 'OK') {
                            var message = form_mode == 'add' ? lang['item_added'] : lang['item_edited'];
                            message = response.message ? response.message : message;

                            printMessage('notice', message);
                            itemsGrid.reload();
                            $('#' + form_mode + '_item').slideUp('normal');

                            $form.find('input[type=text]').val('');
                        } else {
                            printMessage('error', response.message);
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }

                    $submit.val($submit.data('phrase')).attr('disabled', false);
                }, 'json').fail(function(){
                    printMessage('error', lang['system_error']);
                    $submit.val($submit.data('phrase')).attr('disabled', false);
                });
            }

            return false;
        });
    });

    // Prepare edit
    $(function(){
        $form = $('form[name=edit-item-form]');

        $('#grid').on('click', '.actions-cell .edit', function(){
            $form.find('input.error').removeClass('error');

            var data = {
                mode: 'mfPrepareItem',
                key: $(this).data('key')
            };
            $.post(rlConfig['ajax_url'], data, function(response, status){
                $('#edit_item').slideDown();

                if (status == 'success') {
                    if (response.status == 'OK') {
                        for (var code in response.data.names) {
                            $form.find('input[name=name_' + code + ']').val(response.data.names[code]);
                        }

                        if (geo_format) {
                            for (var item in response.data) {
                                if (item.indexOf('Path') === 0) {
                                    var path = response.data[item];
                                    var field = item.toLowerCase();

                                    if (level > 0 && rlConfig['mf_geo_subdomains_type'] !== 'unique') {
                                        path = path.split('/').slice(level).join('-');
                                    }

                                    if (!rlConfig['mf_multilingual_path']) {
                                        field += '_' + rlLang;
                                    }

                                    $form.find('input[name=' + field + ']').val(path);
                                }
                            }

                            $form.find('[name=lat]').val(response.data.Latitude);
                            $form.find('[name=lng]').val(response.data.Longitude);
                        }

                        $form.find('[name=key]').val(response.data.Key);
                        $form.find('[name=status]').val(response.data.Status);
                    } else {
                        printMessage('error', response.message);
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }
            }, 'json').fail(function(){
                printMessage('error', lang['system_error']);
            });
        });
    });

    {/literal}
    </script>

    <!-- related fields list -->
    <div id="related_fields" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.mf_related_fields}
        <table class="form">
        <tr>
            <td class="name" style="width:215px">{$lang.mf_related_listing_fields}</td>
            <td class="field">
                {foreach from=$related_listing_fields item="field"}
                    <div>
                        {$lang.name}: <b>{$field.name} / </b>
                        {$lang.key}: <b>{$field.Key} / </b>
                        <a href="index.php?controller=listing_fields&action=edit&field={$field.Key}" target="_blank">{$lang.edit|strtolower}</a>
                    </div>
                {foreachelse}
                    <span class="field_description_noicon">{$lang.mf_no_related_fields}</span>
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.mf_related_account_fields}</td>
            <td class="field">
                {foreach from=$related_account_fields item="field"}
                    <div>
                        {$lang.name}: <b>{$field.name} / </b>
                        {$lang.key}: <b>{$field.Key} / </b>
                        <a href="index.php?controller=account_fields&action=edit&field={$field.Key}" target="_blank" style="margin-left:5px">{$lang.edit|strtolower}</a>
                    </div>
                {foreachelse}
                    <span class="field_description_noicon">{$lang.mf_no_related_fields}</span>
                {/foreach}
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- related fields list end -->

    <!-- multi formats grid -->
    <div id="grid"></div>
    <script type="text/javascript">
    lang['mass_action_completed'] = "{$lang.mass_action_completed}";
    lang['delete_confirm'] = "{$lang.delete_confirm}";
    rlConfig.mf_parent_status = {if $parent_info.Status}'{$parent_info.Status}'{else}false{/if};

    {if $smarty.get.parent}
        var itemsGrid;
        var parent = '{$smarty.get.parent}';
        
        {literal}
        $(document).ready(function(){
            {/literal}{if !$level}{literal}
                Ext.grid.defaultColumn = function(config){
                    Ext.apply(this, config);
                    if (!this.id){
                        this.id = Ext.id();
                    }
                    this.renderer = this.renderer.createDelegate(this);
                };

                Ext.grid.defaultColumn.prototype = {
                    init : function(grid){
                        this.grid = grid;
                        this.grid.on('render', function(){
                            var view = this.grid.getView();
                            view.mainBody.on('mousedown', this.onMouseDown, this);
                        }, this);
                    },
                    onMouseDown : function(e, t){
                    if (t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1) {
                        e.stopEvent();
                        var index = this.grid.getView().findRowIndex(t);
                        var record = this.grid.store.getAt(index);
                        record.set(this.dataIndex, !record.data[this.dataIndex]);
                            Ext.Ajax.request({
                                waitMsg: lang['ext_saving_changes'],
                                url: rlPlugins + 'multiField/admin/multi_formats.inc.php?q=ext&parent='+parent,
                                method: 'GET',
                                params: {
                                    action: 'update',
                                    id: record.id,
                                    field: this.dataIndex,
                                    value: record.data[this.dataIndex]
                                },
                                failure: function() {
                                    Ext.MessageBox.alert(lang['ext_error_saving_changes']);
                                },
                                success: function() {
                                    itemsGrid.store.commitChanges();
                                    itemsGrid.reload();
                                }
                            });
                        }
                    },
                    renderer : function(v, p, record){
                        p.css += ' x-grid3-check-col-td';
                        return '<div ext:qtip="'+lang['ext_set_default']+'" class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'">&#160;</div>';
                    }
                };
                var defaultColumn = new Ext.grid.defaultColumn({
                    header: lang['ext_default'],
                    dataIndex: 'Default',
                    width: 60,
                    fixed: true
                });
            {/literal}{/if}{literal}

            var mass_actions = [
                [lang['ext_activate'], 'activate'],
                [lang['ext_suspend'], 'approve'],
                [lang['ext_delete'], 'delete']
            ];

            itemsGrid = new gridObj({
                key: 'data_items',
                id: 'grid',
                ajaxUrl: rlPlugins + 'multiField/admin/multi_formats.inc.php?q=ext&parent='+parent,
                defaultSortField: 'name',
                remoteSortable: true,
                checkbox: true,
                actions: mass_actions,
                title: lang['ext_multi_formats_manager'],
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'},
                    {name: 'Icons', mapping: 'Icons'},
                    {name: 'Parent_name', mapping: 'Parent_name', type: 'string'},
                    {/literal}{if $geo_format_data && $geo_format_data.Key == $head_level_data.Key}{literal}
                        {name: 'Path', mapping: 'Path', type: 'string'},
                    {/literal}{/if}{literal}
                    {/literal}{if !$level}{literal}
                    {name: 'Default', mapping: 'Default'}
                    {/literal}{/if}{literal}
                ],
                columns: [
                    {
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        width: 20,
                        id: 'rlExt_item_bold'
                    },
                    {/literal}{if $level}{literal}
                    {
                        header: 'Parent',
                        dataIndex: 'Parent_name',
                        width: 10
                    },
                    {/literal}{/if}
                    {if $geo_format_data && $geo_format_data.Key == $head_level_data.Key}{literal}
                    {
                        header: 'Path',
                        dataIndex: 'Path',
                        width: 10
                    },
                    {/literal}{/if}
                    {if $order_type == 'position'}{literal}{
                        header: lang['ext_position'],
                        dataIndex: 'Position',
                        width: 70,
                        fixed: true,
                        editor: new Ext.form.NumberField({
                            allowBlank: false,
                            allowDecimals: false
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{/literal}{/if}
                    {if !$level}{literal}
                        defaultColumn,
                    {/literal}{/if}{literal}
                    {
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 80,
                        fixed: true,
                        renderer: rlConfig.mf_parent_status === false || rlConfig.mf_parent_status === 'active' ? null : function(value){
                            return '<span ext:qtip="' + lang.mf_inactive_parent_status_hint + '">' + value + '</span>';
                        },
                        editor: rlConfig.mf_parent_status === 'approval' ? null : new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
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
                        width: 75,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(val, obj, row){
                            var manage_href = rlUrlHome+"index.php?controller="+controller+"&parent="+val;    

                            var out = "<div class='actions-cell'>";
                            out += "<a href="+manage_href+" ><img class='manage' ext:qtip='"+lang['ext_manage']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img data-key='"+val+"' class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' />";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete_item']+"\", \"xajax_deleteItem\", \""+val+"\", \"section_load\" )' />";
                            out += "</div>";
                            
                            return out;
                        }
                    }
                ]
            });

            {/literal}{if !$level}{literal}
                itemsGrid.plugins.push(defaultColumn);
            {/literal}{/if}{literal}

            itemsGrid.init();
            grid.push(itemsGrid.grid);

            // actions listener
            itemsGrid.actionButton.addListener('click', function() {
                var selected = itemsGrid.checkboxColumn.getSelections();
                var action = itemsGrid.actionsDropDown.getValue();
    
                if (!action) {
                    return false;
                }

                var ids = new Array();

                var doMassAction = function(){
                    $.each(selected, function(index, item){
                        ids.push(item.id);
                    });
                    
                    ids = ids.join('|');

                    var data = {
                        mode: 'mfBulkAction',
                        ids: ids,
                        action: action
                    };
                    $.post(rlConfig['ajax_url'], data, function(response, status){
                        if (response.status == 'OK') {
                            itemsGrid.checkboxColumn.clearSelections();
                            itemsGrid.actionsDropDown.setVisible(false);
                            itemsGrid.actionButton.setVisible(false);

                            printMessage('notice', lang['mass_action_completed']);
                            itemsGrid.reload();
                        } else {
                            printMessage('error', response.message);
                        }
                    }, 'json').fail(function(object, status) {
                        if (status == 'abort') {
                            return;
                        }

                        printMessage('error', lang['system_error']);
                    });
                }

                if (action == 'delete') {
                    Ext.MessageBox.confirm('Confirm', lang['ext_notice_delete'], function(btn) {
                        if (btn == 'yes') {
                            doMassAction();
                        }
                    });
                } else {
                    doMassAction();
                }
            });
        });
        {/literal}
    {else}
        {literal}
        
        var multiFieldGrid;
        
        $(document).ready(function(){
            
            multiFieldGrid = new gridObj({
                key: 'multi_formats',
                id: 'grid',
                ajaxUrl: rlPlugins + 'multiField/admin/multi_formats.inc.php?q=ext',
                defaultSortField: 'name',
                title: lang['ext_multi_formats_manager'],
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [{
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        id: 'rlExt_item_bold',
                        width: 40
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 100,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
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
                        width: 75,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(val, obj, row){
                            var out = "<div>";
                            var splitter = false;
                            var format_key = row.data.Key;

                            var manage_href = rlUrlHome+"index.php?controller="+controller+"&amp;parent="+val;
                            out += "<a href="+manage_href+" ><img class='manage' ext:qtip='"+lang['ext_manage']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit&amp;item="+format_key+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['delete_confirm']+"\", \"xajax_deleteFormat\", \""+format_key+"\", \"section_load\" )' />";
                            out += "</div>";
                            
                            return out;
                        }
                    }
                ]
            });
            
            multiFieldGrid.init();
            grid.push(multiFieldGrid.grid);
            
        });
        {/literal}
    {/if}

    {if $smarty.get.parent}
    {literal}
        function handleSourceActs()
        {
            $('#import_button').click(function(){
                $(this).val('{/literal}{$lang.loading}{literal}');

                var values = '';
                $('div.td_div input:checked').each(function(){
                    values += $(this).val()+",";
                });
                xajax_importSource( values, $('input[name=table]').val(), $('input#ignore_one:checked').val() );
            });

            $('div.td_div input').click(function(){
                if( $('div.td_div input:checked').length  == 1 )
                {
                    $('#checked_one_hint').fadeIn();
                }else
                {
                    $('#checked_one_hint').fadeOut();
                }
            });
        }
        $(document).ready(function(){
            $.ajaxSetup({ cache: false });
        });
    {/literal}
    {/if}
    </script>
{/if}

<script type="text/javascript">
{literal}
    $('#load_button').click(function(){
        if ($('#load_cont').css('display') == 'none') {
            xajax_listSources('{$smarty.get.parent}');
        }
        show('load_cont');
        $('#search,#add_item,#edit_item,#related_fields').slideUp('fast');
    });
{/literal}
</script>

<script type="text/javascript" src="{$smarty.const.RL_PLUGINS_URL}multiField/static/lib_admin.js"></script>
