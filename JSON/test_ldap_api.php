<?php
    // test api from jfabweb2
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

//    $json_ldap = file_get_contents(request_ldap_api("/JSON_get_one_group_info.php?group=CN=HILUSERS_INTL,OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com") , false, getContextCookies());
    $group_name = "**** JFAB - ERT Members";
    logit($group_name);
    $json_ldap = file_get_contents(request_ldap_api("/JSON_get_one_group_info.php?group=CN=".urlencode($group_name).",OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com") , false, getContextCookies());
//CN=**** JFAB - ERT Members,OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com
//    $json_ldap = file_get_contents(request_ldap_api("/JSON_list_jfab_users.php") , false, getContextCookies());
//    $json_ldap = file_get_contents(request_ldap_api('/JSON_list_jfab_user_by_sam.php?samaccountname='.'ethan.zhao') , false, getContextCookies());
//    $json_ldap = file_get_contents(request_ldap_api("/JSON_find_jfab_ldap_problems.php"));
//    $json_ldap = file_get_contents(request_ldap_api("/find_jfab_phonenumber.php"));

    header('Content-Type: application/json');
    echo(trim($json_ldap));
