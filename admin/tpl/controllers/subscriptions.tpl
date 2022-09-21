<!-- subscription tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplSubscriptionNavBar'}

    {if !$smarty.get.action}
        <a href="javascript:void(0)" onclick="show('search')" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
    {/if}

    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.subscription_list}</span><span class="right"></span></a>
</div>

<!-- navigation bar end -->
<!-- search -->
{if !$smarty.get.action}
    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
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
                        <td class="name w130">{$lang.subscription_id}</td>
                        <td class="field">
                            <input type="text" id="subscription_id" maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.account_type}</td>
                        <td class="field">
                            <select id="account_type" style="width: 200px;">
                                <option value="">{$lang.select}</option>
                                {foreach from=$account_types item='type'}
                                    <option value="{$type.Key}" {if $sPost.profile.type == $type.Key}selected="selected"{/if}>{$type.name}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.plan}</td>
                        <td class="field">
                            <select class="filters w200" id="plan_id">
                                <option value="">{$lang.select}</option>
                                {foreach from=$services item='plans' key="key"}
                                    <option disabled="disabled" value="{$key}" class="highlight_opt">{$lang.$key}</option>
                                    {foreach from=$plans item='plan'}
                                        <option value="{$plan.ID}-{$key}">{$plan.name}</option>
                                    {/foreach}
                                {/foreach}
                            </select>
                        </td>
                    </tr>

                    {rlHook name='apTplSubscriptionSearch1'}

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
                        <td class="name w130">{$lang.payment_gateway}</td>
                        <td class="field">
                            <select class="filters w200" id="gateway_id">
                                <option value="">{$lang.select}</option>
                                {foreach from=$payment_gateways item='gateway'}
                                    <option value="{$gateway.ID}" {if $sPost.gateway_id == $gateway.ID}selected="selected"{/if}>{$gateway.name}</option>
                                {/foreach}
                            </select>
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
                        <td class="name w130">{$lang.status}</td>
                        <td class="field">
                            <select id="search_status" style="width: 200px;">
                                <option value="">- {$lang.all} -</option>
                                {foreach from=$statuses item='status'}
                                    <option value="{$status}" {if $status == $sPost.status}selected="selected"{/if}>{$lang.$status}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.date}</td>
                        <td class="field" style="white-space: nowrap;">
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

                    {rlHook name='apTplSubscriptionSearch2'}

                </table>
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript">
    {literal}
    
    var sFields = new Array('username', 'subscription_id', 'account_type', 'plan_id', 'gateway_id', 'amount_from', 'amount_to', 'search_status', 'date_from', 'date_to');
    var cookie_filters = new Array();
    
    $(document).ready(function(){
        $(function(){
            $('#date_from').datepicker({
                showOn: 'both',
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
        
        if ( readCookie('subscription_sc') )
        {
            $('#search').show();
            cookie_filters = readCookie('subscription_sc').split(',');
            
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
            
            // save search criteria
            createCookie('subscription_sc', save_cookies, 1);
            
            filters.push(new Array('search', 1));
            
            subscriptionGrid.filters = filters;
            subscriptionGrid.reload();
        });
        
        $('#reset_filter_button').click(function(){
            eraseCookie('subscription_sc');
            subscriptionGrid.reset();
            
            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
        });
        
        /* autocomplete js */
        $('#username').rlAutoComplete();
    });
    
    {/literal}
    
    {if $smarty.get.status}
        cookie_filters = new Array();
        cookie_filters[0] = new Array('search_status', '{$smarty.get.status}');
        cookie_filters.push(new Array('search', 1));
    {/if}
    
    {if $smarty.get.account_type}
        cookie_filters = new Array();
        cookie_filters[0] = new Array('account_type', '{$smarty.get.account_type}');
        cookie_filters.push(new Array('search', 1));
    {/if}
    
    {rlHook name='apTplSubscriptionSearchJS'}
    
    </script>
{/if}
<!-- search end -->

{if $smarty.get.action} 
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <table class="list">
        <tr>
            <td class="name">{$lang.service}:</td>
            <td class="value">{$subscription_info.Service}</td>
        </tr>
        {if $subscription_info.Subscription_ID}
        <tr>
            <td class="name">{$lang.subscription_id}:</td>
            <td class="value">{$subscription_info.Subscription_ID}</td>
        </tr>
        {/if}
        <tr>
            <td class="name">{$lang.status}:</td>
            <td class="value">{$lang[$subscription_info.Status]}</td>
        </tr>
        <tr>
            <td class="name">{$lang.subscription_count}:</td>
            <td class="value">{$subscription_info.Count}</td>
        </tr>
        <tr>
            <td class="name">{$lang.item}:</td>
            <td class="value">{$subscription_info.Item_name}</td>
        </tr>
        <tr>
            <td class="name">{$lang.plan}:</td>
            <td class="value">{$subscription_info.plan.name}</td>
        </tr>
        <tr>
            <td class="name">{$lang.price}:</td>
            <td class="value">
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$subscription_info.plan.Price|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.payment_gateway}:</td>
            <td class="value">{$subscription_info.Gateway}</td>
        </tr>
        <tr>
            <td class="name">{$lang.txn_id}:</td>
            <td class="value">{$subscription_info.Txn_ID}</td>
        </tr>
        <tr>
            <td class="name">{$lang.username}:</td>
            <td class="value">
                <a href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$subscription_info.Account_ID}">{$subscription_info.Full_name}</a>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.date}:</td>
            <td class="value">{$subscription_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
        </tr>
        
    </table>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{else}
    <!-- subscription grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var subscriptionGrid;

    {literal}
    $(document).ready(function(){

        subscriptionGrid = new gridObj({
            key: 'subscription',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/subscriptions.inc.php?q=ext',
            defaultSortField: 'ID',
            remoteSortable: true,
            filters: cookie_filters,
            checkbox: false,
            actions: [
                [lang['ext_delete'], 'delete']
            ],
            title: lang['ext_subscription_manager'],

            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Service', mapping: 'Service'},
                {name: 'Item_name', mapping: 'Item_name'},
                {name: 'Total', mapping: 'Total'},
                {name: 'Full_name', mapping: 'Full_name', type: 'string'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                {name: 'Status', mapping: 'Status', type: 'string'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Gateway', mapping: 'Gateway'},
                {name: 'Subscription_ID', mapping: 'Subscription_ID'},
                {name: 'Allow_check', mapping: 'Allow_check'}
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
                    width: 10,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_item'],
                    dataIndex: 'Item_name',
                    width: 20
                },{
                    header: lang['ext_total']+' ('+rlCurrency+')',
                    dataIndex: 'Total',
                    width: 5
                },{
                    header: lang['ext_username'],
                    dataIndex: 'Full_name',
                    width: 10,
                    renderer: function(username, ext, row){
                        if (username) {
                            var out = "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</a>"   
                        } else {
                            var out = '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                        }
                        return out;
                    }
                },{
                    header: lang['ext_gateway'],
                    dataIndex: 'Gateway',
                    width: 10
                },{
                    header: lang['ext_date'],
                    dataIndex: 'Date',
                    width: 10,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true
                },{
                    header: lang['ext_actions'],
                    width: 50,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data, obj, row) {
                        var out = "<center>";

                        if (row.data.Allow_check && row.data.Subscription_ID) {
                            out += "<a href='javascript://' onClick='checkSubscription("+row.data.ID+");'><img class='info' ext:qtip='"+lang['check_subscription']+"' src='"+rlUrlHome+"img/blank.gif' style='vertical-align: top;' /></a>";
                        }

                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=view&item="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";

                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplSubscriptionGrid'}{literal}

        subscriptionGrid.init();
        grid.push(subscriptionGrid.grid);
        
    });

    var checkSubscription = function(itemID) {
        popupSubscriptionInfo = new Ext.Window({
            title: '{/literal}{$lang.subscription_details}{literal}',
            autoLoad: {
                url: rlConfig['ajax_url'],
                scripts: true ,
                params: {item: 'checkSubscription', itemID: itemID}
            },
            layout: 'fit',
            width: 500,
            height: 'auto',
            plain: true,
            modal: true,
            closable: true,
            y: 150,
        });

        popupSubscriptionInfo.show();
        flynax.slideTo('body');
    }
    {/literal}
    //]]>
    </script>
    <!-- subscription grid end -->
{/if}

{rlHook name='apTplPaymentGatewaysBottom'}

<!-- subscription tpl end -->
