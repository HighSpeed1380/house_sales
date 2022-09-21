<!-- my messages tpl -->

{if !empty($contact)}
    {if empty($messages)}
        <div class="text-message">{$lang.no_messages}</div>
    {else}
        {if $contact.ID < 0}
            {addCSS file=$rlTplBase|cat:'components/popup/popup.css'}
            {addJS file=$rlTplBase|cat:'components/popup/_popup.js'}
        {/if}

        <div id="messages_cont">
            <ul id="messages_area">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'messages_area.tpl'}
            </ul>
        </div>

        <div class="send-controls">
            <textarea rows="4" cols="" id="message_text"></textarea>
            <div><input id="send_message" type="button" value="{$lang.send}" /></div>
        </div>

        <script type="text/javascript">
            var period = {if $config.messages_refresh}{$config.messages_refresh} * 1000{else}10000{/if};
            var message_count = 0;
            var contactID = {$contact.ID};
            var isAdminContact = {if $contact.Admin}1{else}0{/if};
            var visitorEmail = {if $smarty.get.visitor_mail}"{$smarty.get.visitor_mail}"{else}null{/if};
            var visitorName = {if $contact.Full_name}"{$contact.Full_name}"{else}null{/if};

            lang['send_message'] = "{$lang.send_message}";
            lang['notice_message_sent'] = "{$lang.notice_message_sent}";

            {literal}
            $(function(){
                message_count = $('ul#messages_area > li').length;
                $textarea = $('#message_text');

                $('#messages_cont').mCustomScrollbar({
                    advanced: {	updateOnContentResize: true }
                });
                $('#messages_cont').mCustomScrollbar('scrollTo', 'bottom');

                messageRemoveHandler();

                $textarea.textareaCount({
                    'maxCharacterSize': rlConfig['messages_length'],
                    'warningNumber': 20
                });

                $textarea.keydown( function(e) {
                    if (e.ctrlKey && e.keyCode == 13) {
                        xajax_sendMessage(contactID, $(this).val(), isAdminContact);
                    }
                });

                flynaxTpl.setupTextarea();

                $('#send_message').click(function(){
                    if (!$textarea.val().trim()) {
                        return;
                    }

                    if (contactID > 0) {
                        xajax_sendMessage(contactID, $textarea.val(), isAdminContact);
                    } else {
                        $('body').popup({
                            click: false,
                            content   : lang.confirm_sent_message_to_visitor,
                            caption   : lang.notice,
                            navigation: {
                                okButton: {
                                    text: lang.send_message,
                                    onClick: function(popup){
                                        var $button = $(this);

                                        $button
                                            .addClass('disabled')
                                            .attr('disabled', true)
                                            .val(lang.loading);

                                        var data = {
                                            mode: 'sendMessageToVisitor',
                                            message: $textarea.val(),
                                            email: visitorEmail,
                                            name: visitorName
                                        };

                                        flUtil.ajax(data, function(response, status) {
                                            if (status == 'success' && response && response.status === 'OK') {
                                                printMessage('notice', lang['notice_message_sent']);
                                                xajax_refreshMessagesArea(contactID, 0, visitorEmail, 0);
                                                $textarea.val('');
                                            } else {
                                                printMessage('error', lang.system_error);
                                            }

                                            $button
                                                .removeClass('disabled')
                                                .removeAttr('disabled')
                                                .val(lang.send_message);

                                            popup.close();
                                        });
                                    }
                                },
                                cancelButton: {text: lang.cancel, class: 'cancel'}
                            }
                        });
                    }
                });

                if (contactID >= 0) {
                    setInterval(function(){
                        xajax_refreshMessagesArea(contactID, 0, 0, isAdminContact);
                    }, period);
                }
            });

            var messageRemoveHandler = function() {
                $('#messages_area li > span').each(function(){
                    var id = $(this).parent().attr('id').split('_')[1];
                    $(this).flModal({
                        caption: '{/literal}{$lang.warning}{literal}',
                        content: '{/literal}{$lang.remove_message_notice}{literal}',
                        prompt: 'mRemoveMsg('+id+')',
                        width: 'auto',
                        height: 'auto'
                    });
                });
            }

            var checkboxControl = function(){
                messageRemoveHandler();

                var length = $('ul#messages_area > li').length;

                if ( length > message_count ) {
                    $('#messages_cont').mCustomScrollbar('scrollTo', 'bottom');
                }

                message_count = length;
            }

            var mRemoveMsg = function(id) {
                if ( id ) {
                    {/literal}xajax_removeMsg(id, {$contact.ID}, {if $contact.Admin}1{else}0{/if});{literal}
                }
            }

            {/literal}
        </script>

    {/if}

