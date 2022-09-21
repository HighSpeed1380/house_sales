<!-- done step -->

<div class="text-notice">
    {if $config.listing_auto_approval}
        {assign var='done_phrase_key' value='notice_after_listing_adding_auto'}
    {else}
        {assign var='done_phrase_key' value='notice_after_listing_adding'}
    {/if}

    {phrase key=$done_phrase_key}
</div>

{if !$addMoreListing}
    {assign var='addMoreListing' value=$pageInfo.Key}
{/if}
{pageUrl assign='return_link' key=$addMoreListing}
{assign var='replace' value='<a href="'|cat:$return_link|cat:'">$1</a>'}
{$lang.add_one_more_listing|regex_replace:'/\[(.*)\]/':$replace}

<!-- done step end -->