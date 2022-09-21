<!-- footer menu block -->

{assign var='footer_menu' value=$footer_menu|@array_values}
{math equation='ceil(total/3)' total=$footer_menu|@count assign='per_column'}

{section name='menu_column' loop=3 max=3}
    <ul class="col-sm-4 col-lg-3 mb-4">
    	<li class="footer-menu-title">{phrase key='footer_menu_'|cat:$smarty.section.menu_column.iteration}</li>
        {math assign='start' equation='(iter-1)*per_column' iter=$smarty.section.menu_column.iteration per_column=$per_column}
        {section name='item' loop=$footer_menu start=$start max=$per_column}
            {assign var='index' value=$smarty.section.item.index}
            {assign var='footerMenu' value=$footer_menu.$index}
    	    <li>
                <a {if $page == $footerMenu.Path}class="active"{/if} {if $footerMenu.No_follow || $footerMenu.Login}rel="nofollow"{/if}title="{$footerMenu.title}" href="{if $footerMenu.Page_type != 'external'}{$rlBase}{/if}{if $pageInfo.Controller != 'add_listing' && $footerMenu.Controller == 'add_listing' && !empty($category.Path) && !$category.Lock}{if $config.mod_rewrite}{$footerMenu.Path}/{$category.Path}/{$steps.plan.path}.html{else}?page={$footerMenu.Path}&amp;step={$steps.plan.path}&amp;id={$category.ID}{/if}{else}{if $footerMenu.Page_type == 'external'}{$footerMenu.Controller}{else}{if $config.mod_rewrite}{if $footerMenu.Path != ''}{$footerMenu.Path}.html{$footerMenu.Get_vars}{/if}{else}{if $footerMenu.Path != ''}?page={$footerMenu.Path}{$footerMenu.Get_vars|replace:'?':'&amp;'}{/if}{/if}{/if}{/if}">
                    {$footerMenu.name}
                </a>
            </li>
        {/section}
    </ul>
{/section}

<!-- footer menu block end -->
