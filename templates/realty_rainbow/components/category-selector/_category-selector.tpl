<!-- Category dropdowns -->

<ul class="select-type{if $dropdown_data|@count <= 1} hide{/if}">
    {foreach from=$dropdown_data item='dropdown_item' name='sectionsF'}{strip}
        {if $dropdown_item.Admin_only}
            {continue}
        {/if}

        <li>
            <label>
                <input type="radio"
                       name="section{if $section_postfix}_{$section_postfix}{/if}"
                       value="{$dropdown_item.Key}"
                       data-path="{$pages[$dropdown_item.Page_key]}"
                       data-no-user-category="{if $no_user_category || !$dropdown_item.Cat_custom_adding}true{else}false{/if}"
                       {if isset($dropdown_item.Single_category)}
                       data-single-category-id="{$dropdown_item.Single_category.ID}"
                       data-single-category-path="{$dropdown_item.Single_category.Path}"
                       data-single-category-name="{$dropdown_item.Single_category.name}"
                       {/if}
                       /> {$dropdown_item.name}
            </label>
        </li>
    {/strip}{/foreach}
</ul>

<ul class="select-category">
    {foreach from=$dropdown_data item='dropdown_item' name='sectionsF'}{strip}
        {if $dropdown_item.Admin_only || isset($dropdown_item.Single_category)}
            {continue}
        {/if}
        
        <li data-type-key="{$dropdown_item.Key}" class="row{if ($smarty.foreach.sectionsF.first && !$addListing->listingType) || ($addListing->listingType && $addListing->listingType.Key == $dropdown_item.Key)} show{/if}">
            <div class="col-md-4"><select size="10" class="tmp" data-no-data-phrase="{$lang.no_items_in_sections}"><option value="0" class="locked">{$lang.loading}</option></select></div>
        </li>
    {/strip}{/foreach}
</ul>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_select.tpl'}

<!-- Category dropdowns end -->
