<!-- display video -->

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}player/swfobject.js"></script>
    <div id="player_obj"></div>
    
    <script type="text/javascript">
    var obj = new SWFObject("{$smarty.const.RL_LIBS_URL}player/player.swf", "ply", "{$config.video_width}", "{$config.video_height}", "8");
    obj.addParam('allowfullscreen', {if $config.video_allowfullscreen}true{else}false{/if});
    obj.addParam('allowscriptaccess','always');
    obj.addParam('wmode','opaque');
    obj.addParam("bgcolor", "000000");

    obj.addVariable("file", "{$smarty.const.RL_FILES_URL}{$video.Video}");
    obj.addVariable("image","{$smarty.const.RL_FILES_URL}{$video.Preview}");
    obj.addVariable("backcolor",'0xffffff');
    //obj.addVariable("fontcolor",'ff00000');
    //obj.addVariable("lightcolor",'ff0000');
    //obj.addVariable("screencolor",'ff0000');
    obj.addVariable("largecontrols",false);
    //if ($config->get("video_display_logo"))
        //obj.addVariable("logo","'.$templs.'/img/logo.png");

    obj.addVariable("width","{$config.video_width}");
    obj.addVariable("height","{$config.video_height}");
        
    obj.addVariable("showeq",false);
    obj.addVariable("autostart",{if $config.video_autostart}true{else}false{/if});
    {if $config.video_bufferlength > 1}
        obj.addVariable("bufferlength", {$config.video_bufferlength});
    {/if}
    obj.addVariable("repeat", {if $config.video_repeat}true{else}false{/if});
    obj.addVariable("smoothing", {if $config.video_smoothing}true{else}false{/if});
    {if $config.video_volume > 1}
        obj.addVariable("volume", {$config.video_volume});
    {/if}

    obj.write("player_obj");
    </script>
    
<!-- display video end -->
