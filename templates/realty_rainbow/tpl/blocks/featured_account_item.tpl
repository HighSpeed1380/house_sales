{strip}{php}
global $page_info;

$block = $this -> get_template_vars('block');
$side_bar_exists = $this -> get_template_vars('side_bar_exists');
$class = 'col-md-3 col-sm-4';

if ( in_array($block['Side'], array('middle', 'bottom', 'top'))) {
    $class = $side_bar_exists ? 'col-sm-4' : 'col-md-3 col-sm-4';
} elseif (in_array($block['Side'], array('middle_left', 'middle_right'))) {
    $class = 'col-md-12 col-sm-4';
}

$this -> assign('box_item_class', $class);
{/php}

<li {if $featured_account.ID}id="fla_{$featured_account.ID}"{/if} class="{$box_item_class}{if !$featured_account.Photo} no-picture{/if}">
    <div class="picture">
	{if $account_types.$type.Page}
        <a title="{$featured_account.Full_name}" {if $config.featured_new_window}target="_blank"{/if} href="{$featured_account.Personal_address}">
    {/if}
            <img alt="{$featured_account.Full_name}"
                 src="{strip}
                    {if $featured_account.Photo}
                        {if !$featured_account.custom}
                            {$smarty.const.RL_FILES_URL}
                        {/if}
                        {$featured_account.Photo}
                    {else}
                        {$rlTplBase}img/blank_10x7.gif
                    {/if}
                 {/strip}"
                 {if $featured_account.Photo_x2}
                 srcset="{strip}
                    {if !$featured_account.custom}
                        {$smarty.const.RL_FILES_URL}
                    {/if}
                    {$featured_account.Photo_x2} 2x
                 {/strip}"
                 {/if} />

	{if $account_types.$type.Page}
        </a>
    {/if}
    </div>

	<ul class="ad-info">
		<li class="title" title="{$featured_account.Full_name}">
			{if $account_types.$type.Page}<a {if $config.featured_new_window}target="_blank"{/if} href="{$featured_account.Personal_address}">{/if}
				{$featured_account.Full_name}
			{if $account_types.$type.Page}</a>{/if}
		</li>
		{if $featured_account.Listings_count}<li class="fields">{$featured_account.Listings_count} {$lang.listings}</li>{/if}
	</ul>
</li>{/strip}
