<!-- alphabetic account search -->

{assign var='at_page_key' value='at_'|cat:$account_type.Key}

{strip}
<div class="alphabetic-saerch">
	<div>
		{foreach from=$alphabet item='character' name='alphaF'}
            {if $smarty.foreach.alphaF.iteration == 1}
                {pageUrl key=$at_page_key assign='characterUrl'}
            {else}
                {if $config.mod_rewrite}
                    {assign var='characterUrl' value=$rlBase|cat:$pages.$at_page_key|cat:'/'|cat:$character|cat:'.html'}
                {else}
                    {assign var='characterUrl' value=$rlBase|cat:'?page='|cat:$pages.$at_page_key|cat:'&character='|cat:$character}
                {/if}
            {/if}

			<a href="{$characterUrl}"
                class="{if $character == $char}active{/if}{if $smarty.foreach.alphaF.iteration == 1 || $smarty.foreach.alphaF.iteration == 2} wide{/if}">
                {$character}
            </a>
		{/foreach}
	</div>
</div>
{/strip}

<!-- alphabetic account search end -->
