<!-- add/edit new field -->

{assign var='sPost' value=$smarty.post}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<form onsubmit="return submitHandler();" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;field={$smarty.get.field}{/if}" method="post">
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
        <td class="name"><span class="red">*</span>{$lang.name}</td>
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
        <td class="name">{$lang.required_field}</td>
        <td>
            {if $sPost.required == '1'}
                {assign var='required_yes' value='checked="checked"'}
            {elseif $sPost.required == '0'}
                {assign var='required_no' value='checked="checked"'}
            {else}
                {assign var='required_no' value='checked="checked"'}
            {/if}
            <label><input {$required_yes} type="radio" name="required" value="1" /> {$lang.yes}</label>
            <label><input {$required_no} type="radio" name="required" value="0" /> {$lang.no}</label>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.google_map}</td>
        <td class="field">
            {if $sPost.map == '1'}
                {assign var='map_yes' value='checked="checked"'}
            {elseif $sPost.map == '0'}
                {assign var='map_no' value='checked="checked"'}
            {else}
                {assign var='map_no' value='checked="checked"'}
            {/if}

            <table>
            <tr>
                <td>
                    <label><input {$map_yes} type="radio" name="map" value="1" /> {$lang.yes}</label>
                    <label><input {$map_no} type="radio" name="map" value="0" /> {$lang.no}</label>
                </td>
                <td>
                    <span class="field_description">{$lang.use_for_displaing_map}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    {if $config.membership_module}
    <tr>
        <td class="name">{$lang.contact_field}</td>
        <td class="field">
            {if $sPost.contact == '1'}
                {assign var='contact_yes' value='checked="checked"'}
            {elseif $sPost.contact == '0'}
                {assign var='contact_no' value='checked="checked"'}
            {else}
                {assign var='contact_no' value='checked="checked"'}
            {/if}

            <table>
            <tr>
                <td>
                    <label><input {$contact_yes} type="radio" name="contact" value="1" /> {$lang.yes}</label>
                    <label><input {$contact_no} type="radio" name="contact" value="0" /> {$lang.no}</label>
                </td>
                <td>
                    <span class="field_description">{$lang.contact_field_for_membership}</span>
                </td>
            </tr>
            </table>
        </td>
    </tr>
    {/if}
    <tr>
        <td class="name">{$lang.show_on}</td>
        <td class="field">
            {if $smarty.get.controller == 'account_fields'}
                {assign var="add_pk" value="pages+name+registration"}
                {assign var="details_pk" value="view_account"}
            {else}
                {assign var="add_pk" value="pages+name+add_listing"}
                {assign var="details_pk" value="pages+name+view_details"}
            {/if}
            <label><input {if isset($sPost.add_page)}checked="checked"{else}{if empty($sPost)}checked="checked"{/if}{/if} type="checkbox" name="add_page" /> {$lang.add_edit_page_tpl|replace:"[page]":$lang.$add_pk}</label>
            <label {if $sPost.type == 'accept'}style="display:none"{/if}><input {if isset($sPost.details_page) && $sPost.type != 'accept'}checked="checked"{else}{if empty($sPost)}checked="checked"{/if}{/if} type="checkbox" name="details_page" /> {$lang.add_edit_page_tpl|replace:"[page]":$lang.$details_pk}</label>
        </td>
    </tr>

    {rlHook name='apTplFieldsForm'}

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
        <td class="name"><span class="red">*</span>{$lang.field_type}</td>
        <td class="field">
            <select {if $smarty.get.action == 'edit'}disabled="disabled"{/if} name="type" class="{if $smarty.get.action == 'edit'}disabled{/if}">
                <option value="">{$lang.select}</option>
                {foreach from=$l_types item='lType' key='key'}
                    <option {if $sPost.type == $key}selected="selected"{/if} value="{$key}">{$lType}</option>
                {/foreach}
            </select>
            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="type" value="{$sPost.type}" />
            {/if}

            {if $smarty.get.action == 'edit' && $sys_fields && $field_info.Key|in_array:$sys_fields}
                <span class="field_description">{$lang.system_field_notice}</span>
            {/if}
        </td>
    </tr>
    </table>

    <!-- additional options -->
    <div id="additional_options">

    <script type="text/javascript">
    var langs_list = Array(
    {foreach from=$allLangs item='languages' name='lF'}
    '{$languages.Code}|{$languages.name}'{if !$smarty.foreach.lF.last},{/if}
    {/foreach}
    );
    </script>

    <!-- text field -->
    {assign var='textDefault' value=$sPost.text.default}
    <div id="field_text" class="hide">
        <table class="form">
        <tr>
            <td class="name">{$lang.default_value}</td>
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
                    <input type="text" name="text[default][{$language.Code}]" value="{$textDefault[$language.Code]}" maxlength="100" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>

        {assign var='text_cond' value=$sPost.text}
        <tr>
            <td class="name">{$lang.check_condition}</td>
            <td class="field">
                <select name="text[condition]">
                    <option value="">{$lang.select}</option>
                    {foreach from=$l_cond item='condition' key='cKey'}
                        <option {if $text_cond.condition == $cKey}selected="selected"{/if} value="{$cKey}">{$condition}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        </table>
        {if $allLangs|@count > 1 && $smarty.get.field != 'First_name' && $smarty.get.field != 'Last_name'}
        <div id="text_multilingual" {if $text_cond.condition}class="hide"{/if}>
            <table class="form">
            <tr>
                <td class="name">{$lang.multilingual}</td>
                <td class="field">
                    {if $sPost.text.multilingual == '1'}
                        {assign var='text_multilingual_yes' value='checked="checked"'}
                    {elseif $sPost.text.multilingual == '0'}
                        {assign var='text_multilingual_no' value='checked="checked"'}
                    {else}
                        {assign var='text_multilingual_no' value='checked="checked"'}
                    {/if}

                    <label><input {$text_multilingual_yes} type="radio" name="text[multilingual]" value="1" /> {$lang.yes}</label>
                    <label><input {$text_multilingual_no} type="radio" name="text[multilingual]" value="0" /> {$lang.no}</label>
                </td>
            </tr>
            </table>
        </div>
        {/if}
        <table class="form">
        <tr>
            <td class="name">{$lang.maxlength}</td>
            <td class="field">
                <input class="numeric" name="text[maxlength]" type="text" style="width: 50px; text-align: center;" value="{$sPost.text.maxlength}" maxlength="3" /> <span class="field_description">{$lang.default_text_value_des}</span>
            </td>
        </tr>

        {rlHook name='apTplFieldsFormText'}

        </table>

        <script type="text/javascript">
        {literal}

        $(document).ready(function(){
            $('select[name="text[condition]"]').change(function(){
                var val = $(this).val();

                if ( val )
                {
                    $('#text_multilingual').slideUp();
                    $('input[name="text[multilingual]"][value=0]').prop('checked', true);
                }
                else
                {
                    $('#text_multilingual').slideDown();
                }
            });
        });

        {/literal}
        </script>
    </div>
    <!-- text field end -->

    <!-- textarea field -->
    {assign var='textarea' value=$sPost.textarea}
    <div id="field_textarea" class="hide">
        <table class="form">
        <tr>
            <td class="name">{$lang.maxlength}</td>
            <td class="field">
                <input class="numeric" name="textarea[maxlength]" type="text" style="width: 50px; text-align: center;" value="{$textarea.maxlength}" maxlength="4" /> <span class="field_description">{$lang.default_textarea_value_des}</span>
            </td>
        </tr>
        {if $allLangs|@count > 1}
        <tr>
            <td class="name">{$lang.multilingual}</td>
            <td class="field">
                {if $sPost.textarea.multilingual == '1'}
                    {assign var='multilingual_yes' value='checked="checked"'}
                {elseif $sPost.textarea.multilingual == '0'}
                    {assign var='multilingual_no' value='checked="checked"'}
                {else}
                    {assign var='multilingual_no' value='checked="checked"'}
                {/if}

                <label><input {$multilingual_yes} type="radio" name="textarea[multilingual]" value="1" /> {$lang.yes}</label>
                <label><input {$multilingual_no} type="radio" name="textarea[multilingual]" value="0" /> {$lang.no}</label>
            </td>
        </tr>
        {/if}
        <tr>
            <td class="name">{$lang.html_editor}</td>
            <td class="field">
                {if $sPost.textarea.html == '1'}
                    {assign var='html_yes' value='checked="checked"'}
                {elseif $sPost.textarea.html == '0'}
                    {assign var='html_no' value='checked="checked"'}
                {else}
                    {assign var='html_no' value='checked="checked"'}
                {/if}

                <label><input {$html_yes} type="radio" name="textarea[html]" value="1" /> {$lang.yes}</label>
                <label><input {$html_no} type="radio" name="textarea[html]" value="0" /> {$lang.no}</label>
            </td>
        </tr>

        {rlHook name='apTplFieldsFormTextarea'}

        </table>
    </div>
    <!-- textarea field end -->

    <!-- number field -->
    {assign var='number' value=$sPost.number}
    <div id="field_number" class="hide">
        <table class="form">
        <tr>
            <td class="name">{$lang.maxlength}</td>
            <td class="field">
                <input class="numeric" name="number[max_length]" type="text" style="width: 60px; text-align: center;" value="{$number.max_length}" maxlength="8" />
                <span class="field_description">{$lang.number_field_length_hint}</span>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.number_format}</td>
            <td class="field">
                {if $sPost.number.format == '1'}
                    {assign var='number_format_yes' value='checked="checked"'}
                {elseif $sPost.number.format == '0'}
                    {assign var='number_format_no' value='checked="checked"'}
                {else}
                    {assign var='number_format_no' value='checked="checked"'}
                {/if}

                <label><input {$number_format_yes} name="number[format]" type="radio" value="1" /> {$lang.yes}</label>
                <label><input {$number_format_no}  name="number[format]" type="radio" value="0" /> {$lang.no}</label>

                <span class="field_description">{$lang.number_format_hint}</span>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.thousands_separator}</td>
            <td class="field">
                <input name="number[thousands_sep]" type="text" value="{$number.thousands_sep}" maxlength="1" style="width: 15px; text-align: center;"/>
                <span class="field_description">{$lang.thousands_separator_hint}</span>
            </td>
        </tr>

        <script type="text/javascript">{literal}
        $(document).ready(function(){
            flynax.numberFormatHandler($('#field_number'));
        });
        {/literal}</script>

        {rlHook name='apTplFieldsNumber'}

        </table>
    </div>
    <!-- number field end -->

    <!-- phone number field -->
    {assign var='phone' value=$sPost.phone}
    <div id="field_phone" class="hide">
        <table class="form">
        {rlHook name='apTplFieldsPhone'}

        <tr>
            <td class="name">{$lang.phone_hide_number}</td>
            <td class="field">
                {if $phone.hide_number == '1'}
                    {assign var='hide_number_yes' value='checked="checked"'}
                {elseif $phone.hide_number == '0'}
                    {assign var='hide_number_no' value='checked="checked"'}
                {else}
                    {assign var='hide_number_no' value='checked="checked"'}
                {/if}

                <label>
                    <input {$hide_number_yes} name="phone[hide_number]" type="radio" value="1" /> {$lang.yes}
                </label>
                <label>
                    <input {$hide_number_no}  name="phone[hide_number]" type="radio" value="0" /> {$lang.no}
                </label>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.bind_data_format}</td>
            <td class="field">
                <select id="dd_phone_block" name="phone[condition]" class="data_format">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$data_formats item='format'}
                    <option value="{$format.Key}"{if $format.Key == $phone.condition} selected="selected"{/if}>{$format.name|strip_tags}</option>
                    {/foreach}
                </select>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.field_format}</td>
            <td class="field_tall">
                <ul class="clear_list">
                    <li><label><input type="checkbox" name="phone[code]" {if $phone.code}checked="checked"{/if} value="1" /> {$lang.phone_code}</label></li>
                    <li id="phone_block" {if $phone.condition}class="hide"{/if}><input style="width: 20px;text-align: center;" type="text" name="phone[area_length]" value="{if $phone.area_length}{$phone.area_length}{else}3{/if}" maxlength="1" /> <label>{$lang.phone_area_length}</label></li>
                    <li><input style="width: 20px;text-align: center;" type="text" name="phone[phone_length]" value="{if $phone.phone_length}{$phone.phone_length}{else}7{/if}" maxlength="1" /> <label>{$lang.phone_number_length}</label></li>
                    <li><label><input type="checkbox" name="phone[ext]" {if $phone.ext}checked="checked"{/if} value="1" /> {$lang.phone_ext}</label></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.field_preview}</td>
            <td class="field">
                <div style="padding: 0 0 10px 0;">
                    <span class="phone_code_prev hide">+ <input disabled="disabled" type="text" maxlength="4" style="width: 30px;text-align: center;" /> -</span>
                    <input disabled="disabled" id="phone_area_input" type="text" maxlength="5" style="width: 40px;text-align: center;" />
                    - <input disabled="disabled" id="phone_number_input" type="text" maxlength="9" style="width: 80px;" /></span>
                    <span class="phone_ext hide">/ <input disabled="disabled" type="text" maxlength="4" style="width: 35px;" /></span>
                </div>
                <div>
                    <span class="phone_code_prev hide">+ xxx</span>
                    <span id="phone_area_preview">(xxx)</span>
                    <span id="phone_number_preview">123-4567</span>
                    <span class="phone_ext hide">{$lang.phone_ext_out}22</span>
                </div>
            </td>
        </tr>

        </table>

        <script type="text/javascript">flynax.phoneFieldControls();</script>
    </div>
    <!-- phone number field -->

    <!-- date field -->
    {assign var='date' value=$sPost.date}
    <div id="field_date" class="hide">
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.mode}</td>
            <td class="field">
                <label><input {if $date.mode == 'single'}checked="checked"{/if} type="radio" name="date[mode]" value="single" /> {$lang.single_date}</label>
                <label><input {if $date.mode == 'multi'}checked="checked"{/if} type="radio" name="date[mode]" value="multi" /> {$lang.time_period}</label>
            </td>
        </tr>

        {rlHook name='apTplFieldsDate'}

        </table>
    </div>
    <!-- date field end -->

    <!-- boolean field -->
    {if $sPost.bool.default == '1'}
        {assign var='bool_default_yes' value='checked="checked"'}
    {elseif $sPost.required == '0'}
        {assign var='bool_default_no' value='checked="checked"'}
    {else}
        {assign var='bool_default_no' value='checked="checked"'}
    {/if}
    <div id="field_bool" class="hide">
        <table class="form">
        <tr>
            <td class="name">{$lang.default_value}</td>
            <td class="field">
                <label><input {$bool_default_yes} type="radio" name="bool[default]" value="1" /> {$lang.yes}</label>
                <label><input {$bool_default_no} type="radio" name="bool[default]" value="0" /> {$lang.no}</label>
            </td>
        </tr>

        {rlHook name='apTplFieldsBool'}

        </table>
    </div>
    <!-- boolean field end -->

    <!-- mixed field -->
    <div id="field_mixed" class="hide">
        <script type="text/javascript">
        var mixed_step = 1;
        </script>
        <table class="form">

        {rlHook name='apTplFieldsMixed'}

        <tr>
            <td class="name">{$lang.bind_data_format}</td>
            <td class="field">
                <select id="dd_mixed_block" name="mixed_data_format" class="data_format">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$data_formats item='format'}
                    <option value="{$format.Key}"{if $format.Key == $sPost.mixed_data_format} selected="selected"{/if}>{$format.name|strip_tags}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.number_format}</td>
            <td class="field">
                {if $sPost.format == '1'}
                    {assign var='mixed_number_format_yes' value='checked="checked"'}
                {elseif $sPost.format == '0'}
                    {assign var='mixed_number_format_no' value='checked="checked"'}
                {else}
                    {assign var='mixed_number_format_no' value='checked="checked"'}
                {/if}

                <label><input {$mixed_number_format_yes} name="mixed[format]" type="radio" value="1" /> {$lang.yes}</label>
                <label><input {$mixed_number_format_no} name="mixed[format]" type="radio" value="0" /> {$lang.no}</label>

                <span class="field_description">{$lang.number_format_hint}</span>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.thousands_separator}</td>
            <td class="field">
                <input name="mixed[thousands_sep]" type="text" value="{$sPost.thousands_sep}" maxlength="1" style="width: 15px; text-align: center;"/>
                <span class="field_description">{$lang.thousands_separator_hint}</span>
            </td>
        </tr>

        <script type="text/javascript">{literal}
        $(document).ready(function(){
            flynax.numberFormatHandler($('#field_mixed'));
        });
        {/literal}</script>

        </table>

        <div id="mixed_block" {if $sPost.mixed_data_format}class="hide"{/if}>
        <table class="form" style="margin: 10px 0 0;">
        <tr>
            <td class="name">{$lang.field_items}</td>
            <td class="field">
                <div class="options-section" id="mixed">
                {if $sPost.mixed}
                    {foreach from=$sPost.mixed item='mixedItem' key='mixedKey'}
                    {if $mixedKey != 'default' && $mixedKey != 'format' && $mixedKey != 'thousands_sep'}
                        <div id="mixed_{$mixedKey}" class="option">
                            <div class="controls">
                                <label><input {if $sPost.mixed.default == $mixedKey}checked="checked"{/if} id="mixed_def_{$mixedKey}" type="radio" name="mixed[default]" value="{$mixedKey}"> Default</label>
                                <a href="javascript:void(0)" onclick="$('#mixed_{$mixedKey}').remove();" class="delete_item">Remove</a>
                            </div>

                            <div class="data">
                                <ul class="tabs">
                                    {foreach from=$allLangs item='languages' name='lang_foreach'}
                                        {assign var='lCode' value=$languages.Code}
                                        <li {if $smarty.foreach.lang_foreach.first}class="active"{/if} lang="{$lCode}">{$languages.name}</li>
                                    {/foreach}
                                </ul>
                                {foreach from=$allLangs item='languages' name='lang_foreach'}
                                    {assign var='lCode' value=$languages.Code}
                                    <div class="tab_area {if !$smarty.foreach.lang_foreach.first}hide{/if} {$lCode}">
                                        <input type="text" class="margin float" name="mixed[{$mixedKey}][{$languages.Code}]" value="{$mixedItem.$lCode}">
                                        <span class="field_description_noicon">{$lang.item_value} (<b>{$languages.name}</b>)</span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                        <script type="text/javascript">
                            if (mixed_step <= {$mixedKey})
                                mixed_step = {$mixedKey} + 1;
                        </script>
                    {/if}
                    {/foreach}
                {/if}
                </div>

                <div class="add_item"><a href="javascript:void(0)" onclick="field_build('mixed', langs_list );">{$lang.add_field_item}</a></div>
            </td>
        </tr>
        </table>
        </div>
    </div>
    <!-- mixed field end -->

    <!-- dropdown list field -->
    <div id="field_select" class="hide">
        <script type="text/javascript">
        var select_step = 1;
        </script>
        <table class="form">

        {rlHook name='apTplFieldsDropdown'}

        <tr>
            <td class="name">{$lang.bind_data_format}</td>
            <td class="field">
                <select id="dd_select_block" name="data_format" class="data_format">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$data_formats item='format'}
                    <option value="{$format.Key}"{if $format.Key == $sPost.data_format} selected="selected"{/if}>{$format.name|strip_tags}</option>
                    {/foreach}
                </select>

                <span class="field_description" id="field_condition_hint">
                    {assign var='replace' value='<a target="_blank" class="static" href="javascript:void(0)">$1</a>'}
                    {$lang.field_data_formats_hint|regex_replace:'/\[(.*)\]/':$replace}
                </span>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.field_autocomplete_option}</td>
            <td class="field">
                {if $sPost.autocomplete == '1' && $sPost.data_format !== 'years'}
                    {assign var='autocomplete_yes' value='checked="checked"'}
                {elseif !$sPost.autocomplete  || $sPost.autocomplete == '0'}
                    {assign var='autocomplete_no' value='checked="checked"'}
                {/if}

                <label>
                    <input type="radio"
                           name="autocomplete"
                           value="1"
                           {$autocomplete_yes}
                           {if $sPost.data_format === 'years'}disabled{/if} /> {$lang.yes}
                </label>
                <label>
                    <input type="radio"
                           name="autocomplete"
                           value="0"
                           {$autocomplete_no}
                           {if $sPost.data_format === 'years'}disabled{/if} /> {$lang.no}
                </label>

                <span class="field_description" id="field_autocomplete_hint">{$lang.autocomplete_not_allowed}</span>
            </td>
        </tr>
        </table>

        <script type="text/javascript">
        var field_condition_href = '{$rlBase}index.php?controller=data_formats&mode=manage&format=[key]';
        {literal}

        $('#dd_select_block').change(function(){
            fieldConditionHandler();
        });

        $(document).ready(function(){
            fieldConditionHandler();
        });

        function fieldConditionHandler() {
            var data_format = $('#dd_select_block').val();
            data_format = data_format && data_format != '0' ? data_format : '';

            if (data_format) {
                $('#field_condition_hint a').attr('href', field_condition_href.replace('[key]', data_format));
                $('#field_condition_hint').fadeIn();
            } else {
                $('#field_condition_hint').fadeOut('fast');
            }

            $('#field_autocomplete_hint')[data_format === 'years' ? 'show' : 'hide']();
            $('[name="autocomplete"]').prop('disabled', data_format === 'years');

            if (data_format === 'years') {
                $('[name="autocomplete"][value="0"]').prop('checked', true);
            }
        }
        {/literal}</script>

        <div id="select_block" {if $sPost.data_format}class="hide"{/if}>
        <table class="form" style="margin: 10px 0 0;">
        <tr>
            <td class="name">{$lang.field_items}</td>
            <td class="field">
                <div class="options-section" id="select">
                {if $sPost.select}
                    {foreach from=$sPost.select item='selectItem' key='selectKey'}
                    {if $selectKey != 'default'}
                        <div id="select_{$selectKey}" class="option">
                            <div class="controls">
                                <label><input {if $sPost.select.default == $selectKey}checked="checked"{/if} id="select_def_{$selectKey}" type="radio" name="select[default]" value="{$selectKey}"> Default</label>
                                <a href="javascript:void(0)" onclick="$('#select_{$selectKey}').remove();" class="delete_item">Remove</a>
                            </div>

                            <div class="data">
                                <ul class="tabs">
                                    {foreach from=$allLangs item='languages' name='lang_foreach'}
                                        {assign var='lCode' value=$languages.Code}
                                        <li {if $smarty.foreach.lang_foreach.first}class="active"{/if} lang="{$lCode}">{$languages.name}</li>
                                    {/foreach}
                                </ul>
                                {foreach from=$allLangs item='languages' name='lang_foreach'}
                                    {assign var='lCode' value=$languages.Code}
                                    <div class="tab_area {if !$smarty.foreach.lang_foreach.first}hide{/if} {$lCode}">
                                        <input type="text" class="margin float" name="select[{$selectKey}][{$languages.Code}]" value="{$selectItem.$lCode}">
                                        <span class="field_description_noicon">{$lang.item_value} (<b>{$languages.name}</b>)</span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                        <script type="text/javascript">
                            if (select_step <= {$selectKey})
                                select_step = {$selectKey} + 1;
                        </script>
                    {/if}
                    {/foreach}
                {/if}
                </div>

                <div class="add_item"><a href="javascript:void(0)" onclick="field_build('select', langs_list );">{$lang.add_field_item}</a></div>
            </td>
        </tr>
        </table>
        </div>
    </div>
    <!-- dropdown list field end -->

    <!-- radio set field -->
    <div id="field_radio" class="hide">
        <script type="text/javascript">
        var radio_step = 1;
        </script>
        <table class="form">

        {rlHook name='apTplFieldsRadio'}

        <tr>
            <td class="name">{$lang.bind_data_format}</td>
            <td class="field">
                <select id="dd_radio_block" name="data_format" class="data_format margin">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$data_formats item='format'}
                    <option value="{$format.Key}"{if $format.Key == $sPost.data_format} selected="selected"{/if}>{$format.name|strip_tags}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        </table>

        <div id="radio_block" {if $sPost.data_format}class="hide"{/if}>
        <table class="form" style="margin: 10px 0 0;">
        <tr>
            <td class="name">{$lang.field_items}</td>
            <td class="field">
                <div class="options-section" id="radio">
                {if $sPost.radio}
                    {foreach from=$sPost.radio item='radioItem' key='radioKey'}
                    {if $radioKey != 'default'}
                        <div id="radio_{$radioKey}" class="option">
                            <div class="controls">
                                <label><input {if $sPost.radio.default == $radioKey}checked="checked"{/if} id="radio_def_{$radioKey}" type="radio" name="radio[default]" value="{$radioKey}"> Default</label>
                                <a href="javascript:void(0)" onclick="$('#radio_{$radioKey}').remove();" class="delete_item">Remove</a>
                            </div>

                            <div class="data">
                                <ul class="tabs">
                                    {foreach from=$allLangs item='languages' name='lang_foreach'}
                                        {assign var='lCode' value=$languages.Code}
                                        <li {if $smarty.foreach.lang_foreach.first}class="active"{/if} lang="{$lCode}">{$languages.name}</li>
                                    {/foreach}
                                </ul>
                                {foreach from=$allLangs item='languages' name='lang_foreach'}
                                    {assign var='lCode' value=$languages.Code}
                                    <div class="tab_area {if !$smarty.foreach.lang_foreach.first}hide{/if} {$lCode}">
                                        <input type="text" class="margin float" name="radio[{$radioKey}][{$languages.Code}]" value="{$radioItem.$lCode}">
                                        <span class="field_description_noicon">{$lang.item_value} (<b>{$languages.name}</b>)</span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                        <script type="text/javascript">
                            if (radio_step <= {$radioKey})
                                radio_step = {$radioKey} + 1;
                        </script>
                    {/if}
                    {/foreach}
                {/if}
                </div>

                <div class="add_item"><a href="javascript:void(0)" onclick="field_build('radio', langs_list );">{$lang.add_field_item}</a></div>
            </td>
        </tr>
        </table>
        </div>
    </div>
    <!-- radio set field end -->

    <!-- checkbox set field -->
    <div id="field_checkbox" class="hide">
        <script type="text/javascript">
        var checkbox_step = 1;
        </script>
        <table class="form">

        {rlHook name='apTplFieldsCheckbox'}

        <tr>
            <td class="name">{$lang.number_of_columns}</td>
            <td>
                <select name="column_number" style="width:40px">
                    {section name="column_numbers" start=1 loop=7 step=1}
                        {assign var="column_number" value=$smarty.section.column_numbers.index}

                        {if $column_number != 5}
                            <option value="{$column_number}" {if ($sPost.column_number && $sPost.column_number == $column_number) || (!$sPost.column_number && $column_number == 3)}selected="selected"{/if}>{$column_number}</option>
                        {/if}
                    {/section}
                </select>
            </td>
        </tr>

        {if $cInfo.Controller == 'account_fields'}
            <input type="hidden" name="{$checkbox_field}" value="0" />
        {else}
        <tr>
            <td class="name">{$lang.show_all_options}</td>
            <td>
                {assign var='checkbox_field' value='show_tils'}

                {if $sPost.$checkbox_field == '1'}
                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                {elseif $sPost.$checkbox_field == '0'}
                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                {else}
                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                {/if}

                <input {$show_tils_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                <input {$show_tils_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>

                <span class="field_description">{$lang.show_all_options_hint}</span>
            </td>
        </tr>
        {/if}

        <tr>
            <td class="name">{$lang.bind_data_format}</td>
            <td>
                <select id="dd_checkbox_block" name="data_format" class="data_format">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$data_formats item='format'}
                    <option value="{$format.Key}"{if $format.Key == $sPost.data_format} selected="selected"{/if}>{$format.name|strip_tags}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        </table>

        <div id="checkbox_block" {if $sPost.data_format}class="hide"{/if}>
        <table class="form" style="margin: 10px 0 0;">
        <tr>
            <td class="name">{$lang.field_items}</td>
            <td class="field">
                <div class="options-section" id="checkbox">
                {if $sPost.checkbox}
                    {foreach from=$sPost.checkbox item='checkboxItem' key='checkboxKey'}
                    {assign var='checkbox' value=$sPost.checkbox}
                    {assign var='checkboxIter' value=$checkbox.$checkboxKey}
                    {if $checkboxKey != 'default'}
                        <div id="checkbox_{$checkboxKey}" class="option">
                            <div class="controls">
                                <label><input {if $checkboxIter.default == $checkboxKey}checked="checked"{/if} id="checkbox_def_{$checkboxKey}" type="checkbox" name="checkbox[default][]" value="{$checkboxKey}"> Default</label>
                                <a href="javascript:void(0)" onclick="$('#checkbox_{$checkboxKey}').remove();" class="delete_item">Remove</a>
                            </div>

                            <div class="data">
                                <ul class="tabs">
                                    {foreach from=$allLangs item='languages' name='lang_foreach'}
                                        {assign var='lCode' value=$languages.Code}
                                        <li {if $smarty.foreach.lang_foreach.first}class="active"{/if} lang="{$lCode}">{$languages.name}</li>
                                    {/foreach}
                                </ul>
                                {foreach from=$allLangs item='languages' name='lang_foreach'}
                                    {assign var='lCode' value=$languages.Code}
                                    <div class="tab_area {if !$smarty.foreach.lang_foreach.first}hide{/if} {$lCode}">
                                        <input type="text" class="margin float" name="checkbox[{$checkboxKey}][{$languages.Code}]" value="{$checkboxItem.$lCode}">
                                        <span class="field_description_noicon">{$lang.item_value} (<b>{$languages.name}</b>)</span>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                        <script type="text/javascript">
                            if (checkbox_step <= {$checkboxKey})
                                checkbox_step = {$checkboxKey} + 1;
                        </script>
                    {/if}
                    {/foreach}
                {/if}
                </div>

                <div class="add_item"><a href="javascript:void(0)" onclick="field_build('checkbox', langs_list );">{$lang.add_field_item}</a></div>
            </td>
        </tr>
        </table>
        </div>
    </div>
    <!-- checkbox set field end -->

    <!-- image field -->
    {assign var='image' value=$sPost.image}
    <div id="field_image" class="hide">
        <table class="form">
        <tr>
            <td class="name">{$lang.resize_type}</td>
            <td class="field">
                <select onchange="resize_action($(this).val());" name="image[resize_type]">
                    <option value="">{$lang.select}</option>
                    {foreach from=$l_resize item='resize' key='resKey'}
                        <option value="{$resKey}" {if $resKey == $sPost.image.resize_type}selected="selected"{/if}>{$resize}</option>
                    {/foreach}
                </select>
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.resolution}</td>
            <td class="field">
                <table>
                <tr>
                    <td>{$lang.width}:</td>
                    <td>
                        <input readonly="readonly" id="resW" class="margin numeric disabled" name="image[width]" type="text" style="width: 40px; text-align: center;" value="{$sPost.image.width}" maxlength="4" />
                    </td>
                </tr>
                <tr>
                    <td>{$lang.height}:</td>
                    <td>
                        <input readonly="readonly" id="resH" class="margin numeric disabled" name="image[height]" type="text" style="width: 40px; text-align: center;" value="{$sPost.image.height}" maxlength="4" />
                    </td>
                </tr>
                </table>
            </td>
        </tr>

        {rlHook name='apTplFieldsImage'}

        </table>
    </div>
    <!-- image field end -->

    <!-- file storage field -->
    {assign var='image' value=$sPost.image}
    <div id="field_file" class="hide">
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.file_type}</td>
            <td class="field">
                <select name="file[type]">
                    <option value="">{$lang.select}</option>
                    {foreach from=$l_file_types item='fTypes' key='ftKey'}
                        <option value="{$ftKey}" {if $ftKey == $sPost.file.type}selected="selected"{/if}>{$fTypes.name} ({$fTypes.ext})</option>
                    {/foreach}
                </select>
            </td>
        </tr>

        {rlHook name='apTplFieldsFile'}

        </table>
    </div>
    <!-- file storage field end -->

    <!-- agreement field -->
    <div id="field_accept" class="hide">
        <table class="form">

        {rlHook name='apTplFieldsAgreement'}

        <tr>
            <td class="name">
                <div>
                    <span class="red">*</span>{$lang.agreement_page}
                </div>
            </td>
            <td class="field">
                <select name="accept_page">
                    <option value="">{$lang.select}</option>

                    {foreach from=$agreement_pages item='page_item'}
                        {assign var='lang_page_key' value='pages+name+'|cat:$page_item.Key}

                        {if $lang.$lang_page_key != ''}
                            <option value="{$page_item.Key}"
                                {if $page_item.Key == $sPost.accept_page}selected="selected"{/if}>
                                {$lang[$lang_page_key]}
                            </option>
                        {/if}
                    {/foreach}
                </select>

                {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=pages&action=add">$1</a>'}
                <span class="field_description">{$lang.agreement_page_notice|regex_replace:'/\[(.*)\]/':$replace}</span>
            </td>
        </tr>

        {if $cInfo.Controller == 'account_fields'}
            <tr>
                <td class="name">{$lang.agreement_first_step}</td>
                <td class="field">
                    {if $sPost.first_step == '1' || $sPost.first_step == ''}
                        {assign var='first_step_yes' value='checked="checked"'}
                    {elseif $sPost.first_step == '0'}
                        {assign var='first_step_no' value='checked="checked"'}
                    {/if}

                    <div style="width: 150px; display: inline-block;">
                        <label><input {$first_step_yes} type="radio" name="first_step" value="1" /> {$lang.yes}</label>
                        <label><input {$first_step_no} type="radio" name="first_step" value="0" /> {$lang.no}</label>
                    </div>

                    <span class="field_description">{$lang.agreement_first_step_hint}</span>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.enable_for}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('accounts_tab_area');">{$lang.account_type}</legend>
                        <div id="accounts_tab_area" style="padding: 0 10px 10px 10px;">
                            <table>
                            <tr>
                                <td>
                                    <table>
                                    <tr>
                                    {foreach from=$account_types item='a_type' name='ac_type'}
                                        {if $a_type.Key != 'visitor'}
                                            <td>
                                                <div style="margin: 0 20px 0 0;">
                                                    <label>
                                                        <input {if $sPost.atypes && $a_type.Key|in_array:$sPost.atypes}checked="checked"{/if}
                                                               style="margin-bottom: 0px;"
                                                               type="checkbox"
                                                               value="{$a_type.Key}"
                                                               name="atypes[]"
                                                        />
                                                        {$a_type.name}
                                                    </label>
                                                </div>
                                            </td>

                                            {if $smarty.foreach.ac_type.iteration%3 == 0 && !$smarty.foreach.ac_type.last}
                                                </tr>
                                                <tr>
                                            {/if}
                                        {/if}
                                    {/foreach}
                                    </tr>
                                    </table>
                                </td>
                                <td>
                                    <span class="field_description">{$lang.agreement_atypes_hint}</span>
                                </td>
                            </tr>
                            </table>

                            <div class="grey_area" style="margin: 8px 0 0;">
                                <span onclick="$('#accounts_tab_area input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                                <span class="divider"> | </span>
                                <span onclick="$('#accounts_tab_area input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                            </div>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <script type="text/javascript">{literal}
            $('[name=first_step]').change(function(){
                fieldAgreementHandler();
            });

            $(function(){
                fieldAgreementHandler();
            });

            function fieldAgreementHandler() {
                var $aTypesArea = $('#accounts_tab_area');

                if ($('[name=first_step]:checked').val() == 0) {
                    $aTypesArea.find('input').removeAttr('checked').prop('checked', false);
                    $aTypesArea.closest('tr').addClass('hide');
                } else {
                    $aTypesArea.closest('tr').removeClass('hide');
                }
            }
            {/literal}</script>
        {/if}
        </table>
    </div>
    <!-- agreement field -->

    {rlHook name='apTplFieldsFormBottom'}

    </div>
    <!-- additional options end -->

    {assign var='no_expand' value=0}
    {if $smarty.get.action == 'edit' && $sys_fields && $field_info.Key|in_array:$sys_fields}
        {assign var='no_expand' value=1}
    {/if}

    <!-- additional JS -->
    <script type="text/javascript">
        field_types({$no_expand});

        {**
         * Show the "Autocomplete" option for the system fields always
         *}
        {if $smarty.get.action == 'edit' && $sys_fields && $field_info.Key|in_array:$sys_fields}
            {literal}
            $('#additional_options').show();
            $('#field_select').removeClass('hide');
            $('#select_block').hide();

            $('#field_select > table.form tr').each(function () {
                $(this)[$(this).find('[name="autocomplete"]').length ? 'show' : 'hide']();

            })
            {/literal}
        {/if}
    </script>

    {if $sPost.image.resize_type}
    <script type="text/javascript">
        resize_action('{$sPost.image.resize_type}');
    </script>
    {/if}
    <!-- additional JS end -->

    <table class="form">
    <tr>
        <td class="no_divider"></td>
        <td class="field">
            <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
        </td>
    </tr>
    </table>
</form>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<!-- add/edit new field end -->
