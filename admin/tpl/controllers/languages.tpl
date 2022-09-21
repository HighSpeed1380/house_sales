<!-- languages tpl -->

{if $smarty.get.action == 'edit'}

    <!-- navigation bar -->
    <div id="nav_bar">
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.languages_list}</span><span class="right"></span></a>
    </div>
    <!-- navigation bar end -->

    <!-- edit language -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action=edit&amp;lang={$smarty.get.lang}" method="post">
        <input type="hidden" name="submit" value="1" />
        <input type="hidden" name="fromPost" value="1" />
        
        {assign var='sPost' value=$smarty.post}
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.iso_code}</td>
            <td class="field">
                <input readonly="readonly" class="disabled" name="code" type="text" style="width: 150px;" value="{$sPost.code}" maxlength="2" />
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.locale_code}</td>
            <td class="field">
                <input name="locale" type="text" style="width: 150px;" value="{$sPost.locale}" maxlength="25" />
                <span class="field_description">{$lang.locale_code_hint}</span>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.lang_direction}</td>
            <td class="field">
                <label title="{$lang.ltr_direction_title}"><input {if $sPost.direction == 'ltr'}checked="checked"{/if} value="ltr" type="radio" name="direction" title="{$lang.ltr_direction_title}" /> {$lang.ltr_direction}</label>
                <label title="{$lang.rtl_direction_title}"><input {if $sPost.direction == 'rtl'}checked="checked"{/if} value="rtl" type="radio" name="direction" title="{$lang.rtl_direction_title}" /> {$lang.rtl_direction}</label>
            </td>
        </tr>
        
        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.name}
            </td>
            <td class="field">
                <input class="text" type="text" name="name" value="{$sPost.name}" style="width: 250px;" maxlength="50" />
            </td>
        </tr>
    
        <tr>
            <td class="name"><span class="red">*</span>{$lang.date_format}</td>
            <td class="field">
                <input name="date_format" type="text" value="{$sPost.date_format}" style="width: 100px;" maxlength="50" />
            </td>
        </tr>
        
        {rlHook name='apTplLanguagesEditField'}
        
        <tr>
            <td class="name"><span class="red">*</span>{$lang.status}</td>
            <td class="field">
                <select name="status" {if $count_active_langs == 1 && $sPost.status == 'active'}class="disabled" disabled="disabled"{/if}>
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
    <!-- edit language end -->

