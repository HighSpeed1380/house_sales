<!-- plans using tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplPlansUsingNavBar'}
    
    {if !$smarty.get.action}
        <a href="javascript:void(0)" onclick="show('search', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
        {if $aRights.$cKey.add}
            <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.grant_plan}</span><span class="right"></span></a>
        {/if}
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.plans_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'add'}
    <!-- add new entry -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;section={$smarty.get.section}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />

        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.username}</td>
            <td class="field">
                <input name="account_id" type="text" value="" maxlength="30" />
                <script type="text/javascript">
                var account_id = {if $smarty.post.account_id}{$smarty.post.account_id}{else}false{/if};
                {literal}
                
                $(document).ready(function(){
                    $('input[name=account_id]').rlAutoComplete({
                        add_id    : true,
                        add_type  : true,
                        id        : account_id,
                        afterload : function(account) {
                            var a_type    = account && account.Type ? account.Type : null;
                            var $packages = $('#packages option');

                            $packages.addClass('hide');

                            if (a_type) {
                                $('#packages').removeClass('disabled').removeAttr('disabled');
                                $packages.eq(0).text('{/literal}{$lang.select}{literal}').removeClass('hide');

                                $packages.each(function(){
                                    if ($(this).val()) {
                                        var allow_for = $(this).data('allowFor');

                                        if ((allow_for && allow_for.indexOf(a_type) >= 0) || allow_for == '') {
                                            $(this).removeClass('hide');
                                        }
                                    }
                                });
                            }
                        }
                    });
                });

                {/literal}
                </script>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.package_plan_short}</td>
            <td class="field">
                <select id="packages" name="package_id" class="disabled" disabled="disabled">
                    <option value="">{$lang.grant_plan_fill_username}</option>
                    {foreach from=$plans item='plan' key='key'}
                        <option {if $plan.ID == $smarty.post.package_id}selected="selected"{/if} value="{$plan.ID}" data-allow-for="{$plan.Allow_for}" class="hide">
                            {$plan.name}
                        </option>
                    {/foreach}
                </select>
            </td>
        </tr>
        
        {rlHook name='apTplPlansUsingAddField'}
        
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{$lang.add}" />
            </td>
        </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add new entry end -->
{else}
    <div id="action_blocks">
        <!-- search -->
        <div id="search" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
            
            <form method="post" onsubmit="return false;" id="search_form" action="{$rlBase}index.php?controller={$smarty.get.controller}">
            <table class="form">
            <tr>
                <td class="name">{$lang.username}</td>
                <td><input type="text" id="Username" /></td>
            </tr>
            <tr>
                <td class="name">{$lang.plan}</td>
                <td class="field">
                    <select id="Plan_ID" style="width: 200px;">
                    <option value="">- {$lang.all} -</option>
                    {foreach from=$plans item='plan' key='key'}
                        <option value="{$plan.ID}">{$plan.name}</option>
                    {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.plan_type}</td>
                <td class="field">
                    <select id="Type" style="width: 200px;">
                        <option value="">- {$lang.all} -</option>
                        <option value="package">{$lang.package_plan}</option>
                        <option value="limited">{$lang.limited_plan}</option>
                    </select>
                </td>
            </tr>
            
            {rlHook name='apTplPlansUsingSearchField'}
            
            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" class="button lang_add" value="{$lang.search}" id="search_button" />
                    <input type="button" class="button" value="{$lang.reset}" id="reset_search_button" />
            
                    <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
            </form>
            
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
        
        <script type="text/javascript">
        {literal}
        
        var search = new Array();
        var write_filters = new Array();
        var cookie_filters = false;
    
        $(document).ready(function(){
            /* read cookies filters */
            if ( readCookie('plans_using_sc') )
            {
                $('#search').show();
                cookie_filters = readCookie('plans_using_sc').split(',');
                
                for (var i in cookie_filters)
                {
                    if ( typeof(cookie_filters[i]) == 'string' )
                    {
                        var item = cookie_filters[i].split('||');
                        $('#'+item[0]).selectOptions(item[1]);
                    }
                }
            }
            
            /* on search button click */
            $('#search_form').submit(function(){
                
                if ( $('#ac_hidden').val() != undefined )
                {
                    search.push(new Array('Username', $('#Username').val()));
                    write_filters.push('Username||'+$('#Username').val());
                }
                search.push(new Array('Plan_ID', $('#Plan_ID').val()));
                search.push(new Array('Type', $('#Type').val()));
                search.push(new Array('action', 'search'));
                
                {/literal}{rlHook name='apTplPlansUsingSearchJS'}{literal}
                
                write_filters.push('Plan_ID||'+$('#Plan_ID').val());
                write_filters.push('Type||'+$('#Type').val());
                write_filters.push('action||search');
                
                // save search criteria
                createCookie('plans_using_sc', write_filters, 1);
                
                plansUsingGrid.filters = search;
                plansUsingGrid.reload();
            });
            
            $('#reset_search_button').click(function(){
                plansUsingGrid.reload();
                eraseCookie('plans_using_sc');

                $('#search input[type="text"]').each(function() {
                    $(this).val('');
                });
                $("#search select option:selected").attr('selected', false);
            });
            
            /* autocomplete js */
            $('#Username').rlAutoComplete({add_id: true});
        });
        
        {/literal}
        
        {if $smarty.get.plan_id}
            cookie_filters = new Array();
            cookie_filters[0] = new Array('Plan_ID', '{$smarty.get.plan_id}');
            cookie_filters.push(new Array('action', 'search'));
        {/if}
        
        </script>
        <!-- search end -->
    </div>
    
    <!-- plans using grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var plansUsingGrid;
    
    {literal}
    $(document).ready(function(){
        
        plansUsingGrid = new gridObj({
            key: 'plansUsing',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/plans_using.inc.php?q=ext',
            defaultSortField: 'ID',
            defaultSortType: 'DESC',
            filters: cookie_filters,
            title: lang['ext_plans_using_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                {name: 'Plan_name', mapping: 'Plan_name'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Type_key', mapping: 'Type_key'},
                {name: 'Price', mapping: 'Price'},
                {name: 'Listings_remains', mapping: 'Listings_remains'},
                {name: 'Standard_remains', mapping: 'Standard_remains'},
                {name: 'Featured_remains', mapping: 'Featured_remains'},
                {name: 'Advanced_mode', mapping: 'Advanced_mode'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 3,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_username'],
                    dataIndex: 'Username',
                    width: 20,
                    id: 'rlExt_item',
                    renderer: function(username, ext, row){
                        if ( username )
                        {
                            return "<span ext:qtip='"+lang['ext_click_to_view_details']+"' style='cursor: pointer;' onClick='location.href=\""+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"\"'>"+username+"</span>"
                        }
                        else
                        {
                            return '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                        }
                    }
                },{
                    header: lang['ext_plan'],
                    dataIndex: 'Plan_name',
                    width: 20
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 10
                },{
                    header: lang['ext_price']+' ('+rlCurrency+')',
                    dataIndex: 'Price',
                    width: 8
                },{
                    header: lang['ext_balance'],
                    dataIndex: 'Listings_remains',
                    width: 8,
                    css: 'font-weight: bold;',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_standard_remains'],
                    dataIndex: 'Standard_remains',
                    width: 8,
                    css: 'font-weight: bold;',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val, ext, row){
                        var out;
                        if ( parseInt(row.data.Advanced_mode) )
                        {
                            out = '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                        else
                        {
                            out = '<span ext:qtip="'+lang['ext_not_available_for_this_plan']+'">'+lang['ext_not_available']+'</span>';
                        }
                        
                        return out;
                    }
                },{
                    header: lang['ext_featured_remains'],
                    dataIndex: 'Featured_remains',
                    width: 8,
                    css: 'font-weight: bold;',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    renderer: function(val, ext, row){
                        var out;
                        if ( parseInt(row.data.Advanced_mode) )
                        {
                            out = '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                        else
                        {
                            out = '<span ext:qtip="'+lang['ext_not_available_for_this_plan']+'">'+lang['ext_not_available']+'</span>';
                        }
                        
                        return out;
                    }
                },{
                    header: lang['ext_date'],
                    dataIndex: 'Date',
                    width: 8,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_actions'],
                    width: 50,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            return "<center><img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deletePlanUsing\", \""+data+"\", \"load\" )' /></center>"
                        }
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplPlansUsingGrid'}{literal}
        
        plansUsingGrid.init();
        grid.push(plansUsingGrid.grid);

        plansUsingGrid.grid.addListener('beforeedit', function(editEvent) {
            if ((editEvent.field == 'Listings_remains' || editEvent.field == 'Standard_remains' || editEvent.field == 'Featured_remains') && editEvent.record.data.Type_key == 'account') {
                return false;
            }
        });
        
    });
    {/literal}
    //]]>
    </script>
    <!-- plans using grid end -->
    
    {rlHook name='apTplPlansUsingBottom'}
    
{/if}

<!-- plans using end tpl -->
