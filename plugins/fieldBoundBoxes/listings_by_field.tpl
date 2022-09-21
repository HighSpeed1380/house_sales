<!-- field bound boxes, listing_by_field.tpl -->

{if !empty($listings)}
    {if !empty($description)}
        <p class="category-description">
            {$description}
        </p>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' hl=true}
        
    {paging calc=$pInfo.calc 
        total=$listings|@count 
        current=$pInfo.current
        per_page=$config.listings_per_page
        method=$listing_type.Submit_method
        url=$item_path}
        
{elseif $fbb_options}
    {include file=$smarty.const.RL_PLUGINS|cat:'fieldBoundBoxes/field-bound_box.tpl' pageMode=true}
{else}
    <div class="info">
        {if $listing_type.Admin_only}
            {$lang.no_listings_found_deny_posting}
        {else}
            {assign var='link' value='<a href="'|cat:$add_listing_link|cat:'">$1</a>'}
            {$lang.no_listings_found|regex_replace:'/\[(.+)\]/':$link}
        {/if}
    </div>
{/if}

<!-- field bound boxes, listing_by_field.tpl end -->
