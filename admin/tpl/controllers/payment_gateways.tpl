<!-- payment gateways tpl -->

<!-- navigation bar -->
{if $smarty.get.action}
    <div id="nav_bar">
        {rlHook name='apTplPaymentGatewaysNavBar'}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.payment_gateways}</span><span class="right"></span></a>
    </div>
{/if}
<!-- navigation bar end -->

{if $smarty.get.action}
    {assign var='sPost' value=$smarty.post}
    
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;item={$smarty.get.item}{/if}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="submit" value="1" />

        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        
        <table class="form">
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.name}
                </td>
                <td>
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
        
            {foreach from=$gateway_settings item=configItem}
                {if $configItem.Type == 'text' || $configItem.Type == 'textarea' || $configItem.Type == 'bool' || $configItem.Type == 'select' || $configItem.Type == 'radio'}
                <tr>
                    <td class="name">{if $configItem.required}<span class="red">*</span>{/if}{$configItem.name}</td>
                    <td class="field">
                        <div class="inner_margin">
                            {if $configItem.Type == 'text'}
                                <input name="post_config[{$configItem.Key}]" class="{if $configItem.Data_type == 'int'}numeric{/if}" type="text" value="{if $sPost.post_config[$configItem.Key]}{$sPost.post_config[$configItem.Key]}{else}{$configItem.Default}{/if}" maxlength="255" />
                            {elseif $configItem.Type == 'bool'}
                                <label><input type="radio" {if $configItem.Default == 1}checked="checked"{/if} name="post_config[{$configItem.Key}]" value="1" /> {$lang.enabled}</label>
                                <label><input type="radio" {if $configItem.Default == 0}checked="checked"{/if} name="post_config[{$configItem.Key}]" value="0" /> {$lang.disabled}</label>
                            {elseif $configItem.Type == 'textarea'}
                                <textarea cols="5" rows="5" class="{if $configItem.Data_type == 'int'}numeric{/if}" name="post_config[{$configItem.Key}]">{if $sPost.post_config[$configItem.Key]}{$sPost.post_config[$configItem.Key]}{else}{$configItem.Default}{/if}</textarea>
                            {elseif $configItem.Type == 'select'}
                                <select {if $configItem.Key == 'timezone'}class="w350"{/if} style="width: 204px;" name="post_config[{$configItem.Key}]" {if $configItem.Values|@count < 2} class="disabled" disabled="disabled"{/if}>
                                    {if $configItem.Values|@count > 1}
                                        <option value="">{$lang.select}</option>
                                    {/if}
                                    {foreach from=$configItem.Values item='sValue' name='sForeach'}
                                        <option value="{if is_array($sValue)}{$sValue.ID}{else}{$sValue}{/if}" {if is_array($sValue)}{if $configItem.Default == $sValue.ID || $sPost.post_config[$configItem.Key] == $sValue.ID}selected="selected"{/if}{else}{if $sValue == $configItem.Default}selected="selected"{/if}{/if}>{if is_array($sValue)}{$sValue.name}{else}{$sValue}{/if}</option>
                                    {/foreach}
                                </select>
                            {elseif $configItem.Type == 'radio'}
                                {assign var='displayItem' value=$configItem.Display}
                                {foreach from=$configItem.Values item='rValue' name='rForeach' key='rKey'}
                                    <input id="radio_{$configItem.Key}_{$rKey}" {if $rValue == $configItem.Default}checked="checked"{/if} type="radio" value="{$rValue}" name="post_config[{$configItem.Key}][value]" /><label for="radio_{$configItem.Key}_{$rKey}">&nbsp;{$displayItem.$rKey}&nbsp;&nbsp;</label>
                                {/foreach}
                            {else}
                                {$configItem.Default}
                            {/if}

                            {if $configItem.des != ''}
                                <span style="{if $configItem.Type == 'textarea'}line-height: 10px;{elseif $configItem.Type == 'bool'}line-height: 14px;margin: 0 10px;{/if}" class="settings_desc">{$configItem.des}</span>
                            {/if}
                        </div>
                    </td>
                </tr>
                {/if}
            {/foreach}
            {if $gateway_info.Recurring_editable}
            <tr>
                <td class="name">{$lang.recurring}</td>
                <td class="field">
                    {assign var='checkbox_field' value='recurring'}
                    
                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}
                    
                    <input {$recurring_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$recurring_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>
            {/if}
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
{else}
    <!-- payment gateways grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var paymentGatewaysGrid;

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
                        url: rlUrlHome + 'controllers/payment_gateways.inc.php?q=ext',
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
                            paymentGatewaysGrid.store.commitChanges();
                            paymentGatewaysGrid.reload();
                        }
                    });
                }
            },
            renderer : function(v, p, record){
                if (record.data.Status_key == 'approval') {
                    return '';
                }

                p.css += ' x-grid3-check-col-td';   
                return '<div ext:qtip="'+lang['ext_set_default']+'" class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'">&#160;</div>';
            }
        };
    
        var defaultColumn = new Ext.grid.defaultColumn({
            header: lang['ext_default'],
            dataIndex: 'Default',
            width: 60,
            fixed: true
        });
        
        paymentGatewaysGrid = new gridObj({
            key: 'payment_gateways',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/payment_gateways.inc.php?q=ext',
            defaultSortField: 'ID',
            remoteSortable: true,
            checkbox: false,
            actions: [
                [lang['ext_delete'], 'delete']
            ],
            title: lang['ext_payment_gateways_manager'],

            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'name', mapping: 'name'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Plugin', mapping: 'Plugin', type: 'string'},
                {name: 'Status', mapping: 'Status', type: 'string'},
                {name: 'Status_key', mapping: 'Status_key', type: 'string'},
                {name: 'Recurring', mapping: 'Recurring', type: 'string'},
                {name: 'Default', mapping: 'Default'},
                {name: 'Type', mapping: 'Type'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 3,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 20,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 15
                },{
                    header: lang['ext_recurring'],
                    dataIndex: 'Recurring',
                    width: 10
                },
                defaultColumn,
                {
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
                    width: 50,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data) {
                        return "<center><a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&item="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a></center>";
                    }
                }
            ]
        });

        paymentGatewaysGrid.plugins.push(defaultColumn);

        {/literal}{rlHook name='apTplPaymentGatewaysGrid'}{literal}

        paymentGatewaysGrid.init();
        grid.push(paymentGatewaysGrid.grid);

        paymentGatewaysGrid.grid.addListener('afteredit', function(editEvent){
            if ('Status' == editEvent.field) {
                paymentGatewaysGrid.reload();
            }
        });
    });
    {/literal}
    //]]>
    </script>
    <!-- payment gateways grid end -->
{/if}

{rlHook name='apTplPaymentGatewaysBottom'}

<!-- payment gateways tpl end -->
