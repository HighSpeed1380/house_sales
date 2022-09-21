<!-- saved search tpl -->

<div class="content-padding" id="saved_search_obj">
    {if !empty($saved_search)}
    <div class="list-table statuses" id="saved_search">
        <div class="header">
            <div class="checkbox" style="width: 40px;"><label><input class="inline all" type="checkbox" /></label></div>
            <div class="center" style="width: 30px;">#</div>
            <div>{$lang.criteria}</div>
            <div style="width: 160px;">{$lang.last_check}</div>
            <div style="width: 80px;">{$lang.status}</div>
        </div>

        {foreach from=$saved_search item='item' name='searchF'}
            {assign var='status_key' value=$item.Status}
            <div class="row" id="item_{$item.ID}">
                <div class="checkbox action no-flex"><label><input class="inline" value="{$item.ID}" type="checkbox" /></label></div>
                <div class="center iteration no-flex">{$smarty.foreach.searchF.iteration}</div>
                <div data-caption="{$lang.criteria}" class="content">
                    <table class="table">
                        {foreach from=$item.fields item='field'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'saved_search_field.tpl'}
                        {/foreach}
                    </table>
                </div>
                <div data-caption="{$lang.last_check}" class="date-cell">
                    <span class="title">{$lang.last_check}:</span></span>
                    <div class="text" style="padding: 0 0 5px;">{$item.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                    <a class="do-search" href="javascript:void(0)" id="search_{$item.ID}">{$lang.check_search}</a>
                </div>
                <div data-caption="{$lang.status}" class="status-cell"><span class="title">{$lang.status}:</span> <span id="status_{$item.ID}"><span class="{$status_key}">{$lang.$status_key}</span></span></div>
            </div>
        {/foreach}
    </div>
    <div id="mass_actions" class="hide mass-actions">{strip}
        <a id="activate" href="javascript:void(0);" title="{$lang.activate}">{$lang.activate}</a>
        <a id="deactivate" href="javascript:void(0);" title="{$lang.deactivate}">{$lang.deactivate}</a>
        <a id="delete" href="javascript:void(0);" title="{$lang.delete}">{$lang.delete}</a>
    {/strip}</div>

    <script class="fl-js-dynamic">
    lang.no_saved_search = '{$lang.no_saved_search}';
    {literal}
        $(function() {
            var $savedSearchInputs = $("#saved_search input:not('.all')"),
                $massActionInput = $('#saved_search input.all'),
                $massActionSection = $('#mass_actions');

            $('a.do-search').click(function() {
                flUtil.ajax(
                    {mode: 'ajaxCheckSavedSearch', id: $(this).attr('id').split('_')[1]},
                    function(response, status) {
                        if (status === 'success' && response && response.status === 'OK' && response.url) {
                            window.location.href = response.url;
                        } else {
                            printMessage(
                                'error',
                                response.message ? response.message : lang.system_error
                            );
                        }
                    }
                );
            });

            $massActionInput.click(function() {
                $savedSearchInputs.each(function() {
                    $(this).attr('checked', !$(this).is(':checked')).trigger('change');
                });
            });

            $savedSearchInputs.change(function() {
                var tab = false;
                $savedSearchInputs.each(function() {
                    if ($(this).is(':checked') && !$(this).hasClass('all')) {
                        tab = true;
                    }
                });

                if (tab === true) {
                    $massActionSection.fadeIn('normal');
                } else {
                    $massActionInput.attr('checked', false);
                    $massActionSection.fadeOut('normal');
                }
            });

            $('#mass_actions a').click(function() {
                var items = '', action = $(this).attr('id');
                $savedSearchInputs.each(function() {
                    if ($(this).is(':checked') && $(this).is(':visible')) {
                        items = items ? items + '|' + $(this).val() : $(this).val();
                    }
                });

                if (action === 'delete') {
                    flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
                    flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
                        $('body').popup({
                            click     : false,
                            content   : '{/literal}{$lang.delete_confirm}{literal}',
                            caption   : lang.notice,
                            navigation: {
                                okButton: {
                                    text: lang.delete,
                                    onClick: function(popup){
                                        sendSavedSearchRequest(items, action);
                                        popup.close();
                                    }
                                },
                                cancelButton: {
                                    text : lang.cancel,
                                    class: 'cancel'
                                }
                            }
                        });
                    });
                } else {
                    sendSavedSearchRequest(items, action);
                }
            });

            var sendSavedSearchRequest = function (items, action) {
                flUtil.ajax(
                    {mode: 'ajaxMassSavedSearch', items: items, action: action},
                    function(response, status) {
                        if (status === 'success' && response && response.status === 'OK') {
                            printMessage('notice', response.message);

                            status  = action === 'activate' ? 'active' : 'approval';
                            var ids = items.split('|');
                            switch (action) {
                                case 'activate':
                                case 'deactivate':
                                    ids.forEach(function (id) {
                                        $('#status_' + id).html(
                                            $('<span>', {class: status}).text(lang[status])
                                        );
                                    });
                                    break;
                                case 'delete':
                                    ids.forEach(function (id) {
                                        $('#item_' + id).fadeOut('slow');
                                    });

                                    if (response.missingAllerts) {
                                        $('#saved_search_obj').html(
                                            $('<div>', {class: 'info'}).html(lang.no_saved_search)
                                        );
                                    }
                                    break;
                            }
                        } else {
                            printMessage(
                                'error',
                                response.message ? response.message : lang.system_error
                            );
                        }
                    }
                );
            }
        });
    {/literal}</script>
</div>

{else}
    <div class="info">{$lang.no_saved_search}</div>
{/if}

<!-- saved search tpl end -->
