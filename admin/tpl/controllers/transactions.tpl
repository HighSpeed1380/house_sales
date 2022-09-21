<!-- transactions tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplTransactionsNavBar'}
    
    <a onclick="show('search');" href="javascript:void(0)" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<!-- search -->
<div id="search" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
    <form onsubmit="return false" method="post" action="">
        <table>
        <tr>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.username}</td>
                    <td class="field">
                        <input type="text" id="username" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.name}</td>
                    <td class="field">
                        <input type="text" id="name" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.mail}</td>
                    <td class="field">
                        <input type="text" id="email" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.account_type}</td>
                    <td class="field">
                        <select id="account_type">
                            <option value="">{$lang.select}</option>
                            {foreach from=$account_types item='type'}
                                <option value="{$type.Key}" {if $sPost.profile.type == $type.Key}selected="selected"{/if}>{$type.name}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                
                {rlHook name='apTplTransactionsSearch1'}
                
                <tr>
                    <td></td>
                    <td class="field">
                        <input id="search_button" type="submit" value="{$lang.search}" />
                        <input type="button" value="{$lang.reset}" id="reset_filter_button" />
                        <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
                    </td>
                </tr>
                </table>
            </td>
            <td style="width: 50px;"></td>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.item}</td>
                    <td class="field">
                        <select id="item">
                            <option value="">{$lang.select}</option>
                            {foreach from=$items item='item'}
                            <option value="{$item.Service}">{if array_key_exists($item.Service,$l_plan_types)}{$l_plan_types[$item.Service]}{else}{$item.Service}{/if}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.txn_id}</td>
                    <td class="field">
                        <input type="text" id="txn_id" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.amount}</td>
                    <td class="field">
                        <input type="text" id="amount_from" maxlength="10" style="width: 50px;text-align: center;" />
                        <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                        <input type="text" id="amount_to" maxlength="10" style="width: 50px;text-align: center;" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.date}</td>
                    <td class="field">
                        <input class="date-calendar"
                            type="text"
                            value="{$smarty.post.date_from}"
                            size="12"
                            maxlength="10"
                            id="date_from"
                            autocomplete="off" />
                        <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                        <input class="date-calendar"
                            type="text"
                            value="{$smarty.post.date_to}"
                            size="12"
                            maxlength="10"
                            id="date_to"
                            autocomplete="off" />
                    </td>
                </tr>

                {rlHook name='apTplTransactionsSearch2'}

                </table>
            </td>
        </tr>   
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
</div>

<script type="text/javascript">
{literal}

var sFields = new Array('username', 'name', 'email', 'account_type', 'item', 'txn_id', 'amount_from', 'amount_to', 'date_from', 'date_to');
var cookie_filters = new Array();

