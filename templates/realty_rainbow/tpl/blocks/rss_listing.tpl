{assign var="pubdate" value=$listing.Date|strtotime}
{assign var="pubdate" value='r'|date:$pubdate}

<item>
    <title>{$listing.listing_title|replace:'&':'&amp;'}</title>
    <link>{$listing.url}</link>
    <guid>{$listing.url}</guid>
    <pubDate>{$pubdate}</pubDate>
        
    <category>{$listing.name|replace:'&':'&amp;'}</category>
    <description><![CDATA[
    <table cellpadding="0" cellspacing="0">
    <tr>
        {if !empty($listing.Main_photo)}
        <td valign="top">
            <a title="{$listing.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">
                <img alt="{$listing.listing_title}" src="{$smarty.const.RL_FILES_URL}{$listing.Main_photo}" />
            </a>
        </td>
        <td width="10px">&nbsp;</td>
        {else}
        <td colspan="2"></td>
        {/if}
        <td valign="top">
            <table cellpadding="0" cellspacing="0">
            {foreach from=$listing.fields item='item' key='field' name='fListings'}
            {if !empty($item.value) && $item.Details_page}
            <tr>
                <td>{$item.name}:</td>
                <td width="10px"></td>
                <td>
                    {if $f_first}
                        <a title="{$item.value}" {if $config.view_details_new_window}target="_blank"{/if} href="{$listing.url}">{$item.value}</a>
                    {else}
                        {$item.value}
                    {/if}
                </td>
            </tr>
            {assign var='f_first' value=false}
            {/if}
            {/foreach}
            <tr>
                <td>{$lang.category}:</td>
                <td></td>
                <td><a title="{$lang.category}: {$listing.name|regex_replace:"/[^A-Za-z0-9\-]/":' '}" class="cat_caption" href="{$rlBase}{if $config.mod_rewrite}{$pages[$listing_type.Page_key]}/{$listing.Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages[$listing_type.Page_key]}&amp;category={$listing.Category_ID}{/if}">{$listing.name}</a></td>
            </tr>
            
            {rlHook name='xmlListingsAfterFields'}
            
            </table>
        </td>
    </table>
    ]]></description>
</item>
