    {include file=$componentDir|cat:'call-owner/_floating-buttons.tpl'}

    {displayCSS mode='footer'}

    {displayJS}

    {include file=$componentDir|cat:'call-owner/_popup-interface.tpl'}
    {include file=$componentDir|cat:'contact-owner/_contact-owner.tpl'}

    <script>{literal}
        $(function () {
            flUtil.loadScript(rlConfig.tpl_base + 'js/form.js', function () {
                $('select.select-autocomplete').each(function () {
                    flForm.addAutocompleteForDropdown($(this));
                });

                $('.show-phone').click(function () {
                    let $phone = $(this).parent().find('.hidden-phone');
                    flForm.showHiddenPhone($phone, $phone.data('entity-id'), $phone.data('entity'), $phone.data('field'));
                });
            });
        });
    {/literal}</script>

    {if $plugins.massmailer_newsletter && false !== $tpl_settings.name|strpos:'_nova_'}
        <script>{literal}
            (function(){
                $('#nova-newsletter-cont').append($('#tmp-newsletter > div'));
                $('#nova-newsletter-cont .newsletter_name').val('Guest');
            })();
        {/literal}</script>
    {/if}
</body>
</html>
