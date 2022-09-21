<!-- step form -->

{addJS file=$rlTplBase|cat:'js/util_data.js'}

{rlHook name='editListingTopTpl'}

<div id="category_container" class="content-padding selected">
    {if !$allowed_types}
        {$lang.add_listing_deny}
    {else}
        <div class="category-selection">
            <div class="text-notice">{$lang.add_listing_notice}</div>

            {include file=$componentDir|cat:'category-selector'|cat:$smarty.const.RL_DS|cat:'_category-selector.tpl' dropdown_data=$allowed_types}

            <div class="form-buttons">
                <a id="next_step" href="javascript:void(0)" class="button disabled" data-default-value="{$lang.select_category}">
                    {$lang.select_category}
                </a>
            </div>
        </div>

        <div class="dynamic-content{if $plans|@count == 1 && $manageListing->planID} single-plan{/if}">
            {if !$single_category_mode}
            <div class="selected-category submit-cell">
                <div class="name">{$lang.category}</div>
                <div class="field checkbox-field">
                    <span class="link">
                        {if $parent_names}
                            {foreach from=$parent_names item='parent_name'}
                                {phrase key='categories+name+'|cat:$parent_name.Key} /
                            {/foreach}
                        {/if}
                        {$manageListing->category.name}
                    </span>
                </div>
            </div>
            {/if}

            <div class="selected-plan submit-cell">
                <div class="name">{$lang.plan} <span class="red">&nbsp;*</span></div>
                <div class="field single-field">
                    <select form="listing_form" name="plan">
                        <option value="0">{$lang.select}</option>
                        {if $plans}
                            {foreach from=$plans item='plan'}
                                <option
                                    value="{$plan.ID}"
                                    {if isset($plan.Listings_remains)}
                                    data-available="true"
                                    {/if}
                                    {if $manageListing->planID == $plan.ID}
                                        selected="selected"
                                    {/if}
                                    {if $plan.plan_disabled && $manageListing->planID != $plan.ID}
                                        disabled="disabled"
                                    {/if}>

                                    {$plan.name} ({strip}
                                    {if !$plan.Advanced_mode && $item_disabled}
                                        {$lang.used_up}
                                    {elseif isset($plan.Listings_remains)}
                                        {$lang.available}
                                    {else}
                                        {if $plan.Price == 0}
                                            {$lang.free}
                                        {else}
                                            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                            {$plan.Price}
                                            {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                        {/if}
                                    {/if}
                                    {/strip})
                                </option>
                            {/foreach}
                        {/if}
                    </select>

                    <span class="plans-chart-link link">{$lang.view_plans_chart}</span>
                </div>
            </div>

            <div class="listing-form">
                {if !$single_category_mode}
                <div class="form-crossed disabled">
                    <div class="submit-cell">
                        <div class="name"><div class="content-padding">{$lang.crossed_categories}</div></div>
                        <div class="field">
                            {include file=$componentDir|cat:'crossed-category'|cat:$smarty.const.RL_DS|cat:'_crossed-category.tpl'
                                     crossed_types=$allowed_types
                                     append_to='listing_form'
                                     selected_type=$manageListing->listingType.Key}
                        </div>
                    </div>
                </div>
                {/if}

                <div class="form-fields">
                    {if $form}
                        {include file=$controllerDir|cat:'add_listing'|cat:$smarty.const.RL_DS|cat:'step_form.tpl'}
                    {else}
                        No form data available
                    {/if}
                </div>

                <div class="form-media">
                    <div class="submit-cell">
                        <div class="name">
                            <div class="content-padding">
                                {assign var='mediaRequired' value=false}
                                {if $manageListing->listingType && $manageListing->listingType.Photo_required === '1'}
                                    {assign var='mediaRequired' value=true}
                                {/if}

                                {$lang.add_media}<span class="red{if !$mediaRequired} d-none{/if}">&nbsp;*</span>
                            </div>
                        </div>
                        <div class="field">
                            {include file=$controllerDir|cat:'add_listing'|cat:$smarty.const.RL_DS|cat:'step_photo.tpl'}
                        </div>
                    </div>
                </div>

                <div class="submit-cell form-buttons">
                    <div class="name"></div>
                    <input form="listing_form" type="submit" value="{$lang.edit_listing}" data-default-phrase="{$lang.edit_listing}" />
                </div>
            </div>
        </div>

        {include file=$controllerDir|cat:'add_listing'|cat:$smarty.const.RL_DS|cat:'plan_option.tpl'}

        <script>
        lang.single_step_select_plan = '{$lang.single_step_select_plan}';
        lang.select_plan             = '{$lang.select_plan}';
        lang.apply                   = '{$lang.apply}';
        lang.used_up                 = '{$lang.used_up}';
        lang.notice_no_plans_related = '{$lang.notice_no_plans_related}';

        rlConfig['user_category_path_prefix'] = '{$manageListing->userCategoryPathPrefix}';

        rlConfig['manageListing'] = [];
        rlConfig['manageListing']['from_post'] = true;
        rlConfig['manageListing']['single_plan'] = {if $manageListing->singlePlan}true{else}false{/if};
        rlConfig['manageListing']['selected_plan_id'] = {if $manageListing->planID}{$manageListing->planID}{else}null{/if};
        rlConfig['manageListing']['current_plans'] = {if $manageListing->plans}JSON.parse('{$manageListing->plans|@json_encode}'.replace(/(\r\n|\n)/gi, '<br />')){else}[]{/if};
        rlConfig['manageListing']['selected_category_id'] = {if $manageListing->category.ID}'{$manageListing->category.ID}'{else}null{/if};
        rlConfig['manageListing']['selected_type'] = {if $manageListing->listingType}'{$manageListing->listingType.Key}'{else}null{/if};
        rlConfig['manageListing']['user_category_id'] = {if $manageListing->category.user_category_id}'{$manageListing->category.user_category_id}'{else}null{/if};
        rlConfig['manageListing']['parent_ids'] = '{if $manageListing->category.Parent_IDs}{$manageListing->category.Parent_IDs}{/if}';
        </script>

        {addJS file=$rlTplBase|cat:'controllers/add_listing/single_step.js' id='single-step'}
    {/if}
</div>

{rlHook name='editListingBottomTpl'}

<!-- step form end -->
