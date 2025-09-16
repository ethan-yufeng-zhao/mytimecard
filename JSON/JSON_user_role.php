<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$current_timestamp = time();

$arr = array();

if(isset($_GET['uid']) && strlen($_GET['uid']) > 0) {
    $user_id     = $_GET['uid'];
} else {
    $user_id     = $_GET['user_id'] ?? '';
}

if (empty($user_id)) {
    $arr['error'] = 'Need user id';
    header('Content-Type: application/json');
    echo(json_encode($arr));
    return;
}

$db_pdo = db_connect();

$querystring_team_users = "SELECT count(*) FROM hr.employee WHERE manager_samaccountname = '".$user_id."'";
$db_arr_team_users = db_query($db_pdo, $querystring_team_users);
if ($db_arr_team_users && $db_arr_team_users[0]['count'] > 0) {
    $arr[$user_id] = 'supervisor';
} else {
    $arr[$user_id] = '';
}

$querystring_admin = "SELECT role FROM hr.timecard_admin WHERE username = '".$user_id."'";
$db_arr_admin = db_query($db_pdo, $querystring_admin);
if ($db_arr_admin && isset($db_arr_admin[0]['role'])) {
    $arr[$user_id] = $db_arr_admin[0]['role'];
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
