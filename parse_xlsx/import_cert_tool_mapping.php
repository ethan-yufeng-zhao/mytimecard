<!DOCTYPE html>
<html>
<head>
    <title>Training Certification System</title>
    <meta charset="utf-8">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<?php
    // parse_xlsx/import_cert_tool_mapping.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    $json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_cert_tool_mapping.php'), false, getContextCookies()), true);
    foreach ($json['row'] as $key => $value) {
        $fields = array();
        $fields['cert_tool'] = $value['cert_tool'];
        $fields['cert_name'] = $value['cert_name'];
//        $fields['cert_days_active'] = $value['cert_days_active'];
//        $fields['cert_description'] = $value['cert_description'];
//        $fields['cert_never_expires'] = $value['cert_never_expires'];
//        $fields['cert_is_iso'] = $value['cert_is_iso'];
//        $fields['cert_is_safety'] = $value['cert_is_safety'];
//        $fields['cert_is_ert'] = '';
//        $fields['cert_last_user'] = 'auto';
//        $fields['cert_notes'] = '';  // Because this is a text field we are going to send it via POST
        // echo($mybaseurl.'/JSON/JSON_ACTION_add_cert.php?'.http_build_query($fields));

        $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_cert_tool_mapping.php?'.http_build_query($fields)), false, getContextCookies()), true);
        if (isset($json_add['success']) && $json_add['success'] == true) {
            echo('<div class="alert alert-success">');
            echo('<p><strong>Success:</strong> Tool has been added to the cert</p>');
            if(isset($json_add['message'])) {
                echo('<p style="margin-left:2em;">'.$json_add['message'].'</p>');
            }
            echo('</div>');
        } else {
            echo('<div class="alert alert-danger">');
            echo('<p><strong>Error:</strong> Tool was unable to be added</p>');
            if(isset($json_add['error'])) {
                echo('<p style="margin-left:2em;">'.$json_add['error'].'</p>');
            } else {
                echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
            }
            echo('</div>');
        }
    }
?>

</body>
</html>
