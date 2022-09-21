<!-- account page listing search -->

<span class="expander"></span>

<section class="side_block_search">
    <div class="search-item single-field{if $search_forms|@count == 1} hide{/if}">
        <select id="search_type_select" name="search_type" class="w-100">
            {foreach from=$search_forms item='search_form'}
                <option value="{$search_form.0.Listing_type}"
                    {if $selected_search_type == $search_form.0.Listing_type}selected="selected"{/if}>
                    {phrase key='listing_types+name+'|cat:$search_form.0.Listing_type}
                </option>
            {/foreach}
        </select>
    </div>

    <div id="search-forms">
        {foreach from=$search_forms item='search_form' key='sf_key' name='searchForms'}
            <div id="area_{$sf_key}" class="search-form-area{if !(($selected_search_type && $selected_search_type == $sf_key) || (!$selected_search_type && $smarty.foreach.searchForms.first))} hide{/if}">
                <form method="post" 
                      action="{$account.Personal_address}{if $config.mod_rewrite}{$search_results_url}.html{else}&{$search_results_url}{/if}"
                      id="form_{$sf_key}">
                    <input type="hidden" name="form_key" value="{$search_form.0.Form_key}" />
                    <input type="hidden" name="listing_type_key" value="{$search_form.0.Listing_type}" />

                    {foreach from=$search_form item='group'}
                        {if $group.Fields.0.Key == 'posted_by'}
                            {continue}
                        {/if}

                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
                    {/foreach}

                    <div class="search-footer">
                        {if $group.With_picture}
                        <div class="search-item">
                            <div class="field"></div>
                            <label><input {if $smarty.request.f.with_photo}checked="checked"{/if} type="checkbox" name="f[with_photo]" value="true" /> {$lang.with_photos_only}</label>
                        </div>
                        {/if}

                        <div class="align-button">
                            <input class="search_field_item button" type="submit" name="search" value="{$lang.search}" />
                        </div>
                    </div>
                </form>
            </div>
        {/foreach}
    </div>
</section>

<script class="fl-js-dynamic">
{literal}

$(function(){
    var $formsContainer = $('#search-forms');

    $('#search_type_select').change(function(){
        var val = $(this).val();
        var $area = $formsContainer.find('#area_' + val);
        var $form = $formsContainer.find('#form_' + val);

        $formsContainer.find('.search-form-area:not(.hide)').addClass('hide');

        $area.removeClass('hide');
        $form.find('.search-item select').each(function(){
            $(this).val($(this).find('option:first').val());
        });
        $form.find('.search-item input').val('');
    });
});

{/literal}
</script>

<!-- account page listing search end -->
