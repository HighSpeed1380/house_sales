<!-- filed bound boxes tpl -->

<div id="nav_bar">
    {if $aRights.$cKey.add}
        {if !$smarty.get.action && !$smarty.get.box}
            <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_block}</span><span class="right"></span></a>
        {elseif $smarty.get.action == 'edit'}
            <a href="{$rlBaseC}box={$smarty.get.box}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.manage}</span><span class="right"></span></a>
        {/if}
    {/if}
    {if $smarty.get.box && $smarty.get.item}
        <a href="{$rlBaseC}&box={$smarty.get.box}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.fb_items_list}</span><span class="right"></span></a>
    {else}
        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.blocks_list}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.box && !$smarty.get.action}
        <a href="{$rlBaseC}action=edit&box={$smarty.get.box}" class="button_bar"><span class="left"></span><span class="center_edit">{$lang.edit_block}</span><span class="right"></span></a>

        {if !$smarty.get.item}
            {if $box_info.Multiple_items}
                <a href="javascript:void(0)"
                   class="button_bar"
                   id="add_new_item">
                   <span class="left"></span><span class="center_add">{$lang.add_item}</span><span class="right"></span>
               </a>
            {else}
                <a href="javascript:void(0)"
                   class="button_bar"
                   onclick="rlConfirm('{$lang.fb_rebuild_notice}', 'fbbReCopyItems', '{$smarty.get.box}')">
                   <span class="left"></span><span class="center_build">{$lang.fb_rebuild_box_items}</span><span class="right"></span>
               </a>
            {/if}
        {/if}
    {/if}
</div>

