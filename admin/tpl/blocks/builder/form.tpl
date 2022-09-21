{if empty($relations)}
    <div class="form_hint">{$lang.drop_group_field_here}</div>
{else}
    {foreach from=$relations item='group' name='fGroups'}
    {if $group.Group_ID}
        <div id="group_{$group.Group_ID}" class="group_obj{if $group.Status == 'approval'} group_inactive{/if}" {if $group.Status == 'approval'}title="{$group.name} ({$lang.approval})"{/if}>
            <div class="group_title">{$group.name}</div>
            <div class="group_fields_container">
                {if $group.Fields}
                    {foreach from=$group.Fields item='field' name='fFields'}
                    {assign var='f_type' value=$field.Type}
                        <div class="field_obj{if $field.Status == 'approval'} field_inactive{/if}" id="field_{$field.ID}">                  
                            <div class="field_title" title="{$field.name}{if $field.Status == 'approval'} ({$lang.approval}){/if}">
                                <div class="title">{$field.name}</div>
                                <span class="b_field_type">{$l_types.$f_type}</span>
                            </div>
                            
                            
                            {*if !$smarty.foreach.fFields.last}
                                <div onclick="xajax_sort('{$group.ID}', '{$field.ID}', 'down', '{$kind_info.ID}');" class="sort_down" title="{$lang.move_down}"></div>
                            {else}
                                <div class="sort_down" style="background: none;cursor: default;"></div>
                            {/if}
                            {if !$smarty.foreach.fFields.first}
                                <div onclick="xajax_sort('{$group.ID}', '{$field.ID}', 'up', '{$kind_info.ID}');" class="sort_up" title="{$lang.move_up}"></div>
                            {/if*}
                            
                        </div>
                    {/foreach}
                {else}
                    <div class="group_hint">{$lang.drop_field_here}</div>
                {/if}
            </div>
        </div>
    {else}
        {assign var='f_type' value=$group.Fields.Type}
        <div class="field_obj{if $group.Fields.Status == 'approval'} field_inactive{/if}" id="field_{$group.Fields.ID}">
            <div class="field_title" title="{$group.Fields.name}{if $group.Fields.Status == 'approval'} ({$lang.approval}){/if}">
                <div class="title">{$group.Fields.name}</div>
                <span class="b_field_type">{$l_types.$f_type}</span>
            </div>
            
            
            {*if !$smarty.foreach.fGroups.last}
                <div onclick="xajax_sort('{$group.ID}', '', 'down', '{$kind_info.ID}');" class="sort_down" title="{$lang.move_down}"></div>
            {else}
                <div class="sort_down" style="background: none;cursor: default;"></div>
            {/if}
            {if !$smarty.foreach.fGroups.first}
                <div onclick="xajax_sort('{$group.ID}', '', 'up', '{$kind_info.ID}');" class="sort_up" title="{$lang.move_up}"></div>
            {/if*}
        </div>
    {/if}
    {/foreach}
{/if}
