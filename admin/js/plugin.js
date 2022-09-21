
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: PLUGIN.JS
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

/**
 * Plugins management class
 *
 * @since 4.8.1
 */
var pluginClass = function(){
    var self = this;

    this.actionsLocked = false;
    this.clickLocked = false;

    this.install = function(key, remote){
        if (!key) {
            console.log('PluginClass.install(), plugin key (the first parameter) is missing')
        }

        if (this.actionsLocked === true) {
            return;
        }

        this.actionsLocked = true;

        flynax.cursorLoading();

        var data = {
            item: 'install',
            key: key,
            remote: remote,
            domain: license_domain,
            license: license_number
        };
        $.getJSON(rlUrlHome+'plugin.ajax.php', data, function(response, status){
            flynax.cursorDefault();

            if (status == 'success' && response.status) {
                switch (response.status) {
                    case 'REDIRECT':
                        location.href = rlUrlHome + 'index.php?controller=' + controller + '&session_expired';
                        break;

                    case 'ERROR':
                        printMessage('error', response.message);
                        self.runCustomJS(response.js);
                        break;

                    default:
                        self.appendBlocks(response.html);
                        self.buildMenuItem(response.menu);
                        self.runCustomJS(response.js);

                        if (remote) {
                            var $area = $('div.changelog_item a[name=' + key + ']').closest('div.changelog_item');
                            var callBack = controller == '' || controller == 'home'
                            ? xajax_getPluginsLog
                            : function(){
                                $area.closest('li').fadeOut(function(){
                                    $(this).remove();

                                    if (!$('ul.browse_plugins li').length) {
                                        $('#browse_content').html(response.phrase.no_new_plugins);
                                    }
                                });
                            };

                            $area.next().find('div.progress').html(response.phrase.remote_progress_installation_completed);
                            setTimeout(function(){
                                callBack.call();
                            }, 1000);
                        }

                        if (typeof pluginsGrid == 'object') {
                            pluginsGrid.reload();
                        }

                        printMessage('notice', response.notice.replace(/\\'/g, "'"));

                        break;
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            self.actionsLocked = false;
        });
    }

    this.remoteInstall = function(key, changelog){
        if (!key) {
            console.log('PluginClass.remoteInstall(), plugin key (the first parameter) is missing')
        }

        if (this.actionsLocked === true) {
            return;
        }

        this.actionsLocked = true;

        var $area = $('div.changelog_item a[name=' + key + ']').closest('div.changelog_item');
        var $progress = $area.next().find('div.progress');

        setTimeout(function(){
            $progress.html(lang['remote_progress_download']);
        }, 500);

        flynax.cursorLoading();

        var data = {
            item: 'remoteInstall',
            key: key,
            domain: license_domain,
            license: license_number
        };
        $.getJSON(rlUrlHome+'plugin.ajax.php', data, function(response, status){
            flynax.cursorDefault();

            if (status == 'success' && response.status) {
                switch (response.status) {
                    case 'REDIRECT':
                        location.href = rlUrlHome + 'index.php?controller=' + controller + '&session_expired';
                        break;

                    case 'ERROR':
                        printMessage('error', response.message);
                        self.runCustomJS(response.js);

                        if (changelog) {
                            $area.next().fadeOut('fast', function(){
                                $area.fadeIn();
                            });
                        } else {
                            $('#update_progress').fadeOut();
                        }
                        break;

                    default:
                        setTimeout(function(){
                            $progress.html(lang['remote_progress_installing']);
                            self.install(key, true);
                        }, 1000);
                        break;
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            self.actionsLocked = false;
        });
    }

    this.update = function(key, remote){
        if (!key) {
            console.log('PluginClass.update(), plugin key (the first parameter) is missing')
        }

        if (this.actionsLocked === true) {
            return;
        }

        this.actionsLocked = true;

        flynax.cursorLoading();

        var data = {
            item: 'update',
            key: key,
            remote: remote,
            domain: license_domain,
            license: license_number
        };
        $.getJSON(rlUrlHome+'plugin.ajax.php', data, function(response, status){
            flynax.cursorDefault();

            if (status == 'success' && response.status) {
                switch (response.status) {
                    case 'REDIRECT':
                        location.href = rlUrlHome + 'index.php?controller=' + controller + '&session_expired';
                        break;

                    case 'ERROR':
                        printMessage('error', response.message);
                        self.runCustomJS(response.js);
                        break;

                    default:
                        self.appendBlocks(response.html);
                        self.buildMenuItem(response.menu);
                        self.runCustomJS(response.js);

                        if (remote) {
                            var $area = $('div.changelog_item a[name=' + key + ']').closest('div.changelog_item');
                            $area.next().find('div.progress').html(response.phrase.remote_progress_update_completed);
                            setTimeout(function(){
                                /**
                                 * @todo - rework using ajax
                                 */
                                xajax_getPluginsLog();
                            }, 1000);
                        } else {
                            $('#update_area').fadeOut(function(){
                                $('#update_progress').removeAttr('style');
                            });
                            pluginsGrid.reload();
                        }

                        printMessage('notice', response.notice.replace(/\\'/g, "'"));
                        break;
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            self.actionsLocked = false;
        });
    }

    this.remoteUpdate = function(key, remote){
        if (!key) {
            console.log('PluginClass.remoteUpdate(), plugin key (the first parameter) is missing')
        }

        if (this.actionsLocked === true) {
            return;
        }

        this.actionsLocked = true;

        var $area = $('div.changelog_item a[name=' + key + ']').closest('div.changelog_item');
        var $progress = $area.next().find('div.progress');

        setTimeout(function(){
            if (remote) {
                $progress.html(lang['remote_progress_download']);
            } else {
                $('div#update_progress div.progress').html(lang['remote_progress_download']);
            }
        }, 1000);

        flynax.cursorLoading();

        var data = {
            item: 'remoteUpdate',
            key: key,
            domain: license_domain,
            license: license_number
        };
        $.getJSON(rlUrlHome+'plugin.ajax.php', data, function(response, status){
            flynax.cursorDefault();

            if (status == 'success' && response.status) {
                switch (response.status) {
                    case 'REDIRECT':
                        location.href = rlUrlHome + 'index.php?controller=' + controller + '&session_expired';
                        break;

                    case 'ERROR':
                        printMessage('error', response.message);
                        self.runCustomJS(response.js);

                        $('#update_progress').fadeOut();

                        if (remote && response.reload_log) {
                            setTimeout(function(){
                                /**
                                 * @todo - rework using ajax
                                 */
                                xajax_getPluginsLog();
                            }, 1000);
                        }

                        if (!remote) {
                            $('#update_info').fadeIn();
                        }
                        break;

                    default:
                        setTimeout(function(){
                            if (remote) {
                                $progress.html(lang['remote_progress_updating']);
                            } else {
                                $('div#update_progress div.progress').html(lang['remote_progress_updating']);
                            }
                            self.update(key, remote);
                        }, 1000);
                        break;
                }
            } else {
                printMessage('error', lang['system_error']);
            }

            self.actionsLocked = false;
        });
    }

    this.appendBlocks = function(blocks){
        if (!blocks) {
            return;
        }

        for (var i in blocks) {
            $('#tmp_dom_blocks_store').append(blocks[i].code);
            $('#tmp_dom_blocks_store div.block').hide();

            $('td.column' + blocks[i].box + ' div.sortable').append($('#tmp_dom_blocks_store div.block'));
            $('td.column' + blocks[i].box + ' div.sortable div.block:last').fadeIn('slow');

            if (blocks[i].ajax && typeof aBlockInit == 'function') {
                aBlockInit.call();
            }
        }
    }

    this.buildMenuItem = function(item){
        if (!item) {
            return;
        }

        var $menuItem = '<div class="mitem" id="mPlugin_' + item.key + '"><a href="' + rlUrlHome + 'index.php?controller=' + item.controller + '">' + item.title + '<\a><\div>';
        $('#plugins_section').append($menuItem);

        apMenu['plugins'][item.key] = new Array();
        apMenu['plugins'][item.key]['Name'] = item.title;
        apMenu['plugins'][item.key]['Controller'] = item.controller;
        apMenu['plugins'][item.key]['Vars'] = '';
    }

    this.runCustomJS = function(js){
        if (!js) {
            return;
        }

        for (var i in js) {
            try {
                eval(js[i]);
            } catch(e){
                // Skip errors
            }
        }
    }
}

var plugin = new pluginClass();