{else}

    {if !empty($contacts)}
        <div class="content-padding">
            <table class="list contacts-list">
                <tr class="header">
                    <td class="last">
                        <label><input class="inline" type="checkbox" id="check_all" /></label>
                    </td>
                    <td class="user">{$lang.user}</td>
                    <td>{$lang.message}</td>
                </tr>

                {assign var='replace' value=`$smarty.ldelim`name`$smarty.rdelim`}
                {foreach from=$contacts item='item' name='searchF' key='contact_id'}
                    {assign var='status_key' value=$item.Status}
                    <tr class="body" id="item_{$contact_id|replace:'@':''|replace:'.':''}">
                        <td>
                            <label><input type="checkbox" name="del_mess" class="inline del_mess {if $item.Admin}admin{/if}" id="contact_{$item.From}" {if $item.Visitor_mail}attr="{$item.Visitor_mail}"{/if} /></label>
                        </td>
                        <td valign="top">
                            <div class="picture{if !$item.Photo} no-picture{/if}">
                                <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?id={$item.From}{else}?page={$pageInfo.Path}&id={$item.From}{/if}{if $item.Admin}&administrator{/if}{if $item.Visitor_mail}&visitor_mail={$item.Visitor_mail}{/if}"
                                   title="{$lang.chat_with|replace:$replace:$item.Full_name}"
                                >
                                    <img class="account-picture"
                                         style="{strip}
                                        width:{if $item.Thumb_width}{$item.Thumb_width}{else}110{/if}px;
                                        height:{if $item.Thumb_height}{$item.Thumb_height}{else}100{/if}px;
                                    {/strip}"
                                         alt="{$item.Full_name}"
                                         src="{if $item.Photo}{$smarty.const.RL_FILES_URL}{$item.Photo}{else}{$rlTplBase}img/blank.gif{/if}"
                                            {if $item.Photo_x2}
                                                srcset="{$smarty.const.RL_FILES_URL}{$item.Photo_x2} 2x"
                                            {/if}
                                    />
                                    {if $item.Status == 'new' && $item.Count > 0}<span title="{$item.Count} {$lang.new_message}" class="new"></span>{/if}
                                </a>
                            </div>
                        </td>
                        <td class="info">
                            <div class="name">{$item.Full_name}{if $item.Admin} <span>({$lang.website_admin})</span>{/if}{if $item.Visitor_mail} <span>({$lang.website_visitor})</span>{/if} {if $item.Status == 'new'}<span title="{$item.Count} {$lang.new_message}" class="new"></span>{/if}</div>
                            <div class="date">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>

                            <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?id={$item.From}{else}?page={$pageInfo.Path}&id={$item.From}{/if}{if $item.Admin}&administrator{/if}{if $item.Visitor_mail}&visitor_mail={$item.Visitor_mail}{/if}">{$item.Message|nl2br|replace:'\n':'<br />'|truncate:120}</a>
                        </td>
                    </tr>
                {/foreach}
            </table>

            <div class="mass-actions">
                <a class="close remove_contacts" href="javascript:void(0)" title="{$lang.remove_selected_messages}">{$lang.remove_selected}</a>
            </div>
        </div>

        <script type="text/javascript">{literal}
            $(document).ready(function(){
                $('.del_mess').click(function(){
                    if ( $('.del_mess:checked').length == 0 ) {
                        $('#check_all').attr('checked', false);
                    }
                });

                $('#check_all').click(function(){
                    if ( $(this).is(':checked') ) {
                        $('.del_mess').prop('checked', true);
                    }
                    else {
                        $('.del_mess').prop('checked', false);
                    }
                });

                $('.remove_contacts').click(function(){
                    var ids = '';
                    var admin = false;

                    $('.del_mess').each(function(){
                        if ($(this).is(':checked')) {
                            if ($(this).attr('attr')) {
                                ids += ids ? ',' + $(this).attr('attr') : $(this).attr('attr');
                            } else {
                                admin = $(this).hasClass('admin');

                                ids += ids
                                    ? ',' + $(this).attr('id').split('_')[1] + (admin ? '_admin' : '')
                                    : $(this).attr('id').split('_')[1] + (admin ? '_admin' : '');
                            }
                        }
                    });

                    if (ids != '') {
                        $(this).flModal({
                            caption: '{/literal}{$lang.warning}{literal}',
                            content: '{/literal}{$lang.remove_contact_notice}{literal}',
                            prompt: 'xajax_removeContacts("' + ids + '")',
                            width: 'auto',
                            height: 'auto',
                            click: false
                        });
                    }
                });
            });
            {/literal}</script>
    {else}
        <div class="text-message">{$lang.no_messages}</div>
    {/if}
{/if}

<!-- my messages tpl end -->
