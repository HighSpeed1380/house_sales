<!-- main menu block -->

<div class="menu d-flex h-100 align-items-center flex-grow-0 flex-md-fill shrink-fix">
    <div class="d-none d-md-flex h-100 flex-fill shrink-fix">
        <span class="mobile-menu-header d-none align-items-center order-1">
            <span class="mobile-menu-header-title">{$lang.menu}</span>
            <div class="flex-fill d-flex mr-3 justify-content-center" id="mobile-left-usernav"></div>
            <svg viewBox="0 0 12 12" class="mobile-close-icon">
                <use xlink:href="#close-icon"></use>
            </svg>
        </span>

        <div class="menu-content pt-3 pb-3 pt-md-0 pb-md-0 order-3">
        {foreach name='mMenu' from=$main_menu item='mainMenu'}
            {if $mainMenu.Key == 'add_listing'}{assign var='add_listing_button' value=$mainMenu}{continue}{/if}

            <a title="{$mainMenu.title}"
               class="{if $pageInfo.Key == $mainMenu.Key} active{/if}"
               {if $mainMenu.No_follow || $mainMenu.Login}
               rel="nofollow"
               {/if}
               href="{strip}
               {if $mainMenu.Page_type == 'external'}
                   {$mainMenu.Controller}
               {else}
                    {pageUrl key=$mainMenu.Key vars=$mainMenu.Get_vars}
               {/if}
               {/strip}">{$mainMenu.name}</a>
        {/foreach}
        </div>

        {if $add_listing_button}
            <a class="button add-property order-2 flex-shrink-0 d-flex d-md-none"
                {if $mainMenu.No_follow || $mainMenu.Login}
                rel="nofollow"
                {/if}
                title="{$mainMenu.title}"
                href="{strip}
                    {if $pageInfo.Controller != 'add_listing'
                        && !empty($category.Path)
                        && !$category.Lock
                    }
                        {$rlBase}
                        {if $config.mod_rewrite}
                            {$add_listing_button.Path}/{$category.Path}/{$steps.plan.path}.html
                        {else}
                            ?page={$add_listing_button.Path}&step={$steps.plan.path}&id={$category.ID}
                        {/if}
                    {else}
                        {pageUrl key=$add_listing_button.Key}
                    {/if}
                {/strip}">
            {$add_listing_button.name}</a>
        {/if}

        <div class="menu-content order-4 d-block d-md-none pt-2 pb-2">
            <div class="content {if $isLogin}a-menu{/if}">
                {if $isLogin}
                    {include file='menus/account_menu.tpl'}
                {else}
                    <span class="user-navbar-container">
                        {include file='blocks/login_modal.tpl'}
                    </span>
                {/if}
            </div>
        </div>
    </div>
</div>

<span class="menu-button d-flex d-md-none align-items-center" title="{$lang.menu}">
    <svg viewBox="0 0 20 14">
        <use xlink:href="#mobile-menu"></use>
    </svg>
</span>

{*<ul id="main_menu_more"></ul>*}

<!-- main menu block end -->
