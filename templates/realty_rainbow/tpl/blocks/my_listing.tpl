<!-- my listing item -->

{rlHook name='myListingTop'}

{if $listing.Listing_type}
	{assign var='listing_type' value=$listing_types[$listing.Listing_type]}
{/if}

<article class="item{if $listing.Featured_expire} featured{/if} {rlHook name='tplMyListingItemClass'}" id="listing_{$listing.ID}">{strip}
	<div class="title">
		<a title="{$listing.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">
            {$listing.listing_title}
		</a>
	</div>
	<div class="nav">
		{if $listing_type.Photo}
			<div class="info">
				<a title="{$listing.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">
					<div class="picture{if !$listing.Main_photo} no-picture{/if}">                        
                        {rlHook name='tplMyListingItemPhoto'}
						{if $listing.Featured_expire}<div class="label"><div title="{$lang.featured}">{$lang.featured}</div></div>{/if}

                        <img src="{if $listing.Main_photo}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                            {if $listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$listing.Main_photo_x2} 2x"{/if}
                            alt="{$listing.listing_title}" />

						{if !empty($listing.Main_photo) && $config.grid_photos_count}
							<span accesskey="{$listing.Photos_count}"></span>
						{/if}
					</div>
				</a>
			</div>
		{/if}
		<div class="navigation">
			<ul>
				{rlHook name='myListingsIconTop'}

				<li class="nav-icon">
					<a class="edit"
                       href="{strip}
                           {if $listing.Status == 'incomplete'}
                               {pageUrl key='add_listing' vars='incomplete='|cat:$listing.ID}
                           {else}
                               {pageUrl key='edit_listing' vars='id='|cat:$listing.ID}
                           {/if}
                       {/strip}"><span>{$lang.edit_listing}</span>&nbsp;
                    </a>
				</li>

				{if $listing.Subscription_ID}
					<li class="nav-icon">
						<a class="unsubscription" id="unsubscription-{$listing.ID}" href="javascript:void(0);" accesskey="{$listing.ID}-{$listing.Subscription_ID}-{$listing.Subscription_service}"><span>{$lang.unsubscription}</span>&nbsp;</a>
					</li>
				{/if}
				
                <li class="nav-icon">
                    <a class="delete" id="delete_listing_{$listing.ID}" title="{$lang.delete}" href="javascript://"><span>{$lang.remove}</span>&nbsp;</a>
                </li>

				{rlHook name='myListingsIcon'}
			</ul>
		</div>
		<div class="stat">
			<ul>
				{if $listing.Plan_type == 'account' && ($listing.Status == 'active' || $listing.Status == 'approval')}
					<li class="switcher-controll">
						<label class="switcher switcher-status">
					        <input type="checkbox" {if $listing.Status == 'active'}checked="checked"{/if} value="{$listing.ID}" class="default">
					        <span></span>
					        <span class="status" data-enabled="{$lang.approval}" data-disabled="{$lang.active}"></span>
					    </label>
				    </li>
			    {else}
			    	<li>
						<div class="statuses">
							{if $listing.Status == 'incomplete'}
								<a href="{pageUrl key='add_listing' vars='incomplete='|cat:$listing.ID}" class="{$listing.Status}">{$lang[$listing.Status]}</a>
							{elseif $listing.Status == 'expired' || $listing.Status == 'approval'}
								<a href="{$rlBase}{if $config.mod_rewrite}{$pages.upgrade_listing}.html?id={$listing.ID}{else}?page={$pages.upgrade_listing}&amp;id={$listing.ID}{/if}" class="{$listing.Status}">{$lang[$listing.Status]}</a>
							{else}
								<span {if $listing.Status == 'pending'}title="{$lang.waiting_approval}"{/if} class="{$listing.Status}">{$lang[$listing.Status]}</span>
							{/if}
						</div>
					</li>
				{/if}
				{if $listing.Plan_type == 'account' && $account_info.plan.Advanced_mode}
				<li class="switcher-controll">
					{if $listing.Status != 'expired'}
						<label class="switcher switcher-featured">
					        <input type="checkbox" {if $listing.Featured_expire}checked="checked"{/if} value="{$listing.ID}" class="default">
					        <span></span>
					        <span class="status" data-enabled="{$lang.featured_off}" data-disabled="{$lang.featured_on}"></span>
					    </label>
				    {/if}
				</li>
				{/if}
				<li><span class="name">{$lang.added}</span> {$listing.Date|date_format:$smarty.const.RL_DATE_FORMAT}</li>

				{if $listing.Plan_expire}
					<li><span class="name">{$lang.active_till}</span> {if $listing.Plan_expire == $listing.Pay_date}{$lang.unlimited}{else}{$listing.Plan_expire|date_format:$smarty.const.RL_DATE_FORMAT}{/if}</li>
				{/if}
				{if $listing.Plan_key && $listing.Plan_type != 'account'}
					<li>
						<span class="name">{$lang.plan}</span> {$lang[$listing.Plan_key]}
						<div style="padding-top: 0px"><a href="{$rlBase}{if $config.mod_rewrite}{$pages.upgrade_listing}.html?id={$listing.ID}{else}?page={$pages.upgrade_listing}&amp;id={$listing.ID}{/if}">{$lang.upgrade_plan}</a></div>
					</li>
				{/if}
				{if $listing.Featured_expire}
					<li>
						<span class="name">{$lang.featured_till}</span> {if $listing.Featured_expire == $listing.Featured_date}{$lang.unlimited}{else}{$listing.Featured_expire|date_format:$smarty.const.RL_DATE_FORMAT}{/if}
					</li>
				{/if}

				{if $listing.Shows && $config.count_listing_visits}
					<li>
						<span class="name">{$lang.shows}</span> {$listing.Shows}
					</li>
				{/if}

				{if !$listing.Featured_expire && $listing.Status == 'active' && $available_plans && $listing.Plan_type != 'account'}
					<li>
						<a title="{$lang.make_featured}" class="nav_icon text_button" href="{$rlBase}{if $config.mod_rewrite}{$pages.upgrade_listing}/featured.html?id={$listing.ID}{else}?page={$pages.upgrade_listing}&amp;id={$listing.ID}&amp;featured{/if}">
						{$lang.make_featured}
					</a>
					</li>
				{/if}

				{rlHook name='myListingsafterStatFields'}
			</ul>
		</div>
	</div>
{/strip}</article>

<!-- my listing item end -->
