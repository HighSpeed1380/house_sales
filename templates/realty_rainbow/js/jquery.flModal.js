/**
 * jQuery modal window plugin by Flynax 
 *
 */
(function($){
    $.flModal = function(el, options){
        var base = this;
        var lock = false;
        var direct = false;
        var fullscreen_mode = false;
        
        // access to jQuery and DOM versions of element
        base.$el = $(el);
        base.el = el;
        
        base.objHeight = 0;
        base.objWidth = 0;
        base.sourceContent = false;

        // add a reverse reference to the DOM object
        base.$el.data("flModal", base);

        base.init = function(){
            base.options = $.extend({},$.flModal.defaultOptions, options);

            // initialize working object id
            if ( $(base.el).attr('id') ) {
                base.options.id = $(base.el).attr('id');
            }
            else {
                $(base.el).attr('id', base.options.id);
            }
            
            fullscreen_mode = false;

            // add mask on click
            if ( base.options.click ) {
                base.$el.click(function(){
                    base.mask();
                    base.loadContent();
                });
            }
            else {
                base.mask();
                base.loadContent();
            }
        };

        base.mask = function() {
            var dom = '<div id="modal_mask" class="w-100"><div id="modal_block" class="modal_block"></div></div>';
            
            $('body').append(dom);
            
            if ( media_query == 'mobile' ) {
                base.options.width = base.options.height = '100%';
            }

            if ( base.options.fill_edge ) {
                $('#modal_block').addClass('fill-edge');
            }

            if ( base.options.width == '100%' && base.options.height == '100%' ) {
                fullscreen_mode = true;
                $('body > *:visible:not(#modal_mask)').addClass('tmp-hidden').hide();
                $('#modal_block').addClass('fullscreen');
                $('#modal_mask').show();
                var width = '100%';
                var height = '100%';
            }
            else {
                var width = $(document).width();
                var height = $(document).height();
            }

            $('#modal_mask').width(width);
            $('#modal_mask').height(height);
            $('#modal_block').width(base.options.width).height(base.options.height);

            if ( !fullscreen_mode ) {
                // on resize
                $(window).bind('resize', base.resize);
            
                // on scroll    
                if ( base.options.scroll ) {
                    $(window).bind('scroll', base.scroll);
                }
            }
        };
        
        base.resize = function() {
            if ( lock )
                return;

            var width = $(window).width();
            var height = $(document).height();
            $('#modal_mask').width(width);
            $('#modal_mask').height(height);
            
            var margin = ($(window).height()/2)-base.objHeight + $(window).scrollTop();
            $('#modal_block').stop().animate({marginTop: margin});
            
            var margin = base.objWidth * -1;
            $('#modal_block').stop().animate({marginLeft: margin});
        };
        
        base.scroll = function() {
            if (lock || media_query == 'tablet')
                return;

            var margin = ($(window).height()/2)-base.objHeight + $(window).scrollTop();
            $('#modal_block').stop().animate({marginTop: margin});
        };
        
        base.loadContent = function() {
            /* load main block source */
            var dom = '<div class="inner"><div class="modal_content"></div><div class="close" title="'+lang['close']+'"><div></div></div></div>';
            $('div#modal_block').html(dom);

            /* load content */
            var content = '';
            var caption_class = base.options.type ? ' '+base.options.type : '';
            base.options.caption = base.options.type && !base.options.caption ? lang[base.options.type] : base.options.caption;
            
            /* save source */
            if ( base.options.source ) {
                if ( $(base.options.source + ' > div.tmp-dom').length > 0 ) {
                    base.sourceContent = $(base.options.source + ' > div.tmp-dom');
                    direct = true;
                }
                else {
                    base.sourceContent = $(base.options.source).html();
                }
            }
            
            /* build content */
            content = base.options.caption ? '<div class="caption'+caption_class+'">'+ base.options.caption + '</div>': '';
            content += base.options.content ? base.options.content : '';
            
            /* clear soruce objects to avoid id overload */
            if ( base.options.source && !direct ) {
                $(base.options.source).html('');
                content += !base.options.content ? base.sourceContent : '';
            }
            
            $('div#modal_block div.inner div.modal_content').html(content);
            
            if ( base.options.source && direct ) {
                $('div#modal_block div.inner div.modal_content').append(base.sourceContent);
            }
            
            if ( base.options.prompt ) {
                var prompt = '<div class="prompt"><input name="ok" type="button" value="Ok" /> <a class="close" href="javascript:void(0)">'+lang['cancel']+'</a></div>';
                $('div#modal_block div.inner div.modal_content').append(prompt);
            }
            
            if ( base.options.ready ) {
                base.options.ready();
            }
            
            $('#modal_block input[name=close], #modal_block .close').click(function(){
                base.closeWindow();
            });
            
            if ( base.options.prompt ) {
                $('#modal_block div.prompt input[name=close]').click(function(){
                    base.closeWindow();
                });
                $('#modal_block div.prompt input[name=ok]').click(function(){
                    var func = base.options.prompt;
                    func += func.indexOf('(') < 0 ? '()' : '';
                    eval(func);
                    base.closeWindow();
                });
            }
            
            /* set initial sizes */
            if ( !fullscreen_mode ) {
                base.objHeight = $('#modal_block').height()/2;
                base.objWidth = $('#modal_block').width()/2;
                
                var setTop = ($(window).height()/2) - base.objHeight + $(window).scrollTop();
                $('#modal_block').css('marginTop', setTop);
                var setLeft = base.objWidth * -1;
                $('#modal_block').css('marginLeft', setLeft);
            }
            
            $('#modal_mask').click(function(e){
                if ( $(e.target).attr('id') == 'modal_mask' ) {
                    base.closeWindow();
                }
            });
            
            $('#modal_block div.close').click(function(){
                base.closeWindow();
            });
        };
        
        base.closeWindow = function() {
            lock = true;
            
            $('#modal_block').animate({opacity: 0});
            $('#modal_mask').animate({opacity: 0}, function(){
                $(this).remove();
                $('#modal_block').remove();
                
                if ( base.options.source ) {
                    $(base.options.source).append(base.sourceContent);
                }
                
                lock = false;
            });

            $(window).unbind('resize', base.resize);
            $(window).unbind('scroll', base.scroll);

            if ( fullscreen_mode ) {
                $('body > *.tmp-hidden').show().removeClass('tmp-hidden');
            }
        };
        
        // run initializer
        base.init();
    };

    $.flModal.defaultOptions = {
        scroll: true,
        type: false,
        width: 340,
        height: 230,
        source: false,
        content: false,
        caption: false,
        prompt: false,
        click: true,
        ready: false,
        fill_edge: false
    };

    $.fn.flModal = function(options){
        return this.each(function(){
            (new $.flModal(this, options));
        });
    };

})(jQuery);
