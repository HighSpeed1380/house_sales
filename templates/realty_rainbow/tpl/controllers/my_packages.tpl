<!-- my packages tpl -->

{addCSS file=$rlTplBase|cat:'components/plans-chart/plans-chart.css'}

<script type="text/javascript">flynax.qtip();</script>

{if $renew_id}

	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='plan_block' name=$lang.plan_details}
		<ul class="packages">
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'my_package_item.tpl' package=$pack_info renew=true}
		</ul>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

	<form id="form-checkout" name="payment" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?renew={$renew_id}{else}?page={$pageInfo.Path}&renew={$renew_id}{/if}">
		<input type="hidden" name="step" value="checkout" />
		{gateways}

		<div class="form-buttons">
			<input type="submit" value="{$lang.checkout}" />
		</div>
	</form>

{elseif $purchase}

	{if empty($available_packages)}
		<div class="info">{$lang.no_available_packages}</div>
	{else}
		<form name="payment" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/purchase.html{else}?page={$pageInfo.Path}&amp;purchase{/if}">
			<input type="hidden" name="action" value="submit" />
            {assign var=subscription_exists value=false}
            {assign var=featured_exists value=false}
            {foreach from=$available_packages item='plan'}{if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}{assign var=subscription_exists value=true}{elseif $plan.Featured && $plan.Price > 0 && !$plan.Listings_remains}{assign var=featured_exists value=true}{/if}{/foreach}
            <!-- select a plan -->
			<div class="plans-container">
                <ul class="plans{if $available_packages|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}{if $featured_exists} with-featured{/if}">
                    {foreach from=$available_packages item='plan' name='plansF'}{strip}
                        {assign var='item_disabled' value=false}
                        {if $used_plans_id && $plan.ID|in_array:$used_plans_id}
                            {assign var='item_disabled' value=true}
                        {/if}

                        <li id="plan_{$plan.ID}" class="plan">
                            <div class="frame{if $plan.Color} colored{/if}{if $item_disabled} disabled{/if}" {if $plan.Color}style="background-color: #{$plan.Color};border-color: #{$plan.Color};"{/if}>
                                <span class="name">{$plan.name}</span>
                                <span class="price">
                                    {if isset($plan.Listings_remains) || $item_disabled}
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
                                <span title="{$lang.images_number}" class="count">
                                    {if $plan.Image_unlim}{$lang.unlimited}{else}{$plan.Image} {$lang.photos_count}{/if}
                                </span>
                                <span title="{$lang.number_of_videos}" class="count">
                                    {if $plan.Video_unlim}{$lang.unlimited}{else}{$plan.Video} {$lang.video}{/if}
                                </span>

                                {rlHook name='tplMyPackagesPlanService'}

                                {if $plan.des}
                                    <span class="description">
                                        <img class="qtip middle-bottom" alt="" title="{$plan.des}" id="fd_{$plan.Key}" src="{$rlTplBase}img/blank.gif" />
                                    </span>
                                {/if}

                                <div class="selector">
                                    <label {if $item_disabled}class="hint" title="{$lang.duplicate_package_purchase_error}"{/if}><input class="multiline" {if $item_disabled}disabled="disabled" {/if} type="radio" name="plan" value="{$plan.ID}" {if $plan.ID == $smarty.post.plan && !$item_disabled}checked="checked"{/if} /></label>

                                    {if $plan.Subscription && $plan.Price > 0 && !$item_disabled}
                                        <div>
                                            {assign var='subscription_period' value='subscription_period_'|cat:$plan.Period}
                                            <label title="{$lang.subscription_period}: {$lang.$subscription_period}" class="hint"><input type="checkbox" name="subscription" {if $smarty.post.subscription == $plan.ID}checked="checked"{/if} class="multiline" value="{$plan.ID}" /> {$lang.subscription}</label>
                                        </div>
                                    {/if}
                                </div>
                            </div>
                        </li>
                    {/strip}{/foreach}
                </ul>
			</div>

			<script type="text/javascript">
			var plans = Array();
			var selected_plan_id = 0;
			var last_plan_id = 0;
			{foreach from=$available_packages item='plan'}
			plans[{$plan.ID}] = new Array();
			plans[{$plan.ID}]['Key'] = '{$plan.Key}';
			plans[{$plan.ID}]['Price'] = {$plan.Price};
			plans[{$plan.ID}]['Featured'] = {$plan.Featured};
			plans[{$plan.ID}]['Advanced_mode'] = {$plan.Advanced_mode};
			plans[{$plan.ID}]['Package_ID'] = {if $plan.Package_ID}{$plan.Package_ID}{else}false{/if};
			plans[{$plan.ID}]['Standard_listings'] = {$plan.Standard_listings};
			plans[{$plan.ID}]['Featured_listings'] = {$plan.Featured_listings};
			plans[{$plan.ID}]['Standard_remains'] = {if $plan.Standard_remains}{$plan.Standard_remains}{else}false{/if};
			plans[{$plan.ID}]['Featured_remains'] = {if $plan.Featured_remains}{$plan.Featured_remains}{else}false{/if};
			plans[{$plan.ID}]['Listings_remains'] = {if $plan.Listings_remains}{$plan.Listings_remains}{else}false{/if};
			{/foreach}

			{literal}

			$(document).ready(function(){
				flynax.planClick();
			});

			{/literal}
			</script>
			<!-- select a plan end -->
            <div class="nav-buttons">
                <input type="submit" value="{$lang.next}" />
            </div>
		</form>
	{/if}

{else}

	{if $packages}
		<ul class="packages">
			{foreach from=$packages item='package' name='packagesF'}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'my_package_item.tpl'}
			{/foreach}
		</ul>

		<a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/purchase.html{else}?page={$pageInfo.Path}&amp;purchase{/if}" class="button">{$lang.purchase_new_package}</a>

		<script type="text/javascript">{literal}
		$(document).ready(function(){
			$('.packages .unsubscription').each(function() {
				$(this).flModal({
					caption: '',
					content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
					prompt: 'xajax_cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +')',
					width: 'auto',
					height: 'auto'
				});
			});
		});

		{/literal}</script>
	{else}

		{if $config.mod_rewrite}
			{assign var='link' value=$rlBase|cat:$pageInfo.Path|cat:'/purchase.html'}
		{else}
			{assign var='link' value=$rlBase|cat:'?page='|cat:$pageInfo.Path|cat:'&amp;purchase'}
		{/if}
		{assign var='replace' value='<a href="'|cat:$link|cat:'" class="static">$1</a>'}
		<span class="info">{$lang.no_packages_available|regex_replace:'/\[(.*)\]/':$replace}</span>

	{/if}

{/if}

<!-- my packages tpl end -->
