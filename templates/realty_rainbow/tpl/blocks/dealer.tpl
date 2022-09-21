<!-- account item -->

<article class="col-sm-4{if $dealer.Thumb_width <= 120} col-md-{if $side_bar_exists}6{else}4{/if} col-lg-4{else}{if !$side_bar_exists} col-md-3{/if}{/if}">
    <div class="main-container clearfix{if $dealer.Thumb_width > 120} landscape{/if}{if !$dealer.Photo} no-picture{/if}" {if $dealer.Thumb_width > 120}style="width: {$dealer.Thumb_width}px;"{/if}>
        {if $dealer.Personal_address}<a title="{$dealer.Full_name}" href="{$dealer.Personal_address}">{/if}
            <div class="picture" style="width: {$dealer.Thumb_width}px; height: {$dealer.Thumb_height}px;">
                {if $dealer.Photo}
                    <img alt="{$dealer.Full_name}"
                         src="{$smarty.const.RL_FILES_URL}{$dealer.Photo}"
                         {if $dealer.Photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$dealer.Photo_x2} 2x"{/if} />
                {/if}
            </div>
        {if $dealer.Personal_address}</a>{/if}

        <div class="statistics">
            <ul>
                <li class="name">
                    {if $dealer.Personal_address}<a title="{$lang.visit_owner_page}" href="{$dealer.Personal_address}">{/if}
                        {if $dealer.company_name}{$dealer.company_name}{else}{$dealer.Full_name}{/if}
                    {if $dealer.Personal_address}</a>{/if}
                </li>
                {*<li class="date" title="{$lang.join_date}">
                    {assign var='type_replace' value=`$smarty.ldelim`account_type`$smarty.rdelim`}
                    {assign var='date_replace' value=`$smarty.ldelim`date`$smarty.rdelim`}
                    {assign var='date' value=$dealer.Date|date_format:$smarty.const.RL_DATE_FORMAT}

                    {$lang.account_type_since_data|replace:$type_replace:$dealer.Type_name|replace:$date_replace:$date}
                </li>*}
                {rlHook name='accountAfterStats'}
            </ul>

            {if $dealer.Listings_count}
                <div class="counter">
                    <span>{$dealer.Listings_count} {$lang.listings}</span>
                </div>
            {/if}

            {if $dealer.Agents_count}
                <div class="counter mt-1">
                    <span>{$dealer.Agents_count} {$lang.agents}</span>
                </div>
            {/if}
        </div>
    </div>

    <ul class="info">
        <li class="fields">
            {assign var='phone' value=false}
            {assign var='inline_fields' value=false}

            {foreach from=$dealer.fields item='item' key='field'}
                {if $item.Key == 'First_name' || $item.Key == 'Last_name' || $item.Key == 'company_name'}{continue}{/if}

                {if !empty($item.value) && $item.Details_page}
                    {if $item.Key|strpos:'phone' || $item.Type == 'phone'}
                        {assign var='phone' value=$item.value}
                        {assign var='hiddenPhone' value=$item.Hidden}
                    {else}
                        {assign var='inline_fields' value=$inline_fields|cat:', '|cat:$item.value}
                        <span>{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out_value.tpl'}</span>
                    {/if}
                {/if}
            {/foreach}

            {rlHook name='accountAfterFields'}
        </li>

        {if $phone}
            <li class="tel">
                {if !$hiddenPhone}<a dir="ltr" href="{if $allow_contacts}tel:{$phone}{else}javascript://{/if}">{/if}
                {$phone}
                {if !$hiddenPhone}</a>{/if}
            </li>
        {/if}
    </ul>
</article>

<!-- account item end -->
