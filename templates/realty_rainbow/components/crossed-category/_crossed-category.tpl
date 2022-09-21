<!-- Crossed categories block -->

<div class="crossed-categories-container">
    <section class="crossed-categories">
        <div class="text-notice">
            {assign var='number_var' value=`$smarty.ldelim`number`$smarty.rdelim`}
            <span class="default">{$lang.crossed_top_text|replace:$number_var:'<b id="crossed_counter"></b>'}</span>
            <span class="exceeded">{$lang.crossed_top_text_denied}</span>
        </div>

        <div class="crossed-selection">
            {include file=$componentDir|cat:'category-selector'|cat:$smarty.const.RL_DS|cat:'_category-selector.tpl' 
                     dropdown_data=$crossed_types
                     section_postfix='crossed'
                     no_user_category=true}
        </div>

        <div class="crossed-add">
            <div class="form-buttons">
                <a href="javascript:void(0)" class="button disabled">{$lang.stick_category}</a>
            </div>
        </div>

        <div class="crossed-tree empty">
            <h3>{$lang.selected_crossed_categories}</h3>
            <ul>
                {if $crossed_categories}
                    {foreach from=$crossed_categories item='crossed_category'}
                    <li data-id="{$crossed_category.ID}">
                        <a href="javascript://" target="_blank">{phrase key='categories+name+'|cat:$crossed_category.Key}</a>
                        <img src="{$rlTplBase}img/blank.gif" class="remove" alt="{$lang.remove}" title="{$lang.remove}" />
                    </li>
                    {/foreach}
                {/if}
            </ul>
        </div>
    </section>

    <input type="hidden" name="crossed_categories" value="{$smarty.post.crossed_categories}"{if $append_to} form="{$append_to}"{/if} />
</div>

{addJS file=$rlTplBase|cat:'components/crossed-category/_crossed-category.js'}

<script class="fl-js-dynamic">
rlConfig['crossed_categories_by_type'] = {$config.crossed_categories_by_type};

lang['crossed_top_text_denied'] = "{$lang.crossed_top_text_denied}";
lang['remove'] = "{$lang.remove}";
{literal}

(function(){
    "use strict";

    rlConfig['cross_category_instance'] = new crossedCategoryClass();
    rlConfig['cross_category_instance'].init({/literal}
        {if $plan_info.Cross}{$plan_info.Cross}{else}null{/if},
        {if $selected_type}'{$selected_type}'{else}null{/if},
        {if $selected_category_id}'{$selected_category_id}'{else}null{/if}
    {literal});
})();

{/literal}
</script>

<!-- Crossed categories block end -->
