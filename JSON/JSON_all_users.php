<?php
// JSON/JSON_all_users.php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

if(isset($_GET['manager']) && strlen($_GET['manager']) > 0){
    $manager = trim($_GET['manager']);
} else {
    $manager = null;
}

$arr = array();

$querystring='';
$db_pdo = db_connect();

//$querystring='SELECT cert_id, cert_name, cert_description, cert_days_active, cert_notes, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety, cert_when_set, cert_when_modified, cert_last_user FROM tcs.cert WHERE is_active = 1;';
if ($GLOBALS['DB_TYPE'] == 'pgsql'){
    $querystring = 'select a.*,(select COUNT(*) from tcs."user" b where b.manager=a.ad_account) as "teamcount",(select COUNT(distinct uc.cert_id) from tcs.user_cert uc JOIN cert ON (cert.cert_id = uc.cert_id) where uc.ad_account=a.ad_account) as "certcount" from tcs."user" a ';
    if ($manager != null && $manager != '') {
        $querystring .= " where a.manager = '$manager' ";
    }
    $querystring .= ' order by a.ad_account;';
    //$querystring='select a.* from tcs."user" a;';
} else { // mysql
    $querystring='SELECT a.user_id, a.user_samaccountname, a.user_supervisor_id, a.user_firstname, a.user_lastname, a.user_email, a.user_is_admin, (SELECT COUNT(*) FROM tcs.`user` b WHERE b.user_supervisor_id = a.user_id) AS "teamcount", (SELECT COUNT(DISTINCT cert_id) FROM tcs.user_cert WHERE is_active = 1 AND user_id = a.user_id) AS "certcount"  FROM tcs.`user` a WHERE a.is_active = 1 ORDER BY a.user_samaccountname;';
}
$db_arr = db_query($db_pdo, $querystring);
//logit($sth_mysql->queryString);
foreach ($db_arr as $key => $data ) {
    $temp_arr = array();
    if ($GLOBALS['DB_TYPE'] == 'pgsql'){
        $temp_arr['user_id'] = $data['ad_account'];
        $temp_arr['user_samaccountname'] = $data['ad_account'];
        $temp_arr['user_supervisor_id'] = $data['manager'];
        $temp_arr['user_firstname'] = $data['first_name'];
        $temp_arr['user_lastname'] = $data['last_name'];
        $temp_arr['user_email'] = $data['ad_account'].'@'.'jfab.aosmd.com';
        $temp_arr['teamcount'] = (int)$data['teamcount'];
        $temp_arr['certcount'] = (int)$data['certcount'];
//            if ($temp_arr['teamcount']){
//                $temp_arr['user_is_admin'] = true;
//            } else {
            $temp_arr['user_is_admin'] = false;
//            }
    } else{ // mysql
        $temp_arr['user_id'] = (int)$data['user_id'];
        $temp_arr['user_samaccountname'] = $data['user_samaccountname'];
        $temp_arr['user_supervisor_id'] = (int)$data['user_supervisor_id'];
        $temp_arr['user_firstname'] = $data['user_firstname'];
        $temp_arr['user_lastname'] = $data['user_lastname'];
        $temp_arr['user_email'] = $data['user_email'];
        $temp_arr['user_is_admin'] = (int)$data['user_is_admin'];
        $temp_arr['teamcount'] = (int)$data['teamcount'];
        $temp_arr['certcount'] = (int)$data['certcount'];
    }

    $arr[$temp_arr['user_id']] = $temp_arr;
    unset($temp_arr);
}
// Close connection to DB
$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));
