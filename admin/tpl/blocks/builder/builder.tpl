<!-- category builder start -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<table class="sTable">
<tr>
    <td style="width: 230px;" valign="top">
        <div class="build_bar">
            <a id="save_build_form" href="javascript:void(0)" class="button_bar"><span class="left"></span><span class="center">{$lang.save}</span><span class="right"></span></a>
        </div>
    </td>
    {if !$no_groups}
    <td style="width: 230px;"></td>
    {/if}
    <td valign="top">
        <div class="build_bar">
            <table cellpadding="0" cellspacing="0">
            <tr>
                <td valign="top">
                    <input type="text" class="float" id="fields_search" />
                </td>
                <td valign="top">
                    <div style="margin-left: 5px;">
                    <select class="float" id="type_search" style="width: 70px;">
                        <option value="0">{$lang.any}</option>
                        {foreach from=$l_types item='f_type' key='f_key'}
                        <option value="{$f_type}">{$f_type}</option>
                        {/foreach}
                    </select>
                    </div>
                </td>
                <td>
                    <a class="cancel" href="javascript:void(0)" id="reset_search">{$lang.reset}</a>
                </td>
            </tr>
            </table>
        </div>
        
        <script type="text/javascript">
        {literal}
        
        $(document).ready(function(){
            /* reset button handler */
            $('#reset_search').click(function(){
                $('#fields_search').val('');
                $('#type_search option[value=0]').attr('selected', true);
                $('#fields_container div.field_obj').show().removeClass('search');
                search = '';
                type = 0;
            });
            
            /* search handler */
            $('#fields_search').keyup(function(){
                search = $(this).val();
                
                fields_search(false);
            });
            
            /* type selector */
            $('#type_search').change(function(){
                type = $('#type_search').val();
                
                fields_search(true);
            }).keyup(function(){
                type = $('#type_search').val();
                
                fields_search(true);
            });
        });
        
        var search = '';
        var type = 0;
        
        var fields_search = function( allow ){
            var pattern = new RegExp(search, 'gi');
            var hide = false;
            
            $('#fields_container div.field_obj').show().removeClass('search');
            
            if ( (search.length > 2 || search.length == 0) || allow )
            {
                $('#fields_container div.title').each(function(){
                    if ( pattern.test($(this).html()) && ( type == 0 || $(this).next().children('span').html() == type ) )
                    {
                        $(this).parent().parent().addClass('search');
                        hide = true;
                    }
                });
                
                if ( hide )
                {
                    $('#fields_container div.field_obj').hide();
                    $('#fields_container div.search').show();
                    hide = false;
                }
            }
        }
        
        {/literal}
        </script>
    </td>
</tr>
<tr>
    <td valign="top">
        <fieldset class="light" style="margin: 0 10px 0 0;">
        <legend id="legend_form_section" class="up" onclick="fieldset_action('form_section');">{$lang.form}</legend>
        <div id="form_section">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'form.tpl'}
        </div>
        </fieldset>
    </td>
    {if !$no_groups}
    <td valign="top">
        <fieldset class="light" style="margin: 0 10px 0 0;">
        <legend id="legend_group_section" class="up" onclick="fieldset_action('group_section');">{$lang.groups_list}</legend>
        <div id="group_section">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'groups.tpl'}
        </div>
        <div id="group_section_tmp"></div>
        </fieldset>
    </td>
    {/if}
    <td valign="top">
        <fieldset class="light" style="margin: 0 10px 0 0;">
        <legend id="legend_fields_section" class="up" onclick="fieldset_action('fields_section');">{$lang.fields_list}</legend>
        <div id="fields_section">
            <div id="fields_container">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'builder'|cat:$smarty.const.RL_DS|cat:'fields.tpl'}
                <div class="clear"></div>
            </div>
            <div id="fields_container_tmp"></div>
        </div>
        </fieldset>
    </td>
</tr>
</table>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
<!-- Listing builder start end -->

<script type="text/javascript">
var build_group_in_group_alert = "{$lang.build_group_in_group_alert}";
//<![CDATA[
{literal}

