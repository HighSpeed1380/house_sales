<!-- category level checkbox crossed -->

{if $categories}
    <ul {if $first}class="first"{/if}>
        {foreach from=$categories item='cat' name='catF'}
            {if !empty($cat.Sub_cat) || ($cat.Add == '1' && $listing_types[$cat_type].Cat_custom_adding)}{assign var='sub_leval' value=true}{else}{assign var='sub_leval' value=false}{/if}
            <li id="tree_cat_{$cat.ID}" {if $cat.Lock}class="locked"{/if}>
                <img {if !$sub_leval}class="no_child"{/if} src="{$rlTplBase}img/blank.gif" alt="" />
                {assign var='c_key' value=$listing_types[$cat.Type].Page_key}
                <label><input {if $cat.Lock || $cat.ID == $category_id}disabled="disabled" class="system"{/if} type="checkbox" name="crossed_categories[]" value="{$cat.ID}" /> <span>{$cat.name}</span><a class="hide" href="{$smarty.const.RL_URL_HOME}{if $config.mod_rewrite}{$pages.$c_key}/{$cat.Path}{if $listing_types[$cat.Type].Cat_postfix}.html{else}/{/if}{else}?page={$pages.$c_key}&amp;category={$cat.ID}{/if}"></a></label>
                <span class="tree_loader"></span>
            </li>
        {/foreach}
    </ul>
{/if}

<!-- category level checkbox crossed end -->
