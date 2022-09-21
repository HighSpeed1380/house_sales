<!-- Multi-Field Geo Filtering Box -->

<div class="gf-root">
    {if $config.mf_geo_block_autocomplete && $geo_box_data.levels_data[0]}
        {include file=$smarty.const.RL_PLUGINS|cat:'multiField'|cat:$smarty.const.RL_DS|cat:'autocomplete.tpl'}
    {/if}

    <div class="gf-box list-view{if $geo_box_data.levels_data[0]} gf-has-levels{/if}">
        {if $geo_filter_data.location}
            <ul class="list-unstyled gf-current">
                {foreach from=$geo_filter_data.location item='item' key='key'}
                    <li>
                        <span class="hborder"></span>
                        {$item.name}
                        <a title="{$item.name}"
                            {if $geo_filter_data.is_location_url && $item.Parent_path}
                                href="{$item.Parent_link}"
                            {else}
                                href="javascript://"
                                class="gf-ajax"
                                {if $item.Parent_path}data-path="{$item.Parent_path}"{else}data-link="{$item.Parent_link}"{/if}
                            {/if}><img class="remove" src="{$rlTplBase}img/blank.gif" />
                        </a>
                    </li>
                {/foreach}
            </ul>
        {/if}

        {assign var='gf_col_class' value='col-lg-6 col-md-12 col-sm-3'}

        {if $block.Side == 'top' || $block.Side == 'middle' || $block.Side == 'bottom'}
            {if $side_bar_exists}
                {assign var='gf_col_class' value='col-lg-3 col-md-4 col-sm-3'}
            {else}
                {assign var='gf_col_class' value='col-lg-2 col-md-3 col-sm-3'}
            {/if}
        {/if}

        {rlHook name='tplGFGeoBoxColClass'}

        {if $geo_box_data.levels_data[0]}
        <div class="gf-container">
            <ul class="list-unstyled row">
                {assign var="level_data" value=$geo_box_data.levels_data[0]}
                {foreach from=$level_data item="item"}
                    <li class="{$gf_col_class}">
                        <a title="{$item.name}"
                            {if $geo_filter_data.is_location_url}
                                href="{$item.Link}"
                            {else}
                                href="javascript://" class="gf-ajax"
                            {/if}
                           data-path="{$item.Path}" data-key="{$item.Key}">{$item.name}</a>
                    </li>
                {/foreach}
            </ul>
        </div>
        {elseif !$geo_filter_data.location}
            {$lang.mf_geo_box_default}
        {/if}
    </div>
</div>

{if $level_data}
<script class="fl-js-dynamic">
{literal}

$(function(){
    $box = $('.gf-box .gf-container');

    if ($box.closest('.special-block').length) {
        $('.gf-box').mCustomScrollbar();
    } else {
        $box.mCustomScrollbar()
    }
});

{/literal}
</script>
{/if}

<script class="fl-js-dynamic">
{literal}

$('.gf-box,.mf-autocomplete-dropdown').on('click', 'a.gf-ajax', function(){
    gfAjaxClick($(this).data('key'), $(this).data('path'), $(this).data('link'))
});

{/literal}
</script>
<!-- Multi-Field Geo Filtering Box end -->
