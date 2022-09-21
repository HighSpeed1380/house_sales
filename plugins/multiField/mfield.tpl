{if $field.Condition|in_array:$multi_format_keys}
    {assign var='field_key' value=$field.Key}
    {assign var='head_field_key' value=$field.Key|regex_replace:'/(_level[0-9]+)$/':''}
    {if $sf_key}
        {assign var='head_field_key' value=$head_field_key|cat:'|'|cat:$sf_key}
    {/if}
    {assign var='data_key' value='location_listing_fields'}
    {if $data_mode == 'account'}
        {assign var='data_key' value='location_account_fields'}
    {/if}

    <script>
        if (typeof mfFields['{$head_field_key}'] == 'undefined') {literal} { {/literal}
            mfFields['{$head_field_key}'] = [];
            mfFieldVals['{$head_field_key}'] = [];
        {literal} } {/literal}

        mfFields['{$head_field_key}'].push('{$field_key}');

        {if $smarty.post.$mf_form_prefix[$field_key]}
            mfFieldVals['{$head_field_key}']['{$field_key}'] = '{$smarty.post.$mf_form_prefix[$field_key]}';
            {assign var='mf_data_source' value='post'}
        {elseif $smarty.post.$field_key}
            mfFieldVals['{$head_field_key}']['{$field_key}'] = '{$smarty.post.$field_key}';
            {assign var='mf_data_source' value='post'}
        {elseif $geo_filter_data.applied_location
            && $geo_filter_data[$data_key][$field_key]
            && $geo_filter_data.is_filtering
            && $mf_data_source != 'post'}
            mfFieldVals['{$head_field_key}']['{$field_key}'] = '{$geo_filter_data[$data_key][$field_key]}';
        {/if}
    </script>
{/if}
