    <footer class="page-footer content-padding">
        <div class="point1 clearfix">
            <div class="row no-gutters">
                {if $plugins.massmailer_newsletter}
                    <div class="newsletter col-12 col-xl-3 order-xl-2 mb-4">
                        <div class="row">
                            <p class="newsletter__text col-xl-12 col-md-6">{$lang.nova_newsletter_text}</p>
                            <div class="col-xl-12 col-md-6" id="nova-newsletter-cont">

                            </div>
                        </div>
                    </div>
                {/if}

                <nav class="footer-menu col-12{if $plugins.massmailer_newsletter} col-xl-9{/if}">
                    <div class="row">
                        {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}

                        <div class="mobile-apps col-lg-3">
                            <h4 class="footer-menu-title">{$lang.nova_mobile_apps}</h4>
                            <a class="d-inline-block pt-0 pt-lg-2 mb-lg-3" target="_blank" href="{$config.ios_app_url}">
                                <img src="{$rlTplBase}img/app-store-icon.svg" alt="App store icon" />
                            </a>
                            <a class="d-inline-block ml-3 ml-lg-0 mt-0" target="_blank" href="{$config.android_app_url}">
                                <img src="{$rlTplBase}img/play-market-icon.svg" alt="Play market icon" />
                            </a>
                        </div>
                    </div>
                </nav>
            </div>

            {include file='footer_data.tpl'}
        </div>
    </footer>

    {rlHook name='tplFooter'}
</div>

{if !$isLogin}
    <div id="login_modal_source" class="hide">
        <div class="tmp-dom">
            {include file='blocks/login_modal.tpl'}
        </div>
    </div>
{/if}

<script>
{literal}

(function(){
    $('#main_container').on('mouseover', '.listing-picture-slider', function(event){
        if ('ontouchstart' in window
            || navigator.maxTouchPoints > 0
            || navigator.msMaxTouchPoints > 0
        ) {
            return;
        }

        if (!this.sliderPicturesLoaded) {
            var id = $(this).data('id');
            var item = this;
            var counter = 0;

            var data = {
                mode: 'getListingPhotos',
                id: id
            };
            flUtil.ajax(data, function(response, status){
                if (status == 'success') {
                    if (response.status == 'OK') {
                        for (var i in response.data) {
                            if (i === '0') {
                                continue;
                            }

                            var index = parseInt(i) + 1;
                            var src = rlConfig['files_url'] + response.data[i].Thumbnail;

                            $(item).find('.pic-empty-' + index).attr('src', src);
                        }

                        $(item).find('img').one('load', function(){
                            counter++;

                            if (counter == (response.data.length - 1)) {
                                $(item).addClass('listing-picture-slider_loaded');
                            }
                        });
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }
            }, true);

            item.sliderPicturesLoaded = true;
        }
    });
})();

{/literal}
</script>

{include file='footerScript.tpl'}
