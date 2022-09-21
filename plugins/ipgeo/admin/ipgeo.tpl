<!-- ipgeo tpl -->

<style>{literal}
.ipgeo-p {
    font-size: 14px;
    line-height: 20px;
}
.ipgeo .red {
    font-size: 14px;
}
.ipgeo .loading-interface {
    border-top: 1px #cccccc solid;
    margin-top: 10px;
    padding-top: 18px;
    display: none;
}
.ipgeo .progress-bar {
    max-width: 600px;
    height: 5px;
    background: #e2e2e2;
    margin: 10px 0;
}
.ipgeo .progress-bar > div {
    height: 100%;
    width: 0;
    background: #748645;
    transition: width 0.2s ease;
}
.ipgeo .progress-error-message {
    margin-top: 15px;
    display: none;
}
.ipgeo .progress-error-message > li:not(:first-child) {
    padding-top: 2px;
}
{/literal}</style>

{include file='blocks/m_block_start.tpl'}
{assign var='compared_version' value=$config.ipgeo_database_version|version_compare:'1.0.0'}

<div class="ipgeo">
    <p class="ipgeo-p">
        {if $compared_version < 0}
            {$lang.ipgeo_remote_install_text}
        {else}
            {$lang.ipgeo_remote_update_text}
        {/if}
    </p>

    {assign var='replace_var' value=`$smarty.ldelim`percent`$smarty.rdelim`}

    <div>
        <input id="install_database"
                {if $compared_version >= 0} accesskey="update"{/if}
                type="button"
                value="{if $compared_version < 0}{$lang.install}{else}{$lang.update}{/if}" />
    </div>
    <div class="loading-interface">
        <div class="progress">{$lang.ipgeo_preparing}</div>
        <div class="progress-bar"><div></div></div>
        <div class="progress-info">{$lang.ipgeo_remote_update_status|replace:$replace_var:'<span>0</span>'}</div>
        <ul class="progress-error-message red"></ul>
    </div>
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script>{literal}
$(function() {
    let $loadingInterface      = $('.ipgeo .loading-interface');
    let $progressBar           = $loadingInterface.find('.progress-bar > div'),
        $errorArea             = $loadingInterface.find('.progress-error-message'),
        $progressDump          = $loadingInterface.find('.progress'),
        $progressInfo          = $loadingInterface.find('.progress-info > span'),
        currentFile            = 0,
        totalFiles             = 0,
        inProgress             = false,
        failTimeout            = 60000, // 60 seconds
        failRequest            = 0,     // Count of the failed requests
        failRequestCountToStop = 15;

    $.ajaxSetup({cache: false});

    var ipGeoDownloadFile = function() {
        $.getJSON(rlConfig.ajax_url, {item: 'ipGeoDownloadFile', file: currentFile}, function(response) {
            if (response.error) {
                if (response.retry && failRequest < failRequestCountToStop) {
                    failRequest++;
                    setTimeout(function() { ipGeoDownloadFile(); }, failTimeout);
                } else {
                    ipGeoError(lang.ipgeo_too_many_failed_requests);
                }
            } else if (response.status === 'OK') {
                if (response.action === 'next_file') {
                    currentFile++;
                    $progressDump.text(
                        lang.ipgeo_file_download_info.replace('{files}', totalFiles).replace('{file}', currentFile)
                    );
                    response.progress = response.progress > 100 ? 100 : response.progress;
                    $progressBar.width(response.progress + '%');
                    $progressInfo.text(response.progress);

                    ipGeoDownloadFile();
                } else if (response.action === 'end') {
                    $progressBar.width('100%');
                    $progressInfo.text(100);

                    inProgress = false;
                    printMessage('notice', lang.ipgeo_import_completed);
                    $progressDump.text(lang.ipgeo_import_completed);
                }
            } else {
                ipGeoError(response.data);
            }
        });
    }

    var ipGeoError = function(data) {
        $errorArea.append($('<li>').text(data)).show();
        $progressBar.css('width', '0');
        inProgress = false;
    }

    $('#install_database').click(function() {
        if ($(this).attr('accesskey') === 'update') {
            $(this).val(lang.loading);
            var self = this;

            $.getJSON(rlConfig.ajax_url, {item: 'ipgeoCheckUpdate'}, function(response) {
                if (response.data === 'NO') {
                    $(self).val(lang.update);
                    printMessage('notice', lang.ipgeo_db_uptodate);
                } else {
                    $(self).removeAttr('accesskey');
                    $(self).trigger('click');
                }
            });
        }
        // import mode
        else {
            $(this).parent().fadeOut(function() {
                $loadingInterface.fadeIn(function() {
                    $.getJSON(rlConfig.ajax_url, {item: 'ipGeoPrepare'}, function(response) {
                        if (response.status === 'OK') {
                            inProgress = true;
                            totalFiles = response.data.calc;
                            $progressDump.text(
                                lang.ipgeo_file_download_info
                                    .replace('{files}', totalFiles)
                                    .replace('{file}', currentFile)
                            );
                            ipGeoDownloadFile();
                        } else {
                            ipGeoError(response.data);
                        }
                    });
                });
            });
        }
    });

    $(window).bind('beforeunload', function() {
        if (inProgress) {
            return 'Uploading the data is in process; closing the page will stop the process.';
        }
    });
});
{/literal}</script>

<!-- ipgeo tpl end -->
