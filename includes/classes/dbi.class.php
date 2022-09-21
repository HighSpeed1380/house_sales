<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: DBI.CLASS.PHP
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

/**
 * Database class supports mysqli library
 *
 * Class is handling mysql connection and all queries in the application using mysqli library
 */
class rlDatabase
{
    /**
     * current table name
     *
     * @var string
     **/
    public $tName = null;

    /**
     * current mysql API version
     *
     * @var string
     **/
    public $mysqlVer = null;

    /**
     * mysql calculate found rows
     *
     * @var bool
     **/
    public $calcRows = false;

    /**
     * sql query start time
     *
     * @var bool
     **/
    public $start = 0;

    /**
     * rows mapping
     *
     * Example:
     */
    public $outputRowsMap = false;

    /**
     * Die/Exit if st. errors
     */
    public $dieIfError = true;

    /**
     * mysqli is avaiable if this class used
     *
     * @var bool
     **/
    public $mysqli = true;

    /**
     * Open mysql connection and select database
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $pass
     * @param string $base_name
     */
    public function connect($host, $port = 3306, $user = '', $pass = '', $base_name = '')
    {
        $GLOBALS['mysqli'] = mysqli_connect($host, $user, $pass, $base_name, $port);

        if (false === $GLOBALS['mysqli']) {
            if ($this->dieIfError === false) {
                return false;
            }
            die('Could not connect to MySQL server');
        }

        $GLOBALS['mysqli']->set_charset('utf8');
        $this->query("SET sql_mode = ''");
    }

    /**
     * set current table name
     *
     * @param string $nama - tabel name
     **/
    public function setTable($name)
    {
        $this->tName = $name;
    }

    /**
     * reset table name
     **/
    public function resetTable()
    {
        $this->tName = null;
    }

    /**
     * get latest insert id
     *
     * @return int
     **/
    public function insertID()
    {
        return $GLOBALS['mysqli']->insert_id;
    }

    /**
     * Returns a string description of the last error
     *
     * @since v4.4
     * @return string
     */
    public function lastError()
    {
        if (!$GLOBALS['mysqli']) {
            return 'Could not connect to MySQL server';
        }
        return $GLOBALS['mysqli']->error;
    }

    /**
     * Returns the error code for the most recent function call
     *
     * @since v4.4
     * @return int
     */
    public function lastErrno()
    {
        return $GLOBALS['mysqli'] ? $GLOBALS['mysqli']->errno : -1;
    }

    /**
     * get affected rows from latest executed query
     *
     * @return int
     **/
    public function affectedRows()
    {
        return $GLOBALS['mysqli']->affected_rows;
    }

    /**
     * Closes a previously opened database connection
     *
     * @since 4.6.0 - Added $force param
     *
     * @param bool $force - Сlose the connection immediately
     */
    public function connectionClose($force = false)
    {
        if ($force === true) {
            $GLOBALS['mysqli']->close();
        } else {
            register_shutdown_function(array(&$GLOBALS['mysqli'], 'close'));
        }
    }

    /**
     * run mySQL query
     *
     * @param string $sql - mySQL query string
     * @return mixed
     **/
    public function query($sql)
    {
        $this->calcTime('start');

        $res = $GLOBALS['mysqli']->query($sql);

        if (!$res) {
            if ($this->dieIfError === false) {
                return false;
            }
            $this->error($sql);
        }

        $this->calcTime('end', $sql);

        return $res;
    }

    /**
     * get all data from the table
     *
     * @param string $sql - mySQL query string
     * @param mixed $outputMap - 'index_key' ||
     *                           array('index_key', 'value_row_key') ||
     *                           array(false, 'value_row_key')
     * Example:
     *     'Key': return: ['key1' => all_selected_rows], etc...
     *     array('Key', 'Path'):  return: ['key1' => 'Path'], etc...
     *     array(false, 'Path'):  return: [0 => 'Path'], etc...
     *
     * @return array
     **/
    public function getAll($sql, $outputMap = false)
    {
        $res = $this->query($sql);

        // mapping
        $map_index = $map_value = false;
        if ($outputMap) {
            if (is_string($outputMap)) {
                $map_index = trim($outputMap);
            } else if (is_array($outputMap) && 2 === count($outputMap)) {
                if ($outputMap[0] !== false) {
                    $map_index = trim($outputMap[0]);
                }

                $map_value = trim($outputMap[1]);
            }
        }

        // Convert to array
        $ret = array();
        while ($row = $res->fetch_assoc()) {
            $row_value = ($map_value && array_key_exists($map_value, $row)) ? $row[$map_value] : $row;

            // Add to array
            if ($map_index && array_key_exists($map_index, $row)) {
                $ret[$row[$map_index]] = $row_value;
            } else {
                array_push($ret, $row_value);
            }
            unset($row, $row_value);
        }

        return $ret;
    }

    /**
     * Get one field of result row
     *
     * @param  string $field  - Field name
     * @param  string $where  - Select condition
     * @param  string $table  - Table name
     * @param  string $prefix - Table prefix
     * @return string         - Data as associative array
     */
    public function getOne($field = false, $where = null, $table = null, $prefix = false)
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        if (!$field || !$where) {
            return '';
        }

