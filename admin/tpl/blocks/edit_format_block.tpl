<!-- edit format form -->

<form onsubmit="editItem('{$item.Key}');$('input[name=item_edit]').val('{$lang.loading}');return false;" action="" method="post">
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.key}</td>
        <td class="field">
            <input readonly="readonly" class="disabled" type="text" id="ni_key" style="width: 200px;" maxlength="60" value="{$item.Key}" />
        </td>
    </tr>
    
    <tr>
        <td class="name">
            <span class="red">*</span>{$lang.value}
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
                <input id="ei_{$language.Code}" type="text" style="width: 250px;" value="{$names[$language.Code].Value}" />
                {if $allLangs|@count > 1}
                    <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                </div>
                {/if}
            {/foreach}
        </td>
    </tr>
    
    {rlHook name='apTplDataFormatsEditItemField'}
    
    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select id="ei_status">
                <option value="active" {if $item.Status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $item.Status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>
    
    <tr>
        <td class="name">{$lang.default}</td>
        <td class="field">
            <input type="checkbox" id="ei_default" value="1" {if $item.Default}checked="checked"{/if}/> 
        </td>
    </tr>
    
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" name="item_edit" value="{$lang.edit}" />
            <a href="javascript:void(0)" onclick="$('#edit_item').slideUp('normal')" class="cancel" type="button">{$lang.close}</a>
        </td>
    </tr>
    </table>
</form>

<!-- edit format form end -->
