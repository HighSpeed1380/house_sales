<!-- category level -->

{if $categories}
    <ul {if $first}class="first"{/if}{if $postfix} lang="{$postfix}"{/if}>
        {foreach from=$categories item='cat' name='catF'}
            {if !empty($cat.Sub_cat) || ($cat.Add == '1' && $listing_types[$cat_type].Cat_custom_adding)}{assign var='sub_leval' value=true}{else}{assign var='sub_leval' value=false}{/if}
            <li id="tree_cat_{$cat.ID}{if $postfix}_{$postfix}{/if}" {if $cat.Lock}class="locked"{/if}>
                <img {if !$sub_leval}class="no_child"{/if} src="{$rlTplBase}img/blank.gif" alt="" />
                <label><input {if $cat.ID|in_array:$deny_tree_categories}disabled="disabled"{/if} onclick="cat_chooser('{$cat.ID}');" type="radio" name="category_id" value="{$cat.ID}" /> <span>{$cat.name}</span></label>
                <span class="tree_loader"></span>
            </li>
        {/foreach}
    </ul>
{/if}

<!-- category level end -->
