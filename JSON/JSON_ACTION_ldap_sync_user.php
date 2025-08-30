<?php
    // JSON/JSON_ACTION_ldap_sync_user.php?user_id=1
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    $arr = array();

    if (isset($_GET['user_id']) && strlen($_GET['user_id']) > 0 && is_numeric($_GET['user_id'])) {
        try {
            $pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
        } catch(PDOException $e) {
            exit($e->getMessage());
        }
        $pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $user_id = $_GET['user_id'];

        $user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$user_id) , false, getContextCookies()), true);
        // To get to this script the user must have a useraccount, so we are going to assume that $user is populated
        $user_samaccountname = $user['user_samaccountname'];
        $json_ldap = json_decode(file_get_contents(request_ldap_api('/JSON_list_jfab_user_by_sam.php?samaccountname='.$user_samaccountname) , false, getContextCookies()), true);
        $user_firstname = trim($json_ldap['items'][0]['givenname']);
        $user_lastname = trim($json_ldap['items'][0]['sn']);
        $user_email = trim($json_ldap['items'][0]['mail']);
        $user_departmentnumber = intval($json_ldap['items'][0]['departmentNumber']);
        $user_manager_samaccountname = $json_ldap['items'][0]['manager_samaccountname']; // Note: this can be 0 length

        if(strlen($user_manager_samaccountname) > 0){
            $supervisor = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$user_manager_samaccountname) , false, getContextCookies()), true);
            if(isset($supervisor['user_id'])){
                $user_supervisor_id = $supervisor['user_id'];
            } else {
                $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($user_manager_samaccountname))), true);
                if($json_add['success'] == true) {
                    $user_supervisor_id = $json_add['user_id'];
                } else {
                    $user_supervisor_id = 0;
                }
                unset($json_add);
            }
        } else {
            $user_supervisor_id = 0;
        }

        $sth_mysql_user_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.user WHERE is_active = 1 AND user_id = :user_id AND user_samaccountname = :user_samaccountname AND user_firstname = :user_firstname AND user_lastname = :user_lastname AND user_email = :user_email AND user_supervisor_id = :user_supervisor_id;');
        $sth_mysql_user_count->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $sth_mysql_user_count->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
        $sth_mysql_user_count->bindParam(':user_firstname', $user_firstname, PDO::PARAM_STR);
        $sth_mysql_user_count->bindParam(':user_lastname', $user_lastname, PDO::PARAM_STR);
        $sth_mysql_user_count->bindParam(':user_email', $user_email, PDO::PARAM_STR);
        $sth_mysql_user_count->bindParam(':user_supervisor_id', $user_supervisor_id, PDO::PARAM_INT);
        $sth_mysql_user_count->execute();
        $usercount_mysql_cert = $sth_mysql_user_count->fetch(PDO::FETCH_ASSOC);
        $user_last_ldap_check = time();
        if($usercount_mysql_cert['mycount'] < 1){
            $sth_mysql_user_update = $pdo_mysql->prepare('UPDATE tcs.user SET user_samaccountname = :user_samaccountname , user_supervisor_id = :user_supervisor_id, user_firstname = :user_firstname, user_lastname = :user_lastname, user_email = :user_email, user_last_ldap_check = :user_last_ldap_check WHERE user_id = :user_id;');
            $sth_mysql_user_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth_mysql_user_update->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
            $sth_mysql_user_update->bindParam(':user_supervisor_id', $user_supervisor_id, PDO::PARAM_INT);
            $sth_mysql_user_update->bindParam(':user_firstname', $user_firstname, PDO::PARAM_STR);
            $sth_mysql_user_update->bindParam(':user_lastname', $user_lastname, PDO::PARAM_STR);
            $sth_mysql_user_update->bindParam(':user_email', $user_email, PDO::PARAM_STR);
            $sth_mysql_user_update->bindParam(':user_last_ldap_check', $user_last_ldap_check, PDO::PARAM_INT);
            $sth_mysql_user_update->execute();
        } else {
            $sth_mysql_user_update = $pdo_mysql->prepare('UPDATE tcs.user SET user_last_ldap_check = :user_last_ldap_check WHERE user_id = :user_id;');
            $sth_mysql_user_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth_mysql_user_update->bindParam(':user_last_ldap_check', $user_last_ldap_check, PDO::PARAM_INT);
            $sth_mysql_user_update->execute();
        }
        if($user_departmentnumber != 0){
            $template_department_number = $user_departmentnumber;
            $sth_mysql_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.template WHERE is_active = 1 AND template_department_number = :template_department_number;');
            $sth_mysql_count->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
            $sth_mysql_count->execute();
            $count_mysql = $sth_mysql_count->fetch(PDO::FETCH_ASSOC);
            if($count_mysql['mycount'] < 1){
                $json_sync_templates = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_sync_departments.php')), true);
                $template_id = $json_sync_templates['items'][$user_departmentnumber]['template_id'];
                unset($json_sync_templates);
            } else {
                $sth_mysql_get_id = $pdo_mysql->prepare('SELECT template_id FROM tcs.template WHERE template_is_default_for_department = 1 AND template_department_number = :template_department_number AND is_active = 1;');
                $sth_mysql_get_id->bindParam(':template_department_number', $template_department_number, PDO::PARAM_INT);
                $sth_mysql_get_id->execute();
                $template_mysql_get_id = $sth_mysql_get_id->fetch(PDO::FETCH_ASSOC);
                $template_id = (int)$template_mysql_get_id['template_id'];
            }
            $sth_mysql_template_count = $pdo_mysql->prepare('SELECT count(*) as "mycount" FROM tcs.template_user_links WHERE template_id = :template_id AND user_id = :user_id;');
            $sth_mysql_template_count->bindParam(':template_id', $template_id, PDO::PARAM_INT);
            $sth_mysql_template_count->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $sth_mysql_template_count->execute();
            $templatecount_mysql_cert = $sth_mysql_template_count->fetch(PDO::FETCH_ASSOC);
            if($templatecount_mysql_cert['mycount'] < 1){
                $sth_mysql_delete = $pdo_mysql->prepare('DELETE FROM tcs.template_user_links WHERE template_id IN (SELECT template_id FROM tcs.template WHERE template_is_default_for_department = 1 AND is_active = 1) AND user_id = :user_id');
                $sth_mysql_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $sth_mysql_delete->execute();
                $sth_mysql_insert = $pdo_mysql->prepare('INSERT INTO tcs.template_user_links (template_id, user_id) VALUES (:template_id, :user_id);');
                $sth_mysql_insert->bindParam(':template_id', $template_id, PDO::PARAM_INT);
                $sth_mysql_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $sth_mysql_insert->execute();
            }
        }
        $arr['success'] = true;
        // Close connection to DB
        unset($pdo_mysql);
    } else {
        $arr['success'] = false;
        $arr['error'] = 'invalid get values passed';
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
