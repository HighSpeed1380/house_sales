<!-- upgrade listing plan -->

{addCSS file=$rlTplBase|cat:'components/plans-chart/plans-chart.css'}

{if isset($smarty.get.completed)}

	<span class="text-notice">
		{assign var='replace' value='<a href="'|cat:$link|cat:'">$1</a>'}
		{$lang.notice_payment_listing_completed|regex_replace:'/\[(.*)\]/':$replace}
	</span>
	
{elseif isset($subscription.ID)}

	<span class="text-notice" style="display: inline-block;margin-bottom: 15px;">{$lang.notice_has_active_subscription}</span>

	<div class="content-padding">
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='subscription_details' name=$lang.subscription_details tall=true}
	
		<div class="table-cell">
			<div class="name">{$lang.item}:</div>
			<div class="value">{$listing_title}</div>
		</div>
		<div class="table-cell">
			<div class="name">{$lang.plan}:</div>
			<div class="value">{$plans[$listing.Plan_ID].name}</div>
		</div>
		<div class="table-cell">
			<div class="name">{$lang.price}:</div>
			<div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$plans[$listing.Plan_ID].Price}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
		</div>
		<div class="table-cell">
			<div class="name">{$lang.subscription_period}:</div>
			{assign var='subscription_period_name' value='subscription_period_'|cat:$plans[$listing.Plan_ID].Period}
			<div class="value">{$lang.$subscription_period_name}</div>
		</div>

		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

		<div class="table-cell">
			<div class="value">
				<a class="unsubscription button" id="unsubscription-{$subscription.Item_ID}" href="javascript:void(0);" accesskey="{$subscription.Item_ID}-{$subscription.ID}-{$subscription.Service}">{$lang.unsubscription}</a>
			</div>
		</div>
	</div>

	<script type="text/javascript">
	{literal}

	$(document).ready(function(){
		$('.unsubscription').each(function() {
			$(this).flModal({
				caption: '',
				content: '{/literal}{$lang.stripe_unsubscripbe_confirmation}{literal}',
				prompt: 'flSubscription.cancelSubscription(\''+ $(this).attr('accesskey').split('-')[2] +'\', \''+ $(this).attr('accesskey').split('-')[0] +'\', '+ $(this).attr('accesskey').split('-')[1] +', \'{/literal}{$pageInfo.Key}{literal}\')',
				width: 'auto',
				height: 'auto'
			});
		});
	});
	{/literal}
	</script>

	{addJS file=$rlTplBase|cat:'js/subscription.js' id='subscription'}
{else}

	{rlHook name='upgradeListingTop'}

	<form method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}{if $featured}/featured{/if}.html?id={if $smarty.get.id}{$smarty.get.id}{else}{$smarty.get.item}{/if}{else}?page={$pageInfo.Path}&amp;id={if $smarty.get.id}{$smarty.get.id}{else}{$smarty.get.item}{/if}{if $featured}&amp;featured{/if}{/if}">
		<input type="hidden" name="upgrade" value="true" />
		<input type="hidden" name="from_post" value="1" />

		<!-- select a plan -->
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'divider.tpl' name=$lang.select_plan}

		<div class="plans-container">
            {assign var=subscription_exists value=false}
            {assign var=featured_exists value=false}
            {foreach from=$plans item='plan'}{if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}{assign var=subscription_exists value=true}{elseif $plan.Featured && $plan.Price > 0 && !$plan.Listings_remains}{assign var=featured_exists value=true}{/if}{/foreach}
            <ul class="plans{if $plans|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}{if $featured_exists} with-featured{/if}">
			{foreach from=$plans item='plan' name='plansF'}{strip}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_plan.tpl'}
			{/strip}{/foreach}
			</ul>
		</div>

		<script type="text/javascript">
		var plans = Array();
		var selected_plan_id = 0;
		var last_plan_id = 0;
		{foreach from=$plans item='plan'}
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
			flynax.qtip();
		});
		
		{/literal}
		</script>
		<!-- select a plan end -->

		<div class="form-buttons">
			<input type="submit" value="{$lang.next}" />
		</div>
		
	</form>

	{rlHook name='upgradeListingBottom'}
	
{/if}

<!-- upgrade listing plan end -->
