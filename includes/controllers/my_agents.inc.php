<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: MY_AGENTS.INC.PHP
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

use Flynax\Classes\Agencies;
use Flynax\Utils\Util;
use Flynax\Utils\Valid;

$agencies = new Agencies();

if ($account_info && $agencies->isAgency($account_info)) {
    if ($_POST['agent-email'] && $_GET['send-invite']) {
        $errors     = [];
        $agentEmail = Valid::escape($_POST['agent-email']);

        if (!Valid::isEmail($agentEmail)) {
            $errors[] = $lang['incorrect_email'];
            $error_fields = 'agent-email';
        } elseif (!$agencies->isAgent($agentEmail)) {
            $errors[] = $rlLang->getSystem('account_cannot_be_agent');
            $error_fields = 'agent-email';
        } elseif ($invite = $rlDb->fetch('*', ['Agent_Email' => $agentEmail], null, 1, 'agency_invites', 'row')) {
            $errors[] = $rlLang->getSystem(
                $account_info['ID'] == $invite['Agency_ID']
                    ? 'invite_already_sent'
                    : 'agent_invited_to_another_agency'
            );
        }

        if ($errors) {
            $rlSmarty->assign_by_ref('errors', $errors);
        } else if ($agencies->sendInviteToAgent((int) $account_info['ID'], $agentEmail)) {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($rlLang->getSystem('agency_invite_sent'));
            Util::redirect($reefless->getPageUrl('my_agents'));
        } else {
            $reefless->loadClass('Notice');
            $rlNotice->saveNotice($lang['system_error'], 'error');
        }
    }

    $pInfo['current'] = (int) $_GET['pg'];

    // Save search criteria to session for pagination function
    if ($_GET['nvar_1'] == $search_results_url || isset($_GET[$search_results_url])) {
        $_SESSION['search_invites_criteria'] = $_POST['f'] ?: $_SESSION['search_invites_criteria'];
        $searchCriteria = $_SESSION['search_invites_criteria'];
    } else {
        unset($_SESSION['search_invites_criteria']);
    }

    $rlSmarty->assign('invites', $agencies->getInvites($account_info['ID'], $searchCriteria ?: [], $pInfo['current']));
    $pInfo['calc']          = $agencies->getCountInvites();
    $pInfo['paginationUrl'] = $searchCriteria ? $search_results_url : '';
    $rlSmarty->assign('pInfo', $pInfo);

    if ($searchCriteria) {
        $bread_crumbs[]     = ['name' => $lang['search_results']];
        $page_info['name']  = str_replace(['{number}'], [$agencies->getCountInvites()], $lang['accounts_found']);
        $page_info['title'] = $lang['search_results'];
    }

    $lang['name_email'] = $lang['name'] . '/' . $lang['mail'];

    $fields = [
        'name_email' => [
            'Key'   => 'name_email',
            'Type'  => 'text',
            'pName' => 'name_email',
        ],
        'date' => [
            'Key'     => 'date',
            'Type'    => 'date',
            'Default' => 'single',
            'pName'   => 'date',
        ],
        'status' => [
            'Key'    => 'status',
            'Type'   => 'select',
            'pName'  => 'status',
            'Values' => [
                'pending'  => ['Key' => 'pending', 'pName'  => 'pending'],
                'accepted' => ['Key' => 'accepted', 'pName' => 'accepted'],
                'declined' => ['Key' => 'declined', 'pName' => 'declined'],
            ]
        ]
    ];
    $rlSmarty->assign('fields', $fields);

    $_POST = $_REQUEST['f'];

    // Emulate system listing type array for proper work of search box
    $rlSmarty->assign('listing_type', ['Submit_method' => 'post']);

    // Emulate system search box in account type page (to get proper design of box)
    if ($blocks['invite_search']) {
        $boxInfo = $blocks['invite_search'];
        $boxInfo['Key'] = 'account_search';
        unset($blocks['invite_search']);
        $blocks['account_search'] = $boxInfo;
        unset($boxInfo);
        $rlCommon->defineBlocksExist($blocks);
    }
} else {
    unset($blocks['invite_search']);
    $rlCommon->defineBlocksExist($blocks);

    $sError = true;
}
