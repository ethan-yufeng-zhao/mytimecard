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

$querystring_current_user = "SELECT * FROM hr.employee WHERE samaccountname = '".$user_id."'";
$db_arr_current_user = db_query($db_pdo, $querystring_current_user);
if ($db_arr_current_user) {
    foreach ($db_arr_current_user as $data) {
        $arr[$user_id]['meta']['employeetype2'] = $data['employeetype'] ?? '';
        $arr[$user_id]['meta']['employeeid'] = $data['employeeid'] ?? '';
        $arr[$user_id]['meta']['givenname'] = $data['givenname'] ?? '';
        $arr[$user_id]['meta']['sn'] = $data['sn'] ?? '';
        $arr[$user_id]['meta']['mail'] = $data['mail'] ?? '';
        $arr[$user_id]['meta']['department'] = $data['department'] ?? '';
        $arr[$user_id]['meta']['departmentnumber'] = $data['departmentnumber'] ?? '';
        $arr[$user_id]['meta']['ipphone'] = $data['ipphone'] ?? '';
        $arr[$user_id]['meta']['telephonenumber'] = $data['telephonenumber'] ?? '';
        $arr[$user_id]['meta']['manager'] = $data['manager_samaccountname'] ?? '';
    }
} else {
    $arr['error'] = 'Cannot find the user: ' . $user_id;
    header('Content-Type: application/json');
    echo(json_encode($arr));
    return;
}

$querystring_team_users = "SELECT count(*) FROM hr.employee WHERE manager_samaccountname = '".$user_id."'";
$db_arr_team_users = db_query($db_pdo, $querystring_team_users);
if ($db_arr_team_users && $db_arr_team_users[0]['count'] > 0) {
    $arr[$user_id]['meta']['role'] = 'supervisor';
} else {
    $arr[$user_id]['meta']['role'] = '';
}

$querystring_admin = "SELECT role FROM hr.timecard_admin WHERE username = '".$user_id."'";
$db_arr_admin = db_query($db_pdo, $querystring_admin);
if ($db_arr_admin && isset($db_arr_admin[0]['role'])) {
    $arr[$user_id]['meta']['role'] = $db_arr_admin[0]['role'];
}

$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
