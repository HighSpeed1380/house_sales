<!-- fieldset block -->

<div class="fieldset{if !$id} light{/if}{if $hide} hidden-default{/if}" {if $id}id="fs_{$id}"{/if}>
	<header {if $class}class="{$class}"{/if}>{if $id}<span class="arrow"></span>{/if}{$name}</header>
		
	<div class="body">
		<div>