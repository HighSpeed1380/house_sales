<!-- static map handler -->

{addCSS file=$rlTplBase|cat:'components/popup/popup.css'}

<script class="fl-js-dynamic">
{literal}

// Static map handler
flUtil.loadScript(rlConfig['tpl_base'] + 'components/popup/_popup.js', function(){
    $('.map-capture').popup({
        fillEdge: true,
        scroll: false,
        content: '<div id="map_fullscreen"></div>',
        onShow: function(){
            flUtil.loadStyle(rlConfig['mapAPI']['css']);
            flUtil.loadScript(rlConfig['mapAPI']['js'], function(){
                window.location.hash = 'map-fullscreen';

                flMap.init($('#map_fullscreen'), {
                    control: 'topleft',
                    zoom: {/literal}{$config.map_default_zoom}{literal},
                    addresses: [{
                        latLng: '{/literal}{$location.direct}',
                        content: '{$location.show}{literal}'
                    }]
                });
            });
        },
        onClose: function(){
            history.pushState('', document.title, window.location.pathname + window.location.search);
            this.destroy();
        }
    });

    $(window).on('hashchange', function(e){
        var oe = e.originalEvent;

        if (oe.oldURL.indexOf('#map-fullscreen') >= 0 && oe.newURL.indexOf('#map-fullscreen') < 0) {
            $('.popup .close').trigger('click');
        }
    });
});

{/literal}
</script>

<!-- static map handler end -->
