<!-- fieldBoundBoxes recount function -->

<tr class="body">
    <td class="list_td">{$lang.fb_recount_text}</td>
    <td class="list_td" align="center">
        <input id="fbbRecount" type="button" value="{$lang.recount}" style="margin: 0;width: 100px;" />

        <script>
            lang['recount'] = "{$lang.recount}";
            lang['fb_recount_in_progress'] = "{$lang.resize_in_progress}";

            {literal}

            var fbbRecountStack = 0;

            $(document).ready(function(){
                $('#fbbRecount').click(function(){

                    $(this).val(lang['loading']);
                    printMessage('notice', lang['fb_recount_in_progress']);

                    $.getJSON(rlConfig['ajax_url'], {item: 'fbbRecount', stack: fbbRecountStack}, function(response){
                        if (response.status == 'ok') {
                            $('#fbbRecount').val(lang['recount']);
                            printMessage('notice', lang['fb_listings_recounted']);
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                    });
                });
            });
            {/literal}
        </script>
    </td>
</tr>

<!-- fieldBoundBoxes recount function end -->
