<!-- home page Drag and Drop block -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$block.name key=$block.Key loading=$block.Ajax fixed=$block.Fixed navigation=true}
    {if $block.Ajax}<div class="hide white" id="{$block.Key}_container"></div>{/if}
    {php}
        eval($this->_tpl_vars['block']['Content']);
    {/php}
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<!-- home page Drag and Drop block end -->
