<!-- listing details header -->

<div class="row listing-header">
    <h1 class="col-md-10">{$pageInfo.name}</h1>
    <div class="col-md-2">
        <div class="icons">{strip}
            {if $listing_data.Account_ID == $account_info.ID}
                <a class="button low" href="{$rlBase}{if $config.mod_rewrite}{$pages.edit_listing}.html?id={$listing_data.ID}{else}?page={$pages.edit_listing}&id={$listing_data.ID}{/if}">{$lang.edit_listing}</a>
            {else}
                {rlHook name='listingDetailsNavIcons'}

                <a rel="nofollow" target="_blank" href="{$rlBase}{if $config.mod_rewrite}{$pages.print}.html?item=listing&id={$listing_data.ID}{else}?page={$pages.print}&item=listing&id={$listing_data.ID}{/if}" title="{$lang.print_page}" class="print"><span></span></a>
                <span id="fav_{$listing_data.ID}" class="favorite add" title="{$lang.add_to_favorites}"><span class="icon"></span></span>
            {/if}
        {/strip}</div>
    </div>
</div>

<!-- listing details header end -->
