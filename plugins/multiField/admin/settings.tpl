<!-- Multifield custom settings tpl -->

<table class="hide">
<tr id="mf_filtering_settings">
    <td colspan="2">
        <input type="hidden" name="post_config[mf_geo_force][value]" value="1" />

        <table class="form">
        {assign var='new_group' value=false}

        {foreach from=$mf_available_pages item='page' name='pages'}

        {if $page.Controller|in_array:$mf_predefine_controllers && !$new_group}
            {assign var='new_group' value=true}

        <tr>
            <td class="divider_line" colspan="2">
                <div class="inner">{$lang.mf_geo_prefilling_group}</div>
            </td>
        </tr>
        {/if}

        <tr{if $smarty.foreach.pages.iteration%2 == 0} class="highlight"{/if}>
            <td class="name"{if $smarty.foreach.pages.first} style="width: 210px;"{/if}>
                {phrase key='pages+name+'|cat:$page.Key}
            </td>
            <td class="field">
                <div class="inner_margin" style="padding-top: 6px;">
                    <label>
                        <input type="radio"
                               name="mf_config[{$page.Key}][filtration]"
                               value="1"
                               {if $page.Key|in_array:$mf_filtering_pages}
                               checked="checked"
                               {/if} />
                        {$lang.enabled}
                    </label>

                    <label>
                        <input type="radio"
                               name="mf_config[{$page.Key}][filtration]"
                               value="0"
                               {if !$page.Key|in_array:$mf_filtering_pages}
                               checked="checked"
                               {/if} />
                        {$lang.disabled}
                    </label>

                    {if !$page.Controller|in_array:$mf_predefine_controllers && $config.mod_rewrite}
                        <label class="mf-opt-label{if !$page.Key|in_array:$mf_filtering_pages} mf-disabled{/if}">
                            <input type="checkbox"
                                   name="mf_config[{$page.Key}][url]"
                                   value="1"
                                   {if !$page.Key|in_array:$mf_filtering_pages}
                                   disabled="disabled"
                                   {/if}
                                   {if $page.Key|in_array:$mf_location_url_pages}
                                   checked="checked"
                                   {/if} />
                            {$lang.mf_apply_location_to_url}
                        </label>
                    {/if}
                </div>
            </td>
        </tr>
        {/foreach}
        </table>

        <p class="mf-hint"><i>{$lang.mf_preselect_data_hint}</i></p>
    </td>
</tr>
</table>

<script>
var mf_group_id   = {$mf_group_id};
var mf_geo_filter = {if $mf_geo_filter}true{else}false{/if};
lang['mf_no_geo_filtering_format'] = '{$lang.mf_no_geo_filtering_format}';
rlConfig['mf_allow_subdomain'] = {if $mf_allow_subdomain}true{else}false{/if};

{if $plugins.sitemap}
    rlConfig.mf_urls_in_sitemap        = {if $config.mf_urls_in_sitemap}true{else}false{/if};
    lang.mf_sitemap_dryrun_box_content = '{$lang.mf_sitemap_dryrun_box_content}';
    lang.mf_sitemap_rebuilding         = '{$lang.mf_sitemap_rebuilding}';
    lang.sm_xml_rebuilt                = '{$lang.sm_xml_rebuilt}';
    lang.sm_rebuild_notify_fail        = '{$lang.sm_rebuild_notify_fail}';
    lang.sm_dryrun_rebuild_fail        = '{$lang.sm_dryrun_rebuild_fail}';
    lang.sm_dryrun_rebuild_in_process  = '{$lang.sm_dryrun_rebuild_in_process}';
    rlConfig.isSitemapSupported        = {if $plugins.sitemap|version_compare:'3.0.3' > 0}true{else}false{/if};
{/if}
{literal}

$(function(){
    var $container = $('#mf_filtering_settings');

    $('#larea_' + mf_group_id + ' table.form tbody > tr:last').before($container);
    $container.removeClass('hide');

    $container.find('input[type=radio][name^=mf_config]').change(function(){
        var is_checked = parseInt($(this).filter(':checked').val());
        var $container = $(this).closest('div');

        $container.find('.mf-opt-label')[is_checked
            ? 'removeClass'
            : 'addClass'
        ]('mf-disabled');

        $container.find('.mf-opt-label input').attr('disabled', !is_checked);
    });

    // No geo filtering alert
    if (!readCookie('mf_no_geo_filtering_format') && !mf_geo_filter) {
        $('#ltab_' + mf_group_id).click(function(){
            setTimeout(function(){
                fail_alert('', lang['mf_no_geo_filtering_format']);
                createCookie('mf_no_geo_filtering_format', 1, 1);
            }, 1000);
        });
    }

    // Hide geo filtering related fields
    if (!mf_geo_filter) {
        $('input[name="post_config[mf_popular_locations_level][value]"]').closest('tr').remove();
    }
});

{/literal}

{if $allLangs|@count === 1}
{literal}

$(function(){
    var $inputs = $('input[name="post_config[mf_multilingual_path][value]"]');

    $inputs.filter('[value=0]').trigger('click');
    $inputs.attr('disabled', true);
});

{/literal}
{/if}

{literal}

