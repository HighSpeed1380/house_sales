<!-- video thumbnails | preview mode -->

{assign var='replace' value=`$smarty.ldelim`key`$smarty.rdelim`}
<script type="text/javascript">var videos = new Array();</script>
<ul class="thumbnails {if $config.video_thumbnail_position == 'bottom' || $config.video_thumbnail_position == 'top'}inline{/if}">
	{foreach from=$videos item='video'}
	<li id="video_{$video.ID}">
		<script type="text/javascript">videos['video_{$video.ID}'] = new Array({$video.ID}, '{$video.Type}', '{$video.Preview}', '{$video.Video}');</script>
		{if $video.Type == 'local'}
			<img class="peview_item" src="{$smarty.const.RL_FILES_URL}{$video.Preview}" alt="" />
		{else}
			<img class="peview_item" src="{$l_youtube_thumbnail|replace:$replace:$video.Preview}" alt="" />
		{/if}
	</li>
	{/foreach}
</ul>

<script type="text/javascript">//<![CDATA[
var video_height = {$config.video_height};
var video_vertical = {if $config.video_thumbnail_position == 'right' || $config.video_thumbnail_position == 'left'}true{else}false{/if};
var video_id = false;
{literal}

$(document).ready(function(){
	$('ul.thumbnails li').click(function(){
		loadVideo(this);
	});
	
	var loadVideo = function(obj){
		var id = $(obj).attr('id');
		if ( id == video_id )
		{
			return;
		}
		
		video_id = id;
		
		if (videos[id][1] == 'local')
		{
			$('#video_youTube').hide();
			$('#player').attr('href', '{/literal}{$smarty.const.RL_FILES_URL}{literal}'+videos[id][3]).css('display', 'block');
			flowplayer('player', {src: '{/literal}{$smarty.const.RL_LIBS_URL}{literal}player/flowplayer-3.2.7.swf', wmode: 'transparent'}, {
				clip: {{/literal}
					autoPlay: {if $config.video_autostart}true{else}false{/if},
					autoBuffering: true,
					{if $config.video_bufferlength > 1}bufferLength: {$config.video_bufferlength},{/if}
				}{literal}
			}).setVolume({/literal}{$config.video_volume}{literal});
		}
		else
		{
			$('#player').hide();
			$('#video_youTube').show()
			var width = $('#video_youTube').width();
			var embed = flynax.youTubeFrame.replace('{key}', videos[id][2]).replace('{width}', width).replace('{height}', video_height);
			
			$('#video_youTube').html(embed);
		}
	};
	
	var video_exist = false;
	
	$('div.tabs li').click(function(){
		if ( !video_exist && $(this).attr('id') == 'tab_video' )
		{
			$('.thumbnails').flSlider({
				vertical: video_vertical,
				height: video_height + 26,
				perSlide: 3,
				clearance: 5
			});
			
			video_exist = true;
		}
		
		setTimeout(function(){
			loadVideo($('#area_video ul li:first'));
		}, 100);
	});
});

{/literal}
//]]>
</script>

<!-- video thumbnails | preview mode end -->