<!-- search tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.ui.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/datePicker/i18n/ui.datepicker-{$smarty.const.RL_LANG_CODE}.js"></script>

<!-- tabs -->
<ul class="tabs tabs-hash">
	{if $search_forms|@count > 0}
		{foreach from=$search_forms item='search_form' key='sf_key' name='sformsF'}
			{assign var='tab_phrase' value='listing_types+name+'|cat:$listing_types[$sf_key].Key}
			<li {if $smarty.foreach.sformsF.first}class="{if $smarty.foreach.sformsF.first && !$keyword_search}active{/if}"{/if} id="tab_{$sf_key|replace:'_':''}">
                <a href="#{$sf_key}" data-target="{$sf_key|replace:'_':''}">{$lang[$tab_phrase]}</a>
            </li>
		{/foreach}
	{/if}

	<li {if $keyword_search || !$search_forms}class="active"{/if} id="tab_keyword">
        <a href="#keyword" data-target="keyword">{phrase key='keyword_search'}</a>
    </li>
</ul>
<!-- tabs end -->

<div class="content-padding">
	{foreach from=$search_forms item='search_form' key='sf_key' name='sformsF'}
		{assign var='spage_key' value=$listing_types[$sf_key].Page_key}

		<div id="area_{$sf_key|replace:'_':''}" class="tab_area{if !$smarty.foreach.sformsF.first || $keyword_search} hide{/if}">
			<form method="{$listing_types[$sf_key].Submit_method}" action="{$rlBase}{if $config.mod_rewrite}{$pages.$spage_key}/{$search_results_url}.html{else}?page={$pages.$spage_key}&amp;{$search_results_url}{/if}">
				<input type="hidden" name="action" value="search" />
				{assign var='post_form_key' value=$sf_key|cat:'_quick'}
				<input type="hidden" name="post_form_key" value="{$post_form_key}" />

				{foreach from=$search_form item='group' name='qsearchF'}
					{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fields_search.tpl' fields=$group.Fields}
				{/foreach}

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
						{if $listing_types[$sf_key].Advanced_search && $listing_types[$sf_key].Advanced_search_availability}
							<a title="{$lang.advanced_search}" href="{$rlBase}{if $config.mod_rewrite}{$pages.$spage_key}/{$advanced_search_url}.html{else}?page={$pages.$spage_key}&amp;{$advanced_search_url}{/if}">{$lang.advanced_search}</a>
						{/if}
					</div>
				</div>
			</form>
		</div>
	{/foreach}

	<div id="area_keyword" class="tab_area{if !$keyword_search && $search_forms|@count > 0} hide{/if}">
		<form class="kws-block" method="post" action="{$rlBase}{if $config.mod_rewrite}{$pages.search}.html{else}?page={$pages.search}{/if}">
			<input type="hidden" name="form" value="keyword_search" />

			<div class="two-inline">
				<div><input type="submit" name="search" value="{$lang.go}" /></div>
				<div style="padding-{$text_dir_rev}: 10px;"><input type="text" maxlength="255" name="f[keyword_search]" {if $smarty.post.f.keyword_search}value="{$smarty.post.f.keyword_search}"{/if} /></div>
			</div>

			<div class="options">
				<ul>
					{assign var='tmp' value=3}
					{section name='keyword_opts' loop=$tmp max=3}
						<li><label><input {if $fVal.keyword_search_type || $keyword_mode}{if $smarty.section.keyword_opts.iteration == $fVal.keyword_search_type || $keyword_mode == $smarty.section.keyword_opts.iteration}checked="checked"{/if}{else}{if $smarty.section.keyword_opts.iteration == $config.keyword_search_type}checked="checked"{/if}{/if} value="{$smarty.section.keyword_opts.iteration}" type="radio" name="f[keyword_search_type]" /> {assign var='ph' value='keyword_search_opt'|cat:$smarty.section.keyword_opts.iteration}{$lang.$ph}</label></li>
					{/section}
				</ul>
			</div>
		</form>

		<div class="listings-area">
		{if !empty($listings)}
			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid_navbar.tpl'}

			{include file='blocks'|cat:$smarty.const.RL_DS|cat:'grid.tpl' hl=trye}
			<script type="text/javascript">flynaxTpl.highlightResults($('#area_keyword input[name="f\[keyword_search\]"]').val());</script>

			<!-- paging block -->
			{if $config.mod_rewrite}
				{paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$category.Path var='listing'}
			{else}
				{paging calc=$pInfo.calc total=$listings|@count current=$pInfo.current per_page=$config.listings_per_page url=$category.ID var='category'}
			{/if}
			<!-- paging block end -->
		{else}
			{if $keyword_search}
				{if $config.mod_rewrite}
					{assign var='href' value=$rlBase|cat:$pages.add_listing|cat:'.html'}
				{else}
					{assign var='href' value=$rlBase|cat:'index.php?page='|cat:$pages.add_listing}
				{/if}

				{assign var='link' value='<a href="'|cat:$href|cat:'">$1</a>'}
				<div class="info">{$lang.no_listings_found|regex_replace:'/\[(.+)\]/':$link}</div>
			{/if}
		{/if}
		</div>
	</div>
</div>

<script class="fl-js-static">
{literal}

flUtil.loadScript(rlConfig['tpl_base'] + 'js/form.js', function(){
    flForm.realtyPropType();
});

{/literal}
</script>

<!-- search tpl end -->
