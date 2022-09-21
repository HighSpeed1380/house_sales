<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: RLDB.CLASS.PHP
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

use Flynax\Utils\Valid;

require_once RL_CLASSES . 'dbi.class.php';

/**
 * @since 4.5.2
 */
class rlDb extends rlDatabase
{
    /**
     * @var bool - Allow html tags
     */
    public $rlAllowHTML = false;

    /**
     * @var array - Mysql functions
     */
    public $mysqlFunctions = array('NOW()', 'DATE_ADD()', 'IF()', 'NULL');

    /**
     * @var bool - Allow 'LIKE' condition in update methods
     */
    public $rlAllowLikeMatch = false;

    /**
     * Register an SQL query for execution on shutdown
     *
     * @since 4.5.2
     *
     * @param string $sql
     */
    public function shutdownQuery($sql)
    {
        register_shutdown_function(array($this, 'query'), $sql);
    }

    /**
     * Check if table exists
     *
     * @since 4.6.0
     *
     * @param  string $table  - Name of the table
     * @param  string $prefix - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @return bool
     */
    public function tableExists($table, $prefix = RL_DBPREFIX)
    {
        $this->query(sprintf("SHOW TABLES LIKE '%s%s'", $prefix, $table));
        return $this->affectedRows() ? true : false;
    }

    /**
     * Create table if not exists
     *
     * @since 4.8.1 - Added default value for $properties parameter
     * @since 4.7.0 - Added $properties parameter
     * @since 4.6.0
     *
     * @param string $table      - Name of the table
     * @param string $raw_sql    - Part of SQL query (like: "`ID` int(5) NOT NULL AUTO_INCREMENT")
     * @param string $prefix     - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @param string $properties - Additional properties, like CHARSET, INDEX and etc.
     */
    public function createTable(
        $table,
        $raw_sql,
        $prefix     = RL_DBPREFIX,
        $properties = 'ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_general_ci'
    ) {
        $this->query(sprintf("CREATE TABLE IF NOT EXISTS `%s%s` (%s) %s", $prefix, $table, $raw_sql, $properties));
    }

    /**
     * Drop table if exists
     *
     * @since 4.5.2
     *
     * @param string $table  - Name of the table you want to drop from database
     * @param string $prefix - Prefix for the table if necessary; by default RL_DBPREFIX constant
     */
    public function dropTable($table, $prefix = RL_DBPREFIX)
    {
        $this->query(sprintf("DROP TABLE IF EXISTS `%s%s`", $prefix, $table));
    }

    /**
     * Drop multiple tables from database
     *
     * @since 4.6.0
     *
     * @param array  $tables - List of tables
     * @param string $prefix - Prefix for the table if necessary; by default RL_DBPREFIX constant
     */
    public function dropTables(array $tables, $prefix = RL_DBPREFIX)
    {
        $tables = implode('`,`', array_map(function ($table) use ($prefix) {return $prefix . $table;}, $tables));
        $this->query("DROP TABLE IF EXISTS `{$tables}`");
    }

    /**
     * Check if column exists
     *
     * @since 4.5.2
     *
     * @param  string $column  - Column to check
     * @param  string $table   - Table to check column availability
     * @param  string $prefix  - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @return bool            - Column availability
     */
    public function columnExists($column, $table, $prefix = RL_DBPREFIX)
    {
        $column = $this->getRow(sprintf("SHOW COLUMNS FROM  `%s%s` LIKE '%s'", $prefix, $table, $column));
        return !empty($column);
    }

    /**
     * Drop column from a table if it exist
     *
     * @since 4.8.2 - Added returned value
     * @since 4.5.2
     *
     * @param string $column  - Column to check
     * @param string $table   - Table to check column availability
     * @param string $prefix  - Prefix for the table if necessary; by default RL_DBPREFIX constant
     *
     * @return bool
     */
    public function dropColumnFromTable($column, $table, $prefix = RL_DBPREFIX)
    {
        if (true === $this->columnExists($column, $table, $prefix)) {
            $this->query(sprintf("ALTER TABLE `%s%s` DROP `%s`", $prefix, $table, $column));
            return true;
        }

        return false;
    }

    /**
     * Drop columns from a table
     *
     * @since 4.8.2 - Added returned value
     * @since 4.5.2
     *
     * @param array  $columns - Names of columns
     * @param string $table   - Name of table
     * @param string $prefix  - Prefix for the table if necessary; by default RL_DBPREFIX constant
     *
     * @return bool
     */
    public function dropColumnsFromTable($columns, $table, $prefix = RL_DBPREFIX)
    {
        $result = false;

        foreach ($columns as $column) {
            $result = $this->dropColumnFromTable($column, $table, $prefix);
        }

        return $result;
    }

    /**
     * Add single column to table
     *
     * @since 4.6.0
     *
     * @param  string $column     - Column name
     * @param  string $sql_params - Part of SQL query (in format: "int(5) NOT NULL")
     * @param  string $table      - Name of the table
     * @param  string $prefix     - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @return bool
     */
    public function addColumnToTable($column, $sql_params, $table, $prefix = RL_DBPREFIX)
    {
        if (false !== $this->columnExists($column, $table, $prefix)) {
            return false;
        }
        return (bool) $this->query(sprintf("ALTER TABLE `%s%s` ADD `%s` %s", $prefix, $table, $column, $sql_params));
    }

