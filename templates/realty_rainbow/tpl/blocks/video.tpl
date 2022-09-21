<!-- display video -->

	<a href="{$smarty.const.RL_FILES_URL}{$videos.0.Video}" style="display:block;height:{$config.video_height}px;" id="player"></a>
	
	<script type="text/javascript">
	{literal}
	flowplayer('player', {src: '{/literal}{$smarty.const.RL_LIBS_URL}{literal}player/flowplayer-3.2.7.swf', wmode: 'transparent'}, {
		clip: {{/literal}
			autoPlay: {if $config.video_autostart}true{else}false{/if},
			autoBuffering: true,
			{if $config.video_bufferlength > 1}bufferLength: {$config.video_bufferlength},{/if}
		}{literal}
	}).setVolume({/literal}{$config.video_volume}{literal});
	{/literal}
	</script>
	
<!-- display video end -->