$(function(){
    var $inputs = $('input[name="post_config[mf_geo_subdomains][value]"]');

    if (rlConfig['mf_allow_subdomain']) {
        $inputs.parent().find('.settings_desc').hide();
    } else {
        $inputs.attr('disabled', true);
    }
});

$(function(){
    // Location structure mode handler
    var $subdomain = $('input[name="post_config[mf_geo_subdomains][value]"]');
    var $linkType  = $('select[name="post_config[mf_geo_subdomains_type][value]"]');

    var changeHandler = function($val){
        var val = parseInt($val);
        $linkType.find('option[value=mixed]')[
            val ? 'show' : 'hide'
        ]();

        var $hints = $linkType.parent().find('.settings_desc > div > div');
        $hints.hide().filter(val ? ':first': ':last').show();

        if (!val && $linkType.val() == 'mixed') {
            $linkType.val('combined');
        }
    }

    $subdomain.change(function(){
        changeHandler($(this).val());
    });

    changeHandler($subdomain.filter(':checked').val());

    // Select location mode handler
    var $selectIterface = $('select[name="post_config[mf_select_interface][value]"]');
    var locationModeHandler = function(){
        $('select[name="post_config[mf_popular_locations_level][value]"]').closest('tr')[
            $selectIterface.val() == 'box' ? 'hide' : 'show'
        ]();
    }

    $selectIterface.change(function(){
        locationModeHandler();
    });

    locationModeHandler();
});

{/literal}{if $plugins.sitemap}{literal}
    var $addHomeToSitemap       = $('input[name="post_config[mf_home_in_sitemap][value]"]'),
        $urlsInSitemap          = $('select[name="post_config[mf_urls_in_sitemap][value]"]'),
        $urlsInSitemapContainer = $urlsInSitemap.closest('tr'),
        $locationsInListingUrls = $('input[name="post_config[mf_listing_geo_urls][value]"]'),
        $urlsWithLocation       = $('#mf_filtering_settings label.mf-opt-label input'),
        sitemapInProgress       = false;

    $(function(){
        addHomeToSitemapHandler($urlsInSitemap.val());

        if (!rlConfig.isSitemapSupported || !$urlsWithLocation.filter(':checked').length) {
            $urlsInSitemapContainer.hide();
        }

        $urlsWithLocation.change(function() {
            $urlsInSitemapContainer[$urlsWithLocation.filter(':checked').length ? 'show' : 'hide']();

            if ($(this).is(':checked') && $urlsWithLocation.filter(':checked').length) {
                dryrunSitemapHandler();
            }
        });

        $urlsInSitemap.change(function(){
            addHomeToSitemapHandler($(this).val());
            dryrunSitemapHandler();
        });

        $addHomeToSitemap.change(function(){
            if ($(this).val() === '1') {
                dryrunSitemapHandler();
            }
        });

        $locationsInListingUrls.change(function() {
            if ($(this).val() === '1') {
                dryrunSitemapHandler();
            }
        });

        $(window).bind('beforeunload', function(){
            if (sitemapInProgress) {
                return lang.sm_dryrun_rebuild_in_process;
            }
        });
    });

    var addHomeToSitemapHandler = function(typeLinksInSitemap){
        $addHomeToSitemap.closest('tr')[typeLinksInSitemap === 'not_empty' ? 'show' : 'hide']();
    };

    var dryrunSitemapHandler = function(){
        if (!$urlsInSitemap.val()) {
            return;
        }

        Ext.MessageBox.confirm(lang.confirm_notice, lang.mf_sitemap_dryrun_box_content, function(btn){
            if (btn === 'yes') {
                sitemapInProgress = true;

                var messageBox = Ext.MessageBox.show({
                    title: lang.mf_sitemap_rebuilding,
                    msg  : lang.loading,
                    width: 300,
                    wait : false
                });

                var mfLocationUrlPages = [];
                $urlsWithLocation.filter(':checked').each(function(){
                    mfLocationUrlPages.push($(this).attr('name').replace(/mf_config\[(.*)\]\[url\]/, '$1'));
                });

                $.post(
                    rlConfig['ajax_url'],
                    {
                        item                 : 'smRebuildFiles',
                        mf_urls_in_sitemap   : $urlsInSitemap.val(),
                        mf_home_in_sitemap   : $addHomeToSitemap.filter(':checked').val(),
                        mf_location_url_pages: mfLocationUrlPages.join(','),
                        mf_listing_geo_urls  : $locationsInListingUrls.filter(':checked').val(),
                    },
                    function(response) {
                        if (response && response.status) {
                        sitemapInProgress = false;
                            messageBox.hide();

                            if (response.status === 'OK') {
                                printMessage('notice', lang.sm_xml_rebuilt);
                            } else {
                                printMessage('error', lang.sm_rebuild_notify_fail);
                            }
                        }
                    },
                    'json'
                ).fail(function() {
                    sitemapInProgress = false;
                    $urlsInSitemap.val('');
                    addHomeToSitemapHandler();
                    messageBox.hide();
                    printMessage('error', lang.sm_dryrun_rebuild_fail);
                    $.post(rlConfig['ajax_url'], {item: 'smRestoreXmlFromBackup'}, function(){}, 'json');
                });
            } else {
                $urlsInSitemap.val('');
                addHomeToSitemapHandler();
            }
        });
    };
{/literal}{/if}

</script>

<!-- Multifield custom settings tpl end -->
