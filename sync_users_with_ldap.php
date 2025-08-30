<?php
    // sync_users_with_ldap.php

    // How the Sync is done:
        // TODO: Check to make sure the user name is set, and is_admin or is_supervisor == true
        // put all ldap users into an array
        // Put all tcs users into an array
        // mark inactive tcs users not in ldap
        // Add ldap users that are not in ldap
        // update any firstname, lastname, or email mismatches
        // unset the tcs user array
        // Put all tcs users back into an array
        // set the supervisor_id field from ldap array

    include_once('header.php');
    // echo('<div class="span-22 append-1 prepend-1 last" style="margin-top:1em;">');
    echo('<h3>Sync userlist with LDAP values.</h3>');


    $user_ldap_arr = array();
/**
 * this adds offsite users to the system as long as they are in the HILUSERS_INTL group
 */
    $json_offsite_ldap = json_decode(file_get_contents(request_ldap_api("/JSON_get_one_group_info.php?group=CN=HILUSERS_INTL,OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com") , false, getContextCookies()), true);
    foreach($json_offsite_ldap['member'] as $value) {
        $user_ldap_arr[trim($value['samaccountname'])] = array('firstname' => trim($value['givenname']), 'lastname' => trim($value['sn']), 'email' => trim($value['mail']), 'manager_samaccountname' => '', 'departmentnumber' => '');
    }
    unset($json_offsite_ldap);

