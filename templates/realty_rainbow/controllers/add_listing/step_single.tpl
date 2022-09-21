<!-- single step mode -->

{addJS file=$rlTplBase|cat:'js/util_data.js'}

{rlHook name='tplStepSingleTop'}

<div id="category_container" class="content-padding{if $manageListing->category} selected{/if}">
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

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'add_user_category.tpl'}

        <div class="dynamic-content">
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

            {if !$manageListing->singlePlan}
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
                                        {if $manageListing->planID == $plan.ID && !$plan.plan_disabled}
                                            selected="selected"
                                        {/if}
                                        {if $plan.plan_disabled}
                                            disabled="disabled"
                                        {/if}>

                                        {$plan.name} ({strip}
                                        {if !$plan.Advanced_mode && $plan.plan_disabled}
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

                        <div class="plans-subscribe disabled">
                            <label>
                                <input form="listing_form" type="checkbox" name="subscription"{if $smarty.post.subscription} checked="checked"{/if} />
                                {$lang.recurring}
                            </label>
                        </div>
                    </div>
                </div>
            {/if}

            <div class="selected-ad-type disabled submit-cell">
                <div class="name">{$lang.listing_type}</div>
                <div class="field checkbox-field" id="sf_field_fetured">
                    <div class="row">
                        <span class="ad-standard custom-input col-xs-12 col-sm-6 col-md-4">
                            <label title="{$lang.standard}">
                                <input form="listing_form"
                                    type="radio" value="standard" name="ad_type"
                                    {if $smarty.post.ad_type == 'standard' || !$smarty.post.ad_type}
                                    checked="checked"
                                    {/if} /> {$lang.standard} <mark></mark>
                            </label>
                        </span>
                        <span class="ad-featured custom-input col-xs-12 col-sm-6 col-md-4">
                            <label title="{$lang.featured}">
                                <input form="listing_form"
                                    type="radio" value="featured" name="ad_type"
                                    {if $smarty.post.ad_type == 'featured'}
                                    checked="checked"
                                    {/if} /> {$lang.featured} <mark></mark>
                            </label>
                        </span>
                    </div>
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
                                     append_to='listing_form'}
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

                {if $config.add_listing_without_reg && !$isLogin}
                <div class="form-auth">
                    <div class="submit-cell">
                        <div class="name"><div class="content-padding">{$lang.authorization}</div></div>
                        <div class="field light-inputs content-padding-negative">
                            {include file=$controllerDir|cat:'add_listing'|cat:$smarty.const.RL_DS|cat:'auth_form.tpl'}
                        </div>
                    </div>
                </div>
                {/if}

                <div class="submit-cell form-buttons">
                    <div class="name"></div>
                    <input form="listing_form" type="submit" value="{$lang.add_listing}" data-default-phrase="{$lang.add_listing}" />
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
        rlConfig['crossed_categories_by_type'] = {$config.crossed_categories_by_type};

        rlConfig['manageListing'] = [];
        rlConfig['manageListing']['from_post'] = {if $smarty.post.from_post}true{else}false{/if};
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

{rlHook name='tplStepSingleBottom'}

<!-- single step mode end -->
