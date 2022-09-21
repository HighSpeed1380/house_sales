<!-- listing fields groups tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplListingGroupsNavBar'}
    
    {if $aRights.$cKey.add}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_group}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.groups_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add new group -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;group={$smarty.get.group}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.key}</td>
            <td class="field">
                <input {if $smarty.get.action == 'edit'}readonly{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
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
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        
        <tr>
            <td class="name">{$lang.default_expand}</td>
            <td class="field">
                <select name="display">
                    <option value="1" {if $sPost.display == '1'}selected="selected"{/if}>{$lang.yes}</option>
                    <option value="0" {if $sPost.display == '0'}selected="selected"{/if}>{$lang.no}</option>
                </select>
            </td>
        </tr>
        
        {rlHook name='apTplListingGroupsForm'}
        
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
    <!-- add new group end -->

{else}

    <!-- listing groups grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var listingGroupsGrid;
    
    {literal}
    $(document).ready(function(){
        
        listingGroupsGrid = new gridObj({
            key: 'listingGroups',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/listing_groups.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_listing_groups_manager'],
            remoteSortable: false,
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Display', mapping: 'Display'},
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
                    header: lang['ext_default_display'],
                    dataIndex: 'Display',
                    width: 12,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
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
                    width: 10,
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
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&group="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteFGroup\", \""+Array(data)+"\", \"section_load\" )' />";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplListingGroupsGrid'}{literal}
        
        listingGroupsGrid.init();
        grid.push(listingGroupsGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- listing groups grid end -->

    {rlHook name='apTplListingGroupsBottom'}
    
{/if}

<!-- listing fields groups tpl end -->
