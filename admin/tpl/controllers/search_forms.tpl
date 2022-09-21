<!-- search forms tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplSearchFormsNavBar'}
    
    {if $smarty.get.action == 'edit'}<a href="{$rlBaseC}action=build&amp;form={$smarty.get.form}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.build_form}</span><span class="right"></span></a>{/if}
    <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_form}</span><span class="right"></span></a>
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.forms_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if isset($smarty.get.action)}

    {if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

        {assign var='sPost' value=$smarty.post}
    
        <!-- add/edit form -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
            <form  onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;form={$smarty.get.form}{/if}" method="post">
                <input type="hidden" name="submit" value="1" />
                {if $smarty.get.action == 'edit'}
                    <input type="hidden" name="fromPost" value="1" />
                {/if}

                <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.form_type}</td>
                    <td class="field">
                        <select name="form_type" {if $smarty.get.action == 'edit'}disabled="disabled"{/if}>
                            <option value="">{$lang.select}</option>
                            {foreach from=$form_types key='form_key' item='form_name'}
                                <option value="{$form_key}" {if $sPost.form_type == $form_key}selected="selected"{/if}>{$form_name}</option>
                            {/foreach}
                        </select>
                        {if $smarty.get.action == 'edit'}
                            <input type="hidden" name="form_type" value="{$sPost.form_type}" />
                        {/if}
                    </td>
                </tr>
                </table>

                <div id="form_key" class="form-option hide">
                    <table class="form">
                    <tr>
                        <td class="name"><span class="red">*</span>{$lang.key}</td>
                        <td class="field">
                            <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
                        </td>
                    </tr>
                    </table>
                </div>

                <table class="form">
                <tr>
                    <td class="name">
                        <span class="red">*</span>{$lang.name}
                    </td>
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
                    <td class="name"><span class="red">*</span>{$lang.listing_type}</td>
                    <td class="field">
                        {if $smarty.get.action == 'add' || !$sPost.readonly}
                            <select name="type" style="width: 200px;">
                                <option value="">{$lang.select}</option>
                                {foreach from=$listing_types item='l_type'}
                                    <option value="{$l_type.Key}" {if $sPost.type == $l_type.Key}selected="selected"{/if}>{$l_type.name}</option>
                                {/foreach}
                            </select>
                        {else}
                            <input style="width: 150px" class="disabled" type="text" disabled="disabled" value="{$listing_types[$sPost.type].name}" />
                            <input type="hidden" name="type" value="{$sPost.type}" />
                        {/if}
                    </td>
                </tr>
                </table>

                {if $smarty.get.action == 'add' || !$sPost.readonly}
                    <div id="select_category" class="form-option hide">
                        <table class="form">
                        <tr>
                            <td class="name"><span class="red">*</span>{$lang.show_in_category}</td>
                            <td class="field">
                                <select name="category_id" style="width: 200px;">
                                    <option value="">{$lang.choose_listing_type}</option>
                                </select>

                                <div style="padding: 10px 0 0 0;">
                                    <label style="padding-bottom: 10px;"><input {if !empty($sPost.subcategories)}checked="checked"{/if} type="checkbox" name="subcategories" value="1" /> {$lang.include_subcats}</label>
                                </div>
                            </td>
                        </tr>
                        </table>
                    </div>

                    <script>
                    var category_selected = {if $sPost.category_id}{$sPost.category_id}{else}false{/if};
                    {literal}

                    $(document).ready(function(){
                        $('select[name=category_id]').categoryDropdown({
                            listingType: 'select[name=type]',
                            default_selection: category_selected,
                            phrases: { {/literal}
                                no_categories_available: "{$lang.no_categories_available}",
                                select: "{$lang.select}",
                                select_category: "{$lang.select_category}"
                            {literal} }
                        });
                    });

                    {/literal}
                    </script>
                {/if}

                <div id="use_groups" class="form-option hide">
                    <table class="form">
                    <tr>
                        <td class="name">{$lang.use_groups}</td>
                        <td class="field">
                            {if $no_groups}
                                {$lang.not_available}
                            {else}
                                <label><input {if $sPost.groups}checked="checked"{/if}type="radio" name="groups" value="1" /> {$lang.yes}</label>
                                <label><input {if !$sPost.groups}checked="checked"{/if} type="radio" name="groups" value="0" /> {$lang.no}</label>
                            {/if}
                        </td>
                    </tr>
                    </table>
                </div>

                <table class="form">
                <tr>
                    <td class="name">{$lang.with_picture_option}</td>
                    <td class="field">
                        <label><input {if $sPost.with_picture}checked="checked"{/if}type="radio" name="with_picture" value="1" /> {$lang.yes}</label>
                        <label><input {if !$sPost.with_picture}checked="checked"{/if} type="radio" name="with_picture" value="0" /> {$lang.no}</label>
                    </td>
                </tr>
                
                {rlHook name='apTplSearchFormsForm'}
                
                <tr>
                    <td class="name">
                        {if !(!$tpl_settings.search_on_map_page && $form_info.Mode == 'on_map')}
                            <span class="red">*</span>
                        {/if}
                        {$lang.status}
                    </td>
                    <td class="field">
                        {if !$tpl_settings.search_on_map_page && $form_info.Mode == 'on_map'}
                            <span class="field_description" style="margin-left: 0;">{$lang.template_incompatible_hint}</span>
                        {else}
                            <select name="status">
                                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                            </select>
                        {/if}
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
        <!-- add/edit form end -->

        <script>
        {literal}

        $(document).ready(function(){
            $('select[name=form_type]').change(function(){
                formTypeChange();
            });

            formTypeChange();
        });

        var formTypeChange = function(){
            var type = $('select[name=form_type]').val();
            
            
            $('.form-option').slideUp();

            switch(type) {
                case 'custom':
                    $('#form_key,#use_groups').slideDown();
                    break;

                case 'in_category':
                    $('#select_category').slideDown();
                    break;
            }
        }

        {/literal}
        </script>

    {elseif $smarty.get.action == 'build'}
    
        {if !$form_info.Groups}
            {assign var='no_groups' value=true}
        {/if}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'builder.tpl'}
    
    {/if}
    
