{strip}
<!-- category level -->

{if $category.Type}
    {assign var='cat_type' value=$category.Type}
{else}
    {assign var='cat_type' value=$section.Type}
{/if}

{assign var='replace' value=`$smarty.ldelim`category`$smarty.rdelim`}

<div>
    <select size="10" {if $section.Key}class="section_{$section.Key}"{/if} id="tree_area_{if $category.ID}{$category.ID}{else}0{/if}">
        <option value="" class="disabled">{$lang.select}</option>
        {foreach from=$categories item='cat' name='catF'}
            {if !empty($cat.Sub_cat) || ($cat.Add == '1' && $listing_types[$cat_type].Cat_custom_adding)}
                {assign var='sub_leval' value=true}
            {else}
                {assign var='sub_leval' value=false}
            {/if}

            <option {if $deny_tree_categories && $cat.ID|in_array:$deny_tree_categories}disabled="disabled"{/if} title="{$cat.name}" {*if $cat.Lock && !$sub_leval}disabled="disabled"{/if*} class="{if !$sub_leval}no_child{/if}" id="tree_cat_{$cat.ID}" value="{if $mode == 'link'}{$rlBase}index.php?controller={$smarty.get.controller}&action=add&category={$cat.ID}{else}{$cat.ID}{/if}">{$cat.name}{if $cat.Lock} - {$lang.locked}{/if}</option>
        {/foreach}
    </select>
</div>

<!-- category level end -->
{/strip}
