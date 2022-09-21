<!-- statistics box -->

{if $statistics_block}
    {addCSS file=$rlTplBase|cat:'components/statistics/statistics.css'}

    {if !$side_bar_exists && !$block.Tpl && ($block.Side == 'top' || $block.Side == 'middle' || $block.Side == 'bottom')}
        {assign var='stat_class' value=' statistics-box_full-width'}

        <script class="fl-js-dynamic">
        {literal}

        (function(){
            var arrangeStatBox = function(){
                var $statBox  = $('.statistics-box');

                $statBox.removeAttr('style');

                var box_width = $statBox.width();
                var doc_width = $(document).width();
                var margin    = (doc_width - box_width) / 2 * -1;

                $statBox.css({
                    marginLeft: margin,
                    marginRight: margin
                })
            }

            $(window).resize(function(){
                arrangeStatBox();
            });

            arrangeStatBox();
        })();

        {/literal}
        </script>
    {/if}

    <script>
    {literal}

    $(function(){
        var $statBox = $('.statistics-box');
        var animated = false;
        var steps = 50;
        var startFrom = 20; // %
        var timeout = 0.035 * 1000;

        var formatNumber = function(number){
            return String(number).replace(/(.)(?=(\d{3})+$)/g,'$1' + rlConfig['price_delimiter']);
        }

        var animate = function($item, max, step, count, current){
            if (current > max) {
                return;
            }

            var setCount = current == max ? count : Math.floor(current * step);
            $item.text(formatNumber(setCount));

            current++;

            setTimeout(function(){
                animate($item, max, step, count, current);
            }, timeout);
        }

        var initAnimation = function(){
            if (animated) {
                return;
            }

            setTimeout(function(){
                $statBox.find('.statistics-box__number').each(function(){
                    var count = parseInt($(this).data('count'));

                    if (count > 0) {
                        var max = count > steps ? steps : count;
                        var step = count > steps ? Math.floor(count / steps) : 1;
                        var start = Math.ceil((max * startFrom) / 100);
                        animate($(this), max, step, count, start);
                    }
                });
            }, 300);

            animated = true;
        }

        var checkScrollTop = function(){
            var boxTop = $statBox.offset().top;
            var boxBottom = $statBox.offset().top + $statBox.height();
            var scrollTop = $(window).scrollTop();
            var scrollBottom = scrollTop + $(window).height();

            if (boxTop >= scrollTop && boxBottom <= scrollBottom) {
                initAnimation();
            }
        }

        $(window).scroll(function(){
            checkScrollTop();
        });

        checkScrollTop();
    });

    {/literal}
    </script>

    {assign var='stats_col_class' value='col-6 col-sm-4 col-md'}

    {if $block.Side == 'left'}
        {assign var='stats_col_class' value='col-6 col-sm-4 col-md-3 col-lg-12 col-xl-6'}
    {elseif $block.Side == 'middle_left' || $block.Side == 'middle_right'}
        {assign var='stats_col_class' value='col-6 col-sm-4 col-md-6 col-lg-4 col-xl-3'}
    {else}
        {assign var='stats_col_class' value=$stats_col_class|cat:' mb-md-0'}
    {/if}

    <section class="statistics-box {$stat_class}">
        {if $stat_class}<div class="point1 mx-auto">{/if}
            <div class="position-relative row">
                {foreach from=$statistics_block key='stat_type_key' item='stat_item'}
                {if !$stat_item.is_account && (!isset($listing_types.$stat_type_key) || !$listing_types.$stat_type_key.Statistics)}{continue}{/if}

                <div class="{$stats_col_class} mb-3 d-flex justify-content-center">
                    <a class="text-center statistics-box__link text-decoration-none" href="{pageUrl key=$stat_item.page_key}">
                        <div class="pb-0 pb-md-2 d-flex statistics-box__count align-items-center justify-content-center">
                            <span class="statistics-box__number" data-count="{$stat_item.total}">
                            {if $stat_item.total > 0}
                                {math equation='ceil((20*count)/100)' count=$stat_item.total}
                            {else}
                                0
                            {/if}
                            </span>
                            {if $stat_item.total}
                            <span class="statistics-box__plus">+</span>
                            {/if}
                        </div>
                        <div class="statistics-box__name">{phrase key=$stat_item.phrase_key}</div>
                    </a>
                </div>
                {/foreach}
            </div>
        {if $stat_class}</div>{/if}
    </section>
{else}
    {$lang.statistics_isnot_available}
{/if}

<!-- statistics box end -->
