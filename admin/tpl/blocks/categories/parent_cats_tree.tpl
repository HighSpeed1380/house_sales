<!-- parent categories -->

{if $categories}
    <div id="section_{$type}">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' single_type=$type button=$lang.apply callback='flynax.openTree(tree_selected, tree_parentPoints, 1)'}
    </div>
{/if}

<!-- parent categories end -->
