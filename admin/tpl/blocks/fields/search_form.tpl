<!-- fields search form -->

{if !isset($smarty.get.action)}
    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
        
        <form method="post" onsubmit="return false;" id="search_form" action="">
        <table class="form">
        <tr>
            <td class="name">{$lang.name}</td>
            <td class="field"><input type="text" id="search_name" /></td>
        </tr>
        <tr>
            <td class="name">{$lang.field_type}</td>
            <td class="field">
                <select id="search_type" style="width: 200px;">
                <option value="">- {$lang.all} -</option>
                {foreach from=$l_types item='item' key='key'}
                    <option value="{$key}">{$item}</option>
                {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.required_field}</td>
            <td class="field" id="search_require_td">
                <label title="{$lang.unmark}"><input title="{$lang.unmark}" type="radio" id="required_uncheck" value="" /> ...</label>
                <label><input type="radio" name="search_required" id="required_yes" value="yes" /> {$lang.yes}</label>
                <label><input type="radio" name="search_required" id="required_no" value="no" /> {$lang.no}</label>
                
                <script type="text/javascript">
                {literal}
                $('#required_uncheck').click(function(){
                    $('#search_require_td input').attr('checked', false);
                });
                {/literal}
                </script>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.google_map}</td>
            <td class="field" id="search_gmap_td">
                <label title="{$lang.unmark}"><input title="{$lang.unmark}" type="radio" id="gmap_uncheck" value="" /> ...</label>
                <label><input type="radio" name="search_gmap" id="gmap_yes" value="yes" /> {$lang.yes}</label>
                <label><input type="radio" name="search_gmap" id="gmap_no" value="no" /> {$lang.no}</label>
                
                <script type="text/javascript">
                {literal}
                $('#gmap_uncheck').click(function(){
                    $('#search_gmap_td input').attr('checked', false);
                });
                {/literal}
                </script>
            </td>
        </tr>
        
        {rlHook name='apTplFieldsSearchField'}
        
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
                <input type="submit" class="button lang_add" value="{$lang.search}" id="search_button" />
                <input type="button" class="button" value="{$lang.reset}" id="reset_search_button" />
        
                <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
            </td>
        </tr>
        </table>
        </form>
        
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    
    <script type="text/javascript">
    var grid_prefix = '{$grid_key}';
    {literal}
    
    var search = new Array();
    var cookie_filters = '';

    $(document).ready(function(){
        
        if ( readCookie('listing_fields_sc') )
        {
            $('#search').show();
            cookie_filters = readCookie('listing_fields_sc').split(',');

            for (var i in cookie_filters)
            {
                if ( typeof(cookie_filters[i]) == 'string' )
                {
                    var item = cookie_filters[i].split('||');
                    if ( item[0] != 'undefined' && item[0] != '' )
                    {
                        if ( item[0] == 'Required' )
                        {
                            $('#search input').each(function(){
                                var val = item[1] == 1 ? 'yes' : 'no';
                                if ( $(this).attr('name') == 'search_required' && $(this).val() == val )
                                {
                                    $(this).attr('checked', true);
                                }
                            });
                        }
                        else if ( item[0] == 'Map' )
                        {
                            $('#search input').each(function(){
                                var val = item[1] == 1 ? 'yes' : 'no';
                                if ( $(this).attr('name') == 'search_gmap' && $(this).val() == val )
                                {
                                    $(this).attr('checked', true);
                                }
                            });
                        }
                        else
                        {
                            $('#search_'+item[0].toLowerCase()).selectOptions(item[1]);
                        }
                    }
                }
            }
        }
        
        $('#search_form').submit(function(){
            createCookie('listing_fields_pn', 0, 1);
            
            search = new Array();
            search.push( new Array('Name', $('#search_name').val()) );
            search.push( new Array('Type', $('#search_type').val()) );
            
            var required = $('input[name=search_required]:checked').val();
            search.push( new Array('Required', required == 'yes' ? 1 : required == 'no' ? 0 : '') );
            
            var map = $('input[name=search_gmap]:checked').val();
            search.push( new Array('Map', map == 'yes' ? 1 : map == 'no' ? 0 : '') );
            search.push( new Array('Status', $('#search_status').val()) );
            
            {/literal}{rlHook name='apTplFieldsSearchJS'}{literal}
            
            search.push( new Array('action', 'search') );
            
            // save search criteria
            var save_search = new Array();
            for(var i in search)
            {
                if ( search[i][1] != '' && search[i][1] != undefined )
                {
                    save_search.push(search[i][0]+'||'+search[i][1]);
                }
            }
            
            createCookie('listing_fields_sc', save_search, 1);
            
            eval(grid_prefix+'.filters = search;');
            eval(grid_prefix+'.reload();');
        });
        
        $('#reset_search_button').click(function(){
            eraseCookie('listing_fields_sc');
            eval(grid_prefix+'.reset();');
            
            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
            $("#search input").each(function(){
                if ( $(this).attr('type') == 'radio' )
                {
                    $(this).attr('checked', false);
                }
            });
        });
        
    });
    
    {/literal}
    </script>
{/if}

<!-- fields search form end -->
