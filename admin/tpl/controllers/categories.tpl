<!-- listing categories tpl -->
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.caret.js"></script>
<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplCategoriesNavBar'}

    {if !isset($smarty.get.action)}
        <a onclick="show('search', '#action_blocks div');" href="javascript:void(0)" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
    {/if}

    {if $aRights.$cKey.add && !$smarty.get.action}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_category}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.action == 'build'}
        {assign var="action_url" value=$rlBase|cat:'index.php?controller=categories&action=build&key='|cat:$category_info.Key|cat:'&form='}
        {assign var='replace' value=`$smarty.ldelim`category`$smarty.rdelim`}

        {if $smarty.get.form != 'submit_form'}
            <a title="{$lang.build_submit_form|replace:$replace:$category_info.name}" href="{$action_url}submit_form" class="button_bar"><span class="left"></span><span class="center_build">{$lang.submit_form}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.form != 'short_form'}
            <a title="{$lang.build_short_form|replace:$replace:$category_info.name}" href="{$action_url}short_form" class="button_bar"><span class="left"></span><span class="center_build">{$lang.short_form}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.form != 'listing_title'}
            <a title="{$lang.build_listing_title_form|replace:$replace:$category_info.name}" href="{$action_url}listing_title" class="button_bar"><span class="left"></span><span class="center_build">{$lang.listing_title_form}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.form != 'featured_form'}
            <a title="{$lang.build_featured_form|replace:$replace:$category_info.name}" href="{$action_url}featured_form" class="button_bar"><span class="left"></span><span class="center_build">{$lang.featured_form}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.form != 'sorting_form'}
            <a title="{$lang.build_sorting_form|replace:$replace:$category_info.name}" href="{$action_url}sorting_form" class="button_bar"><span class="left"></span><span class="center_build">{$lang.sorting_form}</span><span class="right"></span></a>
        {/if}
    {/if}

    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.categories_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<div id="action_blocks">

    {if !isset($smarty.get.action)}
        <!-- search -->
        <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}

        <form method="post" onsubmit="return false;" id="search_form" action="">
            <table class="form">
            <tr>
                <td class="name">{$lang.name}</td>
                <td class="field">
                    <input type="text" id="search_name" />
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.listing_type}</td>
                <td class="field">
                    <select id="search_type" style="width: 200px;">
                    <option value="">- {$lang.all} -</option>
                    {foreach from=$listing_types item='l_type'}
                        <option value="{$l_type.Key}">{$l_type.name}</option>
                    {/foreach}
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.parent}</td>
                <td class="field">
                    <select id="search_parent" style="width: 200px;">
                        <option value="">{$lang.choose_listing_type}</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.locked}</td>
                <td class="field" id="search_locked_td">
                    <label title="{$lang.unmark}"><input title="{$lang.unmark}" type="radio" id="locked_uncheck" value="" /> ...</label>
                    <label><input type="radio" name="search_locked" value="yes" /> {$lang.yes}</label>
                    <label><input type="radio" name="search_locked" value="no" /> {$lang.no}</label>

                    <script type="text/javascript">
                    {literal}
                    $('#locked_uncheck').click(function(){
                        $('#search_locked_td input').attr('checked', false);
                    });
                    {/literal}
                    </script>
                </td>
            </tr>

            {rlHook name='apTplCategoriesSearch'}

            <tr>
                <td class="name">{$lang.status}</td>
                <td class="field">
                    <select id="search_status" style="width: 200px;">
                        <option value="">- {$lang.all} -</option>
                        <option value="active">{$lang.active}</option>
                        <option value="approval">{$lang.approval}</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td></td>
                <td class="field">
                    <input type="submit" class="button" value="{$lang.search}" id="search_button" />
                    <input type="button" class="button" value="{$lang.reset}" id="reset_search_button" />

                    <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
                </td>
            </tr>

            </table>
        </form>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>

        <script type="text/javascript">
        var remote_filters = new Array();
        {if $smarty.get.type}
            remote_filters.push( 'action||search' );
            remote_filters.push( 'Type||{$smarty.get.type}' );
        {/if}

        {literal}

        var search = new Array();
        var cookie_filters = new Array();

        $(document).ready(function(){
            var category_selected = null;

            if ( readCookie('categories_sc') || remote_filters.length > 0 )
            {
                $('#search').show();
                cookie_filters = remote_filters.length > 0 ? remote_filters : readCookie('categories_sc').split(',');

                for (var i in cookie_filters)
                {
                    if ( typeof(cookie_filters[i]) == 'string' )
                    {
                        var item = cookie_filters[i].split('||');
                        if ( item[0] != 'undefined' && item[0] != '' )
                        {
                            if ( item[0] == 'Lock' )
                            {
                                $('#search input').each(function(){
                                    var val = item[1] == 1 ? 'yes' : 'no';
                                    if ( $(this).attr('name') == 'search_locked' && $(this).val() == val )
                                    {
                                        $(this).attr('checked', true);
                                    }
                                });
                            }
                            else
                            {
                                if ( item[0] == 'Parent_ID' ) {
                                    item[0] = 'parent';
                                    category_selected = item[1];
                                }

                                $('#search_'+item[0].toLowerCase()).val(item[1]);
                            }
                        }
                    }
                }
            }

            $('#search_form').submit(function(){

                createCookie('categories_pn', 0, 1);
                search = new Array();
                search.push( new Array('action', 'search') );
                search.push( new Array('Name', $('#search_name').val()) );
                search.push( new Array('Type', $('#search_type').val()) );
                search.push( new Array('Parent_ID', $('#search_parent').val()) );

                {/literal}{rlHook name='apTplCategoriesSearchJS'}{literal}

                if ( $('input[name=search_locked]:checked').length > 0 )
                {
                    search.push( new Array('Lock', $('input[name=search_locked]:checked').val() == 'yes'? 1 : 0) );
                }
                search.push( new Array('Status', $('#search_status').val()) );

                // save search criteria
                var save_search = new Array();
                for(var i in search)
                {
                    if ( search[i][1] != '' && typeof(search[i][1]) != 'undefined'  )
                    {
                        save_search.push(search[i][0]+'||'+search[i][1]);
                    }
                }
                createCookie('categories_sc', save_search, 1);

                categoriesGrid.filters = search;
                categoriesGrid.reload();
            });

            $('#reset_search_button').click(function(){
                eraseCookie('categories_sc');
                categoriesGrid.reset();

                $("#search select option[value='']").attr('selected', true);
                $("#search input[type=text]").val('');
                $("#search input").each(function(){
                    if ( $(this).attr('type') == 'radio' )
                    {
                        $(this).attr('checked', false);
                    }
                });

                $('#search_parent').trigger('reset');
            });

            $('#search_parent').categoryDropdown({
                listingType: '#search_type',
                default_selection: category_selected,
                phrases: { {/literal}
                    no_categories_available: "{$lang.no_categories_available}",
                    select: "{$lang.select}",
                    select_category: "{$lang.select_category}"
                {literal} }
            });
        });

        {/literal}
        </script>
        <!-- search end -->
    {/if}