    /**
     * Add columns to table
     *
     * @since 4.5.2
     *
     * @param  array  $columns - List of columns (in format: 'Key1' => 'SQL_PARAMS', 'Key2' => 'SQL_PARAMS' and etc.)
     * @param  string $table   - Name of table
     * @param  string $prefix  - Prefix for the table if necessary; by default RL_DBPREFIX constant
     * @return bool
     */
    public function addColumnsToTable($columns, $table, $prefix = RL_DBPREFIX)
    {
        $alter_fields = array();
        foreach ($columns as $field => $field_params_sql) {
            if (false === $this->columnExists($field, $table, $prefix)) {
                $alter_fields[] = "ADD `{$field}` {$field_params_sql}";
            }
        }

        if (count($alter_fields)) {
            return $this->query("ALTER TABLE `{$prefix}{$table}` " . implode(', ', $alter_fields));
        }

        return false;
    }

    /**
     * Delete entry
     *
     * @since 4.6.0
     *
     * @param  array  $where   - Where clause parameters as array(field1 => value1, field2 => value2)
     * @param  string $table   - Name of table
     * @param  string $options - Additional options
     * @param  int    $limit   - Limit of entries to be removed
     * @return bool
     */
    public function delete($where, $table, $options = null, $limit = 1)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (!$where) {
            return false;
        }

        // build query
        $query = "DELETE FROM `" . RL_DBPREFIX . $table . "` WHERE ";

        // where clause
        foreach ($where as $key => $value) {
            $GLOBALS['rlValid']->sql($value);
            $query .= "`{$key}` = '{$value}' AND";
        }
        $query = substr($query, 0, -3);

        // additional condition
        if ($options) {
            $query .= "{$options} ";
        }

        // set limit
        if ($limit !== 0 && $limit !== null) {
            $query .= "LIMIT {$limit}";
        }

