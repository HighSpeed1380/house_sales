<!-- home page search box tpl -->

{assign var='first_form_key' value=$search_forms|@key}
{assign var='is_form_arranged' value=false}
{if '/_tab[0-9]$/'|preg_match:$first_form_key}
    {assign var='is_form_arranged' value=true}
{/if}

<div class="point1{if $pageInfo.Key == 'home'} header-tabs{if $search_forms|@count > 1 && !$is_form_arranged} header-tabs__tabs-exists{/if}{/if}">
<!-- tabs -->

{if $search_forms|@count > 1 && !$is_form_arranged}
    <ul class="tabs tabs-hash {if $search_forms|@count < 5} tabs_count_{$search_forms|@count}{/if}">
        {foreach from=$search_forms item='search_form' key='sf_key' name='stabsF'}
            {assign var='listing_type_color' value=$listing_types[$search_form.listing_type].Color}
            <li id="tab_{$sf_key}" class="{if $smarty.foreach.stabsF.first}active{/if}">
                <a href="#{$sf_key}" data-target="{$sf_key}"
                    {if $pageInfo.Key == 'home' && $listing_type_color}
                    {assign var='color' value=$listing_type_color|str_split:2}
                    style="background-color: rgba({$color.0|hexdec}, {$color.1|hexdec}, {$color.2|hexdec}, 0.36);"
                    {/if}
                    >
                    {if $pageInfo.Key == 'home' && $listing_types[$search_form.listing_type].Menu && $listing_types[$search_form.listing_type].Menu_icon}
                        <span class="tab-icon">
                            {fetch file=$smarty.const.FL_TPL_ROOT|cat:'img/icons/'|cat:$listing_types[$search_form.listing_type].Menu_icon}
                        </span>
                    {/if}
                    {$search_form.name}
                </a>
            </li>
        {/foreach}
    </ul>
{/if}
<!-- tabs end -->

<div class="horizontal-search">
    <div class="search-block-content">
        {foreach from=$search_forms item='search_form' key='sf_key' name='sformsF'}
            {assign var='spage_key' value=$listing_types[$search_form.listing_type].Page_key}
            {assign var='listing_type' value=$listing_types[$search_form.listing_type]}
            {assign var='post_form_key' value=$sf_key}

            <div id="area_{$sf_key}" class="search_tab_area{if !$smarty.foreach.sformsF.first} hide{/if}">
                <form name="map-search-form"
                      class="d-flex flex-wrap"
                      accesskey="{$search_form.listing_type}"
                      method="{$listing_type.Submit_method}"
                      action="{pageUrl key=$spage_key add_url=$search_results_url}">{strip}
                    <input type="hidden" name="action" value="search" />
                    <input type="hidden" name="post_form_key" value="{$post_form_key}" />

                    <!-- tabs -->
                    {if $search_forms|@count > 1 && $is_form_arranged}
                    <div class="search-form-cell form-switcher">
                        <div class="align-items-end">
                            <span>{if $search_form.arrange_field}{phrase key='listing_fields+name+'|cat:$search_form.arrange_field}{else}{$lang.listing_type}{/if}</span>
                            <div>
                                {if $search_forms|@count > 3}
                                <select name="pills_{$sf_key}">
                                    {foreach from=$search_forms item='search_pill' key='pill_key'}
                                    <option value="{$pill_key}"{if $sf_key == $pill_key} selected="selected"{/if}>{$search_pill.name}</option>
                                    {/foreach}
                                </select>
                                {else}
                                <span class="pills" data-key="{$sf_key}">
                                    {foreach from=$search_forms item='search_pill' key='pill_key'}
                                        <label data-key="{$pill_key}" title="{$search_pill.name}">
                                            <input type="radio" value="{$pill_key}" name="pills_{$sf_key}" {if $sf_key == $pill_key}checked="checked"{/if} />
                                            {$search_pill.name}
                                        </label>
                                    {/foreach}
                                </span>
                                {/if}
                            </div>
                        </div>
                    </div>
                    {/if}
                    <!-- tabs end -->

                    {foreach from=$search_form.data item='item'}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_horizontal.tpl' fields=$item.Fields}
                    {/foreach}

                    <div class="search-form-cell submit">
                        <div>
                            <span></span>
                            <div>
                                <input type="submit" value="{$lang.search}" />
                            </div>
                        </div>
                    </div>
                {/strip}</form>
            </div>
        {/foreach}

        {if $search_forms|@count > 1 && $is_form_arranged}
        <script class="fl-js-dynamic">
        {literal}

        (function(){
            $('.form-switcher label').click(function(e){
                e.stopPropagation();
                searchFormSwitcher($(this).data('key'));
                return false;
            });

            $('.form-switcher select').change(function(e){
                e.stopPropagation();
                searchFormSwitcher($(this).val());
                return false;
            });

            var searchFormSwitcher = function(key){
                $('.search-block-content > .search_tab_area').addClass('hide');
                $('#area_' + key).removeClass('hide');
            }
        })();

        {/literal}
        </script>
        {/if}
    </div>
</div>
</div>

<!-- home page search box tpl end -->
