<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: AGENCIES.PHP
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

namespace Flynax\Classes;

use Exception;
use Flynax\Utils\Valid;

/**
 * Agencies class
 *
 * @since 4.9.0
 */
class Agencies
{
    /**
     * @var null
     */
    private $reefless = null;

    /**
     * @var null
     */
    private $rlDb = null;

    /**
     * @var null
     */
    private $rlAccountTypes = null;

    /**
     * @var null
     */
    private $rlHook = null;

    /**
     * @var null
     */
    private $rlAccount = null;

    /**
     * @var null
     */
    private $rlMail = null;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var array
     */
    private $lang = [];

    /**
     * @var string
     */
    private $inviteKey = '';

    /**
     * @var array
     */
    private $inviteInfo = [];

    /**
     * @var int
     */
    protected $countInvites = 0;

    /**
     * @var null
     */
    protected $rlAdmin = null;

    /**
     * @var null
     */
    protected $rlMembershipPlan = null;

    /**
     * @var array
     */
    protected $membershipPlans = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reefless = &$GLOBALS['reefless'];

        if (!$GLOBALS['rlAccount']) {
            $this->reefless->loadClass('Account');
        }

        if (!$GLOBALS['rlMail']) {
            $this->reefless->loadClass('Mail');
        }

        if (!$GLOBALS['rlAccountTypes']) {
            $this->reefless->loadClass('AccountTypes');
        }

        $this->rlDb           = &$GLOBALS['rlDb'];
        $this->rlAccountTypes = &$GLOBALS['rlAccountTypes'];
        $this->rlHook         = &$GLOBALS['rlHook'];
        $this->config          = &$GLOBALS['config'];
        $this->lang           = &$GLOBALS['lang'];
        $this->rlAccount      = &$GLOBALS['rlAccount'];
        $this->rlMail         = &$GLOBALS['rlMail'];