$(document).ready(function(){
    $(function(){
        $('#date_from').datepicker({
            showOn         : 'both',
            buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
            buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
            buttonImageOnly: true,
            dateFormat     : 'yy-mm-dd',
            changeMonth    : true,
            changeYear     : true,
            yearRange      : '-100:+30'
        }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

        $('#date_to').datepicker({
            showOn: 'both',
            buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
            buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
            buttonImageOnly: true,
            dateFormat     : 'yy-mm-dd',
            changeMonth    : true,
            changeYear     : true,
            yearRange      : '-100:+30'
        }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
    });
    
    if ( readCookie('transactions_sc') )
    {
        $('#search').show();
        cookie_filters = readCookie('transactions_sc').split(',');
        
        for (var i in cookie_filters)
        {
            if ( typeof(cookie_filters[i]) == 'string' )
            {
                var item = cookie_filters[i].split('||');
                $('#'+item[0]).selectOptions(item[1]);
            }
        }
        
        cookie_filters.push(new Array('search', 1));
    }
    
    $('#search_button').click(function(){       
        var sValues = new Array();
        var filters = new Array();
        var save_cookies = new Array();
        
        for(var si = 0; si < sFields.length; si++)
        {
            sValues[si] = $('#'+sFields[si]).val();
            filters[si] = new Array(sFields[si], $('#'+sFields[si]).val());
            save_cookies[si] = sFields[si]+'||'+$('#'+sFields[si]).val();
        }
        
        {/literal}{rlHook name='apTplTransactionsSearchJS'}{literal}
        
        // save search criteria
        createCookie('transactions_sc', save_cookies, 1);
        
        filters.push(new Array('search', 1));
        
        transactionsGrid.filters = filters;
        transactionsGrid.reload();
    });
    
    $('#reset_filter_button').click(function(){
        eraseCookie('transactions_sc');
        transactionsGrid.reset();
        
        $("#search select option[value='']").attr('selected', true);
        $("#search input[type=text]").val('');
    });
    
    /* autocomplete js */
    $('#username').rlAutoComplete();
});

{/literal}

{if $smarty.get.status}
    cookie_filters = new Array();
    cookie_filters.push(new Array('search_status', '{$smarty.get.status}'));
    cookie_filters.push(new Array('search', 1));
{/if}

</script>
<!-- search end -->

<!-- transactions grid -->
<div id="grid"></div>
<script type="text/javascript">//<![CDATA[
var transactionsGrid;

{literal}
$(document).ready(function(){
    
    transactionsGrid = new gridObj({
        key: 'transactions',
        id: 'grid',
        ajaxUrl: rlUrlHome + 'controllers/transactions.inc.php?q=ext',
        defaultSortField: 'Date',
        remoteSortable: true,
        checkbox: true,
        actions: [
            [lang['ext_delete'], 'delete']
        ],
        title: lang['ext_transactions_manager'],
        filters: cookie_filters,
        fields: [
            {name: 'Item', mapping: 'Item'},
            {name: 'Username', mapping: 'Username', type: 'string'},
            {name: 'Full_name', mapping: 'Full_name', type: 'string'},
            {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
            {name: 'Txn_ID', mapping: 'Txn_ID'},
            {name: 'Total', mapping: 'Total'},
            {name: 'Gateway', mapping: 'Gateway'},
            {name: 'Service', mapping: 'Service'},
            {name: 'pStatus', mapping: 'Status'},
            {name: 'ID', mapping: 'ID', type: 'int'},
            {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ],
        columns: [
            {
                header: lang['ext_id'],
                dataIndex: 'ID',
                width: 3,
                id: 'rlExt_black_bold'
            },{
                header: lang['ext_service'],
                dataIndex: 'Service',
                width: 15
            },{
                header: lang['ext_item'],
                dataIndex: 'Item',
                width: 20,
                id: 'rlExt_item_bold',
                renderer: function(val) {
                    return "<span>"+val+"</span>";
                }
            },{
                header: lang['ext_username'],
                dataIndex: 'Username',
                width: 15,
                renderer: function(username, obj, row){
                    if ( username )
                    {
                        var full_name = trim(row.data.Full_name) ? ' ('+trim(row.data.Full_name)+')' : '';
                        var out = '<a class="green_11_bg" href="'+rlUrlHome+'index.php?controller=accounts&action=view&userid='+row.data.Account_ID+'" ext:qtip="'+lang['ext_click_to_view_details']+'">'+username+'</a>'+full_name;
                    }
                    else
                    {
                        var out = '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                    }
                    return out;
                }
            },{
                header: lang['ext_txn_id'],
                dataIndex: 'Txn_ID',
                width: 15
            },{
                header: lang['ext_total']+' ('+rlCurrency+')',
                dataIndex: 'Total',
                width: 5
            },{
                header: lang['ext_gateway'],
                dataIndex: 'Gateway',
                width: 10,
                css: 'font-weight: bold;'
            },{
                header: lang['ext_date'],
                dataIndex: 'Date',
                width: 10,
                renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
            },{
                header: lang['ext_status'],
                dataIndex: 'pStatus',
                width: 100,
                fixed: true,
                editor: new Ext.form.ComboBox({
                    store: [
                        ['paid', lang['ext_paid']],
                        ['unpaid', lang['ext_unpaid']]
                    ],
                    displayField: 'value',
                    valueField: 'key',
                    typeAhead: true,
                    mode: 'local',
                    triggerAction: 'all',
                    selectOnFocus:true
                }),
                renderer: function(val, obj, row){
                    if(val == lang['ext_paid'])
                    {
                        obj.style += 'background: #d2e798;';
                    }
                    else if (val == lang['ext_unpaid'])
                    {
                        obj.style += 'background: #fbc4c4;';
                    }

                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: lang['ext_actions'],
                width: 50,
                fixed: true,
                dataIndex: 'ID',
                sortable: false,
                renderer: function(data) {
                    return "<center><img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteTransaction\", \""+data+"\", \"load\" )' /></center>";
                }
            }
        ]
    });
    
    {/literal}{rlHook name='apTplTransactionsGrid'}{literal}
    
    transactionsGrid.init();
    grid.push(transactionsGrid.grid);
    
    // actions listener
    transactionsGrid.actionButton.addListener('click', function()
    {
        var sel_obj = transactionsGrid.checkboxColumn.getSelections();
        var action = transactionsGrid.actionsDropDown.getValue();

        if (!action)
        {
            return false;
        }
        
        for( var i = 0; i < sel_obj.length; i++ )
        {
            transactionsGrid.ids += sel_obj[i].id;
            if ( sel_obj.length != i+1 )
            {
                transactionsGrid.ids += '|';
            }
        }
        
        if ( action == 'delete' )
        {
            Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn){
                if ( btn == 'yes' )
                {
                    xajax_deleteTransaction( transactionsGrid.ids );
                }
            });
        }
    });
    
});
{/literal}
//]]>
</script>
<!-- transactions grid end -->

{rlHook name='apTplTransactionsBottom'}

<!-- transactions tpl end -->
