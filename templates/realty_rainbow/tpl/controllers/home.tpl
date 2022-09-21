<!-- home tpl -->{strip}

{rlHook name='homeTop'}

{rlHook name='homeBottomTpl'}

<!-- removing account popup -->
{assign var='remove_account_variable' value='remove-account'}
{if isset($smarty.request.$remove_account_variable) && $smarty.request.id && $smarty.request.hash}
    {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
    {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}
    {addJS file=$rlTplBase|cat:'components/account-removing/_account-removing.js'}

    <script class="fl-js-dynamic">
    $(function(){literal}{{/literal}
        flAccountRemoving.init('{$smarty.request.id}', '{$smarty.request.hash}');
    {literal}}{/literal});
    </script>
{/if}
<!-- removing account popup end -->

<!-- Showing the popup for the agent by link with the invite -->
{if $agentInviteInfo}
    {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
    {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}

    <script class="fl-js-dynamic">
        const invite = {ldelim}
            'Agent_ID': {$agentInviteInfo.Agent_ID},
            'Code'    : '{$agentInviteInfo.Invite_Code}',
            'Status'  : '{$agentInviteInfo.Status}',
        {rdelim},
            loginUrl        = '{pageUrl key='login' vars='agent-invite='|cat:$agentInviteInfo.Invite_Code}',
            registrationUrl = '{pageUrl key='registration' vars='agent-invite='|cat:$agentInviteInfo.Invite_Code}';

        {literal}
        $(function () {
            if (isLogin && rlAccountInfo.ID !== invite.Agent_ID) {
                printMessage('error', lang.deny_use_invite);
                return;
            }

            if (invite.Status !== 'pending') {
                printMessage('error', lang.invite_is_invalid);
                return;
            }

            $('body').popup({
                width     : '360',
                click     : false,
                caption   : lang.notice,
                content   : `<div>${lang.confirmation_invite_notice}</div>`,
                navigation: {
                    okButton: {
                        text: '{/literal}{$lang.rl_accept}{literal}',
                        onClick: function(popup) {
                            let $okButton = $(this);
                            $okButton.val(lang.loading).addClass('disabled').prop('disabled', true);

                            if (isLogin) {
                                flUtil.ajax(
                                    {mode: 'acceptAgentInvite', key: invite.Code},
                                    function(response, status) {
                                        $okButton.val(lang.resend_invite).removeClass('disabled').removeProp('disabled');

                                        if (status === 'success' && response.status === 'OK') {
                                            printMessage('notice', lang.agency_invite_accepted);
                                        } else {
                                            printMessage('error', lang.system_error);
                                        }

                                        popup.close();
                                    }
                                );
                            } else {
                                location.href = invite.Agent_ID ? loginUrl : registrationUrl;
                            }
                        }
                    },
                    cancelButton: {
                        text: '{/literal}{$lang.decline}{literal}',
                        onClick: function(popup) {
                            let $okButton = $(this);
                            $okButton.val(lang.loading).addClass('disabled').prop('disabled', true);

                            flUtil.ajax(
                                {mode: 'declineAgentInvite', key: invite.Code},
                                function(response, status) {
                                    $okButton.val(lang.resend_invite).removeClass('disabled').removeProp('disabled');

                                    if (status === 'success' && response.status === 'OK') {
                                        printMessage('notice', lang.agency_invite_declined);
                                    } else {
                                        printMessage('error', lang.system_error);
                                    }

                                    popup.close();
                                }
                            );
                        }
                    }
                }
            })
        });
    {/literal}</script>
{/if}
<!-- Showing the popup for the agent by link with the invite end -->

{/strip}
<!-- home tpl end -->
