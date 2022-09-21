<!-- MultiField dataEntries.tpl -->

<script>
{literal}

$(function(){
    var multi_formats = '{/literal}{','|implode:$multi_format_keys}{literal}'.split(',');
    var selector = '';

    for (var i in multi_formats) {
        selector += 'option[value=' + multi_formats[i] + '],'
    }

    selector = selector.substring(0, selector.length - 1);

    $('#additional_options > div:not(#field_select)').find('select[name=data_format]').find(selector).remove();
});

{/literal}
</script>

<!-- MultiField dataEntries.tpl end -->
