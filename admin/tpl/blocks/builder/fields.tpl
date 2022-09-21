{if !empty($fields)}
    {foreach from=$fields item='field'}
        {if !$field.hidden}
            {assign var='f_type' value=$field.Type}
            <div class="field_obj{if $field.Status == 'approval'} field_inactive{/if}" style="{if $field.hidden}display: none;{/if}" id="field_{$field.ID}">
                <div class="field_title" title="{$field.name}{if $field.Status == 'approval'} ({$lang.approval}){/if}">
                    <div class="title">{$field.name}</div>
                    <span class="b_field_type">{$l_types.$f_type|truncate:25:"...":true}</span>
                </div>
            </div>
        {/if}
    {/foreach}
{else}
    <div class="form_default">
        <center>{$lang.no_fields}</center>
    </div>
{/if}
