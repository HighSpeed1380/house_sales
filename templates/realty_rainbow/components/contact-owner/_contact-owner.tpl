<!-- Contact Owner buttons handler -->

<div class="d-none hidden-contact-form">
    <div class="tmp-dom w-100">
        {if $allow_send_message}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'contact_seller_form.tpl'}
        {else}
            <div class="restricted-content">
                {if $isLogin}
                    <p>{$lang.contact_form_not_available}</p>
                    <span>
                        <a class="button" title="{$lang.registration}" href="{pageUrl key='my_profile'}#membership">{$lang.change_plan}</a>
                    </span>
                {else}
                    <p style="margin-bottom: 20px;">{$lang.contact_owner_hint}</p>
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'login_modal.tpl'}
                {/if}
            </div>
        {/if}
    </div>
</div>

{if $allow_send_message}
<script>
{literal}

$('body').on('submit', 'form[name=contact_owner]', function(){
    var $form = $(this);
    var $button = $form.find('input[type=submit]');
    var listingID = $form.data('listing-id');
    var accountID = $form.data('account-id');
    var boxID = $form.data('box-id');

    $button.val(lang['loading']);

    if ($form.closest('.popup').length) {
        $form.find('input,textarea').focus(function(){
            $(this).removeClass('error');
        });
    }

    var data = {
        mode:         'contactOwner',
        name:          $form.find('input[name=contact_name]').val(),
        email:         $form.find('input[name=contact_email]').val(),
        phone:         $form.find('input[name=contact_phone]').val(),
        message:       $form.find('textarea[name=contact_message]').val(),
        security_code: $form.find('input[name^=security_code]').val(),
        listing_id:    listingID,
        account_id:    accountID,
        box_index:     boxID
    };
    flUtil.ajax(data, function(response, status){
        if (status == 'success') {
            if (response.status == 'ok') {
                $('#modal_block > div.inner > div.close').trigger('click');
                $form.closest('.popup').find('.close').trigger('click');

                printMessage('notice', response.message_text);

                $form.find('img[class^=contact_code_]').trigger('click');
                $form.find('input[type=reset]').trigger('click');
            } else {
                if ($form.closest('.popup').length) {
                    $form.find(response.error_fields).addClass('error');
                } else {
                    printMessage('error', response.message_text, response.error_fields);
                }
            }

            $button.val($button.data('phrase'));
        } else {
            printMessage('error', lang['system_error']);
        }
    }, true);

    return false;
});

{/literal}
</script>
{/if}

<script>
lang['contact_owner'] = "{phrase key='contact_owner'}";
lang['login'] = "{phrase key='login'}";

{literal}

flUtil.loadStyle(rlConfig['tpl_base'] + 'components/popup/popup.css');
flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
    $('.contact-owner').popup({
        width: 320,
        caption: lang['contact_owner'],
        content: $('.hidden-contact-form > .tmp-dom').clone(true, true),
        onShow: function($interface){
            flynaxTpl.setupTextarea();
        }
    });

    $('a.login').popup({
        caption: lang['login'],
        content : $('#login_modal_source > .tmp-dom').clone(true, true),
        width  : 320
    });
});

{/literal}
</script>

<!-- Contact Owner buttons handler end -->