<!-- Add item in mutiple items mode -->
{if $box_info.Multiple_items}
    <style type="text/css">
    {literal}

    #found_items {
        flex-wrap: wrap;
    }
    .box-item {
        display: flex;
        align-items: center;
        border: 1px #cfcdce solid;
        background: #f1f1f1;
        border-radius: 3px;
        padding: 10px;
        margin: 0 15px 15px 0;
        flex: 0 0 190px;
        min-width: 0;
    }
    .box-item:hover {
        border: 1px #afafaf solid;
        background: #e8e8e8;
    }
    .box-item:hover .box-item_action {
        opacity: 1;
    }
    .box-item_name {
        flex: 1;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
        padding-right: 5px;
    }
    .box-item_action {
        width: 20px;
        height: 20px;
        background: url('{/literal}{$smarty.const.RL_URL_HOME}{$smarty.const.ADMIN}{literal}/img/form.png') 0 -650px no-repeat;
        opacity: .7;
        cursor: pointer;
    }

    {/literal}
    </style>

    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}

        <form name="keyword_search" method="post" accept="">
            <input style="width: 350px;max-width: 100%;" type="text" name="keyword" placeholder="{$lang.keyword_search}" />
            <input type="submit" value="{$lang.search}" data-phrase="{$lang.search}" />
        </form>

        <div style="margin-top: 20px;min-height: 100px;">
            <span id="start_hint" style="padding: 0;" class="field_description_noicon">{$lang.fbb_items_search_hint}</span>
            <div id="found_items" class="hide"></div>
            <div id="limit_hint" class="hide" style="padding: 10px 0;">
                <div class="field_description" style="margin: 0;">{$lang.fbb_too_much_items_found}</div>
            </div>
        </div>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}javascript/jsRender.js"></script>

    <script id="item-tpl" type="text/x-jsrender">
        <div class="box-item" title="[%:name%][%if Parent_name%], [%:Parent_name%][%/if%]">
            <div class="box-item_name">
                [%if Parent_name%]<b>[%/if%][%:name%][%if Parent_name%]</b>, [%:Parent_name%][%/if%]
            </div>
            <div class="box-item_action" data-key="[%:Key%]" data-name="[%:name%]" title="{$lang.add}"></div>
        </div>
    </script>

    <script>
    var multifield_mode = {if $box_multifield}1{else}0{/if};
    var box_id = '{$box_info.ID}';
    var format_id = '{$format_id}';

    {literal}

    $(function(){
        var items_limit = 20;

        var timer = false;
        var val = null;

        var $form = $('[name=keyword_search]');
        var $button = $form.find('[type=submit]');
        var $input = $form.find('[name=keyword]');
        var $startHint = $('#start_hint');
        var $itemsArea = $('#found_items');
        var $limitHint = $('#limit_hint');

        var searchItems = function(){
            if (val.length <= 2) {
                $startHint.show();
                $itemsArea.empty().hide();
            } else {
                $button.val(lang.loading);

                var data = {
                    q: 'ext',
                    parent: format_id,
                    action: 'search',
                    Name: val,
                    Search_all_levels: 1,
                    start: 0,
                    limit: items_limit,
                    sort: 'name'
                };
                var ajax_url = rlPlugins + '/multiField/admin/multi_formats.inc.php';

                $.getJSON(ajax_url, data, function(response, status) {
                    $itemsArea.empty();
                    $limitHint.hide();
                    $button.val($button.data('phrase'));

                    if (status == 'success') {
                        $itemsArea.css('display', 'flex');
                        $startHint.hide();

                        if (response.data.length) {
                            $itemsArea.append($('#item-tpl').render(response.data));
                        } else {
                            $itemsArea.append('<span style="padding: 0;" class="field_description_noicon">' + lang['fbb_no_items_found'] + '</span>');
                        }

                        if (parseInt(response.total) > items_limit) {
                            $limitHint.show();
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }
                });
            }
        }

        $form.submit(function(){
            val = $input.val();
            searchItems();

            return false;
        });

        $itemsArea.on('click', '.box-item_action', function(){
            var $item = $(this);

            var data = {
                item: 'fbbAddItem',
                boxID: box_id,
                itemKey: $(this).data('key'),
                itemName: $(this).data('name')
            }

            $.getJSON(rlConfig['ajax_url'], data, function(response, status) {
                if (status == 'success') {
                    if (response.status == 'OK') {
                        $item.closest('.box-item').hide();
                        itemsGrid.reload();

                        printMessage('notice', response.message);
                    } else {
                        printMessage('error', response.message);
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }
            });
        });

        $('#add_new_item').click(function(){
            show('search');
        });
    });

    {/literal}
    </script>
{/if}
<!-- Add item in mutiple items mode -->

{if $smarty.get.action == 'edit_item'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {assign var='sPost' value=$smarty.post}
    <form action="{$rlBaseC}box={$smarty.get.box}&action=edit_item&item={$smarty.get.item}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="fromPost" value="1" />
        <table class="form">

        {if $fbb_info.Style != 'text'}
        <tr>
            <td class="name">{$lang.fb_item_icon}</td>
            <td class="field">
                <input class="file" type="file" name="icon"/>

                {if $fbb_info.Style != 'responsive'}
                    {assign var='gallery_link' value='<a href="javascript:void(0);" id="open_gallery">$1</a>'}
                    {$lang.fb_select_from_gallery|regex_replace:'/\[(.*)\]/':$gallery_link}

                    {include file=$smarty.const.RL_PLUGINS|cat:'fieldBoundBoxes/admin/icon_manager.tpl'}

                    <input type="hidden" name="svg_icon" />
                {/if}

                <span class="field_description">{if $fbb_info.Style == 'responsive'}{$lang.fb_responsive_style_icons_hint}{else}{$fbb_info.Icons_width}px / {$fbb_info.Icons_height}px{/if}</span>

                <div id="gallery"{if empty($sPost.icon)} class="hide"{/if}>
                    <div style="margin: 1px 0 4px 0;">
                        <fieldset style="margin-top:10px">
                            <legend id="legend_details" class="up" onclick="fieldset_action('details');">{$lang.fb_current_icon}</legend>
                            <div id="fileupload" class="ui-widget" style="padding: 0;">
                                <span class="item active template-download"
                                      style="margin: 0 0 5px 0;{if $fbb_info.Style == 'responsive'}width: 200px;{else}width: {$fbb_info.Icons_width+4}px;{/if}">
                                    <img style="box-sizing: border-box;width: 100%" src="{$smarty.const.RL_FILES_URL}{$sPost.icon}" class="thumbnail" />
                                    <img title="{$lang.delete}" alt="{$lang.delete}" class="delete" src="{$rlTplBase}/img/blank.gif" onclick="fbbDeleteIcon('{$item_id}');" />
                                </span>
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div class="loading" id="photos_loading" style="width: 100%;"></div>
            </td>
        </tr>
        {/if}

        <tr>
            <td class="name"><span class="red">*</span>{$lang.fb_item_path}
            </td>
            <td class="field">
                <table>
                    <tr>
                        <td>
                            <span style="padding: 0 5px 0 0;" class="field_description_noicon">
                                {$smarty.const.RL_URL_HOME}{$fb_page_info.Path}/</span>
                        </td>

                        <td>
                            <input type="text" name="path" value="{$sPost.path}" />
                            <span class="field_description_noicon" style="padding:0" id="path_postfix">{if $fbb_info.postfix}.html{/if}</span>
                        </td>
                    
                        <td>
                            <span class="field_description_noicon" id="cat_postfix_el">
                                {if $sPost.type}{if $listing_types[$sPost.type].Cat_postfix}.html{else}/{/if}{/if}
                            </span>

                            {if $smarty.get.action == 'add'}
                                <span class="field_description"> - {$lang.fb_regenerate_path_desc}</span>
                            {/if}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {if $fbb_info.Style != 'icon'}
        <tr>
            <td class="name">
                {$lang.name}
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
                    <input placeholder="{$option_names[$language.Code]}" type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        {/if}

        <tr>
            <td class="name">
                {$lang.title}
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
                    <input type="text" name="title[{$language.Code}]" value="{$sPost.title[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">
                {$lang.h1_heading}
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
                    <input type="text" name="h1[{$language.Code}]" value="{$sPost.h1[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.h1_heading} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">
                {$lang.description}
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
                    {if $allLangs|@count > 1}<div class="ckeditor tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    {assign var='dCode' value='description_'|cat:$language.Code}
                    {fckEditor name='description_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.meta_description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {assign var='lMetaDescription' value=$sPost.meta_description}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="50" rows="2" name="meta_description[{$language.Code}]">{$lMetaDescription[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.meta_keywords}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {assign var='lMetaKeywords' value=$sPost.meta_keywords}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="50" rows="2" name="meta_keywords[{$language.Code}]">{$lMetaKeywords[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        
        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status">
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>
                        {$lang.active}
                    </option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>
                        {$lang.approval}
                    </option>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{$lang.edit}" />
            </td>
        </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{elseif $smarty.get.action}
    {assign var='sPost' value=$smarty.post}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&box={$smarty.get.box}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />

        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}
        
        <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.fb_field}</td>
                <td class="field">
                    <select name="fbb[field_key]" {if $smarty.get.action == 'edit'}disabled="disabled" class="disabled"{/if}>
                        <option value="0">{$lang.select}</option>
                        {foreach from=$fields item="field"}
                            <option
                                {if $sPost.fbb.field_key == $field.Key}selected="selected"{/if}
                                value="{$field.Key}">
                                    {$field.name}
                            </option>
                        {/foreach}
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.listing_type}</td>
                <td class="field">
                    <select name="fbb[listing_type]">
                        <option value="">- {$lang.all} -</option>
                            {foreach from=$listing_types item='l_type'}
                                <option 
                                    {if $sPost.fbb.listing_type == $l_type.Key}selected="selected"{/if}
                                    value="{$l_type.Key}">
                                        {$l_type.name}
                                </option>
                            {/foreach}
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.fb_show_empty}</td>
                <td class="field">
                    {if $sPost.fbb.show_empty == '1'}
                        {assign var='empty_yes' value='checked="checked"'}
                    {elseif $sPost.fbb.show_empty == '0'}
                        {assign var='empty_no' value='checked="checked"'}
                    {else}
                        {assign var='empty_yes' value='checked="checked"'}
                    {/if}

                    <label>
                        <input {$empty_yes} class="lang_add" type="radio" name="fbb[show_empty]" value="1" />
                            {$lang.yes}
                    </label>
                    <label>
                        <input {$empty_no} class="lang_add" type="radio" name="fbb[show_empty]" value="0" />
                            {$lang.no}
                    </label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.display_counter}</td>
                <td class="field">
                    {if $sPost.fbb.show_count == '1'}
                        {assign var='count_yes' value='checked="checked"'}
                    {elseif $sPost.fbb.show_count == '0'}
                        {assign var='count_no' value='checked="checked"'}
                    {else}
                        {assign var='count_yes' value='checked="checked"'}
                    {/if}

                    <label>
                        <input {$count_yes} class="lang_add" type="radio" name="fbb[show_count]" value="1" /> 
                            {$lang.yes}
                    </label>
                    <label>
                        <input {$count_no} class="lang_add" type="radio" name="fbb[show_count]" value="0" /> 
                            {$lang.no}
                    </label>

                    <label id="counter_single_line" class="hide"><input type="checkbox" name="single_line_counter" value="1"{if $sPost.single_line_counter} checked="chekced"{/if} /> {$lang.fbb_counter_single_line}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.order_type}</td>
                <td class="field">
                    <select name="sorting">
                        <option value="position" {if $sPost.sorting == 'position'}selected="selected"{/if}>
                            {$lang.position}
                        </option>
                        <option value="alphabet" {if $sPost.sorting == 'alphabet'}selected="selected"{/if}>
                            {$lang.alphabetic}
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.status}</td>
                <td class="field">
                    <select name="status">
                        <option value="active" {if $sPost.box.status == 'active'}selected="selected"{/if}>
                            {$lang.active}
                        </option>
                        <option value="approval" {if $sPost.box.status == 'approval'}selected="selected"{/if}>
                            {$lang.approval}
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.fb_box_style}</td>
                <td class="field">
                    <select name="fbb[style]">
                        <option value="text" {if $sPost.fbb.style == 'text'}selected="selected"{/if}>{$lang.fb_box_style_text}</option>
                        <option value="text_pic" {if $sPost.fbb.style == 'text_pic'}selected="selected"{/if}>{$lang.fb_box_style_text_pic}</option>
                        <option value="icon" {if $sPost.fbb.style == 'icon'}selected="selected"{/if}>{$lang.fb_box_style_icon}</option>
                        <option value="responsive" {if $sPost.fbb.style == 'responsive'}selected="selected"{/if}>{$lang.fb_box_style_responsive}</option>
                    </select>
                </td>
            </tr>
            </table>
            
            <div id="plate_orientation_area" class="hide">
                <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.fbb_orientation}</td>
                    <td class="field">
                        {if $sPost.fbb.orientation == 'landscape'}
                            {assign var='landscape' value='checked="checked"'}
                        {elseif $sPost.fbb.orientation == 'portrait'}
                            {assign var='portrait' value='checked="checked"'}
                        {else}
                            {assign var='landscape' value='checked="checked"'}
                        {/if}
                        <label>
                            <input {$landscape} class="lang_add" type="radio" name="fbb[orientation]" value="landscape" />
                                {$lang.fbb_landscape}</label>
                        <label>
                            <input {$portrait} class="lang_add" type="radio" name="fbb[orientation]" value="portrait" />
                                {$lang.fbb_portrait}</label>
                    </td>
                </tr>
            </table>
            </div>

            <div id="icons_area" class="hide">
                <table class="form">
                <tr>
                    <td class="divider" colspan="3">
                        <div class="inner" id="icon_settings_heading">{$lang.fb_icon_settings}</div>
                    </td>
                </tr>
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.fb_icons_position}</td>
                    <td class="field">
                        <select name="fbb[icons_position]">
                            {foreach from=","|explode:"left,right,top,bottom" item="side"}
                                <option 
                                    {if $sPost.fbb.icons_position}
                                        {if $sPost.fbb.icons_position == $side}
                                            selected="selected"
                                        {/if}
                                    {elseif $side == 'top'}
                                        selected="selected"
                                    {/if}
                                    value="{$side}">
                                        {$sides.$side}
                                </option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.width}</td>
                    <td class="field">
                        <input
                            type="text"
                            class="numeric"
                            name="fbb[icons_width]"
                            style="width:30px"
                            value="{if $sPost.fbb.icons_width}{$sPost.fbb.icons_width}{else}70{/if}"
                            />
                    </td>
                </tr>
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.height}</td>
                    <td class="field">
                        <input 
                            type="text"
                            class="numeric"
                            name="fbb[icons_height]"
                            style="width:30px"
                            value="{if $sPost.fbb.icons_height}{$sPost.fbb.icons_height}{else}70{/if}"
                            />
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.fbb_resize_pictures}</td>
                    <td class="field">
                        {assign var='checkbox_field' value='resize_icons'}

                        {if $sPost.fbb.$checkbox_field == '1'}
                            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                        {elseif $sPost.fbb.$checkbox_field == '0'}
                            {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                        {else}
                            {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                        {/if}

                        <input {$resize_icons_yes} type="radio" id="{$checkbox_field}_yes" name="fbb[{$checkbox_field}]" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                        <input {$resize_icons_no} type="radio" id="{$checkbox_field}_no" name="fbb[{$checkbox_field}]" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                    </td>
                </tr>
                </table>
            </div>

            <table class="form">
            <tr>
                <td class="divider" colspan="3">
                    <div class="inner">{$lang.fb_box_settings}</div>
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.name}</td>
                <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                                <li 
                                    lang="{$language.Code}"
                                    {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}
                                </li>
                            {/foreach}
                        </ul>
                    {/if}

                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}

                            <input
                                type="text"
                                name="box[name][{$language.Code}]"
                                value="{$sPost.box.name[$language.Code]}"
                                style="width: 250px;"
                                maxlength="50"/>

                        {if $allLangs|@count > 1}
                                <span class="field_description_noicon">
                                    {$lang.name} (<b>{$language.name}</b>)
                                </span>
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.block_side}</td>
                <td class="field">
                    <select name="box[side]">
                        <option value="">{$lang.select}</option>
                            {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
                                {if $sKey != 'header_banner' && $sKey != 'integrated_banner'}
                                <option
                                    value="{$sKey}"
                                    {if $sKey == $sPost.box.side}selected="selected"{/if}>
                                        {$block_side}
                                </option>
                                {/if}
                        {/foreach}
                    </select>
                </td>
            </tr>
            </table>

            <div class="column-options">
                <table class="form">
                <tr>
                    <td class="name">{$lang.number_of_columns}</td>
                    <td class="field">
                        <select name="fbb[columns]">
                            <option value="auto"{if $sPost.fbb.columns == 'auto' || !$sPost.fbb.columns} selected="selected"{/if}>{$lang.fb_auto}</option>
                            {section name='fb_cols' start=1 loop=5 step=1}
                            <option value="{$smarty.section.fb_cols.index}"{if $sPost.fbb.columns == $smarty.section.fb_cols.index} selected="selected"{/if}>
                                {$smarty.section.fb_cols.index} {$lang.fb_columns}</option>
                            {/section}
                        </select>
                    </td>
                </tr>
                </table>
            </div>

            <table class="form">
            <tr>
                <td class="name">{$lang.use_block_design}</td>
                <td class="field">
                    {if $sPost.box.tpl == '1'}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {elseif $sPost.box.tpl == '0'}
                        {assign var='tpl_no' value='checked="checked"'}
                    {else}
                        {assign var='tpl_yes' value='checked="checked"'}
                    {/if}
                    <label>
                        <input {$tpl_yes} class="lang_add" type="radio" name="box[tpl]" value="1" />
                            {$lang.yes}</label>
                    <label>
                        <input {$tpl_no} class="lang_add" type="radio" name="box[tpl]" value="0" />
                            {$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.use_block_header}</td>
                <td class="field">
                    {if $sPost.box.header == '1'}
                        {assign var='header_yes' value='checked="checked"'}
                    {elseif $sPost.box.header == '0'}
                        {assign var='header_no' value='checked="checked"'}
                    {else}
                        {assign var='header_yes' value='checked="checked"'}
                    {/if}

                    <label>
                        <input {$header_yes} class="lang_add" type="radio" name="box[header]" value="1" />
                            {$lang.yes}</label>
                    <label>
                        <input {$header_no} class="lang_add" type="radio" name="box[header]" value="0" />
                            {$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.show_on_pages}</td>
                <td class="field" id="pages_obj">
                    <fieldset class="light">
                        {assign var='pages_phrase' value='admin_controllers+name+pages'}
                        <legend id="legend_pages" class="up">{$lang.$pages_phrase}</legend>
                        <div id="pages">
                            <div id="pages_cont" {if !empty($sPost.box.show_on_all)}style="display: none;"{/if}>
                                {assign var='bPages' value=$sPost.box.pages}
                                <table class="sTable" style="margin-bottom: 15px;">
                                <tr>
                                <td valign="top">
                                    {foreach from=$pages item='page' name='pagesF'}
                                    {assign var='pId' value=$page.ID}
                                    <div style="padding: 2px 8px;">
                                        <input
                                            class="checkbox"
                                            {if isset($bPages.$pId)}checked="checked"{/if}
                                            id="page_{$page.ID}"
                                            type="checkbox"
                                            name="box[pages][{$page.ID}]"
                                            value="{$page.ID}"/>

                                            <label class="cLabel" for="page_{$page.ID}">{$page.name}</label>
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
                                <label>
                                    <input
                                        id="show_on_all"
                                        {if $sPost.box.show_on_all}checked="checked"{/if}
                                        type="checkbox"
                                        name="box[show_on_all]"
                                        value="true"/>
                                            {$lang.sticky}
                                </label>
                                <span id="pages_nav" {if $sPost.box.show_on_all}class="hide"{/if}>
                                    <span onclick="$('#pages_cont input').attr('checked', true);" class="green_10">
                                        {$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#pages_cont input').attr('checked', false);" class="green_10">
                                        {$lang.uncheck_all}</span>
                                </span>
                            </div>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.show_in_categories}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_cats" class="up" onclick="fieldset_action('cats');">
                            {$lang.categories}</legend>

                        <div id="cats">
                            <div
                                id="cat_checkboxed"
                                style="margin: 0 0 8px;{if $sPost.box.cat_sticky}display: none{/if}">

                                <div class="tree">
                                    {foreach from=$sections item='section'}
                                        <fieldset class="light">
                                            <legend 
                                                id="legend_section_{$section.ID}"
                                                class="up"
                                                onclick="fieldset_action('section_{$section.ID}');">
                                                {$section.name}</legend>

                                            <div id="section_{$section.ID}">
                                            {if !empty($section.Categories)}
                                                {include 
                                                    file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_checkbox.tpl' categories=$section.Categories first=true}
                                            {else}
                                                <div style="padding: 0 0 8px 10px;">{$lang.no_items_in_sections}</div>
                                            {/if}
                                            </div>
                                        </fieldset>
                                    {/foreach}
                                </div>

                                <div style="padding: 0 0 6px 37px;">
                                    <label>
                                        <input 
                                            {if !empty($sPost.subcategories)}checked="checked"{/if}
                                            type="checkbox"
                                            name="box[subcategories]"
                                            value="1"
                                            /> {$lang.include_subcats}
                                    </label>
                                </div>
                            </div>

                            <script type="text/javascript">
                            var tree_selected = {if $smarty.post.categories}[{foreach from=$smarty.post.categories item='post_cat' name='postcatF'}['{$post_cat}']{if !$smarty.foreach.postcatF.last},{/if}{/foreach}]{else}false{/if};
                            var tree_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}['{$parent_point}']{if !$smarty.foreach.parentF.last},{/if}{/foreach}]{else}false{/if};
                            {literal}

                            $(document).ready(function(){
                                flynax.treeLoadLevel(
                                    'checkbox',
                                    'flynax.openTree(tree_selected, tree_parentPoints)',
                                    'div#cat_checkboxed'
                                );
                                flynax.openTree(tree_selected, tree_parentPoints);
                                
                                $('input[name="box[cat_sticky]"]').click(function(){
                                    $('#cat_checkboxed').slideToggle();
                                    $('#cats_nav').fadeToggle();
                                });
                            });
                            
                            {/literal}
                            </script>
            
                            <div class="grey_area">
                                <label>
                                    <input
                                        class="checkbox"
                                        {if $sPost.box.cat_sticky}checked="checked"{/if}
                                        type="checkbox"
                                        name="box[cat_sticky]"
                                        value="true" />
                                    {$lang.sticky}
                                </label>
                                <span id="cats_nav" {if $sPost.cat_sticky}class="hide"{/if}>
                                    <span
                                        onclick="$('#cat_checkboxed div.tree input').attr('checked', true);"
                                        class="green_10">
                                            {$lang.check_all}
                                    </span>
                                    <span class="divider"> | </span>
                                    <span
                                        onclick="$('#cat_checkboxed div.tree input').attr('checked', false);"
                                        class="green_10">
                                            {$lang.uncheck_all}
                                    </span>
                                </span>
                            </div>
                            
                        </div>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <td class="divider" colspan="3">
                    <div class="inner">{$lang.fb_page_settings}</div>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.fb_use_parent_page}</td>
                <td class="field">
                    {if $sPost.fbb.parent_page == '1'}
                        {assign var='parent_page_yes' value='checked="checked"'}
                    {elseif $sPost.fbb.parent_page == '0'}
                        {assign var='parent_page_no' value='checked="checked"'}
                    {else}
                        {assign var='parent_page_no' value='checked="checked"'}
                    {/if}
                    
                    <label>
                        <input {$parent_page_yes} class="lang_add" type="radio" name="fbb[parent_page]" value="1" />
                            {$lang.yes}
                    </label>
                    <label>
                        <input {$parent_page_no} class="lang_add" type="radio" name="fbb[parent_page]" value="0" />
                            {$lang.no}
                    </label>

                    <span class="field_description">{$lang.fb_use_parent_page_hint}</span>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.page_url}</td>
                <td class="field">
                    {if $allLangs|@count > 1 && $config.multilingual_paths}
                        <div>
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                                {/foreach}
                            </ul>

                            {foreach from=$allLangs item='language' name='langF'}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                                    <span style="padding: 0 5px 0 0;" class="field_description_noicon">
                                        {$smarty.const.RL_URL_HOME}{if $language.Code !== $config.lang}{$language.Code}/{/if}
                                    </span>
                                    <input name="page[path][{$language.Code}]" type="text" value="{$sPost.page.path[$language.Code]}" maxlength="40" />
                                    <span class="field_description_noicon">/{$lang.fb_option_key}</span>
                                    <span class="field_description_noicon path_postfix" style="padding:0">
                                        {if $sPost.page.postfix}x.html{else}y/{/if}
                                    </span>
                                </div>
                            {/foreach}
                        </div>

                        <div class="field_description" style="margin: 10px 0;">{$lang.fb_regenerate_path_desc}</div>
                    {else}
                        <table>
                        <tr>
                            <td>
                                <span style="padding: 0 5px 0 0;" class="field_description_noicon">
                                    {$smarty.const.RL_URL_HOME}{if $smarty.const.RL_LANG_CODE !== $config.lang}{$smarty.const.RL_LANG_CODE}/{/if}
                                </span>
                            </td>
                            <td>
                                <input type="text" name="page[path][{$config.lang}]" value="{$sPost.page.path[$config.lang]}" />
                            </td>
                            <td>
                                <span class="field_description_noicon">/{$lang.fb_option_key}</span>
                                <span class="field_description_noicon" style="padding:0" id="path_postfix">
                                    {if $sPost.page.postfix}.html{else}/{/if}
                                </span>
                            </td>
                            <td>
                                {if $smarty.get.action == 'add'}
                                    <span class="field_description"> - {$lang.fb_regenerate_path_desc}</span>
                                {/if}
                            </td>
                        </tr>
                        </table>
                    {/if}
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.fb_html_postfix}</td>
                <td class="field">
                    {if $sPost.fbb.postfix == '1'}
                        {assign var='postfix_yes' value='checked="checked"'}
                    {elseif $sPost.fbb.postfix == '0'}
                        {assign var='postfix_no' value='checked="checked"'}
                    {else}
                        {assign var='postfix_yes' value='checked="checked"'}
                    {/if}
                    
                    <label>
                        <input {$postfix_yes} class="lang_add" type="radio" name="fbb[postfix]" value="1" />
                            {$lang.yes}
                    </label>
                    <label>
                        <input {$postfix_no} class="lang_add" type="radio" name="fbb[postfix]" value="0" />
                            {$lang.no}
                    </label>
                </td>
            </tr>
            </table>

            <div id="page_settings">
                <div class="column-options">
                    <table class="form">
                    <tr>
                        <td class="name">{$lang.number_of_columns}</td>
                        <td class="field">
                            <select name="fbb[page_columns]">
                                <option value="auto"{if $sPost.fbb.page_columns =='auto' || !$sPost.fbb.page_columns} selected="selected"{/if}>{$lang.fb_auto}</option>
                                {section name='page_cols' start=2 loop=5 step=1}
                                <option value="{$smarty.section.page_cols.index}"{if $sPost.fbb.page_columns == $smarty.section.page_cols.index} selected="selected"{/if}>
                                    {$smarty.section.page_cols.index} {$lang.fb_columns}</option>
                                {/section}
                            </select>
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
                                <li
                                    lang="{$language.Code}"
                                    {if $smarty.foreach.langF.first}class="active"{/if}>
                                        {$language.name}
                                </li>
                                {/foreach}
                            </ul>
                        {/if}
                        
                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                            {/if}

                            <input
                                type="text"
                                name="page[name][{$language.Code}]"
                                value="{$sPost.page.name[$language.Code]}"
                                maxlength="350"
                                class="w350"
                            />

                            {if $allLangs|@count > 1}
                                    <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                                </div>
                            {/if}
                        {/foreach}
                    </td>
                </tr>     

                <tr>
                    <td class="name">
                        <span class="red">*</span>{$lang.title}
                    </td>
                    <td class="field">
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                <li
                                    lang="{$language.Code}"
                                    {if $smarty.foreach.langF.first}class="active"{/if}>
                                        {$language.name}
                                </li>
                                {/foreach}
                            </ul>
                        {/if}
                        
                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                            {/if}

                            <input
                                type="text"
                                name="page[title][{$language.Code}]"
                                value="{$sPost.page.title[$language.Code]}"
                                maxlength="350"
                                class="w350"
                            />

                            {if $allLangs|@count > 1}
                                    <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                                </div>
                            {/if}
                        {/foreach}
                    </td>
                </tr>
                
                <tr>
                    <td class="name">
                        {$lang.h1_heading}
                    </td>
                    <td class="field">
                        <div>
                            {if $allLangs|@count > 1}
                                <ul class="tabs">
                                    {foreach from=$allLangs item='language' name='langF'}
                                    <li 
                                        lang="{$language.Code}" 
                                        {if $smarty.foreach.langF.first}class="active"{/if}>
                                            {$language.name}
                                    </li>
                                    {/foreach}
                                </ul>
                            {/if}
                            
                            {foreach from=$allLangs item='language' name='langF'}
                                {if $allLangs|@count > 1}
                                    <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                                {/if}

                                <input 
                                    type="text" 
                                    name="page[h1][{$language.Code}]" 
                                    value="{$sPost.page.h1[$language.Code]}" 
                                    maxlength="350" 
                                    class="w350"/>

                                {if $allLangs|@count > 1}
                                        <span class="field_description_noicon">
                                            {$lang.h1_heading} (<b>{$language.name}</b>)</span>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td class="name">{$lang.meta_description}</td>
                    <td class="field">
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                <li 
                                    lang="{$language.Code}"
                                    {if $smarty.foreach.langF.first}class="active"{/if}>
                                        {$language.name}</li>
                                {/foreach}
                            </ul>
                        {/if}
                        
                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                            {/if}
                                <textarea name="page[meta_description][{$language.Code}]">{$sPost.page.meta_description[$language.Code]}</textarea>
                            {if $allLangs|@count > 1}
                                </div>
                            {/if}
                        {/foreach}
                    </td>
                </tr>
                
                <tr>
                    <td class="name">{$lang.meta_keywords}</td>
                    <td>
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                    <li
                                        lang="{$language.Code}"
                                        {if $smarty.foreach.langF.first}class="active"{/if}>
                                            {$language.name}
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                        
                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                            {/if}
                            <textarea name="page[meta_keywords][{$language.Code}]">{$sPost.page.meta_keywords[$language.Code]}</textarea>
                            {if $allLangs|@count > 1}</div>{/if}
                        {/foreach}
                    </td>
                </tr>
                </table>
            </div>

            <table class="form">
            <tr>
                <td class="divider" colspan="3">
                    <div class="inner">{$lang.fb_seo_settings}</div>
                </td>
            </tr>
            
            <tr>
                <td></td>
                <td class="field">
                    <span class="field_description">
                        {$lang.fb_seo_defaults_hint}
                    </span>
                </td>
            </tr>

            <tr>
                <td class="name">
                    {$lang.h1_heading}
                </td>
                <td class="field">
                    <div>
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                <li 
                                    lang="{$language.Code}" 
                                    {if $smarty.foreach.langF.first}class="active"{/if}>
                                        {$language.name}
                                </li>
                                {/foreach}
                            </ul>
                        {/if}
                        
                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}
                                <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                            {/if}

                            <input 
                                type="text" 
                                name="defaults[h1][{$language.Code}]" 
                                value="{$sPost.defaults.h1[$language.Code]}" 
                                maxlength="350" 
                                class="w350" />

                            {if $allLangs|@count > 1}
                                    <span class="field_description_noicon">
                                        {$lang.h1_heading} (<b>{$language.name}</b>)</span>
                                </div>
                            {/if}
                        {/foreach}                        
                    </div>
                </td>
            </tr>

            <tr>
                <td class="name">
                    {$lang.title}
                </td>
                <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li
                                lang="{$language.Code}"
                                {if $smarty.foreach.langF.first}class="active"{/if}>
                                    {$language.name}
                            </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}

                        <input
                            type="text"
                            name="defaults[title][{$language.Code}]"
                            value="{$sPost.defaults.title[$language.Code]}"
                            maxlength="350"
                            class="w350"
                        />

                        {if $allLangs|@count > 1}
                                <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>

            <tr>
                <td class="name">
                    {$lang.description}
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
                        {if $allLangs|@count > 1}<div class="ckeditor tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                        
                        {assign var='dCode' value='default_des_'|cat:$language.Code}

                        {fckEditor name='default_des_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                        {if $allLangs|@count > 1}</div>{/if}
                    {/foreach}
                </td>
            </tr>

            <tr>
                <td class="name">
                    {$lang.meta_description}
                </td>
                <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li 
                                lang="{$language.Code}"
                                {if $smarty.foreach.langF.first}class="active"{/if}>
                                    {$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}
                            <textarea name="defaults[meta_description][{$language.Code}]">{$sPost.defaults.meta_description[$language.Code]}</textarea>
                        {if $allLangs|@count > 1}
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>
            
            <tr>
                <td class="name">
                    {$lang.meta_keywords}
                </td>
                <td>
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                                <li
                                    lang="{$language.Code}"
                                    {if $smarty.foreach.langF.first}class="active"{/if}>
                                        {$language.name}
                                </li>
                            {/foreach}
                        </ul>
                    {/if}
                    
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                        {/if}
                        <textarea name="defaults[meta_keywords][{$language.Code}]">{$sPost.defaults.meta_keywords[$language.Code]}</textarea>
                        {if $allLangs|@count > 1}</div>{/if}
                    {/foreach}
                </td>
            </tr>            

            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
                </td>
            </tr>
            </table>

            <script type="text/javascript">
                var fields_names = JSON.parse('{$fields_names|@json_encode|escape:'quotes'}'.replace(/(\r\n|\n)/gi, '<br />'));

                var allLangs = new Array();
                {foreach from=$allLangs item='languages'}
                    allLangs.push('{$languages.Code}');
                {/foreach}

                var langs = JSON.parse('{$languages|@json_encode}'.replace(/(\r\n|\n)/gi, '<br />'));
                var box_name_edited = false;
                var page_name_edited = false;
                
                {literal}
                $(document).ready(function(){
                    $('input[name^="box[name]"]').change(function(){
                        box_name_edited = true;
                    });

                    $('input[name^="page[name]"]').change(function(){
                        page_name_edited = true;
                    });

                    $('#legend_pages').click(function(){
                        fieldset_action('pages');
                    });
                    
                    $('input#show_on_all').click(function(){
                        $('#pages_cont').slideToggle();
                        $('#pages_nav').fadeToggle();
                    });

                    $('input[name="fbb[postfix]"]').change(function(){
                        postfixClickHandler();
                    });
                    postfixClickHandler();

                    $('select[name="fbb[field_key]"]').change(function(){
                        fieldsSelectorHandler();
                    });

                    $('select[name="fbb[style]"]').change(function(){
                        styleClickHandler();
                        singleLineCounterHandler();
                    });
                    styleClickHandler(true);

                    $('select[name="box[side]"]').change(function(){
                        sideClickHandler();
                    });
                    sideClickHandler();

                    $('input[name="fbb[parent_page]"]').change(function(){
                        parentPageClickHandler();
                    });
                    parentPageClickHandler();

                    $('input[name="fbb[show_count]"]').change(function(){
                        singleLineCounterHandler();
                    });
                    $('select[name="fbb[icons_position]').change(function(){
                        singleLineCounterHandler();
                    });
                    singleLineCounterHandler();

                    {/literal}
                    {if $smarty.get.action != 'edit'}
                        fieldsSelectorHandler();
                    {/if}
                    {literal}
                });

                var postfixClickHandler = function(){
                    var enabled = parseInt( $('input[name="fbb[postfix]"]:checked').val() ) ? 1 : 0;

                    $('#path_postfix,.path_postfix').html(enabled != 0 ? '.html' : '/');
                };

                var fieldsSelectorHandler = function() {
                    var value = $('select[name="fbb[field_key]"]').val();

                    if (value && value != 0) {
                        allLangs.forEach(function(lang_code) {
                            var field_name = fields_names[value][lang_code];

                            if (!box_name_edited) {
                                $('input[name="box[name][' + lang_code + ']"]').val(field_name);
                            }

                            if (!page_name_edited) {
                                $('input[name="page[name][' + lang_code + ']"]').val(field_name);
                            }
                        });

                        $('input[name="page[path]"]').val(str2path(value));
                    }
                };

                var sideClickHandler = function() {
                    var side = $('select[name="box[side]"] option:selected').val();
                    var action = 'show';

                    $('select[name="fbb[columns]"] option').filter(function(){
                        action = 'show';
                        
                        if (['left', 'middle_left', 'middle_right'].indexOf(side) >= 0
                            && $(this).val() > 2
                        ) {
                            action = 'hide';

                            if ($('select[name="fbb[columns]"]').val() > 2) {
                                $('select[name="fbb[columns]"]').val('auto');
                            }

                        } else if (['top', 'bottom', 'middle'].indexOf(side) >= 0
                            && $(this).val() == 1) {

                            action = 'hide';

                            if ($('select[name="fbb[columns]"]').val() == 1) {
                                $('select[name="fbb[columns]"]').val('auto');
                            }
                        }

                        $(this)[action]();
                    });

                    return;
                };

                var parentPageClickHandler = function() {
                    var enabled = parseInt( $('input[name="fbb[parent_page]"]:checked').val() ) ? 1 : 0;

                    if (enabled != 0) {
                        $('#page_settings').slideDown();
                    } else {
                        $('#page_settings').slideUp();
                    }
                };

                {/literal}
                lang['fb_picture_settings'] = '{$lang.fb_picture_settings}';
                {literal}

                var styleClickHandler = function(init) {
                    var value = $('select[name="fbb[style]"]').val();
                    var hide_effect = init ? 'hide' : 'slideUp';

                    if (value == 'text_pic' || value == 'icon') {
                        $('#icons_area').slideDown();
                        $('#plate_orientation_area')[hide_effect]();
                    } else if (value == 'responsive') {
                        $('#plate_orientation_area').slideDown();
                        $('#icons_area')[hide_effect]();
                    } else {
                        $('#icons_area')[hide_effect]();
                        $('#plate_orientation_area')[hide_effect]();
                    }
                    
                    $('.column-options')[value == 'icon' ? hide_effect : 'slideDown']();

                    $('#icon_settings_heading').html(lang[value == 'text_pic' ? 'fb_picture_settings' : 'fb_icon_settings']);
                };

                var singleLineCounterHandler = function() {
                    $('#counter_single_line')[
                        $('[name="fbb[show_count]"]:checked').val() === '1'
                        && $('[name="fbb[style]"]').val() == 'text_pic'
                        && ['left', 'right'].indexOf($('[name="fbb[icons_position]"').val()) < 0
                            ? 'show' : 'hide'
                    ]();
                }

                var str2path = function(str) {
                    str = str.replace(/[^a-z0-9]+/ig, '-');
                    str = str.toLowerCase();

                    return str ? str : '';
                };
            {/literal}
            </script>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

{else}

    {literal}
    <script type="text/javascript">
    var addItem = function(){
    {/literal}
        var names = new Array();

        {foreach from=$allLangs item='languages'}
        names['{$languages.Code}'] = $('#ni_{$languages.Code}').val();
        {/foreach}

        xajax_addItem(
            $('#ni_key').val(),
            names,
            $('#ni_status').val(),
            '{$smarty.get.box}',
            $('#ni_default:checked').val()
        );
    {literal}
    }
    </script>
    {/literal}

    {literal}
    <script type="text/javascript">
    var editItem = function(key){
    {/literal}
        var names = new Array();
    
        {foreach from=$allLangs item='languages'}
        names['{$languages.Code}'] = $('#ei_{$languages.Code}').val();
        {/foreach}

        xajax_editItem(key, names, $('#ei_status').val(), '{$smarty.get.box}', $('#ei_default:checked').val());
    {literal}
    }
    </script>
    {/literal}
    <!-- add new item end -->

    {if !$smarty.get.box}
    <script type="text/javascript">
        // blocks sides list
        var block_sides = [
            {foreach from=$l_block_sides item='block_side' name='sides_f' key='sKey'}
                {if $sKey == 'header_banner' || $sKey == 'integrated_banner'}
                    {continue}
                {/if}

                ['{$sKey}', '{$block_side}'],
            {/foreach}
        ];
    </script>
    {/if}

    <!-- field-bound boxes grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    {if $smarty.get.box}
        var itemsGrid;
        var box = '{$smarty.get.box}';
        
        {literal}
        $(document).ready(function(){
            itemsGrid = new gridObj({
                key: 'fbb_data_items',
                id: 'grid',
                ajaxUrl: rlPlugins + 'fieldBoundBoxes/admin/field_bound_boxes.inc.php?q=ext&box='+box,
                defaultSortField: 'name',
                remoteSortable: true,
                title: lang['ext_format_items_manager'],
                fields: [
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [
                    {
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        width: 40
                    },{
                        header: lang['ext_position'],
                        dataIndex: 'Position',
                        width: 70,
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
                        width: 80,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
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
                        renderer: function(val) {
                            var out = "<center>";
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&box={/literal}{$smarty.get.box}{literal}&action=edit_item&item="+val+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_delete']+"\", \"fbbDeleteItem\", \""+Array(val)+"\" )' />";
                            out += "</center>";
                            
                            return out;
                        }
                    }
                ]
            });
            
            itemsGrid.init();
            grid.push(itemsGrid.grid);
        });
        {/literal}
    {else}
        {literal}
        var fieldBoundBoxesGrid;
        
        $(document).ready(function(){
            
            fieldBoundBoxesGrid = new gridObj({
                key: 'field_bound_boxes',
                id: 'grid',
                ajaxUrl: rlPlugins + 'fieldBoundBoxes/admin/field_bound_boxes.inc.php?q=ext',
                defaultSortField: 'name',
                title: lang['ext_blocks_manager'],
                fields: [
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Field_name', mapping: 'Field_name'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Tpl', mapping: 'Tpl'},
                    {name: 'Side', mapping: 'Side'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [{
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        id: 'rlExt_item_bold',
                        width: 40
                    },{
                        header: lang['fb_field'],
                        dataIndex: 'Field_name',
                        id: 'rlExt_item_bold',
                        width: 180,
                        fixed: true,
                    },{
                        header: lang['ext_block_side'],
                        dataIndex: 'Side',
                        width: 140,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: block_sides,
                            displayField: 'value',
                            valueField: 'key',
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus: true
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                    },{
                        header: lang['ext_block_style'],
                        dataIndex: 'Tpl',
                        width: 140,
                        fixed: true,
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
                        width: 120,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['active', lang['ext_active']],
                                ['approval', lang['ext_approval']]
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
                            var manage_link = data == 'years' ? "eval(Ext.MessageBox.alert(\""+lang['ext_notice']+"\", \""+lang['ext_data_format_auto']+"\"))" : '';
                            var manage_href = data == 'years' ? "javascript:void(0)" : rlUrlHome+"index.php?controller="+controller+"&box="+data;
                            var out = "<center>";
                            var splitter = false;
                            
                            out += "<a href="+manage_href+" onclick='"+manage_link+"'><img class='manage' ext:qtip='"+lang['ext_manage']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit&box="+data+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"fbbDeleteBox\", \""+Array(data)+"\", \"section_load\" )' />";
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });
            
            fieldBoundBoxesGrid.init();
            grid.push(fieldBoundBoxesGrid.grid);
            
        });
        {/literal}
    {/if}
    //]]>
    </script>
{/if}

<script>
    {literal}
    function fbbDeleteIcon(item_id) {
        var data = {};

        data.item_id = item_id;
        data.item = 'fbbDeleteIcon';

        fbbDoRequest(data, lang['fb_icon_deleted'], function() {
            $('#gallery').slideUp('normal');
        });
    }

    function fbbReCopyItems(box) {
        var data = {};

        data.box_key = box;
        data.item = 'fbbRecopyBoxItems';

        fbbDoRequest(data, lang['fb_items_recopied'], function() { itemsGrid.reload() });
    }

    function fbbDeleteItem(val) {
        var data = {};

        data.box_key = '{/literal}{$smarty.get.box}{literal}';
        data.item_key = val;
        data.item = 'fbbDeleteItem';

        fbbDoRequest(data, '{/literal}{$lang.item_deleted}{literal}', function() { itemsGrid.reload() });

        // Display related item in search results
        $('[data-key=' + val + ']').closest('.box-item').css('display', 'flex');
    }

    function fbbDeleteBox(box) {
        var data = {};
        data.box_key = box;
        data.item = 'fbbDeleteBox';

        fbbDoRequest(data, '{/literal}{$lang.block_deleted}{literal}', function() { fieldBoundBoxesGrid.reload() });
    }

    function fbbDoRequest(data, successMessage, callback) {
        $.getJSON(rlConfig['ajax_url'], data, function(response) {
            if (response.status == 'ok') {
                printMessage('notice', successMessage);
                callback();
            } else {
                return false;
            }
        });
    }
    {/literal}
</script>
<!-- field bound boxes tpl end -->
