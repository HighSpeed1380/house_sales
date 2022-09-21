<!-- contact us -->

{if $smarty.get.sending == 'complete'}
	<div class="text-notice">{$lang.contact_sent}</div>
{else}
	<div class="content-padding">
		<form action="{$rlBase}{if $config.mod_rewrite}{$pages.contact_us}.html{else}?page={$pages.contact_us}{/if}" method="post">
			<input type="hidden" name="action" value="contact_us" />
			
			<div class="submit-cell">
				<div class="name">{$lang.your_name} <span class="red">*</span></div>
				<div class="field">
					<input class="wauto" type="text" name="your_name" maxlength="50" size="50" value="{if $smarty.post.your_name}{$smarty.post.your_name}{elseif $account_info}{$account_info.Full_name}{/if}" />
				</div>
			</div>

			<div class="submit-cell">
				<div class="name">{$lang.your_email} <span class="red">*</span></div>
				<div class="field">
					<input class="wauto" type="text" name="your_email" size="50" maxlength="100" value="{if $smarty.post.your_email}{$smarty.post.your_email}{else}{$account_info.Mail}{/if}" />
				</div>
			</div>

			{rlHook name='contactFields'}

			<div class="submit-cell">
				<div class="name">{$lang.message} <span class="red">*</span></div>
				<div class="field">
					<textarea name="message" rows="6" cols="50">{$smarty.post.message}</textarea>
				</div>
			</div>

			{if $config.security_img_contact_us}
			<div class="submit-cell">
				<div class="name">{$lang.security_code} <span class="red">*</span></div>
				<div class="field">
					{include file='captcha.tpl' no_caption=true}
				</div>
			</div>
			{/if}

			<div class="submit-cell buttons">
				<div class="name"></div>
				<div class="field"><input onclick="$(this).val('{$lang.loading}');" type="submit" name="finish" value="{$lang.send}" /></div>
			</div>
		</form>
	</div>
{/if}

<!-- contact us end -->
