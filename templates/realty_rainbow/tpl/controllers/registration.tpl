<!-- registration controller -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.qtip.js"></script>
<script type="text/javascript">flynax.qtip(); flynax.phoneField();</script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js"></script>

{addCSS file=$rlTplBase|cat:'components/plans-chart/plans-chart.css'}
{addJS file=$rlTplBase|cat:'js/form.js'}

{rlHook name='registrationTopTpl'}

<!-- steps -->
{if $cur_step == 'done'}{assign var='allow_link' value=false}{else}{assign var='allow_link' value=true}{/if}
{assign var='current_found' value=false}

<ul class="steps mobile">
    {assign var='steps_values' value=$reg_steps|@array_values}
    {foreach from=$steps_values item='step' name='stepsF' key='step_key'}{strip}
        {if $step.key == 'account' && $smarty.session.registration.no_account_step}{continue}{/if}
        {if $cur_step == $step.key || !$cur_step}{assign var='allow_link' value=false}{/if}
        {assign var='next_index' value=$step_key+1}
        <li id="step_{$step.key}" class="{if $cur_step}{if $cur_step == $step.key}current{assign var='current_found' value=true}{elseif !$current_found}{if $steps_values[$next_index].key == $cur_step}prev {/if}past{/if}{elseif $smarty.foreach.stepsF.first}current{/if}">
            <a href="{if $allow_link}{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}{if $step.key == 'profile'}.html?edit{else}/{$reg_steps[$step.key].path}.html{/if}{else}?page={$pageInfo.Path}&amp;step={$reg_steps[$step.key].path}{if $step.key == 'profile'}&amp;edit{/if}{/if}{else}javascript:void(0){/if}" title="{$step.name}">{if $step.caption}<span>{$lang.step}</span> {$smarty.foreach.stepsF.iteration}{else}{$step.name}{/if}</a>
        </li>
    {/strip}{/foreach}
</ul>
<!-- steps -->

<h1>{if $cur_step}{$reg_steps[$cur_step].name}{else}{$reg_steps.profile.name}{/if}</h1>

{if $agentRegistration && $cur_step !== 'done'}
    <h3 class="mb-4">{$lang.agent_registration_notice}</h3>
{/if}

