<!-- remove listing tpl -->

{if $show_form}
<div class="highlight">
	{$lang.remote_delete_listing_confirm}
	<div style="padding: 15px 0 0 0;">
		<a class="no-underline" href="{$rlBase}{if $config.mod_rewrite}{$pages.listing_remove}.html?{else}?page={$pages.listing_remove}&amp;{/if}id={$smarty.get.id}&amp;hash={$smarty.get.hash}&amp;confirm">
			<input type="button" value="{$lang.delete}" />
		</a>
		<a class="no-underline" href="{$rlBase}{if $config.mod_rewrite}{$pages[$listing_type.My_key]}.html?{else}?page={$pages[$listing_type.My_key]}&amp;{/if}incomplete={$smarty.get.id}&amp;step={$listing.Last_step}">
			<input type="button" value="{$lang.complete_posting}" />
		</a>
		</a>
	</div>
</div>
{elseif isset($smarty.get.complete)}
    {phrase key="listing_removed"}
{/if}

<!-- remove listing tpl end -->