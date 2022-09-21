<!-- content slides -->

<div id="nav_bar">
    {rlHook name='apTplSlidesNavBar'}

    {if $aRights.$cKey.add && $smarty.get.action != 'add'}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.items_list}</span><span class="right"></span></a>
</div>

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add new/edit item -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&slide={$smarty.get.slide}{/if}" 
              method="post"
              enctype="multipart/form-data">
            <input type="hidden" name="submit" value="1" />
            
            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="fromPost" value="1" />
            {/if}

            <table class="form">
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.photo}
                </td>
                <td class="field_tall">
                    <input class="file" type="file" name="picture" />

                    {if $tpl_settings.home_page_slides_size}
                        <span class="field_description">{$lang.recommended_resolution}: {$tpl_settings.home_page_slides_size} px</span>
                    {/if}

                    {if $item_info.Picture}
                        <div style="padding: 15px 0;">
                            <img style="max-width: 200px;max-height: 200px;" src="{$smarty.const.RL_FILES_URL}slides/{$item_info.Picture}" />
                        </div>
                    {/if}
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.title}
                </td>
                <td>
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" 
                                {if $smarty.foreach.langF.first}
                                class="active"
                                {/if}
                                >{$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                        <input type="text" name="title[{$language.Code}]" value="{$sPost.title[$language.Code]}" maxlength="128" style="width: 100%;max-width: 650px;" />
                        {if $allLangs|@count > 1}
                                <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>
            
            <tr>
                <td class="name">{$lang.description}</td>
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
                        <textarea cols="" rows="" name="description[{$language.Code}]">{$sPost.description[$language.Code]}</textarea>
                        {if $allLangs|@count > 1}</div>{/if}
                    {/foreach}
                </td>
            </tr>
            
            <tr>
                <td class="name">{$lang.url}</td>
                <td class="field">
                    <input name="url" type="text" value="{$sPost.url}" placeholder="https://" maxlength="256" class="w350" />
                </td>
            </tr>

            {rlHook name='apTplSlidesForm'}
            
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
    <!-- add new/edit item end -->
{else}
    <!-- grid -->
    <div id="grid"></div>

    <script>
    var slidesGrid;
    
    {literal}
    $(function(){
        var expanderTpl = '<div style="margin: 0 0px 5px 44px"><img style="max-width: 200px;max-height: 100px;" src="{src}" /></div>';

        slidesGrid = new gridObj({
            key: 'slides',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/slides.inc.php?q=ext',
            defaultSortField: 'ID',
            title: lang['ext_manager'],
            expander: true,
            expanderTpl: expanderTpl,
            fields: [
                {name: 'Title', mapping: 'Title', type: 'string'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Picture', mapping: 'Picture', type: 'string'},
                {name: 'src', mapping: 'src', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'ID', mapping: 'ID', type: 'int'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_title'],
                    dataIndex: 'Title',
                    width: 40,
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        maxLength: 128,
                        autoCreate: {
                            tag: 'input',
                            type: 'text',
                            maxlength: '128'
                        }
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 100,
                    fixed: true,
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
                    width: 80,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(id) {
                        var $out = $('<center>');

                        if (rights[cKey].indexOf('edit') >= 0) {
                            $out.append(
                                $('<a>')
                                    .attr('href', rlUrlController + '&action=edit&slide=' + id)
                                    .append(
                                        $('<img>')
                                            .addClass('edit')
                                            .attr('ext:qtip', lang['ext_edit'])
                                            .attr('src', rlUrlHome + 'img/blank.gif')
                                    )
                            );
                        }

                        if (rights[cKey].indexOf('delete') >= 0) {
                            $out.append(
                                $('<img>')
                                    .addClass('remove')
                                    .attr('ext:qtip', lang['ext_delete'])
                                    .attr('src', rlUrlHome + 'img/blank.gif')
                                    .attr('data-id', id)
                            );
                        }
                        
                        return $out.prop('outerHTML');
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplslidesGrid'}{literal}
        
        slidesGrid.init();
        grid.push(slidesGrid.grid);

        // Delete item handler
        $('#grid').on('click', 'img.remove', function(){
            var id = $(this).data('id');

            if (!id) {
                return;
            }

            rlConfirm(lang['ext_notice_' + delete_mod], 'deleteSlide', [id]);
        });
    });

    var deleteSlide = function(id){
        flynax.sendAjaxRequest('removeSlide', {id: id}, function(response){
            if (response.status == 'OK') {
                slidesGrid.reload();
            }
        });
    }

    {/literal}
    </script>
    <!-- grid end -->

    {rlHook name='apTplSlidesBottom'}
{/if}

<!-- content slides end -->
