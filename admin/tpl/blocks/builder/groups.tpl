{if !empty($groups)}
    {foreach from=$groups item='group'}
        {if !$group.hidden}
            <div class="group_obj{if $group.Status == 'approval'} group_inactive{/if}" style="{if $group.hidden}display: none;{/if}" id="group_{$group.ID}">
                <div class="group_title" {if $group.Status == 'approval'}title="{$group.name} ({$lang.approval})"{/if}>{$group.name}</div>
                <div class="group_fields_container hide">
                    <div class="group_hint">{$lang.drop_field_here}</div>
                </div>
            </div>
        {/if}
    {/foreach}
{else}
    <div class="form_default">
        {$lang.no_groups}
    </div>
{/if}
