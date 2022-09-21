<!-- listings tpl -->

<script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>

<!-- navigation bar -->
<div class="nav_bar">
    {rlHook name='apTplBrowseNavBar'}

    {if $category.ID && $aRights.categories.edit}
        <a id="locked_button" href="javascript:void(0)" onclick="xajax_lockCategory('{$category.ID}', '{if $category.Lock}unlock{else}lock{/if}');" class="button_bar"><span class="left"></span><span class="center_{if $category.Lock}unlock{else}lock{/if}" id="locked_button_phrase">{if $category.Lock}{$lang.unlock_category}{else}{$lang.lock_category}{/if}</span><span class="right"></span></a>
    {/if}

    {if $aRights.categories.add}
        <a href="{$rlBase}index.php?controller=categories&amp;action=add&amp;parent_id={$category.ID}" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_category}</span><span class="right"></span></a>
    {/if}

    {if $category.ID}
        {if $aRights.categories.edit}
            <a href="{$rlBase}index.php?controller=categories&amp;action=build&amp;form=submit_form&amp;key={$category.Key}" class="button_bar"><span class="left"></span><span class="center_build">{$lang.build_category}</span><span class="right"></span></a>

            <a href="{$rlBase}index.php?controller=categories&amp;action=edit&amp;key={$category.Key}&amp;parent_id={$category.Parent_ID}&amp;redirect_id={$category.ID}" class="button_bar"><span class="left"></span><span class="center_edit">{$lang.edit_category}</span><span class="right"></span></a>
        {/if}

        {if $aRights.categories.delete}
            <a href="javascript:void(0)" onclick="{if $listing_types[$category.Type].Cat_general_cat == $category.ID}rlConfirm( '{$lang.notice_delete_general}', 'xajax_prepareDeleting', '{$category.ID}'){else}xajax_prepareDeleting('{$category.ID}'){/if}" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.delete_category}</span><span class="right"></span></a>
        {/if}
    {/if}
</div>
<!-- navigation bar end -->

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
    //]]>
    </script>
</div>
<!-- delete category block end -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<div class="categories">
    {if !empty($categories)}
        {if $smarty.get.id}

            <table class="sTable">
            <tr>
            {foreach from=$categories item='cat' name='fCats'}
                <td style="width: {$width}%;" valign="top">
                    <div class="item">
                        <a class="category" title="{$cat.name}" href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;id={$cat.ID}">{if $cat.name}{$cat.name}{else}{$lang.not_available}{/if}</a>
                        <span class="category_listings_count">{$cat.Count}</span>

                        {if !empty($cat.sub_categories) && $listing_types[$category.Type].Cat_show_subcats}
                        <div class="sub_categories">
                            {foreach from=$cat.sub_categories item='sub_cat' name='subCatF'}
                                <a title="{$sub_cat.name}" href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;id={$sub_cat.ID}" class="sub_category">{if $sub_cat.name}{$sub_cat.name}{else}{$lang.not_available}{/if}</a>{if !$smarty.foreach.subCatF.last}, {/if}
                            {/foreach}
                        </div>
                        {/if}
                    </div>
                </td>

                {if $smarty.foreach.fCats.iteration%3 == 0 && $smarty.foreach.fCats.iteration != $smarty.foreach.fCats.total }
                </tr>
                <tr>
                {/if}
            {/foreach}
            </tr>
            </table>

        {else}

            {foreach from=$categories item='section' name='secF'}
                <fieldset class="light">
                    <legend id="legend_section_{$section.ID}" class="up" onclick="fieldset_action('section_{$section.ID}');">{$section.name}</legend>
                    <div id="section_{$section.ID}">
                        <table class="sTable" style="table-layout: fixed;">
                        <tr>
                            {foreach from=$section.Categories item='cat' name='catF'}
                            <td valign="top">
                                <div class="item">
                                    <a class="category{if $listing_types[$cat.Type].Cat_general_cat == $cat.ID} general_cat{/if}" title="{$cat.name}" href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;id={$cat.ID}">{if $cat.name}{$cat.name}{else}{$lang.not_available}{/if}</a>
                                    {*if $listing_types[$section.Key].Cat_listing_counter*}
                                        <span class="category_listings_count">{$cat.Count}</span>
                                    {*/if*}
                                    {if !empty($cat.sub_categories) && $listing_types[$section.Key].Cat_show_subcats}
                                    <div class="sub_categories">
                                        {foreach from=$cat.sub_categories item='sub_cat' name='subCatF'}
                                            <a title="{$sub_cat.name}" href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;id={$sub_cat.ID}" class="sub_category{if $listing_types[$cat.Type].Cat_general_cat == $sub_cat.ID} general_cat{/if}">{if $sub_cat.name}{$sub_cat.name}{else}{$lang.not_available}{/if}</a>{if !$smarty.foreach.subCatF.last}, {/if}
                                        {/foreach}
                                    </div>
                                    {/if}
                                </div>
                            </td>

                            {if $smarty.foreach.catF.iteration%3 == 0 && $smarty.foreach.catF.iteration != $smarty.foreach.catF.total }
                            </tr>
                            <tr>
                            {/if}
                            {/foreach}
                        </tr>
                        </table>
                    </div>
                </fieldset>
            {/foreach}

        {/if}
    {else}
        {$lang.no_subcategories}
    {/if}
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<!-- navigation bar -->
{if $category.ID}
    {rlHook name='apTplBrowseListingsNavBar'}

    <div id="nav_bar">
        <a href="javascript:void(0)" onclick="show('filters', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.filters}</span><span class="right"></span></a>

        {if $aRights.listings.add}
            <a href="{$rlBase}index.php?controller=listings&amp;action=add&amp;category={$category.ID}" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_listing}</span><span class="right"></span></a>
        {/if}
    </div>
{/if}
<!-- navigation bar end -->