{if !$no_access}
<div class="content-padding">
    {if !$cur_step}
        <div class="area_profile step_area">
            <form name="account_reg_form" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html{else}?page={$pageInfo.Path}{/if}" enctype="multipart/form-data">
                <input type="hidden" name="step" value="profile" />

                <div class="submit-cell{if $config.account_login_mode == 'email'} hide{/if}">
                    <div class="name">{$lang.username}</div>
                    <div class="field single-field">
                        <input size="35" class="wauto" type="text" name="profile[username]" {if $smarty.session.registration.account_id}readonly="readonly"{/if} maxlength="50" {if $smarty.post.profile.username}value="{$smarty.post.profile.username|htmlspecialchars}"{/if} />
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name">{$lang.mail}</div>
                    <div class="field single-field">
                        <div>
                            <input size="45"
                                   class="wauto"
                                   type="text"
                                   name="profile[mail]"
                                   {if $smarty.session.registration.account_id}readonly="readonly"{/if}
                                   value="{strip}
                                        {if $smarty.post.profile.mail}
                                            {$smarty.post.profile.mail|htmlspecialchars}
                                        {elseif $agentRegistration && $agentInvite && $agentInvite.Agent_Email}
                                            {$agentInvite.Agent_Email}
                                        {/if}
                                   {/strip}"
                            />
                        </div>
                        <div class="options">
                            <label class="d-block pt-3 pb-1">
                                <input value="1" type="checkbox" {if isset($smarty.post.profile.display_email)}checked="checked"{/if} name="profile[display_email]" /> {$lang.display_email}
                            </label>

                            {rlHook name='tplRegistrationCheckbox'}<!-- > 4.1.0 -->
                        </div>
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name">{$lang.password}</div>
                    <div class="field single-field two-inline left">
                        <div><input size="25" class="wauto" type="password" name="profile[password]" maxlength="50" {if $smarty.post.profile.password}value="{$smarty.post.profile.password}"{/if} /></div>
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
                            </div>
                        {/if}
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name">{$lang.password_repeat}</div>
                    <div class="field single-field">
                        <input size="25" class="wauto" type="password" name="profile[password_repeat]" maxlength="50" {if $smarty.post.profile.password}value="{$smarty.post.profile.password}"{/if} />
                    </div>
                </div>

                <div class="submit-cell">
                    <div class="name">{$lang.account_type}</div>
                    <div class="field single-field">
                        {if $account_types|@count > 1}
                            <select name="profile[type]">
                                <option value="0">{$lang.select}</option>
                                {foreach from=$account_types item='account_type'}
                                    <option value="{$account_type.ID}"
                                        {if $smarty.post.profile.type == $account_type.ID}selected="selected"{/if}
                                        data-key="{$account_type.Key}">
                                        {$account_type.name}
                                    </option>
                                {/foreach}
                            </select>

                            {foreach from=$account_types item='account_type'}
                                {if $account_type.desc}<div class="qtip_cont">{$account_type.desc}</div><img class="qtip hide desc_{$account_type.ID}" src="{$rlTplBase}img/blank.gif" alt="" />{/if}
                            {/foreach}
                        {else}
                            {foreach from=$account_types item='account_type' name='typesF'}
                                {assign var='own_location' value=$account_type.Own_location}
                                <div class="default_size mt-2">{$account_type.name}</div>
                                <select name="profile[type]" class="hide">
                                    <option value="{$account_type.ID}"
                                        selected="selected"
                                        data-key="{$account_type.Key}">
                                        {$account_type.name}
                                    </option>
                                </select>
                                {if $account_type.desc}
                                    <div class="qtip_cont">{$account_type.desc}</div><img title="" class="qtip" src="{$rlTplBase}img/blank.gif" alt="" />
                                {/if}
                            {/foreach}
                        {/if}
                    </div>
                </div>

                <div id="personal_address_field" class="{if $account_types|@count > 1 || !$own_location}hide{/if}">
                    <div class="submit-cell">
                        <div class="name">{$lang.personal_address}</div>
                        <div class="field single-field">
                            {if $config.account_wildcard}
                                {$scheme}://<input type="text" class="ml-2" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.session.registration.account_id}readonly="readonly"{/if} {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />.{$domain}
                            {else}
                                {$scheme}://{$domain}/{if $smarty.const.RL_DIR}{$smarty.const.RL_DIR}{/if}<input type="text" class="ml-2" style="width: 90px;" maxlength="30" name="profile[location]" {if $smarty.post.profile.location}value="{$smarty.post.profile.location}"{/if} />
                            {/if}

                            <div class="notice">{$lang.latin_characters_only}</div>
                        </div>
                    </div>
                </div>

                {if $config.security_img_registration}
                    <div class="submit-cell">
                        <div class="name">{$lang.security_code}</div>
                        <div class="field">{include file='captcha.tpl' no_caption=true no_hint=true}</div>
                    </div>
                {/if}

                <div class="submit-cell{if $account_types|@count > 1} hide{/if}">
                    <div class="name"></div>
                    <div class="field">
                        {include file='blocks/agreement_fields.tpl'}
                    </div>
                </div>

                <div class="submit-cell buttons">
                    <div class="name"></div>
                    <div class="field">
                        <input type="submit" value="{$lang.next_step}" id="profile_submit" />
                    </div>
                </div>

                <script type="text/javascript">
                var reg_account_fields = false;
                var reg_account_type = false;
                var reg_account_submit = false;
                var account_types = new Array();

                {foreach from=$account_types item='account_type'}
                    account_types[{$account_type.ID}] = {if $account_type.Own_location}1{else}0{/if};
                {/foreach}

                flynax.registration({if $fields}true{/if});
                flynax.passwordStrength();
                </script>
            </form>
        </div>
    {else}
        {if $cur_step == 'account'}

        <!-- account additional fields step	-->
        <div class="area_account step_area">
            <form name="account_reg_form" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$reg_steps.$cur_step.path}.html{else}?page={$pageInfo.Path}&amp;step={$reg_steps.$cur_step.path}{/if}" enctype="multipart/form-data">
                <input type="hidden" name="step" value="account" />

                <div id="account_table">
                    {if $fields}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'reg_account.tpl'}
                    {/if}
                </div>

                <div class="hide eval">flynaxTpl.customInput();</div>

                <span class="form-buttons form">
                    <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?edit{else}?page={$pageInfo.Path}&amp;edit{/if}">{if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}</a>
                    <input type="submit" value="{$lang.next_step}" />
                </span>
            </form>

            <script type="text/javascript">
            flynax.registrationSubmitFormHandler();
            </script>
        </div>

        {elseif $cur_step == 'plan'}

        <!-- plan step	-->
        <div class="area_plan step_area">
            <form name="account_reg_form" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$reg_steps.$cur_step.path}.html{else}?page={$pageInfo.Path}&amp;step={$reg_steps.$cur_step.path}{/if}" enctype="multipart/form-data">
                <input type="hidden" name="step" value="plan" />

                <div class="plans-container membership-plans">
                    {assign var=subscription_exists value=false}
                    {foreach from=$plans item='plan'}{if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}{assign var=subscription_exists value=true}{break}{/if}{/foreach}
                    <ul class="plans{if $plans|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}">
                        {foreach from=$plans item='plan' name='plansF'}{strip}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'membership_plan.tpl'}
                        {/strip}{/foreach}
                    </ul>
                </div>

                <span class="form-buttons form">
                    {if $smarty.session.registration.no_account_step}
                        <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?edit{else}?page={$pageInfo.Path}&amp;edit{/if}">{if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}</a>
                    {else}
                        <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$prev_step.path}.html{else}?page={$pageInfo.Path}&amp;step={$prev_step.path}{/if}">{if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}</a>
                    {/if}
                    <input type="submit" value="{$lang.next_step}" />
                </span>
            </form>

            <script type="text/javascript">
            var plans = Array();
            var selected_plan_id = 0;
            {foreach from=$plans item='plan'}
                plans[{$plan.ID}] = new Array();
                plans[{$plan.ID}]['Key'] = '{$plan.Key}';
                plans[{$plan.ID}]['Featured'] = {$plan.Featured|@intval};
            {/foreach}

            {literal}

            $(document).ready(function(){
                flynax.planClick();
            });

            {/literal}
            </script>
        </div>

        {elseif $cur_step == 'checkout'}

        <!-- checkout step	-->
        <div class="area_checkout step_area content-padding">
            <div style="padding-bottom: 5px;">{$lang.checkout_step_info}</div>

            <form id="form-checkout" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$reg_steps.$cur_step.path}.html{else}?page={$pageInfo.Path}&amp;step={$reg_steps.$cur_step.path}{/if}">
                <input type="hidden" name="step" value="checkout" />
                {gateways}
                <div class="form-buttons no-top-padding">
                    <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$prev_step.path}.html{else}?page={$pageInfo.Path}&amp;step={$prev_step.path}{/if}">{if $smarty.const.RL_LANG_DIR == 'ltr'}&larr;{else}&rarr;{/if} {$lang.perv_step}</a>
                    <input type="submit" value="{$lang.next_step}" />
                </div>
            </form>
        </div>

        {elseif $cur_step == 'done'}

        <!-- done step	-->
        <div class="area_done">
            <div class="caption">{$lang.registration_complete_caption}</div>

            {if $account_types.$registr_account_type.Auto_login && !$account_types.$registr_account_type.Email_confirmation && !$account_types.$registr_account_type.Admin_confirmation}
                {if $config.mod_rewrite}
                    {assign var='add_listing' value=$pages.add_listing|cat:'.html'}
                {else}
                    {assign var='add_listing' value='?page='|cat:$pages.add_listing}
                {/if}
                {assign var='replace' value='<a href="'|cat:$rlBase|cat:$add_listing|cat:'">$1</a>'}
                {$lang.registration_complete_auto_login|regex_replace:'/\[(.*)\]/':$replace}
            {else}
                {if $account_types.$registr_account_type.Email_confirmation}
                    {assign var='replace' value=`$smarty.ldelim`email`$smarty.rdelim`}
                    {$lang.registration_complete_incomplete|replace:$replace:$smarty.session.ses_registration_data.email}
                {else}
                    {if $account_types.$registr_account_type.Admin_confirmation}
                        {$lang.registration_complete_pending}
                    {else}
                        {if $config.mod_rewrite}
                            {assign var='account_area_link' value=$rlBase|cat:$pages.login|cat:'.html'}
                        {else}
                            {assign var='account_area_link' value=$rlBase|cat:'?page='|cat:$pages.login}
                        {/if}
                        {assign var='replace' value='<a href="'|cat:$account_area_link|cat:'">$1</a>'}
                        {$lang.registration_complete_active|regex_replace:'/\[(.*)\]/':$replace}
                    {/if}
                {/if}
            {/if}
        </div>
        {/if}

        {rlHook name='registrationStepActionsTpl'}

    {/if}

    {if $cur_step}
    <script type="text/javascript">
        {literal}
        $(document).ready(function(){
            flynax.switchStep('{/literal}{$cur_step}{literal}');
        });
        {/literal}
    </script>
    {/if}
</div>
{else}
    <ul>
    {foreach from=$errors item='error'}
        <li>{$error}</li>
    {/foreach}
    </ul>
{/if}

{rlHook name='registrationBottomTpl'}

<!-- registration controller end -->
