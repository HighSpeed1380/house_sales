<!-- contact seller form tpl -->

{php}
global $captcha_box_id, $rlSmarty;
$captcha_box_id = $captcha_box_id ? $captcha_box_id + 1 : 1;
$rlSmarty->assign('captcha_box_id', $captcha_box_id);
{/php}

<form name="contact_owner"
      data-listing-id="{if $listing_data.ID}{$listing_data.ID}{/if}"
      data-box-id="{$captcha_box_id}"
      data-account-id="{if $account.ID}{$account.ID}{else}0{/if}"
      class="w-100"
      method="post"
      action="">
    {if $isLogin}
        <div class="submit-cell">
            <textarea {if $allow_send_message}id="contact_owner_message_{$captcha_box_id}" name="contact_message"{/if} rows="{if $allow_send_message}6{else}4{/if}" placeholder="{$lang.message}" cols=""></textarea>
        </div>
        
        {rlHook name='contactSellerFormAboveMessage'}
    {else}
        <div class="submit-cell">
            <div class="field"><input class="w-100" placeholder="{$lang.name}" maxlength="100" type="text" name="contact_name" id="contact_name_{$captcha_box_id}" value="{$account_info.Full_name}" /><span></span></div>
        </div>
        <div class="submit-cell">
            <div class="field"><input class="w-100" placeholder="{$lang.mail}" maxlength="200" type="text" name="contact_email" id="contact_email_{$captcha_box_id}" value="{$account_info.Mail}" /><span></span></div>
        </div>
        <div class="submit-cell">
            <div class="field"><input class="w-100" placeholder="{$lang.contact_phone}" maxlength="30" type="text" name="contact_phone" id="contact_phone_{$captcha_box_id}" /><span></span></div>
        </div>

        {rlHook name='contactSellerFormAboveMessage'}

        <div class="submit-cell">
            <textarea placeholder="{$lang.message}" {if $allow_send_message}id="contact_owner_message_{$captcha_box_id}" name="contact_message"{/if} rows="{if $allow_send_message}5{else}3{/if}" cols=""></textarea>
        </div>

        {if $allow_send_message && $config.security_img_contact_seller}
            <div class="submit-cell">
                <div class="field">
                    {include file='captcha.tpl' captcha_id='contact_code_'|cat:$captcha_box_id no_caption=true no_hint=true}
                </div>
            </div>
        {/if}
    {/if}

    <script>
    {literal}

    $(function(){
        flynaxTpl.setupTextarea();
    });

    {/literal}
    </script>
    <div class="submit-cell buttons">
        <div class="field">
            <input type="submit" class="w-100" name="finish" value="{phrase key='contact_owner'}" data-phrase="{phrase key='contact_owner'}" />
            <input class="hide" type="reset" id="form_reset_{$captcha_box_id}" value="reset" />
        </div>
    </div>
</form>

<!-- contact seller form tpl end -->