/**
 * this adds onsite JFab users to the system
 */
    $json_ldap = json_decode(file_get_contents(request_ldap_api("/JSON_list_jfab_users.php") , false, getContextCookies()), true);
    foreach($json_ldap['items'] as $value){  // Get all user info and order it by samaccountname
        if(isset($value['samaccountname']) && strlen(trim($value['samaccountname'])) > 0 && stripos($value['distinguishedname'], "OU=Service Accounts") === false && stripos($value['distinguishedname'], "OU=Museum") === false && stripos($value['distinguishedname'], "OU=UCMBE Voicemail Service Accounts") === false){

            $user_ldap_arr[trim($value['samaccountname'])] = array('firstname' => trim($value['givenname']), 'lastname' => trim($value['sn']), 'email' => trim($value['mail']), 'manager_samaccountname' => trim($value['manager_samaccountname']), 'departmentnumber' => intval($value['departmentNumber']));
        }
    }
    unset($json_ldap);






    $user_tcs_arr = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php') , false, getContextCookies()), true);
    $user_tcs_sam_key_arr = array();


    foreach($user_tcs_arr as $value){
        if(!array_key_exists($value['user_samaccountname'], $user_ldap_arr)){
            $json_delete = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_mark_user_inactive.php?user_id='.$value['user_id']) , false, getContextCookies()), true);
            if($json_delete['success'] == true) {

                echo('<div class="alert alert-warning">');
                echo('<p>User '.$value['user_samaccountname'].' has been marked inactive</p>');
                echo('</div>');

            } else {
                echo('<div class="alert alert-danger">');
                echo('<p>User '.$value['user_samaccountname'].' has been marked inactive</p>');
                echo('<p><strong>Error:</strong> Unable to mark user '.$value['user_samaccountname'].' inactive.</p>');
                if(isset($json_delete['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_delete['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
            }
            unset($json_delete);
        } else {
            $user_tcs_sam_key_arr[$value['user_samaccountname']] = $value['user_id'];
        }
    }

    foreach($user_ldap_arr as $sam_key => $value){
        if (!array_key_exists($sam_key, $user_tcs_sam_key_arr)) {
            $usercheck = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$sam_key) , false, getContextCookies()), true);
            if(count($usercheck) < 1){
                $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($sam_key).'&user_firstname='.urlencode($value['firstname']).'&user_lastname='.urlencode($value['lastname']).'&user_email='.urlencode($value['email']).'&user_departmentnumber='.urlencode($value['departmentnumber']).'&user_manager_samaccountname='.urlencode($value['manager_samaccountname'])) , false, getContextCookies()), true);
                // echo($mybaseurl.'/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($sam_key).'&user_firstname='.urlencode($value['firstname']).'&user_lastname='.urlencode($value['lastname']).'&user_email='.urlencode($value['email']).'&user_departmentnumber='.urlencode($value['departmentnumber']).'&user_manager_samaccountname='.urlencode($value['manager_samaccountname']));
                // echo('<hr>');
                // exit();

                if($json_add['success'] == true) {
                    echo('<div class="alert alert-warning">');
                    echo('<p>User '.$sam_key.' has been added</p>');
                    echo('</div>');

                    // echo('<p style ="background-color:yellow;">User '.$sam_key.' has been added</p>');
                } else {

                    echo('<div class="alert alert-danger">');
                    echo('<p>User '.$value['user_samaccountname'].' has been marked inactive</p>');
                    echo('<p><strong>Error:</strong> Unable to add user '.$sam_key.'</p>');
                    if(isset($json_add['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_add['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');



                    // echo('<p style ="background-color:red;">ERROR: Unable to add user '.$sam_key);
                    // echo('<br>');
                    // echo('<hr>');

                    // echo($mybaseurl.'/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($sam_key).'&user_firstname='.urlencode($value['firstname']).'&user_lastname='.urlencode($value['lastname']).'&user_email='.urlencode($value['email']).'&user_departmentnumber='.urlencode($value['departmentnumber']).'&user_manager_samaccountname='.urlencode($value['manager_samaccountname']));



                    // echo('<hr>');
                    // echo($json_add['error']);
                    // echo('</p>');
                }
                unset($json_add);
            }
        } else {
            echo('<p>User '.$sam_key.' already in system</p>');
        }
    }

    unset($user_tcs_arr);
    unset($user_tcs_sam_key_arr);
















    // Now that the users are the same we just need to update any changes to firstname, lastname, email, and supervisor
    $user_tcs_arr = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php') , false, getContextCookies()), true);

    foreach($user_tcs_arr as $value){ // re-building the index to be able to lookup a user_id by sam name
        $user_tcs_sam_key_arr[$value['user_samaccountname']] = $value['user_id'];
    }

    foreach($user_tcs_arr as $value){
        if(strlen(trim($user_ldap_arr[$value['user_samaccountname']]['manager_samaccountname'])) > 0 && isset($user_tcs_sam_key_arr[$user_ldap_arr[$value['user_samaccountname']]['manager_samaccountname']])) {
            $user_supervisor_id = $user_tcs_sam_key_arr[$user_ldap_arr[$value['user_samaccountname']]['manager_samaccountname']];
        } else {
            $user_supervisor_id = 0;
        }
        if($value['user_firstname'] != $user_ldap_arr[$value['user_samaccountname']]['firstname'] || $value['user_lastname'] != $user_ldap_arr[$value['user_samaccountname']]['lastname'] || $value['user_email'] != $user_ldap_arr[$value['user_samaccountname']]['email'] || $value['user_supervisor_id'] != $user_supervisor_id) {
            $json_update = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_update_user.php?user_samaccountname='.urlencode($value['user_samaccountname']).'&user_firstname='.urlencode($user_ldap_arr[$value['user_samaccountname']]['firstname']).'&user_lastname='.urlencode($user_ldap_arr[$value['user_samaccountname']]['lastname']).'&user_email='.urlencode($user_ldap_arr[$value['user_samaccountname']]['email']).'&user_supervisor_id='.urlencode($user_supervisor_id)) , false, getContextCookies()), true);
            echo('<hr>');
            if($json_update['success'] == true) {
                echo('<div class="alert alert-warning">');
                echo('<p>User '.$value['user_samaccountname'].' has been updated</p>');
                echo('</div>');
            } else {
                echo('<div class="alert alert-danger">');
                echo('<p>User '.$value['user_samaccountname'].' has been marked inactive</p>');
                echo('<p><strong>Error:</strong> Unable to update user '.$value['user_samaccountname']);
                if(isset($json_update['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_update['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
            }
            unset($json_update);
        } else {
            echo('<p>email, firstname, lastname, and supervisor matches LDAP values for user '.$value['user_samaccountname'].'</p>');
        }
        unset($manager);
    }



    $json_departments = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_department_templates.php') , false, getContextCookies()), true);
    foreach ($json_departments as $user_id => $value) {
        if (isset($user_ldap_arr[$value['user_samaccountname']])
        	&& $user_ldap_arr[$value['user_samaccountname']]['departmentnumber'] == $value['template_department_number']
        	// TODO: if the user is in the all employee's group
        ) {
            echo('<p>The department for '.$value['user_samaccountname'].' matches LDAP</p>');
        } else {
            $json_sync_user = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_ldap_sync_user.php?user_id='.$value['user_id']) , false, getContextCookies()), true);
            if($json_sync_user['success'] == true) {
                echo('<div class="alert alert-warning">');
                echo('<p>Synced Department for '.$value['user_samaccountname'].'</p>');
                echo('</div>');
            } else {
                echo('<div class="alert alert-danger">');
                echo('<p>User '.$value['user_samaccountname'].' has been marked inactive</p>');
                echo('<p><strong>Error:</strong> Unable to sync user '.$value['user_samaccountname'].'</p>');
                if(isset($json_sync_user['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_sync_user['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
            }
            unset($json_sync_user);
        }
    }


















    // echo('</div>');

    include_once('footer.php');
?>
