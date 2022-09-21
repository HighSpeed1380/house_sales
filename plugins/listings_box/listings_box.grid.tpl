{strip}
{php}
$block = $this->get_template_vars('block');
$side_bar_exists = $this->get_template_vars('side_bar_exists');
$flatty = (bool) preg_match('/_flatty$/', $GLOBALS['tpl_settings']['name']);
$class = 'col-md-3 col-sm-4';

if (in_array($block['Side'], array('middle_left', 'middle_right'))) {
    $class = $flatty ? ($block['Tpl'] ? 'col-sm-12 ' : 'col-sm-6 ') : 'col-sm-4 ';
    $lg =  $block['Tpl'] || $flatty ? '' : ' col-lg-6';
    $class .= $side_bar_exists ? 'col-md-12' . $lg : 'col-md-6';
} else if (in_array($block['Side'], array('left', 'right'))) {
    $class = 'col-sm-4 col-md-12';
} else {
    $class = 'col-sm-4 ';
    $lg =  $block['Tpl'] || $flatty ? '' : ' col-lg-2';
    $class .= $side_bar_exists ? '' : 'col-md-3' . $lg;
}

$this->assign('box_grid_item_class', $class);
{/php}

{assign var='type' value=$featured_listing.Listing_type}

<li class="item clearfix {$box_grid_item_class}{if !$featured_listing.Main_photo} no-picture{/if}">
    {if $listing_types.$type.Photo}
        <div class="photo picture{if !$featured_listing.Main_photo} no-picture{/if}">
            <a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}" title="{$featured_listing.listing_title}">
                {if $featured_listing.Main_photo}
                    {if false !== $featured_listing.Main_photo|strpos:$rlTplBase}
                        {assign var='main_photo' value=$featured_listing.Main_photo}
                    {else}
                        {assign var='main_photo' value=$smarty.const.RL_FILES_URL|cat:$featured_listing.Main_photo}
                    {/if}
                {else}
                    {assign var='main_photo' value=$rlTplBase|cat:'img/blank_10x7.gif'}
                {/if}
                <img alt="{$featured_listing.listing_title}" src="{$main_photo}"
                 {if $featured_listing.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$featured_listing.Main_photo_x2} 2x"{/if}
                />
            </a>
        </div>
    {/if}

    {assign var='available_field' value=1}
    <ul>
        <li class="title">
            <a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_listing.url}" title="{$featured_listing.listing_title}">
                {$featured_listing.listing_title}
            </a>
        </li>
        {if $featured_listing.fields[$config.price_tag_field].value}
            <li>
                <span class="price-tag">{$featured_listing.fields[$config.price_tag_field].value}</span>
                {if $featured_listing.sale_rent == 2 && $featured_listing.fields.time_frame.value}
                    <span> / {$featured_listing.fields.time_frame.value}</span>
                {/if}
            </li>
        {/if}
    </ul>
</li>{/strip}
