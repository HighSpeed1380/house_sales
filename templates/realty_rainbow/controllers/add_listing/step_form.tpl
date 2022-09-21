<!-- fill in form step -->

{addJS file=$smarty.const.RL_LIBS_URL|cat:'ckeditor/ckeditor.js'}
{addJS file=$rlTplBase|cat:'js/form.js'}

<script>window.textarea_fields = new Array();</script>

<form id="listing_form" enctype="multipart/form-data" method="post" action="{buildFormAction show_extended=$manageListing->singleStep}">
    <input type="hidden" name="step" value="form" />
    <input type="hidden" name="from_post" value="1" />

    {if $plan_info.Cross && !$manageListing->singleStep && !$single_category_mode}
        <div class="content-padding">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='crossed_categories_group' name=$lang.crossed_categories}
            {include file=$componentDir|cat:'crossed-category'|cat:$smarty.const.RL_DS|cat:'_crossed-category.tpl'
                     selected_type=$manageListing->listingType.Key
                     selected_category_id=$manageListing->category.ID}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
        </div>
    {/if}

    <div class="content-padding">
        {rlHook name='addListingPreFields'}

        {if !$manageListing->singleStep && $manageListing->planType == 'account' && $plan_info.Advanced_mode}
        <div class="submit-cell clearfix">
            <div class="name">
                {$lang.listing_type}
            </div>

            <div class="field checkbox-field" id="sf_field_fetured">
                <div class="row">
                    <span class="custom-input col-xs-12 col-sm-6 col-md-4">
                        <label title="{$lang.standard}">
                            {if $plan_info.Standard_listings == 0}
                                <input type="radio" value="standard" name="ad_type" {if $smarty.post.ad_type == 'standard' || (!isset($smarty.post.ad_type) && $account_info.plan.Featured_remains <= 0 && $plan_info.Featured_listings > 0)}checked="checked"{/if} /> {$lang.standard}
                            {else}
                                {if ($smarty.post.ad_type == 'standard' || !isset($smarty.post.ad_type)) && $plan_info.Standard_listings > 0 && (!isset($account_info.plan.Standard_remains) || (isset($account_info.plan.Standard_remains) && $account_info.plan.Standard_remains > 0))}
                                    {assign var='standard_checked' value=true}
                                {/if}
                                <input type="radio" value="standard" name="ad_type" {if $standard_checked}checked="checked"{/if} {if $plan_info.Standard_listings <= 0 || (isset($account_info.plan.Standard_remains) && $account_info.plan.Standard_remains <= 0)}disabled="disabled"{/if} />
                                {$lang.standard} ({if $plan_using}{$plan_using.Standard_remains}{else}{$plan_info.Standard_listings}{/if})
                            {/if}
                        </label>
                    </span>
                    <span class="custom-input col-xs-12 col-sm-6 col-md-4">
                        <label title="{$lang.featured}">
                            {if $plan_info.Featured_listings == 0}
                                <input type="radio" value="featured" name="ad_type" {if $smarty.post.ad_type == 'featured' || (!isset($smarty.post.ad_type) && $account_info.plan.Standard_remains <= 0 && $plan_info.Standard_listings > 0)}checked="checked"{/if} /> {$lang.featured}
                            {else}
                                {if ($smarty.post.ad_type == 'featured' || (!isset($smarty.post.ad_type) && $account_info.plan.Standard_remains <= 0)) && $plan_info.Featured_listings > 0 && (!isset($account_info.plan.Featured_remains) || (isset($account_info.plan.Featured_remains) && $account_info.plan.Featured_remains > 0)) && !$standard_checked}
                                    {assign var='featured_checked' value=true}
                                {/if}
                                <input type="radio" value="featured" name="ad_type" {if $featured_checked}checked="checked"{/if} {if $plan_info.Featured_listings <= 0 || (isset($account_info.plan.Featured_remains) && $account_info.plan.Featured_remains <= 0)}disabled="disabled"{/if} />
                                {$lang.featured} ({if $plan_using}{$plan_using.Featured_remains}{else}{$plan_info.Featured_listings}{/if})
                            {/if}
                        </label>
                    </span>
                </div>
            </div>
        </div>
        {/if}

        {foreach from=$form item='group'}
        {if $group.Group_ID}
            {if $group.Fields && $group.Display}
                {assign var='hide' value=false}
            {else}
                {assign var='hide' value=true}
            {/if}

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.Key name=$lang[$group.pName]}
            {if $group.Fields}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
            {else}
                {$lang.no_items_in_group}
            {/if}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
        {else}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
        {/if}
        {/foreach}
    </div>

    <!-- login/sing up form -->
    {if !$manageListing->singleStep && $config.add_listing_without_reg && !$isLogin}
        <div class="submit-cell">
            <div class="name"><div class="content-padding">{$lang.authorization}</div></div>
            <div class="field light-inputs">
                {include file=$controllerDir|cat:'add_listing'|cat:$smarty.const.RL_DS|cat:'auth_form.tpl'}
            </div>
        </div>
    {/if}
    <!-- login/sing up form end -->

    {if $config.security_img_add_listing && $manageListing->controller == 'add_listing'}
    <div class="submit-cell">
        <div class="name">{$lang.security_code}</div>
        <div class="field">{include file='captcha.tpl' no_caption=true}</div>
    </div>
    {/if}

    {if !$manageListing->singleStep}
        <span class="form-buttons form">
            <a href="{buildPrevStepURL}">{$lang.perv_step}</a>
            <input type="submit" value="{$lang.next_step}" />
        </span>
    {/if}

    {rlHook name='tplStepFormAfterForm'}
</form>

<script class="fl-js-dynamic">
{literal}

$(function(){
    flForm.auth();
    flForm.typeQTip();
    flForm.fields();
    flForm.accountFieldSimulation();
    flForm.fileFieldAction();
    flForm.realtyPropType();

    // TODO
    flynaxTpl.locationHandler();
    flynax.qtip();
    hashTabs();
});

{/literal}
</script>

<!-- fill in form step end -->
