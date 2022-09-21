<!-- location finder admin contoller -->

{if $db_update}
    {if $update_error}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

        <div class="lf-instruction">
            <p class="notice"><span class="red">IMPORTANT:</span> We recommend making a back-up of the database so you could easily restore it in case of an import failure.</p>
            <p>To enjoy new capabilities of the geo mapping you should populate your database with new locations using the <b>"Multifield/Location Filter"</b> plugin; to do so please follow the instruction:</p>

            {assign var='df_phrase_key' value='admin_controllers+name+data_formats'}
            <ul class="list">
                <li>Go to <a target="_blank" href="{$rlBase}index.php?controller=data_formats">{$lang.$df_phrase_key}</a> and remove the <b>"Countries"</b> data entry if you have it (Trash Box should be disabled);</li>
                <li>Go to <a target="_blank" href="{$rlBase}index.php?controller=multi_formats">Multi-field Plugin</a> manager and click the <b>"Add an Entry"</b> button at the right top corner;</li>
                <li>Enter the following details in the form:<br />
                - Create as: <b>New data entry</b><br />
                - Key: <b>countries</b><br />
                - Name: <b>Country</b><br />
                - Sorting order: <b>Alphabetic</b><br />
                - Geo Filtering: <b>enable or disable</b><br /> - it's up to you, enable it if you need to filter website listings by visitor location
                - Status: <b>Active</b><br /><br />
                Click the <b>"Add"</b> button;
                </li>
                <li>Find the <b>"Country"</b> data entry and click the <b>"Hammer"</b> icon to manage it;</li>
                <li>Then click the <b>"Import Data"</b> button at the right top corner;</li>
                <li>Find the <b>"World Locations - v6"</b> in a window and click the <b>"Import the entire database"</b> or <b>"Select items to be imported"</b> button;</li>
                <li><a href="{$rlBase}index.php?controller={$smarty.get.controller}&action=update">Reload the page</a> once importing in the previous step is completed;</li>
            </ul>

            Click <a href="{$rlBase}index.php?controller={$smarty.get.controller}&action=update&fix">here</a> if you are sure your current location database is OK and you are ready to import the "geo mapping database".</b>.
        </div>

        <script>
        {literal}

        $(document).ready(function(){
            printMessage('error', '{/literal}{$lang.locationFinder_incompatible_database_error}{literal}');
        });

        {/literal}
        </script>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    {else}
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

        <div class="lf-upload-interface">
            <p>
                {if !$config.locationFinder_db_version}
                    {$lang.locationFinder_remote_install_text}
                {else}
                    {$lang.locationFinder_remote_update_text}
                {/if}
            </p>

            <p style="padding: 10px 0 10px;">
                <span class="red"><b>{$lang.notice}:</b></span> {$lang.locationFinder_remote_update_notice}
            </p>

            {assign var='replace_var' value=`$smarty.ldelim`percent`$smarty.rdelim`}

            <div><input id="install_database" {if $config.locationFinder_db_version}accesskey="update"{/if} type="button" value="{if !$config.locationFinder_db_version}{$lang.install}{else}{$lang.update}{/if}" /></div>
            <div class="loading-interface">
                <div class="progress">{$lang.locationFinder_preparing}</div>
                <div class="progress-bar"><div></div></div>
                <div class="progress-info">{$lang.locationFinder_remote_update_status|replace:$replace_var:'<span>0</span>'}</div>
                <ul class="progress-error-message red"></ul>
            </div>
        </div>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

        <script>
        lang['update'] = '{$lang.update}';

        {literal}

        $(document).ready(function(){
            var loading_interface = $('.lf-upload-interface .loading-interface');
            var progress_bar = loading_interface.find('.progress-bar > div');
            var error_area = loading_interface.find('.progress-error-message');
            var progress_dump = loading_interface.find('.progress');
            var progress_info = loading_interface.find('.progress-info > span');
            var current_file = 1;
            var total_files = 0;
            var in_progress = false;

            $.ajaxSetup({cache: false});

            var locationFinderUploadFile = function(){
                $.post(rlConfig['ajax_url'], {item: 'locationFinderUploadFile', file: current_file}, function(response){
                    if (response.status == 'OK') {
                        progress_dump.text(lang['locationFinder_file_upload_info'].replace('{files}', total_files).replace('{file}', current_file));
                        locationFinderImport();
                    } else {
                        locationFinderError(response.data);
                    }
                }, 'json');
            }

            var locationFinderImport = function(){
                $.post(rlConfig['ajax_url'], {item: 'locationFinderImport'}, function(response){
                    if (response['error']) {
                        locationFinderError(Array(response['error']));
                    } else if (response['action'] == 'next_stack') {
                        locationFinderImport();

                        response['progress'] = response['progress'] > 100 ? 100 : response['progress'];
                        progress_bar.width(response['progress']+'%');
                        progress_info.text(response['progress']);
                    } else if (response['action'] == 'next_file') {
                        current_file++;
                        progress_dump.text(lang['locationFinder_file_download_info'].replace('{files}', total_files).replace('{file}', current_file));

                        locationFinderUploadFile();
                    } else if (response['action'] == 'end') {
                        progress_bar.width('100%');
                        progress_info.text(100);

                        in_progress = false;
                        printMessage('notice', lang['locationFinder_import_completed']);
                        progress_dump.text(lang['locationFinder_import_completed']);
                    }
                }, 'json')
            }

            var locationFinderError = function(data){
                for (var i in data) {
                    if (typeof data[i] != 'string')
                        continue;

                    error_area.append($('<li>').text(data[i]));
                }
                error_area.fadeIn();
                progress_bar.css('width', '0');
            }

            $('#install_database').click(function(){
                // update mode
                if ($(this).attr('accesskey') == 'update') {
                    $(this).val(lang['loading']);
                    var self = this;

                    $.post(rlConfig['ajax_url'], {item: 'locationFinderCheckUpdate'}, function(response){
                        if (response.data.update_status == 'NO') {
                            $(self).val(lang['update']);
                            printMessage('notice', lang['locationFinder_db_uptodate']);
                        } else {
                            $(self).removeAttr('accesskey');
                            $(self).trigger('click');
                        }
                    }, 'json');
                }
                // import mode
                else {
                    $(this).parent().fadeOut(function(){
                        loading_interface.fadeIn(function(){
                            $.post(rlConfig['ajax_url'], {item: 'locationFinderPrepare'}, function(response){
                                if (response.status == 'OK') {
                                    in_progress = true;

                                    total_files = response.data.calc;
                                    progress_dump.text(lang['locationFinder_file_download_info'].replace('{files}', total_files).replace('{file}', current_file));
                                    locationFinderUploadFile();
                                } else {
                                    locationFinderError(response.data);
                                }
                            }, 'json');
                        });
                    });
                }
            });

            $(window).bind('beforeunload', function() {
                if (in_progress) {
                    return 'Uploading the data is in process; closing the page will stop the process.';
                }
            });
        });

        {/literal}
        </script>
    {/if}
{else}
    {if $is_mapping_available}
    <!-- navigation bar -->
    <div id="nav_bar">
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}&action=update" class="button_bar"><span class="left"></span><span class="center_import">{$lang.locationFinder_update_database}</span><span class="right"></span></a>
    </div>
    <!-- navigation bar end -->
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {if $is_mapping_available}
        {if $fields}
            {mapsAPI}

            <script src="{$smarty.const.RL_LIBS_URL}maps/geocoder.js"></script>

            <div class="lc-interface clearfix">
                <div>
                    {if $plugins.multiField}
                        {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/tplHeader.tpl'}
                    {/if}

                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field.tpl'}

                    {if $plugins.multiField}
                        {include file=$smarty.const.RL_PLUGINS|cat:'multiField/admin/tplFooter.tpl'}
                    {/if}

                    <table class="form">
                    <tr>
                        <td style="width: 185px;"></td>
                        <td><input id="edit_button" type="button" value="{$lang.edit}" /></td>
                    </tr>
                    </table>
                </div>
                <div>
                    <input id="pac-input" class="hide" type="text" placeholder="{$lang.locationFinder_address_hint}">
                    <div id="map"><span class="hint">{$lang.locationFinder_default_map_hint}</span></div>
                    <div class="hide" id="save_button"><input type="button" value="{$lang.save}" /></div>
                </div>
            </div>

            <script>
            var lfMap       = false;
            var save_button = $('#save_button');
            var pac_input   = $('#pac-input');
            var lfConfig    = new Array();

            lfConfig['locationFinder_default_location'] = "{$config.locationFinder_default_location}";
            lfConfig['phrase_not_found'] = "{$lang.location_not_found}";
            lfConfig['zoom']             = {if $config.locationFinder_map_zoom}{$config.locationFinder_map_zoom}{else}12{/if};
            lfConfig['mapping_state']    = "{$config.locationFinder_mapping_state}";

            lang['edit'] = '{$lang.edit}';
            lang['locationFinder_no_geocoder_location'] = "{$lang.locationFinder_no_geocoder_location}";

            {literal}

            $(document).ready(function(){
                var lfLoading = function(obj, enable){
                    if (enable) {
                        $(obj).val(lang['loading']).attr('disabled', true).addClass('disabled');
                    } else {
                        $(obj).val(lang['edit']).attr('disabled', false).removeClass('disabled');
                    }
                }

                var showSaveButton = function(mode){
                    if ($('.lc-interface select').val() !== '0') {
                        save_button.slideDown();
                    }
                }

                var lfBuildMap = function(address){
                    if (lfMap) {
                        var pos = address[0].split(',');

                        lfMap.markers[0].setLatLng(new L.LatLng(pos[0], pos[1]))
                        lfMap.map.panTo(pos);

                        // show button for empty mapping
                        if (address[2] == 'geocoder') {
                            showSaveButton();
                        }
                    } else {
                        flMap.init($('#map'), {
                            zoom: lfConfig['zoom'],
                            center: address[0],
                            geocoder: {
                                placeholder: lang['locationFinder_address_hint'],
                                onSelect: function(address, lat, lng){
                                    lfMap.markers[0].setLatLng(new L.LatLng(lat, lng));
                                    showSaveButton();
                                }
                            },
                            addresses: [{
                                latLng: address[0]
                            }],
                            idle: function(map){
                                lfMap = this;

                                map.doubleClickZoom.disable();

                                this.markers[0].dragging.enable();

                                // show button for empty mapping
                                if (address[2] == 'geocoder') {
                                    showSaveButton();
                                }

                                this.markers[0].on('dragend', function(){
                                    showSaveButton();
                                });
                            }
                        });
                    }
                }

                // edit button handler
                $('#edit_button').click(function(){
                    var button = this;
                    var last_select = false;
                    var address = new Array();

                    $('.lc-interface select').each(function(){
                        if ($(this).val() != '0') {
                            last_select = $(this);
                            address.push($(this).find('> option:selected').text());
                        }
                    });

                    if (last_select.length) {
                        // enable loading
                        lfLoading(this, true);

                        // get current location data if so
                        $.post(rlConfig['ajax_url'], {item: 'locationFinderGetMapping', key: last_select.val()}, function(response){
                            if (response.status == 'OK' && response.results.Lat) {
                                address = [response.results.Lat + ',' + response.results.Lng, address.join(', '), 'direct'];
                                lfBuildMap(address);

                                // disable loading
                                lfLoading(button, false);
                            } else {
                                geocoder(address.join(','), function(response, status){
                                    if (status == 'success' && response.status == 'OK') {
                                        address = [response.results[0].lat + ',' + response.results[0].lng, address.join(', '), 'geocoder'];
                                        lfBuildMap(address);
                                    } else {
                                        printMessage('error', lang['locationFinder_no_geocoder_location'].replace('{location}', address.join(', ')));
                                        $('.leaflet-autocomplete.leaflet-control').focus();
                                        lfBuildMap([lfConfig['locationFinder_default_location'], '', 'direct']);
                                    }

                                    // disable loading
                                    lfLoading(button, false);
                                });
                            }
                        }, 'json').fail(function(object, status) {
                            // disable loading
                            lfLoading(button, false);

                            if (status == 'abort') {
                                return;
                            }

                            printMessage('error', lang['system_error']);
                            console.log('locationFinder: AP | get mapping ajax request failed');
                        });
                    } else {
                        printMessage('error', lang['locationFinder_js_location_not_selected_error']);
                    }
                });

                // save button handler
                save_button.click(function(){
                    var self = this;
                    var last_select = false;
                    var lat = lfMap.markers[0].getLatLng().lat;
                    var lng = lfMap.markers[0].getLatLng().lng;

                    $('.lc-interface select').each(function(){
                        if ($(this).val() != '0') {
                            last_select = $(this);
                        }
                    });

                    if (!last_select) {
                        return;
                    }

                    lfLoading($(this).find('input'), true);

                    var data = {
                        latlng: lat + ',' + lng
                    };

                    geocoder(data, function(response, status){
                        if (status == 'success' && response.status == 'OK') {
                            var address = response.results;
                            var place_id_city = null;
                            var place_id_neighborhood = null;

                            response.results.forEach(function(item){
                                switch(item.type) {
                                    case 'city':
                                        place_id_city = item.place_id;
                                        break;

                                    case 'suburb':
                                        place_id_neighborhood = item.place_id;
                                        break;
                                }
                            });

                            var data = {
                                item: 'locationFinderSaveMapping',
                                formatKey: last_select.val(),
                                cityPlaceID: place_id_city,
                                target: last_select.attr('name').indexOf(lfConfig['mapping_state']) >= 0 ? 'region' : 'city',
                                neighborhoodPlaceID: place_id_neighborhood,
                                lat: lat,
                                lng: lng
                            };

                            // save location mapping
                            $.post(rlConfig['ajax_url'], data, function(response){
                                if (response.status == 'OK') {
                                    printMessage('notice', lang['locationFinder_mapping_saved']);
                                    $(save_button).slideUp();
                                }
                            }, 'json').fail(function(object, status) {
                                if (status == 'abort')
                                    return;

                                printMessage('error', lang['system_error']);
                                console.log('locationFinder: AP | save mapping ajax request failed');
                            });

                            $(self).slideUp();
                            lfLoading($(self).find('input'), false);
                        } else {
                            printMessage('error', lang['system_error']);
                        }
                    });
                });

                // location dropdowns handler
                $('.lc-interface select').change(function(){
                    $(save_button).slideUp();
                });
            });

            {/literal}
            </script>
        {else}
            {assign var='link' value='<a href="'|cat:$href|cat:'">$1</a>'}
            {$lang.locationFinder_mapping_no_fields_mapping|regex_replace:'/\[(.+)\]/':$link}
        {/if}
    {else}
        {$mapping_error}
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{/if}

<!-- location finder admin contoller end -->
