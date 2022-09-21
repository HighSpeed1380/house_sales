
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: FORM.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

var flynaxForm = function(){
    this.auth = function(){
        $reg_inputs   = $('div.auth div.cell:first input:not([type=hidden])');
        $login_inputs = $('div.auth div.cell:last input:not([type=hidden])');

        $reg_inputs.on('keydown', function(){
            $login_inputs.val('');
        });
        $login_inputs.on('keydown', function(){
            $reg_inputs.val('');
        });
    }

    this.fields = function(){
        if (Object.keys(window.textarea_fields).length) {
            for (var name in window.textarea_fields) {
                if (window.textarea_fields[name].type == 'html') {
                    if (typeof CKEDITOR.instances[name] == 'undefined') {
                        flynax.htmlEditor(
                            [name],
                            textarea_fields[name].length
                            ?   [[
                                    'wordcount',
                                    {
                                        showParagraphs    : false,
                                        showWordCount     : false,
                                        showCharCount     : true,
                                        maxCharCount      : textarea_fields[name].length,
                                        countSpacesAsChars: true,
                                    }
                                ]]
                            : []
                        );
                    }
                } else {
                    if (!$('#' + name).next().hasClass('textarea_counter_default')) {
                        $('#' + name).textareaCount({
                            maxCharacterSize: window.textarea_fields[name].length,
                            warningNumber: 20
                        })
                    }
                }
            }
        }

        $('select.select-autocomplete').each(function () {
            flForm.addAutocompleteForDropdown($(this));
        });

        $('.numeric').numeric({decimal:rlConfig['price_separator']});
        flynax.phoneField();
    }

    this.typeQTip = function(){
        $('[name="register[type]"]').change(function() {
            $('img.qtip').hide();
            $('img.sc_' + $(this).val()).show();
        });
    }

    /**
    * Assign account location data to the same fields in listing form
    **/
    this.accountFieldSimulation = function(){
        var $switcher = $('input[name="f[account_address_on_map]"]');
        var $on_map = $('.on_map');

        if (!$switcher.length) {
            return;
        }

        var handler = function(edit_mode){
            var option = $switcher.filter(':checked').val();
            if (option == '1') {
                $('.on_map_data').each(function(){
                    var key = $(this).data('field-key');
                    $element = $('*[name="f[' + key + ']"]');

                    if (key.indexOf('_level') > 0) {
                        $element.find('option:gt(0)').remove();
                        var option = $('<option>')
                            .attr('selected', true)
                            .text($(this).val())
                            .val($(this).val());

                        $element.append(option);
                    } else {
                        $element.val($(this).val());
                    }

                    $on_map.find('input, textarea, select').attr('disabled', true).addClass('disabled');
                });
            } else if (option == '0' && !edit_mode) {
                $on_map.find('input, textarea').val('');
                $on_map.find('select').val(0);

                $on_map.find('input, textarea, select').attr('disabled', false).removeClass('disabled');
            }
        }

        $switcher.change(function(){
            handler();
        });

        handler(true);
    };

    /**
     * Applys custom action to file inputs with custom style
     */
    this.fileFieldAction = function(){
        // File input click handler
        $('.file-input input[type=file]')
            .unbind('change')
            .bind('change', function(){
                var path = $(this).val().split('\\');
                $(this).parent().find('input[type=text]')
                    .removeClass('error')
                    .val(path[path.length - 1]);
            });

        // Uploaded file remove handler
        flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
            var $interface = $('<span>')
                .text(lang['confirm_notice']);

            $('.file-data .remove-file')
                .unbind('click')
                .bind('click', function(){
                    var $container = $(this).closest('.file-data');
                    var field      = $container.data('field');
                    var value      = $container.data('value');
                    var type       = $container.data('type');
                    var parent     = $container.data('parent');

                    $(this).popup({
                        click: false,
                        content: $interface,
                        caption: lang['delete_file'],
                        navigation: {
                            okButton: {
                                text: lang['delete'],
                                onClick: function(popup){
                                    var $button = $(this);

                                    $button
                                        .addClass('disabled')
                                        .attr('disabled', true)
                                        .val(lang['loading']);

                                    if (value && type) {
                                        var data = {mode: 'deleteFile', field: field, value: value, type: type};
                                    } else {
                                        var data = {mode: 'deleteTmpFile', field: field, parent: parent};
                                    }

                                    flUtil.ajax(data, function(response, status){
                                        if (status == 'success' && response.status == 'OK') {
                                            $container.remove();
                                        } else {
                                            $button
                                                .removeClass()
                                                .attr('disabled', false)
                                                .val(lang['save']);

                                            printMessage('error', lang['system_error']);
                                        }

                                        popup.close();
                                    }, true);
                                }
                            },
                            cancelButton: {
                                text: lang['cancel'],
                                class: 'cancel'
                            }
                        }
                    });
                });
        });
    };

    /**
     * Hide/show "Rental Period" field depending on "Property for" state field
     *
     * @since 4.9.0
     */
    this.realtyPropType = function(selector, target_selector, parent_class) {
        var selector        = typeof selector != 'undefined' ? selector : '#sf_field_sale_rent span.custom-input input',
            target_selector = typeof target_selector != 'undefined' ? target_selector : '#sf_field_time_frame',
            parent_class    = typeof parent_class != 'undefined' ? parent_class : '.submit-cell';

        var $targetParent = $(target_selector).closest(parent_class);

        if (!$(selector).length || $targetParent.data('required')) {
            return;
        }

        $targetParent.hide();

        var action = function(input) {
            var $target = $(input).closest('form').find(target_selector);

            if ($target.length == 0)
                return;

            if (parseInt($(input).val()) == 2) {
                $target.closest(parent_class).fadeIn();
            } else {
                $target.closest(parent_class).fadeOut();
                $target.find('input').removeAttr('checked')
            }
        }

        $(selector).change(function(){
            action(this);
        });

        $(selector + ':checked').each(function(){
            action(this);
        });
    }

    /**
     * Add autocomplete option from the "Select2" library for dropdowns with more values
     *
     * @since 4.9.0
     *
     * @param $field - Element in DOM
     * @param width  - Width of element in percent
     */
    this.addAutocompleteForDropdown = function ($field, width) {
        if (!$field || $field.length === 0) {
            return;
        }

        if (!width) {
            if (rlPageInfo.key === 'search_on_map') {
                width = '100%';
            } else {
                // Set the width to 100% for dropdowns located in the boxes
                width = $field.parents('#controller_area').length ? 'resolve' : '100%';
            }
        }

        flUtil.loadStyle(rlConfig.tpl_base + 'components/select2/select2.css');
        flUtil.loadScript(rlConfig.libs_url + 'jquery/select2.min.js', function () {
            $field.select2({
                width   : width,
                language: {
                    noResults: function () {
                      return lang.field_autocomplete_no_results;
                    },
                }
            }).on('select2:select', function () {
                $field.trigger('focus');
            });
        });
    }

    /**
     * Show hidden phone
     *
     * @since 4.9.0
     *
     * @param $phone - Container with hidden phone
     * @param id     - Entity ID (ID of the listing/account)
     * @param entity - Phone of listing/account, possible values: "listing" and "account"
     * @param field   - Key of phone field
     */
    this.showHiddenPhone = function ($phone, id, entity, field) {
        if (!$phone.length || !id) {
            return;
        }

        entity = entity && ['listing', 'account'].indexOf(entity) > 0 ? entity : 'listing';
        let $showPhoneLink = $phone.next('.show-phone').length ? $phone.next('.show-phone') : null;

        if ($showPhoneLink) {
            $showPhoneLink.find('a').addClass('d-none');
            $showPhoneLink.append($('<span>', {class: 'loading'}).text(lang.loading));
        }

        flUtil.ajax({mode: 'getPhone', entity: entity, id: id, field: field}, function(response) {
            if (response.status === 'OK' && response.phone) {
                if ($showPhoneLink) {
                    $showPhoneLink.addClass('d-none');
                }

                $phone.html($('<a>', {href: 'tel:' + response.phone}).text(response.phone));
                flUtil.ajax({mode: 'savePhoneClick', listingID: $phone.data('listing-id')}, function () {});
            } else if (response.status === 'ERROR') {
                if ($showPhoneLink) {
                    $showPhoneLink.find('.loading').remove();
                    $showPhoneLink.find('a').removeClass('d-none');
                }

                printMessage('error', lang.system_error);
            }
        });
    }
}

var flForm = new flynaxForm();
