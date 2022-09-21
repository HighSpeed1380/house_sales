<!-- manage format item tpl -->

<form name="{$mode}-item-form" class="manage-item-form" action="" method="post">
    <table class="form">
    {if $mode == 'edit'}
    <tr>
        <td class="name">{$lang.key}</td>
        <td class="field">
            <input type="text" name="key" style="width: 250px;" maxlength="60" class="disabled" readonly="readonly" />
        </td>
    </tr>
    {/if}

    {if $geo_format_data && $geo_format_data.Key == $head_level_data.Key}
    <tr>
        <td class="name">{$lang.mf_path}</td>
        <td class="field">

        {if $config.mf_multilingual_path && $allLangs|@count > 1}
            <ul class="tabs">
                {foreach from=$allLangs item='language' name='langF'}
                <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                {/foreach}
            </ul>
        {/if}

        {assign var='system_path_field' value='Path'}

        {if $config.mf_multilingual_path}
            {assign var='system_path_field' value='Path_'|cat:$config.lang}
        {/if}

        {foreach from=$allLangs item='language' name='langF'}
            {assign var='path_field' value='Path'}

            {if $allLangs|@count > 1}
                {assign var='path_field' value='Path_'|cat:$language.Code}
            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
            {/if}

            {if $config.mf_geo_subdomains}
                <span class="field_description_noicon" style="padding: 0;">{strip}
                    {$domain_info.scheme}://&nbsp;

                    {if $level}
                        {if $config.mf_geo_subdomains_type == 'mixed'}
                            <span class="parent-path-cont">
                                {if $parent_path.$path_field.host}
                                    {$parent_path.$path_field.host}
                                {elseif $parent_path[$system_path_field].host}
                                    {$parent_path[$system_path_field].host}
                                {/if}
                            </span>
                        {elseif $config.mf_geo_subdomains_type == 'combined'}
                            <span class="parent-path-cont" style="margin-right: 10px;">
                                {if $parent_path.$path_field}
                                    {$parent_path.$path_field|replace:'/':'-'}-
                                {elseif $parent_path[$system_path_field]}
                                    {$parent_path[$system_path_field]|replace:'/':'-'}-
                                {/if}
                            </span>
                        {/if}
                    {/if}

                    {if ($config.mf_geo_subdomains_type == 'mixed' && !$level) || $config.mf_geo_subdomains_type != 'mixed'}
                        <input type="text" name="path_{$language.Code}" value="" /><span class="copy-phrase hide"></span>
                    {/if}

                    <span {if $config.mf_geo_subdomains_type == 'mixed' && $level}style="padding: 0;"{/if} class="field_description_noicon">.{$domain_info.host}</span>

                    {if $config.mf_geo_subdomains_type == 'mixed' && $level}
                        {if $parent_path.$path_field.dir}
                            /{$parent_path.$path_field.dir}
                        {elseif $parent_path[$system_path_field].dir}
                            /{$parent_path[$system_path_field].dir}
                        {/if}
                        /
                        <input type="text" name="path_{$language.Code}" value="" /><span class="copy-phrase hide"></span>
                    {/if}
                    /
                {/strip}</span>
            {else}
                <span class="field_description_noicon" style="padding: 0;">{strip}
                    {$smarty.const.RL_URL_HOME}
                    {if $level}
                    <span class="parent-path-cont" style="margin-right: 10px;">
                        {if $parent_path.$path_field}
                            {$parent_path.$path_field}/
                        {elseif $parent_path[$system_path_field]}
                            {$parent_path[$system_path_field]}/
                        {/if}
                    </span>
                    {/if}
                    <input type="text" name="path_{$language.Code}" style="width: 220px;" value="" /><span class="copy-phrase hide"></span>
                {/strip}</span>
            {/if}

            {if $allLangs|@count > 1}
            </div>
            {/if}
        {/foreach}

        </td>
    </tr>
    {/if}

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
                <input name="name_{$language.Code}" type="text" style="width: 250px;" />
                {if $allLangs|@count > 1}
                    <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                </div>
                {/if}
            {/foreach}
        </td>
    </tr>

    {if $config.mf_nearby_distance && $geo_format_data.Key == $head_level_data.Key}
        <tr>
            <td class="name">{$lang.mf_coordinates}</td>
            <td class="field">
                <input type="text" name="lat" value="" class="w130" />,<input type="text" name="lng" value="" class="w130" />
            </td>
        </tr>
    {/if}

    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select name="status"{if $parent_info.Status == 'approval'} disabled="disabled"{/if}>
                <option value="active" selected="selected">{$lang.active}</option>
                <option value="approval">{$lang.approval}</option>
            </select>
            {if $parent_info.Status == 'approval'}
                <span class="field_description_noicon">{$lang.mf_inactive_parent_status_hint}</span>
            {/if}
        </td>
    </tr>

    <tr>
        <td></td>
        <td class="field">
            <input type="submit" name="item_submit" value="{$lang[$mode]}" data-phrase="{$lang[$mode]}" />
            <a onclick="$('#{$mode}_item').slideUp('normal')" href="javascript:void(0)" class="cancel">{$lang.close}</a>
        </td>
    </tr>
    </table>
</form>

<!-- manage format item tpl end -->