{elseif isset($smarty.post.compare)}
    
    <!-- navigation bar -->
    <div id="nav_bar">
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.languages_list}</span><span class="right"></span></a>
    </div>
    <!-- navigation bar end -->

    <!-- compare -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' flexible=true block_caption=$lang.languages_compare}
    <form onsubmit="return submitHandler()" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;compare" method="post">
    <input type="hidden" name="compare" value="true" />
    <table class="form">
    <tr>
        <td class="name" style="width: 150px;"><span class="red">*</span>{$lang.compare}</td>
        <td class="field">
            <select name="lang_1" id="lang_1">
            <option value="">{$lang.select}</option>
                {foreach from=$allLangs item='lang_list'}
                <option {if $smarty.post.lang_1 == $lang_list.Code}selected="selected"{/if} value="{$lang_list.Code}">{$lang_list.name}</option>
                {/foreach}
            </select>
            {$lang.with}
            <select name="lang_2" id="lang_2">
            <option value="">{$lang.select}</option>
                {foreach from=$allLangs item='lang_list'}
                <option {if $smarty.post.lang_2 == $lang_list.Code}selected="selected"{/if} value="{$lang_list.Code}">{$lang_list.name}</option>
                {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.compare_mode}</td>
        <td class="field">
            <select name="compare_mode">
                <option {if $smarty.post.compare_mode == 'phrases'}selected="selected"{/if} value="phrases">{$lang.by_phrases_exist}</option>
                <option {if $smarty.post.compare_mode == 'translation'}selected="selected"{/if} value="translation">{$lang.by_translation_different}</option>
            </select>
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" value="{$lang.compare}" />
        </td>
    </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    
    {if $compare_lang1 || $compare_lang2}
    
        {assign var='code_1' value=$compare_lang1.Code}
        {assign var='code_2' value=$compare_lang2.Code}
        
        {assign var='replace_lang1' value=`$smarty.ldelim`lang1`$smarty.rdelim`}
        {assign var='replace_lang2' value=`$smarty.ldelim`lang2`$smarty.rdelim`}

        <div id="compare_area_1">
            <div style="padding: 7px 0">
                {if $compare_lang1.diff}
                    {if $smarty.post.compare_mode == 'phrases'}
                        {$lang.compare_result_info|replace:$replace_lang1:$langs_info.$code_1.name|replace:$replace_lang2:$langs_info.$code_2.name}<br />
                        <input style="margin-top: 5px;" id="copy_button_1" onclick="xajax_copyPhrases(1, 2);$('#loading_1').fadeIn('normal');" type="button" value="{$lang.compare_copy_phrases|replace:$replace_lang1:$langs_info.$code_1.name|replace:$replace_lang2:$langs_info.$code_2.name}" />
                        <div class="grey_loader" id="loading_1"></div>
                    {else}
                        {$lang.compare_translation_result_info|replace:$replace_lang1:$langs_info.$code_1.name|replace:$replace_lang2:$langs_info.$code_2.name}
                    {/if}
                {/if}
            </div>
            <div id="compare_grid1" style="clear: both;"></div>
        </div>
    
        <div id="compare_area_2">
            <div style="padding: 20px 0 7px 0">
                {if $compare_lang2.diff}
                    {if $smarty.post.compare_mode == 'phrases'}
                        {$lang.compare_result_info|replace:$replace_lang1:$langs_info.$code_2.name|replace:$replace_lang2:$langs_info.$code_1.name}<br />
                        <input style="margin-top: 5px;" id="copy_button_2" onclick="xajax_copyPhrases(2, 1);$('#loading_2').fadeIn('normal');" type="button" value="{$lang.compare_copy_phrases|replace:$replace_lang1:$langs_info.$code_2.name|replace:$replace_lang2:$langs_info.$code_1.name}" />
                        <div class="grey_loader" id="loading_2"></div>
                    {else}
                        {$lang.compare_translation_result_info|replace:$replace_lang1:$langs_info.$code_2.name|replace:$replace_lang2:$langs_info.$code_1.name}
                    {/if}
                {/if}
            </div>
            <div id="compare_grid2" style="clear: both;"></div>
        </div>
    
        <!-- compare grids creation -->
        <script type="text/javascript">
        var compare_mode = '{$smarty.post.compare_mode}';
        
        {if $compare_lang1.diff}
            var lang_1 = '{$code_1}';
            var lang1_name = ': {$langs_info.$code_1.name}';
            var compareGrid1;
            
            {literal}
            $(document).ready(function(){
                
                compareGrid1 = new gridObj({
                    key: 'compare1',
                    id: 'compare_grid1',
                    ajaxUrl: rlUrlHome + 'controllers/languages.inc.php?q=compare&grid=1&compare_mode='+compare_mode,
                    defaultSortField: 'Value',
                    title: lang['ext_phrases_manager'] + lang1_name,
                    checkbox: true,
                    actions: [
                        [lang['ext_delete'], 'delete']
                    ],
                    fields: [
                        {name: 'Module', mapping: 'Module'},
                        {name: 'Key', type: 'string'},
                        {name: 'Value', mapping: 'Value', type: 'string'}
                    ],
                    columns: [
                        {
                            header: lang['ext_key'],
                            dataIndex: 'Key',
                            width: 30
                        },{
                            id: 'rlExt_item',
                            header: lang['ext_value'],
                            dataIndex: 'Value',
                            width: 60,
                            editor: new Ext.form.TextArea({
                                allowBlank: false
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: lang['ext_side'],
                            dataIndex: 'Module',
                            width: 15,
                            editor: new Ext.form.ComboBox({
                                store: [
                                    ['common', {/literal}'{$lang.module_common}'{literal}],
                                    ['frontEnd', {/literal}'{$lang.module_frontEnd}'{literal}],
                                    ['admin', {/literal}'{$lang.module_admin}'{literal}],
                                    ['category', {/literal}'{$lang.module_category}'{literal}],
                                    ['system', {/literal}'{$lang.module_system}'{literal}],
                                    ['box', {/literal}'{$lang.module_box}'{literal}]
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
                        }
                    ]
                });
                
                {/literal}{rlHook name='apTplLanguagesCompareGrid1'}{literal}
                
                compareGrid1.init();
                grid.push(compareGrid1.grid);
                
                // actions listener
                compareGrid1.actionButton.addListener('click', function()
                {
                    var sel_obj = compareGrid1.checkboxColumn.getSelections();
                    var action = compareGrid1.actionsDropDown.getValue();
        
                    if (!action)
                    {
                        return false;
                    }
                    
                    for( var i = 0; i < sel_obj.length; i++ )
                    {
                        compareGrid1.ids += sel_obj[i].id;
                        if ( sel_obj.length != i+1 )
                        {
                            compareGrid1.ids += '|';
                        }
                    }
                    
                    if ( action == 'delete' )
                    {
                        Ext.MessageBox.confirm('Confirm', lang['ext_notice_delete'], function(btn){
                            if ( btn == 'yes' )
                            {
                                xajax_massDelete( compareGrid1.ids, 'lang_1', 1 );
                                compareGrid1.reload();
                            }
                        });
                        
                        compareGrid1.checkboxColumn.clearSelections();
                        compareGrid1.actionsDropDown.setVisible(false);
                        compareGrid1.actionButton.setVisible(false);
                    }
                });
                
            });
            
            {/literal}
        {/if}
        
        {if $compare_lang2.diff}
            var lang_2 = '{$code_2}';
            var lang2_name = ': {$langs_info.$code_2.name}';
            var compareGrid2;
            
            {literal}
            $(document).ready(function(){
                
                compareGrid2 = new gridObj({
                    key: 'compare2',
                    id: 'compare_grid2',
                    ajaxUrl: rlUrlHome + 'controllers/languages.inc.php?q=compare&grid=2&compare_mode='+compare_mode,
                    defaultSortField: 'Value',
                    title: lang['ext_phrases_manager'] + lang2_name,
                    checkbox: true,
                    actions: [
                        [lang['ext_delete'], 'delete']
                    ],
                    fields: [
                        {name: 'Module', mapping: 'Module'},
                        {name: 'Key', type: 'string'},
                        {name: 'Value', mapping: 'Value', type: 'string'}
                    ],
                    columns: [
                        {
                            header: lang['ext_key'],
                            dataIndex: 'Key',
                            width: 30
                        },{
                            id: 'rlExt_item',
                            header: lang['ext_value'],
                            dataIndex: 'Value',
                            width: 60,
                            editor: new Ext.form.TextArea({
                                allowBlank: false
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        },{
                            header: lang['ext_side'],
                            dataIndex: 'Module',
                            width: 15,
                            editor: new Ext.form.ComboBox({
                                store: [
                                    ['common', {/literal}'{$lang.module_common}'{literal}],
                                    ['frontEnd', {/literal}'{$lang.module_frontEnd}'{literal}],
                                    ['admin', {/literal}'{$lang.module_admin}'{literal}],
                                    ['category', {/literal}'{$lang.module_category}'{literal}],
                                    ['system', {/literal}'{$lang.module_system}'{literal}],
                                    ['box', {/literal}'{$lang.module_box}'{literal}]
                                ],
                                typeAhead: true,
                                mode: 'local',
                                triggerAction: 'all',
                                selectOnFocus:true
                            }),
                            renderer: function(val){
                                return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                            }
                        }
                    ]
                });
                
                {/literal}{rlHook name='apTplLanguagesCompareGrid2'}{literal}
                
                compareGrid2.init();
                grid.push(compareGrid2.grid);
                
                // actions listener
                compareGrid2.actionButton.addListener('click', function()
                {
                    var sel_obj = compareGrid2.checkboxColumn.getSelections();
                    var action = compareGrid2.actionsDropDown.getValue();
        
                    if (!action)
                    {
                        return false;
                    }
                    
                    for( var i = 0; i < sel_obj.length; i++ )
                    {
                        compareGrid2.ids += sel_obj[i].id;
                        if ( sel_obj.length != i+1 )
                        {
                            compareGrid2.ids += '|';
                        }
                    }
                    
                    if ( action == 'delete' )
                    {
                        Ext.MessageBox.confirm('Confirm', lang['ext_notice_delete'], function(btn){
                            if ( btn == 'yes' )
                            {
                                xajax_massDelete( compareGrid2.ids, 'lang_1', 2 );
                                compareGrid2.reload();
                            }
                        });
                        
                        compareGrid2.checkboxColumn.clearSelections();
                        compareGrid2.actionsDropDown.setVisible(false);
                        compareGrid2.actionButton.setVisible(false);
                    }
                });
                
            });
            
            {/literal}
        {/if}
        </script>
    {/if}
    
    {rlHook name='apTplLanguagesCompareBottom'}

{else}

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplLanguagesNavBar'}

    {if $aRights.$cKey.add}
        <a href="javascript:void(0)" onclick="show('lang_add_phrase', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_phrase}</span><span class="right"></span></a>
        <a href="javascript:void(0)" onclick="show('import', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_import">{$lang.import}</span><span class="right"></span></a>
    {/if}
    
    {if $aRights.$cKey.edit}
        <a href="javascript:void(0)" onclick="show('compare', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_compare">{$lang.compare}</span><span class="right"></span></a>
    {/if}
    
    {if $aRights.$cKey.add}
        <a href="javascript:void(0)" onclick="show('lang_add_container', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_language}</span><span class="right"></span></a>
    {/if}
</div>
<!-- navigation bar end -->

<div id="action_blocks">

    {if $aRights.$cKey.add}
    <!-- add language form -->
    <div id="lang_add_container" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_language}
        <form action="" method="post" onsubmit="return false;">
            <table class="form">
            <tr>
                <td class="name">{$lang.name}</td>
                <td class="field">
                    <input type="text" id="language_name" style="width: 150px;" maxlength="30" />
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.iso_code}</td>
                <td class="field">
                    <input type="text" id="iso_code" style="width: 40px; text-align: center;" maxlength="2" />
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.locale_code}</td>
                <td class="field">
                    <input type="text" id="locale" style="width: 40px; text-align: center;" maxlength="5" />
                    <span class="field_description">{$lang.locale_code_hint}</span>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.lang_direction}</td>
                <td class="field">
                    <label title="{$lang.ltr_direction_title}"><input checked="checked" value="ltr" class="direction" type="radio" name="direction" title="{$lang.ltr_direction_title}" /> {$lang.ltr_direction}</label>
                    <label title="{$lang.rtl_direction_title}"><input value="rtl" class="direction" type="radio" name="direction" title="{$lang.rtl_direction_title}" /> {$lang.rtl_direction}</label>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.date_format}</td>
                <td class="field">
                    <input type="text" id="date_format" style="width: 80px; text-align: center;" maxlength="12" value="%d.%m.%Y" />
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.copy_from}</td>
                <td class="field">
                    <select class="{if $langCount < 2}disabled{/if}" id="source" {if $langCount < 2}disabled{/if}>
                    {foreach from=$allLangs item='languages' name='lang_foreach'}
                        <option value="{$languages.Code}" {if $smarty.const.RL_LANG_CODE == $languages.Code} selected="selected"{/if}>{$languages.name}</option>
                    {/foreach}
                    </select>
                </td>
            </tr>
            
            {rlHook name='apTplLanguagesAddField'}
            
            <tr>
                <td></td>
                <td class="field">
                    <input onclick="return rlCheck( Array( Array( 'language_name', '{$lang.name_field_empty}' ) , Array( 'iso_code', '{$lang.iso_code_incorrect_number}', '==^2' ), Array( 'date_format', '{$lang.language_incorrect_date_format}', '>^3' ), Array( 'source', '{$lang.language_no_selected}' ), Array( '.direction', '{$lang.notice_lang_direction_missed}' ), Array('locale', '{$lang.locale_code_incorrect}', '==^5' ) ), 'xajax_addLanguage', 'lang_add_load' );" type="submit" value="{$lang.add}" />
                    <div class="loader" id="lang_add_load"></div> <a class="cancel" href="javascript:void(0)" onclick="show('lang_add_container')">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- add language form end -->
    {/if}
    
    {if $aRights.$cKey.add}
    <!-- add phrase form -->
    <div id="lang_add_phrase" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_phrase}
        <form action="" method="post" onsubmit="return false;">
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.key}</td>
                <td class="field">
                    <input type="text" id="phrase_key" style="width: 200px;" maxlength="60" />
                </td>
            </tr>
            
            {foreach from=$allLangs item='languages' name='phrase_foreach'}
            <tr>
                <td class="name">
                    <span><span class="red">*</span>{$lang.value} <span class="green_10">(<b>{$languages.name}</b>)</span></span>
                </td>
                <td class="field">
                    <textarea rows="3" cols="" style="height: 50px;" name="{$languages.Code}"></textarea>
                </td>
            </tr>
            {/foreach}
            <tr>
                <td class="name"><span class="red">*</span>{$lang.side}</td>
                <td class="field">
                    <select id="phrase_side">
                        <option value="common" selected="selected">{$lang.module_common}</option>
                        <option value="frontEnd">{$lang.module_frontEnd}</option>
                        <option value="admin">{$lang.module_admin}</option>
                        <option value="category">{$lang.module_category}</option>
                        <option value="system">{$lang.module_system}</option>
                        <option value="box">{$lang.module_box}</option>
                    </select>
                </td>
            </tr>
            
            {rlHook name='apTplLanguagesAddPhraseField'}
            
            <tr>
                <td></td>
                <td class="field">
                    <input id="add_phrase_submit" onclick="return rlCheck(Array(Array('phrase_key', '{$lang.incorrect_phrase_key}', '>^2' ), Array( 'phrase_side', '{$lang.language_incorrect_date_format}')), 'js_addPhrase', 'add_phrase_submit', true);" type="submit" value="{$lang.add}" />
                    <a class="cancel" href="javascript:void(0)" onclick="show('lang_add_phrase')">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- add phrase form end -->
    {/if}
    
    {if $aRights.$cKey.add}
    <!-- import -->
    <div id="import" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.import}
        <form onsubmit="return submitHandler()" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;import" method="post" enctype="multipart/form-data">
            <input type="hidden" name="import" value="true" />
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.sql_dump}</td>
                <td class="field">
                    <input type="file" id="import_file" name="dump" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" value="{$lang.go}" />
                    <a class="cancel" href="javascript:void(0)" onclick="show('import')">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- import end -->
    {/if}
    
    {if $aRights.$cKey.edit}
    <!-- compare -->
    <div id="compare" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.languages_compare}
        <form onsubmit="return submitHandler()" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;compare" method="post">
        <input type="hidden" name="compare" value="true" />
        <table class="form">
        <tr>
            <td class="name" style="width: 150px;"><span class="red">*</span>{$lang.compare}</td>
            <td class="field">
                <select name="lang_1" id="lang_1">
                <option value="">{$lang.select}</option>
                    {foreach from=$allLangs item='lang_list'}
                    <option {if $smarty.post.lang_1 == $lang_list.Code}selected="selected"{/if} value="{$lang_list.Code}">{$lang_list.name}</option>
                    {/foreach}
                </select>
                {$lang.with}
                <select name="lang_2" id="lang_2">
                <option value="">{$lang.select}</option>
                    {foreach from=$allLangs item='lang_list'}
                    <option {if $smarty.post.lang_2 == $lang_list.Code}selected="selected"{/if} value="{$lang_list.Code}">{$lang_list.name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.compare_mode}</td>
            <td class="field">
                <select name="compare_mode">
                    <option {if $smarty.post.compare_mode == 'phrases'}selected="selected"{/if} value="phrases">{$lang.by_phrases_exist}</option>
                    <option {if $smarty.post.compare_mode == 'translation'}selected="selected"{/if} value="translation">{$lang.by_translation_different}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{$lang.compare}" />
                <a class="cancel" href="javascript:void(0)" onclick="show('compare')">{$lang.cancel}</a>
            </td>
        </tr>
        </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- compare end -->
    {/if}

