<!-- listing type -->

{rlHook name='browseTop'}

<!-- search results -->
{if $search_results}
    {if !empty($listings)}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' hl=true grid_photo=$listing_type.Photo}
        <script type="text/javascript">flynaxTpl.highlightResults($('#autocomplete').val());</script>

        <!-- paging block -->
        {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$search_results_url method=$listing_type.Submit_method}
        <!-- paging block end -->
    {else}
        <div class="text-notice">
            {if $listing_type.Admin_only}
                {$lang.no_listings_found_deny_posting}
            {else}
                {assign var='link' value='<a href="'|cat:$add_listing_link|cat:'">$1</a>'}
                {$lang.no_listings_found|regex_replace:'/\[(.+)\]/':$link}
                <br />
                {if !$smarty.cookies.checkAlert}
                    {assign var='link' value='<span name="alter-save-search" class="'|cat:$listing_type.Key|cat:'"><span class="link">$1</span></span>'}
                    {$lang.save_search_text|regex_replace:'/\[(.+)\]/':$link}
                {/if}
            {/if}
        </div>
    {/if}

    <script class="fl-js-dynamic">
    {literal}
    $(function () {
        /**
         * Save search link handler
         *
         * @since 4.8.2 - Function moved from xAjax to ajax && Function moved from system.lib.js
         */
        flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
        flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
            $('span#save_search, span[name=alter-save-search]').click(function() {
                var type = $(this).attr('class');

                if (!type) {
                    console.log('Error: Missing required key of listing type.');
                    return;
                }

                if (isLogin) {
                    $(this).popup({
                        click     : false,
                        content   : lang.save_search_confirm,
                        caption   : lang.notice,
                        navigation: {
                            okButton: {
                                text: lang.save,
                                onClick: function(popup){
                                    var $button = $(this), data;
                                    $button.addClass('disabled').attr('disabled', true).val(lang.loading);

                                    data = {mode: 'ajaxSaveSearch', type: type};
                                    flUtil.ajax(data, function(response, status) {
                                        if (status === 'success' && response && response.status === 'OK') {
                                            printMessage('notice', response.message);
                                        } else {
                                            $button.removeClass().removeAttr('disabled').val(lang.save);
                                            printMessage(
                                                'error',
                                                response.message ? response.message : lang.system_error
                                            );
                                        }

                                        popup.close();
                                    });
                                }
                            },
                            cancelButton: {text: lang.cancel, class: 'cancel'}
                        }
                    });
                } else {
                    let $loginForm = $('#login_modal_source > *').clone();
                    $loginForm.find('.caption_padding').hide();

                    $(this).popup({
                        click  : false,
                        content: $loginForm,
                        caption: '{/literal}{$lang.sign_in}{literal}',
                        width  : 320,
                        onShow : function ($popup) {
                            $popup.find('form').prepend(
                                $('<input>', {type: 'hidden', name: 'alert_type', value: type})
                            );

                            // Prevent closing the popup by click on label with checkbox
                            if ($popup.find('.remember-me')) {
                                $popup.find('input#css_INPUT_1').attr('id', 'css_INPUT_99999');
                                $popup.find('label[for="css_INPUT_1"]').attr('for', 'css_INPUT_99999');
                            }
                        }
                    });
                }
            });
        });
    });
    {/literal}
    </script>
    <!-- search results end -->
{else}
    <!-- browse/search forms mode -->

    {if $advanced_search}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'advanced_search.tpl'}

    {else}

        {if !empty($category.des)}
            <div class="category-description">
                {$category.des}
            </div>
        {/if}

        {if !empty($listings)}

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' grid_photo=$listing_type.Photo}

            <!-- paging block -->
            {if $config.mod_rewrite}
                {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$category.Path}
            {else}
                {paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url='category='|cat:$category.ID}
            {/if}
            <!-- paging block end -->

        {else}
            {if $category.Lock}
                {assign var='br_count' value=$bread_crumbs|@count}
                {assign var='br_count' value=$br_count-2}

                {if $config.mod_rewrite}
                    {assign var='lock_link' value=$rlBase|cat:$bread_crumbs.$br_count.path}
                    {if $listing_type.Cat_postfix}
                        {assign var='lock_link' value=$lock_link|cat:'.html'}
                    {else}
                        {assign var='lock_link' value=$lock_link|cat:'/'}
                    {/if}
                {else}
                    {assign var='lock_link' value=$rlBase|cat:'?page='|cat:$bread_crumbs.$br_count.path}
                {/if}
                {assign var='replace_name' value=`$smarty.ldelim`name`$smarty.rdelim`}
                {assign var='replace' value='<a title="'|cat:$lang.back_to_category|replace:$replace_name:$bread_crumbs.$br_count.name|cat:'" href="'|cat:$lock_link|cat:'">$1</a>'}
                <div class="text-notice">{$lang.browse_category_locked|regex_replace:'/\[(.+)\]/':$replace}</div>
            {else}
                <div class="text-notice">
                    {if $listing_type.Admin_only}
                        {$lang.no_listings_here_submit_deny}
                    {else}
                        {assign var='link' value='<a href="'|cat:$add_listing_link|cat:'">$1</a>'}
                        {$lang.no_listings_here|regex_replace:'/\[(.+)\]/':$link}
                    {/if}
                </div>
            {/if}
        {/if}

    {/if}

{/if}
<!-- browse mode -->

<script class="fl-js-dynamic">
var is_advanced_search = {if $advanced_search}true{else}false{/if};
{literal}

flUtil.loadScript(rlConfig['tpl_base'] + 'js/form.js', function(){
    if (is_advanced_search) {
        flForm.realtyPropType();
    } else {
        flForm.realtyPropType(
            'div.search-item span.custom-input input[name="f[sale_rent]"]',
            'div.search-item span.custom-input input[name="f[time_frame]"]',
            '.search-item'
        );
    }
});

{/literal}
</script>

{rlHook name='browseBottom'}

<!-- listing type end -->
