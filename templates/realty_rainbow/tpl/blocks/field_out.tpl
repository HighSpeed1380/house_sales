<!-- field output tpl -->

<div class="{strip}table-cell clearfix
        {if $group.Key == 'common'} col-md-6 col-sm-6 col-xs-12 two-columns{/if} 
        {if $small} small{/if}
        {if ($item.Type == 'checkbox' && $item.Opt1) || $item.Type == 'textarea'} wide-field
            {if $item.Type == 'textarea'} textarea{/if}
        {/if}
        {if $item.Type == 'phone'} phone{/if}{/strip}" 
    id="df_field_{$item.Key}">
	{if $item.Type == 'image' && $small}{else}
		<div class="name" title="{$item.name}">{if !$small}<div><span>{$item.name}</span></div>{else}{if $item.name}{$item.name}{else}{$lang[$item.pName]}{/if}{/if}</div>
	{/if}
	<div class="value{if $item.Type == 'image'} image{/if}">
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out_value.tpl'}
	</div>
</div>

<!-- field output tpl end -->
