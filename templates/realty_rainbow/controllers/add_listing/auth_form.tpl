<!-- user authorization form -->

{rlHook name='addListingAuthFormTopTpl'}

<div class="auth">{strip}
    <div class="cell">
        <div>
            <div class="caption">{$lang.sign_in}</div>

            <div class="name">{if $config.account_login_mode == 'email'}{$lang.mail}{else}{$lang.username}{/if}</div>
            <input form="listing_form"
                class="w210"
                type="text"
                name="login[username]"
                maxlength="100"
                value="{$smarty.post.login.username}" />

            <div class="name">{$lang.password}</div>
            <input form="listing_form" class="w210" type="password" name="login[password]" maxlength="100" />

            <div class="pt-3">
                <a target="_blank"
                    title="{$lang.remind_pass}"
                    href="{$rlBase}{if $config.mod_rewrite}{$pages.remind}.html{else}?page={$pages.remind}{/if}">
                    {$lang.forgot_pass}
                </a>
            </div>

            {rlHook name='addListingAuthFormAfterLoginTpl'}
        </div>
    </div>
    <div class="divider">{$lang.or}</div>
    <div class="cell">
        {assign var='selected_atype' value=''}

        <div>
            <div class="caption">{$lang.sign_up}</div>

            {if $quick_types && $quick_types|@count <= 1}
                <div class="name">{$lang.your_name}</div>
                <input form="listing_form"
                    class="w210"
                    type="text"
                    name="register[name]"
                    maxlength="100"
                    value="{$smarty.post.register.name}" />
            {/if}

            <div class="name">{$lang.your_email}</div>
            <input form="listing_form"
                class="w210"
                type="text"
                name="register[email]"
                maxlength="100"
                value="{$smarty.post.register.email}"  />

            {if $quick_types && $quick_types|@count > 1}
                <div class="name">{$lang.account_type}</div>
                <select form="listing_form" class="w120" name="register[type]">
                    {foreach from=$quick_types item='quick_reg_type' name='acTypes'}
                        {if $smarty.post.register.type && $smarty.post.register.type == $quick_reg_type.ID}
                            {assign var='selected_atype' value=$quick_reg_type.Key}
                        {elseif !$smarty.post.register.type && $smarty.foreach.acTypes.first}
                            {assign var='selected_atype' value=$quick_reg_type.Key}
                        {/if}

                        <option value="{$quick_reg_type.ID}"
                            {if ($smarty.post.register.type && $smarty.post.register.type == $quick_reg_type.ID)
                                || (!$smarty.post.register.type && $smarty.foreach.acTypes.first)}selected="selected"{/if}
                            data-key="{$quick_reg_type.Key}">
                            {$quick_reg_type.name}
                        </option>
                    {/foreach}
                </select>

                {foreach from=$quick_types item='quick_reg_type' name='acName'}
                    {if $quick_reg_type.desc}
                        <div class="qtip_cont">{$quick_reg_type.desc}</div>
                        <img class="qtip {if !$smarty.foreach.acName.first}hide {/if}sc_{$quick_reg_type.ID}"
                            src="{$rlTplBase}img/blank.gif"
                            alt="" />
                    {/if}
                {/foreach}
            {elseif $quick_types && $quick_types|@count == 1}
                {assign var='selected_atype' value=$quick_types.0.Key}
                <input form="listing_form" type="hidden" name="register[type]" value="{$quick_types.0.ID}" />
            {/if}

            <div class="agreement-fields{if !$selected_atype} hide{/if}">
                {include file='blocks/agreement_fields.tpl' data_form='listing_form'}
            </div>

            <script class="fl-js-dynamic">{literal}
            $(function(){
                $('[name="register[type]"]').off('change', accountTypeHandler).on('change', accountTypeHandler);
            });

            function accountTypeHandler(){
                var atype_key = $('[name="register[type]"]').find('option:selected').data('key');

                // show/hide related agreement fields
                var $agFields          = $('div.ag_fields');
                var $agFieldsContainer = $agFields.closest('div.agreement-fields');

                $agFields.find('input').attr('disabled', true);
                $agFields.addClass('hide');
                $agFieldsContainer.addClass('hide');

                if (atype_key != '' && atype_key != undefined) {
                    $agFieldsContainer.removeClass('hide');

                    $agFields.each(function(){
                        var at_types = $(this).data('types');

                        if (at_types.indexOf(atype_key) != -1 || at_types == '') {
                            $(this).removeClass('hide');
                            $(this).find('input').removeAttr('disabled');
                        }
                    });
                }
            }
            {/literal}</script>

            {rlHook name='addListingAuthFormAfterRegistrationTpl'}
        </div>
    </div>
{/strip}</div>

{rlHook name='addListingAuthFormBottomTpl'}

<!-- user authorization form end -->
