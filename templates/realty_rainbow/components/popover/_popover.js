
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _POPOVER.JS
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

(function($) {
    $.popover = function(element, options){
        var self = this;

        this.$interface = false;
        this.$element = $(element);
        this.options = $.extend({}, $.popover.defaultOptions, options);

        this.init = function(){
            // Set click handler
            self.$element.on('click', self.options.target,
                typeof self.options.onClick == 'function'
                    ? function(){
                        self.options.onClick.call(this, self);
                    }
                    : self.click);

            // Re-assign main element
            if (self.options.target) {
                this.$element = this.$element.find(self.options.target);
            }
        }

        this.click = function(){
            self.$element = $(this);

            if (self.$interface) {
                self.destroy();
                return;
            }

            // build interface
            self.buildUI();

            // append interface
            self.$element.after(self.$interface);
            self.setPosition();

            setTimeout(function(){
                self.setPosition();
            }, 100);

            // set listeners
            if (typeof self.options.onShow == 'function') {
                self.options.onShow.call(self, self.$interface);
            }

            // Focus in the first text input in the body
            self.$interface
                .find('> div > div.body')
                .find('input[type=text]:first')
                .focus();

            // resize listener
            $(window).on('resize', self.setPosition);

            // outside click handler
            $(document).bind('click touchstart', self.documentClick);
        }

        this.close = function(){
            if (typeof self.options.onClose == 'function') {
                self.options.onClose.call(self, self.$interface);
            } else {
                this.destroy();
            }
        }

        this.destroy = function(){
            // reset content
            this.$interface.find('> div > div.body *.error').removeClass('error');
            this.$interface.find('> div > div.body').find('input[type=text],input[type=password],textarea').val('');

            // unbind listeners
            $(window).unbind('resize', self.setPosition);
            $(document).unbind('click touchstart', self.documentClick);

            // remove interface
            self.$interface.remove();
            delete self.$interface;
        }

        this.documentClick = function(event){
            if (event.target != self.$element.get(0)
                && !$(event.target).parents().hasClass('popover'))
            {
                self.destroy();
            }
        }

        this.error = function(message, error_fields){
            // show message
            if (message) {
                self.$interface.find('> div > div.error')
                    .html(message)
                    .removeClass('hide');
            }

            // highlight error fields
            if (error_fields) {
                if (typeof error_fields == 'string' || error_fields instanceof jQuery) {
                    this.highlightError(error_fields);
                } else if (typeof error_fields == 'object') {
                    $.each(error_fields, function(index, item){
                        this.highlightError(item);
                    });
                }
            }
        }

        this.highlightError = function(item){
            var $item = item instanceof jQuery ? item : $(item);

            $item
                .addClass('error')
                .on('blur click', function(){
                    $(this).removeClass('error');
                    self.hideError();
                });
        }

        this.hideError = function(){
            self.$interface.find('> div > div.error').addClass('hide');
        }

        this.setPosition = function(){
            if (!self.$interface) {
                return;
            }

            var position = self.$element.position();
            var offset = self.$element.offset();
            var width = self.$element.width();
            var height = self.$element.height();
            var interface_width = self.$interface.outerWidth(true);
            var interface_height = self.$interface.outerHeight(true);
            var scroll_top = $(window).scrollTop() + $(window).height();

            var position_left = position.left;
            var margin = parseInt(self.$element.css('marginLeft'));

            if (margin) {
                position_left += margin;
            }

            var top = position.top + height + self.options.position.top;
            if (scroll_top - offset.top < interface_height) {
                top = position.top - interface_height - self.options.position.top;
                self.$interface.addClass('above');
            }

            var left = Math.ceil((position_left + (width / 2)) - (interface_width / 2) + self.options.position.left);

            if (offset.left + (width / 2) - (interface_width / 2) < 0) {
                left += (interface_width / 2) - (position_left + (width / 2));
            } else if (offset.left + (width / 2) + (interface_width / 2) >= $(window).width()) {
                left += $(window).width() - (position_left + (width / 2) + (interface_width / 2));
            }

            self.$interface.css('transform', 'translateX(' + left + 'px) translateY(' + top + 'px) translateZ(0)');
        }

        this.buildUI = function(){
            this.$interface = $('<div>')
                .addClass('popover')
                .append($('<div>'));

            // set interface width
            if (self.options.width != 'auto') {
                this.$interface.width(self.options.width);
            }

            // set interface height
            if (self.options.height != 'auto') {
                this.$interface.height(self.options.height);
            }

            // build caption
            if (self.options.caption) {
                this.$interface.find('> div').append(
                    $('<div>')
                        .addClass('caption')
                        .text(self.options.caption)
                        .append(
                            $('<span>')
                                .addClass('close small')
                                .click(function(){
                                    self.close();
                                })
                        )
                );
            }

            // build body
            this.$interface.find('> div').append(
                $('<div>')
                    .addClass('body')
                    .append(self.options.content ? self.options.content : '')
            );

            // build error area
            this.$interface.find('> div').append(
                $('<div>').addClass('error hide')
            );

            // build navigation
            if (self.options.navigation) {
                if (self.options.navigation instanceof jQuery) {
                    $navigaton = $('<nav>').append(self.options.navigation);
                } else if (typeof self.options.navigation == 'object') {
                    var $navigaton = $('<nav>');

                    // add "ok" button
                    if (typeof self.options.navigation.okButton == 'object') {
                        $navigaton.append(
                            $('<input>')
                                .attr('type', 'button')
                                .addClass(self.options.navigation.okButton.class)
                                .val(self.options.navigation.okButton.text)
                                .on('click', function(){
                                    if (typeof self.options.navigation.okButton.onClick == 'function') {
                                        self.options.navigation.okButton.onClick.call(this, self);
                                    }
                                })
                        );
                    }

                    // add "cancel" button
                    if (typeof self.options.navigation.cancelButton == 'object') {
                        $navigaton.append(
                            $('<input>')
                                .attr('type', 'button')
                                .addClass(self.options.navigation.cancelButton.class)
                                .val(self.options.navigation.cancelButton.text)
                                .on('click', function(){
                                    if (typeof self.options.navigation.cancelButton.onClick == 'function') {
                                        self.options.navigation.cancelButton.onClick.call(this, self);
                                    } else {
                                        self.destroy();
                                    }
                                })
                        );
                    }
                }

                this.$interface.find('> div').append($navigaton);
            }
        }

        self.init();
    }

    // default options
    $.popover.defaultOptions = {
        width: 'auto',
        height: 'auto',
        caption: false,
        content: false,
        target: false, // Dynamic target element in main container
        position: {
            top: 10,
            left: 0
        },
        navigation: false,
        // navigation: {
        //     okButton: {
        //         text: "Add",
        //         class: 'low',
        //         onClick: function(){
        //             console.log('ok clicked')
        //         }
        //     },
        //     cancelButton: {
        //         text: "Cancel",
        //         class: 'low cancel',
        //         onClick: function(){
        //             console.log('cancel clicked')
        //         }
        //     }
        // },
        onClick: false,
        onShow: false,
        onClose: false
    };

    $.fn.popover = function(options){
        return this.each(function(){
            (new $.popover(this, options));
        });
    };
}(jQuery));
