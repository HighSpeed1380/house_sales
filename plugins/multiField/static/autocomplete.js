
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: AUTOCOMPLETE.JS
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

"use strict";

var mfAutocompleteClass = function(){
    var self = this;

    var $input    = $('.mf-autocomplete-input');
    var $dropdown = $('.mf-autocomplete-dropdown');

    this.key_enter_code = 13;
    this.key_esc_code   = 27;
    this.key_up_code    = 38;
    this.key_down_code  = 40;
    this.current_pos    = 0;
    this.last_query     = '';

    this.timelimit      = 0.5 * 1000; // Seconds
    this.timeout        = null;

    this.init = function(){
        this.listeners();
    }

    this.listeners = function(){
        $input
            .on('keydown', function(e){
                clearTimeout(self.timeout);

                // Navigation
                if (e.keyCode == self.key_down_code
                    && self.current_pos + 1 < $dropdown.find('> a').length
                ) {
                    if (!$dropdown.hasClass('hide')) {
                        self.current_pos++;
                    }
                    self.show();
                } else if (e.keyCode == self.key_up_code && self.current_pos > 0) {
                    self.current_pos--;
                } else if (e.keyCode == self.key_enter_code) {
                    var $item = $dropdown.find('> a:eq(' + self.current_pos + ')');

                    if ($item.length) {
                        $item.get(0).click();
                    }
                } else if (e.keyCode == self.key_esc_code) {
                    self.current_pos = 0;
                    self.hide();
                }

                self.deselectOptions();
                self.selectCurrentOption();

                if ([
                        self.key_up_code,
                        self.key_down_code,
                        self.key_enter_code,
                        self.key_esc_code
                    ].indexOf(e.keyCode) >= 0
                ) {
                    e.preventDefault();
                    return;
                }

                // Do request
                self.timeout = setTimeout(function(){
                    self.request();
                }, self.timelimit);
            })
            .on('keyup', function(){
                if ($input.val() < 3) {
                    self.hide();
                }
            })
            .on('click', function(){
                if ($dropdown.find('> *').length) {
                    self.show();
                }
            });

        // Mouse
        $dropdown
            .on('mouseenter', '> a', function(){
                self.deselectOptions();
            })
            .on('mouseleave', '> a', function(){
                self.current_pos = $dropdown.find('> a').index(this);
                self.selectCurrentOption(true);
            });

        // Outside click handler
        $(document).on('click touchstart', function(event){
            if (event.target != $input.get(0)
                && !$(event.target).parents().hasClass('mf-autocomplete-dropdown')
            ) {
                self.hide();
            }
        });
    }

    this.deselectOptions = function(){
        $dropdown.find('> a.active').removeClass('active');
    }

    this.selectCurrentOption = function(prevent_scroll){
        if (!$dropdown.find('> a').length) {
            return;
        }

        var $current = $dropdown.find('> a:eq(' + self.current_pos + ')');
        $current.addClass('active');

        if (prevent_scroll) {
            return;
        }

        var scroll_top    = $dropdown.scrollTop();
        var scroll_bottom = scroll_top + $dropdown.height();
        var item_height   = $current.outerHeight();
        var item_top      = self.current_pos * item_height;
        var item_bottom   = item_top + item_height;

        if (item_top < scroll_top) {
            $dropdown.scrollTop(scroll_top - item_height);
        } else if (item_bottom > scroll_bottom) {
            $dropdown.scrollTop(item_bottom - $dropdown.height());
        }
    }

    this.show = function(){
        $dropdown.removeClass('hide');
    }

    this.hide = function(){
        $dropdown.addClass('hide');
    }

    this.request = function(){
        var value = $input.val();

        if (value.length < 3 || value == this.last_query) {
            return;
        }

        flUtil.ajax({
            mode: 'mfGeoAutocomplete',
            ajaxKey: 'mfGeoAutocomplete',
            currentLocation: mf_current_key,
            currentPage: location.href,
            item: $input.val(),
            lang: rlLang
        }, function(response, status){
            if (status == 'success') {
                self.last_query = value;

                $dropdown.empty();

                if (response.results.length) {
                    var highlight = new RegExp(value, 'i');

                    for (var item of response.results) {
                        value = value.charAt(0).toUpperCase() + value.slice(1);
                        item.Value = item.Value.replace(highlight, '<b>' + value + '</b>' );

                        var $a = $('<a>')
                                .attr('href', item.href)
                                .html(item.Value);

                        if (!rlPageInfo['Geo_filter']) {
                            $a
                                .addClass('gf-ajax')
                                .attr('href', 'javascript://')
                                .attr('data-path', item.Path)
                                .attr('data-key', item.Key);
                        }

                        $dropdown.append($a);
                    }

                    self.current_pos = self.current_pos + 1 <= response.results.length
                        ? self.current_pos
                        : response.results.length - 1;
                    $dropdown.find('> a:eq(' + self.current_pos + ')').addClass('active');
                }

                self[response.results.length
                    ? 'show'
                    : 'hide'
                ]();
            } else {
                printMessage('error', lang['system_error']);
            }
        });
    }
};

var mfAutocomplete = new mfAutocompleteClass();
mfAutocomplete.init();
