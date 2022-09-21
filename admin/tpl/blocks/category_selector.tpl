<!-- category selector -->

{if $namespace}<div class="namespace-{$namespace}">{/if}

<ul class="select-type{if $sections && $sections|@count <= 1 || $single_type} hide{/if}">
    {foreach from=$sections item='section' name='sectionsF'}{strip}
        <li><label><input {if $smarty.foreach.sectionsF.first}checked="checked"{/if} type="radio" name="section{if $namespace}_{$namespace}{/if}" value="{$section.Key}"> {$section.name}</label></li>
    {/strip}{/foreach}
</ul>

{if $single_type}
    {php}
        $single_type = $this -> get_template_vars('single_type');
        $categories = $this -> get_template_vars('categories');

        $sections[0] = array(
            'Key' => $single_type,
            'Categories' => $categories
        );

        $this -> assign_by_ref('sections', $sections);
    {/php}
{/if}

<ul class="select-category">
    {foreach from=$sections item='section' name='sectionsF'}{strip}
        <li id="type_section_{$section.Key}{if $namespace}_{$namespace}{/if}" class="{if !$smarty.foreach.sectionsF.first}hide{/if}">
            {if !empty($section.Categories)}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level.tpl' categories=$section.Categories mode=$mode}
            {else}
                <div><select disabled="disabled"><option value="">{$lang.no_items_in_sections}</option></select></div>
            {/if}
        </li>
    {/strip}{/foreach}
</ul>

<div class="form-buttons{if $mode} {$mode}{/if}">
    <a href="javascript:void(0)" class="button disabled">{if $button}{$button}{else}{$lang.next}{/if}</a>
    <a class="cancel" href="javascript:void(0)" onclick="$(this).closest('div.block').parent().slideUp();">{$lang.cancel}</a>
</div>

{if $namespace}</div>{/if}

<script>flynax.treeLoadLevel('', '{$callback}', '', '{$namespace}', '{$mode}');</script>

<!-- category selector end -->
