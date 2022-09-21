<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLCOMMON.CLASS.PHP
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
use Flynax\Utils\Profile;
use Flynax\Utils\Category;

class rlCommon extends reefless
{
    /**
     * @var block keys
     **/
    public $block_keys;

    /**
     * @var error fields string
     **/
    public $error_fields;

    public $listings = array();

    public $tmp = array();

    /**
     * define blocks existing for template sides
     *
     * @param array $blocks - blocks array
     *
     * @return mixed array
     **/
    public function defineBlocksExist(&$blocks)
    {
        global $l_block_sides, $rlSmarty;

        /* unset all sides */
        foreach ($l_block_sides as $key => $value) {
            unset($blocks[$key]);
        }

        /* set available blocks sides */
        foreach ($blocks as $key => $value) {
            if (array_key_exists($value['Side'], $l_block_sides)) {
                $blocks[$value['Side']] = true;
            }
        }

        /* detect wide mode */
        $wide_mode = true;
        if ($blocks['right'] && $blocks['left']) {
            $wide_mode = false;
        }
        $rlSmarty->assign('wide_mode', $wide_mode);
    }

    /**
     * Get bread crumbs details
     *
     * Prepares the breat crumbs data depending of the current page settings,
     * in case of the page has parent page the method will be called requrcive
     *
     * @param  array $cPage - current page information
     * @return array        - page bread crumbs data
     */
    public function getBreadCrumbs($cPage)
    {
        global $lang;

        $bread_crumbs[] = array(
            'name'  => $lang['pages+name+home'],
            'title' => $lang['pages+title+home'],
        );

        if ($cPage['Parent_ID']) {
            $add_bread_crumb = $this->fetch(
                array('Parent_ID', 'Path', 'Key'),
                array(
                    'ID'     => $cPage['Parent_ID'],
                    'Status' => 'active',
                ),
                null, 1, 'pages', 'row'
            );

            $add_bread_crumb = $GLOBALS['rlLang']->replaceLangKeys($add_bread_crumb, 'pages', array('name', 'title'));

            if ($add_bread_crumb['Parent_ID']) {
                return $this->getBreadCrumbs($bread_crumbs);
            }

            $bread_crumbs[] = array(
                'name'  => $add_bread_crumb['name'],
                'title' => $add_bread_crumb['title'],
                'path'  => $add_bread_crumb['Path'],
            );
        }

        // set proper name for view details page
        if ($cPage['Key'] == 'view_details' && $cPage['Controller'] == 'listing_type') {
            $bc_listing_type = array_search($cPage['Path'], $GLOBALS['pages']);
            $cPage['name'] = $GLOBALS['lang']['pages+name+' . $bc_listing_type];
            $cPage['title'] = $GLOBALS['lang']['pages+title+' . $bc_listing_type];
        }

        $bread_crumbs[] = array(
            'name'  => $cPage['name'],
            'title' => $cPage['title'],
            'path'  => $cPage['Path'],
        );

        return $bread_crumbs;
    }

    /**
     * Build menu items (header, footer, account, inventory)
     */
    public function buildMenus()
    {
        global $rlSmarty, $fields, $main_menu, $tpl_settings, $account_info, $deny_pages, $config, $account_menu,
        $footer_menu, $rlDb;

        $fields = ['ID', 'Page_type', 'Key', 'Path', 'Get_vars', 'Controller', 'No_follow', 'Menus', 'Deny', 'Login'];

        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
            $fields[] = 'Path_' . RL_LANG_CODE;
        }

        $rlDb->setTable('pages');
        $menus = $rlDb->fetch($fields, ['Status' => 'active'], 'ORDER BY `Position`');

        if ($config['multilingual_paths'] && RL_LANG_CODE !== $config['lang']) {
            foreach ($menus as &$menu) {
                $menu['Path'] = $menu['Path_' . RL_LANG_CODE] ?: $menu['Path'];
                unset($menu['Path_' . RL_LANG_CODE]);
            }
        }

        $menus = $GLOBALS['rlLang']->replaceLangKeys($menus, 'pages', ['name', 'title']);

        foreach ($menus as $key => $value) {
            // Generate main menu
            if (in_array(1, explode(',', $value['Menus']))) {
                $main_menu[$value['Key']] = $value;
            }

            // Generate footer menu
            if (in_array(3, explode(',', $value['Menus']))) {
                $footer_menu[$value['Key']] = $value;
            }

            // Generate account menu
            if (in_array(2, explode(',', $value['Menus']))
                && (!in_array($account_info['Type_ID'], explode(',', $value['Deny'])) || !$account_info['Type_ID'])
                && (!in_array($value['Key'], $deny_pages) || !$deny_pages)
            ) {
                if ($value['Key'] == 'my_packages' && $config['membership_module'] && !$config['allow_listing_plans']) {
                    continue;
                }

                if ($value['Controller'] === 'my_agents'
                    && $account_info
                    && !(new Agencies())->isAgency($account_info)
                ) {
                    continue;
                }

                $account_menu[$value['Key']] = $value;
            }

            // Generate inventory menu
            if ($tpl_settings['inventory_menu'] === true) {
                if (in_array(4, explode(',', $value['Menus']))) {
                    $inventory_menu[$value['Key']] = $value;
                }
            }
        }

        $rlSmarty->assign_by_ref('main_menu', $main_menu);
        $rlSmarty->assign_by_ref('footer_menu', $footer_menu);
        $rlSmarty->assign_by_ref('account_menu', $account_menu);