        if ($this->config['membership_module']) {
            if (!$GLOBALS['rlMembershipPlan']) {
                $this->reefless->loadClass('MembershipPlan');
            }

            $this->rlMembershipPlan = &$GLOBALS['rlMembershipPlan'];
            $this->membershipPlans  = $this->rlMembershipPlan->getPlans();
        }
    }

    /**
     * Save key of invite to the class
     *
     * @param string $key
     *
     * @return $this
     */
    public function setInviteKey(string $key = ''): Agencies
    {
        if (!Valid::escape($key)) {
            return $this;
        }

        $this->reefless->createCookie('agencyInviteConfirmationKey', $key, time() + (365 * 86400));
        $_COOKIE['agencyInviteConfirmationKey'] = $key;
        $this->inviteKey = $key;

        return $this;
    }

    /**
     * Remove key of invite from cookies and from the Class
     *
     * @return $this
     */
    public function removeInviteKey(): Agencies
    {
        $this->reefless->eraseCookie('agencyInviteConfirmationKey');
        $this->inviteKey = '';

        return $this;
    }

    /**
     * Get full info about invite by key
     *
     * @param string $key
     *
     * @return array
     */
    public function getInviteInfo(string $key = ''): array
    {
        if ($this->inviteInfo) {
            return $this->inviteInfo;
        }

        $key = $key ?: $this->inviteKey;
        $this->inviteInfo = $this->rlDb->fetch('*', ['Invite_Code' => $key], null, 1, 'agency_invites', 'row') ?: [];

        return $this->inviteInfo;
    }

    /**
     * Accept the invite by agent
     *
     * @return $this
     * @throws Exception
     */
    public function acceptInvite(): Agencies
    {
        return $this->changeInviteStatus('accepted');
    }

    /**
     * Decline the invite by agent
     *
     * @return $this
     * @throws Exception
     */
    public function declineInvite(): Agencies
    {
        return $this->changeInviteStatus('declined');
    }

    /**
     * Change the status of invite to "accepted" or "declined"
     *
     * @param string $status
     *
     * @return $this
     * @throws Exception
     */
    protected function changeInviteStatus(string $status): Agencies
    {
        if ($this->inviteKey && !$this->inviteInfo) {
            $this->inviteInfo = $this->getInviteInfo();
        }

        if (!$status || !$this->inviteInfo) {
            throw new Exception('Missing required data about invite.');
        }

        if (!in_array($status, ['accepted', 'declined'])) {
            throw new Exception("Incorrect status of invite '{$status}'. Status must be: accepted or declined.");
        }

        $this->rlDb->updateOne([
            'fields' => ['Status' => $status, $status === 'accepted' ? 'Accepted_Date' : 'Declined_Date' => 'NOW()'],
            'where'  => ['ID' => $this->inviteInfo['ID']],
        ], 'agency_invites');

        if ($status === 'accepted') {
            $this->rlDb->updateOne([
                'fields' => ['Agency_ID' => $this->inviteInfo['Agency_ID']],
                'where'  => ['ID' => $this->inviteInfo['Agent_ID']],
            ], 'accounts');
        }

        $agency       = $this->rlAccount->getProfile((int) $this->inviteInfo['Agency_ID']);
        $agent        = $this->rlAccount->getProfile((int) $this->inviteInfo['Agent_ID']);
        $mailTemplate = $this->rlMail->getEmailTemplate("agent_{$status}_invite", $agency['Lang']);
        $agentPage    = $agent['Personal_address'];
        $myAgentsPage = $this->reefless->getPageUrl('my_agents');

        /**
         * @todo - Remove when the bug will be fixed in the Multifield/Location Filter plugin
         */
        if ($agentPage && false !== strpos($agentPage, 'locfix/')) {
            $agentPage = str_replace('locfix/', '/', $agentPage);
        }

        $mailTemplate['subject'] = str_replace('{agent}', $agent['Full_name'], $mailTemplate['subject']);
        $find     = ['{agency}', '{agent}', '{link}'];
        $replace = [
            $agency['Full_name'],
            $agentPage ? "<a href=\"{$agentPage}\">{$agent['Full_name']}</a>" : $agent['Full_name'],
            "<a href=\"{$myAgentsPage}\">{$myAgentsPage}</a>"
        ];
        $mailTemplate['body'] = str_replace($find, $replace, $mailTemplate['body']);
        $this->rlMail->send($mailTemplate, $agency['Mail']);

        return $this;
    }

    /**
     * Tells the account is supported the subaccounts or not
     *
     * @param array $account
     *
     * @return bool
     */
    public function isAgency(array $account): bool
    {
        if (!$account || !isset($account['Type'], $account['Plan_ID'])) {
            return false;
        }

        $accountType = $this->rlAccountTypes->types[$account['Type']];

        if (!$this->config['membership_module'] && $accountType && $accountType['Agency']) {
            return true;
        }

        if ($this->config['membership_module'] && $this->membershipPlans[$account['Plan_ID']]) {
            return (bool) array_filter(
                $this->membershipPlans[$account['Plan_ID']]['Services'],
                static function ($service) {
                    return $service['Key'] === 'agency' ? $service : null;
                }
            );
        }

        return false;
    }

    /**
     * Tells the account can be an agent of agency.
     * Account can be an agent if it's registered with supported account type, or it's not registered yet.
     *
     * @param string $email
     *
     * @return bool
     */
    public function isAgent(string $email): bool
    {
        $email = Valid::escape($email);

        if (!$email) {
            return false;
        }

        $sql = "SELECT `T2`.`Agent` FROM `{db_prefix}accounts` AS `T1` ";
        $sql .= "LEFT JOIN `{db_prefix}account_types` AS `T2` ON `T1`.`Type` = `T2`.`Key` ";
        $sql .= "WHERE `T1`.`Mail` = '{$email}'";
        $agentOption = $this->rlDb->getRow($sql);

        return $agentOption && $agentOption['Agent'] === '1' || !$agentOption;
    }

    /**
     * Send invite from agency to potential agent
     *
     * @param int    $agencyID
     * @param string $agentEmail
     *
     * @return bool
     */
    public function sendInviteToAgent(int $agencyID, string $agentEmail): bool
    {
        $agentEmail = Valid::escape($agentEmail);

        if (!$agencyID || !$agentEmail || !Valid::isEmail($agentEmail)) {
            return false;
        }

        $agency = $this->rlAccount->getProfile($agencyID);
        $agent  = $this->rlDb->getRow("SELECT * FROM `{db_prefix}accounts` WHERE `Mail` = '{$agentEmail}'");
        $code   = $this->reefless->generateHash();

        if ($this->sendEmailWithInvite($agentEmail, $code, $agency, $agent['Lang'] ?: $agency['Lang'])) {
            $this->rlDb->insertOne([
                'Agency_ID'    => $agency['ID'],
                'Agent_ID'     => $agent ? $agent['ID'] : 0,
                'Invite_Code'  => $code,
                'Agent_Email'  => $agentEmail,
                'Created_Date' => 'NOW()',
            ], 'agency_invites');

            return true;
        }

        return false;
    }

    /**
     * Get list of invites to agents
     *
     * @param int   $agencyID
     * @param array $filters
     * @param int   $page
     * @param int   $limit
     *
     * @return array
     */
    public function getInvites(int $agencyID, array $filters = [], int $page = 0, int $limit = 0): array
    {
        $inviteList = [];

        if (!$agencyID || !is_int($agencyID)) {
            return $inviteList;
        }

        $limit = $limit ?: (int) $this->config['dealers_per_page'];
        $start = $page > 1 ? ($page - 1) * $limit : 0;

        $sql = "SELECT SQL_CALC_FOUND_ROWS `T1`.* ";

        $this->rlHook->load('phpGetInvitesSqlSelect', $sql, $agencyID);

        $sql .= "FROM `{db_prefix}agency_invites` AS `T1` ";

        $this->rlHook->load('phpGetInvitesSqlJoin', $sql, $agencyID);

        if ($filters['name_email']) {
            $sql .= "LEFT JOIN `{db_prefix}accounts` AS `T2` ON `T2`.`ID` = `T1`.`Agent_ID` ";
        }

        $sql .= "WHERE `T1`.`Agency_ID` = {$agencyID} ";

        foreach ($filters as $filterKey => $filter) {
            $filter = Valid::escape($filter);
            if (!$filter) {
                continue;
            }

            switch ($filterKey) {
                case 'status':
                    $sql .= "AND `T1`.`Status` = '{$filter}' ";
                    break;

                case 'name_email':
                    if (Valid::isEmail($filter)) {
                        $sql .= "AND (`T1`.`Agent_Email` = '{$filter}' OR `T2`.`Mail` LIKE '{$filter}') ";
                    } else {
                        $filter = strtolower($filter);
                        $sql .= "AND (`T2`.`Username` LIKE '%{$filter}%'";

                        if ($this->rlDb->columnExists('First_name', 'accounts')) {
                            $sql .= " OR `T2`.`First_name` LIKE '%{$filter}%'";
                        }

                        if ($this->rlDb->columnExists('Last_name', 'accounts')) {
                            $sql .= " OR `T2`.`Last_name` LIKE '%{$filter}%'";
                        }

                        $sql .= ") ";
                    }
                    break;

                case 'date':
                    $from = Valid::escape($filter['from']);
                    $to   = Valid::escape($filter['to']);

                    if ($from) {
                        $sql .= "AND `T1`.`Created_Date` >= '{$from}' ";
                    }

                    if ($to) {
                        $sql .= "AND `T1`.`Created_Date` <= '{$to}' ";
                    }
                    break;
            }
        }

        $this->rlHook->load('phpGetInvitesSqlWhere', $sql, $agencyID);

        $sql .= 'ORDER BY `Created_Date` DESC ';

        $this->rlHook->load('phpGetInvitesSqlOrder', $sql, $agencyID);

        $sql .= "LIMIT {$start}, {$limit}";

        $this->rlHook->load('phpGetInvitesSqlLimit', $sql, $agencyID, $start, $limit);

        $invites = $this->rlDb->getAll($sql);

        $this->setCountInvites((int) $this->rlDb->getRow('SELECT FOUND_ROWS()')['FOUND_ROWS()']);

        foreach ($invites as &$invite) {
            $invite['Agent'] = $this->rlAccount->getProfile((int) $invite['Agent_ID']);
        }

        return $invites;
    }

    /**
     * Resend the invite to the agent
     *
     * @param int $id
     *
     * @return bool
     */
    public function resendInvite(int $id): bool
    {
        if (!$id) {
            return false;
        }

        $invite    = $this->rlDb->getRow("SELECT * FROM `{db_prefix}agency_invites` WHERE `ID` = {$id}");
        $agency    = $this->rlAccount->getProfile((int) $invite['Agency_ID']);
        $agentLang = $agency['Lang'];

        if ($invite['Agent_ID']) {
            $agent = $this->rlAccount->getProfile((int) $invite['Agent_ID']);
            $agentLang = $agent['Lang'];
        }

        if ($this->sendEmailWithInvite($invite['Agent_Email'], $invite['Invite_Code'], $agency, $agentLang)) {
            $this->rlDb->updateOne([
                'fields' => ['Created_Date' => 'NOW()'],
                'where'  => ['ID' => $invite['ID']],
            ], 'agency_invites');
        }

        return true;
    }

    /**
     * @return int
     */
    public function getCountInvites(): int
    {
        return $this->countInvites;
    }

    /**
     * @param int $countInvites
     */
    public function setCountInvites(int $countInvites): void
    {
        $this->countInvites = $countInvites;
    }

    /**
     * Remove the invite with related agent if exist
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteInvite(int $id): bool
    {
        if (!$id) {
            return false;
        }

        $this->inviteInfo = $this->rlDb->fetch('*', ['ID' => $id], null, 1, 'agency_invites', 'row');
        $agency           = $this->rlAccount->getProfile((int) $this->inviteInfo['Agency_ID']);
        $agent            = $this->rlAccount->getProfile((int) $this->inviteInfo['Agent_ID']);

        $this->rlDb->delete(['ID' => $id], 'agency_invites');

        if ($this->inviteInfo['Agent_ID']) {
            $this->rlDb->updateOne([
                'fields' => ['Agency_ID' => '0'],
                'where'  => ['ID' => $this->inviteInfo['Agent_ID']],
            ], 'accounts');
        }

        if ($agent && $this->inviteInfo['Status'] === 'accepted') {
            $mailTemplate = $this->rlMail->getEmailTemplate('agency_removed_agent', $agent['Lang']);
            $agencyPage   = $agency['Personal_address'];

            $mailTemplate['subject'] = str_replace('{agency}', $agency['Full_name'], $mailTemplate['subject']);
            $find = ['{agency}', '{agent}', '{website}'];
            $replace = [
                $agencyPage
                    ? "<a href=\"{$agencyPage}\"><b>\"{$agency['Full_name']}\"</b></a>"
                    : "<b>\"{$agency['Full_name']}\"</b>",
                $agent['Full_name'],
                "<a href=\"{$this->reefless->getPageUrl('home')}\">{$this->lang['email_site_name']}</a>",
            ];
            $mailTemplate['body'] = str_replace($find, $replace, $mailTemplate['body']);

            return $this->rlMail->send($mailTemplate, $agent['Mail']);
        }

        return true;
    }

    /**
     * Send email with invite to agent
     *
     * @param string $agentEmail
     * @param string $inviteCode
     * @param array  $agency
     * @param string $agentLang
     *
     * @return bool
     */
    protected function sendEmailWithInvite(
        string $agentEmail,
        string $inviteCode,
        array  $agency,
        string $agentLang = ''): bool
    {
        $mailTemplate = $this->rlMail->getEmailTemplate('send_invite_to_agent', $agentLang ?: $agency['Lang']);
        $agencyPage   = $agency['Personal_address'];

        /**
         * @todo - Remove when the bug will be fixed in the Multifield/Location Filter plugin
         */
        if ($agencyPage && false !== strpos($agencyPage, 'locfix/')) {
            $agencyPage = str_replace('locfix/', '/', $agencyPage);
        }

        $this->reefless->preventUrlModifying = true;
        $inviteLink = $this->reefless->getPageUrl('home', null, null, 'agent-invite=' . $inviteCode);

        $find    = ['{agency}', '{website}', '{link}'];
        $replace = [
            $agencyPage
                ? "<a href=\"{$agencyPage}\"><b>\"{$agency['Full_name']}\"</b></a>"
                : "<b>\"{$agency['Full_name']}\"</b>",
            "<a href=\"{$this->reefless->getPageUrl('home')}\">{$this->lang['email_site_name']}</a>",
            "<a href=\"{$inviteLink}\">{$inviteLink}</a>",
        ];

        $mailTemplate['body'] = str_replace($find, $replace, $mailTemplate['body']);

        return $this->rlMail->send($mailTemplate, $agentEmail);
    }

    /**
     * Get count of listing added by all agents
     *
     * @param int  $id       - ID of agency account
     * @param bool $isActive - Count only active listings or all
     *
     * @return int
     */
    public function getAgentsListingsCount(int $id, bool $isActive = true): int
    {
        return (int) $this->rlDb->getRow(
            "SELECT SUM(`Listings_count`) FROM `{db_prefix}accounts`
             WHERE `Agency_ID` = {$id}" . ($isActive ? " AND `Status` = 'active'" : '')
        )['SUM(`Listings_count`)'];
    }

    /**
     * Get count of agents in agency
     *
     * @param int  $id       - ID of agency account
     * @param bool $isActive - Count only active agents or all
     *
     * @return int
     */
    public function getAgentsCount(int $id, bool $isActive = true): int
    {
        return (int) $this->rlDb->getRow(
            "SELECT COUNT(*) FROM `{db_prefix}accounts`
             WHERE `Agency_ID` = {$id}" . ($isActive ? " AND `Status` = 'active'" : '')
        )['COUNT(*)'];
    }

    /**
     * Add SQL conditions for getting all agency listings
     *
     * @param string $sql
     * @param int    $agencyID
     * @param array  $params   - Can have the following values: startAnd, endAnd, table, column
     */
    public function addSqlConditionGetListings(string &$sql, int $agencyID, array $params = [])
    {
        $startAnd = !isset($params['startAnd']) || $params['startAnd'];
        $endAnd   = isset($params['endAnd']) && $params['endAnd'];
        $table    = isset($params['table']) ? (string) $params['table'] : 'T1';
        $column   = isset($params['column']) ? (string) $params['column'] : 'Account_ID';

        $this->rlDb->outputRowsMap = [false, 'ID'];
        $agentsIDs   = $this->rlDb->fetch(['ID'], ['Agency_ID' => $agencyID], null, null, 'accounts');
        $agentsIDs[] = $agencyID;

        if ($startAnd) {
            $sql .= ' AND ';
        }

        $sql .= "`{$table}`.`{$column}` IN ('" . implode("', '", $agentsIDs) . "') ";

        if ($endAnd) {
            $sql .= ' AND ';
        }
    }
}
