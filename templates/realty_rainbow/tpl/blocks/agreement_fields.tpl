<!-- agreement fields -->

{foreach from=$agreement_fields item='ag_field'}
    <div class="ag_fields {strip}
        {if $account_types|@count > 1
            && (!$selected_atype
                || ($ag_field.Values && $selected_atype && $ag_field.Values|strstr:$selected_atype == false)
            )
        }
            hide
        {/if}{/strip}" 
        data-types="{$ag_field.Values}">
        <label style="padding: 10px 0 5px;">
            <input value="1"
                type="checkbox" 
                name="profile[accept][{$ag_field.Key}]" 
                {if isset($smarty.post.profile.accept[$ag_field.Key])}checked="checked"{/if} 
                {if $selected_atype && $smarty.session.page_info.current != 'add_listing'}disabled="disabled"{/if} 
                {if $data_form}form="{$data_form}"{/if} />
            &nbsp;{$lang.agree}

            <a target="_blank" href="{pageUrl key=$ag_field.Default}">
                {phrase key='pages+name+'|cat:$ag_field.Default}
            </a>
        </label>
    </div>
{/foreach}

<!-- agreement fields end -->
