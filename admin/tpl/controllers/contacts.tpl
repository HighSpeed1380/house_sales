<!-- contacts tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {rlHook name='apTplContactsNavBar'}

    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.contacts_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'view'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    
    <table class="list">
    <tr>
        <td class="name">{$lang.name}</td>
        <td class="value"><b>{$contact.Name}</b></td>
    </tr>
    <tr>
        <td class="name">{$lang.date}</td>
        <td class="value">{$contact.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
    </tr>
    <tr>
        <td class="name">{$lang.mail}</td>
        <td class="value"><a href="mailto:{$contact.Email}">{$contact.Email}</a></td>
    </tr>
    
    {rlHook name='apTplContactsInfo'}
    
    <tr>
        <td class="name">{$lang.message}</td>
        <td class="" style="padding: 15px 0 20px 0; font-size: 13px;">
            {$contact.Message|nl2br}
        </td>
    </tr>
    </table>
    
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.reply}
    
    <form onsubmit="return submitHandler()" action="{$rlBaseC}action=view&amp;id={$smarty.get.id}" method="post" enctype="multipart/form-data">
        <input type="hidden" name="submit" value="1" />
        <input type="hidden" name="fromPost" value="1" />
        
        <table class="form">
        <tr>
            <td class="name"><span class="red">*</span>{$lang.subject}</td>
            <td class="field">
                <input type="text" name="subject" class="w350" />
            </td>
        </tr>
        
        {rlHook name='apTplContactsForm'}
        
        <tr>
            <td class="name"><span class="red">*</span>{$lang.message}</td>
            <td class="field">
                {fckEditor name='message' width='100%' height='140' value=$smarty.post.message}
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" name="reply" value="{$lang.reply}" />
            </td>
        </tr>
        </table>
    </form>
    
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    
{else}

    <!-- contacts grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var contactsGrid;
    
    {literal}
    $(document).ready(function(){
        
        contactsGrid = new gridObj({
            key: 'contacts',
            id: 'grid',
            ajaxUrl: rlUrlHome + 'controllers/contacts.inc.php?q=ext',
            defaultSortField: 'Date',
            defaultSortType: 'DESC',
            remoteSortable: true,
            title: lang['ext_contacts_manager'],
            fields: [
                {name: 'Name', mapping: 'Name', type: 'string'},
                {name: 'Email', mapping: 'Email', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Message', mapping: 'Message', type: 'string'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'ID', mapping: 'ID'}
            ],
            columns: [
                {
                    header: lang['ext_name'],
                    dataIndex: 'Name',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_email'],
                    dataIndex: 'Email',
                    width: 20,
                    id: 'rlExt_item'
                },{
                    header: lang['ext_add_date'],
                    dataIndex: 'Date',
                    width: 10,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 10
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        
                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=view&id="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteContact\", \""+Array(data)+"\" )' />";
                        
                        out += "</center>";
                        
                        return out;
                    }
                }
            ]
        });
        
        {/literal}{rlHook name='apTplContactsGrid'}{literal}
        
        contactsGrid.init();
        grid.push(contactsGrid.grid);
        
    });
    {/literal}
    //]]>
    </script>
    <!-- contacts grid end -->

    {rlHook name='apTplContactsBottom'}
{/if}

<!-- contacts end tpl -->
