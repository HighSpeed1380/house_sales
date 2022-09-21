<!-- import interface -->
<div class="x-hidden" id="statistic">
    <div class="x-window-header">{$lang.mf_refresh_in_progress}</div>
    <div class="x-window-body" style="padding: 10px 15px;">
        <table class="importing">
        <tr>
            <td class="name">
                {$lang.mf_importing}:
            </td>
            <td class="value">
                <span id="current">1</span> of <label id="total"></label>
            </td>
        </tr>
        <tr>
            <td class="name">
                {$lang.mf_import_current}:
            </td>
            <td class="value">
                <span id="current_text"></span>
            </td>
        </tr>
        </table>
                
        <table class="sTable">
        <tr>
            <td>
                <div class="progress">
                    <div id="processing"></div>
                </div>
            </td>
            <td class="counter">
                <div id="loading_percent" class="hide">0%</div>
            </td>
        </tr>
        </table>

        <div id="dom_area">
            <table class="importing">
            <tr>
                <td class="name">
                    {$lang.mf_import_subprogress}:
                </td>
                <td class="value">
                    <label id="sub_importing">{math x=$config.mf_import_completed  y=1 equation="x+y" assign="startpos"}{$startpos}</label>
                </td>
            </tr>
            </table>
        </div>

        <table class="sTable">
        <tr>
            <td>
                <div class="sub_progress">
                    <div id="sub_processing"></div>
                </div>
            </td>
            <td class="counter">
                <div id="sub_loading_percent" class="hide">0%</div>
            </td>
        </tr>
        </table>
    </div>
</div>

<!-- import interface end -->
