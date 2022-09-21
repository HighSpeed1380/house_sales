<!-- messages area DOM -->

<table class="table">
{foreach from=$messages item='message' name='messagesF'}
    <tr class="body{if $message.Hide} removed{/if}{if $message.From} hlight{/if}">
        <td style="width: 60px;padding-left: 0;" valign="top" align="center">
            <span title="{if $message.From}{$contact.Full_name}{else}{$lang.me}{/if}">
                {assign var="avatar_src" value=$rlTplBase|cat:'img/blank.gif'}
                {assign var="avatar_special_attrs" value='class="avatar40"'}

                {if $message.From && $contact.Photo}
                    {assign var="avatar_src" value=$smarty.const.RL_FILES_URL|cat:$contact.Photo}
                    {assign var="avatar_special_attrs" value='style="width: 36px; height: auto;" class="thumbnail"'}
                {/if}

                <img alt="" {$avatar_special_attrs} src="{$avatar_src}" />
            </span>

            {if !$message.From}
                <div>{$lang.me}</div>
            {/if}
        </td>
        <td class="divider"></td>
        <td valign="top" class="last">
            <table class="sTable">
            <tr>
                <td class="message_cell">
                    <div class="message_content_lim">{$message.Message|nl2br|replace:'\n':'<br />'}</div>
                    <div class="message_date">
                        {$message.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                        {rlHook name='apTplMessagesAfterMessage'}

                        {$message.Date|date_format:'%H:%M'}
                        {if $message.Hide}
                            {assign var='replace_name' value=`$smarty.ldelim`name`$smarty.rdelim`}
                            <span style="padding: 0 10px;" class="red" title="{$lang.removed_by|replace:$replace_name:$contact.Full_name}">{if ($message.Remove == 'from' && $message.From == 0) || ($message.Remove == 'to' && $message.To == 0)}{$lang.removed_by|replace:'[name]':$account_info.Full_name} ({$lang.administrator}){else}{$lang.removed_by|replace:$replace_name:$contact.Full_name}{/if}</span>
                        {/if}
                    </div>
                </td>
                <td class="ralign">
                    <input {if $checked_ids && $message.ID|in_array:$checked_ids}checked="checked"{/if}
                           type="checkbox"
                           name="del_mess"
                           class="del_mess"
                           id="message_{$message.ID}"
                    />
                </td>
            </tr>
            </table>
        </td>
    </tr>
{/foreach}
</table>

<!-- messages area DOM end -->
