<script type="text/javascript">
    var rlLangDir       = '{$smarty.const.RL_LANG_DIR}';
    var rlLang          = '{$smarty.const.RL_LANG_CODE|lower}';
    var isLogin         = {if $isLogin}true{else}false{/if};
    var staticDataClass = {if class_exists('rlStatic')}true{else}false{/if};

    var lang = new Array();
    {foreach from=$js_keys item='js_key'}
    lang['{$js_key}'] = '{$lang[$js_key]|escape:"javascript"}';
    {/foreach}

    var rlPageInfo           = new Array();
    rlPageInfo['key']        = '{$pageInfo.Key}';
    rlPageInfo['controller'] = '{$pageInfo.Controller}';
    rlPageInfo['path']       = '{if $pageInfo.Path_real}{$pageInfo.Path_real}{else}{$pageInfo.Path}{/if}';

    var rlConfig                                 = new Array();
    rlConfig['seo_url']                          = '{$rlBase}';
    rlConfig['tpl_base']                         = '{$rlTplBase}';
    rlConfig['files_url']                        = '{$smarty.const.RL_FILES_URL}';
    rlConfig['libs_url']                         = '{$smarty.const.RL_LIBS_URL}';
    rlConfig['plugins_url']                      = '{$smarty.const.RL_PLUGINS_URL}';

    /**
     * @since 4.8.2 - Added "cors_url", "tpl_cors_base" variables
     */
    rlConfig['cors_url']                         = '{$domain_info.scheme}://{$smarty.server.HTTP_HOST}';
    {if $domain_info.path !== '/'}
        rlConfig['cors_url']                     += '{$domain_info.path}';
    {/if}
    rlConfig['ajax_url']                         = rlConfig['cors_url'] + '/request.ajax.php';
    rlConfig['tpl_cors_base']                    = rlConfig['cors_url'] + '/templates/{$config.template}/';
    rlConfig['mod_rewrite']                      = {$config.mod_rewrite};
    rlConfig['sf_display_fields']                = {$config.sf_display_fields};
    rlConfig['account_password_strength']        = {$config.account_password_strength};
    rlConfig['messages_length']                  = {if $config.messages_length}{$config.messages_length}{else}250{/if};
    rlConfig['pg_upload_thumbnail_width']        = {if $config.pg_upload_thumbnail_width}{$config.pg_upload_thumbnail_width}{else}120{/if};
    rlConfig['pg_upload_thumbnail_height']       = {if $config.pg_upload_thumbnail_height}{$config.pg_upload_thumbnail_height}{else}90{/if};
    rlConfig['thumbnails_x2']                    = {if $config.thumbnails_x2}true{else}false{/if};
    rlConfig['template_type']                    = {if $tpl_settings.type}'{$tpl_settings.type}'{else}false{/if};
    rlConfig['domain']                           = '{$domain_info.domain}';
    rlConfig['domain_path']                      = '{$domain_info.path}';
    rlConfig['isHttps']                          = {if $domain_info.isHttps}true{else}false{/if};
    rlConfig['map_search_listings_limit']        = {if $config.map_search_listings_limit}{$config.map_search_listings_limit}{else}500{/if};
    rlConfig['map_search_listings_limit_mobile'] = {if $config.map_search_listings_limit_mobile}{$config.map_search_listings_limit_mobile}{else}75{/if};
    rlConfig['price_delimiter']                  = {if $config.price_delimiter == '"'}'{$config.price_delimiter}'{else}"{$config.price_delimiter}"{/if};
    rlConfig['price_separator']                  = "{$config.price_separator}";
    rlConfig['random_block_slideshow_delay']     = '{$config.random_block_slideshow_delay}';
    rlConfig['template_name']                    = '{$tpl_settings.name}';
    rlConfig['map_provider']                     = '{$config.map_provider}';
    rlConfig['map_default_zoom']                 = '{$config.map_default_zoom}';
    rlConfig['upload_max_size']                  = {$upload_max_size};
    rlConfig['expire_languages']                 = {if $config.expire_languages}{$config.expire_languages}{else}12{/if};

    var rlAccountInfo = new Array();
    rlAccountInfo['ID'] = {if $account_info}{$account_info.ID}{else}null{/if};

    flynax.langSelector();

    var qtip_style = new Object({literal}{{/literal}
        width      : '{if $tpl_settings.qtip.width}{$tpl_settings.qtip.width}{else}auto{/if}',
        background : '#{if $tpl_settings.qtip.background}{$tpl_settings.qtip.background}{else}396932{/if}',
        color      : '#{if $tpl_settings.qtip.color}{$tpl_settings.qtip.color}{else}ffffff{/if}',
        tip        : '{if $tpl_settings.qtip.tip}{$tpl_settings.qtip.tip}{else}bottomLeft{/if}',
        border     : {literal}{{/literal}
            width  : {if $tpl_settings.qtip.b_width}{$tpl_settings.qtip.b_width}{else}7{/if},
            radius : {if $tpl_settings.qtip.b_radius}{$tpl_settings.qtip.b_radius}{else}0{/if},
            color  : '#{if $tpl_settings.qtip.b_color}{$tpl_settings.qtip.b_color}{else}396932{/if}'
        {literal}}
    }{/literal});
</script>

{php}
    if (in_array($GLOBALS['page_info']['Controller'], array('listing_details', 'listing_type'))) {
        $this->assign('navIcons', ' ');
    }
{/php}
