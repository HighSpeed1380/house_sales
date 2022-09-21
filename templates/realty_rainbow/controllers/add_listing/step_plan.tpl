<!-- select plan step -->

<form method="post" action="{buildFormAction}">
    <input type="hidden" name="step" value="plan" />
    <input type="hidden" name="from_post" value="1" />

    {assign var=subscription_exists value=false}
    {assign var=featured_exists value=false}

    {foreach from=$plans item='plan'}
        {if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}
            {assign var=subscription_exists value=true}
        {elseif $plan.Featured && $plan.Price > 0 && !$plan.Listings_remains}
            {assign var=featured_exists value=true}
        {/if}
    {/foreach}

    <div class="plans-container{if $manageListing->planType == 'account'} membership-plans{/if}{if $manageListing->singleStep} mCustomScrollbar{/if}">
        <ul class="plans{if $plans|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}{if $featured_exists} with-featured{/if}">
            {foreach from=$plans item='plan' name='plansF'}{strip}
                {if $manageListing->planType == 'account'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'membership_plan.tpl' new_account=true}
                {else}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_plan.tpl'}
                {/if}
            {/strip}{/foreach}
        </ul>
    </div>
    
    {if !$manageListing->singleStep}
    <div class="form-buttons">
        {if !$single_category_mode}
        <a href="{buildPrevStepURL}">{$lang.perv_step}</a>
        {/if}
        <input type="submit" value="{$lang.next_step}" />
    </div>
    {/if}
</form>

<script class="fl-js-dynamic">
$(function() {literal} { {/literal}
    var plans = Array();

    {foreach from=$plans item='plan'}
        plans[{$plan.ID}] = new Array();
        {if $manageListing->planType == 'listing'}
            plans[{$plan.ID}]['Advanced_mode'] = {$plan.Advanced_mode};
            plans[{$plan.ID}]['Package_ID'] = {if $plan.Package_ID}{$plan.Package_ID}{else}false{/if};
        {/if}
    {/foreach}

    flynax.planClick(plans);
    flynax.qtip(); // Combine this
    flynaxTpl.qtip(); // and this one
{literal} } {/literal});
</script>

<!-- select plan step end -->
