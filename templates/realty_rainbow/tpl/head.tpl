<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$smarty.const.RL_LANG_CODE|lower}">
<head>

<title>{if $pageInfo.meta_title}{$pageInfo.meta_title}{else}{$pageInfo.title}{/if}</title>

<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="generator" content="Flynax Classifieds Software" />
<meta charset="UTF-8" />
<meta http-equiv="x-dns-prefetch-control" content="on" />
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1" />

<meta name="description" content="{$pageInfo.meta_description|strip_tags|escape}" />
<meta name="Keywords" content="{$pageInfo.meta_keywords|strip_tags|escape}" />

{displayCSS mode='header'}

<link rel="shortcut icon" href="{$rlTplBase}img/favicon.ico" type="image/x-icon" />

{if $pageInfo.canonical}
<link rel="canonical" href="{$pageInfo.canonical}" />
{/if}
{if $pageInfo.rel_prev}
<link rel="prev" href="{$pageInfo.rel_prev}" />
{/if}
{if $pageInfo.rel_next}
<link rel="next" href="{$pageInfo.rel_next}" />
{/if}
{if $pageInfo.robots}
{assign var="meta_robots" value=$pageInfo.robots}
<meta name="robots" content="{if $meta_robots.noindex}noindex{else}index{/if}{if $meta_robots.nofollow},nofollow{/if}">
{/if}

{if $hreflang}
{foreach from=$hreflang item='href' key='code'}
<link rel="alternate" href="{$href}" hreflang="{if $config.lang == $code}x-default{else}{$code}{/if}" />
{/foreach}
{/if}

{if $rss && $pages.rss_feed}
<link rel="alternate" type="application/rss+xml" title="{$rss.title}" href="{getRssUrl}" />
{/if}

<!--[if lte IE 10]>
<meta http-equiv="refresh" content="0; url={$rlTplBase}browser-upgrade.htx" />
<style>{literal}body { display: none!important; }{/literal}</style>
<![endif]-->

<script src="{$smarty.const.RL_LIBS_URL}jquery/jquery.js"></script>
<script src="{$smarty.const.RL_LIBS_URL}javascript/system.lib.js"></script>
<script src="{$smarty.const.RL_LIBS_URL}jquery/jquery.ui.js"></script>
<script src="{$smarty.const.RL_LIBS_URL}jquery/datePicker/i18n/ui.datepicker-{$smarty.const.RL_LANG_CODE|lower}.js"></script>

{rlHook name='tplHeaderCommon'}

{include file='js_config.tpl'}

<script src="{$rlTplBase}js/lib.js"></script>

{rlHook name='tplHeader'}

{$ajaxJavascripts}

</head>

<body class="large {$pageInfo.Key|replace:'_':'-'}-page{if !$side_bar_exists} no-sidebar{/if}{if $bread_crumbs_exists} bc-exists{/if}{if $config.header_banner_space} header-banner{/if}{if $pageInfo.Controller == 'listing_details' && $blocks.get_more_details} get-details-box{/if}{if $config.general_simple_color != 'green'} {$config.general_simple_color}-theme{/if}{if !$config.img_crop_thumbnail} listing-fit-contain{/if}" {if $smarty.const.RL_LANG_DIR == 'rtl'}dir="rtl"{/if}>