</div>

{if isset($smarty.get.import)}
<script type="text/javascript">
{literal}
    $(document).ready(function(){
        show('import', '#action_blocks div');
    });
{/literal}
</script>
{/if}

<!-- languages grid create -->
<div id="grid"></div>
<script>
var languagesGrid;
lang.warning                 = '{$lang.warning}';
lang.removing_english_notice = '{$lang.removing_english_notice}';
{literal}
/**
 * Prevent the losing phrases from English language
 * Offer to admin to compare them with other languages before removing
 *
 * @since 4.7.2 - Added new "action" parameter to first position
 *              - Changed type of "languageID" to mixed and name to "language"
 * @since 4.7.1
 *
 * @param {string} action      - remove|set_default
 * @param {mixed}  language    - ID/Code of language
 * @param {string} deleteMode  - Removing method (delete/trash)
 * @param {string} loadSection - Section which will be closed in end
 */
var apOfferComparePhrases = function(action, language, deleteMode, loadSection) {
    switch (action) {
        case 'remove':
            if (!readCookie('ap_removing_english_notice')) {
                createCookie('ap_removing_english_notice', true);
                Ext.MessageBox.confirm(
                    lang.warning,
                    lang.removing_english_notice,
                    function(btn){
                        if (btn == 'yes') {
                            Ext.MessageBox.hide();
                            $('#nav_bar .center_compare').parent('a').click();
                        } else {
                            rlConfirm(lang['ext_notice_' + deleteMode], 'xajax_deleteLang', language, loadSection);
                        }
                    }
                );
            } else {
                rlConfirm(lang['ext_notice_' + deleteMode], 'xajax_deleteLang', language, loadSection);
            }
            break;
        case 'set_default':
            flynax.sendAjaxRequest('getCountMissingPhrases', {language: language}, function(response) {
                if (response.status == 'OK') {
                    if (response.count > 0) {
                        $(document).flModal({
                            click  : false,
                            width  : 550,
                            height : 'auto',
                            caption: '{/literal}{$lang.comparing_phrases_popup_title}{literal}',
                            content: '<div id="modal_content">' + lang.loading + '</div>',
                            onReady: function() {
                                var $closeButton = $('div.modal-window div span:last');

                                var text = '{/literal}{$lang.comparing_phrases_popup_content}{literal}';
                                text = text.replace('{count}', response.count);

                                var modalContent = text;
                                modalContent += '<p><input type="button" name="ok" value="';
                                modalContent += '{/literal}{$lang.import}{literal}' + '">';
                                modalContent += '<a href="javascript://" class="cancel">' + lang.cancel + '</a></p>';

                                $('#modal_content').html(modalContent);

                                $('#modal_content [name="ok"]').click(function () {
                                    $(this).val(lang.loading).prop('disabled', true);
                                    $('#modal_content a.cancel').hide();

                                    flynax.sendAjaxRequest('importMissingPhrases', {language: language},
                                        function(response) {
                                            createCookie('ap_removing_english_notice', true);
                                            $closeButton.click();
                                            xajax_setDefault('langs_container', language);
                                        }
                                    );
                                });

                                $('#modal_content a.cancel').click(function () {
                                    $closeButton.click();
                                });
                            }
                        });
                    } else {
                        xajax_setDefault('langs_container', language);
                    }
                }
            });
            break;
    }
};

