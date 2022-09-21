<!-- load more button tpl -->

<div class="text-center" id="ads-block-{$block.ID}">
    <input class="pl-5 pr-5" type="button" value="{$lang.nova_load_more_listings}" data-phrase="{$lang.nova_load_more_listings}" />
</div>

{php}
$GLOBALS['rlSmarty']->assign('selected_listing_ids', implode(',', $GLOBALS['rlListings']->selectedIDs));
{/php}

<script class="fl-js-dynamic">
{literal}

$(function(){
    var box_id  = 'ads-block-{/literal}{$block.ID}{literal}';
    var $cont   = $('#' + box_id);
    var $box    = $cont.prev();
    var $button = $cont.find('input[type=button]');

    var data = {
        {/literal}
        mode: 'novaLoadMoreListings',
        key: '{if $block.Plugin == 'listings_box'}{$block.Key}{else}{$block.Key|replace:'ltfb_':''}{/if}',
        type: '{if $block.Plugin == 'listings_box'}listings_box{else}featured{/if}',
        ids: '{$selected_listing_ids}',
        total: $box.find('> li').length,
        side_bar_exists: {if $side_bar_exists}1{else}0{/if},
        block_side: '{$block.Side}'
        {literal}
    };

    $button.width($button.width());

    $button.click(function(){
        $(this).val(lang['loading']);

        flUtil.ajax(data, function(response, status){
            if (status == 'success' && response.status == 'OK') {
                if (response.results.html) {
                    var $html = $(jQuery.parseHTML(response.results.html)[2]);
                    $listings = $html.find('> li').unwrap();

                    if (typeof $.convertPrice == 'function') {
                        $listings.find('.price_tag > *:not(nav)').each(function(){
                            $(this).convertPrice();
                        });
                    }

                    $box.append($listings);

                    flFavoritesHandler();

                    if (response.results.next) {
                        data.total += parseInt(response.results.count);
                        data.ids += ',' + response.results.ids;
                    } else {
                        $cont.remove();
                    }
                } else {
                    $cont.remove();
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            $button.val($button.data('phrase'));
        });
    });
});

{/literal}
</script>

<!-- load more button tpl end -->
