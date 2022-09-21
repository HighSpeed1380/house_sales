<!-- check nearby option tpl -->

<script>
"use strict";

lang['mf_refresh']                       = "{$lang.mf_refresh}";
lang['mf_refresh_in_progress']           = "{$lang.mf_refresh_in_progress}";
lang['mf_enable_nearby_hint']            = "{$lang.mf_enable_nearby_hint}";
lang['mf_database_mismatch_nearby_hint'] = "{$lang.mf_database_mismatch_nearby_hint}";

var mf_show_nearby_listings = "{$config.mf_show_nearby_listings}";
var mf_db_version = "{$config.mf_db_version}";

{literal}
$(function(){
    var in_progress = false;
    var msg_widnow  = false;
    var start       = 0;

    var $option = $('[name="post_config[mf_show_nearby_listings][value]"]');
    var $button = $option.closest('table').find('input[type=submit]');

    var disableButton = function(){
        $button
            .addClass('disabled')
            .attr('disabled', true);
    }

    var enableButton = function(){
        $button
            .removeClass('disabled')
            .attr('disabled', false);
    }

    var rebuild = function(){
        var data = {
            start: start,
            mode: 'mfRebuildCoordinates'
        };
        $.getJSON(rlConfig['ajax_url'], data, function(response, status){
            if (status == 'success') {
                if (response.status == 'next') {
                    start++;
                    rebuild();

                    msg_widnow.updateProgress(response.progress / 100);
                } else if (response.status == 'completed') {
                    in_progress = false;
                    start = 0;

                    msg_widnow.updateProgress(1);

                    setTimeout(function(){
                        $option.closest('form').submit();
                    }, 1000);
                } else {
                    printMessage('error', response.message);
                }
            } else {
                printMessage('error', lang['system_error']);
            }
        });
    }

    var startProgress = function(){
        in_progress = true;

        msg_widnow = Ext.MessageBox.show({
            title: lang['mf_refresh'],
            msg: lang['mf_refresh_in_progress'],
            progress: true,
            width: 300,
            wait: false
        });

        msg_widnow.updateProgress(0);

        rebuild();
    }

    // Unique option listener
    $option.change(function(){
        var val = $option.filter(':checked').val();

        if (mf_show_nearby_listings === '0' && val === '1') {
            disableButton();

            $.getJSON(rlConfig['ajax_url'], {mode: 'mfCheckNearby'}, function(response, status){
                if (status == 'success') {
                    if (response.status == 'OK') {
                        enableButton();
                    } else {
                        if (mf_db_version == 'locations6') {
                            Ext.Msg.show({
                                title: lang['ext_confirm'],
                                msg: lang['mf_enable_nearby_hint'],
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function(btn){
                                    if (btn == 'ok') {
                                        startProgress();
                                    }
                                },
                                animEl: 'elId',
                                icon: Ext.MessageBox.QUESTION
                            });
                        } else {
                            Ext.Msg.show({
                                title: lang['ext_confirm'],
                                msg: lang['mf_database_mismatch_nearby_hint'],
                                buttons: Ext.Msg.OK,
                                animEl: 'elId',
                                icon: Ext.MessageBox.QUESTION
                            });
                        }

                        enableButton();
                    }
                } else {
                    printMessage('error', lang['system_error']);
                }
            });
        }
    });

    $(window).bind('beforeunload', function(){
        if (in_progress) {
            return lang['mf_refresh_in_progress'];
        }
    });
});
{/literal}
</script>

<!-- check nearby option tpl end -->
