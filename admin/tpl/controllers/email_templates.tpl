<!-- email templates tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.caret.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js"></script>

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplEmailTemplatesNavBar'}
    
    {if !$smarty.get.action}<a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_template}</span><span class="right"></span></a>{/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.templates_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add new template -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form  onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;tpl={$smarty.get.tpl}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.key}</td>
            <td class="field">
                <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 250px;" value="{$sPost.key}" />
            </td>
        </tr>
        
        <tr>
            <td class="name"><span class="red">*</span>{$lang.subject}</td>
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
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" style="width: 500px;" maxlength="350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        
        <tr>
            <td class="name"><span class="red">*</span>{$lang.content_type}</td>
            <td class="field">
                <label><input type="radio" name="type" value="plain" {if $sPost.type == 'plain' || !$sPost.type}class="checked"{/if} /> {$lang.plain_text}</label>
                <label><input type="radio" name="type" value="html" {if $sPost.type == 'html'}class="checked"{/if} /> {$lang.html_code}</label>
                
                <script type="text/javascript">
                flynax.switchContentType('input[name=type]', [{foreach from=$allLangs item='language' name='langF'}'body_{$language.Code}'{if !$smarty.foreach.langF.last},{/if}{/foreach}]);
                </script>
            </td>
        </tr>
        
        <tr>
            <td class="name"><span class="red">*</span>{$lang.content}</td>
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
                    <select id="var_sel_{$language.Code}">
                        <option value="">{$lang.select}</option>
                        {foreach from=$l_email_variables item='var'}
                        <option value="{$var}">{$var}</option>
                        {/foreach}
                    </select>
                    <input class="caret_button no_margin" id="input_{$language.Code}" type="button" value="{$lang.add}" style="margin-left: 5px" />
                    <span class="field_description_noicon">{$lang.add_template_variable}</span>
                    <div style="padding: 5px 0 0 0;">
                        <div class="hide">{if $sPost.type == 'html'}{$sPost.description[$language.Code]}{/if}</div>
                        <textarea id="body_{$language.Code}" rows="9" cols="40" name="description[{$language.Code}]" style="height: 200px;">{if $sPost.type == 'plain'}{$sPost.description[$language.Code]}{/if}</textarea>
                    </div>
                    {if $allLangs|@count > 1}
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        
        {rlHook name='apTplEmailTemplatesForm'}
        
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
    <!-- add new template end -->
    
    <script type="text/javascript">{literal}
    $(document).ready(function(){
        flynax.putCursorInCKTextarea('body_' + $('.caret_button:first').attr('id').split('_')[1]);

        $('.caret_button').click(function(){
            var id       = $(this).attr('id').split('_')[1];
            var variable = $('#var_sel_'+id).val();
            var type     = $('input[name=type]:checked').val();

            if (type == 'plain') {
                var text     = $('#body_' + id).val();
                var caret    = $('#body_' + id).getSelection();
                var new_text = text.substring(0, caret.start) + variable + text.substring(caret.end, text.length);

                $('#body_' + id).val(new_text).focus();
                $('#body_' + id).setCursorPosition(caret.start + variable.length);
            } else {
                var instance = CKEDITOR.instances['body_' + id];
                var offset   = instance.getSelection().getRanges()[0];

                if (offset) {
                    var text = offset.startContainer.getText();

                    if (offset.startOffset > 0 
                        && offset.endOffset > 0 
                        && instance.getData().trim().replace(/<br \/\>|[\t\n\r]/gi, '').length != text.length
                    ) {
                        offset.startContainer.setText(
                            text.substring(0, offset.startOffset) + 
                            variable + 
                            text.substring(offset.endOffset, text.length)
                        );
                    } else {
                        instance.setData(variable + instance.getData());
                    }
                }
            }
        });
    });
    {/literal}</script>

    {rlHook name='apTplEmailTemplatesAction'}

{else}

    <!-- email-templates grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var emailTemplatesGrid;
    
    {literal}
    $(document).ready(function(){
        
        emailTemplatesGrid = new gridObj({
            key: 'emailTemplates',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/email_templates.inc.php?q=ext',
            defaultSortField: 'ID',
            remoteSortable: true,
            title: lang['ext_email_templates_manager'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'subject', mapping: 'subject', type: 'string'},
                {name: 'Position', mapping: 'Position'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Type', mapping: 'Type'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    fixed: true,
                    width: 35
                },{
                    header: lang['ext_subject'],
                    dataIndex: 'subject',
                    width: 60,
                    id: 'rlExt_item'
                },{
                    header: lang['ext_key'],
                    dataIndex: 'Key',
                    width: 30
                },{
                    header: '{/literal}{$lang.content_type}{literal}',
                    dataIndex: 'Type',
                    width: 10
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
                    width: 50,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data) {
                        return "<center><a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit&tpl="+data+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a></center>";
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplEmailTemplatesGrid'}{literal}
        
        emailTemplatesGrid.init();
        grid.push(emailTemplatesGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- email-templates grid end -->
    
    {rlHook name='apTplEmailTemplatesBottom'}

{/if}

<!-- email templates tpl end -->
