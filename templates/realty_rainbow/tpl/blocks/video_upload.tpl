<!-- file uploader -->

{if $video_allow && !$plan_info.Video_unlim}
	{assign var='replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
	{assign var='video_left' value=$lang.upload_video_left|replace:$replace:$video_allow}
{else}
	{assign var='video_left' value=$lang.upload_video}
{/if}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='uploadVideo' name=$video_left tall=true}
	{if $video_allow || $plan_info.Video_unlim}

		<form method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}{if $pageInfo.Key == 'add_video'}.html?id={$smarty.get.id}{else}/{$category.Path}/{$steps.$cur_step.path}.html{/if}{else}?page={$pageInfo.Path}{if $pageInfo.Key == 'add_video'}&amp;id={$smarty.get.id}{else}&amp;id={$category.ID}&amp;step={$steps.$cur_step.path}{/if}{/if}" enctype="multipart/form-data">
			<input name="step" value="video" type="hidden" />

			<div class="submit-cell">
				<div class="name">{$lang.video_type}</div>
				<div class="field single-field">
					<select id="video_type" name="type" >
						<option value="">{$lang.select}</option>
						<option {if $smarty.post.type == 'youtube'}selected="selected"{/if} value="youtube">{$lang.youtube}</option>
						<option {if $smarty.post.type == 'local'}selected="selected"{/if} value="local">{$lang.local}</option>
					</select>
				</div>
			</div>

			<div id="local_video" class="hide upload">
				<div class="submit-cell">
					<div class="name">{$lang.video_file}</div>
					<div class="field single-field">
						<div class="file-input">
							<input type="file" class="file" name="video" />
							<input type="text" class="file-name" name="" />
							<span>{$lang.choose}</span>
						</div>

						<div style="padding: 2px 0 10px;">{$lang.max_file_size} {$max_file_size} ({foreach from=$l_player_file_types item=item key='f_type' name='file_typesF'}{$f_type}{if !$smarty.foreach.file_typesF.last}, {/if}{/foreach})</div>
					</div>
				</div>

				<div class="submit-cell">
					<div class="name">{$lang.preview_image}</div>
					<div class="field single-field">
						<div class="file-input">
							<input type="file" class="file" name="preview" />
							<input type="text" class="file-name" name="" />
							<span>{$lang.choose}</span>
						</div>
					</div>
				</div>
			</div>
			
			<div id="youtube_video" class="hide upload">
				<div class="submit-cell">
					<div class="name">{$lang.link_or_embed}</div>
					<div class="field single-field"><textarea cols="" rows="4" name="youtube_embed">{$smarty.post.youtube_embed}</textarea></div>
				</div>
			</div>
			
			<div class="submit-cell">
				<div class="name"></div>
				<div class="field"><input style="margin-top: 0" class="button" type="submit" name="finish" value="{$lang.upload}" /></div>
			</div>
		</form>
	{else}
		{assign var='replace_count' value=`$smarty.ldelim`count`$smarty.rdelim`}
		{assign var='replace_plan' value=`$smarty.ldelim`plan`$smarty.rdelim`}
		<div class="dark">{$lang.no_more_videos|replace:$replace_count:$plan_info.Plan_video|replace:$replace_plan:$plan_info.name}</div>
	{/if}
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<script type="text/javascript">
	flynax.uploadVideoUI();
</script>

<!-- file uploader end -->