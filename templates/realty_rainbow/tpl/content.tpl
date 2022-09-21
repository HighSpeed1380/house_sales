<!-- page content -->

{assign var='featured_gallary' value=false}

<div id="wrapper" class="flex-fill w-100">
    <section id="main_container">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'bread_crumbs.tpl'}

        {if $pageInfo.Key != 'search_on_map' && $config.header_banner_space}
            <div class="header-banner-cont w-100 h-100 mx-auto {if !$bread_crumbs_exists}pt-5{else}pb-5{/if} d-flex justify-content-center">
                <div id="header-banner" class="point1 mx-auto overflow-hidden">
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'header_banner.tpl'}
                </div>
            </div>
        {/if}

        <div class="inside-container point1 clearfix pt-5{if $pageInfo.Key != 'home'} pb-5{/if}">
            {if $pageInfo.Key == 'home' && $config.home_page_h1}
                <h1 class="text-center">{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
            {/if}

            {if $pageInfo.Controller == 'listing_details'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing-details-header.tpl'}
            {/if}

            <div class="row">
                <!-- left blocks area on home page -->
                {if $side_bar_exists && ($blocks.left || $pageInfo.Controller == 'listing_details')}
                    <aside class="left {if $pageInfo.Controller == 'listing_details'}order-2 col-lg-4{else}col-lg-3{/if}">
                        {strip}
                        {if $pageInfo.Controller == 'listing_details'}{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_sidebar.tpl'}{/if}

                        {foreach from=$blocks item='block'}
                        {if $block.Side == 'left'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                        {/if}
                        {/foreach}
                        {/strip}
                    </aside>
                {/if}
                <!-- left blocks area end -->

                <section id="content" class="{if $side_bar_exists}{if $pageInfo.Controller == 'listing_details'}order-1 col-lg-8{else}col-lg-9{/if}{else}col-lg-12{/if}">
                    {if $pageInfo.Key != 'home' && $pageInfo.Key != 'search_on_map' && $pageInfo.Controller != 'listing_details' && !$no_h1}
                        {if $navIcons}
                            <div class="h1-nav">
                                <nav id="content_nav_icons">
                                    {rlHook name='pageNavIcons'}

                                    {if !empty($navIcons)}
                                        {foreach from=$navIcons item='icon'}
                                            {$icon}
                                        {/foreach}
                                    {/if}
                                </nav>
                        {/if}

                        {if ($pageInfo.Controller == 'home' && $config.home_page_h1) || $pageInfo.Controller != 'home'}
                            <h1{if ($pageInfo.Key == 'login' || $pageInfo.Login) && !$isLogin} class="text-center"{/if}>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
                        {/if}

                        {if $navIcons}
                            </div>
                        {/if}
                    {/if}

                    <div id="system_message">
                        {if $errors || $pNotice || $pAlert}
                            <script class="fl-js-dynamic">
                                var fixed_message = {if $fixed_message}false{else}true{/if};
                                var message_text = '', error_fields = '';
                                var message_type = 'error';
                                {if isset($errors)}
                                    error_fields = {if $error_fields}'{$error_fields|escape:"javascript"}'{else}false{/if};
                                    message_text += '<ul>';
                                    {foreach from=$errors item='error'}message_text += '<li>{$error|regex_replace:"/[\r\t\n]/":"<br />"|escape:"javascript"}</li>';{/foreach}
                                    message_text += '</ul>';
                                {/if}
                                {if isset($pNotice)}
                                    message_text = '{$pNotice|escape:"javascript"}';
                                    message_type = 'notice';
                                {/if}
                                {if isset($pAlert)}
                                    var message_text = '{$pAlert|escape:"javascript"}';
                                    message_type = 'warning';
                                {/if}
                                {literal}
                                $(document).ready(function(){
                                    if (message_text) {
                                        printMessage(message_type, message_text, error_fields, fixed_message);
                                    }
                                });
                            {/literal}</script>
                        {/if}

                        <!-- no javascript mode -->
                        {if !$smarty.const.IS_BOT}
                        <noscript>
                        <div class="warning">
                            <div class="inner">
                                <div class="icon"></div>
                                <div class="message">{$lang.no_javascript_warning}</div>
                            </div>
                        </div>
                        </noscript>
                        {/if}
                        <!-- no javascript mode end -->
                    </div>

                    {if $pageInfo.Key != 'search_on_map'}
                        {if $blocks.top}
                        <!-- top blocks area -->
                        <aside class="top">
                            {foreach from=$blocks item='block'}
                            {if $block.Side == 'top'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                            {/foreach}
                        <!-- top blocks area end -->
                        </aside>
                        {/if}
                    {/if}

                    <section id="controller_area">{strip}
                        {if $pageInfo.Page_type == 'system'}
                            {include file=$content}
                        {else}
                            <div class="content-padding">{$staticContent}</div>
                        {/if}
                    {/strip}</section>

                    {if $pageInfo.Key != 'search_on_map'}
                        <!-- middle blocks area -->
                        {if $blocks.middle}
                        <aside class="middle">
                            {foreach from=$blocks item='block'}
                                {if $block.Side == 'middle'}
                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                {/if}
                            {/foreach}
                        </aside>
                        {/if}
                        <!-- middle blocks area end -->

                        {if $blocks.middle_left || $blocks.middle_right}
                        <!-- middle blocks area -->
                        <aside class="row two-middle">
                            <div class="col-md-6 col-sm-12">
                                <div>
                                    {foreach from=$blocks item='block'}
                                    {if $block.Side == 'middle_left'}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                    {/if}
                                    {/foreach}
                                </div>
                            </div>

                            <div class="col-md-6 col-sm-12">
                                <div>
                                    {foreach from=$blocks item='block'}
                                    {if $block.Side == 'middle_right'}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                    {/if}
                                    {/foreach}
                                </div>
                            </div>
                        </aside>
                        <!-- middle blocks area end -->
                        {/if}

                        {if $blocks.bottom}
                        <!-- bottom blocks area -->
                        <aside class="bottom">
                            {foreach from=$blocks item='block'}
                            {if $block.Side == 'bottom'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                            {/foreach}
                        </aside>
                        <!-- bottom blocks area end -->
                        {/if}
                    {/if}
                </section>
            </div>
        </div>
    </section>
</div>

{if $plugins.massmailer_newsletter && $pageInfo.Controller != 'search_map'}
    <div class="hide" id="tmp-newsletter">{include file=$smarty.const.RL_PLUGINS|cat:'massmailer_newsletter'|cat:$smarty.const.RL_DS|cat:'block.tpl'}</div>
{/if}

<!-- page content end -->
