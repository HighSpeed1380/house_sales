<!-- data formats tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplDataFormatsNavBar'}

    {if $aRights.$cKey.add}
        {if $smarty.get.mode}
            <a href="javascript:void(0)" onclick="show('new_item');$('#edit_item').slideUp('fast');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_item}</span><span class="right"></span></a>
        {elseif !$smarty.get.action}
            <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_format}</span><span class="right"></span></a>
        {elseif $smarty.get.action == 'edit'}
            <a href="{$rlBaseC}mode=manage&amp;format={$smarty.get.format}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.manage}</span><span class="right"></span></a>
        {/if}
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.formats_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;format={$smarty.get.format}{/if}" method="post">
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
                        <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" style="width: 250px;" maxlength="50" />
                    {if $allLangs|@count > 1}
                        <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                    </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.order_type}</td>
            <td class="field">
                <select name="order_type">
                    <option value="alphabetic" {if $sPost.order_type == 'alphabetic'}selected="selected"{/if}>{$lang.alphabetic_order}</option>
                    <option value="position" {if $sPost.order_type == 'position'}selected="selected"{/if}>{$lang.position_order}</option>
                </select>
            </td>
        </tr>

        {rlHook name='apTplDataFormatsAddFormatField'}

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
            <td class="name">{$lang.enable_conversion}</td>
            <td class="field">
                {if $sPost.conversion == '1'}
                    {assign var='conv_yes' value='checked="checked"'}
                {elseif $sPost.conversion == '0'}
                    {assign var='conv_no' value='checked="checked"'}
                {else}
                    {assign var='conv_no' value='checked="checked"'}
                {/if}
                <label><input {$conv_yes} class="lang_add" type="radio" name="conversion" value="1" /> {$lang.yes}</label>
                <label><input {$conv_no} class="lang_add" type="radio" name="conversion" value="0" /> {$lang.no}</label>
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
    {if $smarty.get.mode === 'manage'}
        <!-- add new item -->
        <div id="new_item" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}
            <form onsubmit="addItem();$('input[name=item_submit]').val('{$lang.loading}');return false;" action="" method="post">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.key}</td>
                <td class="field">
                    <input type="text" id="ni_key" style="width: 200px;" maxlength="60" />
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.value}</td>
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
                        <input id="ni_{$language.Code}" type="text" style="width: 250px;" />
                        {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>

            {rlHook name='apTplDataFormatsAddItemField'}

            <tr>
                <td class="name">{$lang.status}</td>
                <td class="field">
                    <select id="ni_status">
                        <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.default}</td>
                <td class="field">
                    <input type="checkbox" id="ni_default" value="1" />
                </td>
            </tr>

            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" name="item_submit" value="{$lang.add}" />
                    <a onclick="$('#new_item').slideUp('normal')" href="javascript:void(0)" class="cancel">{$lang.close}</a>
                </td>
            </tr>
            </table>
            </form>
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
        <!-- add new item end -->

        <!-- edit item -->
        <div id="edit_item" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.edit_item}
            <div id="prepare_edit_area">
                <div id="ei_loading" class="open_load" style="margin: 6px 0 0 10px;">{$lang.preparing}</div>
            </div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
        <!-- edit item end -->
    {/if}

    {literal}
    <script type="text/javascript">
    var addItem = function(){
    {/literal}
        var names = new Array();

        {foreach from=$allLangs item='languages'}
        names['{$languages.Code}'] = $('#ni_{$languages.Code}').val();
        {/foreach}

        xajax_addItem($('#ni_key').val(), names, $('#ni_status').val(), '{$smarty.get.format}', $('#ni_default:checked').val());
    {literal}
    }
    </script>
    {/literal}

    {literal}
    <script type="text/javascript">
    var editItem = function(key){
    {/literal}
        var names = new Array();

        {foreach from=$allLangs item='languages'}
        names['{$languages.Code}'] = $('#ei_{$languages.Code}').val();
        {/foreach}

        xajax_editItem(key, names, $('#ei_status').val(), '{$smarty.get.format}', $('#ei_default:checked').val());
    {literal}
    }
    </script>
    {/literal}
    <!-- add new item end -->

    <!-- data formats grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    {if $smarty.get.mode == 'manage'}
        var itemsGrid;
        var format = '{$smarty.get.format}';

        var mass_actions = [
            [lang['ext_activate'], 'activate'],
            [lang['ext_suspend'], 'approve'],
            {if 'delete'|in_array:$aRights.listings}[lang['ext_delete'], 'delete']{/if}
            ];

        {literal}
        $(document).ready(function(){

            Ext.grid.defaultColumn = function(config){
                Ext.apply(this, config);
                if(!this.id){
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
                    if( t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1 )
                    {
                        e.stopEvent();
                        var index = this.grid.getView().findRowIndex(t);
                        var record = this.grid.store.getAt(index);
                        record.set(this.dataIndex, !record.data[this.dataIndex]);
                        Ext.Ajax.request({
                            waitMsg: 'Saving changes...',
                            url: rlUrlHome + 'controllers/data_formats.inc.php?q=ext',
                            method: 'GET',
                            params:
                            {
                                action: 'update',
                                id: record.id,
                                field: this.dataIndex,
                                value: record.data[this.dataIndex]
                            },
                            failure: function()
                            {
                                Ext.MessageBox.alert('Error saving changes...');
                            },
                            success: function()
                            {
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

            itemsGrid = new gridObj({
                key: 'data_items',
                id: 'grid',
                ajaxUrl: rlUrlHome + 'controllers/data_formats.inc.php?q=ext&format='+format,
                defaultSortField: 'name',
                remoteSortable: true,
                checkbox: true,
                actions: mass_actions,
                title: lang['ext_format_items_manager'],
                fields: [
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'},
                    {name: 'Rate', mapping: 'Rate', xtype: 'float', format:'0.000'},
                    {name: 'Default', mapping: 'Default'}
                ],
                columns: [
                    {
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        width: 40,
                        id: 'rlExt_item_bold'
                    },{/literal}{if $format_info.Conversion}{literal}{
                        header: lang['ext_conversion_rate'],
                        dataIndex: 'Rate',
                        width: 70,
                        fixed: true,
                        editor: new Ext.form.NumberField({
                            allowBlank: false,
                            allowDecimals: true,
                            decimalPrecision: 10
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{/literal}{/if}
                    {if $format_info.Order_type == 'position'}{literal}{
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
                    },{/literal}{/if}{literal}
                    defaultColumn,
                    {
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 80,
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
                        renderer: function(data) {
                            var out = "<center>";

                            if ( rights[cKey].indexOf('edit') >= 0 )
                            {
                                out += "<img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' onClick='xajax_prepareEdit(\""+data+"\", \""+format+"\");$(\"#edit_item\").slideDown(\"normal\");$(\"#new_item\").slideUp(\"fast\");$(\"#ei_loading\").fadeIn(\"fast\")' />";
                            }
                            if ( rights[cKey].indexOf('delete') >= 0 )
                            {
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete']+"\", \"xajax_deleteItem\", \""+Array(data,format)+"\" )' />";
                            }
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });

            itemsGrid.plugins.push(defaultColumn);

            {/literal}{rlHook name='apTplDataFormatsItemsGrid'}{literal}

            itemsGrid.init();
            grid.push(itemsGrid.grid);

            // actions listener
            itemsGrid.actionButton.addListener('click', function()
            {
                var sel_obj = itemsGrid.checkboxColumn.getSelections();
                var action = itemsGrid.actionsDropDown.getValue();

                if (!action)
                {
                    return false;
                }

                for( var i = 0; i < sel_obj.length; i++ )
                {
                    itemsGrid.ids += sel_obj[i].id;
                    if ( sel_obj.length != i+1 )
                    {
                        itemsGrid.ids += '|';
                    }
                }

                switch (action){
                    case 'delete':
                        Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn){
                            if ( btn == 'yes' )
                            {
                                xajax_dfItemsMassActions( itemsGrid.ids, action );
                                itemsGrid.store.reload();
                            }
                        });

                        break;
                    default:
                        xajax_dfItemsMassActions( itemsGrid.ids, action );
                        itemsGrid.store.reload();
                        break;
                }

                itemsGrid.checkboxColumn.clearSelections();
                itemsGrid.actionsDropDown.setVisible(false);
                itemsGrid.actionButton.setVisible(false);
            });

        });
        {/literal}
    {else}
        {literal}

        var dataFormatGrid;

        $(document).ready(function(){

            dataFormatGrid = new gridObj({
                key: 'data_formats',
                id: 'grid',
                ajaxUrl: rlUrlHome + 'controllers/data_formats.inc.php?q=ext',
                defaultSortField: 'name',
                title: lang['ext_data_formats_manager'],
                fields: [
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Order_type', mapping: 'Order_type', type: 'string'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [
                    {
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        width: 40,
                        id: 'rlExt_item_bold'
                    },{
                        header: {/literal}'{$lang.order_type}'{literal},
                        dataIndex: 'Order_type',
                        width: 90,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['alphabetic', {/literal}'{$lang.alphabetic_order}'{literal}],
                                ['position', {/literal}'{$lang.position_order}'{literal}]
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus:true
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 80,
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
                        width: 90,
                        fixed: true,
                        dataIndex: 'Key',
                        sortable: false,
                        renderer: function(data) {
                            var manage_link = data == 'years' ? "eval(Ext.MessageBox.alert(\""+lang['ext_notice']+"\", \""+lang['ext_data_format_auto']+"\"))" : '';
                            var manage_href = data == 'years' ? "javascript:void(0)" : rlUrlHome+"index.php?controller="+controller+"&mode=manage&format="+data;
                            var out = "<center>";
                            var splitter = false;

                            if ( rights[cKey].indexOf('edit') >= 0 )
                            {
                                out += "<a href="+manage_href+" onclick='"+manage_link+"'><img class='manage' ext:qtip='"+lang['ext_manage']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                                out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit&format="+data+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            }

                            if ( rights[cKey].indexOf('delete') >= 0 )
                            {
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteFormat\", \""+data+"\" )' />";
                            }
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });

            {/literal}{rlHook name='apTplDataFormatsGrid'}{literal}

            dataFormatGrid.init();
            grid.push(dataFormatGrid.grid);

        });
        {/literal}
    {/if}
    //]]>
    </script>
    <!-- data formats grid end -->

    {rlHook name='apTplDataFormatsBottom'}
{/if}

<!-- data formats tpl end -->
