<!-- login modal content -->

{if $showLoginModalHeader}
    <div class="caption_padding">{$lang.login}</div>
{/if}

{if $loginAttemptsLeft > 0 && $config.security_login_attempt_user_module}
    <div class="attention">{$loginAttemptsMess}</div>
{elseif $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}
    <div class="attention">
        {assign var='periodVar' value=`$smarty.ldelim`period`$smarty.rdelim`}
        {assign var='replace' value='<b>'|cat:$config.security_login_attempt_user_period|cat:'</b>'}
        {assign var='regReplace' value='<span class="red">$1</span>'}
        {$lang.login_attempt_error|replace:$periodVar:$replace|regex_replace:'/\[(.*)\]/':$regReplace}
    </div>
{/if}

<form {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}onsubmit="return false;"{/if}
      action="{pageUrl key='login'}"
      method="post"
      class="login-form"
>
    <input type="hidden" name="action" value="login" />

    <input placeholder="{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}"
           type="text"
           class="w-100 mb-3"
           name="username"
           maxlength="100"
           value="{$smarty.post.username}" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if}
    />
    <input placeholder="{$lang.password}"
           type="password"
           class="w-100 mb-3"
           name="password"
           maxlength="100" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if}
    />

    <div class="mb-3">
        <input type="submit" class="w-100" value="{$lang.login}" {if $loginAttemptsLeft <= 0 && $config.security_login_attempt_user_module}disabled="disabled"{/if} />
        <span class="hookUserNavbar">{rlHook name='tplUserNavbar'}</span>
    </div>

    {if $config.remember_me}
        <div class="remember-me mb-3">
            <label><input type="checkbox" name="remember_me" checked="checked" />{$lang.remember_me}</label>
        </div>
    {/if}
</form>

{if $linkLabels}
    {$lang.forgot_pass} <a title="{$lang.remind_pass}" href="{pageUrl key='remind'}">{$lang.remind_pass}</a>
    {if $pages.registration}
        <div class="mt-1">
            {$lang.new_here} <a title="{$lang.create_account}" href="{pageUrl key='registration'}">{$lang.create_account}</a>
        </div>
    {/if}
{else}
    <div class="text-center">
        <a title="{$lang.remind_pass}" class="font2" href="{pageUrl key='remind'}">{$lang.forgot_pass}</a>
        {if $pages.registration}
            <div class="mt-1">
                <a title="{$lang.create_account}" class="font2" href="{pageUrl key='registration'}">{$lang.registration}</a>
            </div>
        {/if}
    </div>
{/if}

<!-- login modal content end -->
