{include file='header.tpl'}

<!-- header sliders -->
{if $cInfo.Controller == 'home'}
    <div id="sliders_container">
        <div id="sliders_areas">
            <div class="hide slider_item" id="area_desktop">
                <div class="caption">{$lang.desktop}</div>
                <div class="body">
                    <table class="sTable">
                    <tr>
                        <td valign="top">
                            <fieldset>
                                <legend>{$lang.desktop_blocks}</legend>
                                
                                <table class="sTable" id="ap_blocks_manager">
                                <tr class="header">
                                    <td><div>{$lang.name}</div></td>
                                    <td class="divider"></td>
                                    <td align="center" style="width: 90px"><div>{$lang.fixed_height}</div></td>
                                    <td class="divider"></td>
                                    <td align="center" style="width: 55px"><div>{$lang.show}</div></td>
                                </tr>
                                {foreach from=$blocks item='block' name='apB'}
                                <tr class="body">
                                    <td {if $smarty.foreach.apB.iteration%2 == 0}class="highlighted"{/if}>{$block.name}</td>
                                    <td class="divider"></td>
                                    <td align="center" {if $smarty.foreach.apB.iteration%2 == 0}class="highlighted"{/if}>
                                        <input id="apfblock:{$block.Key}" type="checkbox" {if $block.Fixed}checked="checked"{/if} />
                                    </td>
                                    <td class="divider"></td>
                                    <td align="center" {if $smarty.foreach.apB.iteration%2 == 0}class="highlighted"{/if}>
                                        <input id="apsblock:{$block.Key}" type="checkbox" {if $block.Status == 'active'}checked="checked"{/if} />
                                    </td>
                                </tr>
                                {/foreach}
                                
                                {rlHook name='apTplHeaderBlocksListEnd'}
                                
                                </table>
                            </fieldset>
                        </td>
                        <td style="width: 10px;"></td>
                        <td valign="top">
                            <fieldset style="padding-bottom: 5px;">
                                <legend>{$lang.desktop_settings}</legend>
                                
                                <table class="sTable" id="ap_settings">
                                <tr class="header">
                                    <td><div>{$lang.name}</div></td>
                                    <td class="divider"></td>
                                    <td style="width: 105px;"><div>{$lang.value}</div></td>
                                </tr>
                                {foreach from=$desktop_settings item='setting' name='apS'}
                                <tr class="body">
                                    <td {if $smarty.foreach.apS.iteration%2 == 0}class="highlighted"{/if}>{$setting.Name}</td>
                                    <td class="divider"></td>
                                    <td align="center" {if $smarty.foreach.apS.iteration%2 == 0}class="highlighted"{/if}>
                                        {if $setting.Type == 'number'}
                                            <input type="text" class="numeric{if $setting.Deny} deny_item{/if}" name="{$setting.Key}" value="{$setting.Default}" style="width: 58px" />
                                        {elseif $setting.Type == 'bool'}
                                            <input {if $setting.Deny}class="deny_item"{/if} type="checkbox"  name="{$setting.Key}" {if $setting.Default}checked="checked"{/if} />
                                        {elseif $setting.Type == 'select'}
                                            <select {if $setting.Deny}class="deny_item"{/if} style="width: 70px;" name="{$setting.Key}">
                                                {foreach from=$setting.Values item='s_option'}
                                                    <option {if $s_option.Key == $setting.Default}selected="selected"{/if} value="{$s_option.Key}">{$s_option.name}</option>
                                                {/foreach}
                                            </select>
                                        {/if}
                                    </td>
                                </tr>
                                {/foreach}
                                
                                {rlHook name='apTplHeaderSettingsEnd'}
                                
                                <tr>
                                    <td colspan="2"></td>
                                    <td align="center"><input id="save_settings" type="button" value="{$lang.save}" style="margin: 5px 0 0 0;" /></td>
                                </tr>
                                </table>
                                
                                <script type="text/javascript">
                                var loading = "{$lang.loading}";
                                {literal}
                                
                                $('input#save_settings').click(function(){
                                    $(this).val(loading);
                                    
                                    var config = new Array();
                                    $('table#ap_settings input,table#ap_settings select').each(function(){
                                        var item = new Array();
                                        item['key'] = $(this).attr('name');
                                        item['deny'] = $(this).hasClass('deny_item') ? 1 : 0;
                                        item['value'] = $(this).attr('type') == 'checkbox' ? ($(this).attr('checked') ? 1 : 0) : $(this).val() ;
                                        
                                        config.push(item);
                                    });
                                    xajax_saveConfig(config)
                                });
                                
                                {/literal}
                                </script>
                                
                                {rlHook name='apTplHeaderSettingsJS'}
                                
                            </fieldset>
                        </td>
                    </tr>
                    </table>
                </div>
            </div>
            
            {rlHook name='apTplHeaderTabsArea'}
        </div>
        
        <div id="header_sliders" class="hide">
            {rlHook name='apTplHeaderTabs'}
            
            <div class="header_slider" id="slider_desktop">
                <div class="left"></div>
                <div class="center">{$lang.desktop}</div>
                <div class="right"></div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    {literal}
    
    var slidersOpen = false;
    var currentTab = '';
    var slidersHome = -2;
    var activeHeight = 0;
    var slidersPadding = 30;
    
    $(document).ready(function(){
        $('div.header_slider').click(function(){
            $('div.header_slider').removeClass('active');
            $(this).addClass('active');
            slidersShow($(this).attr('id').split('_')[1]);
        });
        
        slidersResize();
        
        $(window).resize(function(){
            slidersResize();
        })
        
        $('table#ap_blocks_manager input[type=checkbox]').click(function(){
            if ( $(this).attr('id').split(':')[0] == 'apsblock' )
            {
                var key = $(this).attr('id').split(':')[1];
                var show = $(this).is(':checked');
                
                if ( show )
                {
                    $('div#apblock\\:'+key).fadeIn();
                    $('table#ap_blocks_manager input#apfblock\\:'+key).attr('disabled', false);
                }
                else
                {
                    $('div#apblock\\:'+key).fadeOut();
                    $('table#ap_blocks_manager input#apfblock\\:'+key).attr('disabled', true);
                }
                
                var blocks_status = new Array();
                $('table#ap_blocks_manager input[type=checkbox]').each(function(){
                    if ( $(this).attr('id').split(':')[0] == 'apsblock' )
                    {
                        var key = $(this).attr('id').split(':')[1];
                        var show = $(this).is(':checked');
                        
                        blocks_status.push(key+'|'+show);
                    }
                });
                
                createCookie('ap_blocks_status', blocks_status.join(','), 365);
            }
            else if (  $(this).attr('id').split(':')[0] == 'apfblock' )
            {
                var key = $(this).attr('id').split(':')[1];
                var fixed = $(this).is(':checked');
                
                if ( fixed )
                {
                    $('div#apblock\\:'+key+' div.outer div.body').css('height', '190px').css('overflow-y', 'auto');
                }
                else
                {
                    $('div#apblock\\:'+key+' div.outer div.body').css('height', 'auto');
                }
                
                var blocks_fixed = new Array();
                $('table#ap_blocks_manager input[type=checkbox]').each(function(){
                    if (  $(this).attr('id').split(':')[0] == 'apfblock' )
                    {
                        var key = $(this).attr('id').split(':')[1];
                        var show = $(this).is(':checked');
                        
                        blocks_fixed.push(key+'|'+show);
                    }
                });
                
                createCookie('ap_blocks_fixed', blocks_fixed.join(','), 365);
            }
        });
        
        $('table#ap_blocks_manager input[type=checkbox]').each(function(){
            if ( $(this).attr('id').split(':')[0] == 'apsblock' )
            {
                var key = $(this).attr('id').split(':')[1];
                var show = $(this).is(':checked');
                if ( !show )
                {
                    $('table#ap_blocks_manager input#apfblock\\:'+key).attr('disabled', true);
                }
            }
        });
    });
    
    var slidersShow = function(id){
        if ( slidersOpen && id != currentTab )
        {
            var tmp_activeHeight = activeHeight;
            
            $('div#area_'+id).show();
            activeHeight = $('div#area_'+id).height();
            
            $('div#area_'+currentTab).hide();
            $('div#area_'+id).height(tmp_activeHeight).show().animate({
                height: activeHeight
            });
            
            $('div#sliders_areas').animate({
                height: activeHeight + slidersPadding
            });
    
            currentTab = id;
    
            return;
        }
        
        /* get request tab details */
        if ( !slidersOpen )
        {
            $('div#area_'+id).show();
            activeHeight = $('div#area_'+id).height();
            $('#sliders_areas').height(activeHeight + slidersPadding).css('margin-top', (activeHeight + slidersPadding + 1) * -1);
        }
        
        var new_pos_area = !slidersOpen ? -1 : (activeHeight + slidersPadding + 1) * -1;
        
        currentTab = id;
        
        $('div#sliders_areas').animate({
            marginTop: new_pos_area
        }, function(){
            if ( !slidersOpen )
            {
                $('div#slider_'+id).removeClass('active');
                $('.slider_item').hide();
            }
        });
        
        slidersOpen = !slidersOpen ? true : false;
    }
    
    {/literal}
    </script>
    
    {rlHook name='apTplHeaderTabsJS'}
{/if}
<!-- header sliders end -->

