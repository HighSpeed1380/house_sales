{if $pageInfo.Controller == 'listing_details'}
    {if $pageInfo.meta_title}
        {assign var="smd_page_title" value=$pageInfo.meta_title|escape:'html'}
    {elseif $pageInfo.name}
        {assign var="smd_page_title" value=$pageInfo.name|escape:'html'}
    {elseif $pageInfo.title}
        {assign var="smd_page_title" value=$pageInfo.title|escape:'html'}
    {/if}
{elseif $pageInfo.Controller == 'news' && ($article || $smarty.get.nvar_1)}
    {if $pageInfo.title}
        {assign var="smd_page_title" value=$pageInfo.title|escape:'html'}
    {else}
        {assign var="smd_page_title" value=$pageInfo.name|escape:'html'}
    {/if}
{elseif $pageInfo.Controller && $category.ID && ($category.title || $category.name)}
    {if $category.title}
        {assign var="smd_page_title" value=$category.title|escape:'html'}
    {else}
        {assign var="smd_page_title" value=$category.name|escape:'html'}
    {/if}
{else}
    {if $pageInfo.title}
        {assign var="smd_page_title" value=$pageInfo.title|escape:'html'}
    {elseif $pageInfo.name}
        {assign var="smd_page_title" value=$pageInfo.name|escape:'html'}
    {/if}
{/if}

<!-- Twitter Card data -->
<meta name="twitter:card" content="{if $pageInfo.Controller == 'listing_details'}product{else}summary{/if}">
<meta name="twitter:title" content="{$smd_page_title}">
{if $pageInfo.meta_description}
<meta name="twitter:description" content="{$pageInfo.meta_description|strip_tags|escape:'html'}">
{/if}
{if $config.smd_twitter_name}
<meta name="twitter:site" content="{$config.smd_twitter_name|escape:'html'}">
{/if}
{if is_array($photos) && $photos|@count > 1}
{foreach from=$photos item='photo' name="listingPhotos"}
{if ($photo.Type == 'photo' || $photo.Type == 'picture' || $photo.Type == 'main') && $photo.Photo}
{if $allow_photos || $smarty.foreach.listingPhotos.first}
<meta name="twitter:image" content="{$photo.Photo}">
{/if}
{/if}
{/foreach}
{else}
{if $smd_logo}
<meta name="twitter:image" content="{$smd_logo}">
{/if}
{/if}
{if $pageInfo.Controller == 'listing_details'}
{if $smd_price}
<meta name="twitter:data1" content="{$smd_price.currency}{$smd_price.value}">
<meta name="twitter:label1" content="Price">
{/if}
{if $smd_second_field}
<meta name="twitter:data2" content="{$smd_second_field.value|escape:'html'}">
<meta name="twitter:label2" content="{$smd_second_field.key}">
{/if}
{/if}

<!-- Open Graph data -->
<meta property="og:title" content="{$smd_page_title}" />
<meta property="og:type" content="{if $pageInfo.Controller == 'listing_details'}product{else}website{/if}" />
{if $pageInfo.meta_description}
<meta property="og:description" content="{$pageInfo.meta_description|strip_tags|escape:'html'}" />
{/if}
<meta property="og:url" content="http{if $smarty.server.HTTPS == 'on'}s{/if}://{$smarty.server.HTTP_HOST}{if $smarty.server.REQUEST_URI != "/"}{$smarty.server.REQUEST_URI}{/if}" />
{if is_array($photos) && $photos|@count > 1}
{foreach from=$photos item='photo' name="listingPhotos"}
{if ($photo.Type == 'photo' || $photo.Type == 'picture' || $photo.Type == 'main') && $photo.Photo}
{if $allow_photos || $smarty.foreach.listingPhotos.first}
<meta property="og:image" content="{$photo.Photo}" />
{if $smarty.foreach.listingPhotos.iteration == 1 && $smd_logo_properties}
<meta property="og:image:type" content="{$smd_logo_properties.mime}" />
<meta property="og:image:width" content="{$smd_logo_properties.width}" />
<meta property="og:image:height" content="{$smd_logo_properties.height}" />
{/if}
{/if}
{/if}
{/foreach}
{else}
{if $smd_logo}
<meta property="og:image" content="{$smd_logo}" />
{if $smd_logo_properties}
<meta property="og:image:type" content="{$smd_logo_properties.mime}" />
<meta property="og:image:width" content="{$smd_logo_properties.width}" />
<meta property="og:image:height" content="{$smd_logo_properties.height}" />
{/if}
{/if}
{/if}
{if $config.site_name}
<meta property="og:site_name" content="{$config.site_name|escape:'html'}" />
{/if}
{if $pageInfo.Controller == 'listing_details' && $smd_price && $curConv_rates[$smd_price.currency_code].Code}
<meta property="og:price:amount" content="{$smd_price.og_value}" />
<meta property="og:price:currency" content="{$curConv_rates[$smd_price.currency_code].Code}" />
{/if}
{if $config.smd_fb_admins}
<meta property="fb:admins" content="{$config.smd_fb_admins}" />
{/if}
{if $config.smd_fb_appid}
<meta property="fb:app_id" content="{$config.smd_fb_appid}" />
{/if}