        if ($tpl_settings['inventory_menu'] === true) {
            $rlSmarty->assign_by_ref('inventory_menu', $inventory_menu);
        }
    }

    /**
     * check dynamic form
     *
     * @param array $data - the form data (field => value)
     * @param array $fields - fields
     * @param string $prefix - field name prefix
     * @param bool $admin - admin mode
     *
     * @return array $errors - errors
     **/
    public function checkDynamicForm($data = false, $fields = false, $prefix = 'f', $admin = false)
    {
        global $error_fields, $lang, $languages, $l_deny_files_regexp, $rlValid;

        $errors = false;

        $flStrlenFunc = 'strlen';

        if (function_exists('mb_strlen') && function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
            $flStrlenFunc = 'mb_strlen';
        }

        if (!$data || !$fields) {
            return false;
        }

        foreach ($fields as $fIndex => $fRow) {
            $sFields[$fIndex] = $fields[$fIndex]['Key'];
        }

        $step = 0;

        foreach ($data as $f2 => $v2) {
            $poss = array_search($f2, $sFields);

            if (false !== $poss) {
                switch ($fields[$poss]['Type']) {
                    case 'text':
                        // Miss other types (for ex. "Tours" in escort package)
                        if (!is_string($data[$f2])) {
                            break;
                        }

                        if ($fields[$poss]['Required']) {
                            if ($fields[$poss]['Multilingual']) {
                                $ml_empty = 0;
                                foreach ($data[$f2] as $ml_key => $ml_val) {
                                    $ml_val = trim($ml_val);
                                    if (empty($ml_val)) {
                                        $ml_empty++;
                                        if ($admin) {
                                            $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][{$ml_key}]";
                                        } else {
                                            $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][{$ml_key}],";
                                        }
                                    }
                                }

                                if (count($data[$f2]) == $ml_empty) {
                                    $errors[$step] = str_replace('{field}', $fields[$poss]['name'], $lang['required_multilingual_error']);
                                }
                            } else {
                                $data[$f2] = trim($data[$f2]);
                                $data[$f2] = $fields[$poss]['Condition'] == 'isUrl' && !(bool) preg_match('/https?\:\/\//', $data[$f2]) && !empty($data[$f2]) ? 'http://' . $data[$f2] : $data[$f2];

                                if (empty($data[$f2])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                                } elseif ($fields[$poss]['Condition']) {
                                    if (!$GLOBALS['rlValid']->{$fields[$poss]['Condition']}($data[$f2])) {
                                        $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_incorrect']);
                                    }
                                }
                            }
                        } else {
                            $data[$f2] = trim($data[$f2]);
                            $data[$f2] = $fields[$poss]['Condition'] == 'isUrl' && !(bool) preg_match('/https?\:\/\//', $data[$f2]) && !empty($data[$f2]) ? 'http://' . $data[$f2] : $data[$f2];

                            if ($fields[$poss]['Condition'] == 'isUrl' && (bool) preg_match('#^https?\://$#', $data[$f2])) {
                                //allow http or https as empty value for isUrl condition, it will be truncated after
                            } elseif ($fields[$poss]['Condition'] && !empty($data[$f2])) {
                                if (!$GLOBALS['rlValid']->{$fields[$poss]['Condition']}($data[$f2])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_incorrect']);
                                }
                            }
                        }
                        break;

                    case 'textarea':
                        $limit = (int) $fields[$poss]['Values'];

                        if ($fields[$poss]['Multilingual']) {
                            $ml_empty = 0;
                            foreach ($data[$f2] as $ml_key => $ml_val) {
                                // Trim the string and remove trailing new line code
                                $ml_val = trim($ml_val);
                                $ml_val = str_replace(PHP_EOL, '', $ml_val);

                                // Revert quotes characters to count length properly
                                Flynax\Utils\Valid::revertQuotes($ml_val);

                                /* check for empty value */
                                if (empty($ml_val) && $fields[$poss]['Required']) {
                                    $ml_empty++;
                                    if ($admin) {
                                        $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][{$ml_key}]";
                                    } else {
                                        $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][{$ml_key}],";
                                    }
                                }

                                /* check for exceeded limit */
                                if ($limit && $flStrlenFunc(strip_tags($ml_val)) > $limit) {
                                    if ($admin) {
                                        $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][{$ml_key}]";
                                    } else {
                                        $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][{$ml_key}],";
                                    }

                                    $errors[$step] = str_replace(array('{field}', '{limit}'), array($fields[$poss]['name'] . ' (' . $languages[$ml_key]['name'] . ')', $limit), $lang['error_textarea_limit_exceeded']);
                                }
                            }

                            if (is_array($data[$f2]) && count($data[$f2]) == $ml_empty && $fields[$poss]['Required']) {
                                $errors[$step] = str_replace(
                                    '{field}',
                                    $fields[$poss]['name'],
                                    $lang['required_multilingual_error']
                                );
                            }
                        } else {
                            // Trim the string and remove trailing new line code
                            $data[$f2] = trim($data[$f2]);
                            $data[$f2] = str_replace(PHP_EOL, '', $data[$f2]);

                            // Revert quotes characters to count length properly
                            Flynax\Utils\Valid::revertQuotes($data[$f2]);

                            /* check for empty value */
                            if (empty($data[$f2]) && $fields[$poss]['Required']) {
                                if ($admin) {
                                    $error_fields[] = $prefix . "[{$fields[$poss]['Key']}]";
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}],";
                                }
                                $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                            }

                            /* check for exceeded limit */
                            if ($limit && $flStrlenFunc(strip_tags($data[$f2])) > $limit) {
                                if ($admin) {
                                    $error_fields[] = $prefix . "[{$fields[$poss]['Key']}]";
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}],";
                                }

                                $errors[$step] = str_replace(array('{field}', '{limit}'), array($fields[$poss]['name'], $limit), $lang['error_textarea_limit_exceeded']);
                            }
                        }
                        break;

                    case 'number':
                        $data[$f2] = trim($data[$f2]);

                        if ($fields[$poss]['Required'] || !empty($data[$f2])) {
                            if ($fields[$poss]['Values'] && $data[$f2]) {
                                if (strlen($data[$f2]) > $fields[$poss]['Values']) {
                                    $errors[$step] = str_replace(array('{field}', '{max}'), array('<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', '<span class="field_error">"' . $fields[$poss]['Values'] . '"</span>'), $GLOBALS['lang']['notice_number_incorrect']);
                                }
                            } else {
                                if (empty($data[$f2])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                                }
                            }
                        }
                        break;

                    case 'phone':
                        if ($fields[$poss]['Required'] && ((empty($data[$f2]['code']) && $fields[$poss]['Opt1']) || empty($data[$f2]['area']) || empty($data[$f2]['number']))) {
                            $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_phone_field_error']);

                            if (empty($data[$f2]['code']) && $fields[$poss]['Opt1']) {
                                if ($admin) {
                                    $error_fields[$step] = $prefix . "[{$fields[$poss]['Key']}][code]";
                                    $step++;
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][code],";
                                }
                            }
                            if (empty($data[$f2]['area'])) {
                                if ($admin) {
                                    $error_fields[$step] = $prefix . "[{$fields[$poss]['Key']}][area]";
                                    $step++;
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][area],";
                                }
                            }
                            if (empty($data[$f2]['number'])) {
                                if ($admin) {
                                    $error_fields[$step] = $prefix . "[{$fields[$poss]['Key']}][number]";
                                    $step++;
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][number],";
                                }
                            }
                        } elseif (!$fields[$poss]['Required'] && (((!empty($data[$f2]['area']) && !$fields[$poss]['Condition']) || !empty($data[$f2]['number']) || (!empty($data[$f2]['code']) && $fields[$poss]['Opt1']) /* || (!empty($data[$f2]['ext']) && $fields[$poss]['Opt2'])*/) && (empty($data[$f2]['area']) || empty($data[$f2]['number']) || (empty($data[$f2]['code']) && $fields[$poss]['Opt1'])))) {
                            $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_phone_field_error']);
                        }
                        break;

                    case 'date':
                        if ($fields[$poss]['Default'] == 'single') {
                            if ($fields[$poss]['Required'] && empty($data[$f2])) {
                                $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                            } elseif (!empty($data[$f2])) {
                                if (!(bool) preg_match('/^[0-9]{4}\-[0-1][0-9]\-[0-3][0-9]$/', $data[$f2])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_incorrect']);
                                }
                            }
                        } elseif ($fields[$poss]['Default'] == 'multi') {
                            if ($fields[$poss]['Required'] && (empty($data[$f2]['from']) || empty($data[$f2]['to']))) {
                                $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                                if ($admin) {
                                    $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][from]";
                                    $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][to]";
                                } else {
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][from],";
                                    $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][to],";
                                }
                            } elseif (!empty($data[$f2]['from']) || !empty($data[$f2]['to'])) {
                                if (!(bool) preg_match('/^[0-9]{4}\-[0-1][0-9]\-[0-3][0-9]$/', $data[$f2]['from']) && !empty($data[$f2]['from'])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '" (' . $GLOBALS['lang']['from'] . ')</span>', $GLOBALS['lang']['notice_field_incorrect']);
                                    if ($admin) {
                                        $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][from]";
                                    } else {
                                        $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][from],";
                                    }
                                }
                                if (!(bool) preg_match('/^[0-9]{4}\-[0-1][0-9]\-[0-3][0-9]$/', $data[$f2]['to']) && !empty($data[$f2]['to'])) {
                                    $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '" (' . $GLOBALS['lang']['to'] . ')</span>', $GLOBALS['lang']['notice_field_incorrect']);
                                    if ($admin) {
                                        $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][to]";
                                    } else {
                                        $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][to],";
                                    }
                                }
                            }
                        }

                        break;

                    case 'mixed':
                    case 'price':
                    case 'unit':
                        $data[$f2]['value'] = trim($data[$f2]['value']);
                        if ($fields[$poss]['Required'] && empty($data[$f2]['value'])) {
                            $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_field_empty']);
                            if ($admin) {
                                $error_fields[] = $prefix . "[{$fields[$poss]['Key']}][value]";
                            } else {
                                $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}][value],";
                            }
                        }
                        break;

                    case 'select':
                        $data[$f2] = trim($data[$f2]);
                        if ($fields[$poss]['Required'] && empty($data[$f2])) {
                            $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_select_empty']);
                        }
                        break;

                    case 'checkbox':
                        unset($data[$f2][0]);
                    case 'radio':
                        if ($fields[$poss]['Required'] && empty($data[$f2])) {
                            $errors[$step] = str_replace('{field}', '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>', $GLOBALS['lang']['notice_checkbox_empty']);
                        }
                        break;

                    case 'accept':
                        if (!$data[$f2]) {
                            $errors[] = str_replace(
                                '{field}',
                                $lang['pages+name+' . $fields[$poss]['Default']],
                                $lang['notice_field_not_accepted']
                            );

                            if ($admin) {
                                $error_fields[] = "{$fields[$poss]['Key']}";
                            } else {
                                $this->error_fields .= "{$fields[$poss]['Key']},";
                            }
                        }
                        break;

                    case 'image':
                        // get exist old image (for edit section)
                        $old_image_exist = false;

                        if (!empty($data['sys_exist_' . $f2])) {
                            $old_image_exist = true;
                        }

                        // check format of new uploaded image if old not exist
                        if ($_FILES[$f2]['name']
                            || (empty($_FILES[$f2]['name']) && $fields[$poss]['Required'] && !$old_image_exist)
                        ) {
                            if (empty($_FILES[$f2]['name']) && $fields[$poss]['Required']) {
                                $errors[$step] = str_replace(
                                    array('{field}'),
                                    array('<span class="field_error">"' . $fields[$poss]['name'] . '"</span>'),
                                    $lang['notice_field_empty']
                                );
                            } else {
                                $ext = array_reverse(explode('.', $_FILES[$f2]['name']))[0];

                                if (!$rlValid->isImage($ext) || preg_match($l_deny_files_regexp, $_FILES[$f2]['name'])) {
                                    $errors[$step] = str_replace(
                                        array('{field}', '{ext}'),
                                        array(
                                            '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>',
                                            '<span class="field_error">"' . $ext . '"</span>'
                                        ),
                                        $lang['notice_bad_file_ext']
                                    );
                                }
                            }
                        }
                        break;

                    case 'file':
                        if (!empty($_FILES[$f2]['name']) || ($fields[$poss]['Required'] && empty($data[$f2]))) {
                            if (empty($_FILES[$f2]['name'])) {
                                $errors[$step] = str_replace(
                                    array('{field}'),
                                    array('<span class="field_error">"' . $fields[$poss]['name'] . '"</span>'),
                                    $GLOBALS['lang']['notice_field_empty']
                                );
                            } else {
                                $file_ext = explode('.', $_FILES[$f2]['name']);
                                $file_ext = array_reverse($file_ext);
                                $file_ext = $file_ext[0];

                                if (!$GLOBALS['rlValid']->isFile($fields[$poss]['Default'], $file_ext)
                                    || preg_match($l_deny_files_regexp, $_FILES[$f2]['name'])
                                ) {
                                    $errors[$step] = str_replace(
                                        array('{field}', '{ext}'),
                                        array(
                                            '<span class="field_error">"' . $fields[$poss]['name'] . '"</span>',
                                            '<span class="field_error">"' . $file_ext . '"</span>',
                                        ),
                                        $GLOBALS['lang']['notice_bad_file_ext']
                                    );

                                    // remove tmp file
                                    unset($_FILES[$f2]['name']);
                                }
                            }
                        }
                        break;
                }

                if ($errors[$step]) {
                    $step++;

                    if (!in_array($fields[$poss]['Type'], array('phone'))) {
                        if ($admin) {
                            $error_fields[] = $prefix . "[{$fields[$poss]['Key']}]";
                        } else {
                            $this->error_fields .= $prefix . "[{$fields[$poss]['Key']}],";
                        }
                    }
                } else {
                    unset($errors[$step]);
                }
            }
        }

        /**
         * @since 4.8.2
         */
        $GLOBALS['rlHook']->load('phpCommonCheckDynamicFormBottom', $errors, $error_fields, $data, $fields, $prefix, $admin);

        return $errors;
    }

    /**
     * Adapt returned value
     *
     * @since 4.7.1 - Added $lTypeKey parameter
     *
     * @param array  $field       - Field info
     * @param mixed  $value       - Original value
     * @param string $type        - Field type
     * @param int    $id          - Item id
     * @param bool   $tags        - Use html tags
     * @param bool   $strip_tags  - Strip tags for html fields
     * @param bool   $edit_mode
     * @param string $custom_lang
     * @param int    $account_id
     * @param string $form
     * @param string $lTypeKey    - Key of listing type
     *
     * @return bool|string
     */
    public function adaptValue(
        &$field,
        $value,
        $type        = 'listing',
        $id          = 0,
        $tags        = true,
        $strip_tags  = false,
        $edit_mode   = false,
        $custom_lang = false,
        $account_id  = 0,
        $form        = 'short_form',
        $lTypeKey    = ''
    ) {
        global $lang, $config, $account_info, $rlMembershipPlan, $rlValid, $rlLang;

        $out = false;

        if ($config['membership_module']
            && $type == 'listing'
            && $account_info['ID'] != $account_id
            && !defined('REALM')
        ) {
            $this->loadClass('MembershipPlan');

            if (false !== $out = $rlMembershipPlan->fakeListingValue($field, $value, $form)) {
                return $out;
            }
        }

        $preferred_lang = $custom_lang ?: RL_LANG_CODE;

        if (empty($value) && $field['Type'] != 'bool') {
            return false;
        }

        switch ($field['Type']) {
            case 'price':
                $price     = null;
                $price     = explode('|', $value);
                $currency  = $price[1] ? $lang['data_formats+name+' . $price[1]] : '';
                $showCents = $lTypeKey ? (bool) $GLOBALS['rlListingTypes']->types[$lTypeKey]['Show_cents'] : null;

                if ($config['system_currency_position'] == 'before') {
                    $out = $currency . ' ' . $rlValid->str2money($price[0], $showCents);
                } else {
                    $out = $rlValid->str2money($price[0], $showCents) . ' ' . $currency;
                }
                break;

            case 'mixed':
                if ($field['Key'] == 'escort_rates') {
                    break;
                }

                $df = null;
                $df = explode('|', $value);

                $thousands_sep = $field['Opt2'];
                $value = $field['Opt1'] ? number_format($df[0], null, null, $thousands_sep) : $df[0];

                if (!empty($field['Condition'])) {
                    $out = $value . ' ' . $lang['data_formats+name+' . $df[1]];
                } else {
                    $out = $value . ' ' . $lang[$type . '_fields+name+' . $df[1]];
                }
                break;

            case 'date':
                if ($field['Default'] == 'single') {
                    if (strtotime($value) > 0) {
                        list($d_year, $d_month, $d_day) = explode('-', $value);
                        $d_timestamp = mktime(0, 0, 0, $d_month, $d_day, $d_year);
                        $out = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), $d_timestamp);
                    }
                } else if ($field['Default'] == 'multi') {
                    if (strtotime($value) > 0) {
                        list($d_year, $d_month, $d_day) = explode('-', $value);
                        $d_timestamp = mktime(0, 0, 0, $d_month, $d_day, $d_year);
                        $d_date = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), $d_timestamp);

                        $out = $tags
                        ? ($lang['from'] . ' ' . $d_date . ' ')
                        : $d_date;
                    }

                    $id = (int) $id;
                    if ($id) {
                        $multi_field = $this->getOne(
                            $field['Key'] . '_multi',
                            "`ID` = '{$id}'",
                            $type == 'listing' ? 'listings' : 'accounts'
                        );

                        if (strtotime($multi_field) > 0) {
                            list($t_year, $t_month, $t_day) = explode('-', $multi_field);
                            $t_timestamp = mktime(0, 0, 0, $t_month, $t_day, $t_year);

                            $d_date_to = date(str_replace(array('%', 'b'), array('', 'M'), RL_DATE_FORMAT), $t_timestamp);
                            $out .= $tags
                            ? ($lang['to'] . ' ' . $d_date_to)
                            : ' - ' . $d_date_to;
                        }
                    }
                }
                break;

            case 'text':
                if (in_array($field['Condition'], array('isUrl', 'isDomain'))) {
                    $value = !(bool) preg_match('/https?\:\/\//', $value) ? 'http://' . $value : $value;
                    $out = $value;
                } else {
                    if ($field['Multilingual'] || (is_string($value) && preg_match('/\{\|[\w]{2}\|\}/', $value))) {
                        $out = $this->parseMultilingual($value, $preferred_lang);
                    } else {
                        $out = $value;
                    }
                }

                if ($strip_tags) {
                    $out = strip_tags($out);
                }
                break;

            case 'textarea':
                if ($field['Multilingual'] || (bool) preg_match('/\{\|[\w]{2}\|\}/', $value)) {
                    $out = $this->parseMultilingual($value, $preferred_lang);
                } else {
                    $out = $value;
                }

                if ($field['Condition'] == 'html' && $strip_tags) {
                    $out = strip_tags($out);
                }

                if ($field['Condition'] != 'html') {
                    $out = nl2br($out);
                }
                break;

            case 'phone':
                $out = $this->parsePhone($value, $edit_mode ? false : $field, !(defined('REALM') && REALM == 'admin'));
                break;

            case 'number':
                if ($field['Opt1']) {
                    $thousands_sep = $field['Opt2'] ?: ',';

                    if (is_numeric(strpos($value, '.'))) {
                        $decimals = explode('.', $value);
                        $decimals = (int) strlen($decimals[1]);

                        $out = number_format($value, $decimals, '.', $thousands_sep);
                    } else {
                        $out = number_format($value, null, null, $thousands_sep);
                    }
                } else {
                    $out = $value;
                }
                break;

            case 'bool':
                if ((bool) $value) {
                    $out = $GLOBALS['lang']['yes'];
                } else {
                    $out = $GLOBALS['lang']['no'];
                }
                break;

            case 'select':
                if (!empty($field['Condition'])) {
                    if ($field['Condition'] != 'years') {
                        $out = $GLOBALS['lang']['data_formats+name+' . $value];
                    } else {
                        $out = $value;
                    }
                } else {
                    if ($field['Key'] == 'Category_ID') {
                        $out = '';

                        if ($this->listings[$id] && $this->listings[$id]['name']) {
                            $parent_keys = $this->listings[$id]['Parent_keys'];
                            $last = $this->listings[$id]['name'];
                        } elseif ($id) {
                            $sql = "SELECT `Parent_keys`, `Key` FROM `{db_prefix}categories` AS `T1` ";
                            $sql .= "JOIN `{db_prefix}listings` AS `T2` ON `T2`.`Category_ID` = `T1`.`ID` ";
                            $sql .= "WHERE `T2`.`ID` = " . $id;
                            $cat_info = $this->getRow($sql);

                            $parent_keys = $cat_info['Parent_keys'];
                            $last = $rlLang->getPhrase('categories+name+' . $cat_info['Key'], $preferred_lang);
                        }

                        if (trim($parent_keys)) {
                            foreach (explode(",", $parent_keys) as $k => $parent) {
                                $out .= $rlLang->getPhrase('categories+name+' . $parent, $preferred_lang) . ', ';
                            }
                        }
                        $out .= $last;
                    } else {
                        $out = $lang[$type . '_fields+name+' . $field['Key'] . '_' . $value];
                    }
                }
                break;

            case 'radio':
            case 'checkbox':
                if (!empty($field['Condition'])) {
                    if ($field['Condition'] != 'years') {
                        $vals = explode(',', $value);
                        foreach ($vals as $val_item) {
                            if (!empty($GLOBALS['lang']['data_formats+name+' . $val_item])) {
                                $out .= $GLOBALS['lang']['data_formats+name+' . $val_item] . ', ';
                            }
                        }
                        $out = substr($out, 0, -2);
                    } else {
                        $out = $value;
                    }
                } else {
                    $multi_values = explode(',', $value);

                    if (!empty($multi_values[0])) {
                        foreach ($multi_values as $chKey => $chVal) {
                            $out .= $GLOBALS['lang'][$type . '_fields+name+' . $field['Key'] . '_' . $multi_values[$chKey]] . ', ';
                        }
                        $out = substr($out, 0, -2);
                    }
                }
                break;

            case 'image':
                if (!$strip_tags) {
                    $out = '<img alt="" src="' . RL_FILES_URL . $value . '" />';
                }
                break;

            case 'file':
                if (!$strip_tags) {
                    $out = '<a class="static" href="' . RL_FILES_URL . $value . '">' . $GLOBALS['lang']['download'] . '</a>';
                }
                break;
        }

        /**
         * @since 4.7.1 - Added $lTypeKey parameter
         */
        $GLOBALS['rlHook']->load('adaptValueBottom', $value, $field, $out, $lTypeKey);

        return $out;
    }

    /**
     * fields types adaptation (select, checkBox, radio)
     *
     * @param array $values - type values
     * @param string $table - table name
     * @param string $listing_type - listing type
     *
     **/
    public function fieldValuesAdaptation($fields, $table, $listing_type = false)
    {
        global $config, $rlCache, $edit_mode;

        if (!$GLOBALS['data_formats']) {
            $this->outputRowsMap = 'Key';
            $GLOBALS['data_formats'] = $data_formats = $this->fetch(array('ID', 'Key', 'Order_type'), array('Parent_ID' => 0, 'Status' => 'active'), 'ORDER BY `Key`', null, 'data_formats');
        } else {
            $data_formats = $GLOBALS['data_formats'];
        }

        $GLOBALS['rlHook']->load('phpCommonFieldValuesAdaptationTop', $fields, $table, $listing_type); // >= v4.3

        if (!empty($fields)) {
            foreach ($fields as $index => $value) {
                // if ($fields[$index]['Key'] != 'Category_ID' && $this -> tmp['adapted_fields'][$fields[$index]['Key']]) {
                //      $fields[$index] = $this -> tmp['adapted_fields'][$fields[$index]['Key']];
                // } else {
                if ($fields[$index]['Type'] == 'select' || $fields[$index]['Type'] == 'checkbox' || $fields[$index]['Type'] == 'radio' || $fields[$index]['Type'] == 'mixed') {
                    // bind with data formats
                    if ($data_formats[$fields[$index]['Condition']]) {
                        $format_values = false;

                        if ($fields[$index]['Condition'] == 'years') {
                            $step = 0;
                            for ($i = date('Y') + 2; $i >= 1940; $i--) {
                                $format_values[$step]['name'] = $i;
                                $format_values[$step]['Key'] = $i;

                                $step++;
                            }
                        } else {
                            $format_values = $GLOBALS['rlCategories']->getDF($fields[$index]['Condition'], $data_formats[$fields[$index]['Condition']]['Order_type']);
                        }

                        $fields[$index]['Values'] = $format_values;
                        unset($format_values);
                    }
                    // system fields
                    else if (intval($fields[$index]['ID']) < 0) {
                        switch ($fields[$index]['Key']) {
                            case 'sf_status':
                                $fields[$index]['Values'] = array();
                                $statuses = array('active', 'approval', 'pending', 'incomplete', 'expired');
                                foreach ($statuses as $i => $status) {
                                    $fields[$index]['Values'][$status] = array(
                                        'pName' => $status,
                                    );
                                }
                                break;

                            case 'sf_active_till':
                                $field = $this->getSystemFields('active_till', true);
                                $fields[$index]['Values'] = $field['Values'];
                                break;

                            case 'sf_plan':
                                $this->loadClass('Plan');
                                $tmp_plans = $GLOBALS['rlPlan']->getPlans(array('listing', 'package'));

                                foreach ($tmp_plans as $plan) {
                                    $plans[$plan['ID']] = array(
                                        'pName' => 'listing_plans+name+' . $plan['Key'],
                                    );
                                }
                                unset($tmp_plans);

                                $fields[$index]['Values'] = $plans;
                                break;
                        }
                    } else {
                        $adapted = array();
                        switch ($fields[$index]['Key']) {
                            case 'Category_ID':
                                $fields[$index]['Values'] = $GLOBALS['rlCategories']->getCategories(0, $listing_type);
                                break;

                            case 'posted_by':
                                $this->loadClass('Account');
                                $tmp_account_types = $GLOBALS['rlAccount']->getAccountTypes('visitor');
                                foreach ($tmp_account_types as $tmp_account_type) {
                                    if ($tmp_account_type['Abilities']) {
                                        $adapted[$tmp_account_type['Key']] = array(
                                            'ID'    => $tmp_account_type['Key'],
                                            'pName' => 'account_types+name+' . $tmp_account_type['Key'],
                                        );
                                    }
                                }
                                $fields[$index]['Values'] = $adapted;
                                unset($tmp_account_types, $adapted);
                                break;

                            default:
                                $values = explode(',', $fields[$index]['Values']);

                                if ($fields[$index]['Type'] == 'checkbox') {
                                    $default = explode(',', $fields[$index]['Default']);
                                    $fields[$index]['Default'] = $default;
                                }

                                foreach ($values as $row) {
                                    $adapted[$row]['Key'] = $fields[$index]['Key'] . '_' . $row;
                                    $adapted[$row]['pName'] = $table . '+name+' . $adapted[$row]['Key'];
                                    $adapted[$row]['ID'] = $row;

                                    if ($fields[$index]['Default'] == $row && $fields[$index]['Type'] == 'mixed') {
                                        $adapted[$row]['Default'] = 1;
                                    }
                                }

                                $fields[$index]['Values'] = $adapted;
                                unset($adapted);
                                break;
                        }
                    }

                    // if ($fields[$index]['Key'] != 'Category_ID') {
                    //   $this -> tmp['adapted_fields'][$fields[$index]['Key']] = $fields[$index];
                    // }
                }
                //}
            }
        }

        $GLOBALS['rlHook']->load('phpCommonFieldValuesAdaptationBottom', $fields, $table, $listing_type); // >= v4.3

        unset($data_formats);

        return $fields;
    }

    /**
     * Check parent with enabled subcategories including | recursive method
     *
     * @deprecated 4.8.1
     *
     * @param int $id - category id
     * @param array $mode - check in area
     *
     * @return bool
     */
    public function detectParentIncludes($id, $mode = 'blocks')
    {}

    /**
     * Get list of blocks in page
     *
     * @return array
     */
    public function getBlocks()
    {
        global $page_info, $config, $rlDb, $rlHook;

        $blocks = [];

        if (in_array($page_info['Controller'], ['search_map'])) {
            return $blocks;
        }

        $category = Category::getCurrentCategory();

        // Redefine page ID with listing details page ID, 25 in this case
        if ($page_info['Controller'] == 'listing_type' && $_GET[$config['mod_rewrite'] ? 'listing_id' : 'id']) {
            $page_info['ID'] = 25;
        }

        $select = ['ID', 'Key', 'Side', 'Type', 'Content', 'Tpl', 'Header', 'Position', 'Plugin'];
        $where  = "`Status` = 'active' ";

        if ($category['ID'] && $page_info['ID'] !== 25) {
            $where .= "AND (FIND_IN_SET('{$category['ID']}', `Category_ID`) > 0 OR `Cat_sticky` = '1'";

            if ($category['Parent_IDs']) {
                $parentIDsCondition = '';
                foreach (explode(',', $category['Parent_IDs']) as $parentID) {
                    $parentIDsCondition .= "(FIND_IN_SET('{$parentID}', `Category_ID`) > 0 ";
                    $parentIDsCondition .= "AND `Subcategories` = '1') OR ";
                }
                $parentIDsCondition = rtrim($parentIDsCondition, ' OR ');

                $where .= ' OR (' . $parentIDsCondition . '))';
            } else {
                $where .= ')';
            }
        } else {
            $where .= "AND (FIND_IN_SET('{$page_info['ID']}', `Page_ID`) > 0 OR `Sticky` = '1') ";
        }

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpGetBlocksPre', $select, $where);

        $sql = "SELECT `" . implode("`, `", $select) . "` FROM `{db_prefix}blocks` ";
        $sql .= "WHERE {$where} ";
        $sql .= "ORDER BY `Position`";
        $tmpBlocks = $rlDb->getAll($sql);

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpGetBlocksAfter', $tmpBlocks, $select, $where);

        if (!empty($tmpBlocks)) {
            foreach ($tmpBlocks as $key => $value) {
                $block_keys[$value['Key']] = true;
                $blocks[$value['Key']]     = $value;

                if ($value['Type'] == 'html') {
                    $blocks[$value['Key']]['Content'] = $GLOBALS['rlLang']->getPhrase(
                        'blocks+content+' . $value['Key'],
                        null,
                        false,
                        true
                    );
                }
            }

            unset($tmpBlocks);
            $this->block_keys = $block_keys;
        }

        /**
         * @since 4.8.1
         */
        $rlHook->load('phpGetBlocks', $this, $blocks);

        return $blocks;
    }

    /**
     * check messages exist
     *
     * @return int - count of messages
     **/
    public function checkMessages()
    {
        global $account_info;

        $account_id = (int) $account_info['ID'] ?: false;
        $additional_sql = '';

        if (!$account_id) {
            return false;
        }

        if ($GLOBALS['rlAccount']->isAdmin()) {
            $from = 'Admin';

            $additional_sql = " AND `To` = 0";
        } else {
            $from = 'To';
        }

        $sql = "SELECT COUNT(`ID`) AS `Count` FROM `{db_prefix}messages` ";
        $sql .= "WHERE `{$from}` = {$account_id} AND `Status` = 'new' {$additional_sql}";
        $count = $this->getRow($sql);

        return intval($count['Count']);
    }

    /**
     * get children | requrcive method
     *
     * @param int $id - main item id
     * @param array $items - all items array
     * @param int $target - target item
     *
     * @return bool - search result
     **/
    public function checkRelation($id, $items, $target)
    {
        if ($parent = $items[$id]) {
            if ($parent == $target) {
                return true;
            }

            if ($poss = $items[$parent]) {
                if ($poss == $target) {
                    return true;
                } else {
                    return $this->checkRelation($poss, $items, $target);
                }
            }
        } else {
            return false;
        }
    }

    /**
     * get available hooks
     *
     *   NOTE: DEPRECATED
     **/
    public function getHooks()
    {
        $this->getInstalledPluginsList();
    }

    /**
     * Fetch all installed and active plugins.
     *
     * @since 4.5.1
     * @return array - [key => version]
     **/
    public function getInstalledPluginsList()
    {
        $this->setTable('plugins');
        $this->outputRowsMap = array('Key', 'Version');
        $plugins = $this->fetch($this->outputRowsMap, array('Status' => 'active'));

        /* support for old logic */
        $GLOBALS['aHooks'] = $GLOBALS['rlHook']->aHooks = &$plugins;

        if (is_object($GLOBALS['rlSmarty'])) {
            $GLOBALS['rlSmarty']->assign('aHooks', $GLOBALS['aHooks']);
        }
        /* support end */

        return $plugins;
    }

    /**
     * @since 4.5.1
     *
     * simulate the additional category box
     *
     **/
    public function simulateCatBlocks()
    {
        global $rlListingTypes, $blocks, $rlCache, $rlSmarty, $rlCategories, $page_info, $rlHook;

        foreach ($rlListingTypes->types as $key => $value) {
            if ($blocks['ltcb_' . $value['Key']] && in_array($page_info['ID'], explode(',', $value['Ablock_pages']))) {
                $cat_blocks[$value['Ablock_position']][] = $key;
                $categories[$key] = $rlCategories->getCategories(0, $key, null, $value['Cat_show_subcats']);
            }
        }

        if ($cat_blocks) {
            $rlSmarty->assign_by_ref('box_categories', $categories);

            foreach ($cat_blocks as $side => $types) {
                if ($types[0] && $blocks['ltcb_' . $types[0]]) {
                    $blocks['ltcb_' . $types[0]]['Content'] = '{include file="blocks"|cat:$smarty.const.RL_DS|cat:"categories_block.tpl" types="' . implode(',', $types) . '"}';
                }

                if (count($types) > 1) {
                    foreach ($types as $key => $type) {
                        if ($key > 0) {
                            unset($blocks['ltcb_' . $type]);
                        }
                    }
                }
            }

            /**
             * @since 4.4
             *
             * @param $cat_blocks @since 4.5.1
             **/
            $rlHook->load('simulateCatBlocks', $blocks, $categories, $cat_blocks);

            $this->defineBlocksExist($blocks);
        }
    }

    /**
     * Custom php function strlen
     *
     * @param string &$string - string for checking
     * @param string $sign - sign [<,>,<=,>=,==,!=]
     * @param int $len - length
     *
     * @return bool or false
     **/
    public function strLen(&$string, $sign = '<', $len = 3)
    {
        if (function_exists('mb_strlen')) {
            eval("\$res = ( mb_strlen(\$string) {$sign} \$len );");
            return $res;
        }

        eval("\$res = ( strlen(\$string) {$sign} \$len );");
        return $res;
    }

    /**
     * Description
     * @param string $key
     * @param bool $assoc
     * @return mixed
     */
    public function getSystemFields($key = null, $assoc = false)
    {
        global $lang;

        // prepare system fields
        $system_fields = array(
            '-1' => array(
                'ID'   => -1,
                'Key'  => 'sf_status',
                'Type' => 'select',
            ),
            '-2' => array(
                'ID'   => -2,
                'Key'  => 'sf_active_till',
                'Type' => 'select',
            ),
            '-3' => array(
                'ID'   => -3,
                'Key'  => 'sf_plan',
                'Type' => 'select',
            ),
            '-4' => array(
                'ID'   => -4,
                'Key'  => 'sf_featured',
                'Type' => 'bool',
            ),
        );

        if (!is_null($key) && $key != "") {
            // mapping if necessary
            if ($assoc === true) {
                $f_map = array(
                    'status'      => '-1',
                    'active_till' => '-2',
                    'plan'        => '-3',
                    'featured'    => '-4',
                );
                $key = $f_map[$key];
            }

            if ($key === '-2') {
                $system_fields[$key]['Values'] = array(
                    1  => array('pName' => 'sf_active_till_1days'),
                    2  => array('pName' => 'sf_active_till_2days'),
                    3  => array('pName' => 'sf_active_till_3days'),
                    7  => array('pName' => 'sf_active_till_1weeks'),
                    14 => array('pName' => 'sf_active_till_2weeks'),
                    21 => array('pName' => 'sf_active_till_3weeks'),
                    30 => array('pName' => 'sf_active_till_1months'),
                    60 => array('pName' => 'sf_active_till_2months'),
                    90 => array('pName' => 'sf_active_till_3months'),
                );

                if (!$GLOBALS['config']) {
                    $this->buildActiveTillPhrases();
                }
            }
            return $system_fields[$key] ?: false;
        }
        return $system_fields;
    }

    public function buildActiveTillPhrases()
    {
        global $lang;

        for ($i = 1; $i <= 3; $i++) {
            $lang['sf_active_till_' . $i . 'days'] = sprintf($lang['sf_active_till_ndays'], $i);
            $lang['sf_active_till_' . $i . 'weeks'] = sprintf($lang['sf_active_till_nweeks'], $i);
            $lang['sf_active_till_' . $i . 'months'] = sprintf($lang['sf_active_till_nmonths'], $i);
        }
    }

    public function tplBlocks()
    {
        global $page_info, $lang, $blocks, $rlCommon, $rlSmarty, $account_info, $search_results_url, $advanced_search_url, $rlValid, $rlListingTypes;

        if (in_array($page_info['Controller'], array('account_type', 'my_messages'))) {
            $account_address = (int) $_GET['id'] ? (int) $_GET['id'] : $_GET['nvar_1'];
            $rlValid->sql($account_address);
            if ($account_address) {
                $account_id = is_numeric($account_address) ? $account_address : $this->getOne('ID', "`Own_address` = '{$account_address}'", 'accounts');
            }
        }

        if ($page_info['Controller'] != 'account_type' || ($account_id && $page_info['Controller'] == 'account_type')) {
            unset($blocks['account_alphabetic_filter'], $blocks['account_search']);
            $recount = true;
        }

        if (in_array($page_info['Controller'], array('account_type', 'my_messages'))) {
            if ($account_id < 0) {
                unset($blocks['account_page_location']);
                $blocks['account_page_info']['name'] = $lang['website_visitor'];

                $recount = true;
            } elseif (!$account_id) {
                unset($blocks['account_page_info'], $blocks['account_page_location']);
                $recount = true;
            }
        } else {
            unset($blocks['account_page_info'], $blocks['account_page_location']);
            $recount = true;
        }

        if ($page_info['Controller'] != 'listing_type' ||
            ($page_info['Controller'] == 'listing_type' && ($_GET['listing_id'] || $_GET['id'] || $_GET['nvar_1'] == $search_results_url || $_GET['nvar_1'] == $advanced_search_url))) {
            foreach ($rlListingTypes->types as &$l_type) {
                if ($blocks['ltcategories_' . $l_type['Key']]) {
                    unset($blocks['ltcategories_' . $l_type['Key']]);
                    $recount = true;
                }
            }
        }

        if (!$page_info['Controller'] == 'profile' || !defined('IS_LOGIN')) {
            unset($blocks['my_profile_sidebar']);
            $recount = true;
        } else {
            $blocks['my_profile_sidebar']['name'] = $account_info['Full_name'];
        }

        if ($recount) {
            $rlCommon->defineBlocksExist($blocks);
        }

        if ($page_info['Controller'] == 'registration') {
            $rlSmarty->assign('no_h1', true);
        }
    }

    /**
     * Remove an "Search in My Ads" box from stack
     *
     * @since 4.5
     * @param mixed $ltype_key    - listing type key
     * @param mixed $box_key      - system box key in a stack
     * @param bool  $define_boxes - trigger to allow or not execute defineBlocksExist method
     */
    public function removeSearchInMyAdsBox($ltype_key = false, $box_key = false, $define_boxes = true)
    {
        global $blocks, $block_keys;

        if (!$ltype_key && !$box_key) {
            return;
        }

        if ($ltype_key) {
            $box_key = 'ltma_' . $ltype_key;
        }

        unset($block_keys[$box_key], $blocks[$box_key]);

        if ($define_boxes !== false) {
            $this->defineBlocksExist($blocks);
        }
    }

    /**
     * Remove all "Search in My Ads" boxes from stack
     *
     * @since 4.5
     * @see removeSearchInMyAdsBox
     */
    public function removeAllSearchInMyAdsBoxes()
    {
        foreach ($GLOBALS['block_keys'] as $key => $value) {
            if (preg_match('/^ltma_/', $key) || $key == 'search_in_my_ads') {
                $this->removeSearchInMyAdsBox(false, $key, false);
            }
        }
        $this->defineBlocksExist($GLOBALS['blocks']);
    }

    /**
     * Remove all boxes related to messages on My Messages page
     *
     * @since 4.9.0
     */
    public function removeBoxesOnMyMessagesPage()
    {
        unset($GLOBALS['blocks']['account_page_info']);
        $this->defineBlocksExist($GLOBALS['blocks']);
    }

    /**
     * prepare special content for the home page
     *
     * @since 4.5.1
     */
    public function homePageSpecialContent()
    {
        global $tpl_settings, $blocks, $rlSmarty, $config;

        if (($tpl_settings['home_page_gallery'] && !$config['home_gallery_box'])
            && ($tpl_settings['home_page_special_block'] && !$config['home_special_box'])
        ) {
            return;
        }

        // define featured listings box to apply gallery affect to
        if ($tpl_settings['home_page_gallery']) {
            $content  = '';
            $demoMode = true;

            if ($config['home_gallery_box'] && $blocks[$config['home_gallery_box']]['Content']) {
                $content = $blocks[$config['home_gallery_box']]['Content'];
                unset($blocks[$config['home_gallery_box']]);
            }

            $GLOBALS['rlCommon']->defineBlocksExist($blocks);

            // box created via Listings_box plugin
            if (false !== strpos($config['home_gallery_box'], 'listing_box_')) {
                $demoMode = false;

                ob_start();
                eval($content);
                ob_end_clean();

                if ($rlSmarty->get_template_vars('listings_box')) {
                    $rlSmarty->assign(
                        'gallary_content',
                        "{include file='blocks/featured.tpl' listings=\$listings_box}"
                    );
                } else {
                    unset($content);
                }
            }

            if (!$content) {
                $content = "{include file='blocks/featured.tpl' ";
                $content .= "listings=\$featured_gallery type='demo_gallery_type' field='condition' value='2'}";

                $GLOBALS['rlListingTypes']->types['demo_gallery_type'] = array(
                    'Photo'  => true,
                    'Status' => 'approval',
                );
            }

            preg_match('/listings=\\$([^\\s]+)/', $content, $matches);

            if ($matches[1]) {
                $rlSmarty->assign('gallary_content', $content);
                $featured_listings = $rlSmarty->get_template_vars($matches[1]);
            }

            if (!$featured_listings) {
                $array = array(
                    'custom'        => true,
                    'listing_title' => 'Demo Gallery, please create feature listings',
                );

                $featured_listings = array_fill(0, 5, $array);
                $rlSmarty->assign_by_ref($matches[1], $featured_listings);
                $rlSmarty->assign('demo_gallery', $demoMode);
            }
        }

        // Prepare the box data for special area on the home page
        if ($tpl_settings['home_page_special_block'] && $config['home_special_box'] && $blocks[$config['home_special_box']]) {
            $special_block = $blocks[$config['home_special_box']];
            unset($blocks[$config['home_special_box']]);

            $this->defineBlocksExist($blocks);

            $special_block['Side'] = 'left';
            $rlSmarty->assign_by_ref('home_page_special_block', $special_block);
        }
    }

    /**
     * Installation of the existence of sidebar in templates
     *
     * @since 4.6.0
     */
    public function defineSidebarExists()
    {
        global $tpl_settings, $page_info, $side_bar_exists;

        $side_bar_exists = false;

        if ($GLOBALS['blocks']['left']
            || (is_array($tpl_settings['sidebar_sticky_pages'])
                && in_array($page_info['Controller'], $tpl_settings['sidebar_sticky_pages'])
            )
            || $tpl_settings['sidebar_sticky_pages'] === 'all'
        ) {
            $side_bar_exists = true;
        }

        if (is_array($tpl_settings['sidebar_restricted_pages'])
            && in_array($page_info['Controller'], $tpl_settings['sidebar_restricted_pages'])
        ) {
            $side_bar_exists = false;
        }

        $GLOBALS['rlSmarty']->assign_by_ref('side_bar_exists', $side_bar_exists);
    }

    /**
     * Definition of the existence the bread crumbs on the page
     *
     * @since 4.8.2
     */
    public function defineBreadCrumbsExists()
    {
        global $bread_crumbs_exists, $bread_crumbs;

        $bread_crumbs_exists = $bread_crumbs && count($bread_crumbs) > 2 && $GLOBALS['pageInfo']['Key'] != 'home';
        $GLOBALS['rlSmarty']->assign_by_ref('bread_crumbs_exists', $bread_crumbs_exists);
    }

    /**
     * Page Meta Tags
     *
     * Wrapper for the set of functions adding meta tags:
     *  - canonical
     *  - rel-prev next
     *  - robots
     *  - hreflang
     *
     * @since 4.6.0
     **/
    public function pageMetaTags()
    {
        global $page_info, $bread_crumbs;

        $this->metaRobots();
        $this->metaRelPrevNext();
        $this->metaRelCanonical();
        $this->hreflangTags();
        $this->metaPagination();

        $GLOBALS['rlHook']->load('phpMetaTags', $page_info);

        /**
         * @since 4.7.0 - Fourth parameter $single_title_controllers removed
         *                Hook moved from pageTitle()
         */
        $GLOBALS['rlHook']->load('pageTitle', $page_info['title'], $bread_crumbs);
    }

    /**
     * Meta Robots
     *
     * adds meta robots tags
     * please note the main rules for robots are already placed in the ROBOTS.TXT files
     * the current function is to handle specific cases and for plugins
     *
     * possible values for robots tags are
     * NOINDEX, FOLLOW or just NOINDEX  - skip or exclude page from indexing (if already added), follow links on the page
     * INDEX, NOFOLLOW or just NOFLOOW  - add page to index, but not follow links on the page
     * NOINDEX, NOFOLLOW                - both no
     *
     * @since 4.6.0
     **/
    public function metaRobots()
    {
        global $page_info, $search_results_url, $advanced_search_url, $listing_type;

        // Default state
        $page_info['robots']['noindex'] = false;
        $page_info['robots']['nofollow'] = false;

        // Set noindex, follow for expired listing pages
        if ($page_info['Key'] == 'view_details'
            && $GLOBALS['listing_data']['Listing_expired']
        ) {
            $page_info['robots']['noindex'] = true;
        }

        // Set noindex, nofollow for 404 pages
        if ($page_info['Controller'] == '404' || $GLOBALS['sError']) {
            $page_info['robots']['noindex'] = true;
            $page_info['robots']['nofollow'] = true;
        }

        // Do not allow search results to be indexed
        if (array_search($search_results_url, $_GET, true)
            || isset($_GET[$search_results_url])
        ) {
            $search_results = true;
        } elseif (
            ($_GET['nvar_1'] == $advanced_search_url || isset($_GET[$advanced_search_url]))
            && $listing_type['Advanced_search']
        ) {
            $search_results = true;
        } elseif ($page_info['Controller'] == 'search') {
            $search_results = true;
        }

        if ($search_results) {
            $page_info['robots']['noindex'] = true;
            $page_info['robots']['nofollow'] = false;
        }

        // Do not index pages with applied sorting
        if ($_GET['sort_by'] || $_GET['sort_type']) {
            $page_info['robots']['nofollow'] = true;
            $page_info['robots']['noindex'] = true;
        }

        // Clear robots array if variables are default
        if (!$page_info['robots']['noindex'] && !$page_info['robots']['nofollow']) {
            unset($page_info['robots']);
        }
    }

    /**
     * Rel Prev Next
     *
     * Add rel-prev, rel-next attributes to page meta tags for proper pages indexing
     *
     * @since 4.6.0
     **/
    public function metaRelPrevNext()
    {
        global $page_info, $config;

        if ($page_info['robots']['noindex']) {
            return false;
        }

        $page_url = SEO_BASE;
        $add_url = $custom = '';

        switch ($page_info['Controller']) {
            case 'listing_type':
                $item = $GLOBALS['category']['ID'] > 0 ? $GLOBALS['category'] : $GLOBALS['listing_type'];
                $count = $item['Count'];
                $add_url = $item['Path'] ?: '';

                $per_page = $config['listings_per_page'];
                break;

            case 'account_type':
                if ($GLOBALS['account']) {
                    // Listings paging
                    $count = $GLOBALS['account']['Listings_count'];
                    $per_page = $config['listings_per_page'];
                    $custom = $GLOBALS['account']['Own_address'];
                } else {
                    // Dealers paging
                    if ($_GET['nvar_1'] == $GLOBALS['search_results_url']
                        || isset($_GET[$GLOBALS['search_results_url']])
                    ) {
                        return false; //Search page excluded from indexing
                    } else {
                        $count = $GLOBALS['pInfo']['calc_alphabet'];
                    }
                    if ($GLOBALS['request_char']) {
                        $add_url = $GLOBALS['char'];
                    }
                    $per_page = $config['dealers_per_page'];
                }
                break;

            case 'news':
                $count = $page_info['calc'];
                $per_page = $config['news_at_page'];
                break;

            case 'recently_added':
                $item = $GLOBALS['requested_type'];
                $count = $GLOBALS['pInfo']['calc'];

                $per_page = $config['listings_per_page'];
                break;
        }

        /**
         * @since 4.7.0
         */
        $GLOBALS['rlHook']->load('phpMetaRelPrevNext', $add_url, $custom, $count, $per_page);

        $paging_tpls = $this->buildPagingUrlTemplate($add_url, $custom);
        $current_page = $page_info['current'] ?: $_GET['pg'];

        if ($count && $count > $per_page) {
            $next_pg = $current_page ? $current_page + 1 : 2;
            $prev_pg = $current_page > 1 ? $current_page - 1 : 0;

            if ($count > $per_page * $current_page) {
                $page_info['rel_next'] = str_replace('[pg]', $next_pg, $paging_tpls['tpl']);
            }

            if ($current_page == 2) {
                $page_info['rel_prev'] = $paging_tpls['first'];
            } elseif ($current_page) {
                $page_info['rel_prev'] = str_replace('[pg]', $prev_pg, $paging_tpls['tpl']);
            }
        }
    }

    /**
     * Page Info Canonical
     *
     * add canonical to the same page without any queries
     *
     * @since 4.6.0
     **/
    public function metaRelCanonical()
    {
        global $page_info;

        if (!$GLOBALS['config']['mod_rewrite'] || $page_info['robots']['noindex'] || $page_info['Key'] == '404') {
            return false;
        }

        $affected = false;
        $request_url = $_SERVER['REQUEST_URI'];
        $request_host = $GLOBALS['domain_info']['scheme'] . '://' . $_SERVER['HTTP_HOST'];

        if (!$page_info['canonical'] && ($_GET['pg'] > 1 || $GLOBALS['pInfo']['current'] > 1 || $page_info['current'] > 1)) {
            $request_url = preg_replace('/(\/index[0-9]+(\.html|\/))/', '$2', $request_url);
            $affected = true;
        }

        if (false !== strpos($_SERVER['REQUEST_URI'], '?')) {
            $request_url = preg_replace('/\?(.+)$/', '', $request_url);
            $affected = true;
        }

        if ((!$page_info['canonical'] && $GLOBALS['config']['force_canonical']) || $affected) {
            $page_info['canonical'] = $request_host . $request_url;
        }
    }

    /**
     * Hreflang Tags
     *
     * Add hreflang meta tags to the header
     *
     * @since 4.6.0
     */
    public function hreflangTags()
    {
        global $languages, $page_info, $reefless, $category, $account;

        if (count($languages) == 1) {
            return;
        }

        $hreflang = [];

        foreach ($languages as $code => $langItem) {
            if ($page_info['Key'] == 'view_details') {
                $hreflang[$code] = $reefless->getListingUrl($GLOBALS['listing_data'], $code);
            } else {
                if ($category && $category['ID']) {
                    $hreflang[$code] = $reefless->getCategoryUrl($category, $code);
                } else {
                    if ($page_info['Controller'] === 'account_type' && $account && $account['ID']) {
                        $hreflang[$code] = Profile::getPersonalAddress($account['ID'], $account, $code);
                    } else {
                        if ($page_info['Controller'] === 'edit_listing' && $_GET['id']) {
                            $url = $reefless->getPageUrl($page_info['Key'], null, $code, 'id=' . $_GET['id']);
                        } else {
                            $url = $reefless->getPageUrl($page_info['Key'], null, $code);
                        }

                        $hreflang[$code] = $url;
                    }
                }
            }
        }

        $GLOBALS['rlSmarty']->assign_by_ref('hreflang', $hreflang);
    }

    private function metaPagination()
    {
        global $page_info;

        $page = intval(strpos($_GET['nvar_1'], 'index') === 0
        ? str_replace('index', '', $_GET['nvar_1'])
        : $_GET['pg']);

        if ($page) {
            $postfix = str_replace('{page}', $page, $GLOBALS['lang']['title_page_part']);

            if ($page_info['meta_description']) {
                $page_info['meta_description'] .= $postfix;
            }

            if ($page_info['title']) {
                $page_info['title'] .= $postfix;
            }
        }
    }

    /**
     * Build Paging Url Template
     *
     * Prepare template (pattern) to be used for pagination and rel-prev,next functions
     *
     * @since 4.8.0 - $customSubdomain param added
     * @since 4.6.0
     *
     * @param $add_url         - add url - e.g. category path
     * @param $custom          - custom url that may replace page path
     * @param $method          - submit method, get or post
     * @param $var             - additional url vars
     * @param $customSubdomain - use custom url part on subdomain
     *
     * @return array - array containing first and tpl items
     **/
    public function buildPagingUrlTemplate($add_url = '', $custom = '', $method = false, $var = false, $customSubdomain = false)
    {
        global $config, $page_info, $domain_info;

        $base = rtrim(SEO_BASE, 'index.php');

        /**
         * @since 4.6.1
         */
        $GLOBALS['rlHook']->load('phpBuildPagingTemplate', $add_url, $custom, $method, $var);

        if ($config['mod_rewrite']) {
            if ($custom) {
                if ($customSubdomain) {
                    $first_url = $domain_info['scheme'] . '://' . $custom . $domain_info['domain'];
                    $first_url .= $config['lang'] != RL_LANG_CODE ? ('/' . RL_LANG_CODE) : '';

                    $tpl_url = $first_url;
                } else {
                    $first_url = $base . $custom;
                    $tpl_url = $first_url;
                }
            } else {
                $first_url = $base . $page_info['Path'];
                $tpl_url = $base . $page_info['Path'];
            }

            if ($add_url) {
                $first_url .= '/' . $add_url;
                $tpl_url .= '/' . $add_url;
            }

            // CategoryFilter trailing html bug fix
            if (is_numeric(strpos($add_url, ':')) || $custom) {
                $first_url .= '/';
            } else {
                $first_url .= '.html';
            }

            $tpl_url .= '/index[pg].html';

            if ($method == 'get') {
                preg_match('/^([^\?]*)\?/', $_SERVER['REQUEST_URI'], $matches);
                if ($matches[0]) {
                    $request_string = preg_replace('/^([^\?]*)\?/', '', $_SERVER['REQUEST_URI']);
                    $first_url .= '?' . $request_string;
                    $tpl_url .= '?' . $request_string;
                }
            }
        } else {
            $query_string = preg_replace('/(page\=[^\?\&]+)/', '', $_SERVER['QUERY_STRING']);
            $query_string = preg_replace('/(\&?\??pg\=[^\?\&]+)/', '', $query_string);

            $first_url = $base . 'index.php?page=' . $page_info['Path'] . '&pg=1';
            $tpl_url = $base . 'index.php?page=' . $page_info['Path'] . '&pg=[pg]';

            if ($add_url) {
                $first_url .= $var ? '&' . $var . '=' . $add_url : '&' . $add_url;
                $tpl_url .= $var ? '&' . $var . '=' . $add_url : '&' . $add_url;
            }
        }

        return array('first' => $first_url, 'tpl' => $tpl_url);
    }

    /**
     * Add names, titles and etc. to global system variables
     *
     * @since 4.8.2
     */
    public function setNames()
    {
        global $page_info, $blocks, $rlLang, $rlListingTypes, $rlAccountTypes, $rlHook;

        $rlHook->load('phpSetNamesTop');

        $page_info = $rlLang->replaceLangKeys(
            $page_info,
            'pages',
            ['name', 'title', 'meta_description', 'meta_keywords', 'h1']
        );

        $blocks = $rlLang->replaceLangKeys($blocks, 'blocks', ['name']);

        $rlListingTypes->types = $rlLang->replaceLangKeys($rlListingTypes->types, 'listing_types', ['name']);
        $rlAccountTypes->types = $rlLang->replaceLangKeys($rlAccountTypes->types, 'account_types', ['name', 'desc']);

        $rlHook->load('phpSetNamesBottom');
    }
}
