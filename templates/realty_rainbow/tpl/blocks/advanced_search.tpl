<!-- advanced search form -->

<div class="content-padding">
	<form class="advanced-search-form" method="{$listing_type.Submit_method}" action="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{$advanced_search_url}/{$search_results_url}.html{else}?page={$pageInfo.Path}&amp;{$search_results_url}&amp;{$advanced_search_url}{/if}">
		<input type="hidden" name="action" value="search" />
		{assign var='post_form_key' value=$listing_type.Key|cat:'_advanced'}
		<input type="hidden" name="post_form_key" value="{$post_form_key}" />
		
		{foreach from=$search_form item='group'}
			{if $group.Group_ID}
				{if $group.Fields && $group.Display}
					{assign var='hide' value=false}
				{else}
					{assign var='hide' value=true}
				{/if}
				
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.ID name=$lang[$group.pName]}
				{if $group.Fields}
					{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search.tpl' fields=$group.Fields}
				{else}
					<div class="text-notice">{$lang.no_items_in_group}</div>
				{/if}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
			{else}
				{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search.tpl' fields=$group.Fields}
			{/if}
		{/foreach}
		
		<!-- sorting -->
		<div class="submit-cell">{strip}
			<div class="name">{$lang.sort_listings_by}</div>
			<div class="field search-item two-fields">
				<select name="f[sort_by]">
					<option value="0">{$lang.select}</option>
					{foreach from=$fields_list item='field'}
						{if $field.Type != 'checkbox'}
							<option value="{$field.Key}" {if $smarty.request.f.sort_by == $field.Key}selected="selected"{/if}>{$field.name}</option>
						{/if}
					{/foreach}
				</select>
				
				<select name="f[sort_type]">
					<option value="asc">{$lang.ascending}</option>
					<option value="desc" {if $smarty.request.f.sort_type == 'desc'}selected="selected"{/if}>{$lang.descending}</option>
				</select>
			</div>
		{/strip}</div>
		<!-- sorting end -->
		
        {if $group.With_picture}
    		<div class="submit-cell custom-padding">
    			<div class="name"></div>
    			<div class="field">
    				<label><input style="margin-{$text_dir}: 20px;" type="checkbox" name="f[with_photo]" value="true" /> {$lang.with_photos_only}</label>
    			</div>
    		</div>
        {/if}

		<div class="submit-cell">
			<div class="name"></div>
			<div class="field search-button">
				<input type="submit" name="search" value="{$lang.search}" />
				<a title="{$lang.quick_search}" href="{$rlBase}{if $config.mod_rewrite}{$pages.search}.html{else}?page={$pages.search}{/if}#{$listing_type.Key}">{$lang.quick_search}</a>
			</div>
		</div>
	</form>
</div>

<!-- advanced search form end -->
