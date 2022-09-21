<!-- footer data tpl -->

<div class="footer-data row mt-4">
    <div class="icons text-left col-12 col-sm-auto col-lg-3 order-2 mt-3 mt-sm-0">
        <a class="facebook" target="_blank" title="{$lang.join_us_on_facebook}" href="{$config.facebook_page}"></a>
        <a class="twitter ml-4" target="_blank" title="{$lang.join_us_on_twitter}" href="{$config.twitter_page}"></a>
        {if $pages.rss_feed}
            <a class="rss ml-4" title="{$lang.subscribe_rss}" href="{getRssUrl mode='footer'}" target="_blank"></a>
        {/if}
    </div>

    <div class="align-self-center col-12 mt-4 mt-sm-0 col-sm">
        &copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by}
        <a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
    </div>
</div>

<!-- footer data tpl end -->
