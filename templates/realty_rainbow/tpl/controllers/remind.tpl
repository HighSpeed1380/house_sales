<!-- remind password page -->

<div class="content-padding">
	{if $change}
		<!-- change password form -->
		{assign var='replace' value=`$smarty.ldelim`username`$smarty.rdelim`}
		{$lang.set_new_password_hint|replace:$replace:$profile_info.Full_name}
		
		<form action="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html?hash={$smarty.get.hash}{else}?page={$pages.remind}&amp;hash={$smarty.get.hash}{/if}" style="margin-top: 20px;" method="post">
			<input type="hidden" name="change" value="1" />
			
			<div class="submit-cell">
				<div class="name">{$lang.new_password}</div>
				<div class="field single-field two-inline left">
					<div><input id="new_password" size="25" class="wauto" type="password" name="profile[password]" maxlength="50" {if $smarty.post.profile.password}value="{$smarty.post.profile.password}"{/if} /></div>
					{if $config.account_password_strength}
						<div>
							<input type="hidden" id="password_strength" value="" />
							<div class="password_strength">
								<div class="scale">
									<div class="color"></div>
									<div class="shine"></div>
								</div>
								<div id="pass_strength"></div>
							</div>

							<script type="text/javascript">
							{literal}
						
							$(document).ready(function(){
								flynax.passwordStrength();
							
								$('#new_password').blur(function(){
									if ( rlConfig['account_password_strength'] ) {
										if ( $('#password_strength').val() < 3 ) {
											printMessage('warning', lang['password_weak_warning'])
										}
										else {
											$('div.warning div.close').trigger('click');
										}
									}
								});
							});
							
							{/literal}
							</script>
						</div>
					{/if}
				</div>
			</div>

			<div class="submit-cell">
				<div class="name">{$lang.new_password_repeat}</div>
				<div class="field single-field"><input class="wauto" size="25" type="password" name="password_repeat" maxlength="30" /></div>
			</div>

			<div class="submit-cell buttons">
				<div class="name"></div>
				<div class="field"><input type="submit" value="{$lang.change}" /></div>
			</div>
		</form>
		
		<!-- change password form end -->
	{else}
		<!-- request password change form -->
		
		<form action="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}" method="post">
			<input type="hidden" name="request" value="1" />

			<div class="submit-cell">
				<div class="name">{$lang.mail}</div>
				<div class="field"><input type="text" name="email" value="{$smarty.post.email}" maxlength="100" size="50" class="wauto" /></div>
			</div>

			<div class="submit-cell buttons">
				<div class="name"></div>
				<div class="field"><input type="submit" value="{$lang.next}" /></div>
			</div>
		</form>
		
		<!-- request password change form end -->
	{/if}
</div>

<!-- remind password page end -->