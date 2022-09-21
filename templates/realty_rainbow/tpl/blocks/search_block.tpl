<!-- search form block -->

{assign var='spage_key' value=$listing_types[$form.listing_type].Page_key}
<form method="{$listing_types[$form.listing_type].Submit_method}" action="{$rlBase}{if $config.mod_rewrite}{$pages.$spage_key}/{$search_results_url}.html{else}?page={$pages.$spage_key}&amp;{$search_results_url}{/if}">
	<input type="hidden" name="form" value="{$form_key}" />
	
	{foreach from=$hidden_fields item='hField_val' key='hField_key'}
	<input type="hidden" name="f[{$hField_key}]" value="{$hField_val}" />	
	{/foreach}

	{foreach from=$form item='group'}{strip}
		{if $group.Group_ID && $group|@is_array}		
			{if $group.Fields && $group.Display}
				{assign var='hide' value=false}
			{else}
				{assign var='hide' value=true}
			{/if}
			
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.Group_ID name=$lang[$group.pName]}
				{if $group.Fields}
					{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
				{else}
					{$lang.no_items_in_group}
				{/if}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
		{else}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search_box.tpl' fields=$group.Fields}
		{/if}
	{/strip}{/foreach}
	
	{if $group.With_picture}
		<div class="search-item">
			<label>
				<input name="f[with_photo]" type="checkbox" value="true" />
				{$lang.with_photos_only}
			</label>
		</div>
	{/if}

	<input type="submit" name="search" value="{$lang.search}" />	
</form>
<!-- search form block -->
