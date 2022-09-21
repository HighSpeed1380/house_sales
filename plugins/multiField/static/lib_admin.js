
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: LIB_ADMIN.JS
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

var MFImportClass = function(){
    var self = this;
    var item_width = width = percent = percent_value = sub_width = sub_item_width = sub_percent = sub_percent_value = sub_percent_to_show = percent_to_show = 0;
    var window = false;
    var request;

    this.config = new Array();
        
    this.import = function(index){
        /* show window */
        if ( index == 0 )
        {
            if ( !window )
            {
                window = new Ext.Window({
                    applyTo: 'statistic',
                    layout: 'fit',
                    width: 447,
                    height: 160,
                    closeAction: 'hide',
                    plain: true
                });
                
                window.addListener('hide', function(){
                    self.stop();
                });
            }
            window.show();
        }
        
        /* import request */
        request = $.getJSON("../plugins/multiField/admin/import.php", {index: index}, function(response){
            if( response['finish'] )
            {
                itemsGrid.reload();
                setTimeout(function(){
                    location.reload();
                }, 2000);
            }
            else
            {
                if ( response['current'] == 1 )
                {
                    $('#total').html(response['count']);
                    
                    var runs = response['count'];
                    item_width = 362/runs;

                    percent_value = 100/runs;
                    $('#loading_percent').show();
                }

                if( index == 0 )
                {
                    $('#current_text').html( response['current_text'] );
                    width += item_width;
                    percent = response['current'] >= response['count'] ? 100 : percent + percent_value;
                    percent_to_show = Math.ceil(percent);

                    sub_width = sub_percent = sub_item_width = 0;

                    var sub_runs = Math.ceil( response['sub_count']/response['limit'] );

                    sub_item_width = 362/sub_runs;
                    sub_percent_value = 100/sub_runs;

                    $('#processing').css('width', width+'px');

                    $('#loading_percent').html(percent_to_show+'%');

                    $('#current_text').html( response['current_text'] );
                    $('#current').html( response['current'] );

                    $('#sub_loading_percent').html('0%');
                    $('#sub_loading_percent').hide();
                }else
                {
                    sub_percent +=sub_percent_value;
                    sub_width += sub_item_width;
                    $('#sub_loading_percent').show();
                }

                sub_percent_to_show = Math.ceil(sub_percent);

                $('#sub_processing').css('width', sub_width+'px');
                $('#sub_loading_percent').html(sub_percent_to_show+'%');
                
                index = response['index'];

                self.import(index);
            }
        });
    }
    
    this.stop = function(){
        request.abort();
    }
    
    this.start = function(){
        self.import(0);
    }
}

var MFImport = new MFImportClass();
