<!-- My Agents tpl -->

<div class="content-padding">
    <h2 class="mb-3">{$lang.invite_for_new_agents}</h2>

    <div class="mb-3">
        <form action="?send-invite=1" method="post" name="add-invite-form" class="row no-gutters">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <input class="w-100" type="text" placeholder="{$lang.placeholder_invite_agent}" name="agent-email"/>
            </div>

            <div class="col-md-4 mt-2 mt-md-0">
                <input type="submit" value="{$lang.send}" class="w-100 w-md-auto ml-0 ml-md-2" />
            </div>
        </form>
    </div>

    {if $invites}
        {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
        {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}

        <h2 class="mb-3">{$lang.invites}</h2>

        <div class="invites list-table content-padding mb-5">
            <div class="header">
                <div class="center" style="width: 60px;">{$lang.id}</div>
                <div>{$lang.name}/{$lang.mail}</div>
                <div>{$lang.date}</div>
                <div>{$lang.status}</div>
                <div>{$lang.actions}</div>
            </div>

            {foreach from=$invites item='invite' name='invitesF'}
                <div class="row">
                    <div data-caption="{$lang.id}" class="center">{$invite.ID}</div>
                    <div data-caption="{$lang.name}/{$lang.mail}" class="no-flex default">
                        {if $invite.Agent.Full_name && $invite.Agent.Photo}
                            <a class="show-agent-thumbnail" href="javascript://" title="{$invite.Agent.Full_name}">
                                <img src="{$smarty.const.RL_FILES_URL}{$invite.Agent.Photo}"
                                     alt="{$invite.Agent.Full_name}"
                                     {if $invite.Agent.Photo_x2}
                                        data-x2="{$smarty.const.RL_FILES_URL}{$invite.Agent.Photo_x2}"
                                     {/if}
                                     width="32"
                                     height="32"
                                     style="object-fit: cover"
                                >
                            </a>
                        {/if}

                        {if $invite.Agent.Personal_address}
                            <a href="{$invite.Agent.Personal_address}" target="_blank" title="{$invite.Agent.Full_name}">
                        {/if}
                        {if $invite.Agent.Full_name}{$invite.Agent.Full_name}{else}{$invite.Agent_Email}{/if}
                        {if $invite.Agent.Personal_address}</a>{/if}
                    </div>
                    <div data-caption="{$lang.date}" class="no-flex default">
                        {$invite.Created_Date|date_format:$smarty.const.RL_DATE_FORMAT}
                    </div>
                    <div data-caption="{$lang.status}" class="no-flex default statuses">
                        {if $invite.Status === 'accepted'}
                            {assign var='inviteStatus' value='active'}
                        {elseif $invite.Status === 'declined'}
                            {assign var='inviteStatus' value='expired'}
                        {else}
                            {assign var='inviteStatus' value=$invite.Status}
                        {/if}

                        <span class="{$inviteStatus}">{$lang[$invite.Status]}</span>

                        {if $invite.Status === 'accepted'}
                            ({$invite.Accepted_Date|date_format:$smarty.const.RL_DATE_FORMAT})
                        {elseif $invite.Status === 'declined'}
                            ({$invite.Declined_Date|date_format:$smarty.const.RL_DATE_FORMAT})
                        {/if}
                    </div>
                    <div data-caption="{$lang.actions}" class="no-flex default">
                        {if $invite.Status === 'pending'}
                            <a class="invite__resend" data-id="{$invite.ID}" href="javascript:">{$lang.resend_invite}</a> |
                        {/if}
                        <a class="red invite__remove" data-id="{$invite.ID}" href="javascript:">{$lang.remove}</a>
                    </div>
                </div>
            {/foreach}
        </div>

        {paging
            calc=$pInfo.calc
            total=$invites|@count
            current=$pInfo.current
            per_page=$config.dealers_per_page
            url=$pInfo.paginationUrl
        }
    {else}
        <div class="info">{$lang.no_agents}</div>
    {/if}
</div>

<script class="fl-js-dynamic">
    lang.mail              = '{$lang.mail}';
    lang.notice_field_empty = '{$lang.notice_field_empty}';
    lang.notice_bad_email  = '{$lang.notice_bad_email}';

    {literal}
    $(function () {
        $('form[name="add-invite-form"]').submit(function () {
            let $email  = $('[name="agent-email"]'), error;

            if ($email.val() === '') {
                error = lang.notice_field_empty.replace('{field}', `<b>"${lang.mail}"</b>`);
                printMessage('error', error, 'agent-email');
                return false;
            } else if (!flUtil.isEmail($email.val())) {
                error = lang.notice_bad_email.replace('{field}', `<b>"${lang.mail}"</b>`);
                printMessage('error', error, 'agent-email');
                return false;
            }

            $(this).find('[type="submit"]').val(lang.loading).addClass('disabled').prop('disabled', true);
        });

        {/literal}{if $invites}{literal}
            $('a.invite__resend').popup({
                caption: lang.notice,
                content: lang.resend_confirm,
                navigation: {
                    okButton: {
                        text: lang.resend_invite,
                        onClick: function(popup) {
                            let $okButton = $(this);
                            $okButton.val(lang.loading).addClass('disabled').prop('disabled', true);

                            flUtil.ajax(
                                {mode: 'resendAgentInvite', id: popup.$element.data('id')},
                                function(response, status) {
                                    $okButton.val(lang.resend_invite).removeClass('disabled').removeProp('disabled');

                                    if (status === 'success' && response.status === 'OK') {
                                        printMessage('notice', lang.invite_resent_successfully);
                                    } else {
                                        printMessage('error', lang.system_error);
                                    }

                                    popup.close();
                                }
                            );
                        }
                    },
                    cancelButton: {text: lang.cancel}
                }
            });

            $('a.invite__remove').popup({
                caption: lang.notice,
                content: lang.delete_invite_confirmation,
                navigation: {
                    okButton: {
                        text: lang.delete,
                        onClick: function(popup) {
                            let $okButton = $(this);
                            $okButton.val(lang.loading).addClass('disabled').prop('disabled', true);

                            flUtil.ajax(
                                {mode: 'deleteAgentInvite', id: popup.$element.data('id')},
                                function(response, status) {
                                    $okButton.val(lang.delete).removeClass('disabled').removeProp('disabled');

                                    if (status === 'success' && response.status === 'OK') {
                                        location.href = '{/literal}{pageUrl key='my_agents'}{literal}';
                                    } else {
                                        printMessage('error', lang.system_error);
                                    }

                                    popup.close();
                                }
                            );
                        }
                    },
                    cancelButton: {text: lang.cancel}
                }
            });
        {/literal}{/if}{literal}

        $('.show-agent-thumbnail').click(function () {
            let $img = $(this).find('img');
            let photoSource = $img.data('x2') ? $img.data('x2') : $img.attr('src');

            $img.popup({
                click  : false,
                caption: $img.attr('alt'),
                content: $('<img>', {src: photoSource, width: '200px', height: '100%'}),
            });
        });
    });
{/literal}</script>

<!-- My Agents tpl end -->
