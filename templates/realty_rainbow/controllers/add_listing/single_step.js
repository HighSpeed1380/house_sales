
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SINGLE_STEP.JS
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

$(function(){
    "use strict";

    var int_indexes = [
        'Advanced_mode', 'Listings_remains',
        'Standard_listings', 'Standard_remains',
        'Featured_listings',  'Featured_remains',
        'Limit', 'Using',
        'Image', 'Image_unlim',
        'Video', 'Video_unlim'
    ];
    var float_indexes = ['Price'];

    var from_post = rlConfig['manageListing']['from_post'];
    var single_plan = rlConfig['manageListing']['single_plan'];
    var selected_plan_id = rlConfig['manageListing']['selected_plan_id'];
    var current_plans = rlConfig['manageListing']['current_plans'];
    var selected_category_id = rlConfig['manageListing']['selected_category_id'];
    var selected_type = rlConfig['manageListing']['selected_type'];
    var user_category_id = rlConfig['manageListing']['user_category_id'];
    var parent_ids = rlConfig['manageListing']['parent_ids'];

    // Adapt data
    parent_ids = parent_ids ? parent_ids.split(',') : '';
    current_plans = Object.keys(current_plans).length
        ? flUtilData.adaptArray(current_plans, 'ID', int_indexes, float_indexes)
        : current_plans;

    // Single step mode vars
    var current_category_id = 0;
    var previous_category_id = 0;
    var current_category_path = '';

    // Dom elements
    var $container = $('#category_container');
    var $action_button = $('#next_step');
    var $selected_category = $('.selected-category span.link');
    var $dynamic_content = $('.dynamic-content');
    var $selected_plan = $dynamic_content.find('.selected-plan select');
    var $listing_form = $dynamic_content.find('.listing-form');
    var $form_fields = $listing_form.find('.form-fields');
    var $form_media = $listing_form.find('.form-media');
    var $form_auth = $listing_form.find('.form-auth');
    var $form_crossed = $listing_form.find('.form-crossed');
    var $crossed_select_type = $form_crossed.find('ul.select-type');
    var $stat_container = $('.upload-stat');
    var $selected_ad_type = $('.selected-ad-type');
    var $plans_subscribe = $('.plans-subscribe');
    var $plans_subscribe_input = $plans_subscribe.find('input[type=checkbox]');
    var $standard_label = $selected_ad_type.find('span.ad-standard mark');
    var $featured_label = $selected_ad_type.find('span.ad-featured mark');
    var $standard_input = $selected_ad_type.find('span.ad-standard input[type=radio]');
    var $featured_input = $selected_ad_type.find('span.ad-featured input[type=radio]');
    var $category_selection = $('.category-selection');

    var initCategorySelector = function(){
        if (current_category_id) {
            return;
        }

        $category_selection.categorySelector({
            actionButton: $action_button,
            selectedID: selected_category_id,
            selectedType: selected_type,
            parentIDs: parent_ids,
            userCategoryID: user_category_id,
            onChange: function($select, $option){
                current_category_id = parseInt($select.val());
                current_category_path = $option.data('path');
            },
            onLevelLoad: typeof addUserCategoryAction == 'function'
                ? addUserCategoryAction
                : null
        });
    };

    /**
     * Append related static js code snippets to the document
     * @param array scripts - js code snippets
     */
    var appendJS = function($container, results, callback){
        if (results.js_files) {
            flUtil.loadScript(results.js_files, function(){
                $container.append(results.js_scripts.join('\r\n'));

                if (typeof callback == 'function') {
                    callback.call();
                }
            });
        } else {
            $container.append(results.js_scripts.join('\r\n'));

            if (typeof callback == 'function') {
                callback.call();
            }
        }
    }

    var restoreFormData = function(form_data){
        // Restore form data
        for (var i in form_data) {
            var $element = $form_fields.find('*[name="' + form_data[i].name + '"]');

            if ($element.prop('tagName') == 'INPUT') {
                if (['text', 'password'].indexOf($element.prop('type')) >= 0) {
                    $element.val(form_data[i].value);
                } else {
                    $element.filter(function() {
                        return this.value == form_data[i].value;
                    }).attr('checked', true);
                }
            } else if ($element.prop('tagName') == 'SELECT') {
                if (form_data[i].value && $element.find('option[value=' + form_data[i].value + ']').length) {
                    $element.val(form_data[i].value);
                }
            } else if ($element.prop('tagName') == 'TEXTAREA') {
                if ($element.data('type') == 'html') {
                    var name = $element.attr('id');
                    if (typeof CKEDITOR.instances[name] != 'undefined') {
                        CKEDITOR.instances[name].setData(window.textarea_fields[name].value);
                    }
                } else {
                    $element.html(form_data[i].value);
                }
            }
        }
    }

    var changePlan = function(plan_id){
        // Update plan in class instance
        var data = {
            mode: 'manageListing',
            action: 'select_plan',
            controller: rlPageInfo['controller'],
            plan_id: plan_id,
            preventAbortParallel: 1
        };
        flUtil.ajax(data, function(response, status){});

        rlConfig['manageListing']['selected_plan_id'] = plan_id;
        selected_plan_id = plan_id;

        // No plan mode
        if (!plan_id) {
            $stat_container.html(lang['single_step_select_plan']);

            $selected_ad_type.addClass('disabled');
            $plans_subscribe.addClass('disabled');
            $form_crossed.addClass('disabled');
            $plans_subscribe_input.attr('checked', false);

            return true;
        }

        var plan_info = current_plans[plan_id];

        // Reset crossed categories interface
        if (typeof rlConfig['cross_category_instance'] == 'object') {
            var crossed_count = parseInt(plan_info.Cross);

            rlConfig['cross_category_instance'].updateGUI(
                crossed_count,
                rlConfig['current_listing_type'].Key,
                selected_category_id ? selected_category_id : current_category_id
            );
        }

        // Ad type handler
        $selected_ad_type[
            plan_info.Advanced_mode
                ? 'removeClass'
                : 'addClass'
        ]('disabled');

        if (plan_info.Advanced_mode) {
            var standard = '',
                featured = '';

            // Define standard
            if (plan_info.Standard_listings > 0) {
                if (plan_info.Listings_remains) {
                    if (plan_info.Standard_remains > 0) {
                        standard = plan_info.Standard_remains;
                    } else {
                        standard = lang['used_up'];
                    }
                } else {
                    standard = plan_info.Standard_listings;
                }
            }

            // Define featured
            if (plan_info.Featured_listings > 0) {
                if (plan_info.Listings_remains) {
                    if (plan_info.Featured_remains > 0) {
                        featured = plan_info.Featured_remains;
                    } else {
                        featured = lang['used_up'];
                    }
                } else {
                    featured = plan_info.Featured_listings;
                }
            }

            // Set label
            $standard_label.text(standard ? '(' + standard + ')' : '');
            $featured_label.text(featured ? '(' + featured + ')' : '');

            // Manage availability
            $standard_input
                .attr('disabled', plan_info.standard_disabled)
                .attr('checked', false);

            $featured_input
                .attr('disabled', plan_info.featured_disabled)
                .attr('checked', false);

            // Select first available if nothing selected
            $selected_ad_type.find('input:not(:disabled):first')
                .attr('checked', true);
        }

        if (plan_info.Subscription == 'active'
            && plan_info.Price > 0
            && !plan_info.Listings_remains
        ) {
            $plans_subscribe.removeClass('disabled');
        } else {
            $plans_subscribe.addClass('disabled');
            $plans_subscribe_input.attr('checked', false);
        }

        // Initialize media manager
        if (!rlConfig['mediaManager']) {
            rlConfig['mediaManager'] = new flMediaManager();
            rlConfig['mediaManager'].init();
        }

        // Media handler
        var picture = false;
        var video = false;

        if (rlConfig['current_listing_type'].Photo == '1') {
            if (plan_info.Image_unlim) {
                picture = true;
            } else if (plan_info.Image > 0) {
                picture = plan_info.Image;
            }
        }

        if (rlConfig['current_listing_type'].Video == '1') {
            if (plan_info.Video_unlim) {
                video = true;
            } else if (plan_info.Video > 0) {
                video = plan_info.Video;
            }
        }

        // Switch "Add Media" interface visablitity
        $form_media[!picture && !video
            ? 'addClass'
            : 'removeClass'
        ]('disabled');

        // Change plan
        rlConfig['mediaManager'].changePlan(
            plan_id,
            plan_info.name,
            picture,
            video
        );

        // Crossed categories handler
        if (typeof rlConfig['cross_category_instance'] == 'object') {
            var crossed_count = parseInt(plan_info.Cross);
            var $single_category = $category_selection.find('input[name=section]:checked');
            var crossed_disallow = $single_category.data('single-category-id') && !rlConfig['crossed_categories_by_type'];

            $form_crossed[
                crossed_count && !crossed_disallow
                    ? 'removeClass'
                    : 'addClass'
            ]('disabled');

            // Single plan mode
            if (single_plan) {
                $crossed_select_type.find('input[name=section_crossed]')
                    .filter('[value=' + rlConfig['current_listing_type'].Key + ']')
                    .trigger('click');
            }
        }

        return true;
    }

    var ieSupport = function(){
        if (!navigator.userAgent.match(/Trident\/7\./)) {
            return;
        }

        $listing_form.find('div.form-buttons input[type=submit]')
            .removeAttr('form')
            .click(function(){
                var $form = $listing_form.find('#listing_form');

                // Remove cloned elements
                $('.ie-support-cloned').remove();

                // Add outside elements to the form
                $dynamic_content.find('*[form=listing_form]').each(function(){
                    var $element = $(this).clone(true);

                    switch ($element.prop('tagName').toLowerCase()) {
                        case 'select':
                            $element.val($(this).val());
                            break;

                        case 'input':
                            $element.attr('checked', $(this).is(':checked'));
                            break;
                    }

                    $element
                        .addClass('ie-support-cloned')
                        .addClass('hide');
                    $form.append($element);
                });

                // Submit form
                $form.submit();
            });
    }

    // Load jsRender library and category_selector plugin
    flUtil.loadScript([
        rlConfig['libs_url'] + 'javascript/jsRender.js',
        rlConfig['tpl_base'] + 'components/category-selector/_category-selector.js'
    ], function(){
        if (!selected_category_id) {
            initCategorySelector();
        }

        // "Select Category" button handler
        $action_button.click(function(){
            if (
                $(this).hasClass('disabled')
                || !current_category_id
                || !current_category_path
            ) {
                return true;
            }

            // Don not update the form if the category was not changed
            if (
                previous_category_id == current_category_id
                || parseInt(current_category_id) == selected_category_id
            ) {
                // Switch interfaces
                $container.addClass('selected');
                return true;
            }

            $(this).text(lang['loading']);

            // Save selected category and other data in the class instance
            var is_user_category = 0;

            if (rlConfig['user_category_path_prefix']
                && current_category_path.indexOf(rlConfig['user_category_path_prefix']) == 0 ) {
                is_user_category = 1;
            }

            var data = {
                mode: 'manageListing',
                action: 'select_category',
                controller: rlPageInfo['controller'],
                lang: rlLang,
                sidebar: $('body').hasClass('no-sidebar') ? false : true,
                data: {
                    category_id: current_category_id,
                    is_user_category: is_user_category
                }
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success') {
                    if (!response.results.plans.length) {
                        printMessage('error', lang['notice_no_plans_related']);

                        $action_button.text($action_button.data('default-value'));
                        return;
                    }

                    // Save current category id
                    previous_category_id = current_category_id;

                    // Switch interfaces
                    $container.addClass('selected');

                    // Reset interfaces
                    if (typeof window.textarea_fields == 'object') {
                        for (var name in window.textarea_fields) {
                            if (
                                window.textarea_fields[name].type == 'html'
                                && typeof CKEDITOR.instances[name] != 'undefined'
                            ) {
                                window.textarea_fields[name].value = CKEDITOR.instances[name].getData();
                                CKEDITOR.instances[name].destroy(true);
                                //CKEDITOR.remove(CKEDITOR.instances[name]);
                            }
                        }
                    }

                    // Reset crossed categories interface
                    if (typeof rlConfig['cross_category_instance'] == 'object') {
                        rlConfig['cross_category_instance'].resetGUI(current_category_id);
                    }

                    // Update related listing type data
                    rlConfig['current_listing_type'] = response.results.listing_type;

                    // Update single plan flag
                    if (response.results.single_plan) {
                        single_plan = response.results.single_plan;
                        selected_plan_id = response.results.plans[0].ID;
                    }

                    // Switch "Add Media" interface visibility
                    $form_media[rlConfig['current_listing_type'].Photo == '0'
                        && rlConfig['current_listing_type'].Video == '0'
                            ? 'addClass'
                            : 'removeClass'
                    ]('disabled');

                    $form_media.find('.name .content-padding .red')[
                        rlConfig.current_listing_type.Photo_required === '1'
                            ? 'removeClass'
                            : 'addClass'
                    ]('d-none')

                    var $single_category = $category_selection.find('input[name=section]:checked');
                    var single_type_name = $single_category.data('single-category-name');
                    var categories = [];

                    // Update category bread crumbs
                    if ($single_category.data('single-category-id')) {
                        categories.push(single_type_name);
                    } else {
                        $('.category-selection ul.select-category > li.show > div > select').each(function(){
                            var name = $.trim($(this).find('option:selected').text());
                            if (name) {
                                categories.push(name);
                            }
                        });
                    }
                    $selected_category.text(categories.join(' / '));

                    // Save current plans
                    current_plans = flUtilData.adaptArray(response.results.plans, 'ID', int_indexes, float_indexes);

                    // Single plan mode
                    if (single_plan && selected_plan_id) {
                        $dynamic_content.addClass('single-plan');
                        changePlan(selected_plan_id);
                    }
                    // Update plans selector
                    else {
                        $selected_plan.find('option:gt(0)').remove();
                        var plans_count = Object.keys(response.results.plans).length;

                        if (plans_count > 0) {
                            $dynamic_content.removeClass('single-plan');

                            $selected_plan.append(
                                $('#plan_selector_option').render(response.results.plans)
                            );

                            // Previously selected plan is unavailable anymore
                            if (selected_plan_id && selected_plan_id != parseInt($selected_plan.val())) {
                                selected_plan_id = 0;
                            }
                            // Select current plan
                            else if (selected_plan_id) {
                                $selected_plan.find('option[value=' + selected_plan_id + ']').removeAttr('disabled');
                            }
                            // Select the first available package
                            else if ($selected_plan.find('option[data-available=true]').length) {
                                selected_plan_id = parseInt($selected_plan.find('option[data-available=true]').val());
                            }

                            if (typeof selected_plan_id == 'number') {
                                $selected_plan
                                    .val(selected_plan_id)
                                    .trigger('change');
                            }
                        } else {
                            printMessage('error', lang['notice_no_plans_related']);
                        }
                    }

                    // Save form data
                    var form_data = $form_fields.find('form').serializeArray();

                    // Remove all listeners
                    $form_fields
                        .find('*')
                        .off();

                    // Append submit form
                    $form_fields
                        .empty()
                        .append(response.results.form);

                    // Update auth form
                    $form_auth.find('div.field')
                        .empty()
                        .append(response.results.auth);

                    // Set current listing ID
                    rlConfig['current_listing_id'] = response.results.listing_id;

                    // Run default scripts
                    flynaxTpl.customInput();
                    flFieldset();
                    flynaxTpl.tabsMore();

                    restoreFormData(form_data);

                    // Load related js scripts
                    appendJS($form_fields, response.results, function(){
                        restoreFormData(form_data);
                    });

                    // Load related css files
                    if (response.results.css_files && response.results.css_files.length) {
                        flUtil.loadStyle(response.results.css_files);
                    }

                    // Restore incomplete listing media
                    if (typeof rlConfig['mediaManager'] == 'object') {
                        rlConfig['mediaManager'].loadHandler();
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }

                $action_button.text($action_button.data('default-value'));
            });
        });

        // Selected category link click handler
        $selected_category.click(function(){
            initCategorySelector();
            $container.removeClass('selected');
        });

        // Plan selector handler
        $selected_plan.change(function(){
            $(this).removeClass('error');

            var plan_id = parseInt($(this).val());

            // Reset crossed categories interface
            if (plan_id && typeof rlConfig['cross_category_instance'] == 'object') {
                rlConfig['cross_category_instance'].resetGUI(current_category_id);
            }

            return changePlan(plan_id);
        });

        // Trigger plan change if form submitted
        if (from_post) {
            // Trigger plan change in single plan mode
            // if (single_plan && selected_plan_id) {
            //     changePlan(selected_plan_id);
            // }
            // // Trigger plan change in multiple plans mode
            // else {
            //     $selected_plan.trigger('change');
            // }

            if (selected_plan_id) {
                changePlan(selected_plan_id);
            }

            if (rlConfig['current_listing_type'].Photo == '0'
                && rlConfig['current_listing_type'].Video == '0') {
                $form_media.addClass('disabled');
            }
        }

        // Ad type change handler
        $selected_ad_type.find('input[name=ad_type]').change(function(){
            var data = {
                mode: 'manageListing',
                action: 'change_ad_type',
                controller: rlPageInfo['controller'],
                ad_type: $(this).val(),
                preventAbortParallel: 1
            };
            flUtil.ajax(data, function(response, status){});
        });

        // Subscription change handler
        $plans_subscribe_input.change(function(){
            var data = {
                mode: 'manageListing',
                action: 'change_subscription',
                controller: rlPageInfo['controller'],
                subscription: + $(this).is(':checked'),
                preventAbortParallel: 1
            };
            flUtil.ajax(data, function(response, status){});
        });

        var scrollToPlan = function(){
            printMessage('error', lang['single_step_select_plan']);
            $selected_plan.addClass('error');
            flynax.slideTo('#content');
        }

        // Media select handler
        $('input[name="files[]"]').click(function(e){
            if (!selected_plan_id) {
                scrollToPlan();
                return false;
            }
        });

        // Media drop handler
        $('.upload-zone').on('drop', function(e){
            if (!selected_plan_id) {
                scrollToPlan()
                e.stopPropagation();
                return false;
            }
        });

        // IE form attibute support
        ieSupport();
    });

    // Load jsRender library and category_selector plugin
    flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
        var $interface = $('<div>')
                        .addClass('plans-chart point1')
                        .text(lang['loading']);

        $('span.plans-chart-link').popup({
            scroll: false,
            content: $interface,
            caption: lang['select_plan'],
            onShow: function(content){
                var self = this;

                var data = {
                    mode: 'manageListing',
                    action: 'get_plans_chart',
                    lang: rlLang,
                    controller: rlPageInfo['controller'],
                    sidebar: $('body').hasClass('no-sidebar') ? false : true,
                    data: {
                        category_id: current_category_id
                            ? current_category_id
                            : selected_category_id
                    }
                };
                flUtil.ajax(data, function(response, status){
                    if (status == 'success') {
                        content.find('div.plans-chart')
                            .empty()
                            .append(response.results.html);

                        // Load related js scripts
                        appendJS(content.find('div.plans-chart'), response.results);

                        // Run default scripts
                        flynaxTpl.customInput();

                        // Fix popup position
                        self.setPosition();

                        // Remove apply button if there are not available plans
                        if (!content.find('input[name=plan]:not(:disabled)').length) {
                            self.$interface.find('> div > div > nav > *:first').hide();
                        }
                    } else {
                        printMessage('error', lang['system_error']);
                    }
                });
            },
            navigation: {
                okButton: {
                    text: lang['apply'],
                    onClick: function(popup){
                        var $plan_frame = popup.$interface.find('input[name=plan]').closest('li');

                        var plan_id = popup.$interface.find('input[name=plan]:checked').val();
                        var ad_type = $plan_frame.find('input[name=ad_type]:checked').val();
                        var subscription = $plan_frame.find('input[name=subscription]').is(':checked');

                        // No available plan flow
                        if (!plan_id) {
                            popup.close();
                            return;
                        }

                        // Set plan
                        $selected_plan
                            .val(plan_id)
                            .trigger('change');

                        // Set ad type
                        if (ad_type) {
                            if (ad_type == 'standard') {
                                $standard_input.trigger('click');
                            } else {
                                $featured_input.trigger('click');
                            }
                        }

                        // Set subscription
                        if (typeof subscription == 'boolean') {
                            $plans_subscribe_input
                                .attr('checked', subscription)
                                .change();
                        }

                        popup.close();
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
