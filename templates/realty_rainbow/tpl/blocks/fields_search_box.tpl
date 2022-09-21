{strip}
<!-- fields block ( for search ) -->

{if $listing_type.Submit_method == 'post'}
    {assign var='fVal' value=$smarty.post}
{else}
    {assign var='fVal' value=$smarty.get}
{/if}

{assign var='sbd_file' value=$smarty.const.RL_PLUGINS|cat:$smarty.const.RL_DS|cat:'search_by_distance'|cat:$smarty.const.RL_DS|cat:'field.tpl'}
{assign var="multicat_listing_type" value=$group.Listing_type}
{assign var="levels_number" value=$listing_types[$multicat_listing_type].Search_multicat_levels}
{assign var='any_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}

{foreach from=$fields item='field'}
    {assign var='fKey' value=$field.Key}

    {assign var='cell_class' value='single-field'}
    {if $field.Type == 'price' || $field.Type == 'mixed'}
        {assign var='cell_class' value='three-field'}
    {elseif $field.Type == 'select' && $field.Condition == 'years'}
        {assign var='cell_class' value='two-fields'}
    {elseif $field.Type == 'bool'}
        {assign var='cell_class' value='couple-field'}
    {elseif $field.Type == 'date'}
        {assign var='cell_class' value='two-fields'}
    {elseif $field.Type == 'number'}
        {assign var='cell_class' value='two-fields'}
    {elseif $field.Type == 'checkbox'}
        {assign var='cell_class' value='checkbox-field'}
    {elseif $field.Type == 'radio'}
        {if $field.Values|@count > 2}
        {assign var='cell_class' value='checkbox-field'}
        {else}
            {assign var='cell_class' value='couple-field'}
        {/if}
    {/if}

    {if $in_category_search && $field.Key == 'Category_ID'}
        {assign var='cell_class' value=$cell_class|cat:' hide'}
    {/if}

    <div class="search-item	{$cell_class}">

    <div class="field">
        {assign var='field_phrase_key' value=$field.pName}
        {if $field.Key == $config.sbd_zip_field}
            {assign var='field_phrase_key' value='sbd_field_label'}
        {elseif $listing_types[$multicat_listing_type].Search_multi_categories > 0 && $field.Key == 'Category_ID' && $listing_types[$multicat_listing_type].Search_multicat_phrases}
            {assign var='field_phrase_key' value='multilevel_category+'|cat:$multicat_listing_type|cat:'+'|cat:$smarty.const.RL_LANG_CODE|cat:'+1'}
        {/if}

        {if $lang.$field_phrase_key}{$lang.$field_phrase_key}{else}{$lang[$field.pName]}{/if}
    </div>

    {if $field.Type == 'text'}
        {if $aHooks.search_by_distance && $field.Key == $config.sbd_zip_field && is_file($sbd_file)}
            {include file=$sbd_file}
        {else}
            <input type="text" name="f[{$field.Key}]" maxlength="{if $field.Values != ''}{$field.Values}{else}255{/if}" {if $fVal.$fKey}value="{$fVal.$fKey}"{/if} />
            {if $field.Key == 'keyword_search'}
                <div class="kws-block">
                    <div class="options hide">
                        <ul>
                            {assign var='tmp' value=3}
                            {section name='keyword_opts' loop=$tmp max=3}
                                <li><label><input {if $fVal.keyword_search_type}{if $smarty.section.keyword_opts.iteration == $fVal.keyword_search_type}checked="checked"{/if}{else}{if $smarty.section.keyword_opts.iteration == $config.keyword_search_type}checked="checked"{/if}{/if} value="{$smarty.section.keyword_opts.iteration}" type="radio" name="f[keyword_search_type]" /> {assign var='ph' value='keyword_search_opt'|cat:$smarty.section.keyword_opts.iteration}{$lang.$ph}</label></li>
                            {/section}
                        </ul>
                    </div>
                    <div><span id="refine_keyword_opt" class="link">{$lang.advanced_options}</span></div>
                </div>
            {/if}
        {/if}
    {elseif $field.Type == 'number'}
        {if $aHooks.search_by_distance && $field.Key == $config.sbd_zip_field && is_file($sbd_file)}
            {include file=$sbd_file}
        {else}
            <input value="{if $fVal.$fKey.from}{$fVal.$fKey.from}{/if}" placeholder="{$lang.from}" class="numeric" type="text" name="f[{$field.Key}][from]" maxlength="{if $field.Values}{$field.Values}{else}18{/if}" />
            <input value="{if $fVal.$fKey.to}{$fVal.$fKey.to}{/if}" placeholder="{$lang.to}" class="numeric" type="text" name="f[{$field.Key}][to]" maxlength="{if $field.Values}{$field.Values}{else}18{/if}" />
        {/if}
    {elseif $field.Type == 'date'}
        {addCSS file=$rlTplBase|cat:'css/jquery.ui.css'}

        {if $field.Default == 'multi'}
            <input class="date"
                type="text"
                id="date_{$field.Key}{if $postfix}_{$postfix}{/if}_{$post_form_key}"
                name="f[{$field.Key}]"
                maxlength="10"
                value="{$fVal.$fKey}"
                autocomplete="off" />
            <div class="clear"></div>

            <script class="fl-js-dynamic">
            $(document).ready(function(){literal}{{/literal}
                $('#date_{$field.Key}{if $postfix}_{$postfix}{/if}_{$post_form_key}')
                    .datepicker({literal}{
                        showOn     : 'focus',
                        dateFormat : 'yy-mm-dd',
                        changeMonth: true,
                        changeYear : true,
                        yearRange  : '-100:+30'
                    }{/literal})
                    .datepicker($.datepicker.regional['{$smarty.const.RL_LANG_CODE}']);
            {literal}}{/literal});
            </script>
        {elseif $field.Default == 'single'}
            <input placeholder="{$lang.from}"
                class="date"
                type="text"
                id="date_{$field.Key}_from{if $postfix}_{$postfix}{/if}_{$post_form_key}"
                name="f[{$field.Key}][from]"
                maxlength="10"
                value="{$fVal.$fKey.from}"
                autocomplete="off" />

            <input placeholder="{$lang.to}"
                class="date"
                type="text"
                id="date_{$field.Key}_to{if $postfix}_{$postfix}{/if}_{$post_form_key}"
                name="f[{$field.Key}][to]"
                maxlength="10"
                value="{$fVal.$fKey.to}"
                autocomplete="off" />

            <script class="fl-js-dynamic">
            $(document).ready(function(){literal}{{/literal}
                $('#date_{$field.Key}_from{if $postfix}_{$postfix}{/if}_{$post_form_key}')
                    .datepicker({literal}{
                        showOn     : 'focus',
                        dateFormat : 'yy-mm-dd',
                        changeMonth: true,
                        changeYear : true,
                        yearRange  : '-100:+30'
                    }{/literal})
                    .datepicker($.datepicker.regional['{$smarty.const.RL_LANG_CODE}']);

                $('#date_{$field.Key}_to{if $postfix}_{$postfix}{/if}_{$post_form_key}')
                    .datepicker({literal}{
                        showOn     : 'focus',
                        dateFormat : 'yy-mm-dd',
                        changeMonth: true,
                        changeYear : true,
                        yearRange  : '-100:+30'
                    }{/literal})
                    .datepicker($.datepicker.regional['{$smarty.const.RL_LANG_CODE}']);
            {literal}}{/literal});
            </script>
        {/if}
    {elseif $field.Type == 'mixed'}
        <input value="{if $fVal.$fKey.from}{$fVal.$fKey.from}{/if}" placeholder="{$lang.from}" class="numeric" type="text" name="f[{$field.Key}][from]" maxlength="15" />
        <input value="{if $fVal.$fKey.to}{$fVal.$fKey.to}{/if}" placeholder="{$lang.to}" class="numeric" type="text" name="f[{$field.Key}][to]" maxlength="15" />

        {if !empty($field.Condition)}
            {assign var='df_source' value=$field.Condition|df}
        {else}
            {assign var='df_source' value=$field.Values}
        {/if}

        {if $df_source|@count == 1}
            <span>{foreach from=$df_source item='df_item'}{$lang[$df_item.pName]}{break}{/foreach}</span>
        {elseif $df_source|@count > 1}
            <select name="f[{$field.Key}][df]">
                <option value="0">{$lang.unit}</option>
                {foreach from=$df_source item='df_item'}
                    <option value="{$df_item.Key}" {if $df_item.Key == $fVal.$fKey.df}selected="selected"{/if}>{$lang[$df_item.pName]}</option>
                {/foreach}
            </select>
        {/if}
    {elseif $field.Type == 'price'}
        <input {if $fVal.$fKey.from}value="{$fVal.$fKey.from}"{/if} placeholder="{$lang.from}" class="numeric" type="text" name="f[{$field.Key}][from]" maxlength="15" />
        <input {if $fVal.$fKey.to}value="{$fVal.$fKey.to}"{/if} placeholder="{$lang.to}" class="numeric" type="text" name="f[{$field.Key}][to]" maxlength="15" />

        {assign var='currency_suorce' value='currency'|df}
        {if $currency_suorce|@count == 1}
            <span>{foreach from=$currency_suorce item='currency_item'}{$lang[$currency_item.pName]}{break}{/foreach}</span>
        {elseif $currency_suorce|@count > 1}
            <select title="{$lang.currency}" name="f[{$field.Key}][currency]">
                <option value="0">{$lang.any|replace:'-':''}</option>
                {foreach from=$currency_suorce item='currency_item'}
                    <option value="{$currency_item.Key}" {if $currency_item.Key == $fVal.$fKey.currency}selected="selected"{/if}>{$lang[$currency_item.pName]}</option>
                {/foreach}
            </select>
        {/if}
    {elseif $field.Type == 'bool'}
        <span class="custom-input">
            <label>
                <input type="radio" value="1" name="f[{$field.Key}]" {if $fVal.$fKey == '1'}checked="checked"{/if} />
                {$lang.yes}
            </label>
        </span>
        <span class="custom-input">
            <label>
                <input type="radio" value="0" name="f[{$field.Key}]" {if $fVal.$fKey == '0'}checked="checked"{/if}/>
                {$lang.no}
            </label>
        </span>
    {elseif $field.Type == 'select'}
        {rlHook name='tplSearchFieldSelect'}

        {if $field.Condition == 'years'}
            <select name="f[{$field.Key}][from]">
                <option value="0">{$lang.from}</option>
                {foreach from=$field.Values item='option' key='key'}
                    {if $field.Condition}
                        {assign var='key' value=$option.Key}
                    {/if}
                    <option {if $fVal.$fKey.from}{if $fVal.$fKey.from == $key}selected="selected"{/if}{/if} value="{if $field.Condition}{$option.Key}{else}{$key}{/if}">{$option.name}</option>
                {/foreach}
            </select>
            <select name="f[{$field.Key}][to]">
                <option value="0">{$lang.to}</option>
                {foreach from=$field.Values item='option' key='key'}
                    {if $field.Condition}
                        {assign var='key' value=$option.Key}
                    {/if}
                    <option {if $fVal.$fKey.to}{if $fVal.$fKey.to == $key}selected="selected"{/if}{/if} value="{if $field.Condition}{$option.Key}{else}{$key}{/if}">{$option.name}</option>
                {/foreach}
            </select>
        {elseif $field.Key == 'Category_ID' && $listing_types[$multicat_listing_type].Search_multi_categories}
            <input type="hidden"
                   data-listing-type="{$listing_types[$multicat_listing_type].Key}"
                   name="f[Category_ID]"
                   value="{if $fVal.$fKey}{$fVal.$fKey}{elseif $in_category_search}{$category.ID}{/if}" />

            <input type="hidden"
                   name="f[category_parent_ids]"
                   value="{if $fVal.category_parent_ids}{$fVal.category_parent_ids}{elseif $in_category_search}{$category.ID}{/if}" />

            <select class="multicat{if $field.Autocomplete} select-autocomplete{/if}" id="cascading-category-{$multicat_listing_type}-{$post_form_key}">
                <option value="0">{$lang.any}</option>
                {foreach from=$field.Values item='option' key='key'}
                    <option {if $fVal.$fKey == $option.ID}selected="selected"{/if} value="{$option.ID}">{phrase key=$option.pName}</option>
                {/foreach}
            </select>

            <script class="fl-js-dynamic">
                {literal}
                flUtil.loadScript(rlConfig['tpl_base'] + 'components/cascading-category/_cascading-category.js', function(){
                    $('#cascading-category-{/literal}{$multicat_listing_type}-{$post_form_key}{literal}').cascadingCategory();
                });
                {/literal}
            </script>

            {section name='multicat' start=1 loop=$levels_number step=1}

        </div>

        {if $in_category_search && $category.Level + 1 == $smarty.section.multicat.index}
            {assign var='cell_class' value=$cell_class|replace:' hide':''}
        {/if}

        <div class="search-item {$cell_class}">
            <div class="field">
                {assign var='field_phrase_key' value='subcategory'}
                {if $listing_types[$multicat_listing_type].Search_multi_categories > 0 && $listing_types[$multicat_listing_type].Search_multicat_phrases}
                    {assign var='field_phrase_key' value='multilevel_category+'|cat:$multicat_listing_type|cat:'+'|cat:$smarty.const.RL_LANG_CODE|cat:'+'|cat:$smarty.section.multicat.index+1}
                {/if}

                {if $lang.$field_phrase_key}{$lang.$field_phrase_key}{else}{$lang.subcategory}{/if}
            </div>

            <select disabled="disabled" class="multicat disabled{if $field.Autocomplete} select-autocomplete{/if}">
                <option value="0">{$lang.any}</option>
            </select>

            {/section}
        {else}
            <select name="f[{$field.Key}]"{if $field.Autocomplete} class="select-autocomplete"{/if}>
                <option value="0">{$lang.any}</option>
                {foreach from=$field.Values item='option' key='key'}
                    {if $field.Key == 'Category_ID'}
                        {assign var='key' value=$option.ID}
                    {elseif $field.Condition}
                        {assign var='key' value=$option.Key}
                    {/if}
                    <option{if isset($fVal.$fKey) && $fVal.$fKey == $key} selected="selected"{/if} value="{$key}">{phrase key=$option.pName}</option>
                {/foreach}
            </select>
        {/if}
    {elseif $field.Type == 'checkbox'}
        {assign var='fDefault' value=$field.Default}
        <input type="hidden" name="f[{$field.Key}][0]" value="0" />

        {foreach from=$field.Values item='option' key='key' name='checkboxF'}
            {if $field.Condition}
                {assign var='key' value=$option.Key}
            {/if}
            {assign var='chPost' value=$fVal.$fKey}

            <span class="custom-input">
                <label title="{$lang[$option.pName]}">
                    <input type="checkbox" {if $chPost.$key}checked="checked"{/if} value="{$key}" name="f[{$field.Key}][{$key}]" />
                    {$lang[$option.pName]}
                </label>
            </span>
        {/foreach}
    {elseif $field.Type == 'radio'}
        <input type="hidden" value="0" name="f[{$field.Key}]" />

        {foreach from=$field.Values item='option' key='key' name='radioF'}
            {if $field.Condition}
                {assign var='key' value=$option.Key}
            {/if}

            <span class="custom-input">
                <label title="{$lang[$option.pName]}">
                    <input type="radio" value="{$key}" name="f[{$field.Key}]" {if $fVal.$fKey}{if $fVal.$fKey == $key}checked="checked"{/if}{/if} />
                    {$lang[$option.pName]}
                </label>
            </span>
        {/foreach}
    {elseif $field.Type == 'phone'}
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
            <input type="text" name="f[{$field.Key}][area]" {if $fVal.$fKey.area}value="{$fVal.$fKey.area}"{/if} maxlength="{$field.Default}" size="{$field.Default}" class="wauto ta-center numeric" />
        {/if}
        &nbsp;-&nbsp;
        <input type="text" name="f[{$field.Key}][number]" {if $fVal.$fKey.number}value="{$fVal.$fKey.number}"{/if} maxlength="{$field.Values}" size="{$field.Values+2}" class="wauto ta-center numeric" />
        {if $field.Opt2}
            &nbsp;{$lang.phone_ext_out} <input type="text" name="f[{$field.Key}][ext]" {if $fVal.$fKey.ext}value="{$fVal.$fKey.ext}"{/if} maxlength="4" size="3" class="wauto ta-center" />
        {/if}

    {/if}
    </div>
{/foreach}

<!-- fields block ( for search ) end -->
{/strip}