        return $this->query($query);
    }

    /**
     * Insert data in db
     *
     * @param array $data   - array format:
     *                        array(
     *                                  [item] => array(
     *                                  [field] => [value],
     *                                  [field] => [value],
     *                                  ...     => ...
     *                                  )
     *                              )
     * @param string $table - table name
     * @param array  $html_fields - fields keys which can contain HTML
     * @return bool
     */
    public function insert($data, $table, $html_fields = null)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (empty($data)) {
            return false;
        }

        reset($data);

        if (is_array(current($data))) {
            foreach ($data as $insert) {
                $this->insertOne($insert, $table, $html_fields);
            }
        } else {
            $this->insertOne($data, $table, $html_fields);
        }

        return true;
    }

    /**
     * Insert data in db
     *
     * @param array $data   - updated criterias:
     *                        array(
     *                                  [field] => [value],
     *                                  [field] => [value],
     *                                  ...     => ...
     *                        )
     * @param string $table - table name
     * @param array  $html_fields - fields keys which can contain HTML
     * @return bool|object
     */
    public function insertOne($data, $table, $html_fields = null)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (empty($data)) {
            return false;
        }

        $sql = "INSERT INTO `" . RL_DBPREFIX . $table . "` ( ";

        // define fields collection
        foreach ($data as $field => $value) {
            $sql .= "`{$field}`, ";
        }

        $sql = substr($sql, 0, -2);

        // set values
        $sql .= " ) VALUES ( ";

        foreach ($data as $field => $value) {
            Valid::revertQuotes($value);

            if ($value) {
                preg_match('/^([A-Z_]+)\(.+/', $value, $matches);
            }

            if ($html_fields && in_array($field, $html_fields)) {
                Valid::stripJS($value, true);
            } elseif (!defined('REALM')
                && !$this->rlAllowHTML
                && !in_array($matches[1] . '()', $this->mysqlFunctions)
            ) {
                Valid::html($value, true);
            }

            Valid::escape($value, true);

            if (in_array($value, $this->mysqlFunctions) || in_array($matches[1] . '()', $this->mysqlFunctions)) {
                $sql .= "{$value}, ";
            } else {
                $sql .= "'{$value}', ";
            }

            if ($matches) {
                unset($matches);
            }
        }

        $sql = substr($sql, 0, -2);
        $sql .= " )";

        return $this->query($sql);
    }

    /**
     * Update database information
     *
     * @param array $data   - updated criterias:
     *                        array(
     *                                  [item] => array
     *                                  (
     *                                      [fields] => array()
     *                                      [where] =>  array()
     *                                   )
     *                         )
     * @param string $table - table name
     * @param array  $html_fields - fields keys which can contain HTML
     * @return bool
     */
    public function update($data, $table, $html_fields = null)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (empty($data)) {
            return false;
        }

        reset($data);

        if (key($data) === 'fields') {
            $this->updateOne($data, $table, $html_fields);
        } else {
            foreach ($data as $update) {
                $this->updateOne($update, $table, $html_fields);
            }
        }

        return true;
    }

    /**
     * Update one db row
     *
     * @param array $data   - updated criterias:
     *                      array(
     *                          [fields] => array()
     *                          [where] =>  array()
     *                      )
     * @param string $table - table name
     * @param array  $html_fields - fields keys which can contain HTML
     * @return bool|object
     */
    public function updateOne($data, $table, $html_fields = null)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (empty($data)) {
            return false;
        }

        if (!is_array($data['fields']) || !is_array($data['where'])) {
            $fields = $data['fields'] ? ' - ' . serialize($data['fields']) . ',' : '';
            $where  = $data['where'] ? ' - ' . serialize($data['where']) : '';

            trigger_error(
                __METHOD__ . " failed, data['fields']{$fields} and data['where']{$where} are required",
                E_USER_ERROR
            );

            return false;
        }

        $sql = "UPDATE `" . RL_DBPREFIX . $table . "` SET ";

        foreach ($data['fields'] as $field => $value) {
            $sql .= "`{$field}` = ";

            Valid::revertQuotes($value);

            if ($value) {
                preg_match('/^([A-Z_]+)\(.+/', $value, $matches);
            }

            if ($html_fields && in_array($field, $html_fields)) {
                Valid::stripJS($value, true);
            } elseif (!defined('REALM')
                && !$this->rlAllowHTML
                && !in_array($matches[1] . '()', $this->mysqlFunctions)
            ) {
                Valid::html($value, true);
            }

            Valid::escape($value);

            if (in_array($value, $this->mysqlFunctions)
                || in_array($matches[1] . '()', $this->mysqlFunctions)
            ) {
                $sql .= "{$value}";
            } else {
                $sql .= "'{$value}'";
            }
            $sql .= ", ";

            if ($matches) {
                unset($matches);
            }
        }

        $sql = substr($sql, 0, -2);

        $sql .= " WHERE ";

        foreach ($data['where'] as $field => $value) {
            Valid::escape($value);

            $match_sign = is_numeric(strpos($value, '%')) && $this->rlAllowLikeMatch ? 'LIKE' : '=';
            $sql .= "`{$field}` {$match_sign} '{$value}' AND ";
        }

        $sql = substr($sql, 0, -4);

        return $this->query($sql);
    }

    /**
     * Performs a query on the database.
     * Data inside the query should be properly escaped
     *
     * @since    4.6.0
     * @override rlDatabase::query
     *
     * @param  string $sql    - Query string
     * @param  string $prefix - Custom prefix if necessary; by default RL_DBPREFIX constant
     * @return bool|object
     */
    public function query($sql, $prefix = RL_DBPREFIX)
    {
        $sql = str_replace('{db_prefix}', $prefix, $sql);
        return parent::query($sql);
    }

    /**
     * Print error statement
     *
     * @param string $query - Error query
     */
    public function error($query = false)
    {
        $mysql_error = parent::error();

        $error_stack = debug_backtrace();
        $index = 2;

        foreach ($error_stack as $error_index => &$error) {
            if (!in_array($error['class'], array('rlDatabase', 'rlDb'))) {
                $index = $error_index;
                break;
            }
        }

        $error = $error_stack[$index];

        $GLOBALS['rlDebug']->logger($mysql_error, $error['file'], $error['line'], 'Mysql Error', false);

        if (!RL_DB_DEBUG && !RL_DEBUG) {
            die('MYSQL ERROR');
        }

        if (isset($_POST['xjxfun']) || $_GET['q'] == 'ext' || defined('ANDROID_APP')) {
            echo 'MYSQL ERROR' . PHP_EOL;
            echo 'Error: ' . $mysql_error . PHP_EOL;
            echo 'Query: ' . $query . PHP_EOL;

            if ($error['function']) {
                echo 'Function: ' . $error['function'] . PHP_EOL;
            }

            if ($error['class']) {
                echo 'Class: ' . $error['class'] . PHP_EOL;
            }

            if ($error['file']) {
                echo 'File: ' . $error['file'] . ' (line# ' . $error['line'] . ')' . PHP_EOL;
            }
        } else {
            echo '<table style="width: 100%;font-family: Arial;font-size: 14px;">';
            echo '<tr><td colspan="2" style="color: red;font-weight: bold;">MYSQL ERROR</td></tr>';
            echo '<tr><td style="width: 90px;">Error:</td><td>' . $mysql_error . '</td></tr>';
            echo '<tr><td>Query:</td><td>' . $query . '</td></tr>';

            if ($error['function']) {
                echo '<tr><td>Function:</td><td>' . $error['function'] . '</td></tr>';
            }
            if ($error['class']) {
                echo '<tr><td>Class:</td><td>' . $error['class'] . '</td></tr>';
            }
            if ($error['file']) {
                echo '<tr><td>File:</td><td>' . $error['file'] . ' (line# ' . $error['line'] . ')</td></tr>';
            }

            echo '</table>';
        }

        exit;
    }
}
