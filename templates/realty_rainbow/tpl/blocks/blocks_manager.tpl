{if $block.Side == 'left' || $block.Side == 'right' || $side == 'sidebar'}
	{assign var='style' value='side'}
{else}
	{assign var='style' value='content'}
{/if}

{assign var='block_class' value=false}
{if $block.Key|strpos:'ltcategories_' === 0}
	{assign var='block_class' value='categories-box stick'}
{elseif $block.Key|strpos:'ltsb_' === 0 || $block.Key|strpos:'ltma_' === 0}
	{assign var='block_class' value='side_block_search light-inputs stick'}
{elseif $block.Content|strpos:'search' != false && $block.Content|strpos:'key="' != false}
	{assign var='block_class' value='side_block_search light-inputs stick'}
{elseif $block.Key == 'account_alphabetic_filter'}
	{assign var='block_class' value='stick'}
{elseif $block.Key == 'account_search'}
	{assign var='block_class' value='side_block_search stick'}
{elseif $block.Key == 'account_page_info'}
	{assign var='block_class' value='account-info seller-short stick'}
{elseif $block.Key == 'account_page_location'}
	{assign var='block_class' value='account-location stick'}
{elseif $block.Key == 'my_profile_sidebar' || ($block.Key == 'search_by_distance' && $pageInfo.Key == 'search_by_distance')}
	{assign var='block_class' value='stick'}
{elseif $block.Key|strpos:'categoryFilter_' === 0}
	{assign var='block_class' value='stick'}
{elseif $block.Key == 'get_more_details'}
	{assign var='block_class' value='highlighted'}
{/if}

{if $block.Plugin && $block.Plugin != 'responsive_42'}
	{assign var='block_class' value=$block_class|cat:' '|cat:$block.Plugin}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:$style|cat:'_block_header.tpl' title=$block.name no_padding=$no_padding block_class=$block_class}

{if $block.Type == 'html'}
	{$block.Content}
{elseif $block.Type == 'smarty'}
	{insert name="eval" content=$block.Content}
{elseif $block.Type == 'php'}
	{php}
		eval($this->_tpl_vars['block']['Content']);
	{/php}
{/if}

{php}
    $key = str_replace('ltfb_', '', $this->_tpl_vars['block']['Key']);
    $var = 'featured_' . $key;

    $this->assign('featured_listings_count', $this->_tpl_vars[$var] ? count($this->_tpl_vars[$var]) : 0);
{/php}

{if $pageInfo.Key == 'home' && (($block.Key|@strpos:'ltfb_' === 0 && $featured_listings_count >= $config.featured_per_page) || ($block.Plugin == 'listings_box' && $listings_box && $listings_box|@count > $config.featured_per_page))}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'load-more-button.tpl'}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:$style|cat:'_block_footer.tpl'}
