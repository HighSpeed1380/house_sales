{if $data}
    <div class="dark_14_bold" style="margin-bottom:15px">{$lang.mf_server_datalist}</div>

    <table style="height:100%">
    {foreach from=$data item="obj"}
        {assign var="item" value=$obj|@get_object_vars}
        <tr>
            <td style="padding:5px">
                {$item.Name}
            </td>
            <td style="padding:0 10px 8px 20px">
                <input type="button" value="{$lang.mf_import_all}" onclick="$(this).attr('disabled', 'disabled');xajax_importSource('', '{$item.Table}', 0)" />
            </td>
            <td style="padding:0 10px 8px 20px">
                <input type="button" value="{$lang.mf_import_partially}" onclick="$(this).attr('disabled', 'disabled');xajax_expandSource('{$item.Table}')" />
            </td>
            {if $config.mf_db_version == $item.Table && $item.Multilingual}
                <td style="padding:0 10px 8px 20px">
                    <input type="button" value="{$lang.mf_import_sync_phrases}" onclick="$(this).attr('disabled', 'disabled');xajax_importSource('', '{$item.Table}', 0, 0, 1)" />
                </td>
            {/if}
            {if $smarty.session.mf_import.parents}
                <td style="padding:0 10px 8px 20px">
                    <input type="button" value="{$lang.mf_import_resume}" onclick="$(this).attr('disabled', 'disabled');xajax_importSource('', '{$item.Table}', 0, 1)"/>
                </td>
            {/if}
        </tr>
    {/foreach}
    </table>
{elseif $topdata}
    <input type="hidden" value="{$table}" name="table"/>

    <div class="dark_14_bold" style="margin-bottom:15px">{$lang.mf_choose_items_to_import}</div>
    <span id="cats_nav" {if $sPost.show_on_all}class="hide"{/if}>
        <span onclick="$('div.td_div input').attr('checked', true);" class="green_10" style="cursor:pointer">{$lang.check_all}</span>
        <span class="divider"> | </span>
        <span onclick="$('div.td_div input').attr('checked', false);" class="green_10" style="cursor:pointer">{$lang.uncheck_all}</span>
    </span>

    <div style="padding:10px">
        {foreach from=$topdata item="obj" name="tdLoop"}
            {assign var="item" value=$obj|@get_object_vars}
            <div class="td_div">
                <label title="{$item.Name}" alt="{$item.Name}"><input style="margin:2px 4px 0" type="checkbox" value="{$item.Key}" />{$item.Name}</label>
            </div>
        {/foreach}
    </div>
    <div class="clear"></div>

    <span id="cats_nav" {if $sPost.show_on_all}class="hide"{/if}>
        <span onclick="$('div.td_div input').attr('checked', true);" class="green_10" style="cursor:pointer">{$lang.check_all}</span>
        <span class="divider"> | </span>
        <span onclick="$('div.td_div input').attr('checked', false);" class="green_10" style="cursor:pointer">{$lang.uncheck_all}</span>
    </span>

    <div class="hide clear field_description" style="margin:10px 10px 10px 0" id="checked_one_hint">
        <label style="margin-left:20px"><input type="checkbox" id="ignore_one" value="1" style="margin:0 10px"/>{$lang.mf_import_without_parent_ignore}</label>
        {$lang.mf_import_without_parent_hint}
    </div>
    
    <div>
        <input type="button" id="import_button" value="{$lang.mf_import}" />
        <input type="button" id="back_button" value="Back" onclick="$(this).val('{$lang.loading}');xajax_listSources()"/>
    </div>
    
    <style>
        {literal}
        .td_div
        {
            float:left;
            width:200px;
            height:19px;
            overflow:hidden;
        }
        {/literal}
    </style>
{else}
    <div style="padding: 85px 0 60px 0;text-align: center;" class="purple_13">{$lang.flynax_connect_fail}</div>
{/if}
