<!-- listing fields tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplListingFieldsNavBar'}

    {if !isset($smarty.get.action)}
        <a href="javascript:void(0)" onclick="show('search', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
    {/if}
    
    {if $aRights.$cKey.add && $smarty.get.action != 'add'}
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
    
    <!-- listing fields grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var listingFieldsGrid;
    
    {literal}
    $(document).ready(function(){
        
        listingFieldsGrid = new gridObj({
            key: 'listingFields',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/listing_fields.inc.php?q=ext',
            defaultSortField: 'name',
            remoteSortable: true,
            title: lang['ext_listing_fields_manager'],
            filters: cookie_filters,
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Required', mapping: 'Required'},
                {name: 'Add_page', mapping: 'Add_page'},
                {name: 'Details_page', mapping: 'Details_page'},
                {name: 'Map', mapping: 'Map'},
                {name: 'Arrange', mapping: 'Arrange'},
                {name: 'Status', mapping: 'Status'},
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
                    renderer: function(data, ext, row) {
                        var out = "<center>";
                        var splitter = false;
                        var mess = '';
                        
                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&field="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            if ( row.data.Arrange )
                            {
                                 mess = '<br /><br />'+lang['ext_field_in_type_arrange_warning'].replace('{type}', row.data.Arrange);
                            }
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_delete']+mess+"\", \"xajax_deleteLField\", \""+Array(data)+"\", \"field_load\" )' />";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplListingFieldsGrid'}{literal}
        
        listingFieldsGrid.init();
        grid.push(listingFieldsGrid.grid);

        // prevent of disabling the option "Required" for agreement fields
        listingFieldsGrid.grid.addListener('beforeedit', function(editEvent){
            if (editEvent.field == 'Required' 
                && editEvent.record.data.Type == '{/literal}{$lang.type_accept}{literal}'
            ) {
                editEvent.cancel = true;
                listingFieldsGrid.store.rejectChanges();
            }
        });
    });
    {/literal}
    //]]>
    </script>
    <!-- listing fields grid end -->
    
    {rlHook name='apTplListingFieldsBottom'}

{/if}

<!-- listing fields tpl end -->
