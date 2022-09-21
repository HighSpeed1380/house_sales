<!-- database -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplDatabaseNavBar'}
    
    <a href="javascript:void(0)" onclick="show('import', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_import">{$lang.import}</span><span class="right"></span></a>
</div>

<div class="clear" style="*margin: -3px 0; *height: 1px;"></div>
<!-- navigation bar end -->

<div id="action_blocks">
    <!-- import -->
    <div id="import" class="hide">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.import}
    <form onsubmit="return submitHandler()" action="{$rlBase}index.php?controller={$smarty.get.controller}&amp;import" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import" value="true" />
    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.sql_dump}</td>
        <td class="field">
            <input type="file" id="import_file" name="dump" />
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="field">
            <input type="submit" value="{$lang.go}" />
        </td>
    </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
</div>

<!-- query area -->
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

<div style="padding: 5px 10px 0 10px;">
    <table class="form">
    <tr>
        <td class="name">{$lang.sql_query}</td>
        <td class="field" align="right">
            <textarea cols="" rows="" style="height: 80px;" id="query">SELECT * FROM `{$smarty.const.RL_DBPREFIX}config` WHERE 1</textarea>

            <a style="padding: 0 15px 0 15px;" href="javascript:void(0)" onclick="$('#query').val('');" class="cancel">{$lang.reset}</a>
            <input id="run_button" type="button" value="{$lang.go}" onclick="xajax_runSqlQuery($('#query').val());$(this).val('{$lang.loading}');" />
        </td>
    </tr>
    </table>    
</div>

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
<!-- query area end -->

<div id="grid"></div>

{rlHook name='apTplDatabaseBottom'}

<!-- database end -->
