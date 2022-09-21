<!-- custom categories tpl -->

{if $allow_tmp}
    <!-- categories grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var listingGroupsGrid;
    
    {literal}
    $(document).ready(function(){
        
        categoriesGrid = new gridObj({
            key: 'customCategories',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/custom_categories.inc.php?q=ext',
            defaultSortField: 'Name',
            title: lang['ext_categories_manager'],
            remoteSortable: false,
            fields: [
                {name: 'Name', mapping: 'Name'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Parent', mapping: 'Parent'},
                {name: 'Parent_ID', mapping: 'Parent_ID'},
                {name: 'Account_ID', mapping: 'Account_ID'},
                {name: 'Username', mapping: 'Username'},
                {name: 'Listing_ID', mapping: 'Listing_ID'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'ID', mapping: 'ID'}
            ],
            columns: [
                {
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
                    header: lang['ext_listing_id'],
                    dataIndex: 'Listing_ID',
                    width: 15,
                    id: 'rlExt_black_bold',
                    renderer: function(value, param1, row){
                        if ( parseInt(value) )
                        {
                            value = '<a href="'+rlUrlHome+'index.php?controller=listings&action=view&id='+row.data.Listing_ID+'" target="_blank"><img ext:qtip="'+lang['ext_click_to_view_details']+'" class="view grid-icon" src="'+rlUrlHome+'img/blank.gif" alt="" /></a> ' + value;
                        }
                        return value;
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 15
                },{
                    header: lang['ext_parent'],
                    dataIndex: 'Parent',
                    id: 'rlExt_item',
                    width: 15,
                    renderer: function(value, obj, row){
                        return '<a target="_blank" href="'+rlUrlHome+'index.php?controller=browse&id='+row.data.Parent_ID+'">'+value+'</a>';
                    }
                },{
                    header: lang['ext_owner'],
                    dataIndex: 'Username',
                    width: 15,
                    renderer: function(username, ext, row){
                        if ( username )
                        {
                            return "<a class='green_11_bg' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</span>"
                        }
                        else
                        {
                            return lang['ext_visitor_incomplete'];
                        }
                    }
                },{
                    header: lang['ext_join_date'],
                    dataIndex: 'Date',
                    width: 13,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        
                        out += "<img class='activate' ext:qtip='"+lang['ext_activate']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_activate_category_notice']+"\", \"xajax_activateTmpCategory\", \""+Array(data)+"\", \"section_load\" )' class='delete' />";
                        out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteTmpCategory\", \""+Array(data)+"\", \"section_load\" )' />";
                        
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplCustomCategoriesGrid'}{literal}
        
        categoriesGrid.init();
        grid.push(categoriesGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- categories grid end -->
    
    {rlHook name='apTplCustomCategoriesBottom'}
{/if}

<!-- custom categories tpl end -->
