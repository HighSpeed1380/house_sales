<!-- trash box tpl -->

{if $config.trash}

    <!-- navigation bar -->
    <div id="nav_bar">
        {rlHook name='apTplTrashNavBar'}
        
        <a href="javascript:void(0)" onclick="rlConfirm( '{$lang.clear_trash_nitice}', 'refMethod', '');" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.clear_trash}</span><span class="right"></span></a>
    </div>
    <!-- navigation bar end -->

    <!-- trash grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    {literal}
    var refMethod = function(){
        $('.button_bar span.center_remove').html('{/literal}{$lang.loading}{literal}');
        xajax_clearTrash();
    };
    var trashGrid;
    
    $(document).ready(function(){
        
        trashGrid = new gridObj({
            key: 'trash',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/trash.inc.php?q=ext',
            defaultSortField: 'Date',
            defaultSortType: 'desc',
            checkbox: true,
            actions: [
                [lang['ext_restore'], 'restore'],
                [lang['ext_delete'], 'delete']
            ],
            title: lang['ext_trash_manager'],
            fields: [
                {name: 'Admin', mapping: 'Admin'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Zones', mapping: 'Zones'},
                {name: 'Item', mapping: 'Item'},
                {name: 'ID', mapping: 'ID'}
            ],
            columns: [
                {
                    header: lang['ext_area'],
                    dataIndex: 'Zones',
                    width: 15
                },{
                    id: 'rlExt_item',
                    header: lang['ext_item'],
                    dataIndex: 'Item',
                    width: 60
                },{
                    header: lang['ext_deleted_by'],
                    dataIndex: 'Admin',
                    width: 15
                },{
                    header: lang['ext_delete_date'],
                    dataIndex: 'Date',
                    width: 15,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M')),
                    editor: new Ext.form.DateField({
                        format: 'Y-m-d H:i:s'
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
    
                        out += "<img class='restore' ext:qtip='"+lang['ext_restore']+"' src='"+rlUrlHome+"img/blank.gif' onClick='xajax_restoreTrashItem(\""+data+"\")' />";
                        out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete']+"\", \"xajax_deleteTrashItem\", \""+Array(data)+"\", \"section_load\" )' />";
    
                        out += "</center>";
                    
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplTrashGrid'}{literal}
        
        trashGrid.init();
        grid.push(trashGrid.grid);
        
        /* mass actions listener */
        trashGrid.actionButton.addListener('click', function()
        {
            var sel_obj = trashGrid.checkboxColumn.getSelections();
            var action = trashGrid.actionsDropDown.getValue();

            if (!action)
            {
                return false;
            }
            
            for( var i = 0; i < sel_obj.length; i++ )
            {
                trashGrid.ids += sel_obj[i].id;
                if ( sel_obj.length != i+1 )
                {
                    trashGrid.ids += '|';
                }
            }
            
            if ( action == 'delete' )
            {
                Ext.MessageBox.confirm('Confirm', lang['ext_notice_delete'], function(btn){
                    if ( btn == 'yes' )
                    {
                        xajax_massActions( trashGrid.ids, action );
                        trashGrid.reload();
                    }
                });
            }
            else
            {
                xajax_massActions( trashGrid.ids, action );
                trashGrid.reload();
            }

            trashGrid.checkboxColumn.clearSelections();
            trashGrid.actionsDropDown.setVisible(false);
            trashGrid.actionButton.setVisible(false);
        });
        
    });
    {/literal}
    //]]>
    </script>
    <!-- trash grid end -->
    
    {rlHook name='apTplTrashBottom'}
    
{/if}

<!-- trash box tpl end -->
