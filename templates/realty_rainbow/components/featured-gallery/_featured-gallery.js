
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: _FEATURED-GALLERY.JS
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

var featuredGallery = function(inverseDirection) {
    var self = this,
        $gl = $('div.featured_gallery ul.featured'),
        $preview = $('div.featured_gallery > div.preview'),
        count = $gl.find('> li').length,
        item_width = $gl.find('> li:first').width(),
        item_height = $gl.find('> li:first').height(),
        active_count = 5,
        shift = 0,
        current = 0,
        timer = false,
        slideshow = true,
        slide_delay = typeof rlConfig['random_block_slideshow_delay'] != 'undefined' ? rlConfig['random_block_slideshow_delay'] * 1000 : 12000,
        demo = $('div.featured_gallery').hasClass('demo'),
        $priceBadge = $preview.find('> div.fg-price'),
        $titleBadge = $preview.find('> div.fg-title');
    
    if (count <= 0) {
        return;
    }

    // Prepare content
    $gl.find('> li img').each(function(){
        if ($(this).attr('src').indexOf(rlConfig['tpl_base']) == 0) {
            $(this)
                .attr('src', rlConfig['tpl_base'] + 'img/no-thumb.jpg')
                .attr('accesskey', rlConfig['tpl_base'] + 'img/no-thumb-preview.jpg');
        }

        var href = $(this).closest('a').attr('href');
        $(this).closest('li').find('ul:first').attr('accesskey', href);
        $(this).closest('a').replaceWith(this);
    });

    // Hide fields to calculate height properly
    $gl.find('li ul').addClass('hide');

    this.isVertical = function(){
        var vertical = false;

        if (inverseDirection) {
            if (media_query == 'tablet') {
                vertical = true;
            }
        } else {
            if (media_query == 'desktop') {
                vertical = large_desktop;
            } else if (media_query == 'tablet') {
                vertical = true;
            }
        }

        return vertical;
    }

    this.calc = function(){
        item_width = $gl.find('> li:first').outerWidth(),
        item_height = $gl.find('> li:first').outerHeight();

        var cont_height = $gl.parent().hasClass('featured_gallery_items') ? $gl.parent().height() : $gl.height();
        active_count = this.isVertical() ? cont_height / (item_height-2) : $gl.width() / (item_width-2);
        active_count = Math.floor(active_count);

        $gl.css({'transform': 'translate(0, 0)'});
    };

    this.showImage = function(obj, url){
        $preview.find('> a > div').css('backgroundImage', 'url('+url+')').fadeIn();
        $preview.find('> a').attr('href', $(obj).closest('li').find('ul:first').attr('accesskey'));
    };

    this.loadImage = function(){
        self.slide();

        var obj = $gl.find('> li:eq('+current+') img');
        obj.closest('li').parent().find('li.active').removeClass('active');
        obj.closest('li').addClass('active');
        var src = obj.attr('src');

        if (demo) {
            src = src.replace('no-thumb.jpg', 'no-thumb-preview.jpg');
            obj.attr('accesskey', src);
        } else {
            src = src.indexOf(rlConfig['tpl_base']) == 0
            ? 'no-thumb-preview.jpg'
            : src.replace(rlConfig['files_url'], '');
        }

        // Set title
        var title = obj.attr('alt');

        if (title) {
            $titleBadge.show().html(title);
        } else {
            $titleBadge.hide().html('');
        }
        
        // Set price
        var price = obj.closest('li').find('ul.ad-info [class*=price] span:last').text();

        if (price) {
            $priceBadge.show().html(price);
        } else {
            $priceBadge.hide().html('');
        }

        if (obj.attr('accesskey')) {
            self.showImage(obj, obj.attr('accesskey'));
        } else {
            $.getJSON(rlConfig['ajax_url'], {mode: 'photo', item: src, lang: rlLang}, function(url) {
                if (url != '' && url != null) {
                    var img = new Image();
                    img.onload = function(){
                        self.showImage(obj, url);
                        obj.attr('accesskey', url);
                    }
                    img.src = url;
                }
            });
        }

        if (typeof($.convertPrice) == 'function') {
            if (price && $priceBadge.length) {
                delete $priceBadge[0].convertPriceInited;
                $priceBadge.convertPrice();
            } else {
                $gl.find('.price_tag > div').convertPrice();
            }
        }
    };

    this.slide = function(){
        var do_slide = false;

        if (current >= (shift + active_count - 1) && (count - 1) > current) {
            shift++;
            do_slide = true;
        } else if (current > 0 && shift == current) {
            shift--;
            do_slide = true;
        } else if (current == 0 && shift != 0) {
            shift = 0;
            do_slide = true;
        }

        if (do_slide) {
            if (this.isVertical()) {
                var shift_pos = shift * item_height * -1;
                var shift_option = 'translateY';
            } else {
                var shift_pos = shift * item_width;
                shift_pos = rlLangDir == 'ltr' ? shift_pos * -1 : shift_pos;
                var shift_option = 'translateX';
            }

            $gl.css({'transform': shift_option + '(' + shift_pos + 'px)'});
        }
    };

    if (slideshow) {
        timer = setInterval(function(){
            current = current == (count - 1) ? 0 : current + 1;

            self.loadImage(current);
        }, slide_delay);
    }

    this.calc();
    this.loadImage();

    $gl.find('li img').unbind('click').click(function(){
        slideshow = false;
        clearInterval(timer);
        current = $gl.find('> li').index($(this).closest('li'));
        self.loadImage();
    });

    $preview.find('span.next').click(function(){
        slideshow = false;
        clearInterval(timer);

        current = current == (count - 1) ? 0 : current + 1;
        self.loadImage();
    })
    $preview.find('span.prev').click(function(){
        slideshow = false;
        clearInterval(timer);

        current = current == 0 ? count - 1: current - 1;
        self.loadImage();
    });

    $(window).bind('resize', function(){
        self.calc();
    });
};
