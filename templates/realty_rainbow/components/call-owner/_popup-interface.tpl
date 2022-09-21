<!-- Popup interface -->

<script id="call_owner_popup_content" type="text/x-jsrender">
<div class="d-flex flex-column">
    <div class="pb-3">
        [%if main_phone%]
            <a href="tel:[%:main_phone%]" style="font-size: 1.750em;">[%:main_phone%]</a>
        [%else%]
            {$lang.not_available}
        [%/if%]
    </div>
    <div class="pb-1">[%:full_name%]</div>
    <div class="date">[%:seller_data%]</div>

    [%if phones%]
    <div class="mt-4">
        <h4>[%:phrases.call_owner_additional_numbers%]</h4>
        [%for phones%]
            <div class="pt-1">
                <a href="tel:[%:%]">[%:%]</a>
            </div>
        [%/for%]
    </div>
    [%/if%]
</div>
</script>

<script>
{literal}

flUtil.loadStyle(rlConfig['tpl_base'] + 'components/popup/popup.css');
flUtil.loadScript([
        rlConfig['tpl_base'] + 'components/popup/_popup.js',
        rlConfig['libs_url'] + 'javascript/jsRender.js'
    ], function(){
        $('body').on('click', '.call-owner', function(){
            $(this).popup({
                click: false,
                width: 320,
                caption: lang['call_owner'],
                content: $('<div>').css('height', '90px').text(lang['loading']),
                onShow: function($interface){
                    var listingID = this.$element.data('listing-id');
                    var data = {
                        mode: 'getCallOwnerData',
                        listingID: listingID
                    };
                    flUtil.ajax(data, function(response, status){
                        if (status == 'success' && response.status == 'OK') {
                            var $content = $interface.find('.body');

                            $content.empty();
                            $content.append($('#call_owner_popup_content').render(response.results));

                            flUtil.ajax({mode: 'savePhoneClick', listingID: listingID}, function () {});
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                    }, true);
                }
            });
        });
    }
);

{/literal}
</script>

<!-- Popup interface end -->