        $prefix = $prefix ?: RL_DBPREFIX;
        $sql = "SELECT `{$field}` FROM `{$prefix}{$table}` WHERE {$where} LIMIT 1";

        $res = $this->query($sql);
        $ret = $res->fetch_row();

        return is_array($ret) ? $ret[0] : '';
    }

    /**
     * get one row from the table
     *
     * @param string $sql - mySQL query string
     * @param string $field - return only it
     *
     * @return data as associative array / string
     **/
    public function getRow($sql = false, $field = false)
    {
        if (!(bool) preg_match('/LIMIT\s+[0-9]+/', $sql) && !is_numeric(strpos($sql, 'SHOW'))) {
            $sql .= ' LIMIT 1';
        }

        $res = $this->query($sql);
        $row = $res->fetch_assoc();

        if ($field !== false) {
            return $row[$field];
        }
        return $row;
    }

    /**
     * select data by criteria from the table
     *
     * @param array $fields - fields names array: array( 'field1', 'field2', 'field3')
     * @param array $where  - array of selected criterias:
     *           array(
     *             'field name' => 'value',
     *             'field name' => 'value'
     *                 )
     * @param string $options  - options string: "ORDER BY `field` "
     * @param int|array $limit - limit parametrs: int (rows number) or array( 'from', 'rows' )
     * @param string $table    - table name
     * @param string $action   - selected type: all table content or one row
     *
     * @return data as associative array
     **/
    public function fetch($fields = '*', $where = null, $options = null, $limit = null, $table = null, $action = 'all')
    {
        if ($table == null) {
            if ($this->tName != null) {
                $table = $this->tName;
            } else {
                $this->tableNoSel();
            }
        }

        $query = "SELECT ";

        if ($this->calcRows) {
            $query .= "SQL_CALC_FOUND_ROWS ";
        }

        if (is_array($fields)) {
            foreach ($fields as $sel_field) {
                $query .= "`{$sel_field}`,";
            }
            $query = substr($query, 0, -1);
        } else {
            $query .= " * ";
        }

        $query .= " FROM `" . RL_DBPREFIX . $table . "` ";

        if (is_array($where)) {
            $query .= " WHERE ";

            foreach ($where as $key => $value) {
                $GLOBALS['rlValid']->sql($value);
                $query .= " (`{$key}` = '{$value}') AND";
            }
            $query = substr($query, 0, -3);
        }

        if ($options != null) {
            $query .= " " . $options . " ";
        }

        if (is_array($limit)) {
            $qStart = (int) $limit[0];
            $qLimit = (int) $limit[1];
            $query .= " LIMIT {$qStart}, {$qLimit} ";
        } else {
            if ($action == 'row' && empty($limit)) {
                $limit = 1;
            }

            if (!empty($limit)) {
                $query .= " LIMIT {$limit} ";
            }
        }

        if ($action == 'row') {
            $output = $this->getRow($query);
        } else {
            $output = $this->getAll($query, $this->outputRowsMap);
            $this->outputRowsMap = false;
        }

        if ($this->calcRows) {
            $calc = $this->getRow("SELECT FOUND_ROWS() AS `calc`");
            $this->foundRows = $calc['calc'];
        }

        return $output;
    }

    /**
     * Return mysql error
     *
     * @return String - MySQL error
     **/
    public function error()
    {
        return $GLOBALS['mysqli']->error;
    }

    /**
     * calculate query time
     *
     * @param string $mode - start or end of the query
     **/
    public function calcTime($mode = 'start', $sql = false)
    {
        if (!RL_DB_DEBUG) {
            return false;
        }

        if ($mode == 'start') {
            $time = microtime();
            $time = explode(" ", $time);
            $time = $time[1] + $time[0];
            $this->start = $time;
        } else {
            if (!$_SESSION['sql_debug_time']) {
                $_SESSION['sql_debug_time'] = 0;
            }

            $time = microtime();
            $time = explode(" ", $time);
            $time = $time[1] + $time[0];
            $finish = $time;
            $totaltime = ($finish - $this->start);
            $_SESSION['sql_debug_time'] += $totaltime;
            $color = 'green';
            if ($totaltime >= 0.1) {
                $color = 'red';
            } elseif ($totaltime > 0.01 && $totaltime < 0.1) {
                $color = 'orange';
            }
            printf("The query took <span style=\"color: {$color};\"><b>%f</b></span> seconds to load.<br />", $totaltime);
            $backtrace = debug_backtrace();
            $level = count($backtrace);
            $log = $level > 1 ? $backtrace[$level - 3] : $backtrace[0];
            echo $log['file'] . "({$log['line']}) / function: {$log['function']}<br />";
            echo $sql . '<br /><br />';
        }
    }

    /**
     * @since 4.5.0
     *
     * get mysql version
     *
     **/
    public function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * display no table selected error
     *
     * @todo show error, write logs
     **/
    public function tableNoSel()
    {
        RlDebug::logger("SQL query can't be run, it isn't table name selected", null, null, 'Warning');
        die('Table not selected, see error log');
    }
}
