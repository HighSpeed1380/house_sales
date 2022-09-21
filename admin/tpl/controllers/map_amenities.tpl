<!-- map amenities tpl -->

<!-- navigation bar -->
{if $aRights.$cKey.add}
    {rlHook name='apTplMapAmenitiesNavBar'}
    
    <div id="nav_bar">
        <a href="javascript:void(0)" onclick="show('new_item');$('#edit_item').slideUp('fast');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_amenity}</span><span class="right"></span></a>
    </div>
{/if}
<!-- navigation bar end -->

<!-- add new item -->
<div id="new_item" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}
    <form onsubmit="addItem();$('input[name=add_item_submit]').val('{$lang.loading}');return false;" action="" method="post">
    <table class="form">    
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
                <input id="ni_{$language.Code}" type="text" style="width: 250px;" />
                {if $allLangs|@count > 1}
                    <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                </div>
                {/if}
            {/foreach}
        </td>
    </tr>
    
    {rlHook name='apTplMapAmenitiesAddField'}
    
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" name="add_item_submit" value="{$lang.add}" />
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
    <form onsubmit="editItem();$('input[name=edit_item_submit]').val('{$lang.loading}');return false;" action="" method="post">
    <table class="form">    
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
                <input lang="{$language.Code}" id="ei_{$language.Code}" type="text" style="width: 250px;" />
                {if $allLangs|@count > 1}
                    <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                </div>
                {/if}
            {/foreach}
        </td>
    </tr>
    
    {rlHook name='apTplMapAmenitiesEditField'}
    
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" name="edit_item_submit" value="{$lang.edit}" />
            <a onclick="$('#edit_item').slideUp('normal')" href="javascript:void(0)" class="cancel">{$lang.close}</a>
        </td>
    </tr>
    </table>
    </form>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
</div>
<!-- edit item end -->

{literal}
<script type="text/javascript">
var editKey = '';

var addItem = function()
{
    var names = new Array();
    {/literal}
    {foreach from=$allLangs item='languages'}
    names['{$languages.Code}'] = $('#ni_{$languages.Code}').val();
    {/foreach}
    {literal}

    xajax_addAmenity(names);
}

var prepareEdit = function(key)
{
    $('#new_item').slideUp('fast');
    $('#edit_item').slideDown();
    
    $('#edit_item input[type=text]').flPhrase({
        key: 'map_amenities+name+'+key
    });
    
    editKey = key;
}

var editItem = function()
{
    var names = new Array();
    {/literal}
    {foreach from=$allLangs item='languages'}
    names['{$languages.Code}'] = $('#ei_{$languages.Code}').val();
    {/foreach}
    {literal}

    xajax_editAmenity(editKey, names);
}
</script>
{/literal}

<!-- amenities grid -->
<div id="grid"></div>
<script type="text/javascript">//<![CDATA[
var listingGroupsGrid;

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
                    url: rlUrlHome + 'controllers/map_amenities.inc.php?q=ext',
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
                        mapAmenitiesGrid.store.commitChanges();
                        mapAmenitiesGrid.reload();
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
        width: 5
    });
    
    mapAmenitiesGrid = new gridObj({
        key: 'mapAmenities',
        id: 'grid',
        ajaxUrl: rlUrlHome + 'controllers/map_amenities.inc.php?q=ext',
        defaultSortField: 'name',
        title: lang['ext_amenities_manager'],
        fields: [
            {name: 'name', mapping: 'name', type: 'string'},
            {name: 'Position', mapping: 'Position', type: 'int'},
            {name: 'Status', mapping: 'Status'},
            {name: 'Default', mapping: 'Default'},
            {name: 'Key', mapping: 'Key'}
        ],
        columns: [
            {
                header: lang['ext_name'],
                dataIndex: 'name',
                width: 60,
                id: 'rlExt_item_bold'
            },defaultColumn,{
                header: lang['ext_position'],
                dataIndex: 'Position',
                width: 6,
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
                        out += '<img class="edit" ext:qtip="'+lang['ext_edit']+'" src="'+rlUrlHome+'img/blank.gif" onclick="prepareEdit(\''+data+'\')" />';
                    }
                    if ( rights[cKey].indexOf('delete') >= 0 )
                    {
                        out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteAmenity\", \""+Array(data)+"\", \"section_load\" )' />";
                    }
                    out += "</center>";
                    
                    return out;
                }
            }
        ]
    });
    
    mapAmenitiesGrid.plugins.push(defaultColumn);
    
    {/literal}{rlHook name='apTplMapAmenitiesGrid'}{literal}
    
    mapAmenitiesGrid.init();
    grid.push(mapAmenitiesGrid.grid);
    
});
{/literal}
//]]>
</script>
<!-- amenities grid end -->

{rlHook name='apTplMapAmenitiesBottom'}

<!-- map amenities tpl end -->
