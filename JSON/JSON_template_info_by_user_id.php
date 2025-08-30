<?php
// JSON/JSON_template_info_by_user_id.php?user_id=228
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$arr = array();
$arr['items'] = array();
$arr['count'] = 0;
$user_templates = array();

if(isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 ) {
    $user_id = $_GET['user_id'];

    $querystring='';

    $db_pdo = db_connect();
    if ($GLOBALS['DB_TYPE'] == 'pgsql'){
        $querystring='select * from tcs.template t where t.is_active = true order by t.template_id;';
        $template_arr = db_query($db_pdo, $querystring);
        foreach ($template_arr as $key => $data){
            $users = json_decode($data['users'] ?? '{}', true);
            foreach ($users as $k => $v){
//					$user_templates[$v][] = $data['template_id'];
                $user_templates[$v][$data['template_id']]['template_id'] = $data['template_id'];
                $user_templates[$v][$data['template_id']]['template_name'] = $data['template_name'];
                $user_templates[$v][$data['template_id']]['certs'] = $data['certs'];
            }
        }
        //get ws_account -- no need to get ws account, use ad account directly
//			$querystring = 'SELECT ws_account FROM tcs.user WHERE ad_account = \''.$user_id.'\';';
//			$ws_account_arr = db_query($db_pdo, $querystring);
//			$ws_account = '';
//			if ($ws_account_arr != null && count($ws_account_arr) > 0) {
//				$ws_account = $ws_account_arr[0]['ws_account'];
//			}
        //
        foreach ($user_templates as $k => $v){
            if ($k == $user_id){
                foreach ($v as $key => $data){
                    $template_id = (int)$data['template_id'];
                    $arr['items'][$template_id]['template_id'] = $template_id;
                    $arr['items'][$template_id]['template_name'] = $data['template_name'];

                    $certs = json_decode($data['certs'] ?? '{}', true);
                    sort($certs);
                    $cert_string = $data['certs'];

                    $cert_string = str_replace("[", "(", $cert_string);
                    $cert_string = str_replace(']', ')', $cert_string);
                    $cert_string = str_replace('"', '\'', $cert_string);

                    $arr['items'][$template_id]['certcount'] = count($certs);
                    $arr['count']++;

                    if (count($certs) == 0){
                        $arr['items'][$template_id]['certs'] = array();
                        continue;
                    }
                    $querystring='SELECT * FROM tcs.cert WHERE cert.cert_id in '.$cert_string.' AND cert.is_active = true ORDER BY cert.cert_id;';
                    $certs_arr = db_query($db_pdo, $querystring);

                    if ($certs_arr != null && count($certs_arr) > 0) {
                        foreach ($certs_arr as $key => $certdata ) {
                            $temp_arr = array();
//							$temp_arr['template_cert_links_id'] = (int)$certdata['template_cert_links_id'];
                            $temp_arr['cert_id'] = (int)$certdata['cert_id'];
                            $temp_arr['cert_name'] = $certdata['cert_name'];
                            $temp_arr['cert_notes'] = $certdata['cert_notes'];
                            $temp_arr['cert_description'] = $certdata['cert_description'];
                            $temp_arr['cert_days_active'] = (int)$certdata['cert_days_active'];
                            $temp_arr['cert_never_expires'] = (int)$certdata['cert_never_expires'];
                            $temp_arr['cert_is_ert'] = (int)$certdata['cert_is_ert'];
                            $temp_arr['cert_is_iso'] = (int)$certdata['cert_is_iso'];
                            $temp_arr['cert_is_safety'] = (int)$certdata['cert_is_safety'];
                            $temp_arr['cert_points'] = (int)$certdata['cert_points'];
                            $arr['items'][$template_id]['certs'][$temp_arr['cert_id']] = $temp_arr;
                            unset($temp_arr);
//							$arr['items'][$template_id]['certcount']++;
                        }
                        // re-calc the count since some certs has been deleted
                        $arr['items'][$template_id]['certcount'] = count($arr['items'][$template_id]['certs']);
                    } else {
                        $arr['items'][$template_id]['certs'] = [];
                        $arr['items'][$template_id]['certcount'] = 0; // cert has been deleted
                    }
                }
            }
        }
    } else { // mysql
        $querystring='SELECT template.template_id, template_name FROM tcs.template_user_links JOIN template ON (template.template_id = template_user_links.template_id) WHERE `template`.`is_active` = 1 AND template_user_links.user_id = '.$user_id.';';
        $db_arr = db_query($db_pdo, $querystring);

        foreach ($db_arr as $key => $data ) {
            $template_id = (int)$data['template_id'];
            $arr['items'][$template_id]['template_id'] = $template_id;
            $arr['items'][$template_id]['template_name'] = $data['template_name'];
            $arr['items'][$template_id]['certcount'] = 0;
            $arr['count']++;

            $querystring='SELECT template_cert_links_id, cert.cert_id, cert_name, cert_notes, cert_description, cert_days_active, cert_never_expires, cert_is_ert, cert_is_iso, cert_is_safety FROM tcs.template_cert_links JOIN cert ON (cert.cert_id = template_cert_links.cert_id) WHERE cert.is_active = 1 AND template_cert_links.template_id = '.$template_id.' ORDER BY cert.cert_id;';
            $certs_arr = db_query($db_pdo, $querystring);

            $mycount = 0;

            foreach ($certs_arr as $key => $certdata ) {
                $temp_arr = array();
                $temp_arr['template_cert_links_id'] = (int)$certdata['template_cert_links_id'];
                $temp_arr['cert_id'] = (int)$certdata['cert_id'];
                $temp_arr['cert_name'] = $certdata['cert_name'];
                $temp_arr['cert_notes'] = $certdata['cert_notes'];
                $temp_arr['cert_description'] = $certdata['cert_description'];
                $temp_arr['cert_days_active'] = (int)$certdata['cert_days_active'];
                $temp_arr['cert_never_expires'] = (int)$certdata['cert_never_expires'];
                $temp_arr['cert_is_ert'] = (int)$certdata['cert_is_ert'];
                $temp_arr['cert_is_iso'] = (int)$certdata['cert_is_iso'];
                $temp_arr['cert_is_safety'] = (int)$certdata['cert_is_safety'];
                $arr['items'][$template_id]['certs'][$temp_arr['template_cert_links_id']] = $temp_arr;
                unset($temp_arr);
                $arr['items'][$template_id]['certcount']++;
            }
        }
    }
    // Close connection to DB
    $db_pdo = null;
}

header('Content-Type: application/json');
echo(json_encode($arr));