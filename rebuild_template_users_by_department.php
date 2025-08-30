<?php
    // get all users and their department
    // update template table by department
    include_once('header.php');
    // echo('<div class="span-22 append-1 prepend-1 last" style="margin-top:1em;">');
    echo('<h3>Rebuild template users by department.</h3>');

    $jsons_ldap = json_decode(file_get_contents(request_json_api('/JSON/JSON_rebuild_special_template_by_group.php') , false, getContextCookies()), true);$count = 0;
    foreach ($jsons_ldap as $departmentnumber => $users) {
        if($users < 0) {
            echo('<div class="alert alert-error">');
            echo('<p><strong>Error:</strong> Unable to rebuild template users by template #'.$departmentnumber.'</p>');
            echo('</div>');
        } else {
            $count++;
            echo('<div class="alert alert-success">');
            echo('<p>'.$count.': Success to rebuild template users ('.$users.') by template #'.$departmentnumber.'</p>');
            echo('</div>');
        }
    }
    unset($jsons_ldap);

    $jsons = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_department.php') , false, getContextCookies()), true);
    $count = 0;
    foreach ($jsons as $departmentnumber => $users) {
        if($users < 0) {
            echo('<div class="alert alert-error">');
            echo('<p><strong>Error:</strong> Unable to rebuild template users by department #'.$departmentnumber.'</p>');
            echo('</div>');
        } else {
            $count++;
            echo('<div class="alert alert-success">');
            echo('<p>'.$count.': Success to rebuild template users ('.$users.') by department #'.$departmentnumber.'</p>');
            echo('</div>');
        }
    }
    unset($jsons);

    include_once('footer.php');
