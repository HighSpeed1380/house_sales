<!-- saved searches tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplSavedSearchesNavBar'}
    
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.searches_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'view'}

    <!-- view details -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    
    <table class="sTatic">
    <tr>
        <td valign="top" style="width: 170px;text-align: right;padding-right: 20px;">
            <a title="{$lang.visit_owner_page}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$profile_data.ID}">
                <img style="display: inline;" {if !empty($profile_data.Photo)}class="thumbnail"{/if} alt="{$lang.seller_thumbnail}" src="{if !empty($profile_data.Photo)}{$smarty.const.RL_FILES_URL}{$profile_data.Photo}{else}{$rlTplBase}img/no-account.png{/if}" />
            </a>

            <ul class="info">
                {if $config.messages_module}<li><input id="contact_owner" type="button" value="{$lang.contact_owner}" /></li>{/if}
            </ul>
        </td>
        <td valign="top">
            <div class="username">{$profile_data.Full_name}</div>
            
            <table class="list" style="margin-bottom: 25px;">
                <tr id="si_field_username">
                    <td class="name">{$lang.username}:</td>
                    <td class="value first">{$profile_data.Username}</td>
                </tr>
                <tr id="si_field_email">
                    <td class="name">{$lang.mail}:</td>
                    <td class="value"><a href="mailto:{$profile_data.Mail}">{$profile_data.Mail}</a></td>
                </tr>
                
                {rlHook name='apTplSavedSearchesUserField'}
            </table>
            
            <div class="username">{$lang.search_criteria}</div>
            {if $saved_search}
                <table class="list">
                {foreach from=$saved_search.fields item='field'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'saved_search_field.tpl'}
                {/foreach}
                
                {rlHook name='apTplSavedSearchesCriteraiField'}
                
                <tr>
                    <td class="name"></td>
                    <td class="value"><input name="search" type="button" value="{$lang.search_and_send}" /></td>
                </tr>
                </table>
            {/if}
        </td>
    </tr>
    </table>
    
    <script type="text/javascript">
    var owner_id = {if $profile_data.ID}{$profile_data.ID}{else}false{/if};
    var search_id = {if $saved_search.ID}{$saved_search.ID}{else}false{/if};
    {literal}
    
    $(document).ready(function(){
        $('#contact_owner').click(function(){
            rlPrompt('{/literal}{$lang.contact_owner}{literal}', 'xajax_contactOwner', owner_id, true);
        });
        
        $('input[name=search]').click(function(){
            rlConfirm("{/literal}{$lang.make_saved_search_notice}{literal}", 'xajax_checkSavedSearch', search_id);
        });
    });
    
    {/literal}
    </script>
    
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- view details end -->
    
    {rlHook name='apTplSavedSearchesViewBottom'}

{else}

    <!-- listing groups grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var savedSearchesGrid;
    
    {literal}
    $(document).ready(function(){
        
        savedSearchesGrid = new gridObj({
            key: 'savedSearches',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/saved_searches.inc.php?q=ext',
            defaultSortField: 'ID',
            title: lang['ext_saved_searches_manager'],
            remoteSortable: false,
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Form_key', mapping: 'Form_key', type: 'string'},
                {name: 'Form_name', mapping: 'Form_name', type: 'string'},
                {name: 'Listing_type', mapping: 'Listing_type', type: 'string'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_username'],
                    dataIndex: 'Username',
                    width: 8,
                    id: 'rlExt_item_bold',
                    renderer: function(username, ext, row){
                        return "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</a>"
                    }
                },
                {
                    header: lang['ext_search_listing_type'],
                    dataIndex: 'name',
                    width: 40,
                    id: 'rlExt_item'
                },{
                    header: "{/literal}{$lang.last_check}{literal}",
                    dataIndex: 'Date',
                    width: 10,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    fixed: true,
                    width: 110,
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

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=view&id="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteSavedSearch\", \""+data+"\" )' />";
                        }
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplSavedSearchesGrid'}{literal}
        
        savedSearchesGrid.init();
        grid.push(savedSearchesGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- listing groups grid end -->

    {rlHook name='apTplSavedSearchesBottom'}
    
{/if}

<!-- saved searches tpl end -->
