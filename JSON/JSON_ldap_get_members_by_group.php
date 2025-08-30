<?php
    // test api from jfabweb2
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    if(isset($_GET['group_name']) && strlen($_GET['group_name']) > 0 ) {
        $group_name = $_GET['group_name'];
        logit($group_name);

        $value = file_get_contents(request_ldap_api("/JSON_get_one_group_info.php?group=CN=" . urlencode($group_name) . ",OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com"), false, getContextCookies());
        // return null if decoding
//        $value = json_decode(file_get_contents(request_ldap_api("/JSON_get_one_group_info.php?group=CN=".urlencode($group_name).",OU=Groups,OU=JFab,DC=jfab,DC=aosmd,DC=com") , false, getContextCookies()), true);


        header('Content-Type: application/json');
        echo(trim($value));
//        echo(json_encode($value));
    } else {
        header('Content-Type: application/json');
        echo(json_encode(null));
        //echo(json_encode($json_ldap));
    }


