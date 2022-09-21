<fieldset class="light">
    <legend id="legend_cats" class="up" onclick="fieldset_action('cats');">{$lang.categories}</legend>
    <div id="cats">
        <div id="cat_checkboxed" style="margin: 0 0 8px;{if $sPost.cat_sticky}display: none{/if}">
            <div class="tree">
                {foreach from=$sections item='section'}
                    <fieldset class="light">
                        <legend id="legend_section_{$section.ID}" class="up" onclick="fieldset_action('section_{$section.ID}');">{$section.name}</legend>
                        <div id="section_{$section.ID}">
                            {if !empty($section.Categories)}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_checkbox.tpl' categories=$section.Categories first=true}
                            {else}
                                <div style="padding: 0 0 8px 10px;">{$lang.no_items_in_sections}</div>
                            {/if}
                        </div>
                        {if $section && $section.Categories && $section.Categories|@count > 5}
                            <div class="grey_area">
                                <span>
                                    <span onclick="$('#section_{$section.ID} input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#section_{$section.ID} input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </span>
                            </div>
                        {/if}
                    </fieldset>
                {/foreach}
            </div>

            <div style="padding: 0 0 6px 37px;">
                <label><input {if !empty($sPost.subcategories)}checked="checked"{/if} type="checkbox" name="subcategories" value="1" /> {$lang.include_subcats}</label>
            </div>
        </div>

        <script type="text/javascript">
        var tree_selected = {if $smarty.post.categories}[{foreach from=$smarty.post.categories item='post_cat' name='postcatF'}['{$post_cat}']{if !$smarty.foreach.postcatF.last},{/if}{/foreach}]{else}false{/if};
        var tree_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}['{$parent_point}']{if !$smarty.foreach.parentF.last},{/if}{/foreach}]{else}false{/if};
        {literal}

        $(document).ready(function(){
            flynax.treeLoadLevel('checkbox', 'flynax.openTree(tree_selected, tree_parentPoints)', 'div#cat_checkboxed');
            flynax.openTree(tree_selected, tree_parentPoints);

            $('input[name=cat_sticky]').click(function(){
                $('#cat_checkboxed').slideToggle();
                $('#cats_nav').fadeToggle();
            });
        });

        {/literal}
        </script>

        <div class="grey_area">
            <label><input class="checkbox" {if $sPost.cat_sticky}checked="checked"{/if} type="checkbox" name="cat_sticky" value="true" /> {$lang.sticky}</label>
            <span id="cats_nav" {if $sPost.cat_sticky}class="hide"{/if}>
                <span onclick="$('#cat_checkboxed div.tree input').attr('checked', true);" class="green_10">{$lang.check_all}</span>
                <span class="divider"> | </span>
                <span onclick="$('#cat_checkboxed div.tree input').attr('checked', false);" class="green_10">{$lang.uncheck_all}</span>
            </span>
        </div>

    </div>
</fieldset>
