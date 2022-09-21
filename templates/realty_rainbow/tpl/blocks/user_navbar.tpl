<!-- user navigation bar -->

<span class="d-none d-md-flex circle{if $isLogin} logged-in{/if}{if $new_messages} notify{/if}" id="user-navbar">
    <span class="default">
        <svg viewBox="0 0 22 22" class="header-usernav-icon-fill">
            <use xlink:href="#user-icon"></use>
        </svg>
    </span>
    <span class="content {if $isLogin}a-menu{/if} hide">
        {if $isLogin}
            <div class="account-name">{$lang.greeting}{$isLogin}</div>
            {include file='menus/account_menu.tpl' removeAccountMenuWelcomeContainer=true}
        {else}
            <span class="user-navbar-container">
                {include file='blocks/login_modal.tpl'}
            </span>
        {/if}
    </span>
</span>

<span class="circle" id="theme-switcher">
    <span class="default">
        <svg viewBox="0 0 22 22">
            {if $smarty.cookies.colorTheme}
            <use xlink:href="#theme-{if $smarty.cookies.colorTheme == 'dark'}sun{else}moon{/if}-icon"></use>
            {else}
            <use id="theme-switcher-sun" xlink:href="#theme-sun-icon"></use>
            <use id="theme-switcher-moon" xlink:href="#theme-moon-icon"></use>
            {/if}
        </svg>
    </span>
</span>

<!-- user navigation bar end -->
