{strip}
<!-- fields block -->

<script>if (typeof window.textarea_fields == 'undefined') window.textarea_fields = new Array();</script>

{foreach from=$fields item='field'}
    {if $field.Add_page}
        {assign var='fKey' value=$field.Key}
        {assign var='fVal' value=$smarty.post.f}

        {assign var='cell_class' value='single-field'}
        {if $field.Type == 'price' || $field.Type == 'mixed'}
            {assign var='cell_class' value='combo-field'}
        {elseif $field.Type == 'checkbox'}
            {assign var='cell_class' value='checkbox-field'}
        {elseif $field.Type == 'radio'}
            {if $field.Values|@count <= 2}
                {assign var='cell_class' value='inline-fields'}
            {else}
                {assign var='cell_class' value='checkbox-field'}
            {/if}
        {elseif $field.Type == 'bool'}
            {assign var='cell_class' value='inline-fields'}
        {elseif $field.Type == 'date'}
            {assign var='cell_class' value='two-fields'}
        {elseif $field.Key == 'Category_ID' && $listing_types[$group.Listing_type].Search_multi_categories}
            {assign var="levels_number" value=$listing_types[$group.Listing_type].Search_multicat_levels}
            {if $levels_number == 2}
                {assign var='cell_class' value='two-fields'}
            {elseif $levels_number > 2}
                {assign var='cell_class' value='three-field'}
            {/if}
        {elseif $field.Type == 'phone'}
            {assign var='cell_class' value=$cell_class|cat:' phone'}
        {elseif $field.Type == 'accept'}
            {assign var='cell_class' value='inline-fields'}
        {/if}

        {if $field.Map && $account_address[$field.Key]}
            <input type="hidden" class="on_map_data" data-field-key="{$field.Key}" value="{$account_address[$field.Key]}" />
        {/if}

        {customFieldHandler field=$field assign='use_custom'}

        {assign var='default_value' value=''}

        {if !$use_custom}
            <div class="submit-cell clearfix"{if $field.Required} data-required="true"{/if}>
                <div class="name">
                    {$lang[$field.pName]}
                    {if $field.Required}
                        <span class="red">&nbsp;*</span>
                    {/if}
                    {if !empty($lang[$field.pDescription])}
                        <img class="qtip" alt="" title="{$lang[$field.pDescription]}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
                    {/if}
                </div>
                <div class="field {$cell_class}{if $field.Map && $field.Key != 'account_address_on_map'} on_map{/if}" id="sf_field_{$field.Key}">

                {if $field.Type == 'text'}
                    {if ($field.Multilingual && $languages|@count > 1) || is_array($fVal.$fKey)}
                        <ul class="tabs tabs-hash">
                            {foreach from=$languages item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                    <a href="#{$fKey}_{$language.Code}" data-target="{$fKey}_{$language.Code}">{$language.name}</a>
                                </li>
                            {/foreach}
                        </ul>
                        <div class="ml_tabs_content light-inputs">
                            {foreach from=$languages item='language' name='langF'}
                            <div lang="{$language.Code}" {if !$smarty.foreach.langF.first}class="hide"{/if} id="area_{$fKey}_{$language.Code}">
                                {if $fVal.$fKey[$language.Code]}
                                    {assign var='default_value' value=$fVal.$fKey[$language.Code]}
                                {elseif $field.pMultiDefault}
                                    {assign var='default_value' value=$field.pMultiDefault[$language.Code]}
                                {elseif $field.Default && $lang[$field.pDefault]}
                                    {assign var='default_value' value=$lang[$field.pDefault]}
                                {/if}

                                <input type="text" name="f[{$field.Key}][{$language.Code}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" />
                            </div>
                            {/foreach}
                        </div>
                    {else}
                        {if $fVal.$fKey}
                            {assign var='default_value' value=$fVal.$fKey}
                        {elseif $field.Default}
                            {assign var='default_value' value=$lang[$field.pDefault]}
                        {/if}
                        <input type="text" name="f[{$field.Key}]" {if $field.Values < 100}size="{$field.Values}" class="wauto"{/if} maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" value="{$default_value}" />
                    {/if}
                {elseif $field.Type == 'textarea'}
                    {if ($field.Multilingual && $languages|@count > 1) || is_array($fVal.$fKey)}
                        <ul class="tabs tabs-hash">
                            {foreach from=$languages item='language' name='langF'}
                                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>
                                    <a href="#{$fKey}_{$language.Code}" data-target="{$fKey}_{$language.Code}">{$language.name}</a>
                                </li>
                            {/foreach}
                        </ul>
                        <div class="ml_tabs_content">
                            {foreach from=$languages item='language' name='langF'}
                            <div lang="{$language.Code}" {if !$smarty.foreach.langF.first}class="hide"{/if} id="area_{$fKey}_{$language.Code}">
                                <textarea data-type="{$field.Condition}" rows="5" cols="" name="f[{$field.Key}][{$language.Code}]" id="textarea_{$field.Key}_{$language.Code}">
                                {if $fVal.$fKey[$language.Code]}
                                    {$fVal.$fKey[$language.Code]}
                                {/if}
                                </textarea>

                                <script>
                                if (!window.textarea_fields['textarea_{$field.Key}_{$language.Code}']) {literal} { {/literal}
                                    window.textarea_fields['textarea_{$field.Key}_{$language.Code}'] = {literal} { {/literal}
                                        type: '{$field.Condition}',
                                        length: '{$field.Values}'
                                    {literal} } {/literal};
                                {literal} } {/literal};
                                </script>
                            </div>
                            {/foreach}
                        </div>
                    {else}
                        <textarea data-type="{$field.Condition}" rows="5" cols="" name="f[{$field.Key}]" id="textarea_{$field.Key}">
                        {if $fVal.$fKey}
                            {$fVal.$fKey}
                        {elseif $field.Default}
                            {$lang[$field.pDefault]}
                        {/if}
                        </textarea>

                        <script>
                        if (!window.textarea_fields['textarea_{$field.Key}']) {literal} { {/literal}
                            window.textarea_fields['textarea_{$field.Key}'] = {literal} { {/literal}
                                type: '{$field.Condition}',
                                length: '{$field.Values}'
                            {literal} } {/literal};
                        {literal} } {/literal};
                        </script>
                    {/if}
                {elseif $field.Type == 'number'}
                    <input class="numeric wauto" type="text" name="f[{$field.Key}]" size="{if $field.Values}{$field.Values}{else}18{/if}" maxlength="{if $field.Values}{$field.Values}{else}18{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{/if} />
                {elseif $field.Type == 'phone'}
                    <span class="phone-field">
                        {if $field.Opt1}
                            + <input type="text" name="f[{$field.Key}][code]" {if $fVal.$fKey.code}value="{$fVal.$fKey.code}"{/if} maxlength="4" size="3" class="wauto ta-center numeric" /> -&nbsp;
                        {/if}
                        {if $field.Condition}
                            {assign var='df_source' value=$field.Condition|df}
                            <select name="f[{$field.Key}][area]">
                                {foreach from=$df_source item='df_item' key='df_key'}
                                    <option value="{$lang[$df_item.pName]}" {if $fVal.$fKey.area}{if $lang[$df_item.pName] == $fVal.$fKey.area}selected="selected"{/if}{else}{if $df_item.Default}selected="selected"{/if}{/if}>{$lang[$df_item.pName]}</option>
                                {/foreach}
                            </select>
                        {else}
                            <input type="text" name="f[{$field.Key}][area]" {if $fVal.$fKey.area}value="{$fVal.$fKey.area}"{/if} maxlength="{$field.Default}" size="{$field.Default}" class="ta-center numeric" />
                        {/if}
                        &nbsp;-&nbsp;
                        <input type="text" name="f[{$field.Key}][number]" {if $fVal.$fKey.number}value="{$fVal.$fKey.number}"{/if} maxlength="{$field.Values}" size="{$field.Values+2}" class="ta-center numeric" />
                        {if $field.Opt2}
                            &nbsp;{$lang.phone_ext_out} <input type="text" name="f[{$field.Key}][ext]" {if $fVal.$fKey.ext}value="{$fVal.$fKey.ext}"{/if} maxlength="4" size="3" class="ta-center" />
                        {/if}
                    </span>
                {elseif $field.Type == 'date'}
                    {if $field.Default == 'single'}
                        <input class="date"
                            type="text"
                            id="date_{$field.Key}"
                            name="f[{$field.Key}]"
                            maxlength="10"
                            value="{$fVal.$fKey}"
                            autocomplete="off" />

                        <script class="fl-js-dynamic">{literal}
                        $(document).ready(function(){
                            $('#date_{/literal}{$field.Key}{literal}')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                        });
                        {/literal}</script>
                    {elseif $field.Default == 'multi'}
                        <input placeholder="{$lang.from}"
                            class="date"
                            type="text"
                            id="date_{$field.Key}_from"
                            name="f[{$field.Key}][from]"
                            maxlength="10"
                            value="{$fVal.$fKey.from}"
                            autocomplete="off" />

                        <input placeholder="{$lang.to}"
                            class="date"
                            type="text"
                            id="date_{$field.Key}_to"
                            name="f[{$field.Key}][to]"
                            maxlength="10"
                            value="{$fVal.$fKey.to}"
                            autocomplete="off" />

                        <script class="fl-js-dynamic">{literal}
                        $(document).ready(function(){
                            $('#date_{/literal}{$field.Key}{literal}_from')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                            $('#date_{/literal}{$field.Key}{literal}_to')
                                .datepicker({
                                    showOn     : 'focus',
                                    dateFormat : 'yy-mm-dd',
                                    changeMonth: true,
                                    changeYear : true,
                                    yearRange  : '-100:+30'
                                })
                                .datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                        });
                        {/literal}</script>
                    {/if}
                {elseif $field.Type == 'mixed' || $field.Type == 'unit'}
                    <input class="numeric" type="text" name="f[{$field.Key}][value]" size="8" maxlength="15" {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if} />
                    {if !empty($field.Condition)}
                        {assign var='df_source' value=$field.Condition|df}
                    {else}
                        {assign var='df_source' value=$field.Values}
                    {/if}

                    {if $df_source|@count > 1}
                        <select name="f[{$field.Key}][df]">
                            {foreach from=$df_source item='df_item' key='df_key'}
                                <option value="{$df_item.Key}" {if ($df_item.Key == $fVal.$fKey.df) || (!$fVal.$fKey.df && $df_item.Default)}selected="selected"{/if}>{$lang[$df_item.pName]}</option>
                            {/foreach}
                        </select>
                    {else}
                        <input type="hidden" name="f[{$field.Key}][df]" value="{foreach from=$df_source item='mItem'}{$mItem.Key}{assign var='mixed_cur_val' value=$mItem.pName}{break}{/foreach}" />
                        <span>{$lang[$mixed_cur_val]}</span>
                    {/if}
                {elseif $field.Type == 'price'}
                    {assign var='currency' value='currency'|df}
                    <input class="numeric" type="text" name="f[{$field.Key}][value]" size="8" maxlength="15" {if $fVal.$fKey.value}value="{$fVal.$fKey.value}"{/if} />
                    {if $currency|@count > 1}
                        <select name="f[{$field.Key}][currency]">
                            {foreach from=$currency item='currency_item'}
                                <option value="{$currency_item.Key}" {if ($currency_item.Key == $fVal.$fKey.currency) || (!$fVal.$fKey.currency && $currency_item.Default)}selected="selected"{/if}>{$lang[$currency_item.pName]}</option>
                            {/foreach}
                        </select>
                    {else}
                        <input type="hidden" name="f[{$field.Key}][currency]" value="{foreach from=$currency item='cItem'}{$cItem.Key}{assign var='price_cur_val' value=$cItem.name}{break}{/foreach}" />
                        <span>{$price_cur_val}</span>
                    {/if}
                {elseif $field.Type == 'bool'}
                    <span class="custom-input">
                        <label>
                            <input type="radio" value="1" name="f[{$field.Key}]" {if $fVal.$fKey == '1'}checked="checked"{elseif $field.Default}checked="checked"{/if} />
                            {$lang.yes}
                        </label>
                    </span>
                    <span class="custom-input">
                        <label>
                            <input type="radio" value="0" name="f[{$field.Key}]" {if $fVal.$fKey == '0'}checked="checked"{elseif !$field.Default && !$fVal.$fKey}checked="checked"{/if}/>
                            {$lang.no}
                        </label>
                    </span>
                {elseif $field.Type == 'select'}
                    {rlHook name='tplListingFieldSelect'}
                    <select class="{if $field.Key == 'year'}w120{/if}{if $field.Autocomplete} select-autocomplete{/if}" name="f[{$field.Key}]">
                        <option value="0">{$lang.select}</option>

                        {foreach from=$field.Values item='option' key='key'}
                            {if $field.Condition}
                                {assign var='key' value=$option.Key}
                            {/if}
                            <option value="{if $field.Condition}{$option.Key}{else}{$key}{/if}" {if $fVal.$fKey}{if $fVal.$fKey == $key}selected="selected"{/if}{else}{if ($field.Default == $key) || $option.Default }selected="selected"{/if}{/if}>{if $field.Condition == 'years'}{$option.name}{else}{$lang[$option.pName]}{/if}</option>
                        {/foreach}
                    </select>
                {elseif $field.Type == 'checkbox'}
                    {assign var='fDefault' value=$field.Default}
                    {if $field.Opt2}{math assign='col_count' equation='12 / opt' opt=$field.Opt2}{/if}
                    <input type="hidden" name="f[{$field.Key}][0]" value="0" />

                    <div class="row">
                    {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                        {if $field.Condition}
                            {assign var='key' value=$option.Key}
                        {/if}
                        <span class="custom-input col-xs-12 {if $col_count}col-sm-{$col_count}{else}col-lg-4 col-md-6 col-sm-4{/if}">
                            <label title="{$lang[$option.pName]}">
                                    <input type="checkbox" {if is_array($fVal.$fKey)}{if $key|in_array:$fVal.$fKey}checked="checked"{/if}{else}{if $option.Default || ($field.Default && is_numeric($key|array_search:$field.Default))}checked="checked"{/if}{/if} value="{$key}" name="f[{$field.Key}][{$key}]" />
                                {$lang[$option.pName]}
                            </label>
                        </span>
                    {/foreach}
                    </div>

                    <div class="checkbox_bar"><a href="javascript:void(0)" onclick="$(this).parent().prev().find('input[type=checkbox]').attr('checked', true)">{$lang.check_all}</a> / <a onclick="$(this).parent().prev().find('input[type=checkbox]').attr('checked', false)" href="javascript:void(0)">{$lang.uncheck_all}</a></div>
                {elseif $field.Type == 'radio'}
                    <input type="hidden" value="0" name="f[{$field.Key}]" />

                    {if $field.Values|@count > 2}<div class="row">{/if}
                    {foreach from=$field.Values item='option' key='key' name='checkboxF'}
                        {if $field.Condition}
                            {assign var='key' value=$option.Key}
                        {/if}

                        <span class="custom-input{if $field.Values|@count > 2} col-xs-12 col-sm-6 col-md-4{/if}">
                            <label title="{$lang[$option.pName]}">
                                <input type="radio" value="{$key}" name="f[{$field.Key}]" {if $fVal.$fKey}{if $fVal.$fKey == $key}checked="checked"{/if}{else}{if ($field.Default == $key) || $option.Default}checked="checked"{/if}{/if} />
                                {$lang[$option.pName]}
                            </label>
                        </span>
                    {/foreach}
                    {if $field.Values|@count > 2}</div>{/if}
                {elseif $field.Type == 'file' || $field.Type == 'image'}
                    {assign var="field_value" value=''}

                    {if $fVal.$fKey}
                        {assign var="field_value" value=$fVal[$fKey]}
                    {elseif $smarty.post.f_sys_exist.$fKey}
                        {assign var="field_value" value=$smarty.post.f_sys_exist.$fKey}
                    {/if}

                    {if $field_value && !$field.Key|files}
                        <div id="{$field.Key}_file"
                            class="image-field-preview file-data"
                            data-field="{$field.Key}"
                            data-value="{$field_value}"
                            data-type="listing">
                            <div class="relative fleft">
                                <input type="hidden" name="f[sys_exist_{$field.Key}]" value="{$field_value}" />

                                <div class="fleft" style="margin-bottom: 5px;">
                                    <div>
                                        <table class="sTable">
                                            <tr>
                                                <td>{$lang.currently_uploaded_file}</td>
                                                <td class="ralign">
                                                    <a href="javascript://" id="delete_{$field.Key}" class="remove-file">
                                                        {$lang.remove}
                                                        <img id="delete_{$field.Key}" class="delete icon"
                                                        src="{$rlTplBase}img/blank.gif" alt="" title="{$lang.delete}" />
                                                    </a>
                                                </td></tr>
                                        </table>
                                    </div>
                                    <span style="font-style:italic;" class="dark_13" title="{$field_value}">
                                        <b>{$field_value}</b>
                                    </span>
                                </div>
                                <div class="clear"></div>

                                {if $field.Type == 'image'}
                                    <img style="width: auto;" class="thumbnail" title="{$field.name}"
                                        alt="{$field.name}" src="{$smarty.const.RL_FILES_URL}{$field_value}" />
                                {/if}
                            </div>
                            <div class="clear"></div>
                        </div>
                    {else}
                        {getTmpFile field=$field.Key parent="f"}
                    {/if}

                    {assign var='field_type' value=$field.Default}
                    <div class="file-input">
                        <input type="hidden" name="f[{$field.Key}]" value="" />
                        <input class="file" type="file" name="{$field.Key}" />
                        <input type="text" class="file-name" name="" />
                        <span>{$lang.choose}</span>
                    </div>
                    {if $field.Type == 'file' && !empty($field.Default)}<em>{$l_file_types.$field_type.name} (.{$l_file_types.$field_type.ext|replace:',':', .'})</em>{/if}
                {elseif $field.Type == 'accept'}
                    <input class="hide"
                        value="0"
                        type="checkbox"
                        name="f[{$field.Key}]"
                        checked="checked" />

                    <label>
                        <input value="1"
                            type="checkbox"
                            name="f[{$field.Key}]"
                            {if $fVal.$fKey == '1'}checked="checked"{/if} />
                        &nbsp;{$lang.agree}

                        <a target="_blank" href="{pageUrl key=$field.Default}">
                            {phrase key='pages+name+'|cat:$field.Default}
                        </a>
                    </label>
                {/if}

                </div>
            </div>
        {/if}
    {/if}
{/foreach}

<!-- fields block end -->
{/strip}
