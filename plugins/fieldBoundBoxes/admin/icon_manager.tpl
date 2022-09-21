<!-- svg icons manager -->

{assign var='icon_dir' value=$smarty.const.RL_URL_HOME|cat:'templates/'|cat:$config.template|cat:'/img/icons/'}

<style>
{literal}

.svg-icon {
    display: inline-block;
    background: #f5f7f0;
    border: 1px #d7dcc9 solid;
    padding: 10px;
    border-radius: 3px;
    margin: 5px 0 10px;
}
.svg-icon img.img-preview {
    height: 36px;
    vertical-align: middle;
    margin: 0 20px;
}
.svg-icon img + a + span.icon-reset-cont {
    display: inline-block;
}
#icons-grid {
    margin: 20px 0 10px 0;
    height: 340px;
    overflow: auto;
}
#icons-grid > div {
    display: flex;
    flex-wrap: wrap;
}
.icons-grod-icon {
    padding: 14px;
    max-width: 60px;
    margin: 2px;
    float: left;
    cursor: pointer;
    border-radius: 4px;
}
.icons-grod-icon:hover {
    background: #f0f0f0;
}
.icons-grod-icon.icon-active {
    background: #d7dcc9;
}
.icons-grod-icon img {
    height: 36px;
    max-width: 100%;
    object-fit: contain;
}
.icon-controls {
    text-align: right;
}
.icon-next {
    text-align: center;
}
.icon-next.invisible {
    visibility: hidden;
}
.icon-next input {
    margin-top: 0;
}
.icon-controls .cancel {
    padding-right: 15px !important;
}

{/literal}
</style>

<script>
lang['search'] = "{$lang.search}";
lang['choose'] = "{$lang.choose}";
lang['cancel'] = "{$lang.cancel}";
lang['nova_load_more'] = "{$lang.nova_load_more}";
rlConfig['url_home'] = "{$smarty.const.RL_URL_HOME}";
{literal}

$(function(){
    var $manage   = $('#open_gallery');
    var $input    = $('input[name=svg_icon]');

    $manage.flModal({
        width: 789,
        height: 'auto',
        caption: lang['fb_svg_icons'],
        content: '<div class=""><input name="icon-search" type="text" placeholder="' + lang['search'] + '" /><div id="icons-grid"><div>' + lang['loading'] + '</div></div><div class="icon-next invisible"><input type="button" value="' + lang['nova_load_more'] + '" /></div><div class="icon-controls"><a class="cancel" href="javascript://">' + lang['cancel'] + '</a><input type="button" value="' + lang['choose'] + '" /></div></div>',
        onReady: function(){
            var $grid = $('#icons-grid > div');
            var $controls = $('.icon-controls');
            var $next_cont = $('.icon-next');
            var $search = $('input[name=icon-search]');
            var $next = $next_cont.find('input');

            var stack = 0;
            var search_timer = 0;
            var search_query = '';
            var closeWindow = function(){
                $('.modal-window > div:first > span:last').trigger('click');
            }

            $grid.on('click', '.icons-grod-icon', function(){
                $('#icons-grid').find('.icon-active').removeClass('icon-active');
                $(this).addClass('icon-active');
            });
            $controls.find('.cancel').click(function(){
                closeWindow();
            });
            $controls.find('input').click(function(){
                var $active_icon = $grid.find('.icon-active');

                if ($active_icon.length) {
                    $input.val($active_icon.data('name'));

                    $('img.thumbnail').attr('src', $active_icon.find('img').attr('src'));
                    $('#gallery').slideDown();
                }

                closeWindow();
            });

            $next.click(function(){
                stack++;
                loadStack();
                $next.val(lang['loading']);
            });

            $search.on('keyup', function(){
                clearTimeout(search_timer);

                stack = 0;
                search_query = $search.val().length < 3 ? '' : $search.val();

                search_timer = setTimeout(function(){
                    loadStack();
                }, 700);
            });

            var loadStack = function(){
                var data = {
                    start: stack,
                    q: search_query
                };
                flynax.sendAjaxRequest('fbbGetIcons', data, function(response){
                    if (!stack) {
                        $grid.empty();
                    }

                    if (response == 'session_expired') {
                        location.reload();
                    } else if (response.results) {
                        $.each(response.results, function(index, icon_name){
                            if (/\.svg$/.test(icon_name)) {
                                var src = rlConfig['url_home'] + 'files/fieldBoundBoxes/svg_icons/' + icon_name;
                                var class_name = $input.val() == icon_name ? 'icon-active' : '';
                                var $icon = '<div class="icons-grod-icon ' + class_name + '" data-name="' + icon_name + '" title="' + icon_name.replace('.svg', '') + '"><img src="' + src + '" /></div>';
                                $grid.append($icon);
                            }
                        });

                        $next_cont[
                            response.next ? 'removeClass' : 'addClass'
                        ]('invisible');

                        if (stack) {
                            $next.val(lang['nova_load_more']);
                            $grid.parent().animate({scrollTop: $grid.height()}, 'slow');
                        }
                    } else {
                        $grid.append(lang['system_error']);
                    }
                });
            };

            loadStack();
        }
    });
});

{/literal}
</script>

<!-- svg icons manager end -->
