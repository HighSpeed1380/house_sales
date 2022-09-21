<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLACTIONS.CLASS.PHP
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

class rlActions extends reefless
{
    /**
     * @var language class object
     **/
    public $rlLang;

    /**
     * @var validator class object
     **/
    public $rlValid;

    /**
     * @var configuration class object
     **/
    public $rlConfig;

    /**
     * @var photoSaveOriginal
     **/
    public $photoSaveOriginal = false;

    /**
     * @var photoOriginal
     **/
    public $photoOriginal = false;

    /**
     * class constructor
     **/
    public function __construct()
    {
        global $rlLang, $rlValid, $rlConfig;

        $this->rlLang = &$rlLang;
        $this->rlValid = &$rlValid;
        $this->rlConfig = &$rlConfig;
    }

    /**
     * delete item
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $table - table name
     * @param string $options - aditional deleting options
     * @param string $limit - rows number | DEPRECATED
     * @param string $key - main item key or ID
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function delete($fields = false, $table = false, $options = false, $limit = 1/*deprecated*/, $key = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = false)
    {
        if (!is_array($table)) {
            $table = array($table);
        }

        if ($GLOBALS['config']['trash']) {
            $this->trash($fields, $table, $key, $lang_keys, $className, $deleteMethod, $restoreMethod, $plugin);
            $this->action = "dropped";
        } else {
            $this->remove($fields, $table, $key, $options, $lang_keys, $className, $deleteMethod, $restoreMethod, $plugin);
            $this->action = "deleted";
        }
    }

    /**
     * remove/delete rows from DB
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $table - table name
     * @param string $key - main item key or ID
     * @param string $options - aditional deleting options
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function remove($fields = false, $table = false, $key = false, $options = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = '')
    {
        if (!is_array($fields)) {
            $msg = "remove method can not be run, incorrect structure of \$data parameter";
            trigger_error($msg, E_WARNING);
            $GLOBALS['rlDebug']->logger($msg);

            return false;
        }

        if (empty($table)) {
            $msg = "remove method can not be run, database table does not chose";
            trigger_error($msg, E_WARNING);
            $GLOBALS['rlDebug']->logger($msg);

            return false;
        }

        foreach ($table as $tbl) {
            $sql = "DELETE FROM `" . RL_DBPREFIX . $tbl . "` WHERE ";

            foreach ($fields as $field => $value) {
                if ($table[0] == 'accounts' && $tbl != 'accounts' && $field == 'ID') {
                    $field = "Account_ID";
                }

                $criterion .= "`{$field}` = '{$value}' AND ";
                $sql .= "`{$field}` = '{$value}' AND ";
            }

            if ($options != null && !empty($options) && $tbl != 'lang_keys') {
                $sql .= $options;
            } else {
                $sql = substr($sql, 0, -4);
            }


            $sql .= " LIMIT 1";

            $this->query($sql);
        }

        // delete langusge's keys
        if (!empty($lang_keys)) {
            foreach ($lang_keys as $lKey => $lVal) {
                $d_sql = "DELETE FROM `{db_prefix}lang_keys` WHERE `Key` = '{$lang_keys[$lKey]['Key']}'";
                $this->query($d_sql);
            }
        }

        // call delete method
        if ($className && $deleteMethod) {
            $this->loadClass($className, null, $plugin);
            $className = 'rl' . $className;

            if (!method_exists($className, $deleteMethod)) {
                $GLOBALS['rlDebug']->logger("There are not such method ({$deleteMethod}) in loaded class ({$className})");
                return false;
            }

            global $$className;
            $$className->$deleteMethod($key);
        }

        return true;
    }

    /**
     * drop the item to the trash box
     *
     * @param array $fields - data array: array( 'field' => 'value' )
     * @param array $tables - system tables
     * @param string $key - main item key or ID
     * @param array $lang_keys - languages phrase's keys
     * @param string $className - class name to search methods for
     * @param string $deleteMethod - delete method name
     * @param string $restoreMethod - restore method name
     * @param string $plugin - plugin key
     *
     * @return bool
     **/
    public function trash($fields = false, $tables = false, $key = false, $lang_keys = false, $className = false, $deleteMethod = false, $restoreMethod = false, $plugin = false)
    {
        if (!is_array($fields)) {
            $msg = "trash method can not be run, incorrect structure of \$fields parameter";
            trigger_error($msg, E_WARNING);
            $GLOBALS['rlDebug']->logger($msg);
            return false;
        }

        if (empty($tables)) {
            $msg = "trash method can not be run, System zones does not chose";
            trigger_error($msg, E_WARNING);
            $GLOBALS['rlDebug']->logger($msg);
            return false;
        }

        foreach ($tables as $table) {
            $sql = "UPDATE `" . RL_DBPREFIX . $table . "` SET ";
            $sql .= "`Status` = 'trash' WHERE ";
            $criterion = '';

            foreach ($fields as $field => $value) {
                $criterion .= "`{$field}` = '{$value}' AND ";

                // simulation field
                if ($tables[0] == 'accounts' && $table != 'accounts' && $field == 'ID') {
                    $field = "Account_ID";
                }

                $sql .= "`{$field}` = '{$value}' AND";
            }

            $sql = substr($sql, 0, -3);

            $this->query($sql);

            $tables_set .= $table . ",";
        }
        $tables_set = substr($tables_set, 0, -1);

        // set trash status for langusge's keys
        if (!empty($lang_keys)) {
            foreach ($lang_keys as $lKey => $lVal) {
                $l_update[$lKey]['where'] = $lang_keys[$lKey];
                $l_update[$lKey]['fields'] = array('Status' => 'trash');
            }

            $this->update($l_update, 'lang_keys');
        }

        // insert data into trash box
        $criterion = substr($criterion, 0, -4);

        $admin = (defined('REALM') && REALM == 'admin') ? $_SESSION['sessAdmin']['user_id'] : 0;

        $qTrash = array(
            'Zones'          => $tables_set,
            'Key'            => $key,
            'Criterion'      => $criterion,
            'Class_name'     => $className,
            'Restore_method' => $restoreMethod,
            'Remove_method'  => $deleteMethod,
            'Admin_ID'       => $admin,
            'Date'           => 'NOW()',
            'Lang_keys'      => serialize($lang_keys),
            'Plugin'         => $plugin,
        );

        $this->insertOne($qTrash, 'trash_box');

        return true;
    }

    /**
     * insert data in db | DEPRECATED
     *
     * @deprecated 4.6.0 - Use rlDb->insert()
     *
     * @param array $data   - updated criterias:
     *           array(
     *               [field] => [value],
     *               [field] => [value],
     *               ...     => ...
     *           )
     * @param string $table - table name
     * @param array $html_fields - fields keys which can contain HTML
     *
     * @return bool
     **/
    public function insertOne($data, $table = null, $html_fields = false)
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->insertOne($data, $table, $html_fields);
    }

    /**
     * insert data in db | DEPRECATED
     *
     * @deprecated 4.6.0 - Use rlDb->insert()
     *
     * @param array $data   - array format:
     *           array(
     *                   [item] => array(
     *                       [field] => [value],
     *                       [field] => [value],
     *                       ...     => ...
     *                   )
     *           )
     * @param string $table - table name
     *
     * @return bool
     **/
    public function insert($data, $table, $html_fields = null)
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->insert($data, $table);
    }

    /**
     * update database information | DEPRECATED
     *
     * @deprecated 4.6.0 - Use rlDb->update()
     *
     * @param array $data   - updated criterias:
     *           array(
     *             [item] => array
     *               (
     *                   [fields] => array()
     *                   [where] =>  array()
     *               )
     *           )
     * @param string $table - table name
     *
     * @return bool
     **/
    public function update($data, $table, $html_fields = null)
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->update($data, $table);
    }

    /**
     * update one db row | DEPRECATED
     *
     * @deprecated 4.6.0 - Use rlDb->update()
     *
     * @param array $data   - updated criterias:
     *           array(
     *                   [fields] => array()
     *                   [where] =>  array()
     *           )
     * @param string $table - table name
     * @param array $html_fields - fields keys which can contain HTML
     *
     * @return bool
     **/
    public function updateOne($data, $table = null, $html_fields = false)
    {
        $GLOBALS['rlDb']->rlAllowHTML = $this->rlAllowHTML;
        return $GLOBALS['rlDb']->updateOne($data, $table, $html_fields);
    }

    /**
     * file upload (from form)
     *
     * @param string $field        - field name from the form
     * @param string/array $file   - new file name
     * @param string $resize_type  - resolution type
     *        resize types: W = Width, H =   Height, P = Percentage, C = Custom (array(w, h))
     * @param mixed  $resolution   - resolution size
     * @param string $parent  - parent input field name
     * @param bool $watermark  - wotermark on uploaded image
     *
     * @return bool
     **/
    public function upload($field = false, $file = false, $resize_type = false, $resolution = false, $parent = false, $watermark = true)
    {
        global $config, $l_deny_files_regexp, $rlHook;

        /* get tmp file if exists */
        $tmp = $_SESSION['tmp_files'];

        $rlHook->load('phpUpload');

        if ((is_readable(RL_UPLOAD . $tmp[$parent][$field]) && $tmp[$parent][$field]) || (is_readable(RL_UPLOAD . $tmp[$field]) && $tmp[$field])) {
            $file_tmp_name = $_SESSION['tmp_files'][$parent][$field] ? $_SESSION['tmp_files'][$parent][$field] : $_SESSION['tmp_files'][$field];

            /* prevent denied files upload */
            if (preg_match($l_deny_files_regexp, $file_tmp_name)) {
                return false;
            }

            $file_ext = pathinfo($file_tmp_name, PATHINFO_EXTENSION);
            $file_name = $file . '.' . $file_ext;
            $file_dir = RL_UPLOAD . $file_tmp_name;
        }
        /* get file from FILES */
        else {
            $file_tmp_name = $parent && $_FILES[$parent] ? $_FILES[$parent]['name'][$field] : $_FILES[$field]['name'];

            /* prevent denied files upload */
            if (preg_match($l_deny_files_regexp, $file_tmp_name)) {
                return false;
            }

            $file_type = $parent && $_FILES[$parent] ? $_FILES[$parent]['type'][$field] : $_FILES[$field]['type'];
            $file_tmp_dir = $parent && $_FILES[$parent] ? $_FILES[$parent]['tmp_name'][$field] : $_FILES[$field]['tmp_name'];
            $file_ext = pathinfo($file_tmp_name, PATHINFO_EXTENSION);
            $file_name = $file . '.' . $file_ext;

            if ($this->photoSaveOriginal) {
                $file_name_original = $file . '_original.' . $file_ext;
                $this->photoOriginal = $file_name_original;
            }
            $file_dir = RL_UPLOAD . $file_name;

            /* upload file */
            if ($file_tmp_dir && $file_dir) {
                if (move_uploaded_file($file_tmp_dir, $file_dir)) {
                    chmod($file_dir, 0777);
                } else {
                    trigger_error('Unable to move_uploaded_file', E_USER_WARNING);
                    $GLOBALS['rlDebug']->logger("Unable to move_uploaded_file");
                }
            }
        }

        if (is_readable($file_dir)) {
            $final_distanation = RL_FILES . $file_name;

            if ($this->photoSaveOriginal && $this->photoOriginal) {
                copy($file_dir, RL_FILES . $this->photoOriginal);
            }

            if (!empty($resize_type) && !empty($resolution)) {
                $this->loadClass('Resize');
                $this->loadClass('Crop');

                /* crop image */
                if (is_array($resolution)) {
                    $GLOBALS['rlCrop']->loadImage($file_dir);
                    $GLOBALS['rlCrop']->cropBySize($resolution[0], $resolution[1], ccCENTER);
                    $GLOBALS['rlCrop']->saveImage($file_dir, $config['img_quality']);
                    $GLOBALS['rlCrop']->flushImages();
                }

                /* resize image */
                $resize_type = strtoupper($resize_type);
                $GLOBALS['rlResize']->resize($file_dir, $final_distanation, $resize_type, $resolution, true, $watermark);
            } else {
                copy($file_dir, $final_distanation);
            }

            if (is_readable($final_distanation)) {
                chmod($final_distanation, 0644);

                unlink($file_dir);

                if ($parent) {
                    unset($_SESSION['tmp_files'][$parent][$field]);
                } else if ($field) {
                    unset($_SESSION['tmp_files'][$field]);
                }

                return $file_name;
            }
        }

        return false;
    }

    /**
     * add new value to enum database field
     *
     * @param string $table - table
     * @param string $field - field
     * @param string $value - new value
     * @param boolean $pasive - show die message
     *
     * @return bool
     **/
    public function enumAdd($table = false, $field = false, $value = false, $pasive = false)
    {
        $this->enum($table, $field, $value, 'add', $pasive);
    }

    /**
     * remove value from enum database field
     *
     * @param string $table - table
     * @param string $field - field
     * @param string $value - new value
     * @param boolean $pasive - show die message
     *
     * @return bool
     **/
    public function enumRemove($table = false, $field = false, $value = false, $pasive = false)
    {
        $this->enum($table, $field, $value, 'remove', $pasive);
    }

    /**
     * enum/set database fields manager
     *
     * @param string $table - table
     * @param string $field - field
     * @param string $value - new value
     * @param option $mode  - add or remove
     * @param boolean $pasive - show die message
     *
     * @return bool
     **/
    public function enum($table = false, $field = false, $value = false, $mode = 'add', $pasive = false)
    {
        if (!$table || !$field || !$value) {
            return false;
        }

        $sql = "SHOW COLUMNS FROM `" . RL_DBPREFIX . $table . "` LIKE '{$field}'";
        $enum_row = $this->getRow($sql);
        preg_match('/([a-z]*)\((.*)\)/', $enum_row['Type'], $matches);

        if (!in_array(strtolower($matches[1]), array('enum', 'set'))) {
            die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field is not ENUM or SET type field');
            return false;
        }

        $enum_values = explode(',', $matches[2]);

        if ($mode == 'add') {
            if (false !== array_search("'{$value}'", $enum_values)) {
                die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field already has <b>' . $value . '</b> value');
                return false;
            }

            array_push($enum_values, "'{$value}'");
        } elseif ($mode == 'remove') {
            $pos = array_search("'{$value}'", $enum_values);

            if ($pos === false) {
                return false;
            }

            unset($enum_values[$pos]);

            if (empty($enum_values)) {
                die('ENUM add/edit method (table: ' . $table . '): <b>' . $field . '</b> field will not has any values after your remove');
                return false;
            }

            $enum_values = array_values($enum_values);
        }

        $sql = "ALTER TABLE `" . RL_DBPREFIX . $table . "` CHANGE `{$field}` `{$field}` " . strtoupper($matches[1]) . "( " . implode(',', $enum_values) . " ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
        if (strtolower($matches[1]) == 'enum') {
            $sql .= " DEFAULT {$enum_values[0]}";
        }

        $this->query($sql);

        return true;
    }

    /**
     * display no table selected error
     *
     * @todo show error, write logs
     **/
    public function tableNoSel()
    {
        RlDebug::logger("SQL query can't be run, it isn't table name selected", null, null, 'Warning');
        return 'Table not selected, see error log';
    }
}
