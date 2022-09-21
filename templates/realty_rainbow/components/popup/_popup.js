
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _POPUP.JS
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
    $.popup = function(element, options){
        var self = this;

        this.$interface = false;
        this.$element = $(element);
        this.options = $.extend({}, $.popup.defaultOptions, options);

        this.init = function(){
            if (this.options.click) {
                this.$element.on('click', self.click);
            } else {
                this.click.call();
            }
        }

        this.click = function(){
            if (self.$interface) {
                self.destroy();
                return;
            }

            // build interface
            self.buildUI();

            // append interface
            $('body').append(self.$interface);
            self.setPosition();

            // lock body
            if (!self.options.scroll) {
                $('body').addClass('popup-no-scroll');
            }

            // set listeners
            if (typeof self.options.onShow == 'function') {
                self.options.onShow.call(self, self.$interface);
            }

            // resize listener
            $(window).on('resize', self.setPosition);

            // outside click handler
            if (self.options.closeOnOutsideClick) {
                $(document).bind('click touchstart', self.documentClick);
            }
        }

        this.close = function(){
            if (typeof self.options.onClose == 'function') {
                self.options.onClose.call(self, self.$interface);
            } else {
                this.destroy();
            }
        }

        this.destroy = function(){
            // unbind listeners
            $(window).unbind('resize', self.setPosition);
            $(document).unbind('click touchstart', self.documentClick);

            // unlock body
            $('body.popup-no-scroll').removeClass('popup-no-scroll');

            // remove interface
            self.$interface
                .addClass('removing')
                .on('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd', function(){
                    if (typeof self.$interface == 'undefined') {
                        return;
                    }

                    self.$interface.remove();
                    delete self.$interface;
                })
        }

        this.documentClick = function(event){
            if (event.target != self.$element.get(0)
                && $.inArray(self.$element.get(0), $(event.target).parents()) < 0
                && !$(event.target).parents().hasClass('popup'))
            {
                self.close();
            }
        }

        // this.error = function(message, error_fields){
        //     // show message
        //     if (message) {
        //         self.$interface.find('> div > div.error')
        //             .html(message)
        //             .removeClass('hide');
        //     }

        //     // highlight error fields
        //     if (error_fields) {
        //         if (typeof error_fields == 'string' || error_fields instanceof jQuery) {
        //             this.highlightError(error_fields);
        //         } else if (typeof error_fields == 'object') {
        //             $.each(error_fields, function(index, item){
        //                 this.highlightError(item);
        //             });
        //         }
        //     }
        // }

        // this.highlightError = function(item){
        //     var $item = item instanceof jQuery ? item : $(item);

        //     $item
        //         .addClass('error')
        //         .on('blur click', function(){
        //             $(this).removeClass('error');
        //             self.hideError();
        //         });
        // }

        // this.hideError = function(){
        //     self.$interface.find('> div > div.error').addClass('hide');
        // }

        this.setPosition = function(){
            if (self.options.fillEdge) {
                return;
            }

            var $inner = self.$interface.find('> div');
            var translate = '';

            if ($inner.outerHeight() >= $(window).height()) {
                $inner.addClass('fill-height');
            } else {
                $inner.removeClass('fill-height');

                // Define top offset
                var top_offset = $inner.outerHeight() / 2 * -1;
                translate += 'translateY(' + top_offset + 'px)';
            }

            if ($inner.outerWidth() >= $(window).width()) {
                $inner.addClass('fill-width');
            } else {
                $inner.removeClass('fill-width');

                // define left offset
                var left_offset = $inner.outerWidth() / 2 * -1;
                translate += 'translateX(' + left_offset + 'px)';
            }

            $inner.css('transform', translate);
        }

        this.buildUI = function(){
            this.$interface = $('<div>')
                .addClass('popup')
                .append($('<div>').append(
                    $('<div>')
                ));

            var $inner = this.$interface.find('> div > div');

            if (self.options.fillEdge) {
                self.$interface.addClass('fill-edge');
            } else {
                // set interface width
                if (self.options.width != 'auto') {
                    $inner.width(self.options.width);
                }

                // set interface height
                if (self.options.height != 'auto') {
                    $inner.height(self.options.height);
                }
            }

            // build caption
            if (self.options.caption) {
                $inner.append(
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
            $inner.append(
                $('<div>')
                    .addClass('body')
                    .append(self.options.content ? self.options.content : '')
            );

            if (self.options.fillEdge) {
                $inner.find('> div.body').append(
                    $('<span>')
                        .addClass('close small')
                        .click(function(){
                            self.close();
                        })
                );
            }

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

                $inner.append($navigaton);
            }
        }

        this.init();
    }

    // default options
    $.popup.defaultOptions = {
        scroll: true,
        width: 'auto',
        height: 'auto',
        content: false,
        caption: false,
        navigation: false,
        // navigation: {
        //     okButton: {
        //         text: "Add",
        //         class: '',
        //         onClick: function(){
        //             console.log('ok clicked')
        //         }
        //     },
        //     cancelButton: {
        //         text: "Cancel",
        //         class: 'cancel',
        //         onClick: function(){
        //             console.log('cancel clicked')
        //         }
        //     }
        // },
        click: true,
        fillEdge: false,
        closeOnOutsideClick: true,
        onShow: false,
        onClose: false
    };

    $.fn.popup = function(options){
        return this.each(function(){
            (new $.popup(this, options));
        });
    };
}(jQuery));
