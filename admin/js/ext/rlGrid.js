
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLGRID.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

var grid = new Array();

/**
* Flynax ext class
* ext version: 3.3.1
**/
var gridObj = function(properties){
    
    // common options
    this.key = 'grid';
    this.id = 'grid';
    this.title = false;
    this.perPage = 20;
    this.fields = Array();
    this.store = false;
    this.ajaxUrl = '';
    this.urlString = '';
    this.ajaxMethod = 'GET';
    this.updateMethod = 'GET';
    this.fieldID = 'ID';
    this.columns = Array();
    this.columnModel = false;
    this.fitToResize = new Ext.ux.FitToParent();
    this.toolBar = false;
    this.toolBarItems = Array();
    this.grid = false;
    this.actionButton = false;
    this.restoreButton = false;
    this.paginationBar = false;
    this.perPageDropDown = false;
    this.perPageDropBar = false;
    this.ids = false;
    this.plugins = [this.fitToResize];
    this.remoteSortable = false;
    this.trackMouse = true;
    this.reloadOnUpdate = false;
    
    // sorting
    this.defaultSortField = false;
    this.defaultSortType = 'ASC';
    
    // filters
    this.filters = Array();
    this.filtersPrefix = false;
    
    // checkbox column
    this.checkbox = false;
    this.checkboxColumn = false;
    
    // action options
    this.actions = Array();
    this.actionsDropDown = false;
    this.affectedObjects = false;
    this.actionsLastValue = false;
    
    // cache options
    this.cacheMove = false;
    this.cacheVisibility = false;
    
    // expander options
    this.expander = false;
    this.expanderTpl = false;

    this.init = function(){
        this.extend();
        this.prepare();
        this.callExt();
    };
    
    this.prepare = function(){
        var self = this;
        
        // clera workspace
        $('#'+this.id).html('');
        this.toolBarItems = new Array();
        
        /* add renderer to status field */
        for (var h = 0; h < this.columns.length; h++) {
            if (rights[cKey] && this.columns[h].editor) {
                if ((typeof rights[cKey] == 'object' && rights[cKey].indexOf('edit') < 0)
                    || (typeof rights[cKey] == 'string' && rights[cKey] !== 'true')
                    ) {
                this.columns[h].editor = null;
                lang['ext_click_to_edit'] = '';
            }
            }

            if (this.columns[h].dataIndex === 'Status' && !this.columns[h].renderer) {
                this.columns[h].renderer = function(val, param1){
                    if (val === lang.active) {
                        param1.style += 'background: #d2e798;';
                    } else if (val === lang.approval) {
                        param1.style += 'background: #ffe7ad;';
                    } else if (val === lang.expired) {
                        param1.style += 'background: #fbc4c4;';
                    } else if (val === lang.new) {
                        param1.style += 'background: #fbc4c4;';
                    } else if (val === lang.reviewed) {
                        param1.style += 'background: #d2e798;';
                    } else if (val === lang.pending || val === lang.replied) {
                        param1.style += 'background: #c0ecee;';
                    } else if (val === lang.incomplete
                        || val === lang.canceled
                        || val === lang.plugin_not_compatible
                    ) {
                        param1.style += 'background: #e0e0e0;';
                    } else if (val === 'not_installed' || val === 'pending') {
                        param1.style += 'background: #f9cece;';
                        val = lang.not_installed;
                    }

                    if (rights[cKey] && rights[cKey].indexOf('edit') >= 0) {
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';                     
                    } else {
                        return val;
                    }
                };
            }
        }
        
        /* call namespace */
        Ext.namespace('Ext.exampledata');
        
        /* checkboxes model */
        if ( this.checkbox )
        {
            // create checkboxes column
            this.checkboxColumn = new Ext.grid.CheckboxSelectionModel({
                'width': 21
            });
            
            this.columns.splice(0, 0, this.checkboxColumn);
            
            // create actions
            if ( this.actions[0] != '' )
            {
                Ext.exampledata.mass_items = this.actions;
            
                var mass_items = new Ext.data.SimpleStore({
                    fields: ['value', 'key'],
                    data : Ext.exampledata.mass_items
                });
                
                this.actionsDropDown = new Ext.form.ComboBox({          
                    store: mass_items,
                    displayField: 'value',
                    valueField: 'key',
                    typeAhead: true,
                    mode: 'local',
                    triggerAction: 'all',
                    hidden: true,
                    selectOnFocus: true,
                    emptyText: lang['ext_select_action']
                });
                
                this.toolBarItems.push(this.actionsDropDown);
                
                // create action button
                this.actionButton = new Ext.Toolbar.Button({
                    text: 'Go',
                    hidden: true,
                    style: 'margin: 0 5px;'
                });
                
                this.toolBarItems.push(this.actionButton);
            }
        }
        
        var isRestoreButtonVisible = true;
        // create restore cookies button
        if ( readCookie(this.key+'_columnPositions') || readCookie(this.key+'_columnWidth') || readCookie(this.key+'_columnVisibility') /*|| readCookie(this.key+'_columnOrder')*/ )
        {
            isRestoreButtonVisible = false;
        }
        
        this.restoreButton = new Ext.Toolbar.Button({
            text: lang['ext_restore_options'],
            tooltip: lang['ext_restore_options_title'],
            style: 'margin: 0 0 0 20px;',
            hidden: isRestoreButtonVisible,
            handler: function(){
                Ext.MessageBox.confirm('Confirm', lang['ext_restore_options_confirm'], function(btn){
                    if ( btn == 'yes' )
                    {
                        self.restore();
                    }
                });
            }
        });
        
        this.toolBarItems.push(this.restoreButton);
        
        // filters handler
        if ( this.filters[0] )
        {
            this.urlString = '&';
            for (var i = 0; i < this.filters.length; i++)
            {
                if ( this.filters[i].indexOf('||') >= 0 )
                {
                    this.filters[i] = this.filters[i].split('||');
                }
                
                this.urlString += this.filtersPrefix ? 'f_' : '';
                this.urlString += this.filters[i][0]+'='+this.filters[i][1];
                
                if ( i != this.filters.length -1 )
                {
                    this.urlString += '&';
                }
            }
        }
        else
        {
            this.urlString = '';
        }
    };
    
    this.renderFilters = function(){
        if ( this.filters[0] )
        {
            this.urlString = '&';
            for (var i = 0; i < this.filters.length; i++)
            {
                //var f_item = this.filters[i].split('||');
                this.urlString += this.filtersPrefix ? 'f_' : '';
                this.urlString += this.filters[i][0]+'='+this.filters[i][1];
                
                if ( i != this.filters.length -1 )
                {
                    this.urlString += '&';
                }
            }
        }
        else
        {
            this.urlString = '';
        }
        
        var rendered_url = this.ajaxUrl + this.urlString;
        this.toolBar.store.proxy.setUrl(rendered_url);
        
        this.toolBar.store.proxy.api.create.url = rendered_url;
        this.toolBar.store.proxy.api.destroy.url = rendered_url;
        this.toolBar.store.proxy.api.read.url = rendered_url;
        this.toolBar.store.proxy.api.update.url = rendered_url;
        
        this.grid.store.proxy.setUrl(rendered_url);
    };
    
    this.callExt = function(){
        
        /* init quick tops */
        Ext.QuickTips.init();
        Ext.apply(Ext.QuickTips.getQuickTip(), {
            showDelay: 50,
            trackMouse: true
        });
        
        /* init data store */
        this.store = new Ext.data.Store({
            proxy: new Ext.data.HttpProxy({
                url: this.ajaxUrl+this.urlString,
                method: this.ajaxMethod
            }),

            // create reader that reads the Topic records
            reader: new Ext.data.JsonReader({
                root: 'data',
                totalProperty: 'total',
                remoteSort: true,
                id: this.fieldID
                }, Ext.data.Record.create(this.fields)
            ),
            remoteSort: this.remoteSortable
        });
        
        // set filed for default sort
        if ( this.defaultSortField )
        {
            var sortCookie = readCookie(this.key+'_columnOrder');
            if ( sortCookie )
            {
                sortCookie = sortCookie.split('|');
                this.store.setDefaultSort(sortCookie[0], sortCookie[1]);
            }
            else
            {
                this.store.setDefaultSort(this.defaultSortField, this.defaultSortType);
            }
        }
        
        // row expander
        if ( this.expander && this.expanderTpl )
        {
            var expander = new Ext.grid.RowExpander({
                tpl : new Ext.Template(this.expanderTpl)
            });
            
            this.columns.splice(this.checkbox ? 1 : 0, 0, expander);
            this.plugins.push(expander);
        }
        
        // create the column model
        this.columnModel = new Ext.grid.ColumnModel({
            defaults: {
                sortable: true
            },
            columns: this.columns
        });
        
        // items per page selector
        Ext.exampledata.perPageMapping = [
            [10, 10],
            [20, 20],
            [50, 50],
            [100, 100],
            [1000, 1000]
        ];
        
        var perPageMapping = new Ext.data.SimpleStore({
            fields: ['value', 'key'],
            data : Ext.exampledata.perPageMapping
        });
        
        // per page items
        this.perPage = readCookie(this.key+'_pp') ? readCookie(this.key+'_pp') : this.perPage;
        this.perPage = parseInt(this.perPage);
        
        this.perPageDropDown = new Ext.form.ComboBox({          
            store: perPageMapping,
            width: 50,
            displayField: 'value',
            valueField: 'key',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',
            selectOnFocus: true,
            emptyText: this.perPage
        });
        
        this.perPageBar = new Ext.Toolbar({
            border: false,
            style: 'border: 0;margin: 0 5px;',
            items: [this.perPageDropDown]
        });
        
        this.toolBarItems.splice(0, 0, this.perPageBar);
        
        // create pagination toolbar
        this.toolBar = new Ext.PagingToolbar({
            beforePageText: lang['ext_page'],
            afterPageText: lang['ext_of']+' {0}',
            pageSize: this.perPage,
            store: this.store,
            displayInfo: true,
            displayMsg: lang['ext_display_items'],
            emptyMsg: lang['ext_no_items'],
            items: this.toolBarItems
        });

        this.grid = new Ext.grid.EditorGridPanel({
            el: this.id,
            autoHeight: true,
            autoWidth: true,
            title: this.title,
            store: this.store,
            clicksToEdit: 1,
            cm: this.columnModel,
            sm: this.checkboxColumn,
            trackMouseOver: this.trackMouse,
            loadMask: {msg: lang['loading']},
            plugins: this.plugins,
            viewConfig: {
                forceFit: true,
                enableRowBody: true,
                showPreview: false,
                emptyText: lang['ext_empty_grid_message']
            },
            bbar: this.toolBar
        });
    
        // render the grid
        this.grid.render();

        // trigger the data store load
        var startPos = readCookie(this.key+'_pn') ? parseInt(readCookie(this.key+'_pn')) : 0;
        startPos = startPos * this.perPage;
        this.store.load({params:{start:startPos, limit:this.perPage}});
        
        this.saveCache();
        this.setConfigs();
        
        // edit values listener
        var self = this;
        this.grid.addListener('afteredit', function(editEvent)
        {
            Ext.Ajax.request({
                waitMsg: lang['ext_saving_changes'],
                url: self.ajaxUrl,
                method: self.updateMethod,
                params:
                {
                    action: 'update',
                    type: editEvent.grid.type,
                    id: editEvent.record.id,
                    field: editEvent.field,
                    value: editEvent.value
                },
                failure: function()
                {
                    Ext.MessageBox.alert(lang['ext_error_saving_changes']);
                },
                success: function(response)
                {
                    if ( response.responseText == 'session_expired' )
                    {
                        location.href = rlUrlHome+'index.php?session_expired';
                    }
                    
                    if ( self.reloadOnUpdate )
                    {
                        self.reload();
                    }
                    else
                    {
                        var field = editEvent.field;
                        var index = self.columnModel.findColumnIndex(field);
                        var row = self.columnModel.getColumnAt(index);
                        var new_val = row.editor.lastSelectionText;
                        
                        if ( new_val )
                        {
                            eval("editEvent.record.data."+field+" = '"+new_val+"'");
                        }
                        
                        self.grid.store.commitChanges();
                        editEvent.record.commit();
                    }
                }
            });
        });
        
        // per page selector listener
        this.perPageDropDown.addListener('select', function(editEvent)
        {
            if (editEvent.value != self.perPage)
            {
                self.perPage = editEvent.value;
                self.toolBar.pageSize = self.perPage;
                self.toolBar.doLoad(0);
                createCookie(self.key+'_pp', self.perPage, 365);
                if ( self.urlString != '')
                {
                    self.grid.store.proxy.setUrl(self.ajaxUrl + self.urlString);
                }
                else
                {
                    self.grid.store.proxy.setUrl(self.ajaxUrl);
                }
                self.grid.store.load({params:{start:0, limit:self.perPage}});
            }
        });
        
        // checkbox listeners
        if ( this.checkboxColumn )
        {
            this.checkboxColumn.addListener('rowselect', function()
            {
                self.ids = '';
                if( self.actionsDropDown.hidden )
                {
                    self.actionsDropDown.setVisible(true);
                    self.actionButton.setVisible(true);
                }
            });
        
            this.checkboxColumn.addListener('rowdeselect', function()
            {
                if( self.checkboxColumn.getCount() == 0 && !self.actionsDropDown.hidden )
                {
                    self.actionsDropDown.setVisible(false);
                    self.actionButton.setVisible(false);
                    if ( self.affectedObjects )
                    {
                        $(self.affectedObjects).fadeOut('fast');
                    }
                }
            });
        }
        
        // actions dropdown listener
        if ( this.actionsDropDown )
        {
            this.actionsDropDown.addListener('select', function(event){
                if ( self.actionsLastValue != event.value )
                {
                    $(self.affectedObjects).fadeOut('fast');
                }
                self.actionsLastValue = event.value;
            });
        }
        
        // paging listener
        this.toolBar.addListener('change', function(page, param){
            var setPage = param.activePage-1;
            
            if ( param.activePage > param.pages )
            {
                if ( self.urlString != '')
                {
                    self.store.proxy.setUrl(self.ajaxUrl + self.urlString);
                }
                else
                {
                    self.store.proxy.setUrl(self.ajaxUrl);
                }
                self.toolBar.moveFirst();
                
                setPage = 0;
            }
            createCookie(self.key+'_pn', setPage, 365);
        });
        
        // store load listener
        this.store.addListener('load', function(editEvent){
            var subtract = typeof(grid_subtract_width) == 'number' ? grid_subtract_width : 0; 
            self.grid.setSize($(window).width() - 60 - subtract - $('#sidebar').width(), false);
        });
        
        // store exception listener
        this.store.addListener('exception', function(event, param1, param2, param3, response){
            if ( response.responseText == 'session_expired' )
            {
                location.href = rlUrlHome+'index.php?session_expired';
            }
        });
        
        // column positions listener
        this.columnModel.addListener('columnmoved', function(obj, old_pos, new_pos){
            var positions = new Array();
            
            for (var i = 0; i < obj.columns.length; i++ )
            {
                var dataIndex = obj.columns[i].dataIndex == '' ? 'column_'+i : obj.columns[i].dataIndex;
                positions[i] = dataIndex;
            }
            
            positions = self.serialize(positions);
            createCookie(self.key+'_columnPositions', positions, 365);
            
            // show restore button
            self.restoreButton.setVisible(true);
        });
        
        // column visibility change listener
        this.columnModel.addListener('hiddenchange', function(obj, index, is_visibile){
            var cookie = readCookie(self.key+'_columnVisibility');
            
            if ( cookie )
            {
                cookie = self.unserialize(cookie, true);
                cookie[index] = is_visibile;
            }
            else
            {
                cookie = new Array();
                cookie[index] = is_visibile;
            }
            cookie = self.serialize(cookie);
            createCookie(self.key+'_columnVisibility', cookie, 365);
            
            // show restore button
            self.restoreButton.setVisible(true);
        });
        
        // column width change listener
        this.grid.addListener('columnresize', function(index, width){
            var cookie = readCookie(self.key+'_columnWidth');
            
            if ( cookie )
            {
                cookie = self.unserialize(cookie, true);
                cookie[index] = width;
            }
            else
            {
                cookie = new Array();
                cookie[index] = width;
            }
            cookie = self.serialize(cookie);
            createCookie(self.key+'_columnWidth', cookie, 365);
            
            // show restore button
            self.restoreButton.setVisible(true);
        });
        
        // column ordering listener
        this.grid.addListener('sortchange', function(obj, info){
            createCookie(self.key+'_columnOrder', info['field']+'|'+info['direction'], 365);
            
            // show restore button
            /*self.restoreButton.setVisible(true);*/
        });
    };
    
    this.setConfigs = function(){       
        // set columns positions
        var cookie_positions = readCookie(this.key+'_columnPositions');
        var positions = new Array();
        
        if ( cookie_positions )
        {
            cookie_positions = this.unserialize(cookie_positions);
            
            for(var i = 0; i < this.columnModel.columns.length; i++ )
            {
                if ( this.columnModel.columns[i].dataIndex == '' )
                {
                    var dataIndex = 'column_'+i;
                }
                else
                {
                    var dataIndex = this.columnModel.columns[i].dataIndex;
                }
                positions[i] = dataIndex;
            }
            
            for (var k = 0; k < positions.length; k++)
            {
                var cookie_index = positions.indexOf(cookie_positions[k]);
                if ( k != cookie_index )
                {
                    var tmp = positions[k];
                    positions.splice(cookie_index, 1);
                    positions.splice(k, 0, cookie_positions[k]);
                    this.columnModel.moveColumn(cookie_index, k);
                }
            }
        }
        
        // set columns vivibility
        var cookie_visibility = readCookie(this.key+'_columnVisibility');
        
        if ( cookie_visibility )
        {
            cookie_visibility = this.unserialize(cookie_visibility, true);
            for ( var i in cookie_visibility )
            {
                if ( typeof(cookie_visibility[i]) != 'function' )
                {
                    this.columnModel.setHidden(i, cookie_visibility[i] == 'true' ? true : false);
                }
            }
        }
        
        // set columns width
        var cookie_width = readCookie(this.key+'_columnWidth');
        
        if ( cookie_width )
        {
            cookie_width = this.unserialize(cookie_width, true);
            for ( var i in cookie_width )
            {
                if ( typeof(cookie_width[i]) != 'function' )
                {
                    this.columnModel.setColumnWidth(i, parseInt(cookie_width[i]), true);
                }
            }
        }
    };
    
    this.saveCache = function(){
        
        this.cacheMove = new Array();
        this.cacheVisibility = new Array();
        
        for(var i = 0; i < this.columnModel.columns.length; i++ )
        {
            // save positions
            var dataIndex = this.columnModel.columns[i].dataIndex == '' ? 'column_'+i : this.columnModel.columns[i].dataIndex;
            this.cacheMove[i] = dataIndex;
            
            // save visiblity
            this.cacheVisibility[i] = this.columnModel.isHidden(i);
        }
    };
    
    this.restore = function(){
        // restore positions
        var cookie_positions = readCookie(this.key+'_columnPositions');
        
        if ( cookie_positions )
        {
            cookie_positions = this.unserialize(cookie_positions);
            this.cacheMove
            for (var k = 0; k < cookie_positions.length; k++)
            {
                var cache_index = cookie_positions.indexOf(this.cacheMove[k]);
                if ( k != cache_index )
                {
                    var tmp = cookie_positions[k];
                    cookie_positions.splice(cache_index, 1);
                    cookie_positions.splice(k, 0, this.cacheMove[k]);
                    this.columnModel.moveColumn(cache_index, k);
                }
            }
        }
        
        // restore visibility
        var cookie_visiblity = readCookie(this.key+'_columnVisibility');
        
        if ( cookie_visiblity )
        {
            for ( var i in this.cacheVisibility )
            {
                if ( typeof(this.cacheVisibility[i]) != 'function' )
                {
                    this.columnModel.setHidden(i, this.cacheVisibility[i]);
                }
            }
        }
        
        eraseCookie(this.key+'_columnPositions');
        eraseCookie(this.key+'_columnWidth');
        eraseCookie(this.key+'_columnVisibility');
        /*eraseCookie(this.key+'_columnOrder');*/
        
        this.restoreButton.setVisible(false);
    };
    
    this.reload = function(){
        this.ids = '';
        this.renderFilters();
        this.store.reload();
    };
    
    this.reset = function(){
        this.urlString = '';
        this.filters = false;
        this.renderFilters();
        this.store.proxy.setUrl(this.ajaxUrl);
        this.store.reload();
    };
    
    this.destroy = function(){
        this.urlString = '';
        this.store.proxy.setUrl(this.ajaxUrl);
        this.grid.destroy();
    };
    
    this.resetPage = function(){
        eraseCookie(this.key+'_pn');
        this.toolBar.moveFirst();
    };
    
    this.extend = function(){
        for (var i in properties)
        {
            eval(" \
            if ( typeof(this."+i+") != 'undefined' ) \
            { \
                this."+i+" = properties[i]; \
            } \
            ");
        }
    };
            
    this.getInstance = function(){
        return properties;
    };
    
    this.serialize = function( array ){
        var out = '';
        for (var i in array)
        {
            if ( typeof(array[i]) != 'function' )
            {
                out += i+':'+array[i]+'|';
            }
        }
        out = out.substr(0, out.length-1);
        
        return out;
    };
    
    this.unserialize = function( string, is_array ){
        if ( !string )
        {
            return false;
        }
        
        var array = string.split('|');
        var out = new Array();
        
        if ( array[0] != '' )
        {
            for ( var i in array )
            {
                if ( typeof(array[i]) != 'function' )
                {
                    if ( is_array )
                    {
                        out[array[i].split(':')[0]] = array[i].split(':')[1];
                    }
                    else
                    {
                        out.push(array[i].split(':')[1]);
                    }
                }
            }
        }
        
        return out;
    };
}

