<!-- my package item -->

<li class="clearfix">
	<div class="frame clearfix" {if $package.Color}style="background-color: #{$package.Color};border-color: #{$package.Color};"{/if}>
		<h3 class="name">{$package.name}</h3>
		<div class="plan-info">
			<span class="price">
				{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
				{$package.Price}
				{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
			</span>
			<span>
				{$lang.plan_live_for}: <span class="highlight">{if $package.Plan_period}{$package.Plan_period} {$lang.days}{else}{$lang.unlimited_short}{/if}</span>
			</span>
		</div>

		<div class="listing-info">
			<span class="count">
				{$lang.listing_live_for}: <span class="highlight">{if $package.Listing_period}{$package.Listing_period} {$lang.days}{else}{$lang.unlimited_short}{/if}</span>
			</span>
			<span title="{$lang.images_number}" class="count">
				{$lang.photos_count}: <span class="highlight">{if $package.Image_unlim}{$lang.unlimited_short}{else}{$package.Image}{/if}</span>
			</span>
			<span title="{$lang.number_of_videos}" class="count">
				{$lang.video}: <span class="highlight">{if $package.Video_unlim}{$lang.unlimited_short}{else}{$package.Video}{/if}</span>
			</span>

			{rlHook name='tplMyPackageItemListingInfo'}
		</div>
	</div>

	<div class="status">
		<ul class="package_info">
			{if $package.Advanced_mode}
				<li>
					{$lang.standard}:
					<span {if empty($package.Standard_remains) && !empty($package.Standard_listings)}class="overdue"{/if}>
						{if empty($package.Standard_listings)}
							<b>{$lang.unlimited_short}</b>
						{else}
							{assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
							{assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
							{$lang.rest_of_amount|replace:$rRest:$package.Standard_remains|replace:$rAmount:$package.Standard_listings}
						{/if}
					</span>
				</li>
				<li style="padding-top: 5px;">
					{$lang.featured}:
					<span {if empty($package.Featured_remains) && !empty($package.Featured_listings)}class="overdue"{/if}>
						{if empty($package.Featured_listings)}
							<b>{$lang.unlimited_short}</b>
						{else}
							{assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
							{assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
							{$lang.rest_of_amount|replace:$rRest:$package.Featured_remains|replace:$rAmount:$package.Featured_listings}
						{/if}
					</span>
				</li>
			{else}
				<li>
					{if $package.Featured}
						{$lang.featured}: 
					{else}
						{$lang.standard}: 
					{/if}
					<span {if empty($package.Listings_remains) && !empty($package.Listing_number)}class="overdue"{/if}>
						{if empty($package.Listing_number)}
							<b>{$lang.unlimited_short}</b>
						{else}
							{assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
							{assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
							{$lang.rest_of_amount|replace:$rRest:$package.Listings_remains|replace:$rAmount:$package.Listing_number}
						{/if}
					</span>
				</li>
			{/if}
			<li class="{$package.Exp_status}" style="padding: 10px 0 0;">
				{$lang.expiration_date}: {if $package.Exp_date == 'unlimited'}{$lang.unlimited_short}{else}{$package.Exp_date|date_format:$smarty.const.RL_DATE_FORMAT}{/if}
			</li>
		</ul>

		{if !$renew}
			<div class="renew">
				{if $package.Subscription}
					<a class="unsubscription button" id="unsubscription-{$package.ID}" href="javascript:void(0);" accesskey="{$package.ID}-{$package.Subscription_ID}-{$package.Subscription_service}"><span>{$lang.unsubscription}</span>&nbsp;</a>
				{else}
					<a class="button" href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?renew={$package.ID}{else}?page={$pageInfo.Path}&amp;renew={$package.ID}{/if}">{$lang.renew}</a>
				{/if}
			</div>
		{/if}
	</div>
</li>

<!-- my package item emd -->