$(document).ready(function(){
    languagesGrid = new gridObj({
        key: 'languages',
        id: 'grid',
        ajaxUrl: rlUrlHome + 'controllers/languages.inc.php?q=ext_list',
        defaultSortField: 'name',
        title: lang['ext_languages_manager'],
        fields: [
            {name: 'ID', mapping: 'ID', type: 'int'},
            {name: 'Data', mapping: 'Data', type: 'string'},
            {name: 'name', mapping: 'name', type: 'string'},
            {name: 'Number', mapping: 'Number', type: 'string'},
            {name: 'Direction', mapping: 'Direction', type: 'string'},
            {name: 'Status', mapping: 'Status'}
        ],
        columns: [
            {
                id: 'rlExt_item',
                header: lang['ext_name'],
                dataIndex: 'name',
                width: 50
            },{
                header: lang['ext_text_direction'],
                dataIndex: 'Direction',
                width: 10,
                editor: new Ext.form.ComboBox({
                    store: [{/literal}
                        ['ltr', '{$lang.ltr_direction_title}'],
                        ['rtl', '{$lang.rtl_direction_title}']
                    {literal}],
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
                header: lang['ext_phrases_number'],
                dataIndex: 'Number',
                width: 12,
                id: 'rlExt_item_bold',
                renderer: function(data, param1, param2) {
                    data += ' <a onclick="phrasesManager('+param2.id+')" class="green_11_bg" href="javascript:void(0)">{/literal}{$lang.manage_phrases}{literal}</a>';
                    return data;
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
                width: 80,
                fixed: true,
                dataIndex: 'Data',
                sortable: false,
                renderer: function(data, column, row) {
                    data = data.split('|');
                    var out = '';
                    var splitter = false;

                    if (rights[cKey].indexOf('edit') >= 0) {
                        out += "<a href='" + rlUrlHome + "index.php?controller=" + controller;
                        out += "&action=export&lang=" + data[0] + "'><img class='export' ext:qtip='";
                        out += lang['ext_export'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>";

                        out += "<a href='" + rlUrlHome + "index.php?controller=" + controller;
                        out += "&action=edit&lang=" + data[0] + "'><img class='edit' ext:qtip='";
                        out += lang['ext_edit'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                    }

                    if (rights[cKey].indexOf('delete') >= 0 && data[1] == 'false') {
                        var onclickFunction = '';

                        if (row.json.Code == 'en') {
                            onclickFunction = "apOfferComparePhrases('remove', '";
                            onclickFunction += Array(data[0]) + "', '" + delete_mod + "', 'admin_load')";
                        } else {
                            onclickFunction = "rlConfirm('" + lang['ext_notice_' + delete_mod] + "', ";
                            onclickFunction += "'xajax_deleteLang'" + ", '" + Array(data[0]) + "', ";
                            onclickFunction += "'admin_load'" + ')';
                        }

                        out += $('<a>')
                                    .attr({
                                        href   : 'javascript:void(0)',
                                        onclick: onclickFunction
                                    }).append(
                                        $('<img>')
                                            .addClass('remove')
                                            .attr({
                                                'ext:qtip': lang.ext_delete,
                                                src       : rlUrlHome + 'img/blank.gif'})
                                    )[0].outerHTML;
                    }
                    
                    return out;
                }
            }
        ]
    });

    {/literal}{rlHook name='apTplLanguagesGrid'}{literal}

    languagesGrid.init();
    grid.push(languagesGrid.grid);

    // disallow to disable last active language
    languagesGrid.grid.addListener('beforeedit', function(editEvent) {
        if (editEvent.field == 'Status' && editEvent.value == lang.active) {
            if (languagesGrid.store.data.items) {
                var count_active_lang = 0;
                for (var i = languagesGrid.store.data.items.length - 1; i >= 0; i--) {
                    if (languagesGrid.store.data.items[i].data.Status ==  lang.active) {
                        count_active_lang++;
                    }
                }

                if (count_active_lang == 1) {
                    printMessage('error', lang['ext_disallow_disable_lang']);
                    return false;
                }
            }
        }
    });
});
{/literal}</script>
<!-- languages grid create end -->

{rlHook name='apTplLanguagesMiddle'}

<!-- search button -->
{if $aRights.$cKey.edit}
<div class="aright" style="padding: 10px 0;">
    <a href="javascript:void(0)" onclick="show('lang_search_block');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
</div>
{/if}
<!-- search button end -->

<!-- search block -->
{if $aRights.$cKey.edit}
<div id="lang_search_block" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
    <table class="form">
    <tr>
        <td class="name">{$lang.phrase}</td>
        <td class="field">
            <input type="text" id="phrase" style="width:400px;max-width:400px;"/>
            <label style="display:block;padding: 5px 0;"><input type="checkbox" id="exact_match" /> {$lang.keyword_search_opt3}</label>
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.search_in}</td>
        <td class="field">
            <label><input name="criteria" type="radio" id="in_value" checked="checked" /> {$lang.phrase_text}</label>
            <label><input name="criteria" type="radio" id="in_key" /> {$lang.phrase_key}</label>
        </td>
    </tr>
    <tr>
        <td class="name" style="text-transform: capitalize;">{$lang.language}</td>
        <td class="field">
            <select class="{if $langCount < 2}disabled{/if}" id="in_language" {if $langCount < 2}disabled{/if}>
            {if $langCount > 1}<option value="all">{$lang.all}</option>{/if}
            {foreach from=$allLangs item='languages' name='lang_foreach'}
                <option value="{$languages.Code}">{$languages.name}</option>
            {/foreach}
            </select>
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.search_in_plugins}</td>
        <td class="field">
            <label><input name="search_in_plugins" type="radio" value="0" checked /> {$lang.no}</label>
            <label><input name="search_in_plugins" type="radio" value="1" /> {$lang.yes}</label>
        </td>
    </tr>
    </table>

    <div class="hide" id="plugins_list" style="margin-left: 185px;">
        <select id="in_plugin">
            <option value="all">{$lang.all}</option>
            {foreach from=$plugins_list item='plugin'}
                <option value="{$plugin.Key}">{$plugin.Name}</option>
            {/foreach}
        </select>
    </div>

    <table class="form">
    <tr>
        <td class="name no_divider"></td>
        <td class="field">
            <input id="search_button" type="button" value="{$lang.search}" />
            <div class="loader" id="search_load"></div> <a class="cancel" href="javascript:void(0)" onclick="show('lang_search_block')">{$lang.cancel}</a>
        </td>
    </tr>
    </table>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
</div>
{/if}
<!-- search block end -->

<!-- phrases grid -->
<div id="phrases"></div>
<script type="text/javascript">//<![CDATA[
var phrasesGrid;

{literal}
$(document).ready(function(){
    
    phrasesGrid = new gridObj({
        key: 'phrases',
        id: 'phrases',
        ajaxUrl: rlUrlHome + 'controllers/languages.inc.php?q=ext',
        updateMethod: 'POST',
        defaultSortField: 'Value',
        title: lang['ext_phrases_manager'],
        fields: [
            {name: 'Module', mapping: 'Module'},
            {name: 'Key', type: 'string'},
            {name: 'JS', type: 'JS'},
            {name: 'Value', mapping: 'Value', type: 'string'}
        ],
        columns: [
            {
                header: lang['ext_key'],
                dataIndex: 'Key',
                width: 30
            },{
                id: 'rlExt_item',
                header: lang['ext_value'],
                dataIndex: 'Value',
                width: 60,
                editor: new Ext.form.TextArea({
                    allowBlank: false
                }),
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: 'JS',
                dataIndex: 'JS',
                fixed: true,
                width: 70,
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
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: lang['ext_side'],
                dataIndex: 'Module',
                width: 15,
                editor: new Ext.form.ComboBox({
                    store: [
                        ['common', {/literal}'{$lang.module_common}'{literal}],
                        ['frontEnd', {/literal}'{$lang.module_frontEnd}'{literal}],
                        ['admin', {/literal}'{$lang.module_admin}'{literal}],
                        ['category', {/literal}'{$lang.module_category}'{literal}],
                        ['system', {/literal}'{$lang.module_system}'{literal}],
                        ['box', {/literal}'{$lang.module_box}'{literal}]
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
            }
        ]
    });

    $('input[name=search_in_plugins]').click(function() {
        if (parseInt($(this).val()))
            $('#plugins_list').slideDown('fast');
        else
            $('#plugins_list').slideUp('fast');
    });

    $('input#search_button').click(function(){
        current_lang_id = false;
        
        var phrase = $('#phrase').val();
        var search_lang= $('#in_language').val();
        var criteria = $('#in_value').is(':checked') ? 'in_value' : 'in_key';
        var exact_match = $('input#exact_match').is(':checked') ? 1 : 0;
        var search_in_plugins = parseInt($('input[name=search_in_plugins]:checked').val());
        var plugin = $('select#in_plugin').val();
        
        if ( phrase != '' || (search_in_plugins && plugin != 'all') )
        {
            var search = new Array();
            search.push( new Array('action', 'search') );
            search.push( new Array('criteria', criteria) );
            search.push( new Array('phrase', phrase) );
            search.push( new Array('lang_code', search_lang) );
            search.push( new Array('exact_match', exact_match) );

            //
            if (search_in_plugins)
                search.push( new Array('plugin', plugin) );

            phrasesGrid.filters = search;
            
            {/literal}{rlHook name='apTplLanguagesPhrasesGrid'}{literal}
            
            if ( !phrasesGridPush )
            {
                phrasesGrid.init();
                grid.push(phrasesGrid.grid)
                phrasesGridPush = true;
            }
            else
            {
                phrasesGrid.reload();
            }
        }
    });
});

var phrasesGridPush = false;
var current_lang_id = false;
var phrasesManager = function(id){
    if ( current_lang_id != id )
    {
        phrasesGrid.filters = new Array();
        phrasesGrid.filters[0] = ['lang_id', id];
        current_lang_id = id;
        
        if ( !phrasesGridPush )
        {
            phrasesGrid.init();
            grid.push(phrasesGrid.grid)
            phrasesGridPush = true;
        }
        else
        {
            phrasesGrid.resetPage();
            phrasesGrid.reload();
        }
    }
};

{/literal}
//]]>
</script>
<!-- phrases grid end -->

{rlHook name='apTplLanguagesBottom'}

{/if}

<!-- languages tpl end -->
