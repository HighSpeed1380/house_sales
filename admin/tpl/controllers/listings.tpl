<!-- listings tpl -->

{if !$deny}

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.qtip.js"></script>
    <script type="text/javascript">flynax.qtip(); flynax.phoneField();</script>

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.caret.js"></script>
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}ckeditor/ckeditor.js"></script>

    <!-- navigation bar -->
    <div id="nav_bar">
        {rlHook name='apTplListingsNavBar'}

        {if $smarty.get.action == 'photos'}
            <a href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=video&amp;id={$smarty.get.id}" class="button_bar"><span class="left"></span><span class="center_video">{$lang.manage_video}</span><span class="right"></span></a>
        {/if}

        {if !isset($smarty.get.action)}
            <a href="javascript:void(0)" onclick="show('filters', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.filters}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.action == 'video'}
            <a href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=photos&amp;id={$smarty.get.id}" class="button_bar"><span class="left"></span><span class="center_photo">{$lang.manage_photos}</span><span class="right"></span></a>
        {/if}

        {if $aRights.$cKey.add && !isset($smarty.get.action)}
            <a href="javascript:void(0)" onclick="show('new_listing', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_listing}</span><span class="right"></span></a>
        {/if}

        {if $smarty.get.action == 'view' && $aRights.$cKey.edit == 'edit'}
            <a href="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=edit&amp;id={$listing_data.ID}" class="button_bar"><span class="left"></span><span class="center_edit">{$lang.edit_listing}</span><span class="right"></span></a>
        {/if}

        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.listings_list}</span><span class="right"></span></a>
    </div>
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
                                    <option value="{$value}" {if isset($status) && $field == 'Status' && $value == $status}selected="selected"{/if}>{if $item|is_array}{if $item.name}{$item.name}{else}{$lang[$item.pName]}{/if}{else}{$item}{/if}</option>
                                {/foreach}
                                </select>
                            </td>
                        </tr>
                        {/foreach}

                        <tr>
                            <td></td>
                            <td class="field nowrap">
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

                        {rlHook name='apTplListingsSearch2'}

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

                    {/literal}{rlHook name='apTplListingsSearchJS'}{literal}

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
        {if $aRights.$cKey.add && !$smarty.get.action}
            <div id="new_listing" class="hide">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.select_category}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' namespace='new' mode='link'}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
            </div>
        {/if}
        <!-- categories list end -->

    </div>

    {assign var='sPost' value=$smarty.post}

    {if $smarty.get.action == 'add'}

        <!-- add new listing -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_listing}

        <!-- listing fieldset -->
        <div style="margin: 5px 10px 10px;">
            <form onsubmit="return submitHandler()" id="add_listing" method="post" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=add&amp;category={$smarty.get.category}" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add" />

                <!-- display plans -->
                {if !empty($plans) && (!$config.membership_module || ($config.membership_module && $config.allow_listing_plans))}
                <fieldset class="light">
                    <legend id="legend_plans" class="up" onclick="fieldset_action('plans');">{$lang.plans}</legend>
                    <div id="plans">
                        {foreach from=$plans item='plan' name='fPlan'}
                            <div class="plan_item">
                                <table class="sTable">
                                <tr>
                                    <td align="center" style="width: 30px"><input accesskey="{$plan.Cross}" style="margin: 0 10px 0 0;" id="plan_{$plan.ID}" type="radio" name="f[l_plan]" value="{$plan.ID}" {if $plan.ID == $smarty.post.f.l_plan}checked="checked"{else}{if $smarty.foreach.fPlan.first}checked="checked"{/if}{/if} /></td>
                                    <td>
                                        <label for="plan_{$plan.ID}" class="blue_11_normal">
                                            {assign var='l_type' value=$plan.Type|cat:'_plan'}
                                            {$plan.name} - <b>{if $plan.Price > 0}{$config.system_currency}{$plan.Price}{else}{$lang.free}{/if}</b>
                                        </label>
                                        <div class="desc">{$plan.des}</div>
                                        {if $plan.Advanced_mode}
                                            <div id="featured_option_{$plan.ID}" class="featured_option hide">
                                                <div>{$lang.feature_mode_caption}</div>
                                                <label>
                                                    <input class="{if $smarty.post.listing_type == 'standard' || !$smarty.post.listing_type}checked{/if}" type="radio" name="listing_type" value="standard" />
                                                    {$lang.standard_listing}
                                                </label>
                                                <label>
                                                    <input class="{if $smarty.post.listing_type == 'featured'}checked{/if}{if $plan.Package_ID && empty($plan.Featured_remains) && $plan.Featured_listings != 0} disabled{/if}" type="radio" name="listing_type" value="featured" />
                                                    {$lang.featured_listing}
                                                </label>
                                            </div>
                                        {/if}
                                    </td>
                                </tr>
                                </table>
                            </div>
                        {/foreach}
                    </div>
                </fieldset>
                {/if}
                <!-- display plans end -->

                <!-- crossed categories -->
                <div id="crossed_area" class="hide">
                    <input type="hidden" name="crossed_done" value="{if $smarty.session.add_listing.crossed_done}1{else}0{/if}" />

                    <fieldset class="light">
                        <legend id="legend_crossed" class="up" onclick="fieldset_action('crossed');">{$lang.crossed_categories}</legend>
                        <div id="crossed">
                            <div class="auth">
                                <div style="padding: 0 0 10px 0;">
                                    {assign var='number_var' value=`$smarty.ldelim`number`$smarty.rdelim`}
                                    <div class="dark" id="cc_text">{$lang.crossed_top_text|replace:$number_var:'<b id="cc_number"></b>'}</div>
                                    <div class="dark hide" id="cc_text_denied">{$lang.crossed_top_text_denied}</div>
                                </div>

                                <!-- print sections/categories tree -->
                                <div id="crossed_tree" class="tree{if $smarty.post.crossed_done} hide{/if}">
                                {foreach from=$sections item='section'}
                                    <fieldset class="light">
                                        <legend id="legend_crossed_{$section.ID}" class="up" onclick="fieldset_action('crossed_{$section.ID}');">{$section.name}</legend>
                                        <div id="crossed_{$section.ID}" class="tree">
                                            {assign var='type_page_key' value='lt_'|cat:$section.Key}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$section.ID name=$section.name}

                                            {if !empty($section.Categories)}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_crossed.tpl' categories=$section.Categories first=true}
                                            {else}
                                                <div class="dark">{$lang.no_items_in_sections}</div>
                                            {/if}

                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                                        </div>
                                    </fieldset>
                                {/foreach}
                                </div>
                                <!-- print sections/categories tree end -->

                                <ul class="hide" id="crossed_selected"><li class="first dark"><b>{$lang.selected_crossed_categories}</b></li></ul>
                                <input id="crossed_button" type="button" value="{if $smarty.post.crossed_done}{$lang.manage}{else}{$lang.done}{/if}" />
                            </div>
                        </div>
                    </fieldset>
                </div>

                <script type="text/javascript">
                var plans = Array();
                var selected_plan_id = {if $smarty.post.f.l_plan}{$smarty.post.f.l_plan}{else}0{/if};
                var last_plan_id = 0;
                var ca_post = {if $crossed}[{foreach from=$crossed item='crossed_cat' name='crossedF'}['{$crossed_cat}']{if !$smarty.foreach.crossedF.last},{/if}{/foreach}]{else}false{/if};
                var cc_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}['{$parent_point}']{if !$smarty.foreach.parentF.last},{/if}{/foreach}]{else}false{/if};

                {foreach from=$plans item='plan'}
                plans[{$plan.ID}] = new Array();
                plans[{$plan.ID}]['Key'] = '{$plan.Key}';
                plans[{$plan.ID}]['Cross'] = {$plan.Cross};
                plans[{$plan.ID}]['Featured'] = {$plan.Featured};
                plans[{$plan.ID}]['Advanced_mode'] = {$plan.Advanced_mode};
                {/foreach}

                {literal}

                $(document).ready(function(){
                    flynax.treeLoadLevel('crossed', 'crossedTree');

                    if ( plans[selected_plan_id] && plans[selected_plan_id]['Cross'] )
                    {
                        crossCount = plans[selected_plan_id]['Cross'];
                        $('#crossed_area').show();
                        crossedTree();
                    }

                    if ( plans[selected_plan_id] )
                    {
                        planClickHandler($('input#plan_'+selected_plan_id));
                    }

                    /* plans click handler */
                    $('input[name="f[l_plan]"]').click(function(){
                        selected_plan_id = $(this).attr('id').split('_')[1];
                        crossCount = plans[selected_plan_id]['Cross'];

                        if ( crossCount > 0 )
                        {
                            $('#crossed_area').slideDown();
                            crossedTree();
                        }
                        else
                        {
                            $('#crossed_area').slideUp();
                        }

                        planClickHandler($(this));
                    });
                });

                var planClickHandler = function(obj){
                    if ( obj.length == 0 )
                        return;

                    selected_plan_id = $(obj).attr('id').split('_')[1];

                    if ( last_plan_id == selected_plan_id )
                        return;

                    last_plan_id = selected_plan_id;

                    $('div.featured_option').hide();
                    $('div.featured_option').prev().show();
                    $('div.featured_option input').attr('disabled', true);

                    if ( plans[selected_plan_id]['Featured'] && plans[selected_plan_id]['Advanced_mode'] )
                    {
                        $('#featured_option_'+selected_plan_id).prev().hide();
                        $('#featured_option_'+selected_plan_id).show();
                        $('#featured_option_'+selected_plan_id+' input').attr('disabled', false);
                        $('#featured_option_'+selected_plan_id+' input.disabled').attr('disabled', true);
                        $('#featured_option_'+selected_plan_id+' input:not(.disabled):first').attr('checked', true);
                        $('#featured_option_'+selected_plan_id+' input.checked').attr('checked', true);
                    }
                }

                {/literal}
                </script>
                <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}javascript/crossed.js"></script>
                <!-- crossed categories end -->

                <table class="form" style="margin: 0 16px 15px;">
                <tr>
                    <td class="name">{$lang.set_owner} <span class="red">*</span></td>
                    <td class="field">
                        <input type="text" name="account_id" id="account_id" value="{$requested_username}" />
                        <script type="text/javascript">
                        var post_account_id = {if $sPost.account_id}{$sPost.account_id}{else}false{/if};
                        var post_listing_type = '{$sPost.listing_type}';
                        {literal}
                            $('#account_id').rlAutoComplete({add_id: true, id: post_account_id});
                            {/literal}{if $config.membership_module && !$config.allow_listing_plans}{literal}
                            $(document).ready(function(){
                                if ($('#account_id').val() != '') {
                                    checkMemebershipPlan($('#account_id').val());
                                }
                                $(document).on('click', '#ac_interface div', function(){
                                    var username = $(this).html().replace(/<b>/i, '').replace(/<\/b>/i, '');
                                    checkMemebershipPlan(username);
                                });
                            });

                            var checkMemebershipPlan = function(username) {
                                var standard_checked = '';
                                var featured_checked = '';
                                $('table.form').find('tr.listing_type').remove();
                                $.getJSON(rlConfig['ajax_url'], {item: 'checkMemebershipPlan', username: username}, function(response){
                                    if (response.status == 'ok') {
                                        if (response.plan.Advanced_mode == 1) {
                                            if (response.plan.Standard_listings == 0) {
                                                standard_field = '<input type="radio" id="listing_type_standard" name="listing_type" value="standard" '+(post_listing_type == 'standard' || post_listing_type == '' ? 'checked="checked"' : '')+' /> <label for="listing_type_standard">{/literal}{$lang.standard}{literal}</label>';
                                            } else {
                                                if ((post_listing_type == 'standard' && response.plan.Standard_remains > 0) || (post_listing_type == '' && (response.plan.Standard_remains > 0 || response.plan.Standard_remains == ''))) {
                                                    standard_checked = 'checked="checked"';
                                                }
                                                standard_field = '<input type="radio" id="listing_type_standard" name="listing_type" value="standard" '+standard_checked+' '+(response.plan.Standard_remains <= 0 ? ' disabled="disabled"' : '')+' /> <label for="listing_type_standard">{/literal}{$lang.standard}{literal} ('+ response.plan.Standard_remains +')</label>';
                                            }
                                            if (response.plan.Featured_listings == 0) {
                                                featured_field = '<input type="radio" id="listing_type_featured" name="listing_type" value="featured" '+(post_listing_type == 'featured' || (post_listing_type == '' && response.plan.Standard_remains <= 0 && response.plan.Standard_listings > 0) ? 'checked="checked"' : '')+' /> <label for="listing_type_featured">{/literal}{$lang.featured}{literal}</label>';
                                            } else {
                                                if ((post_listing_type == 'featured' && response.plan.Featured_remains > 0) || (post_listing_type == '' && (response.plan.Featured_remains > 0 || response.plan.Featured_remains == ''))) {
                                                    featured_checked = 'checked="checked"';
                                                }
                                                featured_field = '<input type="radio" id="listing_type_featured" name="listing_type" value="featured" '+featured_checked+' '+(response.plan.Featured_remains <= 0 ? ' disabled="disabled"' : '')+' /> <label for="listing_type_featured">{/literal}{$lang.featured}{literal} ('+ response.plan.Featured_remains +')</label>';
                                            }
                                            $('select[name="status"]').parent().parent().after('<tr class="listing_type"><td class="name">{/literal}{$lang.listing_type}{literal} <span class="red">*</span></td><td class="field">' +standard_field + featured_field + '</td></tr>');
                                        } else {
                                            $('table.form').find('tr.listing_type').remove();
                                        }
                                    } else {
                                        $('#account_id').val('');
                                        if ($('#ac_hidden').length) {
                                            $('#ac_hidden').val('');
                                        }
                                        $('table.form').find('tr.listing_type').remove();
                                        printMessage('error', '{/literal}{$lang.listing_limit_exceeded_admin}{literal}');
                                    }
                                });
                            }
                            {/literal}{/if}{literal}
                        {/literal}
                        </script>
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.status} <span class="red">*</span></td>
                    <td class="field">
                        <select name="status" class="login_input_select">
                            <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                            <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                        </select>
                    </td>
                </tr>

                {rlHook name='apTplListingsFormAdd'}

                </table>

                {foreach from=$form item='group'}
                {if $group.Group_ID}
                    {if $group.Fields || !$group.Display}
                        {assign var='hide' value='false'}
                    {else}
                        {assign var='hide' value='true'}
                    {/if}

                    <fieldset>
                        <legend id="legend_group_{$group.Key}" class="up" onclick="fieldset_action('group_{$group.Key}');">{$lang[$group.pName]}</legend>
                        <div id="group_{$group.Key}">
                        {if $group.Fields}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
                        {else}
                            <span class="blue_middle">{$lang.no_items_in_group}</span>
                        {/if}
                        </div>
                    </fieldset>
                {else}
                    <div style="padding: 0 0 0 16px;">
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
                    </div>
                {/if}
                {/foreach}

                <table class="form" style="margin: 0 16px;">
                <tr>
                    <td class="no_divider"></td>
                    <td class="field"><input type="submit" value="{$lang.add_listing}" /></td>
                </tr>
                </table>
            </form>
        </div>

        <!-- listing fieldset end -->
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        <!-- add new listing end -->

    {elseif $smarty.get.action == 'edit'}

        <!-- listing fieldset -->
        {if !empty($form)}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

        <form onsubmit="return submitHandler()" id="edit_listing" method="post" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=edit&amp;id={$smarty.get.id}{if $smarty.get.ui}&amp;ui={$smarty.get.ui}{/if}{if $smarty.get.cat_id}&amp;cat_id={$smarty.get.cat_id}{/if}" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit" />
            <input type="hidden" name="fromPost" value="1" />
            {if $listing_info.Plan_type == 'account'}
                <input type="hidden" name="f[l_plan]" value="{$plan_info.ID}" />
            {/if}

            <!-- display plans -->
            {if !empty($plans) && $listing_info.Plan_type == 'listing'}
            <fieldset class="light">

                <legend id="legend_plans" class="up" onclick="fieldset_action('plans');">{$lang.plans}</legend>
                <div id="plans">
                    {foreach from=$plans item='plan' name='fPlan'}
                        <div class="plan_item{if $plan.ID != $smarty.post.f.l_plan} hide{/if}">
                            <table class="sTable">
                            <tr>
                                <td align="center" style="width: 30px"><input accesskey="{$plan.Cross}" style="margin: 0 10px 0 0;" id="plan_{$plan.ID}" type="radio" name="f[l_plan]" value="{$plan.ID}" {if $plan.ID == $smarty.post.f.l_plan}checked="checked"{else}{if $smarty.foreach.fPlan.first}checked="checked"{/if}{/if} /></td>
                                <td>
                                    <label for="plan_{$plan.ID}" class="blue_11_normal">
                                        {assign var='l_type' value=$plan.Type|cat:'_plan'}
                                        {$plan.name} - <b>{if $plan.Price > 0}{$config.system_currency}{$plan.Price}{else}{$lang.free}{/if}</b>
                                    </label>
                                    <div class="desc">{$plan.des}</div>
                                        {if $plan.Advanced_mode}
                                        <div id="featured_option_{$plan.ID}" class="featured_option hide">
                                            <div>{$lang.feature_mode_caption}</div>
                                            <label>
                                                <input {if $smarty.post.listing_type == 'standard' || !$smarty.post.listing_type}checked="checked"{/if} class="{if $smarty.post.listing_type == 'standard' || !$smarty.post.listing_type}checked{/if}" type="radio" name="listing_type" value="standard" />
                                                {$lang.standard_listing}
                                            </label>
                                            <label>
                                                <input {if $smarty.post.listing_type == 'featured'}checked="checked"{/if} class="{if $smarty.post.listing_type == 'featured'}checked{/if}{if $plan.Package_ID && empty($plan.Featured_remains) && $plan.Featured_listings != 0} disabled{/if}" type="radio" name="listing_type" value="featured" />
                                                {$lang.featured_listing}
                                            </label>
                                        </div>
                                    {/if}
                                </td>
                            </tr>
                            </table>
                        </div>
                    {/foreach}

                    {if $plans|@count > 1 || !$smarty.post.f.l_plan}
                        <input id="manage_plans" type="button" value="{$lang.manage}" />
                    {/if}
                </div>
            </fieldset>

            <script type="text/javascript">
            {literal}
            var plans_expand = false;
            $(document).ready(function(){
                $('#manage_plans').click(function(){
                    if ( plans_expand )
                    {
                        plans_expand = false;
                        $('div#plans div.hide').fadeOut();
                        $(this).val('{/literal}{$lang.manage}{literal}');
                    }
                    else
                    {
                        plans_expand = true;
                        $(this).val('{/literal}{$lang.apply}{literal}');
                        $('div#plans div.hide').fadeIn();
                    }
                });

                $('div#plans div.plan_item').click(function(){
                    $('div#plans div.plan_item').addClass('hide').css('display', 'block');
                    $(this).removeClass('hide');
                });

                $('.featured_option').click(function(){
                    $(this).closest('tr').find('input[name="f[l_plan]"]').attr('checked',true);
                });
            });

            {/literal}
            </script>
            {/if}
            <!-- display plans end -->

            <!-- crossed categories -->
            <div id="crossed_area" {if !$plan_info.Cross}class="hide"{/if}>
                <input type="hidden" name="crossed_done" value="{if $smarty.session.add_listing.crossed_done}1{else}0{/if}" />

                <fieldset class="light">
                    <legend id="legend_crossed" class="up" onclick="fieldset_action('crossed');">{$lang.crossed_categories}</legend>
                    <div id="crossed">

                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='crossed' name=$lang.crossed_categories}
                        <div class="auth">
                            <div style="padding: 0 0 10px 0;">
                                {assign var='number_var' value=`$smarty.ldelim`number`$smarty.rdelim`}
                                <div class="dark" id="cc_text">{$lang.crossed_top_text|replace:$number_var:'<b id="cc_number"></b>'}</div>
                                <div class="dark hide" id="cc_text_denied">{$lang.crossed_top_text_denied}</div>
                            </div>

                            <!-- print sections/categories tree -->
                            <div id="crossed_tree" class="tree{if $smarty.post.crossed_done} hide{/if}">
                            {foreach from=$sections item='section'}
                                <fieldset class="light">
                                    <legend id="legend_crossed_{$section.ID}" class="up" onclick="fieldset_action('crossed_{$section.ID}');">{$section.name}</legend>
                                    <div id="crossed_{$section.ID}" class="tree">
                                        {assign var='type_page_key' value='lt_'|cat:$section.Key}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$section.ID name=$section.name}

                                        {if !empty($section.Categories)}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_crossed.tpl' categories=$section.Categories first=true}
                                        {else}
                                            <div class="dark">{$lang.no_items_in_sections}</div>
                                        {/if}

                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                                    </div>
                                </fieldset>
                            {/foreach}
                            </div>
                            <!-- print sections/categories tree end -->

                            <ul class="hide" id="crossed_selected"><li class="first dark"><b>{$lang.selected_crossed_categories}</b></li></ul>
                            <input id="crossed_button" type="button" value="{if $smarty.post.crossed_done}{$lang.manage}{else}{$lang.done}{/if}" />
                        </div>
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

                        <script type="text/javascript">
                        var plans = Array();
                        var selected_plan_id = {if $smarty.post.f.l_plan}{$smarty.post.f.l_plan}{else}0{/if};
                        var ca_post = {if $crossed}[{foreach from=$crossed item='crossed_cat' name='crossedF'}['{$crossed_cat}']{if !$smarty.foreach.crossedF.last},{/if}{/foreach}]{else}false{/if};
                        var cc_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}['{$parent_point}']{if !$smarty.foreach.parentF.last},{/if}{/foreach}]{else}false{/if};

                        {foreach from=$plans item='plan'}
                        plans[{$plan.ID}] = new Array();
                        plans[{$plan.ID}]['Key'] = '{$plan.Key}';
                        plans[{$plan.ID}]['Cross'] = {$plan.Cross};
                        {/foreach}

                        {literal}

                        $(document).ready(function(){
                            flynax.treeLoadLevel('crossed', 'crossedTree');

                            if ( plans[selected_plan_id] && plans[selected_plan_id]['Cross'] )
                            {
                                crossCount = plans[selected_plan_id]['Cross'];
                                $('#crossed_area').show();
                                crossedTree();
                            }

                            /* plans click handler */
                            $('input[name="f[l_plan]"]').click(function(){
                                selected_plan_id = $(this).attr('id').split('_')[1];
                                crossCount = plans[selected_plan_id]['Cross'];

                                if ( crossCount > 0 )
                                {
                                    $('#crossed_area').slideDown();
                                    crossedTree();
                                }
                                else
                                {
                                    $('#crossed_area').slideUp();
                                }
                            });
                        });

                        {/literal}
                        </script>
                        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}javascript/crossed.js"></script>
                    </div>
                </fieldset>
            </div>
            <!-- crossed categories end -->

            <table class="form" style="margin: 0 16px 15px;">
            <tr>
                <td class="name">
                    {$lang.set_owner}
                </td>
                <td class="field">
                    <input type="text" name="account_id" id="account_id" value="{$requested_username}" />
                    <script type="text/javascript">
                    var account_id = {if $sPost.account_id}{$sPost.account_id}{else}false{/if};
                    var post_listing_type = '{if $sPost.listing_type}{$sPost.listing_type}{else}{$listing_info.Last_type}{/if}';
                    {literal}
                        $('#account_id').rlAutoComplete({add_id: true, id: account_id});

                        {/literal}{if $config.membership_module && !$config.allow_listing_plans}{literal}
                        $(document).ready(function(){
                            {/literal}{if $listing_info.Account_ID != $sPost.account_id}{literal}
                            if ($('#account_id').val() != '') {
                                checkMemebershipPlan($('#account_id').val());
                            }
                            {/literal}{/if}{literal}
                            $(document).on('click', '#ac_interface div', function(){
                                var username = $(this).html().replace(/<b>/i, '').replace(/<\/b>/i, '');
                                checkMemebershipPlan(username);
                            });
                        });

                        var checkMemebershipPlan = function(username) {
                            var checked = '';
                            $('table.form').find('tr.listing_type').remove();
                            $.getJSON(rlConfig['ajax_url'], {item: 'checkMemebershipPlan', username: username, listing_type: '{/literal}{$listing_info.Last_type}{literal}', edit: true}, function(response) {
                                if (response.status == 'ok') {
                                    if (response.plan.Advanced_mode == 1) {
                                        if (response.listing_type_not_match) {
                                            Ext.MessageBox.confirm(lang['warning'], '{/literal}{$lang.confirm_change_listing_type}{literal}', function(btn) {
                                                if (btn == 'yes') {
                                                    diaplayAdvancedMode(response.plan);
                                                }
                                                if (btn == 'no') {
                                                    $('#account_id').val('{/literal}{$listing_info.Username}{literal}');
                                                    $('#ac_hidden').val('{/literal}{$listing_info.Account_ID}{literal}');
                                                }
                                            });
                                        } else {
                                            diaplayAdvancedMode(response.plan);
                                        }

                                    } else {
                                        $('table.form').find('tr.listing_type').remove();
                                    }
                                } else {
                                    $('#account_id').val('{/literal}{$listing_info.Username}{literal}');
                                    $('#ac_hidden').val('{/literal}{$listing_info.Account_ID}{literal}');
                                    $('table.form').find('tr.listing_type').remove();
                                    printMessage('error', '{/literal}{$lang.listing_limit_exceeded_admin}{literal}');
                                }
                            });
                        }

                        var diaplayAdvancedMode = function(plan) {
                            if (plan.Standard_listings == 0) {
                                standard_field = '<input type="radio" id="listing_type_standard" name="listing_type" value="standard" '+(post_listing_type == 'standard' || post_listing_type == '' ? 'checked="checked"' : '')+' /> <label for="listing_type_standard">{/literal}{$lang.standard}{literal}</label>';
                            } else {
                                if ((post_listing_type == 'standard' && plan.Standard_remains > 0) || (post_listing_type == '' && (plan.Standard_remains > 0 || plan.Standard_remains == ''))) {
                                    var standard_checked = 'checked="checked"';
                                }
                                standard_field = '<input type="radio" id="listing_type_standard" name="listing_type" value="standard" '+standard_checked+' '+(plan.Standard_remains <= 0 ? ' disabled="disabled"' : '')+' /> <label for="listing_type_standard">{/literal}{$lang.standard}{literal} ('+ plan.Standard_remains +')</label>';
                            }
                            if (plan.Featured_listings == 0) {
                                featured_field = '<input type="radio" id="listing_type_featured" name="listing_type" value="featured" '+(post_listing_type == 'featured' || (post_listing_type == '' && plan.Standard_remains <= 0 && plan.Standard_listings > 0) ? 'checked="checked"' : '')+' /> <label for="listing_type_featured">{/literal}{$lang.featured}{literal}</label>';
                            } else {
                                if ((post_listing_type == 'featured' && plan.Featured_remains > 0) || (post_listing_type == '' && (plan.Featured_remains > 0 || plan.Featured_remains == ''))) {
                                    var featured_checked = 'checked="checked"';
                                }
                                featured_field = '<input type="radio" id="listing_type_featured" name="listing_type" value="featured" '+featured_checked+' '+(plan.Featured_remains <= 0 ? ' disabled="disabled"' : '')+' /> <label for="listing_type_featured">{/literal}{$lang.featured}{literal} ('+ plan.Featured_remains +')</label>';
                            }
                            $('select[name="status"]').parent().parent().after('<tr class="listing_type"><td class="name">{/literal}{$lang.listing_type}{literal} <span class="red">*</span></td><td class="field">' +standard_field + featured_field + '</td></tr>');
                        }
                        {/literal}{/if}{literal}
                    {/literal}
                    </script>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.status} <span class="red">*</span></td>
                <td class="field">
                    <select name="status" class="login_input_select">
                        <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>

            {rlHook name='apTplListingsFormEdit'}

            </table>

            {foreach from=$form item='group'}
            {if $group.Group_ID}
                {if $group.Fields || !$group.Display}
                    {assign var='hide' value='false'}
                {else}
                    {assign var='hide' value='true'}
                {/if}

                <fieldset>
                <legend id="legend_group_{$group.Key}" class="up" onclick="fieldset_action('group_{$group.Key}');">{$lang[$group.pName]}</legend>
                    <div id="group_{$group.Key}">
                    {if $group.Fields}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
                    {else}
                        <span>{$lang.no_items_in_group}</span>
                    {/if}
                    </div>
                </fieldset>
            {else}
                <div style="padding: 0 16px;">
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl' fields=$group.Fields}
                </div>
            {/if}
            {/foreach}

            <table class="form" style="margin: 0 16px;">
            <tr>
                <td class="no_divider"></td>
                <td class="field"><input type="submit" value="{$lang.edit_listing}" /></td>
            </tr>
            </table>
        </form>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        {/if}

        <!-- listing fieldset end -->
    {elseif $smarty.get.action == 'photos'}
        <!-- manage listing photo -->

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

        <!-- listing info -->
        <fieldset style="margin: 0 0 10px 0;">
            <legend id="legend_details" class="up" onclick="fieldset_action('details');">{$lang.listing_details}</legend>
            <div id="details">
                <h3 style="margin: 0 0 10px 10px;">{$listing.listing_title}</h3>
                <table class="list" style="margin: 0 10px 5px 10px;">
                {foreach from=$listing.fields item='item' key='field' name='fListings'}
                {if !empty($item.value)}
                <tr>
                    <td class="name">{$item.name}:</td>
                    <td class="value">{$item.value}</td>
                </tr>
                {/if}
                {/foreach}
                </table>
            </div>
        </fieldset>
        <!-- listing info end -->

        <!-- photos list -->
        <fieldset style="margin: 10px 0;">
            <legend id="legend_photos_list" class="up" onclick="fieldset_action('photos_list');">{$lang.pictures_manager}</legend>
            <div id="photos_list">
                <div style="padding: 0 10px;" id="photos_dom">
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'photo_manager.tpl'}
                </div>
            </div>
        </fieldset>
        <!-- photos list end -->

        {rlHook name='apTplListingsPhotos'}

        <style type="text/css">
        @import url("{$smarty.const.RL_LIBS_URL}cropper/cropper.css");
        </style>

        <script src="{$smarty.const.RL_LIBS_URL}cropper/cropper.min.js"></script>

        <script>
        lang['crop_completed'] = "{$lang.crop_completed}";
        rlConfig['current_listing_id'] = {$listing.ID};
        rlConfig['current_listing_account_id'] = {$listing.Account_ID};
        rlConfig['img_crop_module'] = {$config.img_crop_module};
        rlConfig['img_crop_thumbnail'] = {$config.img_crop_thumbnail};
        rlConfig['pg_upload_thumbnail_width'] = {if $config.pg_upload_thumbnail_width}{$config.pg_upload_thumbnail_width}{else}120{/if};
        rlConfig['pg_upload_thumbnail_height'] = {if $config.pg_upload_thumbnail_height}{$config.pg_upload_thumbnail_height}{else}90{/if};
        </script>

        <script src="{$rlTplBase}js/crop.js"></script>

        <!-- file crop -->
        <div id="crop_block" class="hide">
            <fieldset style="margin: 10px 0;">
                <legend id="legend_crop_area" class="up" onclick="fieldset_action('crop_area');">{$lang.pictures_manager}</legend>
                <div id="crop_area">

                    <div class="dark">{$lang.crop_notice}</div>
                    <div id="crop_obj" style="padding: 10px 0;"></div>

                    <input type="button" class="button" value="{$lang.rl_accept}" data-default-phrase="{$lang.rl_accept}" id="crop_accept" />
                    <input type="button" class="button" value="{$lang.cancel}" id="crop_cancel" />
                </div>
            </fieldset>
        </div>
        <!-- file crop end -->

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <!-- manage listing photo end -->
    {elseif $smarty.get.action == 'video'}
        <!-- add listing video -->

        {if $listing.Plan_video || $listing.Video_unlim}

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

            <!-- listing info -->
            <fieldset style="margin: 0 0 10px 0;">
                <legend id="legend_details" class="up" onclick="fieldset_action('details');">{$lang.listing_details}</legend>
                <div id="details">
                    <h3 style="margin: 0 0 10px 10px;">{$listing.listing_title}</h3>
                    <table class="list" style="margin: 0 10px 5px 10px;">
                    {foreach from=$listing.fields item='item' key='field' name='fListings'}
                    {if !empty($item.value)}
                    <tr>
                        <td class="name">{$item.name}:</td>
                        <td class="value">{$item.value}</td>
                    </tr>
                    {/if}
                    {/foreach}
                    </table>
                </div>
            </fieldset>
            <!-- listing info end -->

            <!-- file uploader -->
            <fieldset style="margin: 10px 0;">
                {if $video_allow && !$listing.Video_unlim}
                    {assign var='replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
                    {assign var='video_left' value=$lang.upload_video_left|replace:$replace:$video_allow}
                {else}
                    {assign var='video_left' value=$lang.upload_video}
                {/if}

                <legend id="legend_upload_area" class="up" onclick="fieldset_action('upload_area');"><span id="video_left">{$video_left}</span></legend>
                <div id="upload_area">

                    {if !$video_allow && !$listing.Video_unlim}
                        {assign var='replace_count' value=`$smarty.ldelim`count`$smarty.rdelim`}
                        {assign var='replace_plan' value=`$smarty.ldelim`plan`$smarty.rdelim`}
                        {assign var='plan_key' value='listing_plans+name+'|cat:$listing.Plan_key}
                        <div class="grey_middle" style="padding: 0 0 5px 10px">{$lang.no_more_videos|replace:$replace_count:$listing.Plan_video|replace:$replace_plan:$lang.$plan_key}</div>
                    {/if}

                    <div id="protect" {if !$video_allow && !$listing.Video_unlim}class="hide"{/if}>
                    <form method="post" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;action=video&amp;id={$smarty.get.id}" enctype="multipart/form-data">
                        <input name="upload" value="true" type="hidden" />
                        <div style="margin: 0 0 5px 10px;">
                            <table class="form" id="upload_fields">
                            <tr>
                                <td class="name w130">{$lang.video_type}:</td>
                                <td class="field">
                                    <select id="type_selector" name="type" >
                                        <option value="">{$lang.select}</option>
                                        <option {if $smarty.post.type == 'local'}selected="selected"{/if} value="local">{$lang.local}</option>
                                        <option {if $smarty.post.type == 'youtube'}selected="selected"{/if} value="youtube">{$lang.youtube}</option>
                                    </select>
                                </td>
                            </tr>
                            </table>

                            <div id="local_video" class="upload{if $smarty.post.type != 'local'} hide{/if}">
                                <table class="form">
                                <tr>
                                    <td class="name w130">{$lang.file}:</td>
                                    <td class="field">
                                        <input class="file" type="file" name="video" />
                                        <table>
                                        <tr>
                                            <td>{$lang.max_file_size}:</td>
                                            <td><b><em>{$max_file_size}</em></b></td>
                                        </tr>
                                        <tr>
                                            <td>{$lang.available_file_type}:</td>
                                            <td>
                                                {foreach from=$l_player_file_types item=item key='f_type' name='file_typesF'}
                                                <b><em>{$f_type}</em></b>{if !$smarty.foreach.file_typesF.last},{/if}
                                                {/foreach}
                                            </td>
                                        </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="name w130">{$lang.preview_image}:</td>
                                    <td class="field">
                                        <input class="file" type="file" name="preview" />
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <input class="button" type="submit" value="{$lang.upload}" />
                                    </td>
                                </tr>
                                </table>
                            </div>

                            <div id="youtube_video" class="upload{if $smarty.post.type != 'youtube'} hide{/if}">
                                <table class="form">
                                <tr>
                                    <td class="name w130">{$lang.embed}:</td>
                                    <td class="field">
                                        <textarea style="width: 500px; height: 80px;" cols="" rows="" name="youtube_embed">{$smarty.post.youtube_embed}</textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td>
                                        <input class="button" type="submit" value="{$lang.upload}" />
                                    </td>
                                </tr>
                                </table>
                            </div>
                        </div>
                    </form>
                    </div>
                </div>
            </fieldset>
            <!-- file uploader end -->

            <style type="text/css">
            @import url("{$smarty.const.RL_LIBS_URL}jquery/fancybox/jquery.fancybox.css");
            </style>

            <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}player/flowplayer.js"></script>
            <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.fancybox.js"></script>
            {if $config.gallery_slideshow}
                <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/fancybox/helpers/jquery.fancybox-buttons.js"></script>
            {/if}

            <!-- video list -->
            <fieldset style="margin: 10px 0;">
                <legend id="legend_video_area" class="up" onclick="fieldset_action('video_area');">{$lang.listing_video}</legend>
                <div id="video_area" style="padding: 0 0 4px 10px;">
                    {if empty($videos)}
                        <div class="grey_middle">{$lang.no_video_uploaded}</div>
                    {else}
                        <script>var videos_source = new Array();</script>
                        {assign var='replace' value=`$smarty.ldelim`key`$smarty.rdelim`}

                        <ul class="items" id="uploaded_video">
                        {foreach from=$videos item='video'}
                            <script>
                            videos_source.push({literal}{{/literal}
                                ID: '{$video.ID}',
                                Video: '{$video.Video}',
                                Preview: '{if $video.Type == "local"}{$video.Preview}{elseif $video.Type == "youtube"}{$l_youtube_direct|replace:$replace:$video.Preview}{/if}',
                                Type: '{$video.Type}',
                                Width: '{$config.video_width}',
                                Height: '{$config.video_height}'
                            {literal}}{/literal});
                            </script>

                            <li id="video_{$video.ID}">
                                {if $video.Type == 'local'}
                                    {assign var="preview_url" value=$smarty.const.RL_FILES_URL|cat:$video.Video}
                                    <video controls>
                                         <source src="{$preview_url}" type="video/mp4">
                                    </video>
                                {else}
                                    {assign var="preview_url" value=$l_youtube_thumbnail|replace:$replace:$video.Preview}
                                    <img class="preview_item" src="{$preview_url}" alt="" />
                                {/if}

                                <img title="{$lang.remove}" src="{$rlTplBase}img/blank.gif" id="remove_{$video.ID}" class="remove_item" alt="" />
                            </li>
                        {/foreach}
                        </ul>

                        <script>{literal}
                        $(document).ready(function(){
                            flynax.listingVideosHandler(videos_source, 'preview_item');
                        });
                        {/literal}</script>
                    {/if}
                </div>
            </fieldset>
            <!-- video list end -->

            {rlHook name='apTplListingsVideo'}

            <script type="text/javascript">//<![CDATA[
            var video_listing_id = {$listing.ID};
            var sort_save = false;
            {literal}

            $('div#video_area ul.items').sortable({
                placeholder: 'hover',
                stop: function(event, obj){
                    /* save sorting */
                    var sort = '';
                    var count = 0;
                    $('div#video_area ul.items li').each(function(){
                        var id = $(this).attr('id').split('_')[1];
                        count++;
                        var pos = $('div#video_area ul.items li').index($(this))+1;
                        sort += id+','+pos+';';
                    });

                    if ( sort.length > 0 && count > 1 && sort_save != sort )
                    {
                        sort_save = sort;
                        sort = rtrim(sort, ';');
                        xajax_reorderVideo(video_listing_id, sort);
                    }
                }
            });

            $(document).ready(function(){
                $('#type_selector').change(function(){
                    var id = $(this).val().split('_')[0];
                    $('div.upload').slideUp();
                    $('div#'+id+'_video').slideDown('slow');
                });

                $('img.remove_item').click(function(){
                    rlConfirm("{/literal}{$lang.delete_confirm}{literal}", 'xajax_deleteVideo', $(this).attr('id').split('_')[1]);
                });
            });

            {/literal}
            //]]>
            </script>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        {/if}

        <!-- add listing video end -->

    {elseif $smarty.get.action == 'view'}
        <style type="text/css">
        @import url("{$smarty.const.RL_LIBS_URL}jquery/fancybox/jquery.fancybox.css");
        </style>

        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}player/flowplayer.js"></script>
        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.fancybox.js"></script>

        <ul class="tabs">
            {foreach from=$tabs item='tab' name='tabsF'}
            <li lang="{$tab.key}" {if $smarty.foreach.tabsF.first}class="active"{/if}>{$tab.name}</li>
            {/foreach}
        </ul>

        <div class="tab_area listing listing_details">
            <table class="sTable">
            <tr>
                <td class="sidebar">
                    {if $photos}
                        <ul class="media">
                        {foreach from=$photos item='photo' name='photosF'}
                            <li {if $smarty.foreach.photosF.iteration%2 != 0}class="nl"{/if}>
                                <a title="{$photo.Description}" rel="group" href="{$smarty.const.RL_FILES_URL}{$photo.Photo}">
                                    <img class="shadow" src="{$smarty.const.RL_FILES_URL}{if $photo.Thumbnail_x2}{$photo.Thumbnail_x2}{else}{$photo.Thumbnail}{/if}" />
                                </a>
                            </li>
                        {/foreach}
                        </ul>
                    {/if}

                    <ul class="statistics">
                        {rlHook name='apTplListingBeforeStats'}

                        <li><span class="name">{$lang.category}:</span> <a href="{$rlBase}index.php?controller=browse&amp;id={$listing_data.Category_ID}" target="_blank">{$listing_data.category_name}</a></li>
                        {if $config.count_listing_visits}<li><span class="name">{$lang.shows}:</span> {$listing_data.Shows}</li>{/if}
                        {if $config.display_posted_date}<li><span class="name">{$lang.posted}:</span> {$listing_data.Date|date_format:$smarty.const.RL_DATE_FORMAT}</li>{/if}

                        {rlHook name='apTplListingAfterStats'}
                    </ul>
                </td>
                <td valign="top">
                    <!-- listing info -->
                    {rlHook name='apListingDetailsPreFields'}

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
                                <fieldset class="light">
                                    <legend id="legend_group_{$group.ID}" class="up" onclick="fieldset_action('group_{$group.ID}');">{$group.name}</legend>
                                    <div id="group_{$group.ID}" class="tree">

                                        <table class="list">
                                        {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                            {if !empty($item.value) && $item.Details_page}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                            {/if}
                                        {/foreach}
                                        </table>

                                    </div>
                                </fieldset>
                            {/if}
                        {else}
                            {if $group.Fields}
                                <table class="list">
                                {foreach from=$group.Fields item='item' }
                                    {if !empty($item.value) && $item.Details_page}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                    {/if}
                                {/foreach}
                                </table>
                            {/if}
                        {/if}
                    {/foreach}

                    {if $config.map_module && $location.direct}
                        <fieldset class="light">
                            <legend id="legend_group_map_system" class="up" onclick="fieldset_action('group_map_system');">{$lang.map}</legend>
                            <div id="group_map_system" class="tree">
                                <div id="map_container" style="height: 30vw;"></div>
                            </div>
                        </fieldset>

                        {mapsAPI}

                        <script>
                        {literal}

                        $(function(){
                            flMap.init($('#map_container'), {
                                zoom: {/literal}{$config.map_default_zoom}{literal},
                                addresses: [{
                                    latLng: '{/literal}{$location.direct}',
                                    content: '{$location.show|escape:'quotes'}{literal}'
                                }]
                            });
                        });

                        {/literal}
                        </script>
                    {/if}
                    <!-- listing info end -->
                </td>
            </tr>
            </table>

            <script type="text/javascript">
            {literal}

            $(document).ready(function(){
                $('ul.media a').fancybox({
                    titlePosition: 'over',
                    centerOnScroll: true,
                    scrolling: 'yes'
                });
            });

            {/literal}
            </script>
        </div>

        <div class="tab_area seller listing_details hide">
            <table class="sTatic">
            <tr>
                <td valign="top" style="width: 170px;text-align: right;padding-right: 20px;">
                    <a title="{$lang.visit_owner_page}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$seller_info.ID}">
                        <img style="display: inline;width: auto;" {if !empty($seller_info.Photo)}class="thumbnail"{/if} alt="{$lang.seller_thumbnail}" src="{if !empty($seller_info.Photo)}{$smarty.const.RL_FILES_URL}{$seller_info.Photo}{else}{$rlTplBase}img/no-account.png{/if}" />
                    </a>

                    <ul class="info">
                        {if $config.messages_module}<li><input id="contact_owner" type="button" value="{$lang.contact_owner}" /></li>{/if}
                        {if $seller_info.Own_page}
                            <li><a target="_blank" title="{$lang.visit_owner_page}" href="{$seller_info.Personal_address}">{$lang.visit_owner_page}</a></li>
                            <li><a title="{$lang.other_owner_listings}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$seller_info.ID}#listings">{$lang.other_owner_listings}</a> <span class="counter">({$seller_info.Listings_count})</span></li>
                        {/if}
                    </ul>
                </td>
                <td valign="top">
                    <div class="username">{$seller_info.Full_name}</div>
                    {if $seller_info.Fields}
                        <table class="list" style="margin-bottom: 20px;">
                            <tr id="si_field_username">
                                <td class="name">{$lang.username}:</td>
                                <td class="value first">{$seller_info.Username}</td>
                            </tr>
                            <tr id="si_field_date">
                                <td class="name">{$lang.join_date}:</td>
                                <td class="value">{$seller_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                            </tr>
                            <tr id="si_field_email">
                                <td class="name">{$lang.mail}:</td>
                                <td class="value"><a href="mailto:{$seller_info.Mail}">{$seller_info.Mail}</a></td>
                            </tr>

                            {if $seller_info.Personal_address}
                                <tr id="si_field_personal_address">
                                    <td class="name">{$lang.personal_address}:</td>
                                    <td class="value">
                                        <a target="_blank" href="{$seller_info.Personal_address}">
                                            {$seller_info.Personal_address}
                                        </a>
                                    </td>
                                </tr>
                            {/if}

                            {if $seller_info.Agency_Info}
                                <tr id="si_field_agency">
                                    <td class="name">{$lang.agency}:</td>
                                    <td class="value">
                                        <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$seller_info.Agency_Info.ID}">
                                            {$seller_info.Agency_Info.Full_name}
                                        </a>
                                    </td>
                                </tr>
                            {/if}

                            {rlHook name='apTplListingsUserInfo'}
                        </table>

                        <table class="list">
                        {foreach from=$seller_info.Fields item='item' name='sellerF'}
                            {if !empty($item.value)}
                            <tr id="si_field_{$item.Key}">
                                <td class="name">{$item.name}:</td>
                                <td class="value">{$item.value}</td>
                            </tr>
                            {/if}
                        {/foreach}
                        </table>
                    {/if}
                </td>
            </tr>
            </table>

            <script type="text/javascript">
            var owner_id = {if $seller_info.ID}{$seller_info.ID}{else}false{/if}
            {literal}

            $(document).ready(function(){
                $('#contact_owner').click(function(){
                    rlPrompt('{/literal}{$lang.contact_owner}{literal}', 'xajax_contactOwner', owner_id, true);
                });
            });

            {/literal}
            </script>
        </div>

        {if $videos}
            <div class="tab_area video listing_details hide">
                <script>var videos_source = new Array();</script>
                {assign var='replace' value=`$smarty.ldelim`key`$smarty.rdelim`}

                <ul class="media media-video">
                {foreach from=$videos item='video'}
                    <script>
                    videos_source.push({literal}{{/literal}
                        ID: '{$video.ID}',
                        Video: '{$video.Original}',
                        Preview: '{if $video.Original == "youtube"}{$l_youtube_direct|replace:$replace:$video.Photo}{else}{$video.Thumbnail}{/if}',
                        Type: '{if $video.Original == 'youtube'}youtube{else}local{/if}',
                        Width: '{$config.video_width}',
                        Height: '{$config.video_height}'
                    {literal}}{/literal});
                    </script>

                    <li id="video_{$video.ID}">
                        {if $video.Type == 'local'}
                            {assign var="preview_url" value=$smarty.const.RL_FILES_URL|cat:$video.Video}
                            <video controls>
                                 <source src="{$preview_url}" type="video/mp4">
                            </video>
                        {else}
                            <iframe width="100%" height="100%" src="https://www.youtube.com/embed/{$video.Preview}" frameborder="0" allow="accelerometer;encrypted-media; gyroscope;" allowfullscreen></iframe>
                        {/if}
                    </li>
                {/foreach}
                </ul>

                {*<script>{literal}
                $(document).ready(function(){
                    flynax.listingVideosHandler(videos_source);
                });
                {/literal}</script>*}
            </div>
        {/if}

        {rlHook name='apTplListingsTabsArea'}
    {else}
        {if !$config.cron_last_run}
        <script type="text/javascript">
            printMessage('alert', '{$lang.cron_not_configured|escape:quotes}');
        </script>
        {/if}

        <script type="text/javascript">//<![CDATA[
        // collect plans
        var listing_plans = [
            {foreach from=$plans item='plan' name='plans_f'}
                ['{$plan.ID}', '{$plan.name}']{if !$smarty.foreach.plans_f.last},{/if}
            {/foreach}
        ];

        var ui = typeof( rl_ui ) != 'undefined' ? '&ui='+rl_ui : '';
        var ui_cat_id = typeof( cur_cat_id ) != 'undefined' ? '&cat_id='+cur_cat_id : '';

        /* read cookies filters */
        var cookies_filters = false;

        if ( readCookie('listings_sc') )
            cookies_filters = readCookie('listings_sc').split(',');

        {if isset($status)}
            cookies_filters = new Array();
            cookies_filters[0] = new Array('Status', '{$status}');
        {/if}

        {if $smarty.get.username}
            cookies_filters = new Array();
            cookies_filters[0] = new Array('Account', '{$smarty.get.username}');
        {/if}

        {if $smarty.get.account_type}
            cookies_filters = new Array();
            cookies_filters[0] = new Array('account_type', '{$smarty.get.account_type}');
        {/if}

        {if $smarty.get.listing_type}
            cookies_filters = new Array();
            cookies_filters[0] = new Array('Type', '{$smarty.get.listing_type}');
        {/if}

        {if $smarty.get.plan_id}
            cookies_filters = new Array();
            cookies_filters[0] = new Array('Plan_ID', '{$smarty.get.plan_id}');
        {/if}

        {rlHook name='apTplListingsRemoteFilter'}

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
                ajaxUrl: rlUrlHome + 'controllers/listings.inc.php?q=ext',
                defaultSortField: 'Date',
                defaultSortType: 'DESC',
                remoteSortable: false,
                checkbox: true,
                actions: mass_actions,
                filters: cookies_filters,
                filtersPrefix: true,
                title: lang['ext_listings_manager'],
                expander: true,
                expanderTpl: '<div style="margin: 0 5px 5px 83px"> \
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
                    {name: 'Plan_type', mapping: 'Plan_type'},
                    {name: 'Featured_ID', mapping: 'Featured_ID', type: 'int'},
                    {name: 'Plan_info', mapping: 'Plan_info'},
                    {name: 'Cat_ID', mapping: 'Cat_ID', type: 'int'},
                    {name: 'Cat_custom', mapping: 'Cat_custom', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Pay_date', mapping: 'Pay_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Expired_date', mapping: 'Expired_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'Featured_expired_date', mapping: 'Featured_expired_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                    {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                    {name: 'fields', mapping: 'fields', type: 'string'},
                    {name: 'data', mapping: 'data', type: 'string'},
                    {name: 'Status_value', mapping: 'Status_value'},
                    {name: 'Allow_photo', mapping: 'Allow_photo', type: 'int'},
                    {name: 'Allow_video', mapping: 'Allow_video', type: 'int'}
                ],
                columns: [
                    {
                        header: lang['ext_id'],
                        dataIndex: 'ID',
                        width: 50,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    },{
                        header: lang['ext_title'],
                        dataIndex: 'title',
                        width: 23,
                        renderer: function(val, ext, row){
                            var out = '<a href="'+rlUrlHome+'index.php?controller=listings&action=view&id='+row.data.ID+'">'+val+'</a>';
                            return out;
                        }
                    },{
                        header: lang['ext_owner'],
                        dataIndex: 'Username',
                        width: 120,
                        fixed: true,
                        id: 'rlExt_item_bold',
                        renderer: function(username, ext, row){
                            return "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</a>"
                        }
                    },{
                        header: `${lang.ext_type}/${lang.ext_category}`,
                        dataIndex: 'Type',
                        width: 14
                    },{
                        header: lang['ext_add_date'],
                        dataIndex: 'Date',
                        width: 10,
                        hidden: true,
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                    },{
                        header: lang['ext_payed'],
                        dataIndex: 'Pay_date',
                        width: 90,
                        fixed: true,
                        renderer: function(val){
                            if (!val) {
                                return '<span class="delete" ext:qtip="'+lang['ext_click_to_set_pay']+'">'+lang['ext_not_payed']+'</span>';
                            } else {
                                let date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                                return '<span class="build" ext:qtip="'+lang['ext_click_to_edit']+'">'+date+'</span>';
                            }
                        },
                        editor: new Ext.form.DateField({
                            format: 'Y-m-d H:i:s'
                        })
                    },{
                        header: '{/literal}{$lang.active_till}{literal}',
                        dataIndex: 'Expired_date',
                        width: 90,
                        fixed: true,
                        renderer: function(date, row, store) {
                            if (store.json.Pay_date === store.json.Expired_date) {
                                return '{/literal}{$lang.unlimited}{literal}';
                            } else {
                                return date
                                    ? '<span ext:qtip="' + lang.deny_change_ex_date + '">'
                                        + Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(date)
                                        + '</span>'
                                    : lang.ext_not_available;
                            }
                        }
                    },{
                        header: '{/literal}{$lang.featured_till}{literal}',
                        dataIndex: 'Featured_expired_date',
                        width: 90,
                        fixed: true,
                        renderer: function(date, row, store) {
                            if (store.json.Featured_date === store.json.Featured_expired_date) {
                                return '{/literal}{$lang.unlimited}{literal}';
                            } else {
                                return date
                                    ? '<span ext:qtip="' + lang.deny_change_ex_date + '">'
                                        + Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(date)
                                        + '</span>'
                                    : lang.ext_not_available;
                            }
                        }
                    },{
                        header: lang['ext_plan'],
                        dataIndex: 'Plan_ID',
                        width: 140,
                        fixed: true,
                        editor: new Ext.form.ComboBox({
                            store: listing_plans,
                            mode: 'local',
                            triggerAction: 'all'
                        }),
                        renderer: function (val, obj, row){
                            if (row.data.Plan_type == 'account') {
                                var qtip = lang['ext_uneditable'];
                            } else {
                                var qtip = lang['ext_click_to_edit'];
                            }
                            if (val != '') {
                                var f_class = row.data.Featured_ID > 0 ? ' featured' : '';
                                return '<img class="info'+f_class+'" ext:qtip="'+row.data.Plan_info+'" alt="" src="'+rlUrlHome+'img/blank.gif" />&nbsp;&nbsp;<span ext:qtip="'+qtip+'">'+val+'</span>';
                            } else {
                                return '<span class="delete" ext:qtip="'+qtip+'" style="margin-left: 21px;">'+lang['ext_no_plan_set']+'</span>';
                            }
                        }
                    },{
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 100,
                        fixed: true,
                        editor: rights[cKey].indexOf('edit') >= 0 ? new Ext.form.ComboBox({
                            store: [
                                ['active', lang.active],
                                ['approval', lang.approval]
                            ],
                            mode: 'local',
                            typeAhead: true,
                            triggerAction: 'all',
                            selectOnFocus: true,
                            listeners: {
                                beforeselect: function(combo, record){
                                    var index = combo.gridEditor.row;
                                    var row = listingsGrid.grid.store.data.items[index];

                                    if (record.data.field1 == 'active' && row.data.Plan_type == 'account' && row.data.Status_value == 'expired') {
                                        Ext.MessageBox.confirm(lang['warning'], '{/literal}{$lang.confirm_change_listing_status}{literal}', function(btn) {
                                            if (btn == 'yes') {
                                                $.getJSON(rlConfig['ajax_url'], {item: 'changeListingStatus', id: row.data.ID, value: record.data.field1, membership_upgarde: true}, function(response) {
                                                    if (response) {
                                                        if (response.status == 'ok') {
                                                            listingsGrid.reload();
                                                        } else {
                                                            printMessage('error', lang['ext_error_saving_changes']);
                                                        }
                                                    }
                                                });
                                            }
                                        });

                                        return false;
                                    }
                                }
                            }
                        }) : false
                    },{
                        header: lang['ext_actions'],
                        width: 120,
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
                            out += "<a href='"+rlUrlHome+"index.php?controller=listings&action=view&id="+id+"'><img class='view' ext:qtip='"+lang['ext_view_details']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
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

            {/literal}{rlHook name='apTplListingsGrid'}{literal}

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

                listingsGrid.ids = '';

                for( var i = 0; i < sel_obj.length; i++ )
                {
                    listingsGrid.ids += sel_obj[i].id;
                    if ( sel_obj.length != i+1 )
                    {
                        listingsGrid.ids += '|';
                    }
                }

                switch (action){
                    case 'delete':
                        Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn){
                            if ( btn == 'yes' )
                            {
                                xajax_massActions( listingsGrid.ids, action );
                                listingsGrid.store.reload();
                            }
                        });

                        break;

                    case 'featured':
                        $('#make_featured').fadeIn('slow');
                        return false;

                        break;

                    case 'annul_featured':
                        $('#mass_areas div.scroll').fadeOut('fast');
                        Ext.MessageBox.confirm('Confirm', lang['ext_annul_featued_notice'], function(btn){
                            if ( btn == 'yes' )
                            {
                                xajax_annulFeatured( listingsGrid.ids );
                            }
                        });
                        return false;

                        break;

                    case 'move':
                        $('#mass_areas div.scroll').fadeOut('fast');
                        $('#move_area').fadeIn('slow');
                        return false;

                        break;

                    default:
                        $('#make_featured,#move_area').fadeOut('fast');
                        xajax_massActions( listingsGrid.ids, action );
                        listingsGrid.store.reload();

                        break;
                }

                listingsGrid.checkboxColumn.clearSelections();
                listingsGrid.actionsDropDown.setVisible(false);
                listingsGrid.actionButton.setVisible(false);
            });

            listingsGrid.grid.addListener('beforeedit', function(editEvent) {
                if (editEvent.field == 'Plan_ID' && editEvent.record.data.Plan_type == 'account') {
                    return false;
                }
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

        {rlHook name='apTplListingsMiddle'}

        <div id="mass_areas">

            <!-- make featured -->
            <div id="make_featured" style="margin-top: 10px;" class="hide scroll">

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.make_featured}
                <table class="form">
                <tr>
                    <td class="name w130"><span class="red">*</span>{$lang.plan}</td>
                    <td class="field">
                        <select id="featured_plan">
                            <option value="0">{$lang.select}</option>
                            {foreach from=$featured_plans item='featured_plan'}
                                <option value="{$featured_plan.ID}">{$featured_plan.name}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="w130"></td>
                    <td class="field">
                        <input type="button" onclick="xajax_makeFeatured(listingsGrid.ids, $('#featured_plan').val());" value="{$lang.save}" />
                        <a class="cancel" href="javascript:void(0)" onclick="$('#make_featured').fadeOut();">{$lang.cancel}</a>
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
                var move_clicked = false;
                {literal}
                function moveOnCategorySelect(id, name) {
                    move_category_id = id;
                }

                function moveOnButtonClick() {
                    if (listingsGrid.ids.length > 0 && move_category_id > 0) {
                        if(!move_clicked) {
                            $('div.namespace-move a.button').text(lang['loading']);
                            xajax_moveListing(listingsGrid.ids, move_category_id);
                            move_clicked = true;
                        }
                    }
                }
                {/literal}
                </script>

                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.move_listings}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_selector.tpl' namespace='move' button=$lang.move}
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
            </div>
            <!-- move listing block end -->

        </div>

        {rlHook name='apTplListingsBottom'}

    {/if}

    {if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}
        <script type="text/javascript">
            {literal}
                $(document).ready(function(){
                     flynax.onMapHandler();
                });
            {/literal}
        </script>
    {/if}
{/if}

<!-- listings tpl end -->
