<!-- Location selector in user navbar | multifield -->

{include file=$smarty.const.RL_PLUGINS|cat:'multiField'|cat:$smarty.const.RL_DS|cat:'static'|cat:$smarty.const.RL_DS|cat:'gallery.svg'}

<span class="circle" id="mf-location-selector">
    <span class="default{if $mf_is_nova} header-contacts{/if}">
        {strip}
        <svg class="mf-location-icon mr-2 align-self-center{if !$mf_is_nova} header-usernav-icon-fill{/if}" viewBox="0 0 20 20">
            <use xlink:href="#mf-location"></use>
        </svg>
        <span class="flex-fill">
        {if $geo_filter_data.applied_location}
            {$geo_filter_data.applied_location.name}
        {else}
            {$lang.mf_select_location}
        {/if}
        </span>
        {/strip}
    </span>
</span>

<script class="fl-js-dynamic">
var mf_current_location = "{$mf_current_location|escape:'quotes'}";
var mf_location_autodetected = {if $smarty.session.geo_location_autodetected}true{else}false{/if};
lang['mf_is_your_location'] = '{$lang.mf_is_your_location}';
lang['mf_no_location_in_popover'] = '{$lang.mf_no_location_in_popover}';
lang['mf_select_location'] = '{$lang.mf_select_location}';
lang['yes'] = '{$lang.yes}';
lang['no'] = '{$lang.no}';
{literal}

$(function(){
    var popupPrepared = false;
    var $buttonDefault = $('#mf-location-selector');
    var $button = $buttonDefault.find(' > .default');
    var cities = [];

    $('.gf-root').on('click', 'a.gf-ajax', function(){
        gfAjaxClick($(this).data('key'), $(this).data('path'), $(this).data('link'))
    });

    var showCities = function(){
        if (cities.length) {
            var $container = $('.gf-cities');

            if (!$container.find('ul').length) {
                var $list = $('<ul>').attr('class', 'list-unstyled row');

                $list.append($('#gf_city_item').render(cities));
                $container.append($list);
            }
        }
    }

    var showPopup = function(){
        var $geoFilterBox = $('.gf-root');

        $('#mf-location-selector').popup({
            click: false,
            scroll: false,
            content: $geoFilterBox,
            caption: lang['mf_select_location'],
            onShow: function(){
                showCities();

                $buttonDefault.unbind('click');

                createCookie('mf_usernavbar_popup_showed', 1, 365);
            },
            onClose: function($interface){
                var tmp = $geoFilterBox.clone();
                $('#gf_tmp').append($geoFilterBox);

                // Keep clone of interface to allow the box looks properly during the fade affect
                $interface.find('.body').append(tmp);

                this.destroy();
            }
        });
    }

    var getCities = function(){
        flUtil.ajax({
            mode: 'mfGetCities',
            path: location.pathname
        }, function(response, status) {
            if (status == 'success' && response.status == 'OK') {
                cities = response.results;
                showCities();
            } else {
                console.log('GeoFilter: Unable to get popular cities, ajax request failed')
            }
        });
    }

    var initPopup = function(){
        if (popupPrepared) {
            showPopup();
        } else {
            flUtil.loadScript([
                rlConfig['tpl_base'] + 'components/popup/_popup.js',
                rlConfig['libs_url'] + 'javascript/jsRender.js'
            ], function(){
                showPopup();
                getCities();
                popupPrepared = true;
            });
        }
    }

    if (!readCookie('mf_usernavbar_popup_showed')) {
        flUtil.loadStyle(rlConfig['tpl_base'] + 'components/popover/popover.css');
        flUtil.loadScript(rlConfig['tpl_base'] + 'components/popover/_popover.js', function(){
            var closeSave = function(popover){
                popover.close()
                createCookie('mf_usernavbar_popup_showed', 1, 365);
            }

            var $content = $('<div>').append(
                mf_location_autodetected
                    ? lang['mf_is_your_location'].replace('{location}', '<b>' + mf_current_location + '</b>')
                    : lang['mf_no_location_in_popover']
            );

            $buttonDefault.popover({
                width: 200,
                content: $content,
                navigation: {
                    okButton: {
                        text: lang['yes'],
                        class: 'low',
                        onClick: function(popover){
                            closeSave(popover);

                            if (!mf_location_autodetected) {
                                setTimeout(function(){
                                    initPopup();
                                }, 10);
                            }
                        }
                    },
                    cancelButton: {
                        text: lang['no'],
                        class: 'low cancel',
                        onClick: function(popover){
                            closeSave(popover);

                            if (mf_location_autodetected) {
                                setTimeout(function(){
                                    initPopup();
                                }, 10);
                            }
                        }
                    }
                }
            }).trigger('click');

            $button.click(function(){
                initPopup();
            });
        });
    } else {
        $button.click(function(){
            initPopup();
        });
    }
});

{/literal}
</script>

<!-- Location selector in user navbar | multifield end -->
