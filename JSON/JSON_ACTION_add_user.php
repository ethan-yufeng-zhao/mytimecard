<?php
    // JSON/JSON_ACTION_add_user.php?user_samaccountname=jbravo&user_firstname=Johnny&user_lastname=Bravo&user_email=johnny.bravo@jfab.aosmd.com
    // JSON/JSON_ACTION_add_user.php?user_samaccountname=terence.huang
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    $arr = array();
    // TODO: fix ldap sync to pass all these values when creating a new user

    if (isset($_GET['user_samaccountname']) && strlen($_GET['user_samaccountname']) > 0) {
        $sam_arr = array();
        $add_user = true;
        $user_samaccountname = $_GET['user_samaccountname'];
        if (isset($_GET['user_firstname'])
            && strlen($_GET['user_firstname']) > 0
            && isset($_GET['user_lastname'])
            && strlen($_GET['user_lastname']) > 0
            && isset($_GET['user_email'])
            && strlen($_GET['user_email']) > 0
            && isset($_GET['user_departmentnumber'])
            && strlen($_GET['user_departmentnumber']) > 0
            && is_numeric($_GET['user_departmentnumber'])
            && isset($_GET['user_manager_samaccountname'])
        ) {
            $sam_arr[] = $_GET['user_samaccountname'];
            $user_firstname = $_GET['user_firstname'];
            $user_lastname = $_GET['user_lastname'];
            $user_email = $_GET['user_email'];
            $user_departmentnumber = intval($_GET['user_departmentnumber']);
            $user_manager_samaccountname = $_GET['user_manager_samaccountname']; // Note: this can be 0 length
        } else {
            $user_firstname = '';
            $user_lastname = '';
            $user_email = '';
            $user_departmentnumber = 0;
            $user_manager_samaccountname = '';
            $json_ldap = json_decode(file_get_contents(request_ldap_api('/JSON_list_jfab_user_by_sam.php?samaccountname='.$user_samaccountname)), true);
            $user_ldap_arr = array();
            if($json_ldap['count'] == 0){
                $add_user = false;
            }
            foreach($json_ldap['items'] as $value){  // Get all user info and order it by samaccountname
                $sam_arr[] = $value['samaccountname'];
                if (isset($value['samaccountname'])
                    && $value['samaccountname'] == $user_samaccountname
                    && stripos($value['distinguishedname'], "OU=Service Accounts") === false
                    && stripos($value['distinguishedname'], "OU=Museum") === false
                    && stripos($value['distinguishedname'], "OU=UCMBE Voicemail Service Accounts") === false
                ) {
                    $user_firstname = trim($value['givenname']);
                    $user_lastname = trim($value['sn']);
                    $user_email = trim($value['mail']);
                    $user_departmentnumber = intval($value['departmentNumber']);
                    $user_manager_samaccountname = $value['manager_samaccountname']; // Note: this can be 0 length
                }
            }
            unset($json_ldap);
        }

        try{
            $pdo_mysql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
        } catch(PDOException $e) {
            exit($e->getMessage());
        }// if(strlen(trim($user_manager_samaccountname)) > 0 && in_array($user_manager_samaccountname, $sam_arr)){
        $pdo_mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // if(strlen(trim($user_manager_samaccountname)) > 0 && in_array($user_manager_samaccountname, $sam_arr)){
        if(strlen(trim($user_manager_samaccountname)) > 0) {
            // var_dump($user_manager_samaccountname);
            $supervisor = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$user_manager_samaccountname)), true);
            // var_dump($supervisor);
            // exit();
            if(count($supervisor) < 1){
                $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($user_manager_samaccountname))), true);
                if($json_add['success'] == true) {
                    $user_supervisor_id = $json_add['user_id'];
                } else {
                    $user_supervisor_id = 0;
                }
                unset($json_add);
            } else {
                $user_supervisor_id = $supervisor['user_id'];
            }
            unset($supervisor);
        } else {
            $user_supervisor_id = 0;
        }

        // $user_supervisor_id = 0;
        if(isset($add_user) && $add_user == true){
            $sth_mysql_user_count = $pdo_mysql->prepare('SELECT COUNT(*) AS "mycount" FROM tcs.user WHERE is_active = 1 AND user_samaccountname = :user_samaccountname;');
            $sth_mysql_user_count->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
            $sth_mysql_user_count->execute();
            $usercount_mysql_cert = $sth_mysql_user_count->fetch(PDO::FETCH_ASSOC);
            if($usercount_mysql_cert['mycount'] < 1){
                $user_last_ldap_check = time();
                $sth_mysql_user_insert = $pdo_mysql->prepare('INSERT INTO tcs.user (user_samaccountname, user_firstname, user_lastname, user_email, user_supervisor_id, user_last_ldap_check) VALUES (:user_samaccountname, :user_firstname, :user_lastname, :user_email, :user_supervisor_id, :user_last_ldap_check);');
                $sth_mysql_user_insert->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
                $sth_mysql_user_insert->bindParam(':user_firstname', $user_firstname, PDO::PARAM_STR);
                $sth_mysql_user_insert->bindParam(':user_lastname', $user_lastname, PDO::PARAM_STR);
                $sth_mysql_user_insert->bindParam(':user_email', $user_email, PDO::PARAM_STR);
                $sth_mysql_user_insert->bindParam(':user_supervisor_id', $user_supervisor_id, PDO::PARAM_INT);
                $sth_mysql_user_insert->bindParam(':user_last_ldap_check', $user_last_ldap_check, PDO::PARAM_INT);
                $sth_mysql_user_insert->execute();
                $arr['success'] = true;
                $arr['user_id'] = (int)$pdo_mysql->lastInsertId();


                $template_id = 1; // The JFab Employees template
                $user_id = $arr['user_id'];
                $sth_mysql_insert = $pdo_mysql->prepare('INSERT INTO tcs.template_user_links (template_id, user_id) VALUES (:template_id, :user_id);');
                $sth_mysql_insert->bindParam(':template_id', $template_id, PDO::PARAM_INT);
                $sth_mysql_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $sth_mysql_insert->execute();


            } else {
                $sth_mysql_get_user_id = $pdo_mysql->prepare('SELECT user_id FROM tcs.user WHERE is_active = 1 AND user_samaccountname = :user_samaccountname;');
                $sth_mysql_get_user_id->bindParam(':user_samaccountname', $user_samaccountname, PDO::PARAM_STR);
                $sth_mysql_get_user_id->execute();
                $user_mysql_get_id = $sth_mysql_get_user_id->fetch(PDO::FETCH_ASSOC);
                $arr['success'] = true;
                $arr['user_id'] = (int)$user_mysql_get_id['user_id'];
            }

            $user_id = $arr['user_id'];

            // TODO: check if template is already in database if so use that one.  If not do the below.

            if ($user_departmentnumber != 0) {

                $template_department_number = $user_departmentnumber;
                // var_dump($template_department_number);

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
            // TODO: Add employee to a non-department "JFab Employees" template
        } else {
            $arr['success'] = false;
            $arr['error'] = 'Unable to add user that is not in LDAP';
        }

        // Close connection to DB
        unset($pdo_mysql);

    } else {
        $arr['success'] = false;
        $arr['error'] = 'invalid get values passed';
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
