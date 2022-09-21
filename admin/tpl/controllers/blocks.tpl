<!-- blocks tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplBlocksNavBar'}

    {if $aRights.$cKey.add && $smarty.get.action != 'add'}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_block}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.blocks_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}
    {assign var='sPost' value=$smarty.post}

    {**
     * Prevent showing "Show box on" and "Show in categories" sections
     * @since 4.7.2
     *}
    {if $block.Key|strpos:'ltsb_' === 0 || $block.Key === 'my_profile_sidebar'}
        {assign var='preventChangeBoxPosition' value=true}
    {/if}

    <!-- add new/edit block -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;block={$smarty.get.block}{/if}" method="post" enctype="multipart/form-data">
            <input type="hidden" name="submit" value="1" />

            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="fromPost" value="1" />
            {/if}
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.key}</td>
                <td class="field">
                    <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
                </td>
            </tr>

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

            <tr>
                <td class="name"><span class="red">*</span>{$lang.block_side}</td>
                <td class="field">
                    <select name="side">
                        <option value="">{$lang.select}</option>
                        {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
                        <option value="{$sKey}" {if $sKey == $sPost.side}selected="selected"{/if}>{$block_side}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>

            {if !$block.Readonly}
            <tr>
                <td class="name"><span class="red">*</span>{$lang.block_type}</td>
                <td class="field">
                    <select {if !empty($block.Plugin) && $block.Type != 'html'}disabled="disabled"{/if} onchange="block_banner('btype_'+$(this).val(), '#btypes div');" name="type" class="{if !empty($block.Plugin) && $block.Type != 'html'}disabled{/if}">
                        <option value="">{$lang.select}</option>
                        {foreach from=$l_block_types item='block_type' name='types_f' key='tKey'}
                        <option value="{$tKey}" {if $tKey == $sPost.type}selected="selected"{/if}>{$block_type}</option>
                        {/foreach}
                    </select>
                    {if !empty($block.Plugin) && $block.Type != 'html'}<input type="hidden" name="type" value="{$sPost.type}" />{/if}
                </td>
            </tr>
            {/if}
            </table>

            <div id="btypes">

            <div id="btype_other" class="hide">
                <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.content}</td>
                    <td class="field">
                        <textarea {if $block.Readonly}readonly="readonly"{/if} rows="6" cols="" name="content" class="{if $block.Readonly}disabled{/if}">{$sPost.content}</textarea>
                    </td>
                </tr>
                </table>
            </div>

            <div class="hide" id="btype_html">
                <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.content}</td>
                    <td class="field ckeditor">
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                                {/foreach}
                            </ul>
                        {/if}

                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                            {assign var='dCode' value='html_content_'|cat:$language.Code}
                            {fckEditor name='html_content_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                            {if $allLangs|@count > 1}</div>{/if}
                        {/foreach}
                    </td>
                </tr>
                </table>
            </div>

            </div>

            <table class="form">
            {if !empty($pages_list)}
                <tr {if $preventChangeBoxPosition}class="hide"{/if}>
                    <td class="name">{$lang.show_on_pages}</td>
                    <td class="field" id="pages_obj">
                        <fieldset class="light">
                            {assign var='pages_phrase' value='admin_controllers+name+pages'}
                            <legend id="legend_pages" class="up">{$lang.$pages_phrase}</legend>
                            <div id="pages">
                                <div id="pages_cont" {if !empty($sPost.show_on_all)}style="display: none;"{/if}>
                                    {assign var='bPages' value=$sPost.pages}
                                    <table class="sTable" style="margin-bottom: 15px;">
                                    <tr>
                                        <td valign="top">
                                        {foreach from=$pages_list item='page' name='pagesF'}
                                        {assign var='pId' value=$page.ID}
                                        <div style="padding: 2px 8px;">
                                            <input class="checkbox" {if isset($bPages.$pId)}checked="checked"{/if} id="page_{$page.ID}" type="checkbox" name="pages[{$page.ID}]" value="{$page.ID}" /> <label class="cLabel" for="page_{$page.ID}">{$page.name}</label>
                                        </div>
                                        {assign var='perCol' value=$smarty.foreach.pagesF.total/3|ceil}

                                        {if $smarty.foreach.pagesF.iteration % $perCol == 0}
                                            </td>
                                            <td valign="top">
                                        {/if}
                                        {/foreach}
                                        </td>
                                    </tr>
                                    </table>
                                </div>

                                <div class="grey_area" style="margin: 0 0 5px;">
                                    {if $allowPageSticky}
                                        <label>
                                            <input id="show_on_all"
                                                   {if $sPost.show_on_all}checked="checked"{/if}
                                                   type="checkbox"
                                                   name="show_on_all"
                                                   value="true" /> {$lang.sticky}
                                        </label>
                                    {/if}
                                    <span id="pages_nav" {if $sPost.show_on_all}class="hide"{/if}>
                                        <span onclick="$('#pages_cont input').attr('checked', true).prop('checked', true);"
                                              class="green_10"
                                        >
                                            {$lang.check_all}
                                        </span>
                                        <span class="divider"> | </span>
                                        <span onclick="$('#pages_cont input').attr('checked', false).prop('checked', false);"
                                              class="green_10"
                                        >
                                            {$lang.uncheck_all}
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </fieldset>

                        <script type="text/javascript">{literal}
                        $(document).ready(function(){
                            $('#legend_pages').click(function(){
                                fieldset_action('pages');
                            });

                            $('input#show_on_all').click(function(){
                                $('#pages_cont').slideToggle();
                                $('#pages_nav').fadeToggle();
                            });

                            $('#pages input').click(function(){
                                if ($('#pages input:checked').length > 0) {
                                    //$('#show_on_all').prop('checked', false);
                                }
                            });
                        });
                        {/literal}</script>
                    </td>
                </tr>
            {/if}

            {if !empty($sections)}
                <tr {if $preventChangeBoxPosition}class="hide"{/if}>
                    <td class="name">{$lang.show_in_categories}</td>
                    <td class="field">
                        {include file="blocks"|cat:$smarty.const.RL_DS|cat:"categories_tree.tpl"}
                    </td>
                </tr>
            {/if}

            <tr>
                <td class="name">{$lang.use_block_design}</td>
                <td class="field">
                    {if $sPost.tpl == '1'}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {elseif $sPost.tpl == '0'}
                        {assign var='tpl_no' value='checked="checked"'}
                    {else}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {/if}
                    <label><input {$tpl_yes} class="lang_add" type="radio" name="tpl" value="1" /> {$lang.yes}</label>
                    <label><input {$tpl_no} class="lang_add" type="radio" name="tpl" value="0" /> {$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.use_block_header}</td>
                <td class="field">
                    {if $sPost.header == '1'}
                        {assign var='header_yes' value='checked="checked"'}
                    {elseif $sPost.header == '0'}
                        {assign var='header_no' value='checked="checked"'}
                    {else}
                        {assign var='header_yes' value='checked="checked"'}
                    {/if}
                    <label><input {$header_yes} class="lang_add" type="radio" name="header" value="1" /> {$lang.yes}</label>
                    <label><input {$header_no} class="lang_add" type="radio" name="header" value="0" /> {$lang.no}</label>
                </td>
            </tr>

            {rlHook name='apTplBlocksForm'}

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
    <!-- add new block end -->

    <!-- select category action -->
    <script type="text/javascript">

    {literal}
    function cat_chooser(cat_id){
        return true;
    }
    {/literal}

    {if $smarty.post.parent_id}
        cat_chooser('{$smarty.post.parent_id}');
    {elseif $smarty.get.parent_id}
        cat_chooser('{$smarty.get.parent_id}');
    {/if}

    </script>
    <!-- select category action end -->

    <!-- additional JS -->
    {if $sPost.type}
    <script type="text/javascript">
    {literal}
    $(document).ready(function(){
        block_banner('btype_{/literal}{$sPost.type}{literal}', '#btypes div');
    });

    {/literal}
    </script>
    {/if}
    <!-- additional JS end -->

    {rlHook name='apTplBlocksAction'}

{else}

    <script type="text/javascript">
    // blocks sides list
    var block_sides = [
    {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
        ['{$sKey}', '{$block_side}']{if !$smarty.foreach.sides_f.last},{/if}
    {/foreach}
    ];
    </script>

    <!-- blocks grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var blocksGrid;
    var sides_to_remove = ['integrated_banner', 'header_banner'];
    var box_prefixes = {if $l_block_excluded}{$l_block_excluded|@json_encode}{else}[]{/if};

    {literal}
    $(document).ready(function(){

        blocksGrid = new gridObj({
            key: 'blocks',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/blocks.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_blocks_manager'],
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Tpl', mapping: 'Tpl'},
                {name: 'Header', mapping: 'Header'},
                {name: 'Side', mapping: 'Side'},
                {name: 'Key', mapping: 'Key'},
                {name: 'deny_side', mapping: 'deny_side'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    id: 'rlExt_item_bold',
                    width: 40
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    id: 'rlExt_item',
                    width: 10
                },{
                    header: lang['ext_block_side'],
                    dataIndex: 'Side',
                    width: 10,
                    editor: new Ext.form.ComboBox({
                        store: block_sides,
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus: true
                    }),
                    renderer: function(val, ext, row){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
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
                    header: lang['ext_block_header'],
                    dataIndex: 'Header',
                    width: 10,
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
                    header: lang['ext_block_style'],
                    dataIndex: 'Tpl',
                    width: 10,
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
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&block="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteBlock\", \""+Array(data)+"\", \"section_load\" )' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplBlocksGrid'}{literal}

        blocksGrid.init();
        grid.push(blocksGrid.grid);

        var banners_removed = false;

        blocksGrid.grid.addListener('beforeedit', function(editEvent){
            if ('Side' == editEvent.field) {
                var column = editEvent.grid.colModel.columns[2];

                var pattern = new RegExp('^(' + box_prefixes.join('|') + ')');
                if (pattern.test(editEvent.record.data.Key) ) {
                    var items = column.editor.getStore().data.items;
                    var items_ids = [];
                    for (var i = 0; i < items.length; i++) {
                        if (sides_to_remove.indexOf(items[i].data.field1) >= 0) {
                            items_ids.push(i);
                        }
                    }

                    if (items_ids.length) {
                        for (var i in items_ids.reverse()) {
                            column.editor.getStore().removeAt(items_ids[i])
                        }

                        banners_removed = true;
                    }
                } else {
                    if (banners_removed) {
                        column.editor = new Ext.form.ComboBox({
                            store: block_sides,
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus: true
                        });
                        banners_removed = false;
                    }
                }
            }
        });
    });
    {/literal}
    //]]>
    </script>
    <!-- blocks grid end -->

    {rlHook name='apTplBlocksBottom'}
{/if}

<!-- blocks tpl end -->
