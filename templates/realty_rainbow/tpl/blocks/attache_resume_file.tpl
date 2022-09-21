<!-- attache resume tpl -->

<div class="submit-cell">
    <div class="field">
        <div class="file-input">
            <input type="hidden" name="resume_file" value="" />
            <input class="file" id="attached_file_{$captcha_box_id}" type="file" name="resume" />
            <span>{$lang.choose}</span>
            <div><input autocomplete="off" type="text" class="file-name" placeholder="{$lang.attach_resume}" /></div>
        </div>
    </div>
</div>

{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.ui.widget.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.iframe-transport.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload.js'}
{addJS file=$smarty.const.RL_LIBS_URL|cat:'jquery/upload/jquery.fileupload-ui.js'}

<script class="fl-js-static">
{literal}

$(function(){
    "use strict";

    $('input[name=resume]').closest('form').each(function(){
        var self = this;
        var onSubmit = $(this).attr('onsubmit').replace('return false;', '');
        var required_fields = ['contact_name', 'contact_email', 'contact_message', 'security_code'];
        var submit_data = false;
        var $button = $(this).find('input[name=finish]');

        // File upload handler
        $(this).find('input[name=resume]').fileupload({
            url: rlConfig['ajax_url'] + '?mode=attachResume', 
            dataType: 'json',
            autoUpload: false,
            singleFileUploads: true,
            add: function(e, data) {
                $(self).find('.file-name').val(data.files[0].name);
                submit_data = data;
            },
            getFilesFromResponse: function(data) {
                if (data.result.status == 'OK') {
                    eval(onSubmit.replace('this', 'self'));
                } else {
                    printMessage('error', lang['system_error']);
                    $button.val($button.attr('accesskey'));
                }
            },
            fail: function(e, data) {
                $button.val($button.attr('accesskey'));
                printMessage('error', lang['system_error']);
            }
        });

        // On form submit handler
        $(this).attr('onsubmit', '').submit(function(e){
            // count failed fields
            var fail_count = $(this).find('*[name=' + required_fields.join('],*[name=') + ']').filter(function(){
                return !$(this).val();
            }).length;
            var file_name = $(this).find('.file-name').val();
            
            if (fail_count > 1 || file_name == '') {
                eval(onSubmit);
            } else {
                var valid_ext = ['jpg', 'png', 'pdf', 'doc', 'docx', 'zip', 'rar'];
                var file_ext = file_name.substr(file_name.lastIndexOf('.') + 1);

                if ($.inArray(file_ext, valid_ext) == -1){
                    printMessage(
                        'error', 
                        lang['invalid_file_extension'].replace('{ext}', '"' + valid_ext.join(', ') + '"'),
                        '#' + $(this).find('input.file-name').attr('id')
                    );
                } else {
                    $button.val(lang['loading']);
                    submit_data.submit();
                }
            }

            e.preventDefault();
        });
    });
});

{/literal}
</script>

<!-- attache resume tpl end -->