</div>

{if isset($smarty.get.action)}

    {if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

        {assign var='sPost' value=$smarty.post}

        <script type="text/javascript">
        var new_option = new Array();
        {foreach from=$listing_types item='item' name='item'}
            new_option["{$item.Key}"] = new Array('{$item.Cat_custom_adding}');
        {/foreach}
        {literal}

        $(document).ready(function(){
            setAllowCat($('select#listing_type').val());

            $('select#listing_type').change(function(){
                /* clear relations */
                $('#category_id').val(0);
                $('#category_name').html($('#categories a.delete_item').text());
                $('input#parent_id').val(0);

                var type = $(this).val();
                setAllowCat(type);

                if (type != '0') {
                    $('span#listing_type_loading').fadeIn();
                    xajax_loadType(type);
                } else {
                    cat_chooser(0, $('#categories a.delete_item').text());
                    $('#categories').slideUp(function(){
                        $(this).find('#section_listings').remove();
                    });
                    $('#parent_category').slideUp();
                }
            });
            $('input.add_variable_button').click(function(){
                var variable = $(this).prev().val();
                if( variable != '0' && variable )
                {
                    var text_obj = $(this).parent().parent().find('input[type=text]:visible');

                    var text = text_obj.val();

                    var caret = text_obj.getSelection();
                    var new_text = text.substring(0, caret.start) + '{' + variable + '}' + text.substring(caret.end, text.length);

                    text_obj.val(new_text).focus();
                    text_obj.setCursorPosition(caret.start+variable.length+2);
                }
            });
        });
        function setAllowCat( key ) {
            if (new_option[key] == 1) {
                $("#allow_subcategories").removeClass('hide');
            } else {
                $("#allow_subcategories").addClass('hide');
            }
        }
        {/literal}
        </script>

        <!-- select category action -->
        <script type="text/javascript">
        var cat_mode          = '{$smarty.get.action}';
        var tree_selected     = {if $parent_id}[{$parent_id}]{else}false{/if};
        var tree_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}'{$parent_point}'{if !$smarty.foreach.parentF.last},{/if}{/foreach}]{else}false{/if};
        var defaultLanguage   = '{$config.lang}';

        {literal}
        function cat_chooser(cat_id, cat_name){
            cat_id = parseInt(cat_id);

            $('#category_id').val(cat_id);
            $('#category_name').html(cat_name);

            flynax.sendAjaxRequest('getCategoryPathsByID', {id: cat_id}, function(response) {
                if (response.paths) {
                    Object.keys(response.paths).forEach(key => {
                        var path     = response.paths[key];
                        var langCode = key.replace('Path_', '');

                        if (!path && response.paths['Path_' + defaultLanguage]) {
                            path = response.paths['Path_' + defaultLanguage];
                        }

                        path = cat_id == 0 ? '' : path + '/';
                        $('.ap.' + langCode).html(path);

                        if (cat_mode == 'edit') {
                            var $pathInput   = $('input[name="path[' + langCode + ']"]');
                            var current_path = $pathInput.val().split('/');
                            $pathInput.val(current_path[current_path.length - 1]);
                        }
                    });
                }
            });
        }

        var OnCategorySelect = function(id, name) {
            cat_chooser(id, name);
        }
        var OnButtonClick = function() {
            show('categories');
        }

        $(document).ready(function(){
            flynax.openTree(tree_selected, tree_parentPoints, 1);
        });

        {/literal}
        </script>

        <div id="categories" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

            <div>
                <a class="delete_item" href="javascript:void(0)" onclick="cat_chooser('0', $(this).text()); $('#parent_categories ul.select-category select:first').val('').trigger('change');">{$lang.no_parent}</a>
            </div>
            <div id="parent_categories">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'categories'|cat:$smarty.const.RL_DS|cat:'parent_cats_tree.tpl'}
            </div>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>

        <!-- add/edit category -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <form  onsubmit="return submitHandler()" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;key={$smarty.get.key}{/if}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="submit" value="1" />
        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}

        <div id="parent_category" {if !isset($smarty.request.parent_id) && $smarty.get.action == 'add'}class="hide"{/if}>
            <table class="form">
            <tr>
                <td class="name">{$lang.parent_category}</td>
                <td>
                    <span class="grey_middle" id="category_name">{$lang.no_parent}</span> <span class="divider">|</span> <a href="javascript:void(0)" onclick="show('categories')" class="static"><b>{$lang.edit}</b></a>
                    <input id="category_id" type="hidden" name="parent_id" value="0" />
                </td>
            </tr>
            </table>
        </div>
        <!-- select category action end -->

        <table class="form">
        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.listing_type}
            </td>
            <td class="field">
                <select name="type" id="listing_type">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$listing_types item='l_type'}
                        <option value="{$l_type.Key}" {if $sPost.type == $l_type.Key}selected="selected"{/if}>
                            {$l_type.name}
                        </option>
                    {/foreach}
                </select>
                <span id="listing_type_loading" style="margin-top: -2px;" class="loader">&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </td>
        </tr>
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
                        <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" class="w350" maxlength="50" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.h1_heading}
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
                    <input type="text" name="h1_heading[{$language.Code}]" value="{$sPost.h1_heading[$language.Code]}" maxlength="350" class="w350" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.h1_heading} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.url}</td>
            <td class="field">
                {assign var='type_page' value='lt_'|cat:$type}

                {if $allLangs|@count > 1 && $config.multilingual_paths}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1 && $config.multilingual_paths}
                        <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                    {/if}

                    <span style="padding: 0 5px 0 0;" class="field_description_noicon">{strip}
                        <span class="abase {$language.Code}">
                            {if $abase}
                                {$abase}
                                {if $language.Code !== $config.lang}{$language.Code}/{/if}
                            {else}
                                {$smarty.const.RL_URL_HOME}
                                {if $language.Code !== $config.lang}{$language.Code}/{/if}
                            {/if}
                        </span>
                        <span class="ab {$language.Code}">
                            {assign var="multilingualLTPageKey" value='Path_'|cat:$language.Code}

                            {if $listing_types[$type].Links_type == 'full'}
                                {if $config.multilingual_paths && $multilingualLTPage.$multilingualLTPageKey}
                                    {$multilingualLTPage.$multilingualLTPageKey}/
                                {elseif $pages.$type_page}
                                    {$pages.$type_page}/
                                {/if}
                            {/if}
                        </span>
                        <span class="ap {$language.Code}"></span>
                    {/strip}</span>

                    <input type="text" name="path[{$language.Code}]" value="{$sPost.path[$language.Code]}" />

                    <span class="field_description_noicon" id="cat_postfix_el">
                        {if $sPost.type}
                            {if $listing_types[$sPost.type].Cat_postfix}.html{else}/{/if}
                        {/if}
                    </span>

                    {if $smarty.get.action == 'add'}
                        <span class="field_description"> {$lang.regenerate_path_desc}</span>
                    {/if}

                    {if $allLangs|@count > 1 && $config.multilingual_paths}
                        </div>
                    {/if}

                    {if !$config.multilingual_paths}
                        {break}
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.title}</td>
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
                        <input type="text" name="title[{$language.Code}]" value="{$sPost.title[$language.Code]}" class="w350" maxlength="250" />
                    {if $allLangs|@count > 1}
                            <span class="field_description_noicon">{$lang.title} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.description}
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
                    {if $allLangs|@count > 1}<div class="ckeditor tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    {assign var='dCode' value='description_'|cat:$language.Code}
                    {fckEditor name='description_'|cat:$language.Code width='100%' height='140' value=$sPost.$dCode}
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>

        <tr>
            <td class="name">{$lang.meta_description}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {assign var='lMetaDescription' value=$sPost.meta_description}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="50" rows="2" name="meta_description[{$language.Code}]">{$lMetaDescription[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.meta_keywords}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}

                {foreach from=$allLangs item='language' name='langF'}
                    {assign var='lMetaKeywords' value=$sPost.meta_keywords}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <textarea cols="50" rows="2" name="meta_keywords[{$language.Code}]">{$lMetaKeywords[$language.Code]}</textarea>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.listing_meta_description}</td>
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
                        <input type="text" name="listing_meta_description[{$language.Code}]" value="{$sPost.listing_meta_description[$language.Code]}" class="w350" maxlength="255" />
                        <span class="field_description">{$lang.listing_meta_description_des}{if $allLangs|@count > 1} (<b>{$language.name}</b>){/if}</span>
                        <div>
                        <select>
                            <option value="0">{$lang.select}</option>
                            {foreach from=$fields item="field"}
                                <option value="{$field.Key}">{$field.name}</option>
                            {/foreach}
                        </select>
                        <input type="button" class="add_variable_button" value="{$lang.add}"/>
                        </div>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.listing_meta_keywords}</td>
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
                        <input type="text" name="listing_meta_keywords[{$language.Code}]" value="{$sPost.listing_meta_keywords[$language.Code]}" class="w350" maxlength="255" />
                        <span class="field_description">{$lang.listing_meta_keywords_des}{if $allLangs|@count > 1} (<b>{$language.name}</b>){/if}</span>
                        <div>
                        <select>
                            <option value="0">{$lang.select}</option>
                            {foreach from=$fields item="field"}
                                <option value="{$field.Key}">{$field.name}</option>
                            {/foreach}
                        </select>
                        <input type="button" class="add_variable_button" value="{$lang.add}"/>
                        </div>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.listing_meta_title}</td>
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
                        <input type="text" name="listing_meta_title[{$language.Code}]" value="{$sPost.listing_meta_title[$language.Code]}" class="w350" maxlength="255" />
                        <span class="field_description">{$lang.listing_meta_title_des}{if $allLangs|@count > 1} (<b>{$language.name}</b>){/if}</span>
                        <div>
                        <select>
                            <option value="0">{$lang.select}</option>
                            {foreach from=$fields item="field"}
                                <option value="{$field.Key}">{$field.name}</option>
                            {/foreach}
                        </select>
                        <input type="button" class="add_variable_button" value="{$lang.add}"/>
                        </div>
                    {if $allLangs|@count > 1}</div>{/if}
                {/foreach}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.lock_category}</td>
            <td class="field">
                {if $sPost.lock == '1'}
                    {assign var='locked_yes' value='checked="checked"'}
                {elseif $sPost.lock == '0'}
                    {assign var='locked_no' value='checked="checked"'}
                {else}
                    {assign var='locked_no' value='checked="checked"'}
                {/if}
                <label><input {$locked_yes} type="radio" name="lock" value="1" /> {$lang.yes}</label>
                <label><input {$locked_no} type="radio" name="lock" value="0" /> {$lang.no}</label>
            </td>
        </tr>


        <tr id="allow_subcategories">
            <td class="name">{$lang.allow_subcategories}</td>
            <td class="field" id="add_mode_td">
                {if $sPost.allow_children == '1'}
                    {assign var='allow_children_yes' value='checked="checked"'}
                {elseif $sPost.allow_children == '0'}
                    {assign var='allow_children_no' value='checked="checked"'}
                {else}
                    {assign var='allow_children_no' value='checked="checked"'}
                {/if}
                <label><input {$allow_children_yes} type="radio" name="allow_children" value="1" /> {$lang.yes}</label>
                <label><input {$allow_children_no} type="radio" name="allow_children" value="0" /> {$lang.no}</label>
                <span style="margin: 5px 10px 5px 0;">
                    <label><input {if !empty($sPost.subcategories)}checked="checked"{/if} type="checkbox" name="subcategories" value="1" /> {$lang.include_subcats}</label>
                </span>
            </td>
        </tr>

        {rlHook name='apTplCategoriesForm'}

        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status">
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                </select>
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
        </tr>
        </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        <!-- add new category end -->

    {elseif $smarty.get.action == 'build'}

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'builder.tpl' no_groups=$no_groups}

    {/if}

