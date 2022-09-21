<section class="content_block{if !$block.Tpl} no-style{/if}{if isset($block.Header) && !$block.Header} no-header{/if}{if $block.Key|strpos:'ltcb_' === 0}{if ','|explode:$types|@count <= 1} categories-box-nav{/if}{/if}{if $block_class} {$block_class}{/if}">
	{if $block.Header}<h3>{if $name}{$name}{else}{$block.name}{/if}</h3>{/if}
	<div>