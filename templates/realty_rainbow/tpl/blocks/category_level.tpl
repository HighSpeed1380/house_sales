{strip}
<!-- category level -->

{if $category.Type}
	{assign var='cat_type' value=$category.Type}
{else}
	{assign var='cat_type' value=$section.Type}
{/if}

{assign var='replace' value=`$smarty.ldelim`category`$smarty.rdelim`}

<div>
<select size="10" {if $section.Key}class="section_{$section.Key}"{/if} id="tree_area_{if $category.ID}{$category.ID}{else}0{/if}">
	<option value="" class="disabled">{$lang.select}</option>
{foreach from=$categories item='cat' name='catF'}
	{if !empty($cat.Sub_cat) || ($cat.Add == '1' && $listing_types[$cat_type].Cat_custom_adding)}
		{assign var='sub_leval' value=true}
	{else}
		{assign var='sub_leval' value=false}
	{/if}
		
	<option title="{$cat.name}" {if $cat.Lock && !$sub_leval}disabled="disabled"{/if} class="{if !$sub_leval}no_child{/if} {if $cat.Lock}disabled{/if}" id="tree_cat_{$cat.ID}" value="{if $cat.Lock}javascript:void(0);{else}{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}/{if $cat.Tmp}tmp-category{else}{$cat.Path}{/if}/{if !$smarty.session.add_listing.no_plan_step}{$steps.plan.path}{else}{$steps.form.path}{/if}.html{if $cat.Tmp}?tmp_id={$cat.ID}{/if}{else}?page={$pageInfo.Path}&amp;step={if !$smarty.session.add_listing.no_plan_step}{$steps.plan.path}{else}{$steps.form.path}{/if}&amp;{if $cat.Tmp}tmp_id{else}id{/if}={$cat.ID}{/if}{/if}">{$cat.name} {if $cat.Lock}- {$lang.locked}{/if}</option>
{/foreach}
</select>

	{if $category && $listing_types[$cat_type].Cat_custom_adding && $category.Add == 1}
		{assign var='tmp_link' value='<a href="javascript:void(0);" class="add">$1</a>'}
		{assign var='cat_name' value='"'|cat:$category.name|cat:'"'}
		{assign var='replace' value=`$smarty.ldelim`category`$smarty.rdelim`}

		<span class="tmp-category">
			<span class="tmp_info">{$lang.tmp_category_info|regex_replace:'/\[(.*)\]/':$tmp_link|replace:$replace:$cat_name}</span>
			<span class="tmp_input hide">
				<input type="text" />
				<input value="{$lang.add}" onclick="xajax_addTmpCategory($(this).prev().val(), '{$category.ID}');$(this).val('{$lang.loading}');" type="button" />
				<span class="red margin">{$lang.cancel}</span>
			</span>
		</span>
	{/if}
</div>

<!-- category level end -->
{/strip}