{else}

    <!-- search forms grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var with_picture_option_phrase = "{$lang.with_picture_option}";
    var searchFormsGrid;
    
    {literal}
    $(document).ready(function(){
        
        searchFormsGrid = new gridObj({
            key: 'searchForms',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/search_forms.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_search_forms_manager'],
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Mode', mapping: 'Mode'},
                {name: 'Mode_key', mapping: 'Mode_key'},
                {name: 'Groups', mapping: 'Groups'},
                {name: 'With_picture', mapping: 'With_picture'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Status_key', mapping: 'Status_key'},
                {name: 'Key', mapping: 'Key'},
                {name: 'no_groups', mapping: 'no_groups'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 50,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Mode',
                    width: 130,
                    fixed: true,
                    id: 'rlExt_item'
                },{
                    header: "{/literal}{$lang.listing_type}{literal}",
                    dataIndex: 'Type',
                    width: 130,
                    fixed: true,
                },{
                    header: with_picture_option_phrase,
                    dataIndex: 'With_picture',
                    width: 130,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        emptyText: lang['ext_not_available'],
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus: true
                    })
                },{
                    header: lang['ext_use_groups'],
                    dataIndex: 'Groups',
                    width: 130,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        emptyText: lang['ext_not_available'],
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus: true
                    }),
                    renderer: function(val, ext, row){
                        if (row.data.no_groups) {
                            val = lang['ext_not_available'];
                        }
                        return val;
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 90,
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
                    width: 100,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
        
                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=build&form="+data+"'><img class='build' ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&form="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteSearchForm\", \""+Array(data)+"\", \"section_load\" )' />";
                        
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplSearchFormsGrid'}{literal}
        
        searchFormsGrid.init();
        grid.push(searchFormsGrid.grid);

        // before edit event handler
        searchFormsGrid.grid.addListener('beforeedit', function(editEvent){
            if (editEvent.field == 'Groups' && editEvent.record.data.no_groups) {
                return false;
            } else if (editEvent.field == 'Status'
                       && editEvent.record.data.Mode_key == 'on_map'
                       && editEvent.record.data.Status_key == 'incompatible'
            ) {
                return false;
            }
        });
        
    });
    {/literal}
    //]]>
    </script>
    <!-- search forms grid end -->

    {rlHook name='apTplSearchFormsBottom'}
    
{/if}

<!-- search form tpl end -->