<div id="action_blocks">

    {if !isset($smarty.get.action)}
        <!-- filters -->
        <div id="filters" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.filter_by}

            <table>
            <tr>
                <td valign="top">
                    <table class="form">
                    {foreach from=$filters item='filter' key='field'}
                    <tr>
                        <td class="name w130">{$filter.phrase}</td>
                        <td class="field">
                            <select class="filters w200" id="{$field}">
                            <option value="">{if $field == 'Category_ID'}{$lang.choose_listing_type}{else}- {$lang.all} -{/if}</option>
                            {foreach from=$filter.items item='item' key='value'}
                                <option {if $item|is_array && $item.type}id="option_{$item.type}_{$value}"{/if} {if $item|is_array && $item.margin}{if $item.margin == 5}class="highlight_opt"{/if} style="margin-left: {$item.margin}px;"{/if}value="{$value}" {if isset($status) && $field == 'Status' && $value == $status}selected="selected"{/if}>{if $item|is_array}{$item.name}{else}{$item}{/if}</option>
                            {/foreach}
                            </select>
                        </td>
                    </tr>
                    {/foreach}

                    <tr>
                        <td></td>
                        <td class="field">
                            <input type="button" class="button" value="{$lang.filter}" id="filter_button" />
                            <input type="button" class="button" value="{$lang.reset}" id="reset_filter_button" />
                            <a class="cancel" href="javascript:void(0)" onclick="show('filters')">{$lang.cancel}</a>
                        </td>
                    </tr>
                    </table>
                </td>
                <td style="width: 50px;"></td>
                <td valign="top">
                    <table class="form">
                    <tr>
                        <td class="name w130">{$lang.listing_id}</td>
                        <td class="field">
                            <input class="filters" type="text" id="listing_id" maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.username}</td>
                        <td class="field">
                            <input class="filters" type="text" maxlength="255" id="Account" />
                        </td>
                    </tr>
                    <tr>
                    <td class="name w130">{$lang.name}</td>
                        <td class="field">
                            <input class="filters" type="text" id="name" maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.mail}</td>
                        <td class="field">
                            <input class="filters" type="text" id="email" maxlength="60" />
                        </td>
                    </tr>
                    <tr>
                        <td class="name w130">{$lang.account_type}</td>
                        <td class="field">
                            <select class="filters w200" id="account_type">
                                <option value="">{$lang.select}</option>
                                {foreach from=$account_types item='type'}
                                    <option value="{$type.Key}" {if $sPost.profile.type == $type.Key}selected="selected"{/if}>{$type.name}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>

                    {rlHook name='apTplBrowseListingsSearch'}

                    </table>
                </td>
            </tr>
            </table>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>

        <script type="text/javascript">
        {literal}
        var filters = new Array();
        var step = 0;
        var category_selected = null;

        $(document).ready(function(){

            if ( readCookie('listings_sc') )
            {
                $('#filters').show();
                var cookie_filters = readCookie('listings_sc').split(',');

                for (var i in cookie_filters)
                {
                    if ( typeof(cookie_filters[i]) == 'string' )
                    {
                        var item = cookie_filters[i].split('||');
                        $('#'+item[0]).val(item[1]);

                        if ( item[0] == 'Category_ID' ) {
                            category_selected = item[1];
                        }
                    }
                }
            }

            $('#filter_button').click(function(){
                filters = new Array();
                write_filters = new Array();

                createCookie('listings_pn', 0, 1);

                $('.filters').each(function(){
                    if ($(this).attr('value') != 0)
                    {
                        filters.push(new Array($(this).attr('id'), $(this).attr('value')));
                        write_filters.push($(this).attr('id')+'||'+$(this).attr('value'));
                    }
                });

                {/literal}{rlHook name='apTplBrowseSearchJS'}{literal}

                // save search criteria
                createCookie('listings_sc', write_filters, 1);

                // reload grid
                listingsGrid.filters = filters;
                listingsGrid.reload();
            });

            $('#reset_filter_button').click(function(){
                eraseCookie('listings_sc');
                listingsGrid.reset();

                $("#filters select option[value='']").attr('selected', true);
                $("#filters input[type=text]").val('');

                $('#Category_ID').trigger('reset');
            });

            /* autocomplete js */
            $('#Account').rlAutoComplete();

            $('#Category_ID').categoryDropdown({
                listingType: '#Type',
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
        <!-- filters end -->
    {/if}

    <!-- categories list -->
    <div id="new_listing" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <div class="mc_title">{$lang.choose_category}:</div>

    <div style="margin: 10px;">
        {foreach from=$categories_form item='section'}
        <div class="grey_middle grey_line" style="margin-bottom: 6px;">
            <b>{$section.name}</b>
        </div>

        {if !empty($section.Types)}
        <ul style="margin: 5px;">
            {foreach from=$section.Types item='type' name='fTypes'}
                <li class="grey_middle" style="margin-bottom: 5px;">
                    {$smarty.foreach.fTypes.iteration}.
                    <a title="{$type.name}" href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=add&amp;category={$type.ID}" style="margin-left: 5px;text-transform: capitalize;" class="blue_11_bold_link">{$type.name}</a>
                </li>
            {/foreach}
        </ul>
        {else}
            <div style="margin-left: 10px;" class="blue_middle">{$lang.no_items_in_sections}</div>
        {/if}

        {/foreach}
    </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- categories list end -->

</div>

<!-- remote actions -->
<script type="text/javascript">
{literal}
$(document).ready(function(){
    {/literal}
    {if isset($smarty.get.status)}
        show('filters', '#action_blocks div');
    {elseif isset($smarty.get.new_listing)}
        show('new_listing', '#action_blocks div');
    {/if}
    {literal}
});
{/literal}
</script>

<!-- remote actions end -->

{if $category.ID}

    <script type="text/javascript">//<![CDATA[
    // collect plans
    var listing_plans = [
        {foreach from=$plans item='plan' name='plans_f'}
            ['{$plan.ID}', '{$plan.name}']{if !$smarty.foreach.plans_f.last},{/if}
        {/foreach}
    ];

    //var filters_url = '';
    var ui = typeof( rl_ui ) != 'undefined' ? '&ui='+rl_ui : '';
    var ui_cat_id = typeof( cur_cat_id ) != 'undefined' ? '&cat_id='+cur_cat_id : '';

    /* read cookies filters */
    var cookies_filters = false;

    if ( readCookie('listings_sc') )
        cookies_filters = readCookie('listings_sc').split(',');

    //]]>
    </script>

    <!-- listings grid create -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var mass_actions = [
        [lang['ext_activate'], 'activate'],
        [lang['ext_suspend'], 'approve'],
        [lang['ext_renew'], 'renew'],
        {if 'delete'|in_array:$aRights.listings}[lang['ext_delete'], 'delete'],{/if}
        [lang['ext_move'], 'move'],
        [lang['ext_make_featured'], 'featured'],
        [lang['ext_annul_featured'], 'annul_featured']
    ];

    {literal}

    var listingsGrid;
    $(document).ready(function(){

        listingsGrid = new gridObj({
            key: 'listings',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/listings.inc.php?q=ext&f_Category_ID={/literal}{$category.ID}{literal}',
            defaultSortField: 'Date',
            defaultSortType: 'DESC',
            remoteSortable: false,
            checkbox: true,
            actions: mass_actions,
            filters: cookies_filters,
            filtersPrefix: true,
            title: lang['ext_listings_manager'],
            expander: true,
            expanderTpl: '<div style="margin: 0 5px 5px 80px"> \
                <table> \
                <tr> \
                <td>{thumbnail}</td> \
                <td>{fields}</td> \
                </tr> \
                </table> \
                <div> \
            ',
            affectedObjects: '#make_featured,#move_area',
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'title', mapping: 'title', type: 'string'},
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Type_key', mapping: 'Type_key'},
                {name: 'Plan_name', mapping: 'Plan_name'},
                {name: 'Plan_ID', mapping: 'Plan_name'},
                {name: 'Plan_info', mapping: 'Plan_info'},
                {name: 'Cat_title', mapping: 'Cat_title', type: 'string'},
                {name: 'Cat_ID', mapping: 'Cat_ID', type: 'int'},
                {name: 'Cat_custom', mapping: 'Cat_custom', type: 'int'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Pay_date', mapping: 'Pay_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                {name: 'fields', mapping: 'fields', type: 'string'},
                {name: 'data', mapping: 'data', type: 'string'},
                {name: 'Allow_photo', mapping: 'Allow_photo', type: 'int'},
                {name: 'Allow_video', mapping: 'Allow_video', type: 'int'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 3,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_title'],
                    dataIndex: 'title',
                    width: 25,
                    id: 'rlExt_item'
                },{
                    header: lang['ext_owner'],
                    dataIndex: 'Username',
                    width: 8,
                    id: 'rlExt_item_bold',
                    renderer: function(username, ext, row){
                        return "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</a>"
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 8,
                    renderer: function(val, obj, row){
                        var out = '<a target="_blank" ext:qtip="'+lang['ext_click_to_view_details']+'" href="'+rlUrlHome+'index.php?controller=listing_types&action=edit&key='+row.data.Type_key+'">'+val+'</a>';
                        return out;
                    }
                },{
                    header: lang['ext_category'],
                    dataIndex: 'Cat_title',
                    width: 9,
                    renderer: function(val, obj, row){
                        var link = row.data.Cat_custom ? rlUrlHome+'index.php?controller=custom_categories' : rlUrlHome+'index.php?controller=browse&id='+row.data.Cat_ID;
                        var out = '<a target="_blank" ext:qtip="'+lang['ext_click_to_view_details']+'" href="'+link+'">'+val+'</a>';
                        return out;
                    }
                },{
                    header: lang['ext_add_date'],
                    dataIndex: 'Date',
                    width: 10,
                    hidden: true,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_payed'],
                    dataIndex: 'Pay_date',
                    width: 8,
                    renderer: function(val){
                        if (!val)
                        {
                            var date = '<span class="delete" ext:qtip="'+lang['ext_click_to_set_pay']+'">'+lang['ext_not_payed']+'</span>';
                        }
                        else
                        {
                            var date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                            date = '<span class="build" ext:qtip="'+lang['ext_click_to_edit']+'">'+date+'</span>';
                        }
                        return date;
                    },
                    editor: new Ext.form.DateField({
                        format: 'Y-m-d H:i:s'
                    })
                },{
                    header: lang['ext_plan'],
                    dataIndex: 'Plan_ID',
                    width: 11,
                    editor: new Ext.form.ComboBox({
                        store: listing_plans,
                        mode: 'local',
                        triggerAction: 'all'
                    }),
                    renderer: function (val, obj, row){
                        if (val != '')
                        {
                            return '<img class="info" ext:qtip="'+row.data.Plan_info+'" alt="" src="'+rlUrlHome+'img/blank.gif" />&nbsp;&nbsp;<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                        }
                        else
                        {
                            return '<span class="delete" ext:qtip="'+lang['ext_click_to_edit']+'" style="margin-left: 21px;">'+lang['ext_no_plan_set']+'</span>';
                        }
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 5,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang.active],
                            ['approval', lang.approval]
                        ],
                        mode: 'local',
                        typeAhead: true,
                        triggerAction: 'all',
                        selectOnFocus: true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 100,
                    fixed: true,
                    dataIndex: 'data',
                    sortable: false,
                    resizeable: false,
                    renderer: function(id, obj, row){
                        var out = "<div style='text-align: right'>";
                        var splitter = false;

                        if ( cKey == 'browse' )
                        {
                            cKey = 'listings';
                        }
                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            if ( row.data.Allow_photo )
                            {
                                out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=photos&id="+id+"'><img class='photo' ext:qtip='"+lang['ext_manage_photo']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            }
                            if ( row.data.Allow_video )
                            {
                                out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=video&id="+id+"'><img class='video' ext:qtip='"+lang['ext_manage_video']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            }
                        }
                        if ( rights[cKey].indexOf('edit') >= 0 )
                        {
                            out += "<a href=\""+rlUrlHome+"index.php?controller=listings&action=edit&id="+id+ui+ui_cat_id+"\"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        }
                        if ( rights[cKey].indexOf('delete') >= 0 )
                        {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlPrompt( \""+lang['ext_notice_'+delete_mod]+"\",  \"xajax_deleteListing\", \""+id+"\" )' />";
                        }
                        out += "</div>";

                        return out;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTplBrowseListingsGrid'}{literal}

        listingsGrid.init();
        grid.push(listingsGrid.grid);

        // actions listener
        listingsGrid.actionButton.addListener('click', function()
        {
            var sel_obj = listingsGrid.checkboxColumn.getSelections();
            var action = listingsGrid.actionsDropDown.getValue();

            if (!action)
            {
                return false;
            }

            for( var i = 0; i < sel_obj.length; i++ )
            {
                listingsGrid.ids += sel_obj[i].id;
                if ( sel_obj.length != i+1 )
                {
                    listingsGrid.ids += '|';
                }
            }

            if ( action == 'delete' )
            {
                Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn){
                    if ( btn == 'yes' )
                    {
                        xajax_massActions( listingsGrid.ids, action );
                        listingsGrid.store.reload();
                    }
                });
            }
            else if( action == 'featured' )
            {
                $('#make_featured').fadeIn('slow');
                return false;
            }
            else if( action == 'annul_featured' )
            {
                $('#mass_areas div.scroll').fadeOut('fast');
                Ext.MessageBox.confirm('Confirm', lang['ext_annul_featued_notice'], function(btn){
                    if ( btn == 'yes' )
                    {
                        xajax_annulFeatured( listingsGrid.ids );
                    }
                });

                return false;
            }
            else if( action == 'move' )
            {
                $('#mass_areas div.scroll').fadeOut('fast');
                $('#move_area').fadeIn('slow');
                return false;
            }
            else
            {
                $('#make_featured,#move_area').fadeOut('fast');
                xajax_massActions( listingsGrid.ids, action );
                listingsGrid.store.reload();
            }

            listingsGrid.checkboxColumn.clearSelections();
            listingsGrid.actionsDropDown.setVisible(false);
            listingsGrid.actionButton.setVisible(false);
        });

        listingsGrid.grid.addListener('afteredit', function(editEvent)
        {
            if ( editEvent.field == 'Plan_ID' )
            {
                listingsGrid.reload();
            }
        });

    });
    {/literal}
    //]]>
    </script>

    <!-- make featured -->
    <div id="make_featured" style="margin-top: 10px;" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
        <div class="mc_title">{$lang.make_featured}</div>
        <table class="sTable">
        <tr>
            <td style="width: 180px" class="td_splitter"><span class="red">*</span>{$lang.plan}</td>
            <td>
                <select class="lang_add" id="featured_plan">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$featured_plans item='featured_plan'}
                        <option value="{$featured_plan.ID}">{$featured_plan.name}</option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="button" onclick="xajax_makeFeatured(listingsGrid.ids, $('#featured_plan').val());" class="button lang_add" value="{$lang.save}" />
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- make featured end -->

    <!-- move listing block -->
    <div id="move_area" style="margin-top: 10px;" class="hide scroll">
        <script type="text/javascript">
        var move_category_id = 0;

        {literal}
        function moveOnCategorySelect(id, name) {
            move_category_id = id;
        }

        function moveOnButtonClick() {
            if (listingsGrid.ids.length > 0 && move_category_id > 0) {
                $('div.namespace-move a.button').text(lang['loading']);
                xajax_moveListing(listingsGrid.ids, move_category_id);
            }
        }
        {/literal}
        </script>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.move_listings}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' namespace='move' button=$lang.move}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <!-- move listing block end -->

    {rlHook name='apTplBrowseBottom'}

{/if}

<!-- listings tpl end -->