{else}

    <!-- build reqest -->
    {if $smarty.get.request == 'build'}
        <script type="text/javascript">
        var request_category_key = '{$smarty.get.key}';
        var request_category_notice = "{$lang.suggest_category_building}";
        {literal}

        $(document).ready(function(){
            rlConfirm(request_category_notice, 'requestRedirect');
        });

        var requestRedirect = function(){
            location.href = rlUrlHome+'index.php?controller='+controller+'&action=build&form=submit_form&key='+request_category_key;
        };

        {/literal}
        </script>
    {/if}
    <!-- build reqest end -->

    <!-- delete category block -->
    <div id="delete_block" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.delete_category}
            <div id="delete_container">
                {$lang.detecting}
            </div>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <script type="text/javascript">//<![CDATA[
        var category_key = ''; // assigns in rlCategories -> ajaxPrepareDeleting()
        var category_name = ''; // assigns in rlCategories -> ajaxPrepareDeleting()
        var replace_category_id = 0;

        var notice_phrase = "{if $config.trash}{$lang.notice_drop_empty_category}{else}{$lang.notice_delete_empty_category}{/if}";
        var delete_conform_phrase = "{if $config.trash}{$lang.notice_drop_empty_category}{else}{$lang.notice_delete_empty_category}{/if}";

        {literal}

        function OnCategorySelect(id, name) {
            replace_category_id = parseInt(id);
        }

        function OnButtonClick() {
            if (replace_category_id > 0) {
                rlConfirm(notice_phrase.replace('{category}', category_name), 'replaceCategory', category_key);
            }
        }

        function delete_chooser(method, key, name)
        {
            if (method == 'delete')
            {
                rlConfirm(delete_conform_phrase.replace('{category}', name), 'xajax_deleteCategory', key);
            }
            else if (method == 'replace')
            {
                $('#top_buttons').slideUp('slow');
                $('#bottom_buttons').slideDown('slow');
                $('#replace_content').slideDown('slow');
            }
        }

        function cat_chooser(id)
        {
            $('#replace_category').val(id);
        }

        function replaceCategory(key)
        {
            xajax_deleteCategory(key, replace_category_id);
        }

        {/literal}

        {if $smarty.get.listing_type}
            cookie_filters = new Array();
            cookie_filters.push(new Array('Type', '{$smarty.get.listing_type}'));
            cookie_filters.push(new Array('action', 'search'));
        {/if}

        //]]>
        </script>
    </div>
    <!-- delete category block end -->

    <!-- categories grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[

    var mass_actions = [
        {if 'delete'|in_array:$aRights.categories}[lang['ext_delete'], 'delete'],{/if}
        [lang['ext_activate'], 'activate'],
        [lang['ext_suspend'], 'approve']
    ];

    var general_cats = new Array();
    var i =0;
    {foreach from=$listing_types item='item' name='item'}
        general_cats[i] = parseInt('{$item.Cat_general_cat}');
        i++;
    {/foreach}

    var categoriesGrid;
    {literal}

    var action_url = rlUrlHome + 'index.php?controller=' + controller + '&action=build&key={key}&form=';
    var list = [
        {
            text: lang['ext_build_submit_form'],
            href: action_url + 'submit_form'
        },
        {
            text: lang['ext_build_short_form'],
            href: action_url + 'short_form'
        },
        {
            text: lang['ext_build_listing_title'],
            href: action_url + 'listing_title'
        },
        {
            text: lang['ext_build_featured'],
            href: action_url + 'featured_form'
        },
        {
            text: lang['ext_build_sorting'],
            href: action_url + 'sorting_form'
        }
    ];

    $(document).ready(function(){
        categoriesGrid = new gridObj({
            key: 'categories',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/categories.inc.php?q=ext',
            remoteSortable: true,
            defaultSortField: 'name',
            title: lang['ext_categories_manager'],
            filters: cookie_filters,
            checkbox: true,
            actions: mass_actions,
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Count', mapping: 'Count'},
                {name: 'Parent', mapping: 'Parent', type: 'string'},
                {name: 'Parent_ID', mapping: 'Parent_ID', type: 'int'},
                {name: 'Parent_key', mapping: 'Parent_key', type: 'string'},
                {name: 'Lock', mapping: 'Lock'},
                {name: 'Position', mapping: 'Position', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Key', mapping: 'Key'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    width: 22,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_listings_count'],
                    dataIndex: 'Count',
                    width: 8,
                    renderer: function(value, ext, row){
                        value = '<a style="display: block;" ext:qtip="'+lang['ext_browse_category']+'" class="green_11" href="'+ rlUrlHome +'index.php?controller=browse&id='+ row.data.ID +'"><b>'+ value +'</b></a>';
                        return value;
                    }
                },{
                    header: lang['ext_parent'],
                    dataIndex: 'Parent',
                    id: 'rlExt_item',
                    width: 15,
                    renderer: function(value, ext, row){
                        if ( row.data.Parent_ID )
                        {
                            value = '<a ext:qtip="'+lang['ext_edit_parent_category']+'" class="green_11" href="'+ rlUrlHome +'index.php?controller=categories&action=edit&key='+ row.data.Parent_key +'">'+ value +'</a>';
                        }
                        return value;
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 10,
                    renderer: function(value){
                        return '<b>'+value+'</b>';
                    }
                },{
                    header: lang['ext_locked'],
                    dataIndex: 'Lock',
                    width: 8,
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
                        selectOnFocus: true
                    })
                },{
                    header: lang['ext_position'],
                    dataIndex: 'Position',
                    width: 70,
                    fixed: true,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true,
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
                    width: 100,
                    fixed: true,
                    dataIndex: 'Key',
                    sortable: false,
                    renderer: function(data, ext, row) {
                        var out = "<center>";
                        var splitter = false;

                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += '<img onclick="flynax.extModal(this, \''+data+'\');" class="build" ext:qtip="'+lang['ext_build']+'" src="'+rlUrlHome+'img/blank.gif" /></a>';
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&key="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }

                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            if (general_cats.indexOf(row.data.ID) >= 0) {
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_delete_general']+"\", \"xajax_prepareDeleting\", \""+row.data.ID+"\" )' />";

                            } else {
                                out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='xajax_prepareDeleting("+row.data.ID+");' />";
                            }
                        }
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplCategoriesGrid'}{literal}

        categoriesGrid.init();
        grid.push(categoriesGrid.grid);

        // actions listener
        categoriesGrid.actionButton.addListener('click', function()
        {
            var sel_obj = categoriesGrid.checkboxColumn.getSelections();
            var action = categoriesGrid.actionsDropDown.getValue();

            if (!action)
            {
                return false;
            }

            for( var i = 0; i < sel_obj.length; i++ )
            {
                categoriesGrid.ids += sel_obj[i].id;
                if ( sel_obj.length != i+1 )
                {
                    categoriesGrid.ids += '|';
                }
            }

            switch (action) {
                case 'delete':
                    Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn) {
                        if (btn == 'yes') {
                            xajax_categoryMassActions(categoriesGrid.ids, action);
                        //  categoriesGrid.store.reload();
                        }
                    });
                    break;

                default:
                    xajax_categoryMassActions(categoriesGrid.ids, action);
                    //categoriesGrid.store.reload();
                    break;
            }

            categoriesGrid.checkboxColumn.clearSelections();
            categoriesGrid.actionsDropDown.setVisible(false);
            categoriesGrid.actionButton.setVisible(false);
        });
    });
    {/literal}
    //]]>
    </script>
    <!-- categories grid end -->

    {rlHook name='apTplCategoriesBottom'}

{/if}

<!-- listing categories tpl end -->
