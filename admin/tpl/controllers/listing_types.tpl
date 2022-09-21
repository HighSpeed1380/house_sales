<!-- listing types tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplListingTypesNavBar'}

    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_type}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.types_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    <!-- add/edit type -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;key={$smarty.get.key}{/if}" method="post" enctype="multipart/form-data">
    <input type="hidden" name="submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
    {/if}
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.key}</td>
        <td class="field">
            <input {if $smarty.get.action == 'edit'}readonly="readonly"{/if} class="{if $smarty.get.action == 'edit'}disabled{/if}" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
        </td>
    </tr>

    <tr>
        <td class="name">
            <span class="red">*</span>{$lang.name}
        </td>
        <td class="field">
            {if $allLangs|@count > 1}
                <ul class="tabs">
                    {foreach from=$allLangs item='language' name='langF'}
                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                    {/foreach}
                </ul>
            {/if}

            {foreach from=$allLangs item='language' name='langF'}
                {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" class="w250" maxlength="50" /> <span class="field_description_noicon">
                {if $allLangs|@count > 1}{$lang.name} (<b>{$language.name}</b>)</span>
                    </div>
                {/if}
            {/foreach}
        </td>
    </tr>

    {if $tpl_settings.listing_type_color}
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/colorpicker/js/colorpicker.js"></script>

    <tr>
        <td class="name">{$lang.color}</td>
        <td class="field">
            {assign var='default_color' value='cccccc'}

            <div style="padding: 0 0 5px 0;">
                <input type="hidden" name="color" value="{$sPost.color}" />
                <div id="default_color" class="colorSelector">
                    <div style="background-color: #{if $sPost.color}{$sPost.color}{else}{$default_color}{/if}"></div>
                </div>
            </div>

            <script type="text/javascript">
            {literal}

            $(function(){
                var $defaultColor = $('#default_color');

                $defaultColor.ColorPicker({
                    color: '{/literal}#{if $sPost.color}{$sPost.color}{else}{$default_color}{/if}{literal}',
                    onChange: function (hsb, hex, rgb) {
                        $defaultColor.find('> *').css('backgroundColor', '#' + hex);
                        $('input[name=color]').val(hex);
                    }
                });
            });

            {/literal}
            </script>
        </td>
    </tr>
    {/if}
    </table>

    <div class="individual_add_listing_page">
        <table class="form">
        <tr>
            <td class="name">{$lang.individual_add_listing_page}</td>
            <td class="field">
                {assign var='checkbox_field' value='add_page'}

                {if $sPost.$checkbox_field == '1'}
                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                {elseif $sPost.$checkbox_field == '0'}
                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                {else}
                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                {/if}

                <table>
                <tr>
                    <td>
                        <input {$add_page_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                        <input {$add_page_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                    </td>
                    {if $smarty.get.action == 'edit' && $sPost.$checkbox_field}
                        {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=pages&amp;action=edit&amp;page=al_'|cat:$smarty.get.key|cat:'">$1</a>'}
                        <td><span class="field_description">{$lang.add_listing_page_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                    {/if}
                </tr>
                </table>
            </td>
        </tr>
        </table>
    </div>

    <table class="form">
    <tr>
        <td class="name">{$lang.apply_pictures}</td>
        <td class="field">
            {assign var='checkbox_field' value='photo'}

            {if $sPost.$checkbox_field == '1'}
                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
            {elseif $sPost.$checkbox_field == '0'}
                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
            {else}
                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
            {/if}

            <input {$photo_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
            <input {$photo_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>

            <span>
                <label><input type="checkbox" name="photo_required" value="1" {if $sPost.photo_required}checked="checked"{/if}/> {$lang.required_field}</label>
            </span>

            <script>
            {literal}

            $(document).ready(function(){
                $('input[name="photo"]').change(function(){
                    photoRequiredHandler();
                });

                photoRequiredHandler();
            });

            var photoRequiredHandler = function(){
                if ($('input[name="photo"]:checked').val() == "1") {
                    $('input[name="photo_required"]').closest('span').fadeIn();
                } else {
                    $('input[name="photo_required"]').closest('span').fadeOut('fast');
                }
            }

            {/literal}
            </script>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.apply_video}</td>
        <td class="field">
            {assign var='checkbox_field' value='video'}

            {if $sPost.$checkbox_field == '1'}
                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
            {elseif $sPost.$checkbox_field == '0'}
                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
            {else}
                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
            {/if}

            <input {$video_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
            <input {$video_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
        </td>
    </tr>

    <tr class="admin_only_row">
        <td class="name">{$lang.admin_only}</td>
        <td class="field">
            {assign var='checkbox_field' value='admin'}

            {if $sPost.$checkbox_field == '1'}
                {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
            {elseif $sPost.$checkbox_field == '0'}
                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
            {else}
                {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
            {/if}

            <table>
            <tr>
                <td>
                    <input {$admin_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$admin_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
                {if $smarty.get.action == 'edit' && !$sPost.admin}
                    {if $config.one_my_listings_page}
                        {assign var='myPageKey' value='my_all_ads'}
                        {assign var='myPageName' value=$lang.listings}
                    {else}
                        {assign var='myPageKey' value='my_'|cat:$smarty.get.key}
                        {assign var='myPageName' value=$sPost.name[$smarty.const.RL_LANG_CODE]}
                    {/if}

                    {assign var='replace_ind' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=pages&action=edit&page='|cat:$myPageKey|cat:'">$1</a>'}
                    {assign var='replace_type' value=`$smarty.ldelim`type`$smarty.rdelim`}
                    <td>
                        <span class="field_description">
                            <span class="my-ads-common">
                                {$lang.my_listings_page_hint|regex_replace:'/\[(.*)\]/':$replace_ind|replace:$replace_type:$myPageName}
                            </span>
                        </span>
                    </td>

                    <script>{literal}
                    $(function() {
                        $('input[name="admin"]').change(function(){
                            adminOnlyHandler();
                        });

                        adminOnlyHandler();
                    });

                    var adminOnlyHandler = function() {
                        if ($('input[name="admin"]:checked').val() === '1') {
                            $('.individual_add_listing_page').slideUp();
                            $('.admin_only_row .field_description').hide();
                        } else {
                            $('.individual_add_listing_page').slideDown();
                            $('.admin_only_row .field_description').show();
                        }
                    }
                    {/literal}</script>
                {/if}
            </tr>
            </table>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.show_cents}</td>
        <td class="field">
            {assign var='radio_field' value='show_cents'}

            {if $sPost.$radio_field == '1'}
                {assign var=$radio_field|cat:'_yes' value='checked="checked"'}
            {elseif $sPost.$radio_field == '0'}
                {assign var=$radio_field|cat:'_no' value='checked="checked"'}
            {else}
                {assign var=$radio_field|cat:'_yes' value='checked="checked"'}
            {/if}

            <input {$show_cents_yes} type="radio" id="{$radio_field}_yes" name="{$radio_field}" value="1" />
            <label for="{$radio_field}_yes">{$lang.yes}</label>
            <input {$show_cents_no} type="radio" id="{$radio_field}_no" name="{$radio_field}" value="0" />
            <label for="{$radio_field}_no">{$lang.no}</label>
        </td>
    </tr>

    <tr>
        <td class="name">{$lang.show_in_stat_block}</td>
        <td class="field">
            {assign var='radio_field' value='statistics'}

            {if $sPost.$radio_field == '1'}
                {assign var=$radio_field|cat:'_yes' value='checked="checked"'}
            {elseif $sPost.$radio_field == '0'}
                {assign var=$radio_field|cat:'_no' value='checked="checked"'}
            {else}
                {assign var=$radio_field|cat:'_yes' value='checked="checked"'}
            {/if}

            <input {$statistics_yes} type="radio" id="{$radio_field}_yes" name="{$radio_field}" value="1" />
            <label for="{$radio_field}_yes">{$lang.yes}</label>
            <input {$statistics_no} type="radio" id="{$radio_field}_no" name="{$radio_field}" value="0" />
            <label for="{$radio_field}_no">{$lang.no}</label>
        </td>
    </tr>

    <tr>
        <td class="name"><span class="red">*</span>{$lang.links_type}</td>
        <td class="field">
            <table>
            <tr>
                <td>
                    <select name="links_type">
                        <option value="full" {if $sPost.links_type == 'full' || !$sPost.links_type}selected="selected"{/if}>{$lang.lt_links_full}</option>
                        <option value="short" {if $sPost.links_type == 'short'}selected="selected"{/if}>{$lang.lt_links_short}</option>
                        <option value="subdomain" {if $sPost.links_type == 'subdomain'}selected="selected"{/if}>{$lang.lt_links_subdomain}</option>
                    </select>
                </td>
                <td>
                    <span class="field_description">{$lang.lt_links_subdomain_hint}</span>
                </td>
            </tr>
            </table>

            <script>{literal}
            $(document).ready(function(){
                $('select[name="links_type"]').change(function(){
                    linksTypeHandler();
                });

                linksTypeHandler();
            });

            var linksTypeHandler = function(){
                if ($('select[name="links_type"] option:selected').val() == 'subdomain') {
                    $('select[name="links_type"]').closest('tr').find('span.field_description').fadeIn();
                } else {
                    $('select[name="links_type"]').closest('tr').find('span.field_description').fadeOut('fast');
                }
            }
            {/literal}</script>
        </td>
    </tr>

    <tr>
        <td class="name"><span class="red">*</span>{$lang.status}</td>
        <td class="field">
            <select name="status">
                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>

    {rlHook name='apTplListingTypesForm'}

    </table>

    <div class="individual_page_item">
        <div id="cat_settings">
            <table class="form" style="margin-top: 5px;">
            <tr>
                <td class="divider" colspan="3"><div class="inner">{$lang.category_settings}</div></td>
            </tr>
            <tr>
                <td class="name">{$lang.general_category}</td>
                <td class="field">
                    <select name="general_cat" {if $smarty.get.action == 'add'}disabled="disabled" class="disabled"{/if}>
                        <option {if $sPost.general_cat}value="{$sPost.general_cat}" selected="selected"{else}value=""{/if}>{if $smarty.get.action == 'add'}{$lang.no_categories_available}{else}{$lang.select_category}{/if}</option>
                    </select>
                    <span class="field_description" id="build_general_cat_hint">
                        {if $smarty.get.action == 'add'}
                            {$lang.general_category_hint}
                        {else}
                            {assign var='replace' value='<a target="_blank" class="static" href="javascript:void(0)">$1</a>'}
                            {$lang.general_category_manage_hint|regex_replace:'/\[(.*)\]/':$replace}
                        {/if}
                    </span>

                    {if $smarty.get.action == 'edit'}
                        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>
                        <script>
                        var category_selected = {if $sPost.general_cat}{$sPost.general_cat}{else}null{/if};
                        var general_cat_href = '{$rlBase}index.php?controller=categories&action=build&form=submit_form&id=[id]';

                        {literal}

                        $(document).ready(function(){
                            $('select[name=general_cat]').categoryDropdown({
                                listingTypeKey: '{/literal}{$sPost.key}{literal}',
                                default_selection: category_selected,
                                onChange: generalCatHandler,
                                phrases: { {/literal}
                                    no_categories_available: "{$lang.no_categories_available}",
                                    select: "{$lang.select}",
                                    select_category: "{$lang.select_category}"
                                {literal} }
                            });
                            generalCatHandler();
                        });

                        function generalCatHandler(general_cat_id, parent_id) {
                            general_cat_id = general_cat_id ? general_cat_id : parent_id;

                            if (general_cat_id) {
                                $('#build_general_cat_hint a').attr('href', general_cat_href.replace('[id]', general_cat_id));
                                $('#build_general_cat_hint').fadeIn();
                            } else {
                                $('#build_general_cat_hint').fadeOut('fast');
                            }
                        }
                        {/literal}
                        </script>
                    {/if}
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.category_box_type_page_position}</td>
                <td class="field">
                    <select name="cat_position">
                        {foreach from=$cat_positions item='block_side' key='sKey'}
                        <option value="{$sKey}" {if $sKey == $sPost.cat_position || (!$sPost.cat_position && $sKey == 'hide')}selected="selected"{/if}>{$block_side}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.display_counter}</td>
                <td class="field">
                    {assign var='checkbox_field' value='display_counter'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <input {$display_counter_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$display_counter_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.hide_empty_cats}</td>
                <td class="field">
                    {assign var='checkbox_field' value='cat_hide_empty'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}

                    <input {$cat_hide_empty_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$cat_hide_empty_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>


            <tr>
                <td class="name">{$lang.display_postfix}</td>
                <td class="field">
                    {assign var='checkbox_field' value='html_postfix'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <input {$html_postfix_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$html_postfix_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.category_order}</td>
                <td class="field">
                    <select name="category_order">
                        {foreach from=$category_order_types item='cat_order_type'}
                        <option value="{$cat_order_type.key}" {if $cat_order_type.key == $sPost.category_order}selected="selected"{/if}>{$cat_order_type.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.allow_subcategories}</td>
                <td class="field">
                    {assign var='checkbox_field' value='allow_subcategories'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}

                    <input {$allow_subcategories_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$allow_subcategories_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.display_subcategories}</td>
                <td class="field">
                    {assign var='checkbox_field' value='display_subcategories'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <input {$display_subcategories_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$display_subcategories_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            {rlHook name='apTplListingTypesFormCategory'}
            </table>

            <table class="form" style="margin-top: 5px;">
            <tr>
                <td class="divider" colspan="3"><div class="inner">{$lang.additional_cat_block}</div></td>
            </tr>
            <tr>
                <td class="name">{$lang.show_on_pages}</td>
                <td class="field">
                    {assign var='bPages' value=$sPost.ablock_pages}
                    <table class="sTable" id="pagas_checkboxes">
                    <tr>
                        <td valign="top">
                        {foreach from=$pages item='page' name='pagesF'}
                            {assign var='pId' value=$page.ID}
                            <div style="padding: 2px 8px 2px 0;">
                                <input class="checkbox"
                                       {if $bPages && $pId|in_array:$bPages}checked="checked"{/if}
                                       id="page_{$page.ID}"
                                       type="checkbox"
                                       name="ablock_pages[{$page.ID}]"
                                       value="{$page.ID}"
                                /> <label class="cLabel" for="page_{$page.ID}">{$page.name}</label>
                        </div>
                        {assign var='perCol' value=$smarty.foreach.pagesF.total/3|ceil}

                        {if $smarty.foreach.pagesF.iteration % $perCol == 0}
                            </td>
                            <td valign="top">
                        {/if}
                        {/foreach}
                        </td>
                    </tr>
                    </table>

                    <script type="text/javascript">
                    {literal}

                    $(document).ready(function(){
                        $('table#pagas_checkboxes input').click(function(){
                            pagesTracker();
                        });

                        pagesTracker();
                    });

                    var pagesTracker = function(){
                        $('.position_star').hide();

                        $('table#pagas_checkboxes input').each(function(){
                            if ( $(this).attr('checked') )
                            {
                                $('.position_star').show();
                                return;
                            }
                        });
                    }

                    {/literal}
                    </script>
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red hide position_star">*</span>{$lang.position}</td>
                <td class="field">
                    <select name="ablock_position">
                        <option value="">{$lang.select}</option>
                        {foreach from=$cat_positions item='block_side' key='sKey'}
                        {if $sKey == 'hide'}{continue}{/if}
                        <option value="{$sKey}" {if $sKey == $sPost.ablock_position}selected="selected"{/if}>{$block_side}</option>
                        {/foreach}
                    </select>

                    {if $smarty.get.action == 'edit' && !empty($sPost.ablock_pages.0)}
                        {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=blocks&amp;action=edit&amp;block=ltcb_'|cat:$smarty.get.key|cat:'">$1</a>'}
                        <span class="field_description">{$lang.show_on_pages_hint|regex_replace:'/\[(.*)\]/':$replace}</span>
                    {/if}
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.display_first}</td>
                <td class="field">
                    <input style="width: 30px;" type="text" class="numeric" name="ablock_visible_number" value="{$sPost.ablock_visible_number}" />
                    <span class="field_description">{$lang.display_first_hint}</span>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.category_scrolling_in_box}</td>
                <td class="field">
                    {assign var='checkbox_field' value='ablock_scrolling_in_box'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <input {$ablock_scrolling_in_box_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$ablock_scrolling_in_box_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.display_subcategories}</td>
                <td class="field">
                    {assign var='checkbox_field' value='ablock_display_subcategories'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <input {$ablock_display_subcategories_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                    <input {$ablock_display_subcategories_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.ablock_subcats_number}</td>
                <td class="field">
                    <input style="width: 30px;" type="text" class="numeric" name="ablock_subcategories_number" value="{$sPost.ablock_subcategories_number}" />
                    <span class="field_description">{$lang.display_first_subcats_hint}</span>
                </td>
            </tr>

            {rlHook name='apTplListingTypesFormCategoryAddBlock'}
            </table>
        </div>

        <div id="search_settings">
            <table class="form" style="margin-top: 5px;">
            <tr>
                <td class="divider" colspan="3"><div class="inner">{$lang.search_settings}</div></td>
            </tr>
            <tr>
                <td class="name">{$lang.search_form}</td>
                <td valign="top" class="field">
                    {assign var='checkbox_field' value='search_form'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <table>
                    <tr>
                        <td>
                            <input {$search_form_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                            <input {$search_form_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                        </td>
                        {if $smarty.get.action == 'edit' && $sPost.search_form}
                            {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=search_forms&action=build&form='|cat:$smarty.get.key|cat:'_quick">$1</a>'}
                            <td><span class="field_description">{$lang.search_form_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                        {/if}
                    </tr>
                    </table>

                    <div style="padding: 10px 0 4px 1px;"><label><input type="checkbox" {if $sPost.search_home}checked="checked"{/if} name="search_home" value="1" /> {$lang.search_form_on_home}</label></div>
                    <div style="padding: 6px 0 4px 1px;"><label><input type="checkbox" {if $sPost.search_page}checked="checked"{/if} name="search_page" value="1" /> {$lang.search_form_on_search}</label></div>

                    <div style="padding: 6px 0 4px 1px;">
                        {assign var='type_page_key' value='pages+name+lt_'|cat:$sPost.key}
                        {assign var='type_replace' value=`$smarty.ldelim`listing_type`$smarty.rdelim`}

                        {if $smarty.get.action == 'edit' && $sPost.key && $lang.$type_page_key}
                            {assign var='type_page_key' value=$lang.$type_page_key}
                            {assign var='type_page_key' value=$lang.search_form_on_type_page|replace:$type_replace:$type_page_key}
                        {else}
                            {assign var='type_page_key' value=$lang.search_form_on_type_page|replace:$type_replace:$lang.listing_type}
                        {/if}

                        <label><input type="checkbox" {if $sPost.search_type}checked="checked"{/if} name="search_type" value="1" /> {$type_page_key}</label>
                    </div>

                    <div style="padding: 6px 0 4px 1px;"><label><input type="checkbox" {if $sPost.search_account}checked="checked"{/if} name="search_account" value="1" /> {$lang.search_form_on_account}</label></div>

                    <div style="padding: 4px 0 4px 1px;"><label><input type="checkbox" {if $sPost.search_multi_categories}checked="checked"{/if} name="search_multi_categories" value="1" /> {$lang.search_multi_categories}</label></div>
                </td>
            </tr>
            </table>

            <div id="multi_categories_levels">
                <table class="form" style="margin-top: 5px;">
                <tr>
                    <td class="name">{$lang.number_of_levels}</td>
                    <td class="field">
                        <select name="search_multicat_levels" style="width:40px">
                            {section name="multicats_number" start=2 loop=5 step=1}
                                {assign var="cnumber" value=$smarty.section.multicats_number.index}
                                <option value="{$cnumber}" {if $sPost.search_multicat_levels == $cnumber}selected="selected"{/if}>{$cnumber}</option>
                            {/section}
                        </select>
                        <span class="field_description">{$lang.search_multicat_levels_hint}</span>

                        <div style="padding: 10px 0 4px 1px;"><label><input type="checkbox" {if $sPost.search_multicat_phrases}checked="checked"{/if} name="search_multicat_phrases" value="1" /> {$lang.use_custom_phrases}</label></div>
                    </td>
                </tr>
                </table>
            </div>

            <div id="multi_categories_phrases">
                <table class="form">
                {assign var='replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
                {section name='multicats_number' start=1 loop=5 step=1}
                <tr>
                    <td class="name">{$lang.level_number|replace:$replace:$smarty.section.multicats_number.iteration}</td>
                    <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                            {/foreach}
                        </ul>
                    {/if}
                    {foreach from=$allLangs item='language' name='langF'}
                        {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                            <input type="text" name="multicat_phrases[{$smarty.section.multicats_number.iteration}][{$language.Code}]" value="{$sPost.multicat_phrases[$smarty.section.multicats_number.iteration][$language.Code]}" class="w250" maxlength="50" /> <span class="field_description_noicon">
                        {if $allLangs|@count > 1}{$lang.name} (<b>{$language.name}</b>)</span>
                            </div>
                        {/if}
                    {/foreach}
                    </td>
                </tr>
                {/section}
                </table>
            </div>

            <table class="form" style="margin-top: 5px;">
            <tr>
                <td class="name">{$lang.advanced_search}</td>
                <td class="field">
                    {assign var='checkbox_field' value='advanced_search'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <table>
                    <tr>
                        <td>
                            <input {$advanced_search_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                            <input {$advanced_search_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                        </td>
                        {if $smarty.get.action == 'edit' && $sPost.advanced_search}
                            {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=search_forms&action=build&form='|cat:$smarty.get.key|cat:'_advanced">$1</a>'}
                            <td><span class="field_description">{$lang.search_form_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                        {/if}
                    </tr>
                    </table>
                </td>
            </tr>

            {if $tpl_settings.search_on_map_page}
            <tr>
                <td class="name">{$lang.on_map_search}</td>
                <td class="field">
                    {assign var='checkbox_field' value='on_map_search'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {/if}

                    <table>
                    <tr>
                        <td>
                            <input {$on_map_search_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                            <input {$on_map_search_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                        </td>
                        {if $smarty.get.action == 'edit' && $sPost.on_map_search}
                            {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=search_forms&action=build&form='|cat:$smarty.get.key|cat:'_on_map">$1</a>'}
                            <td><span class="field_description">{$lang.search_form_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                        {/if}
                    </tr>
                    </table>
                </td>
            </tr>
            {/if}

            <tr>
                <td class="name">{$lang.myads_search}</td>
                <td class="field">
                    {assign var='checkbox_field' value='myads_search'}

                    {if $sPost.$checkbox_field == '1'}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {elseif $sPost.$checkbox_field == '0'}
                        {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                    {else}
                        {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                    {/if}

                    <table>
                    <tr>
                        <td>
                            <input {$myads_search_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                            <input {$myads_search_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                        </td>
                        {if $smarty.get.action == 'edit' && $sPost.myads_search}
                            {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=search_forms&action=build&form='|cat:$smarty.get.key|cat:'_myads">$1</a>'}
                            <td><span class="field_description">{$lang.search_form_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                        {/if}
                    </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.submit_method}</td>
                <td class="field">
                    <select name="refine_search_type">
                        {foreach from=$refine_search_types item='refine_search_type'}
                        <option value="{$refine_search_type.key}" {if $refine_search_type.key == $sPost.refine_search_type}selected="selected"{/if}>{$refine_search_type.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>

            {rlHook name='apTplListingTypesFormSearch'}
            </table>

            <script type="text/javascript">
            {literal}

            $(document).ready(function(){
                $('input[name=search_form]').click(function(){
                    searchFormTracker();
                });

                searchFormTracker();
            });

            var searchFormTracker = function() {
                var disabled = $('input[name=search_form]:checked').val() == '1' ? false : true;
                var class_name = $('input[name=search_form]:checked').val() == '1' ? 'selector' : 'selector_disabled';

                $('input[name=advanced_search]').attr('disabled', disabled);
                $('select[name=refine_search_type]').attr('disabled', disabled);
                $('select[name=refine_search_type]').parent().attr('class', class_name);
                $('input[name=search_home]').attr('disabled', disabled);
                $('input[name=search_page]').attr('disabled', disabled);
            }

            {/literal}
            </script>
        </div>
    </div>

    <div id="featured_settings">
        <table class="form" style="margin-top: 5px;">
        <tr>
            <td class="divider" colspan="3"><div class="inner">{$lang.featured_settings}</div></td>
        </tr>
        <tr>
            <td class="name">{$lang.featured_blocks}</td>
            <td class="field">
                {assign var='checkbox_field' value='featured_blocks'}

                {if $sPost.$checkbox_field == '1'}
                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                {elseif $sPost.$checkbox_field == '0'}
                    {assign var=$checkbox_field|cat:'_no' value='checked="checked"'}
                {else}
                    {assign var=$checkbox_field|cat:'_yes' value='checked="checked"'}
                {/if}

                <table>
                <tr>
                    <td>
                        <input {$featured_blocks_yes} type="radio" id="{$checkbox_field}_yes" name="{$checkbox_field}" value="1" /> <label for="{$checkbox_field}_yes">{$lang.yes}</label>
                        <input {$featured_blocks_no} type="radio" id="{$checkbox_field}_no" name="{$checkbox_field}" value="0" /> <label for="{$checkbox_field}_no">{$lang.no}</label>
                    </td>
                    {if $smarty.get.action == 'edit' && $sPost.$checkbox_field}
                        {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=blocks&amp;action=edit&amp;block=ltfb_'|cat:$smarty.get.key|cat:'">$1</a>'}
                        <td><span class="field_description">{$lang.featured_blocks_hint|regex_replace:'/\[(.*)\]/':$replace}</span></td>
                    {/if}
                </tr>
                </table>
            </td>
        </tr>
        </table>

        {rlHook name='apTplListingTypesFormFeatured'}
    </div>

   <div{* id="arrange_settings"*}>
        <table class="form" style="margin-top: 5px;">
        <tr>
            <td class="divider" colspan="3"><div class="inner">{$lang.arrange_settings}</div></td>
        </tr>
        <tr>
            <td class="name">{$lang.arrange_by_field}</td>
            <td class="field">
                {if $smarty.get.action == 'add'}
                    {$lang.not_available} <span class="field_description">{$lang.general_category_hint}</span>
                {else}
                    <select name="arrange_field">
                        <option value="0">- {$lang.disabled} -</option>
                        {foreach from=$fields item='field'}
                        {assign var='type_phrase' value='type_'|cat:$field.Type}
                        <option value="{$field.Key}" {if $sPost.arrange_field == $field.Key}selected="selected"{/if}>{$field.name} ({$lang.$type_phrase})</option>
                        {foreachelse}
                        <option value="0">{$lang.no_fields_available}</option>
                        {/foreach}
                    </select>
                    <span class="field_description">{$lang.arrange_by_field_hint}</span>
                {/if}
            </td>
        </tr>
        </table>

        {if $smarty.get.action == 'edit'}
            <div id="arrange_area" class="hide">
                <table class="form" style="margin-top: 5px;">
                <tr>
                    <td class="name">{$lang.apply_to}</td>
                    <td class="field">
                        <div class="individual_page_item">
                            <div style="padding: 6px 0 4px;" id="arrange_search">
                                <label><input class="modules" type="checkbox" {if $sPost.is_arrange_search}checked="checked"{/if} name="is_arrange_search" value="1" /> {$lang.arrange_search_form}</label>
                                <div class="area hide">
                                    <div style="padding: 5px 0;">

                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="padding: 4px 0;" id="arrange_featured">
                            <label><input class="modules" type="checkbox" {if $sPost.is_arrange_featured}checked="checked"{/if} name="is_arrange_featured" value="1" /> {$lang.arrange_featured_block}</label>
                            <div class="area hide">
                                <div style="padding: 5px 0;">

                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                </table>
            </div>
        {/if}

        {rlHook name='apTplListingTypesFormArrange'}
    </div>

    {if $smarty.get.action == 'edit'}
        <script type="text/javascript">//<![CDATA[
        var arrange_langs = new Array();
        var langs_list = new Array();
        {assign var='exp_values' value=','|explode:$fields[$sPost.arrange_field].Values}
        {assign var='arrange_key' value=$fields[$sPost.arrange_field].Key}
        {foreach from=$allLangs item='languages' name='lF'}
        langs_list['{$languages.Code}'] = '{$languages.name}';

        arrange_langs['{$arrange_key}_{$languages.Code}'] = [
            [{foreach from=$exp_values item='value' name='valueF'}{if $sPost.arrange_search[$arrange_key].$value[$languages.Code]}'{$sPost.arrange_search[$arrange_key].$value[$languages.Code]}'{else}false{/if}{if !$smarty.foreach.valueF.last},{/if}{/foreach}],
            [{foreach from=$exp_values item='value' name='valueF'}{if $sPost.arrange_featured[$arrange_key].$value[$languages.Code]}'{$sPost.arrange_featured[$arrange_key].$value[$languages.Code]}'{else}false{/if}{if !$smarty.foreach.valueF.last},{/if}{/foreach}]
        ];
        {/foreach}
        var arrange_modules = ['arrange_search', 'arrange_featured'];
        var arrange_names = ['{$lang.arrange_tab_name}', '{$lang.arrange_box_name}', '{$lang.arrange_col_name}'];


        var fields = new Array();
        {foreach from=$fields item='field'}
            {assign var='exp_values' value=','|explode:$field.Values}
            fields['{$field.Key}'] = [
                '{$field.Type}',
                '{$field.Values}',
                [{foreach from=$exp_values item='value' name='valueF'}{assign var='val_phrase' value='listing_fields+name+'|cat:$field.Key|cat:'_'|cat:$value}{if $field.Type == 'bool'}'{if $value}{$lang.yes}{else}{$lang.no}{/if}'{else}'{$lang.$val_phrase}'{/if}{if !$smarty.foreach.valueF.last},{/if}{/foreach}]
            ];
        {/foreach}

        {literal}

        $(document).ready(function(){
            $('select[name=arrange_field]').change(function(){
                arrangeField();
            });

            arrangeField();

            $('#arrange_area input.modules').click(function(){
                arrangeOpen(this);
            })
            $('#arrange_area input.modules:checked').each(function(){
                arrangeOpen(this);
            });
        });

        var arrangeOpen = function(obj){
            if ($(obj).is(':checked')) {
                $(obj).parent().next().slideDown();
            } else {
                $(obj).parent().next().slideUp();
            }
        };

        var arrangeField = function(){
            var key = $('select[name=arrange_field]').val();

            if (key != '0') {
                $('#arrange_area').slideDown();
                $('#arrange_area input.tmp').attr('checked', true).removeClass('tmp').parent().next().slideDown();
            } else {
                $('#arrange_area').slideUp();
                $('#arrange_area input.modules:checked').attr('checked', false).addClass('tmp').parent().next().slideUp();
                return;
            }

            arrangeBuild(key);
        };

        var arrangeBuild = function(key){
            var tabs = '<ul class="tabs">';
            var first_tab = true;
            for (var lng in langs_list) {
                if (typeof(langs_list[lng]) != 'function') {
                    var active = first_tab ? ' class="active"' : '';
                    tabs+= '<li'+active+' lang="'+lng+'">'+langs_list[lng]+'</li> ';
                    first_tab = false;
                }
            }
            tabs += '</ul>';

            for (var j=0; j<arrange_modules.length;j++) {
                var module = arrange_modules[j];
                var values = fields[key][1].split(',');
                var html = tabs;

                first_tab = true;
                for (var lng in langs_list) {
                    if (typeof(langs_list[lng]) != 'function') {
                        var hide = first_tab ? '' : ' hide';
                        html += '<div class="tab_area '+lng+' '+hide+'">';
                        html += '<table style="margin-left: 20px;" class="frame"><tr>';
                        for (var i=0; i<values.length; i++) {
                            if (!arrange_langs[key+'_'+lng]) {
                                var set = fields[key][2][i];
                            } else {
                                var set = arrange_langs[key+'_'+lng][j][i] ? arrange_langs[key+'_'+lng][j][i] : fields[key][2][i];
                            }
                            html += '<td class="name">'+arrange_names[j].replace('{name}', fields[key][2][i])+'</td><td class="field ckeditor"><input type="text" name="'+module+'['+key+']['+values[i]+']['+lng+']" value="'+set+'" /> <span class="field_description_noicon">('+langs_list[lng]+')</span></td></tr><tr>';
                        }
                        html += '</tr></table></div>';
                        first_tab = false;
                    }
                }

                /* append search tabs fieds */
                $('#'+module+' div.area div').html(html);
                flynax.tabs();
            }
        };
        {/literal}
        //]]>
        </script>
    {/if}

    <script type="text/javascript">{literal}
    $(document).ready(function(){
        $('input[name=photo]').change(function(){
            photoOpt();
        });

        photoOpt();

        // multi category option handler
        $('input[name=search_multi_categories]').change(function(){
            searchMultiCat();
        });
        searchMultiCat();

        // multi category phrases handler
        $('input[name=search_multicat_phrases]').change(function(){
            phrasesMultiCat();
        });
        phrasesMultiCat();

        $('select[name=search_multicat_levels]').change(function(){
            phrasesLevels();
        });
        phrasesLevels();
    });

    var photoOpt = function(){
        var value = parseInt($('input[name=photo]:checked').val());
    };

    var phrasesMultiCat = function(){
        if ($('input[name=search_multicat_phrases]').is(':checked') && $('input[name=search_multi_categories]').is(':checked')) {
            $('#multi_categories_phrases').slideDown();
        } else {
            $('#multi_categories_phrases').slideUp();
        }
    }
    var searchMultiCat = function(){
        if ($('input[name=search_multi_categories]:checked').val()) {
            $('#multi_categories_levels').slideDown();
        } else {
            $('#multi_categories_levels').slideUp();
        }

        phrasesMultiCat();
    };

    var phrasesLevels = function(){
        var levels = parseInt($('select[name=search_multicat_levels]').val()) - 1;
        var table = $('#multi_categories_phrases > table.form > tbody');

        table.find('> tr').show();
        table.find('> tr input[name^=multicat_phrases]').attr('disabled', false);

        table.find('> tr:gt(' + levels + ')').hide();
        table.find('> tr:gt(' + levels + ') input[name^=multicat_phrases]').attr('disabled', true);
    }
    {/literal}</script>

    <table class="form">
    <tr>
        <td style="width: 185px;"></td>
        <td class="field">
            <input class="button" type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
        </td>
    </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add/edit type end -->

    {rlHook name='apTplListingTypesAction'}

{else}

    <!-- delete listing type block -->
    <div id="delete_block" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.remove_listing_type}
            <div id="delete_container">
                {$lang.detecting}
            </div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <script type="text/javascript">//<![CDATA[
        {if $config.trash}
            var delete_conform_phrase = "{$lang.notice_drop_empty_listing_type}";
        {else}
            var delete_conform_phrase = "{$lang.notice_delete_empty_listing_type}";
        {/if}

        {literal}

        function delete_chooser(method, key, name)
        {
            if (method == 'delete')
            {
                rlPrompt(delete_conform_phrase.replace('{type}', name), 'xajax_deleteListingType', key);
            }
            else if (method == 'replace')
            {
                $('#top_buttons').slideUp('slow');
                $('#bottom_buttons').slideDown('slow');
                $('#replace_content').slideDown('slow');
            }
        }

        {/literal}
        //]]>
        </script>
    </div>
    <!-- delete listing type block end -->

    <!-- listing types grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var listingTypesGrid;

    {literal}
    $(document).ready(function(){

        listingTypesGrid = new gridObj({
            key: 'listingTypes',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/listing_types.inc.php?q=ext',
            defaultSortField: 'name',
            title: lang['ext_listing_types_manager'],
            fields: [
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Admin_only', mapping: 'Admin_only'},
                {name: 'Order', mapping: 'Order', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 50,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Order',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_admin_only'],
                    dataIndex: 'Admin_only',
                    width: 10,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['1', lang['ext_yes']],
                            ['0', lang['ext_no']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        emptyText: lang['ext_not_available'],
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    fixed: true,
                    width: 100,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang.active],
                            ['approval', lang.approval]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data, ext, row) {
                        var out = "<center>";

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&key="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }

                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='xajax_prepareDeleting(\""+row.data.Key+"\")' />";
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplListingTypesGrid'}{literal}

        listingTypesGrid.init();
        grid.push(listingTypesGrid.grid);

    });
    {/literal}
    //]]>
    </script>
    <!-- listing types grid end -->

    {rlHook name='apTplListingTypesBottom'}

{/if}

<!-- listing types tpl end -->
