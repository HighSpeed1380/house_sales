<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.0
 *  LICENSE: FL8B19R2G24B - https://www.flynax.com/license-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: property.blue
 *  FILE: DATABASE.INC.PHP
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

if ($_POST['import']) {
    $dump_sours = $_FILES['dump']['tmp_name'];
    $dump_file = $_FILES['dump']['name'];

    preg_match("/(\.sql)/", $dump_file, $matches);

    if (strtolower($matches[1]) == '.sql') {
        if (is_readable($dump_sours)) {
            $dump_content = fopen($dump_sours, "r");

            if ($dump_content) {
                $rlDb->dieIfError = false;

                while ($query = fgets($dump_content, 10240)) {
                    $query = trim($query);
                    if ($query[0] == '#') {
                        continue;
                    }

                    if ($query[0] == '-') {
                        continue;
                    }

                    if ($query[strlen($query) - 1] == ';') {
                        $query_sql .= $query;
                    } else {
                        $query_sql .= $query;
                        continue;
                    }

                    if (!empty($query_sql) && empty($errors)) {
                        $query_sql = str_replace(array('{sql_prefix}', '{db_prefix}'), RL_DBPREFIX, $query_sql);
                    }

                    $res = $rlDb->query($query_sql);
                    if (!$res && count($errors) < 5) {
                        $errors[] = $lang['can_not_run_sql_query'] . addslashes($rlDb->lastError());
                    }
                    unset($query_sql);
                }

                $rlDb->dieIfError = true;
                fclose($sql_dump);

                if (empty($errors)) {
                    $rlNotice->saveNotice($lang['dump_imported']);
                    $aUrl = array("controller" => $controller);

                    $reefless->redirect($aUrl);
                } else {
                    $errors[] = $lang['dump_query_corrupt'];
                }
            } else {
                $errors[] = $lang['dump_has_not_content'];
            }
        } else {
            $errors[] = $lang['can_not_read_file'];
            trigger_error("Can not to read uploaded file | Database Import", E_WARNING);
            $rlDebug->logger("Can not to read uploaded file | Database Import");
        }
    } else {
        $errors[] = $lang['incorrect_dump_file'];
    }

    if (!empty($errors)) {
        $rlSmarty->assign_by_ref('errors', $errors);
    }
}

$rlHook->load('apPhpDatabaseBottom');

/* register ajax methods */
$rlXajax->registerFunction(array('runSqlQuery', $rlAdmin, 'ajaxRunSqlQuery'));
