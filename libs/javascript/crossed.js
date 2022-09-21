
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: CROSSED.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2022 | All copyrights reserved.
 *  
 *  https://www.flynax.com/
 ******************************************************************************/

crossCount = plans[selected_plan_id] ? plans[selected_plan_id]['Cross'] : false;
crossTotal = 0;
xajaxFix = false;

var crossedTree = function(clear, first){
    this.inputs = $('#crossed_tree input[name="crossed_categories[]"]');
    this.printInfo = function(){
        $('#cc_number').html(crossCount);
        if( crossTotal > 0)
        {
            $('#crossed_selected').fadeIn();
        }
        else
        {
            $('#crossed_selected').fadeOut();
        }
        
        if ( crossCount == 0 )
        {
            $('#cc_text').hide();
            $('#cc_text_denied').show();
            
            $('#crossed_tree input:not(.disabled)').attr('disabled', true);
        }
        else if ( crossCount == 1 )
        {
            $('#cc_text_denied').hide();
            $('#cc_text').show();
            
            $('#crossed_tree input:not(.disabled,.system)').attr('disabled', false);
        }
    };
    this.remove = function(){
        $('li.tmp_crossed_select img.remove').unbind('click').click(function(){
            var id = $(this).parent().attr('id').replace('tmp_', '');

            $('li#'+id+' input[type=checkbox]:first').attr('checked', false);
            $(this).parent().remove();
            crossCount++;
            crossTotal--;
            base.printInfo();
        });
    };
    
    /* reset environment */
    if ( clear )
    {
        $('#crossed_tree input[name="crossed_categories[]"]').attr({checked: false});
        $('#crossed_tree input[name="crossed_categories[]"]:not(.system)').attr({disabled: false,class: false});
    }
    
    /* unbind events */
    $(inputs).unbind('click');
    
    /* call default methods */
    this.printInfo();
    
    /* define referent to class self */
    var base = this;
    
    /* open sub-level checkboxes handler */
    if ( crossCount == 0 )
    {
        $('#cc_text').hide();
        $('#cc_text_denied').show();
        
        $('#crossed_tree input:not(.disabled)').attr('disabled', true);
    }
    
    /* click handler */
    $(inputs).click(function(){
        var id = $(this).closest('li').attr('id');
        
        if ( $(this).is(':checked') )
        {
            crossCount--;
            crossTotal++;
            $(this).addClass('disabled');
            
            var html = '<li class="tmp_crossed_select" id="tmp_'+id+'"><a href="'+$(this).next().next().attr('href')+'" target="_blank">'+$(this).next().html()+'</a> <img src="'+rlConfig['tpl_base']+'img/blank.gif" class="remove" alt="" title="'+ lang['delete'] +'" /></li>';
            $('#crossed_selected').append(html);
            base.remove();
        }
        else
        {
            crossCount++;
            crossTotal--;
            $(this).removeClass('disabled');
            
            $('#crossed_selected li#tmp_'+id).remove();
        }
        
        base.printInfo();
    });
    
    /* post handler */
    if ( ca_post.length > 0 )
    {
        if ( cc_parentPoints.length > 0 )
        {
            for ( var i=0; i<cc_parentPoints.length; i++ )
            {
                $('#tree_cat_'+cc_parentPoints[0]+'>img:first').trigger('click');
            }
            cc_parentPoints.splice(0, 1);
        }
        else
        {
            for ( var i=0; i<ca_post.length; i++ )
            {
                $('#tree_cat_'+ca_post[i]+' input:first').trigger('click');
                if ( !first )
                {
                    $('#tree_cat_'+ca_post[i]+' input:first').attr('checked', true);
                }
            }
            ca_post = false;
        }
    }
}

$(document).ready(function(){
    //crossedTree(true, true);
    
    $('#crossed_button').click(function(){
        var value = parseInt($('input[name=crossed_done]').val());

        if ( !value )
        {
            $('input[name=crossed_done]').val(1);
            $('#crossed_tree').slideUp();
            $(this).val(lang['manage']);
        }
        else
        {
            $('input[name=crossed_done]').val(0);
            $('#crossed_tree').slideDown();
            $(this).val(lang['done']);
            $('#crossed_tree span.tree_loader').hide();
        }
    });
});