<div class="middle_right">

    <!-- print bread crumbs -->
    <div id="bc_container">{strip}
        {if $cInfo.Controller != 'home'}
            <a href="{$rlBase}index.php" class="bread_crumbs">{$lang.admin_panel}</a>
        {/if}

        {if $cInfo.Controller != 'home'}
            {foreach from=$breadCrumbs item='bc' name='bc_foreach'}
                {if $smarty.foreach.bc_foreach.last}
                    <a href="{$rlBase}index.php?controller=
                        {if empty($bc.Controller)}
                            {$smarty.server.QUERY_STRING|replace:'controller=':''|replace:'&':'&amp;'}
                        {else}
                            {$bc.Controller}
                        {/if}

                        {if $bc.Vars}
                            &amp;{$bc.Vars}
                        {/if}" class="current">{$bc.name}</a>
                    {assign var='pTitle' value=$bc.name}
                {else}
                    <a href="{$rlBase}index.php?controller={$bc.Controller}{if $bc.Vars}&amp;{$bc.Vars}{/if}">{$bc.name}</a>
                {/if}
            {/foreach}
        {else}
            {assign var='pTitle' value=$breadCrumbs.1.name}
        {/if}
    {/strip}</div>

    {if !empty($pTitle)}
        <h1>{if empty($cpTitle)}{$pTitle}{else}{$cpTitle}{/if}</h1>
    {/if}
    <!-- print bread crumbs end -->
    
    <!-- print system notice -->
    <div id="system_message"></div>

    {if $pNotice || $alerts || $errors || $infos}
        <script type="text/javascript">
        $(document).ready(function(){literal}{ {/literal}
            {if $pNotice}
                printMessage('notice', '{$pNotice|escape:"javascript"}');
            {elseif $alerts}
                printMessage('alert', '{if is_array($alerts)}<ul>{foreach from=$alerts item="alert"}<li>{$alert|escape:"javascript"}</li>{/foreach}</ul>{else}{$alerts|escape:"javascript"}{/if}');
            {elseif $errors}
                printMessage('error', '{if is_array($errors)}<ul>{foreach from=$errors item="error"}<li>{$error|escape:"javascript"}</li>{/foreach}</ul>{else}{$errors|escape:"javascript"}{/if}');
            {elseif $infos}
                printMessage('info', '{if is_array($infos)}<ul>{foreach from=$infos item="info"}<li>{$info|escape:"javascript"}</li>{/foreach}</ul>{else}{$infos|escape:"javascript"}{/if}');
            {/if}
        {literal}});{/literal}
        </script>
    {/if}
    <!-- print system notice end -->
    
    <!-- load controller -->
    {if $cInfo.Plugin}
        {include file=$smarty.const.RL_PLUGINS|cat:$cInfo.Plugin|cat:$smarty.const.RL_DS|cat:'admin'|cat:$smarty.const.RL_DS|cat:$cInfo.Controller|cat:'.tpl'}
    {else}
        {include file='controllers'|cat:$smarty.const.RL_DS|cat:$cInfo.Controller|cat:'.tpl'}
    {/if}
    <!-- load controller end -->
    
    {rlHook name='apTplContentBottom'}
    
</div>

{include file='footer.tpl'}
