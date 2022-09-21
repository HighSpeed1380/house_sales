<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>

<title>
{$config.site_name}
</title>

<meta name="generator" content="Flynax Classifieds Software" />
<meta http-equiv="Content-Type" content="text/html; charset={$config.encoding}" />
<meta name="robots" content="noindex, follow">

<link href="{$rlTplBase}css/print.css" type="text/css" rel="stylesheet" />

<link type="image/x-icon" rel="shortcut icon" href="{$rlTplBase}img/favicon.ico" />

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.js"></script>
<script src="{$smarty.const.RL_LIBS_URL}javascript/system.lib.js"></script>

{include file='js_config.tpl'}

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/cookie.js"></script>
<script type="text/javascript" src="{$rlTplBase}js/lib.js"></script>

</head>
<body{if $smarty.const.RL_LANG_DIR == 'rtl'} dir="rtl"{/if}>
	<div class="print-button">
		<input title="{$lang.print_page}" onclick="$(this).parent().hide();window.print();" type="button" value="{$lang.print_page}" />
	</div>

    {assign var='logo_ext' value='png'}
    {assign var='logo_file' value=$smarty.const.RL_ROOT|cat:'templates/'|cat:$config.template|cat:'/img/logo.svg'}

    {if is_file($logo_file)}
        {assign var='logo_ext' value='svg'}
    {/if}

	<div class="header">
		<div class="two-inline left clearfix">
			<img src="{$rlTplBase}img/logo.{$logo_ext}" />
			<div class="site-name">{$config.site_name}</div>
		</div>
	</div>

	<div class="container">	
		{if $smarty.get.item == 'listing'}
            <div class="two-inline">
                {if $price_tag_value}
                    {foreach from=$listing item='field_group'}
                        {if isset($field_group.Fields.time_frame) && $field_group.Fields.time_frame.value}
                            {assign var='time_frame_value' value=$field_group.Fields.time_frame.value}
                        {/if}
                        {if isset($field_group.Fields.sale_rent) && $field_group.Fields.sale_rent.source.0}
                            {assign var='sale_rent_value' value=$field_group.Fields.sale_rent.source.0}
                        {/if}
                    {/foreach}

                    <div class="price-tag">
                        <span>{$price_tag_value}</span>
                        {if $sale_rent_value == 2 && $time_frame_value} / {$time_frame_value}{/if}
                    </div>
                {/if}
    			<h1>{$listing_title}</h1>
            </div>

			{if $main_photo}
				<div class="pic-gallery">
					<img alt="" src="{$main_photo}" />
				</div>
			{/if}

			<div class="details clearfix">
				<div class="listing">
					<h2>{$lang.listing_details}</h2>

					<div>
						{foreach from=$listing item='group'}
							{if $group.Group_ID}
								{assign var='hide' value=true}
								{if $group.Fields && $group.Display}
									{assign var='hide' value=false}
								{/if}
						
								{assign var='value_counter' value='0'}
								{foreach from=$group.Fields item='group_values' name='groupsF'}
									{if $group_values.value == '' || !$group_values.Details_page}
										{assign var='value_counter' value=$value_counter+1}
									{/if}
								{/foreach}
						
								{if !empty($group.Fields) && ($smarty.foreach.groupsF.total != $value_counter)}
									<summary class="group-name">{$group.name}</summary>
									<section>
										{foreach from=$group.Fields item='item' key='field' name='fListings'}
											{if !empty($item.value) && $item.Details_page}
												{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
											{/if}
										{/foreach}
									</section>
								{/if}
							{else}
								{if $group.Fields}
									{foreach from=$group.Fields item='item'}
										{if !empty($item.value) && $item.Details_page}
											{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
										{/if}
									{/foreach}
								{/if}
							{/if}
						{/foreach}
					</div>
				</div>

                {assign var='type_replace' value=`$smarty.ldelim`account_type`$smarty.rdelim`}
                
                {if $allow_contacts}
				<div class="owner">
					<h2>{$lang.account_type_details|replace:$type_replace:$seller_info.Type_name}</h2>

					<div>
						<div class="profile two-inline clearfix">
							{if $seller_info.Photo}
								<div class="picture">
									<img style="{strip}
                                            background-image: url('{$smarty.const.RL_FILES_URL}{$seller_info.Photo}');
                                            width:{if $seller_info.Thumb_width}{$seller_info.Thumb_width}{else}110{/if}px;
                                            height:{if $seller_info.Thumb_height}{$seller_info.Thumb_height}{else}100{/if}px;{/strip}" 
                                        alt="{$lang.seller_thumbnail}" 
                                        src="{$rlTplBase}img/blank.gif" />
								</div>
							{/if}
							<div class="summary">
								{assign var='date_replace' value=`$smarty.ldelim`date`$smarty.rdelim`}
								{assign var='date' value=$seller_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}

								<span class="name">{$seller_info.Full_name}</span>
								<div class="type">{$lang.account_type_since_data|replace:$type_replace:$seller_info.Type_name|replace:$date_replace:$date}</div>

								{if $seller_info.Fields.about_me.value}
									<div class="about">{$seller_info.Fields.about_me.value}</div>
								{/if}
							</div>
						</div>

						{if $seller_info.Fields}
							<summary class="group-name">{$lang.personal_details}</summary>
							<section>
								{foreach from=$seller_info.Fields item='item'}
									{if !empty($item.value) && $item.Details_page && $item.Key != 'First_name' && $item.Key != 'Last_name' && $item.Key != 'about_me'}
										{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
									{/if}
								{/foreach}

								{if $seller_info.Display_email}
									<div class="table-cell small">
										<div class="name">{$lang.mail}</div>
										<div class="value">{if $allow_contacts}{encodeEmail email=$seller_info.Mail}{else}{assign var='mail_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}{$lang.fake_value|replace:$mail_replace:$lang.mail}{/if}</div>
									</div>
								{/if}
							</section>
						{/if}
					</div>
				</div>
				{/if}
			</div>

			{if $config.map_module && $location && $config.google_map_key}
				<div class="map">
                    <img alt="{$lang.expand_map}" 
                         src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=150}" 
                         srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=150 scale=2} 2x" />
					<span class="media-enlarge"><span></span></span>
				</section>
			{/if}
		{elseif $smarty.get.item == 'browse' || $smarty.get.item == 'search' || $smarty.get.item == 'listings' }
			<table class="sTable">
			<tr>
                {assign var='replace_category' value=`$smarty.ldelim`category`$smarty.rdelim`}
				<td><h1>{if $smarty.get.item == 'browse'}{$lang.listings_of_category|replace:$replace_category:$rss.title}{else}{$lang.search_results}{/if}</h1></td>
				<td align="right"><input title="{$lang.print_page}" onclick="window.print();$(this).slideUp('slow');" type="button" value="{$lang.print_page}" /></td>
			</tr>
			</table>
			<div class="sLine"></div>

			<div id="listings">
				{if !empty($listings)}
					{foreach from=$listings item='listing' key='key'}
					
					<div style="padding: 13px 0;border-bottom: 1px #ccc solid;">
						<table class="sTable">
						<tr>
							<td rowspan="2" style="width: 100px;padding: 0 10px 0 0;" align="center" valign="top">
								<img src="{if empty($listing.Main_photo)}{$rlTplBase}img/no-picture.jpg{else}{$smarty.const.RL_FILES_URL}{$listing.Main_photo}{/if}" />
							</td>
							<td class="spliter" rowspan="2"></td>
							<td valign="top" style="height: 65px">
								<table>
								{foreach from=$listing.fields item='item' key='field' name='fListings'}
								{if !empty($item.value)}
								<tr>
									<td valign="top" align="left">
										<div class="field">{$item.name}:</div>
									</td>
									<td style="width: 3px;"></td>
									<td valign="top" align="left">
										<div class="value">
										{if $smarty.foreach.fListings.first}
											<b>{$item.value}</b>
										{else}
											{$item.value}
										{/if}
										</div>
									</td>
								</tr>
								{/if}
								{/foreach}
								<tr>
									<td valign="top" align="left"><div class="field">{$lang.category}:</div></td>
									<td style="width: 3px;"></td>
									<td valign="top" align="left">
										<div class="value">{$listing.name}</div>
									</td>
								</tr>
								</table>
							</td>
						</tr>
						</table>
					</div>
					
					{/foreach}
				{/if}
			</div>
		{/if}

		{rlHook name='tplPrintPage'}

		<div class="footer">
			<span>&copy; {$smarty.now|date_format:'%Y'}, {$lang.powered_by} </span><a title="{$lang.powered_by} {$lang.copy_rights}" href="{$lang.flynax_url}">{$lang.copy_rights}</a>
		</div>

	</div>
</body>
</html>
