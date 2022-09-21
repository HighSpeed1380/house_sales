<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

<title>{$lang.login_to} {$lang.rl_admin_panel}</title>

<meta name="generator" content="reefless_admin" />
<meta http-equiv="Content-Type" content="text/html; charset={$config.encoding}" />
<link href="{$rlTplBase}css/login.css" type="text/css" rel="stylesheet" />
<link href="{$rlTplBase}css/style.css" type="text/css" rel="stylesheet" />
<link rel="shortcut icon" href="{$rlTplBase}img/favicon.ico" />
<link href="{$rlTplBase}css/ext/ext-all.css" type="text/css" rel="stylesheet" />
<link href="{$rlTplBase}css/ext/rlExt.css" type="text/css" rel="stylesheet" />

{$ajaxJavascripts}

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}extJs/ext-base.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}extJs/ext-all.js"></script>
<script type="text/javascript" src="{$rlBase}js/login.js"></script>

</head>
<body>
<div id="height">
    <div id="middle">
        <div id="login_block">
            <div class="top">
                <div class="left"></div>
                <div class="center"></div>
                <div class="right"></div>
            </div>
            <div class="clear"></div>
            
            <div class="middle_outer">
                <div class="middle_inner">
                    <div id="logo"></div>
                    {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_admin_module}
                        <div class="error">
                            <div class="inner">
                                <div class="icon"></div>
                                {assign var='periodVar' value=`$smarty.ldelim`period`$smarty.rdelim`}
                                {assign var='replace' value='<b>'|cat:$config.security_login_attempt_admin_period|cat:'</b>'}
                                {$lang.login_attempt_error|replace:$periodVar:$replace}
                            </div>
                        </div>
                    {else}
                        <form name="login" action="" method="post" onsubmit="return false;">
                            <div class="relative" style="margin: 0 0 5px">
                                <input style="width: 215px;" maxlength="25" type="text" id="username" name="username" placeholder="{$lang.username}" />
                            </div>
                            
                            <div class="relative" style="margin: 0 0 5px">
                                <input style="width: 215px;" maxlength="25" type="password" id="password" name="password" placeholder="{$lang.password}" />
                            </div>
                            
                            <select {if $langCount < 2}class="disabled"{/if} title="{$lang.rl_interface}" id="interface" style="width: 100px;" {if $langCount < 2}disabled="disabled"{/if}>
                                {foreach from=$languages item='languages' name='lang_foreach'}
                                    <option value="{$languages.Code}" {if $smarty.const.RL_LANG_CODE == $languages.Code} selected="selected"{/if}>{$languages.name}</option>
                                {/foreach}
                            </select>
                            
                            <div style="margin-top: 20px;">
                                <input id="login_button" type="submit" name="go" value="{$lang.login}" />
                            </div>
                        </form>
                    {/if}
                </div>
            </div>
            
            <div class="bottom">
                <div class="left"></div>
                <div class="center"></div>
                <div class="right"></div>
            </div>
        </div>
    </div>
    <div id="crosspiece"></div>
</div>

<!-- copyrights -->
<div id="login_footer">
    &copy; <a href="{$lang.flynax_url}">{$lang.copy_rights}</a> {$lang.version} <b>{$config.rl_version}</b>
</div>
<!-- copyrights end -->

<script type="text/javascript">//<![CDATA[
var lang = new Array();

lang['loading'] = '{$lang.loading|escape}';
lang['alert'] = '{$lang.alert|escape}';

{if isset($smarty.get.session_expired) || $smarty.get.action == 'session_expired'}
    fail_alert('', '{$lang.session_expired}');
{/if}

{literal}

var is_visible = true;

$(document).ready(function(){
    
    /* submit handler */
    $('#login_button').click(function(){
        jsLogin("{/literal}{$lang.rl_empty_username|regex_replace:'/[\r\t\n]/':'<br />'}{literal}", "{/literal}{$lang.rl_empty_pass|regex_replace:'/[\r\t\n]/':'<br />'}{literal}");
    });
    
    /* height handler */
    var heightHandler = function(){
        var height = Math.floor((( $(window).height() - 54 ) / 2 ) - 155);
        height = height <= 0 ? 30 : height;
        
        $('#middle').css('padding-top', height);
    };
    
    heightHandler();
    
    $(window).resize(function(){
        heightHandler();
    });
});

{/literal}
//]]>
</script>

{rlHook name='apTplLogin'}

</body>
</html>
