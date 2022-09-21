    <!-- register fields tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplAccountFieldsNavBar'}

    {if !isset($smarty.get.action)}
        <a href="javascript:void(0)" onclick="show('search', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
    {/if}
    
    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_field}</span><span class="right"></span></a>
    {/if}
    
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.fields_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<div id="action_blocks">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields'|cat:$smarty.const.RL_DS|cat:'search_form.tpl'}
</div>

{if $smarty.get.action}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields'|cat:$smarty.const.RL_DS|cat:'add_edit_form.tpl'}

{else}

    <!-- account fields grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var accountFieldsGrid;
    
    {literal}
    $(document).ready(function(){
        
        accountFieldsGrid = new gridObj({
            key: 'accountFields',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/account_fields.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_account_fields_manager'],
            remoteSortable: true,
            filters: cookie_filters,
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Required', mapping: 'Required'},
                {name: 'Map', mapping: 'Map'},
                {name: 'Short_form', mapping: 'Short_form'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Key', mapping: 'Key'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    id: 'rlExt_item',
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    fixed: true,
                    width: 150,
                },{
                    header: lang['ext_required_field'],
                    dataIndex: 'Required',
                    fixed: true,
                    width: 110,
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
                    renderer: function(val, ext, row){
                        var hint = row.data.Type != '{/literal}{$lang.type_accept}{literal}' 
                        ? lang['ext_click_to_edit']
                        : lang['ext_accept_must_be_required'];

                        return '<span ext:qtip="' + hint + '">' + val + '</span>';
                    }
                },{
                    header: '{/literal}{$lang.google_map}{literal}',
                    dataIndex: 'Map',
                    fixed: true,
                    width: 110,
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
                        selectOnFocus: true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
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
                    renderer: function(data) {
                        var out = "<center>";
                        var splitter = false;
                        
                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&field="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_delete']+"\", \"xajax_deleteAField\", \""+Array(data)+"\", \"field_load\" )' />";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplAccountFieldsGrid'}{literal}
        
        accountFieldsGrid.init();
        grid.push(accountFieldsGrid.grid);

        // prevent of disabling the option "Required" for agreement fields
        accountFieldsGrid.grid.addListener('beforeedit', function(editEvent){
            if (editEvent.field == 'Required' 
                && editEvent.record.data.Type == '{/literal}{$lang.type_accept}{literal}'
            ) {
                editEvent.cancel = true;
                accountFieldsGrid.store.rejectChanges();
            }
        });
    });
    {/literal}
    //]]>
    </script>
    <!-- account fields grid end -->
    
    {rlHook name='apTplAccountFieldsBottom'}

{/if}

<!-- register fields tpl end -->
