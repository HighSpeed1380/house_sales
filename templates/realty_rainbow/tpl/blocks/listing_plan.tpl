{strip}
<!-- listing plan tpl -->

<li id="plan_{$plan.ID}" class="plan">{/strip}
    <div class="frame{if $plan.Color} colored{/if}{if $plan.plan_disabled} disabled{/if}" {if $plan.Color}style="background-color: #{$plan.Color};border-color: #{$plan.Color};"{/if}>
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
        <span title="{$lang.plan_type}" class="type">
            {assign var='l_type' value=$plan.Type|cat:'_plan_short'}{$lang.$l_type}
        </span><span title="{$lang.listing_live}" class="count">
            {if $plan.Listing_period}{$plan.Listing_period} {$lang.days}{else}{$lang.unlimited}{/if}
        </span>

        {if $plan.Type != 'featured'}
            {if $listing_type.Photo}
            <span title="{$lang.images_number}" class="count">
                {if $plan.Image_unlim}{$lang.unlimited}{else}{$plan.Image} {$lang.photos_count}{/if}
            </span>
            {/if}

            {if $listing_type.Video}
            <span title="{$lang.number_of_videos}" class="count">
                {if $plan.Video_unlim}{$lang.unlimited}{else}{$plan.Video} {$lang.video}{/if}
            </span>
            {/if}
        {/if}

        {rlHook name='tplListingPlanService'}

   		{if $plan.des}
            <span class="description">
                <img class="qtip middle-bottom" alt="" title="{$plan.des}" id="fd_{$field.Key}" src="{$rlTplBase}img/blank.gif" />
            </span>
        {/if}

        <div class="selector">
            {if $plan.Advanced_mode}
                <input class="hide" type="radio" name="plan" value="{$plan.ID}" {if $plan.ID == $smarty.post.plan && !$plan.plan_disabled}checked="checked"{/if} />

                <label{if $plan.standard_disabled} class="hint" title="{$lang.plan_option_using_limit_hint}"{/if}><input class="multiline" {if !$plan.standard_disabled && $plan.ID == $smarty.post.plan && ($smarty.post.ad_type == 'standard' || !$smarty.post.ad_type)}checked="checked"{/if} {if $plan.standard_disabled}disabled="disabled"{/if} type="radio" name="ad_type" value="standard" /> {$lang.standard_listing} {if $plan.Standard_listings != 0}({if isset($plan.Listings_remains)}{if empty($plan.Standard_remains)}{$lang.used_up}{else}{$plan.Standard_remains}{/if}{else}{$plan.Standard_listings}{/if}){/if}</label>

                <div>
                    <label{if $plan.featured_disabled} class="hint" title="{$lang.plan_option_using_limit_hint}"{/if}><input class="multiline" {if !$plan.featured_disabled && $plan.ID == $smarty.post.plan && $smarty.post.ad_type == 'featured'}checked="checked"{/if} {if $plan.featured_disabled}disabled="disabled"{/if} type="radio" name="ad_type" value="featured" /> {$lang.featured_listing} {if $plan.Featured_listings != 0}({if isset($plan.Listings_remains)}{if empty($plan.Featured_remains)}{$lang.used_up}{else}{$plan.Featured_remains}{/if}{else}{$plan.Featured_listings}{/if}){/if}</label>
                </div>
            {else}
                <label {if $plan.plan_disabled}class="hint" title="{$lang.plan_limit_using_deny}"{/if}>
                    <input class="multiline" 
                        {if $plan.plan_disabled}
                        disabled="disabled"
                        {/if} 
                        type="radio"
                        name="plan"
                        value="{$plan.ID}" 
                        {if $plan.ID == $smarty.post.plan && !$plan.plan_disabled}
                        checked="checked"
                        {/if}
                        />
                        {if $plan.Featured || $featured}
                            {$lang.featured_listing}
                        {else}
                            {$lang.standard_listing}
                        {/if}

                        {if $plan.plan_disabled}
                            ({$lang.used_up})
                        {elseif $plan.Listings_remains}
                            ({$plan.Listings_remains})
                        {elseif $plan.Listing_number > 0}
                            ({$plan.Listing_number})
                        {/if}
                    </label>
            {/if}

            {if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}
                <div>
                    {assign var='subscription_period' value='subscription_period_'|cat:$plan.Period}
                    <label title="{$lang.subscription_period}: {$lang.$subscription_period}" class="hint"><input class="multiline" type="checkbox" name="subscription" {if $smarty.post.subscription == $plan.ID}checked="checked"{/if} value="{$plan.ID}" /> {$lang.subscription}</label>
                </div>
            {/if}
        </div>
    </div>
{strip}</li>

<!-- listing plan tpl end -->
{/strip}