<?php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$arr = [];

// Connect to DB
$db_pdo = db_connect();

// Fetch user_cert rows where ws_account is missing
$querystring = "SELECT * FROM tcs.user_cert WHERE ws_account IS NULL OR ws_account = '' ORDER BY ad_account, cert_id;";
$db_arr = db_query($db_pdo, $querystring);

// Fetch user table and build lookup
$querystringUser = "SELECT * FROM tcs.user ORDER BY ad_account;";
$dbuser_arr = db_query($db_pdo, $querystringUser);
$dbusers = [];

// Normalize user WS accounts
foreach ($dbuser_arr as $value) {
    $ad = $value['ad_account'];
    if ($ad === "msalee") {
        $dbusers[$ad] = "MSALEE"; // Handle duplicate manually
    } else {
        // Normalize: convert empty string to null
        $ws = trim($value['ws_account'] ?? '');
        $dbusers[$ad] = ($ws === '') ? null : $ws;
    }
}
$arr['user_count'] = count($dbusers);

$counter = 0;

foreach ($db_arr as $data) {
    $counter++;
    $ad_account = $data['ad_account'];
    $ws_account = $dbusers[$ad_account] ?? null;

    if ($ws_account === null) {
        // Log missing WS account
//        $arr['noWSname'][$ad_account] = "MISSING";
        continue;
    }

    // Use parameterized query for safety
    $updatestring = "UPDATE tcs.user_cert SET ws_account = :ws_account WHERE ad_account = :ad_account;";
    $params = [
        ':ws_account' => $ws_account,
        ':ad_account' => $ad_account
    ];

    if (db_update($db_pdo, $updatestring, $params)) {
        $arr['success'][$ad_account] = $ws_account;
    } else {
        $arr['fail'][$ad_account] = $ws_account;
        $arr['error'] = 'Failed on database execute';
    }
}

// Close connection
$db_pdo = null;

header('Content-Type: application/json');
echo json_encode($arr);
