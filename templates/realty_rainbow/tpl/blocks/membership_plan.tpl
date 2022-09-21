<!-- membership plan tpl -->

{assign var='item_disabled' value=false}
{if
    ($account_info.plan.ID == $plan.ID && !$new_account)
    || ($isLogin && $plan.Limit > 0 && $plan.Limit <= $plan.Count_used)
}
    {assign var='item_disabled' value=true}
{/if}

{strip}<li id="plan_{$plan.ID}" class="plan">
    <div class="frame{if $plan.Color} colored{/if}{if $item_disabled} disabled{/if}" {if $plan.Color}style="background-color: #{$plan.Color};border-color: #{$plan.Color};"{/if}>
        <span class="name">{$plan.name}</span>

        <span class="price">
            {if isset($plan.Listings_remains)}
                &#8212;
            {else}
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                {$plan.Price}
                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
            {/if}
        </span>

        <span class="count">
            {if $plan.Plan_period}{$plan.Plan_period} {$lang.days}{else}{$lang.unlimited}{/if}
        </span>

        {if $plan.Services.add_listing}
            <span class="count">
                {if $plan.Listing_number}
                    {if $plan.Featured_listing && !$plan.Advanced_mode}
                        {if $plan.Listing_number > 1}
                            {$plan.Listing_number} {$lang.featured_type_featured}
                        {else}
                            {$lang.featured_listing}
                        {/if}
                    {else}
                        {$plan.Listing_number} {$lang.listings}
                    {/if}
                {else}
                    {$lang.unlimited}
                {/if}
            </span>
        {/if}

        {foreach from=$plan.Services item='service'}
            {if !empty($service.Key)}
                {if $service.Key == 'add_listing'}{continue}{/if}
                <span class="count">{phrase key='membership_services+name+'|cat:$service.Key}</span>
            {/if}
        {/foreach}

   		{if $plan.des}
            <span class="description">
                <img class="qtip middle-bottom" alt="" title="{$plan.des}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
            </span>
        {/if}

        <div class="selector">
            <label><input class="multiline" {if $item_disabled}disabled="disabled" {/if} type="radio" name="plan" value="{$plan.ID}" {if $plan.ID == $smarty.post.plan && !$item_disabled}checked="checked"{/if} /></label>

            {if $plan.Subscription && $plan.Price > 0}
                <div>
                    {assign var='subscription_period' value='subscription_period_'|cat:$plan.Period}
                    <label title="{$lang.subscription_period}: {$lang.$subscription_period}" class="hint"><input class="multiline" type="checkbox" {if $item_disabled}disabled="disabled"{/if} name="subscription" {if $smarty.post.subscription == $plan.ID}checked="checked"{/if} value="{$plan.ID}" /> {$lang.subscription}</label>
                </div>
            {/if}
        </div>
    </div>
</li>{/strip}

<!-- membership plan tpl end -->