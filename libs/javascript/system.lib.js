
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: SYSTEM.LIB.JS
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

var flynaxClass = function(){
    
    /**
    * youTube embed code pattern
    **/
    this.youTubeFrame = '<iframe width="{width}" height="{height}" src="//www.youtube.com/embed/{key}" frameborder="0" allowfullscreen></iframe>';
    
    /**
    * load youTube video
    *
    * @param string key - youtube video key
    * @param selctor dom - dom element to assing video
    **/
    this.loadVideo = function(key, dom){
        $(dom).append(this.youTubeFrame.replace('{key}', key).replace('{width}', '100%').replace('{height}', 400));
    };
    
    /**
    * current step by switchStep method
    **/
    this.currentStep;
    
    /**
    * the reference to the self object
    **/
    var self = this;
    
    /**
    * registration steps handler
    **/
    this.registration = function(type){
        var base = this;
        
        if (!type) {
            type = 'multi_steps';
        }
        
        /* check user exit */
        $('input[name="profile[username]"]').blur(function(){
            var val = $.trim($(this).val());
            if ( val != '' )
            {
                xajax_userExist(val);
            }
        });
        
        /* check email exit */
        $('input[name="profile[mail]"]').blur(function(){
            var val = $.trim($(this).val());
            if ( val != '' )
            {
                xajax_emailExist(val);
            }
        });
        
        /* check type fields exist */
        $('select[name="profile[type]"]').change(function(){
            base.accountTypeChange(this);
        });
        
        /* check personal address */
        if ( account_types[$('select[name="profile[type]"]').val()] )
        {
            $('input[name="profile[location]"]').blur(function(){
                var val = $.trim($(this).val());
                if ( val != '' )
                {
                    xajax_checkLocation(val);
                }
            }); 
        }

        /* validate "on the fly" fields */
        if (type == 'one_step') {
            xajax_validateProfile(
                $('input[name="profile[username]"]').val(), 
                $('input[name="profile[mail]"]').val(), 
                $('input[name="profile[location]"]').val(),
                account_types[$('select[name="profile[type]"]').val()]
            );
        }
        
        if ( $('select[name="profile[type]"] option').length > 1 )
        {
            this.accountTypeChange($('select[name="profile[type]"]'), true);
        }
        
        var check_repeat = false;
        /* check for password */
        $('input[name="profile[password]"]').blur(function(){
            if ( $('input[name="profile[password]"]').val().length < 3 )
            {
                printMessage('error', lang['notice_reg_length'].replace('{field}', lang['password']), 'profile[password]');
            }
            else
            {
                check_repeat = true;
                
                if ( rlConfig['account_password_strength'] && $('#password_strength').val() < 3 )
                {
                    printMessage('warning', lang['password_weak_warning'])
                }
                else if ( rlConfig['account_password_strength'] && $('#password_strength').val() >= 3 )
                {
                    $('div.warning div.close').trigger('click');
                }
            }
        });
        
        $('input[name="profile[password_repeat]"]').blur(function(){
            /* clear field status */
            if ( $(this).next().hasClass('fail_field') || $(this).next().hasClass('success_field') )
            {
                $(this).next().remove();
            }
            
            /* show error in case of empty value */
            if ( !$(this).val() ) {
                $(this).addClass('error').after('<span class="fail_field">&nbsp;</span>');
                return;
            }
            
            /* check passwords */
            var pass = $('input[name="profile[password]"]').val();
            
            if ( pass != '' && check_repeat )
            {
                if ( $(this).val() != pass )
                {
                    printMessage('error', lang['notice_pass_bad'], 'profile[password_repeat]');
                    $(this).after('<span class="fail_field">&nbsp;</span>')
                }
                else
                {
                    $('div.error div.close').trigger('click');
                    $(this).removeClass('error').after('<span class="success_field">&nbsp;</span>')
                }
            }
        });

        this.registrationSubmitFormHandler();
    };

    /**
    * control the submit of account form
    **/
    this.registrationSubmitFormHandler = function(){
        $('[name="account_reg_form"]').submit(function(){
            $(this).find('[type="submit"]').val(lang['loading']).addClass('disabled').attr('disabled', true);

            return true;
        });
    }
    
    /**
     * Account type change event hendler | Secondary method
     */
    this.accountTypeChange = function(obj, direct){
        $('img.qtip').hide();

        var atype_id      = $(obj).val();
        var atype_key     = $('[name="profile[type]"]').find('option[value="' + atype_id + '"]').data('key');
        var $addressField = $('#personal_address_field');

        if (atype_id != '') {
            xajax_checkTypeFields(atype_id);

            // description replacement
            $('img.desc_' + atype_id).show();

            // personal address toggle
            if (account_types[atype_id]) {
                if (direct) {
                    $addressField.show();
                } else {
                    $addressField.slideDown();
                }
            } else {
                $addressField.slideUp();
            }

            // show/hide related agreement fields
            var $agFields          = $('div.ag_fields');
            var $agFieldsContainer = $agFields.closest('div.submit-cell');

            $agFields.find('input').attr('disabled', true);
            $agFields.addClass('hide');
            $agFieldsContainer.addClass('hide');

            if (atype_key != '' && atype_key != undefined) {
                $agFieldsContainer.removeClass('hide');

                $agFields.each(function(){
                    var at_types = $(this).data('types');

                    if (at_types.indexOf(atype_key) != -1 || at_types == '') {
                        $(this).removeClass('hide');
                        $(this).find('input').removeAttr('disabled');
                    }
                });
            }
        } else {
            $addressField.slideUp();
        }
    };
    
    /**
    * switch steps by requested step key
    **/
    this.switchStep = function(step){
        $('table.steps td').removeClass('active');
        $('table.steps td#step_'+step).prevAll().attr('class', 'past');
        $('table.steps td#step_'+step).attr('class', 'active');
        $('div.step_area').hide();
        $('div.area_'+step).show();
        $('input[name=reg_step]').val(step);
        
        this.currentStep = step;
    };
    
    /**
    * password strength handler
    **/
    this.passwordStrength = function(){
        $('#pass_strength').html(lang['password_strength_pattern'].replace('{number}', 0).replace('{maximum}', 5));
        
        $('input[name="profile[password]"]').keyup(function(){
            doMatch(this);
        });
        
        var strengthHandler = function( val ){
            var strength = 0;
            var repeat = new RegExp('(\\w)\\1+', 'gm');
            val = val.replace(repeat, '$1');
            
            /* check for lower */
            var lower = new RegExp('[a-z]+', 'gm');
            var lower_matches = val.match(lower);
            var lower_strength = 0;
            if (lower_matches)
            {
                for(var i=0; i<lower_matches.length;i++)
                {
                    lower_strength += lower_matches[i].length;
                }
            }
            if (lower_strength >= 2 && lower_strength <= 4)
            {
                strength++;
            }
            else if (lower_strength > 4)
            {
                strength += 2;
            }
            
            /* check for upper */
            var upper = new RegExp('[A-Z]+', 'gm');
            var upper_matches = val.match(upper);
            var upper_strength = 0;
            if (upper_matches)
            {
                for(var i=0; i<upper_matches.length;i++)
                {
                    upper_strength += upper_matches[i].length;
                }
            }
            if (upper_strength > 0 && upper_strength < 3)
            {
                strength++;
            }
            else if (upper_strength >= 3)
            {
                strength += 2;
            }
            
            /* check for numbers */
            var number = new RegExp('[0-9]+', 'gm');
            var number_matches = val.match(number);
            var number_strength = 0;
            if (number_matches)
            {
                for(var i=0; i<number_matches.length;i++)
                {
                    number_strength += number_matches[i].length;
                }
            }
            if (number_strength > 0 && number_strength < 4)
            {
                strength++;
            }
            else if (number_strength >= 4)
            {
                strength += 2;
            }
            
            /* check for system symbols */
            var symbol = new RegExp('[\!\@\#\$\%\^\&\*\(\)\-\+\|\{\}\:\?\/\,\<\>\;\\s]+', 'gm');
            var symbol_matches = val.match(symbol);
            var symbol_strength = 0;
            if (symbol_matches)
            {
                for(var i=0; i<symbol_matches.length;i++)
                {
                    symbol_strength += symbol_matches[i].length;
                }
            }
            if (symbol_strength > 0 && symbol_strength < 3)
            {
                strength++;
            }
            else if (symbol_strength >= 3 && symbol_strength < 5)
            {
                strength += 2;
            }
            else if (symbol_strength >= 3 && symbol_strength >= 5)
            {
                strength += 3;
            }
            
            /* check for length */
            if ( val.length >= 8 )
            {
                strength += 0.5;
            }
            
            strength = strength > 5 ? 5 : strength; 
            return Math.floor(strength);
        };
        
        var doMatch = function(obj)
        {
            var password = $(obj).val();
            var strength = strengthHandler(password);
            var scale = new Array('', 'red', 'red', 'yellow', 'yellow', 'green');
            
            $('div.password_strength div.scale div.color').width(strength*20+'%').attr('class', '').addClass('color').addClass(scale[strength]);
            $('div.password_strength div.scale div.shine').width(strength*20+'%');
            $('#pass_strength').html(lang['password_strength_pattern'].replace('{number}', strength).replace('{maximum}', 5));
            
            $('#password_strength').val(strength);
        };
        
        doMatch($('input[name="profile[password]"]'));
    };
    
    /**
    * qtips handler
    **/
    this.qtip = function(direct){
        if ( direct )
        {
            this.qtipInit();
        }
        else
        {
            var base = this;
            $(document).ready(function(){
                base.qtipInit();
            });
        }
    }
    
    /**
    * qtips init | secondary method
    **/
    this.qtipInit = function(){
        $('.qtip').each(function(){
            var target = 'topRight';
            var tooltip = 'bottomLeft';

            // RTL mode
            if (rlLangDir == 'rtl') {
                target = 'topLeft';
                tooltip = 'bottomRight';
                qtip_style.tip = 'bottomRight';
                qtip_style.textAlign = 'right';
            }

            // Middle position mode
            if ($(this).hasClass('middle-bottom')) {
                target = 'topMiddle';
                tooltip = 'bottomMiddle';

                var tmp_style = jQuery.extend({}, qtip_style);
                tmp_style.tip = 'bottomMiddle';
            }

            $(this).qtip({
                content: $(this).attr('title') ? $(this).attr('title') : $(this).prev('div.qtip_cont').html(),
                show: 'mouseover',
                hide: 'mouseout',
                position: {
                    corner: {
                        target: target,
                        tooltip: tooltip
                    }
                },
                style: $(this).hasClass('middle-bottom') ? tmp_style : qtip_style
            }).attr('title', '');
        });
    };
    
    /**
    * languages selector
    **/
    this.langSelector = function(){
        var lang_bar_open = false;
        $(document).ready(function(){
            $('div#user_navbar div.languages div.bg').click(function(event){
                if ( !lang_bar_open )
                {
                    $(this).find('ul').show();
                    $('div#user_navbar div.languages').addClass('active');
                    $('#current_lang_name').show();
                    lang_bar_open = true;
                }
                else
                {
                    if ( event.target.localName == 'a' )
                        return;
                        
                    $('div#user_navbar div.languages div.bg').find('ul').hide();
                    $('div#user_navbar div.languages').removeClass('active');
                    $('#current_lang_name').hide();
                    lang_bar_open = false;
                }
            });

            // Save user language choice
            $('#lang-selector a').click(function(event){
                event.preventDefault();

                var code = $(this).data('code');

                if (code && code !== '') {
                    createCookie('userLangChoice', code, rlConfig.expire_languages);
                }

                window.location.href = $(this).attr('href');
            });
        });
        
        $(document).click(function(event){
            var close = true;
            
            $(event.target).parents().each(function(){
                if ( $(this).attr('class') == 'languages' )
                {
                    close = false;
                }
            });
            
            if ( $(event.target).parent().attr('class') == 'bg' ||$(event.target).attr('class') == 'bg' || $(event.target).attr('class') == 'arrow' || event.target.localName == 'a' )
            {
                close = false;
            }

            if ( close )
            {
                $('div#user_navbar div.languages div.bg').find('ul').hide();
                $('div#user_navbar div.languages').removeClass('active');
                $('#current_lang_name').hide();
                lang_bar_open = false;
            }
        });
    };
    
    /**
    * payment gateways handler
    **/
    this.paymentGateway = function(){
        var loadedForms = [];        
        var loadPaymentForm = function(gateway, type) {
            if (type == 'default' || type == 'custom') {
                $('#checkout-form-container').removeClass('hide');
            }
            if (type == 'default') {
                $('#custom-form').html('');
                $('#card-form').removeClass('hide');
            } else if(type == 'custom') {
                $('#card-form').addClass('hide');
                $('#custom-form').html(lang['loading']);
                $.getJSON(
                    rlConfig['ajax_url'], {
                        mode: 'loadPaymentForm',
                        gateway: gateway
                    }, 
                    function(response) {
                        if (response.status == 'OK') {
                            if (response.html) {
                                $('#custom-form').html(response.html);
                            } else {
                                $('#custom-form').html('');    
                            }
                        }
                    }
                );
                loadedForms[gateway] = true;
            } else {
                $('#checkout-form-container').addClass('hide');
            }    
        }
        $('ul#payment_gateways li input[type="radio"]').each(function() {
            if ($(this).is(':checked')) {
                loadPaymentForm($(this).val(), $(this).parent().parent().data('form-type'));   
            }
        });

        $('ul#payment_gateways li').click(function() {
            $('ul#payment_gateways li').removeClass('active');
            $(this).addClass('active');
            $(this).find('input').attr('checked', true);
            loadPaymentForm($(this).find('input').val(), $(this).data('form-type'));
        });
    };
    
    /**
    * upload video ui handler
    **/
    this.uploadVideoUI = function(){
        this.videoTypeHandler = function(slide){
            if ( !$('#video_type').length )
            {
                return false;
            }
            
            var id = $('#video_type').val().split('_')[0];
            if ( slide )
            {
                $('.upload').slideUp();
                $('#'+id+'_video').slideDown('slow');
            }
            else
            {
                $('.upload').hide();
                $('#'+id+'_video').show();
            }
        }
        
        var base = this;
        $('#video_type').change(function(){
            base.videoTypeHandler(true);
        });
        
        this.videoTypeHandler();
        flynaxTpl.fileUploadAction();
    };

    this.isURL = function(str){
        return /^(https?):\/\/((?:[a-z0-9.-]|%[0-9A-F]{2}){3,})(?::(\d+))?((?:\/(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9A-F]{2})*)*)(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9A-F]{2})*))?(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9A-F]{2})*))?$/i.test(str);
    }

    /**
    * @deprecated - use flynax.ajax instead
    * categories tree level loader
    **/
    this.treeLoadLevel = function(tpl, callback, section) {
        if (tpl == 'crossed') {
            $('div.tree ul li > img:not(.no_child), div.tree li.locked a:not(.add)').unbind('click').click(function(){
                if ($(this).hasClass('done')) {
                    var img = this;

                    $(this).parent().find('ul:first').fadeToggle(function(event){
                        if ($(this).is(':visible')) {
                            $(img).addClass('opened');
                        } else {
                            $(img).removeClass('opened');
                        }
                    });
                } else {
                    var id = parseInt($(this).parent().attr('id').split('_')[2]);
                    var postfix = $(this).parent().parent().attr('lang');
                    xajax_getCatLevel(id, false, tpl, callback, postfix);
                    $(this).addClass('done').parent().find('span.tree_loader').fadeIn('fast');
                }
            });
            
            $('div.tree span.tmp_info a').click(function(){
                $(this).parent().hide();
                $(this).parent().next().show();
            });
            $('div.tree span.tmp_input img').click(function(){
                $(this).parent().hide();
                $(this).parent().prev().show();
            });
        } else {
            $('ul.select-category select').unbind('change').bind('change', function() {
                var obj = this;

                clearTimeout(self.timer);
                self.timer = setTimeout(function(){
                    self.treeLoadAction(obj, tpl, callback, section);
                }, 250);

                if (typeof window['OnCategorySelect'] === 'function' && $(this).val()) {
                    var id = parseInt($(this).find('option:selected').attr('id').split('_')[2]);
                    var name = $(this).find('option:selected').attr('title');
                    eval('OnCategorySelect('+id+', name)');
                }
            });

            $('ul.select-type li input').unbind().click(function(){
                var key = $(this).val();

                $('ul.select-category > li').hide();
                //$('ul.select-category > li > select:first').nextAll().remove();
                
                var li = $('ul.select-category > li#type_section_'+key);

                if (li.find('select > option').length == 2 && li.find('select > option:eq(1)').hasClass('no_child')) {
                    $('a#next_step').attr('href', li.find('select > option:last').val()).removeClass('disabled');
                } else {
                    li.show();
                    if (li.find('select:first').val() == '') {
                        $('a#next_step').attr('href', 'javascript:void(0)').addClass('disabled');
                    } else {
                        $('a#next_step').attr('href', li.find('select:last').val()).removeClass('disabled');
                    }
                }
            });

            $('ul.select-category').scrollLeft(2000);

            if ( media_query != 'desktop' ) {
                $('ul.select-category li select').removeAttr('size');
            }

            $('ul.select-category li select').each(function(){
                if ( $(this).find('option:selected').val() == '' || $(this).find('option:selected').length <= 0 ) {
                    $(this).find('option:first').attr('selected', true);
                }
            });

            if ( ct_parentPoints.length > 0 ) {
                var val = $('ul.select-category option#tree_cat_'+ct_parentPoints[0]).val();
                $('ul.select-category li#type_section_'+ct_selectedType+' select:last').val(val).trigger('change');
                ct_parentPoints.splice(0, 1);
            }
            else if ( ct_parentPoints.length == 0 ) {
                var val = $('ul.select-category option#tree_cat_'+ct_selectedID).val();
                $('ul.select-category li#type_section_'+ct_selectedType+' select:last').val(val).trigger('change');
            }

            $('ul.select-category span.tmp_info a').unbind('click').click(function(){
                $(this).parent().hide();
                $(this).parent().next().show();
            });
            $('ul.select-category span.tmp_input span.red').unbind('click').click(function(){
                $(this).parent().hide();
                $(this).parent().prev().show();
            });
        }
    };

    this.treeLoadAction = function(obj, tpl, callback, section){
        /* clear next already created dropdowns */
        $(obj).parent().nextAll().remove();

        $(obj).find('option[selected=selected]').attr('selected', false);
        $(obj).find('option:selected').attr('selected', 'selected')

        /* run xajax function */
        if ($(obj).find('option:selected').attr('id')) {
            var id = parseInt($(obj).find('option:selected').attr('id').split('_')[2]);
            if (!section) {
                var set_section = $(obj).attr('class').replace('section_', '');
            } else {
                var set_section = $(obj).attr('class') ? $(obj).attr('class').replace('section_', '') : section;
            }

            xajax_getCatLevel(id, false, tpl, callback, '', set_section);
        }
        
        /* next button handler */
        if ($(obj).find('option:selected').hasClass('disabled')) {
            $('a#next_step').attr('href', 'javascript:void(0)').addClass('disabled');
        } else {
            $('a#next_step').attr('href', $(obj).val()).removeClass('disabled');
        }
    }
    
    /**
    * slide to
    **/
    this.slideTo = function(selector){
        var top_offset;
        var bottom_offset;
        
        if ( self.pageYOffset )
        {
            top_offset = self.pageYOffset;
        }
        else if ( document.documentElement && document.documentElement.scrollTop )
        {
            top_offset = document.documentElement.scrollTop;// Explorer 6 Strict
        }
        else if ( document.body )
        {
            top_offset = document.body.scrollTop;// all other Explorers
        }
    
        var pos = $(selector).offset();
        bottom_offset = top_offset + $(window).height();

        if ( top_offset > pos.top || pos.top > bottom_offset || (pos.top + $(selector).height()) > bottom_offset )
        {
            $('html, body').stop().animate({scrollTop:pos.top - 10}, 'slow');
        }
    };

    /**
     * Save search link handler
     *
     * @deprecated 4.8.2 - Code moved to static flSaveSearch()
     */
    this.saveSearch = function() {};

    /**
    * DEPRECATED FROM v4.5.1
    *
    * search form tabs handler
    **/
    this.searchTabs = function(){
        console.log('system.lib.js - searchTabs() method is deprecated');
        
        $('ul.search_tabs li').click(function(){
            $('ul.search_tabs li.active').removeClass('active');
            $(this).addClass('active');
            var index = $('ul.search_tabs li:not(.more,.overflowed)').index(this);
            
            $('div.search_tab_area').hide();
            $('div.search_tab_area input.search_tab_hidden').attr('disabled', true);
            $('div.search_tab_area:eq('+index+')').show();
            $('div.search_tab_area:eq('+index+') input.search_tab_hidden').attr('disabled', false);
        });
    };
    
    /**
    * field from and to phrases handler
    **/
    this.fromTo = function(from, to){
        $('input.field_from').focus(function(){
            if ( $(this).val() == from )
            {
                $(this).val('');
            }
        }).blur(function(){
            if ( $(this).val() == '' )
            {
                $(this).val(from);
            }
        });
        $('input.field_to').focus(function(){
            if ( $(this).val() == to )
            {
                $(this).val('');
            }
        }).blur(function(){
            if ( $(this).val() == '' )
            {
                $(this).val(to);
            }
        });
    };
    
    /**
    * highlight search results in grid
    **/
    this.highlightSRGrid = function(query){
        if ( !query )
            return;
        
        query = trim(query);
        var repeat = new RegExp('(\\s)\\1+', 'gm');
        query = query.replace(repeat, ' ');
        query = query.split(' ');
        
        var pattern = '';
        for (var i=0; i<query.length; i++)
        {
            if ( query[i].length > 2 )
            {
                pattern += query[i]+'|'
            }
        }
        pattern = rtrim(pattern, '|');
        
        var pattern = new RegExp('('+pattern+')(?=[^>]*(<|$))', 'gi');
        var link_pattern = new RegExp('<a([^>]*)>(.*)</a>');
        
        $('#listings div.list div.item td.fields>span,#listings table.table div.item td.fields td.value').each(function(){
            var value = trim($(this).html());
            var href = false;
            if ( $(this).find('a').length > 0 )
            {
                value = trim($(this).find('a').html());
                href = $(this).find('a').attr('href');
            }

            //value = value.replace(/(<([^>]+)>)/ig,"");
            value = value.replace(pattern, '<span class="ks_highlight">$1</span>');
            value = href ? '<a href="'+href+'">'+value+'</a>' : value;
                
            $(this).html(value);
        });
    };
    
    /**
    * fighlight search results on listing details
    **/
    this.highlightSRDetails = function(query){
        query = trim(query);
        
        if ( !query )
            return false;
        
        var repeat = new RegExp('(\\s)\\1+', 'gm');
        query = query.replace(repeat, ' ');
        query = query.split(' ');
        
        var pattern = '';
        for (var i=0; i<query.length; i++)
        {
            if ( query[i].length > 2 )
            {
                pattern += query[i]+'|'
            }
        }
        pattern = rtrim(pattern, '|');
        
        var pattern = new RegExp('('+pattern+')(?=[^>]*(<|$))', 'gi');
        
        var link_pattern = new RegExp('<a([^>].*)>(.*)</a>');
        
        $('table.listing_details td.details table.table td.value').each(function(){
            var value = trim($(this).html());
            var href = false;
            if ( value.indexOf('<a') >= 0 )
            {
                var matches = value.match(link_pattern);
                if ( matches[2] )
                {
                    value = trim(matches[2]);
                    href = matches[1];
                }
            }
            value = value.replace(pattern, '<span class="ks_highlight">$1</span>');
            value = href ? '<a '+href+'>'+value+'</a>' : value;
            $(this).html(value);
        });
    };
    
    /**
    * plans click handler
    **/
    this.planClick = function(plans){
        var $container = $('.plans-container ul.plans');
        var $plan = $container.find('> li > div.frame:not(.disabled)');

        $plan.click(function(e){
            // define input
            if (e.target.tagName.toUpperCase() == 'INPUT' 
                && $(e.target).attr('type') == 'radio') {
                var input = e.target;
            } else {
                var input = $(this).find('input[name=plan]').get(0);
                $(input).attr('checked', true);
            }

            // uncheck all except this
            $container.find('> li > div').not(this).find('input').attr('checked', false);

            // check the first input in advanced plan mode
            if ($(input).hasClass('hide') && !$(this).find('input:not(.hide):checked').length) {
                $(this).find('input:not(.hide):not(:disabled)').first().attr('checked', true);
            }
        });

        // click the first available plan if the default plan disabled/exceeded or unavailable for any reason
        if ($container.find('input[name=plan]:checked').length == 0) {
            $plan.first().trigger('click');
        }
    };
    
    /**
    * is payment gateway method selected checker | DEPRECATED
    **/
    this.isGatewaySelected = function(){
        if ( $('form[name=payment] input:checked').length <= 0 )
        {
            printMessage('error', lang['gateway_fail']);
            return false;
        }
        
        return true;
    };
    
    /**
    * DEPRECATED FROM v4.5.1
    *
    * run contact owner xajax method on form submit
    *
    * @param object obj - clicked element
    * @param int listing_id - requested listing id
    **/
    this.contactOwnerSubmit = function(obj, listing_id){
        console.log('contactOwnerSubmit() method of main system javascript library is deprecated, please use the same method in template library.');
    };
    
    /**
    * get page hash, # removed
    **/
    this.getHash = function(){
        var hash = window.location.hash;
        return hash.substring(1);
    };
    
    /**
    * show/hide other sub-categories
    **/
    this.moreCategories = function(){
        var prev_button = false;

        $('div.sub_categories span.more').click(function(){
            $('div.other_categories_tmp').remove();

            if ($('div.other_categories_tmp').length && prev_button == this) {
                return;
            }
            
            prev_button = this;
            var pos = $(this).offset();
            var sub_cats = $(this).parent().find('div.other_categories').html();
            var tmp = '<div class="other_categories_tmp"><div></div></div>'
            $('body').append(tmp);
            $('div.other_categories_tmp div').html(sub_cats);
            $('div.other_categories_tmp div').append('<img class="close" title="'+lang['close']+'" src="'+rlConfig['tpl_base']+'img/blank.gif" />');
            
            var rest = rlLangDir == 'ltr' ? 0 : $('div.other_categories_tmp').width();
            var set_top = pos.top;

            var doc_top = $(window).scrollTop();
            var doc_bottom = doc_top + $(window).height();
            var box_height = $('div.other_categories_tmp').height();
            var box_top = pos.top;
            var box_bottom = box_top + box_height;

            if (box_bottom > doc_bottom) {
                set_top -= box_height + 10;
            }

            $('div.other_categories_tmp').css({
                top: set_top,
                left: pos.left-rest,
                display: 'block'
            });
            
            $('div.other_categories_tmp div img.close').click(function(){
                $('div.other_categories_tmp').remove();
            });
        });
        
        $(document).bind('click touchstart', function(event){
            if (!$(event.target).parents().hasClass('other_categories_tmp') && !$(event.target).hasClass('more')) {
                $('div.other_categories_tmp').remove();
            }
        });
    };
    
    /**
     * Content type switcher
     *
     * @param {array} fields             - Array of the form fields
     * @param {array} additional_configs - Additional configs which must be updated or added
     */
    this.htmlEditor = function(fields, additional_configs){
        if ( !fields )
            return;
    
        var configs = {
            toolbar: [
                ['Source', '-', 'Bold', 'Italic', 'Underline', 'Strike'],
                ['Link', 'Unlink', 'Anchor'],
                ['Undo', 'Redo', '-', 'Find', 'Replace', '-', 'SelectAll', 'RemoveFormat'],
                ['TextColor', 'BGColor']
            ],
            height: 160,
            language: rlLang
        };

        // add/update configs
        if (additional_configs) {
            for (var key in additional_configs) {
                if (additional_configs[key][0] && additional_configs[key][1]) {
                    configs[additional_configs[key][0]] = additional_configs[key][1];
                }
            }
        }

        var nl_pattern = /[\t\n\r]/gi;
        for (var i=0; i<fields.length; i++) {
            var field = fields[i];
            CKEDITOR.replace(field, configs);
            
            var instance = CKEDITOR.instances[field];
            if (instance) {
                instance.on('focus', function(){
                    $('#'+field).closest('div.ml_tabs_content').find('> div.error').removeClass('error');
                });
            }
        }
    };

    /** 
    * @deprecated 4.7.0 - use cascadingCategory component instead
    * category selectors handler
    **/ 
    this.multiCatsHandler = function() {}

    /**
    * phone field manager
    **/
    this.phoneField = function() {
        var deny_codes = [9, 16];
        
        $(document).ready(function(){   
            $('span.phone-field input').keyup(function(event){
                if ( deny_codes.indexOf(event.keyCode) >= 0 )
                {
                    return;
                }
                    
                if ( $(this).val().length >= parseInt($(this).attr('maxlength')) )
                {
                    $(this).next('input,select').focus();
                }
                
                if ( $(this).val().length == 0 && event.keyCode == 8)
                {
                    $(this).prev('input,select').focus().select();
                }
            });
        });
    }
}

var flynax = new flynaxClass();

/* save client utc time */
$(document).ready(function(){
if (!readCookie('client_utc_time')) {
    var client_offset = new Date().getTimezoneOffset(), o = Math.abs(client_offset);
    var client_utc = (client_offset < 0 ? "plus" : "minus") + ("00" + Math.floor(o / 60)).slice(-2) + ":" + ("00" + (o % 60)).slice(-2);

    createCookie('client_utc_time', client_utc);
}});
