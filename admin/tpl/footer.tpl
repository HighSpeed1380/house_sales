    </td>
</tr>
<!-- footer -->
<tr>
    <td id="footer" valign="bottom">
        <div>&copy; <a href="{$lang.flynax_url}">{$lang.copy_rights}</a> <b>{$lang.version} {$config.rl_version}</b></div>
    </td>
</tr>
<!-- footer end -->
</table>
<!-- additional JS -->
<script type="text/javascript">
var menu_collapsed = {if $smarty.cookies.ap_menu_collapsed == 'true'}true{else}false{/if};
var error_fields = Array();
{if !empty($error_fields)}
    {foreach from=$error_fields item='error_field'}
        error_fields.push('{$error_field}');
    {/foreach}
{/if}

{literal}
$(document).ready(function(){
    $("input.numeric").numeric();
    flynax.tabs({/literal}{if $smarty.get.action == 'view'}true{/if}{literal});
    flynax.copyPhrase();

    var pattern = /[\w]+\[(\w{2})\]/i;

    /* force error fields */
    for( var i = 0; i < error_fields.length; i++ )
    {
        if ( pattern.test(error_fields[i]) )
        {
            $('input[name="'+error_fields[i]+'"]').parent().parent().find('ul.tabs li[lang='+error_fields[i].match(pattern)[1]+']').addClass('error');
            $('input[name="'+error_fields[i]+'"]').click(function(){
                $(this).parent().parent().find('ul.tabs li[lang='+$(this).attr('name').match(pattern)[1]+']').removeClass('error');
            });
            $('textarea[name="'+error_fields[i]+'"]').parent().parent().parent().find('ul.tabs li[lang='+error_fields[i].match(pattern)[1]+']').addClass('error');
            $('textarea[name="'+error_fields[i]+'"]').click(function(){
                $(this).parent().parent().parent().find('ul.tabs li[lang='+$(this).attr('name').match(pattern)[1]+']').removeClass('error');
            });
        }

        $('input[name="'+error_fields[i]+'"],textarea[name="'+error_fields[i]+'"],select[name="'+error_fields[i]+'"]').addClass('error');
        $('input[name="'+error_fields[i]+'"],textarea[name="'+error_fields[i]+'"],select[name="'+error_fields[i]+'"]').focus(function(){
            $(this).removeClass('error');
        });

        if ( $('input[name^="'+error_fields[i]+'"]:last').attr('type') == 'checkbox' || $('input[name^="'+error_fields[i]+'"]:last').attr('type') == 'radio' )
        {
            $('input[name^="'+error_fields[i]+'"]:last').closest('table:not(.form,.sTable)').addClass('error');
            $('input[name^="'+error_fields[i]+'"]:last').closest('table:not(.form,.sTable)').click(function(){
                $(this).removeClass('error');
            });
        }
    }
});
{/literal}
</script>
<!-- additional JS end -->

<script src="{$rlBase}js/util.js"></script>
<script>flUtil.init();</script>

<script>
    $('select.select-autocomplete').each(function () {ldelim}
        flynax.addAutocompleteForDropdown($(this));
    {rdelim});
</script>

{rlHook name='apTplFooter'}

</body>
</html>
