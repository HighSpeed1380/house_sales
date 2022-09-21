<!-- bottom listing details seller -->

{if $contact}
    {assign var='seller_info' value=$contact}
{/if}

{if !$is_owner && $config.messages_module && ($isLogin || (!$isLogin && $config.messages_allow_free)) && !$contact}
    {assign var='allow_contact_section' value=true}
{/if}

{if !$allow_contact_section && !$sidebar}
    {assign var='inline_contacts' value=true}
{/if}

<div class="{if !$inline_contacts}row {/if}seller-short">
    <div class="{if !$inline_contacts}col-sm-6 {if $sidebar}col-md-12{else}col-md-6 col-xs-12{/if}{/if}{if $seller_info.Listings_count && $seller_info.Own_page && !$owner_page} button-exists{/if}">
        {if $inline_contacts}
        <div class="row">
            <div class="col-sm-6">
        {/if}

        {if !$block.Header && !$sidebar}
            {assign var='get_more_phrase' value='blocks+name+get_more_details'}
            <h3>{$lang.$get_more_phrase}</h3>
        {/if}

        <div class="clearfix relative">
            {if $seller_info.Photo}
                <div class="picture{if $seller_info.Thumb_width > 120} landscape{/if}">
                    {if $seller_info.Own_page && !$owner_page}<a target="_blank" title="{$lang.visit_owner_page}" href="{$seller_info.Personal_address}">{/if}
                    <img alt="{$lang.seller_thumbnail}"
                        src="{$smarty.const.RL_FILES_URL}{$seller_info.Photo}"
                        {if $seller_info.Photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$seller_info.Photo_x2} 2x"{/if} />
                    {if $seller_info.Own_page && !$owner_page}</a>{/if}
                </div>
            {/if}
            <ul class="seller-info">
                <li class="name">
                    {if $seller_info.Own_page && !$owner_page}<a title="{$lang.visit_owner_page}" href="{$seller_info.Personal_address}">{/if}
                    {$seller_info.Full_name}
                    {if $seller_info.Own_page && !$owner_page}</a>{/if}

                    {if $seller_info.Type}
                        {assign var='type_replace' value=`$smarty.ldelim`account_type`$smarty.rdelim`}
                        {assign var='date_replace' value=`$smarty.ldelim`date`$smarty.rdelim`}
                        {assign var='date' value=$seller_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                        <div class="type">{$lang.account_type_since_data|replace:$type_replace:$seller_info.Type_name|replace:$date_replace:$date}</div>
                    {/if}
                </li>

                {if $seller_info.Fields.about_me.value}
                    <li class="about">{$seller_info.Fields.about_me.value}</li>
                {/if}

                {if !$owner_page && $seller_info.Listings_count && $seller_info.Own_page}
                    <li class="listings-button">
                        <a class="button low" href="{$seller_info.Personal_address}#listings" title="{$lang.account_listings}">{phrase key='account_listings'}</a>
                    </li>
                {/if}
            </ul>

            {if $seller_info.Listings_count && !$contact}
                {assign var='listings_count_exists' value=true}
            {/if}

            {if $owner_page && ($listings_count_exists || $seller_info.Agents_count)}
                <ul class="d-flex align-items-center pt-4">
                    {if $listings_count_exists}
                        <li class="counter pt-0">
                            <span class="counter d-inline">{$seller_info.Listings_count}</span>
                            <span>{$lang.listings}</span>
                        </li>
                    {/if}

                    {if $seller_info.Agents_count && $listings_count_exists}
                        <li class="ml-3"><span class="date">/</span></li>
                    {/if}

                    {if $seller_info.Agents_count}
                        <li class="counter pt-0{if $listings_count_exists} ml-3{/if}">
                            {if $seller_info.Personal_address}
                                <a title="{$lang.agency_listings}"
                                   href="{$seller_info.Personal_address}#agents"
                                   class="agencies-agents"
                                >
                            {/if}
                            <span class="counter d-inline">{$seller_info.Agents_count}</span>
                            <span>{$lang.agents}</span>
                            {if $seller_info.Personal_address}</a>{/if}
                        </li>
                    {/if}
                </ul>
            {/if}
        </div>

        {assign var='show_owner_details' value=false}

        {foreach from=$owner_short_details item='item'}
            {if !$item.Map && !empty($item.value) && $item.Details_page && $item.Key != 'First_name' && $item.Key != 'Last_name' && $item.Key != 'about_me'}
                {assign var='show_owner_details' value=true}
                {break}
            {/if}
        {/foreach}

        {if $inline_contacts}
            </div>
            <div class="col-sm-6">
        {/if}

        {if $owner_short_details && $show_owner_details}
            <div class="owner-details">
                {if !$allow_contacts}<h3 class="cd-caption">{$lang.contact_details}</h3>{/if}
                <div class="info-table">
                    <div{if !$allow_contacts} class="masked"{/if}>
                        {foreach from=$owner_short_details item='item'}
                            {if !$item.Map && !empty($item.value) && $item.Details_page && $item.Key != 'First_name' && $item.Key != 'Last_name' && $item.Key != 'about_me'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' small=true}
                            {/if}
                        {/foreach}

                        {if $seller_info.Display_email}
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.mail}</div>
                                <div class="value">{if $allow_contacts}{encodeEmail email=$seller_info.Mail}{else}{assign var='mail_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}{$lang.fake_value|replace:$mail_replace:$lang.mail}{/if}</div>
                            </div>
                        {/if}

                        {if !$allow_contacts}
                            <div class="login-mask">
                                <div class="restricted-content">
                                    {if $isLogin}
                                        <p>{$lang.contacts_not_available}</p>
                                        <span>
                                            <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                                        </span>
                                    {else}
                                        <p>{$lang.contact_details_hint}</p>
                                        <span>
                                            <a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
                                        </span>
                                    {/if}
                                </div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        {/if}

        {rlHook name='listingDetailsSellerBox'}

        {if $inline_contacts}
            </div>
        </div>
        {/if}
    </div>

    {if $allow_contact_section}
        <div class="col-sm-6 {if $sidebar}col-md-12 form{else}col-md-6 col-xs-12{/if}">
            {if !$allow_send_message}<h3 class="cd-caption">{$lang.contact_the_owner}</h3>{/if}
            <div{if !$allow_send_message} class="masked"{/if}>
                {if $sidebar && $config.show_call_owner_button && $pageInfo.Controller == 'listing_details' && $allow_contacts}
                    {include file=$componentDir|cat:'call-owner/_sidebar-buttons.tpl'}
                {else}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'contact_seller_form.tpl'}
                {/if}

                {if !$allow_send_message}
                    <div class="login-mask">
                        <div class="restricted-content">
                            {if $isLogin}
                                <p>{$lang.contact_form_not_available}</p>
                                <span>
                                    <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                                </span>
                            {else}
                                <p>{$lang.contact_owner_hint}</p>
                                <span>
                                    <a href="javascript://" class="button login">{$lang.sign_in}</a> <span>{$lang.or}</span> <a title="{$lang.registration}" href="{pageUrl key='registration'}">{$lang.sign_up}</a>
                                </span>
                            {/if}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    {/if}
</div>

<!-- bottom listing details seller end -->
