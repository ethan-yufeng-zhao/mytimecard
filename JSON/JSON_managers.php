<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();
$arr = array();
$db_pdo = db_connect();

$querystring = "SELECT DISTINCT manager_samaccountname FROM hr.employee WHERE manager_samaccountname IS NOT NULL ORDER BY manager_samaccountname;";
$db_arr_managers = db_query($db_pdo, $querystring);
if ($db_arr_managers) {
    foreach ($db_arr_managers as $row) {
        if (!empty($row['manager_samaccountname'])) {
            $arr[] = $row['manager_samaccountname'];
        }
    }
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
