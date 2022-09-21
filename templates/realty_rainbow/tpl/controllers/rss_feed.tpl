<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<image>
    <url>{$rlTplBase}img/logo.png</url>
    <title>{$site_name|replace:'&':'&amp;'}</title>
    <link>{$smarty.const.RL_URL_HOME}</link>
</image>

<title>{$rss.title}</title>
<description>{$rss.description}</description>
<link>{$rss.back_link}</link>
<atom:link href="{$smarty.server.SCRIPT_URI}" rel="self" type="application/rss+xml" />

{if $rss_item == 'account-listings'}
    {foreach from=$listings item='listing'}
        {if $listing.Listing_type}
            {assign var='listing_type' value=$listing_types[$listing.Listing_type]}
        {/if}
        
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'rss_listing.tpl'}
    {/foreach}
{elseif $rss_item == 'category'}
    {foreach from=$listings item='listing'}
        {if $listing.Listing_type}
            {assign var='listing_type' value=$listing_types[$listing.Listing_type]}
        {/if}
        
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'rss_listing.tpl'}
    {/foreach}
{elseif $rss_item == 'news'}
    {foreach from=$news item='news_item'}
        {assign var="news_item_url" value=$rlBase}
        {if $config.mod_rewrite}
            {assign var="news_item_url" value=$news_item_url|cat:$pages.news|cat:'/'|cat:$news_item.Path|cat:'.html'}
        {else}
            {assign var="news_item_url" value=$news_item_url|cat:'?page='|cat:$pages.news|cat:'&id='|cat:$news_item.ID}
        {/if}

            {assign var="pubdate" value=$news_item.Date|strtotime}
            {assign var="pubdate" value='r'|date:$pubdate}

        <item>
            <title>{$news_item.title|replace:'&':'&amp;'}</title>
            <pubDate>{$pubdate}</pubDate>
            <link>{$news_item_url}</link>
            <guid>{$news_item_url}</guid>
            <description><![CDATA[{$news_item.content}]]></description>
            {$tplRssNewsItem}
        </item>
    {/foreach}
{else}
    {foreach from=$listings item='listing'}
        {if $listing.Listing_type}
            {assign var='listing_type' value=$listing_types[$listing.Listing_type]}
        {/if}
        
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'rss_listing.tpl'}
    {/foreach}
{/if}

{rlHook name='tplRssFeedBottom'}

</channel>
</rss>
