<?php
// all_certs.php
include_once('header.php');  // has everything up to the container div in the body

$authorized = false;
if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
    $authorized = true;
}

if($authorized){
    // true delete
    if (isset($_POST['true_delete_cert']) && is_numeric($_POST['true_delete_cert'])) { // Delete
        // TODO delete this certificate
        $json_delete = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_true_delete_cert.php?cert_id='.$_POST['cert_id'].'&true_delete_cert='.$_POST['true_delete_cert'].'&cert_last_user='.$_POST['cert_last_user']) , false, getContextCookies()), true);
        if (isset($json_delete['success']) && $json_delete['success'] == true) {
            // echo('<p style ="background-color:yellow;">'.$json_delete['message'].'</p>');

            echo('<div class="alert alert-dismissable alert-success">');
            echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
            echo('<strong>Success:</strong> '.$json_delete['message']);
            echo('</div>');
        } else {
            echo('<div class="alert alert-dismissable alert-danger">');
            echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
            echo('<p><strong>Error:</strong> Cert was unable to be deleted</p>');
            if(isset($json_delete['error'])) {
                echo('<p style="margin-left:2em;">'.$json_delete['error'].'</p>');
            } else {
                echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
            }
            echo('</div>');
            // echo('<p style ="background-color:red;">ERROR: Cert was unable to be deleted');
            // echo('<br>');
            // echo($json_delete['error']);
            // echo('</p>');
        }
    }
    // actually disable
    if (isset($_POST['delete_cert']) && is_numeric($_POST['delete_cert'])) { // Delete
        // TODO delete this certificate
        $json_delete = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_cert.php?cert_id='.$_POST['cert_id'].'&delete_cert='.$_POST['delete_cert'].'&cert_last_user='.$_POST['cert_last_user']) , false, getContextCookies()), true);
        if (isset($json_delete['success']) && $json_delete['success'] == true) {
            // echo('<p style ="background-color:yellow;">'.$json_delete['message'].'</p>');
            echo('<div class="alert alert-dismissable alert-success">');
            echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
            echo('<strong>Success:</strong> '.$json_delete['message']);
            echo('</div>');
        } else {
            echo('<div class="alert alert-dismissable alert-danger">');
            echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
            echo('<p><strong>Error:</strong> Cert was unable to be deleted</p>');
            if(isset($json_delete['error'])) {
                echo('<p style="margin-left:2em;">'.$json_delete['error'].'</p>');
            } else {
                echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
            }
            echo('</div>');
            // echo('<p style ="background-color:red;">ERROR: Cert was unable to be deleted');
            // echo('<br>');
            // echo($json_delete['error']);
            // echo('</p>');
        }
    }

    echo("<div id='jfabtable'>\n");
    echo('<table><tr>');
    echo('<td><h2 style="margin:0px;">');

    echo('All Certs');
    echo('</h2></td></tr>');
    echo('<tr><td>');
    echo(date('Y-m-d H:i:s'));
    echo('</td>');
    echo('</tr></table>');

    // echo('<p><a href="'.$mybaseurl.'/cert.php?edit=1\">Create a new Certification</a></p>');
    echo('<p><a target="_blank" href="'.$mybaseurl.'/cert.php?edit=1" class="btn btn-primary btn-sm hidden-print">Create a new Certification</a></p>');

    echo("<table class='tablesorter'>");
    echo("<thead>");
    echo("<tr>");

    echo("<th>");
    echo("Cert ID");
    echo("</th>");

    echo("<th>");
    echo("Cert Name");
    echo("</th>");

    echo("<th>");
    echo("Description");
    echo("</th>");

    echo("<th>");
    echo("Days Active");
    echo("</th>");

    // echo("<th>");
    // echo("Never Expires");
    // echo("</th>");

    echo("<th>");
    echo("ERT");
    echo("</th>");

    echo("<th>");
    echo("ISO");
    echo("</th>");

    echo("<th>");
    echo("Safety");
    echo("</th>");

    if ($GLOBALS['DB_TYPE'] == 'pgsql'){
        echo("<th>");
        echo("Tools");
        echo("</th>");
        echo("<th>");
        echo("Points");
        echo("</th>");
    } else {
        echo("<th>");
        echo("Warnings");
        echo("</th>");
    }

    echo("</tr>\n");
    echo("</thead>");

    echo("<tbody>\n");

    $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php') , false, getContextCookies()), true);
    $count = 0;
    if ($json) {
        foreach ($json as $key => $value) {
            $count++;
            echo('<tr>');

            echo('<td>');
            echo($value['cert_id']);
            echo("</td>\n");

            echo('<td>');
            echo('<a target="_blank" href="'.$mybaseurl.'/cert.php?cert_id='.$key.'">');
            echo($value['cert_name']);
            echo('</a>');
            if(!$value['cert_is_active']) {
                echo('<span style="display:none;">||||</span> <span class="label label-warning">Inactive</span>');
            }
            echo("</td>\n");

            echo('<td>');
            echo($value['cert_description']);
            echo("</td>\n");

            echo('<td>');
            if($value['cert_never_expires'] == 0){
                // echo('Y');
                echo($value['cert_days_active']);
            }
            if($value['cert_never_expires'] == 1){
                echo('never expires');
            }
            echo("</td>\n");

            // echo('<td>');
            // if($value['cert_never_expires'] == 1){
            // 	echo('Y');
            // }
            // echo("</td>\n");

            echo('<td>');
            if($value['cert_is_ert'] == 1){
                echo('Y');
            }
            echo("</td>\n");

            echo('<td>');
            if($value['cert_is_iso'] == 1){
                echo('Y');
            }
            echo("</td>\n");

            echo('<td>');
            if($value['cert_is_safety'] == 1){
                echo('Y');
            }
            echo("</td>\n");

            if ($GLOBALS['DB_TYPE'] == 'pgsql'){
                echo('<td>');
                if($value['tool'] !=null && count($value['tool']) > 0) {
                    echo(count($value['tool']));
                }
                echo("</td>\n");
                echo('<td>');
                echo($value['cert_points'] ?? 0);
                echo("</td>\n");
            } else {
                echo('<td>');
                if(count($value['warning']) > 0) {
                    echo(count($value['warning']));
                }
                echo("</td>\n");
            }

            echo("</tr>\n");
        }
    }
    echo("</tbody>");
    echo("</table>\n");
    // echo('<p><a href="'.$mybaseurl.'/cert.php?edit=1">Create a new Certification</a></p>');
//    echo('<p>&nbsp;</p>');
    echo("</div>\n");

    echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
    echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' onsubmit='return saveToExcel();'>\n");
    echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
    echo("<input type='hidden' id='filename' name='filename' value='All_certs.xls'>");
    echo("</form>");
//    echo('<p>&nbsp;</p>');
} else {
    echo('<div class="alert alert-danger">');
    echo('<p>Authorization failed</p>');
    echo('</div>');
}

include_once('footer.php');
