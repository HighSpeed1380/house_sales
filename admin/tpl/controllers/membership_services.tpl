<!-- membership services tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplMembershipServiceBar'}
    
    {if $smarty.get.action}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.services_list}</span><span class="right"></span></a> 
    {/if}
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add new/edit service -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;id={$smarty.get.id}{/if}" method="post">
    <input type="hidden" name="submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
        <input type="hidden" name="key" value="{$sPost.Key}" />
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
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        {rlHook name='apTplMembershipServiceForm'}

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
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add new/edit service end -->

{else}

    <!-- membership services grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var membershipServicesGrid;
    
    {literal}
    $(document).ready(function(){
        
        membershipServicesGrid = new gridObj({
            key: 'news',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/membership_services.inc.php?q=ext',
            defaultSortField: 'Position',
            defaultSortType: 'ASC',
            title: lang['ext_membership_services_manager'],
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Price', mapping: 'Price', type: 'float'},
                {name: 'Position', mapping: 'Position', type: 'integer'},
                {name: 'Status', mapping: 'Status'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 10,
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
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        var splitter = false;
                        
                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&id="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplMembershipServicesGrid'}{literal}
        
        membershipServicesGrid.init();
        grid.push(membershipServicesGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- membership services grid end -->
    
    {rlHook name='apTplMembershipServicesBottom'}

{/if}

<!-- membership services tpl end -->
