<!-- icon manager -->

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
    width: 36px;
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
    width: 36px;
    height: 36px;
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

<tr>
    <td class="name">{$lang.nova_category_menu}</td>
    <td class="field" id="add_mode_td" style="padding-top: 10px">
        <div>
            {if $sPost.category_menu == '1'}
                {assign var='category_menu_yes' value='checked="checked"'}
            {elseif $sPost.category_menu == '0'}
                {assign var='category_menu_no' value='checked="checked"'}
            {else}
                {assign var='category_menu_no' value='checked="checked"'}
            {/if}
            <label><input {$category_menu_yes} type="radio" name="category_menu" value="1" /> {$lang.yes}</label>
            <label><input {$category_menu_no} type="radio" name="category_menu" value="0" /> {$lang.no}</label>
        </div>

        <input type="hidden" name="category_menu_icon" value="{$sPost.category_menu_icon}" />

        <div class="svg-icon">
            {$lang.nova_category_icon}:
            {if $sPost.category_menu_icon}
                <img class="img-preview" src="{$icon_dir|cat:$sPost.category_menu_icon}" />
            {/if}
            <a class="icon-set" href="javascript://">{$lang.manage}</a>
            <span class="icon-reset-cont hide">
                / <a class="icon-reset" href="javascript://">{$lang.reset}</a>
            </span>
        </div>
    </td>
</tr>

<script>
lang['nova_category_icon'] = "{$lang.nova_category_icon}";
lang['search'] = "{$lang.search}";
lang['choose'] = "{$lang.choose}";
lang['cancel'] = "{$lang.cancel}";
lang['nova_load_more'] = "{$lang.nova_load_more}";
rlConfig['url_home'] = "{$smarty.const.RL_URL_HOME}";
rlConfig['template'] = "{$config.template}";
{literal}

$(function(){
    var $svg_cont = $('.svg-icon');
    var $manage   = $svg_cont.find('a.icon-set');
    var $reset    = $svg_cont.find('a.icon-reset');
    var $input    = $('input[name=category_menu_icon]');

    $manage.flModal({
        width: 789,
        height: 'auto',
        caption: lang['nova_category_icon'],
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
                $input.val($active_icon.data('name'));

                if ($svg_cont.find('img.img-preview').length) {
                    $svg_cont.find('img.img-preview').attr(
                        'src',
                        $active_icon.find('img').attr('src')
                    );
                } else {
                    $svg_cont.find('a.icon-set').before(
                        $('<img>')
                            .attr('src', $active_icon.find('img').attr('src'))
                            .addClass('img-preview')
                    );
                }

                $('input[name=category_menu][value=1]').trigger('click');

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
                flynax.sendAjaxRequest('novaGetIcons', data, function(response){
                    if (!stack) {
                        $grid.empty();
                    }

                    if (response == 'session_expired') {
                        location.reload();
                    } else if (response.results) {
                        $.each(response.results, function(index, icon_name){
                            if (/\.svg$/.test(icon_name)) {
                                var src = rlConfig['url_home'] + 'templates/' + rlConfig['template'] + '/img/icons/' + icon_name;
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

    $reset.click(function(){
        $input.val('');
        $svg_cont.find('img.img-preview').remove();
    });
});

{/literal}
</script>

<!-- icon manager end -->
