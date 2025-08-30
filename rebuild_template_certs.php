<?php
    // get all users and their department
    // update template table by department
    include_once('header.php');
    // echo('<div class="span-22 append-1 prepend-1 last" style="margin-top:1em;">');
    echo('<h3>Rebuild template certs.</h3>');

    $jsons = json_decode(file_get_contents(request_json_api('/JSON/JSON_rebuild_template_certs.php') , false, getContextCookies()), true);
    $count = 0;
    foreach ($jsons as $template_id => $counts) {
        if($counts < 0) {
            echo('<div class="alert alert-error">');
            echo('<p><strong>Error:</strong> Unable to rebuild template certs for template #'.$template_id.'</p>');
            echo('</div>');
        } else {
            $count++;
            echo('<div class="alert alert-success">');
            echo('<p>'.$count.': Success to rebuild template certs ('.$counts.') for template #'.$template_id.'</p>');
            echo('</div>');
        }
    }
    unset($jsons);

    include_once('footer.php');
