<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: HOME.INC.PHP
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

/* get blocks */
$rlAdmin->assignBlocks();

/* get blocks */
$rlAdmin->apNotifications();

/* get statistics */
$rlAdmin->getStatistics();
/*$plugin_statistics[] = array(
'name' => 'test1',
'items' => array(
array(
'name' => 'Item 1',
'link' => 'http://www.com/item1',
'count' => 1
),
array(
'name' => 'Item 2',
'link' => 'http://www.com/item2',
'count' => 2,
'note' => 'per 2 last days'
)
)
);*/
$rlSmarty->assign_by_ref('plugin_statistics', $plugin_statistics);

/* build desktop settings */
foreach ($languages as $key => $value) {
    $sett_languages[$key] = array(
        'Key'  => $value['Code'],
        'name' => $value['name'],
    );
}

$desktop_settings = array(
    array(
        'Key'     => 'lang',
        'Type'    => 'select',
        'Name'    => $lang['language'],
        'Default' => RL_LANG_CODE,
        'Values'  => $sett_languages,
        'Deny'    => true,
    ),
    array(
        'Key'     => 'flynax_news_number',
        'Type'    => 'number',
        'Name'    => $rlLang->getPhrase('config+name+flynax_news_number', null, null, true),
        'Default' => $config['flynax_news_number'],
    ),
);

if ($_SESSION['sessAdmin']['rights']['config'] || $_SESSION['sessAdmin']['type'] == 'super') {
    $desktop_settings[] = array(
        'Key'     => 'admin_hide_denied_items',
        'Type'    => 'bool',
        'Name'    => $rlLang->getPhrase('config+name+admin_hide_denied_items', null, null, true),
        'Default' => $config['admin_hide_denied_items'],
    );
}

$rlSmarty->assign_by_ref('desktop_settings', $desktop_settings);

eval(base64_decode(RL_SETUP));
eval(base64_decode(RL_ASSIGN));

$rlHook->load('apPhpHome');

/* registr ajax functions */
$rlXajax->registerFunction(array('getFlynaxRss', $rlAdmin, 'ajaxGetFlynaxRss'));
$rlXajax->registerFunction(array('getPluginsLog', $rlAdmin, 'ajaxGetPluginsLog'));
$rlXajax->registerFunction(array('saveConfig', $rlAdmin, 'ajaxSaveConfig'));