$(document).ready(function(){
    $('#form_section').sortable({
        placeholder: 'ui-field-highlight',
        connectWith: '#group_section, #fields_container, .group_fields_container',
        handle: '.group_title, .field_title',
        cursor: 'move',
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.5,
        stop: function(event, ui){
            var obj = $(ui.item).attr('class');
            
            /* tmp way */
            if ( obj.indexOf('group_obj') >= 0 && $(ui.item).parent().hasClass('group_fields_container') )
            {
                /* back fields/group to own places */
                $(ui.item).find('div.group_fields_container').hide();
                $(ui.item).addClass('tmp').hide();
                
                if ( $(ui.item).find('.group_fields_container div.field_obj').length > 0 )
                {
                    $(ui.item).find('.group_fields_container div.field_obj').addClass('tmp').hide();
                    var fields = $(ui.item).find('.group_fields_container').html();
                    $(ui.item).find('.group_fields_container').html('');
                    $('#fields_container').prepend(fields);
                    $('#fields_container div.tmp').fadeIn('slow').removeClass('tmp');
                }
                
                $('#group_section').prepend($(ui.item));
                $('#group_section div.tmp').fadeIn('slow').removeClass('tmp');
                
                printMessage('alert', build_group_in_group_alert);
            }
            
            readBuilForm();
        },
        start: function(event, ui){
            clearTimeout(build_timer);
        },
        over: function(event, ui){
            $(this).find('div.group_hint, div.form_hint').remove();
        }
    });

    $('#group_section').sortable({
        placeholder: 'ui-field-highlight',
        connectWith: '#form_section',
        handle: '.group_title',
        cursor: 'move',
        /*cancel: '.group_hint',*/
        forcePlaceholderSize: true,
        opacity: 0.5,
        stop: function(event, ui){
            var id = $(ui.item).parent().attr('id');
            
            if ( id != 'group_section' )
            {
                $(ui.item).find('.group_fields_container').show();
            }
            
            readBuilForm();
        },
        start: function(event, ui){
            clearTimeout(build_timer);
        },
        receive: function(event, ui){
            /* back fields to field section */
            $(ui.item).find('div.group_fields_container').hide();
            
            if ( $(ui.item).find('.group_fields_container div.field_obj').length > 0 )
            {
                $(ui.item).find('.group_fields_container div.field_obj').addClass('tmp').hide();
                var fields = $(ui.item).find('.group_fields_container').html();
                $(ui.item).find('.group_fields_container').html('');
                $('#fields_container').prepend(fields);
                $('#fields_container div.tmp').fadeIn('slow');
            }
            
            /* back fields to own section */
            var obj = $(ui.item).attr('class');
            
            if ( obj.indexOf('field_obj') >= 0 )
            {
                $(ui.item).hide().addClass('tmp');
                $('#fields_container').prepend($(ui.item));
                $('#fields_container').find('div.tmp').fadeIn('slow').removeClass('tmp');
            }
        }
    }).disableSelection();

    $('.group_fields_container').sortable({
        placeholder: 'ui-field-highlight',
        connectWith: '#form_section, #fields_container, .group_fields_container',
        cursor: 'move',
        forcePlaceholderSize: true,
        helper: 'clone',
        cancel: '.group_hint',
        opacity: 0.5,
        start: function(){
            clearTimeout(build_timer);
        },
        stop: function(event, ui){
            readBuilForm();
        }
    }).disableSelection();
    
    $('#fields_container').sortable({
        placeholder: 'ui-field-highlight',
        connectWith: '#form_section, .group_fields_container',
        cursor: 'move',
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.5,
        start: function(){
            clearTimeout(build_timer);
        },
        stop: function(event, ui){
            readBuilForm();
        },
        receive: function(event, ui){
            var obj = $(ui.item).attr('class');
            
            if ( obj.indexOf('field_obj') < 0 )
            {
                /* back fields/group to own places */
                $(ui.item).find('div.group_fields_container').hide();
                $(ui.item).addClass('tmp').hide();
                
                if ( $(ui.item).find('.group_fields_container div.field_obj').length > 0 )
                {
                    $(ui.item).find('.group_fields_container div.field_obj').addClass('tmp').hide();
                    var fields = $(ui.item).find('.group_fields_container').html();
                    $(ui.item).find('.group_fields_container').html('');
                    $('#fields_container').prepend(fields);
                    $('#fields_container div.tmp').fadeIn('slow').removeClass('tmp');
                }
                
                $('#group_section').prepend($(ui.item));
                $('#group_section div.tmp').fadeIn('slow').removeClass('tmp');
            }
        }
    }).disableSelection();
    
    /* bind save button click */
    $('#save_build_form').click(function(){
        if ( !build_in_progress )
        {
            clearTimeout(build_timer);
            
            $('#save_build_form').addClass('bb_hover').find('span.center').html(lang['loading']);
            build_in_progress = true;
            xajax_buildForm(build_category_id, build_form, build_no_groups);
        }
    });
    
    /* build form to further compare */
    build_form = readBuilForm('read');
    
    /* expand/collapse groups */
    $('div.group_title').dblclick(function(){
        if ( $(this).next().is(':visible') )
        {
            $(this).next().fadeOut();
            $(this).addClass('collapsed');
        }
        else
        {
            $(this).next().fadeIn();
            $(this).removeClass('collapsed');
        }
    });
});

var build_form = new Array();
var build_timer = false;
var build_in_progress = false;
var build_no_groups = {/literal}{if $no_groups}1{else}0{/if}{literal};
var build_category_id = {/literal}{if $category_info.ID}{$category_info.ID}{elseif $form_info.ID}{$form_info.ID}{else}0{/if}{literal};

var readBuilForm = function( mode ){
    if ( build_in_progress )
    {
        return;
    }
    
    clearTimeout(build_timer);
    
    var tmp_build_form = new Array();
    var groups = $('#form_section').sortable('toArray');
    var ordering = '';
            
    if ( groups )
    {
        for ( var i in groups )
        {
            if ( typeof(groups[i]) != 'function' )
            {
                ordering += groups[i]+',';
                
                if ( groups[i].indexOf('group') == 0 )
                {
                    var fields = $('#form_section div#'+groups[i]+' div.group_fields_container').sortable('toArray');

                    tmp_build_form[groups[i]] = Array();
                    tmp_build_form[groups[i]] = fields;
                }
                else
                {
                    tmp_build_form[groups[i]] = true;
                }
            }
        }
        
        tmp_build_form['ordering'] = ordering;
    }
    
    if ( mode == 'read' )
    {
        return tmp_build_form;
    }
    
    if ( rlIsDiff(build_form, tmp_build_form) )
    {
        build_timer = setTimeout(function(){
            $('#save_build_form').addClass('bb_hover').find('span.center').html(lang['loading']);
            
            if ( !build_in_progress )
            {
                build_in_progress = true;
                xajax_buildForm(build_category_id, tmp_build_form, build_no_groups);
            }
            
        }, 3000);
        
        build_form = tmp_build_form;
    }
}

{/literal}
//]]>
</script>

<!-- category builder end -->
