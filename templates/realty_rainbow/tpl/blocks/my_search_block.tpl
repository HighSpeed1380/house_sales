<!-- my search block tpl -->

<section class="side_block_search">
    <form id="my-search-form" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$search_results_url}.html{else}?page={$pageInfo.Path}&{$search_results_url}{/if}">
        <input type="hidden" name="action" value="search" />

        <div class="search-item single-field{if $listing_types|@count == 1} hide{/if}">
            <select id="search_type_select" name="search_type">
                <option value="" {if !$selected_search_type}selected="selected"{/if}>{$lang.any_of_listing_type}</option>

                {foreach from=$listing_types item='search_type'}
                    <option value="{$search_type.Key}"
                        {if $selected_search_type == $search_type.Key
                            || $listing_types|@count === 1}selected="selected"{/if}>
                        {$search_type.name}
                    </option>
                {/foreach}
            </select>
        </div>

        {if $search_forms && $listing_types}
            <div id="search-forms" class="hide">
                {if $listing_types|@count > 1}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='searchType'}
                {/if}

                {foreach from=$listing_types item='listing_type' key='listing_type_key'}
                    <div id="form_{$listing_type_key}" class="hide form"></div>
                {/foreach}

                {if $listing_types|@count > 1}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                {/if}
            </div>
        {/if}

        <div class="search-footer clearfix">
            {if $group.With_picture}
            <div class="search-item">
                <div class="field"></div>
                <label><input {if $smarty.request.f.with_photo}checked="checked"{/if} type="checkbox" name="f[with_photo]" value="true" /> {$lang.with_photos_only}</label>
            </div>
            {/if}

            <div class="align-button">
                <input id="search-button" class="search_field_item button" type="submit" name="search" value="{$lang.search}" />
            </div>
        </div>
    </form>
</section>

{if $search_forms && $listing_types}
    <div id="search-forms-fields">
        {foreach from=$listing_types item='listing_type' key='listing_type_key'}
            <form>
                <div id="fields_{$listing_type_key}" class="hide">
                    {assign var="search_form" value=$search_forms.$listing_type_key}
                    {assign var="listing_type" value=$listing_type}

                    <input type="hidden" name="post_form_key" value="{$listing_type.Key|cat:'_myads'}" />

                    {if $search_form}
                        {foreach from=$search_form item='group'}{strip}
                            {if $group.Group_ID}
                                {if $group.Fields && $group.Display}
                                    {assign var='hide' value=false}
                                {else}
                                    {assign var='hide' value=true}
                                {/if}

                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.Group_ID name=$lang[$group.pName]}
                                    {if $group.Fields}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                                    {else}
                                        {$lang.no_items_in_group}
                                    {/if}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                            {else}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                            {/if}
                        {/strip}{/foreach}
                    {else}
                        {$lang.search_form_empty}
                    {/if}
                </div>
            </form>
        {/foreach}
    </div>
{/if}

<script class="fl-js-dynamic">
var selected_type = '{if $selected_search_type}{$selected_search_type}{elseif $listing_types|@count === 1}{$listing_types|@key}{/if}';

{literal}
    $(document).ready(function(){
        // general container of search forms
        var $formsContainer = $('#search-forms');

        // show type which was selected in previous page
        if (selected_type) {
            $('#fields_' + selected_type)
                .appendTo($('#form_' + selected_type).removeClass('hide'))
                .removeClass('hide');
            $formsContainer.removeClass('hide');
        }

        // add handler to select of types
        $('#search_type_select').change(function(){
            // selected listing type
            var type = $(this).find('option:selected').val() != '0' ? $(this).find('option:selected').val() : '';

            // hide all forms
            $formsContainer.find('.form').addClass('hide');

            // show selected form if it exist
            if (type) {
                var $appendContainer = $('#form_' + type);
                $formsContainer.removeClass('hide');

                // move all forms to hidden container
                $formsContainer.find('.form').each(function(){
                    if ($(this).children().length) {
                        var $moved_form = $(this).children().detach();
                        $moved_form.addClass('hide').appendTo($('#search-forms-fields'));
                    }
                });

                // move fields of selected search form
                var $content = $('#fields_' + type).length ? $('#fields_' + type).detach() : null;

                if ($content) {
                    $content.appendTo($appendContainer);
                    $content.removeClass('hide');
                    $content.parent().removeClass('hide');
                }
            }
            // hide forms & disable submit button
            else {
                $formsContainer.addClass('hide');
            }
        });
    });

    // added "Loading..." text after submit of form
    $('#my-search-form').submit(function(){
        $('#search-button').val(lang['loading']).addClass('disabled').attr('disabled', true);
        return true;
    });
{/literal}</script>

<!-- my search block tpl end -->