/* fit to parent */
Ext.namespace('Ext.ux');
Ext.ux.FitToParent = Ext.extend(Object, {
    constructor: function(config) {
        config = config || {};
        if(config.tagName || config.dom || Ext.isString(config)){
            config = {parent: config};
        }
        Ext.apply(this, config);
    },
    init: function(c) {
        this.component = c;
        c.on('render', function(c) {
            this.parent = Ext.get(this.parent || c.getPositionEl().dom.parentNode);
            if(c.doLayout){
                c.monitorResize = true;
                c.doLayout = c.doLayout.createInterceptor(this.fitSize, this);
            } else {
                this.fitSize();
                Ext.EventManager.onWindowResize(this.fitSize, this);
            }
        }, this, {single: true});
    },
    fitSize: function() {
        var subtract = typeof(grid_subtract_width) == 'number' ? grid_subtract_width : 0; 
        var pos = $(window).width() - 60 - subtract - $('#sidebar').width();
        this.component.setSize(pos, null);
    }
});
Ext.preg('fittoparent', Ext.ux.FitToParent);

/* button panel */
var buttonPanel = Ext.extend(Ext.Panel, {
    layout: 'table',
    defaultType: 'button',
    baseCls: 'x-plain',
    cls: 'btn-panel',
    constructor: function(button, id){
        this.renderTo = id;
        
        buttonPanel.superclass.constructor.call(this, {
            items: button
        });
    }
});
