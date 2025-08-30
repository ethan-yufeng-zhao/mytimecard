<!DOCTYPE html>
<html>
<head>
    <title>Training Certification System</title>
    <meta charset="utf-8">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<?php
    // parse_xlsx/import_normal_templates.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    $json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_normal_templates.php'), false, getContextCookies()), true);
    foreach ($json['items'] as $value) {
        $fields = array();
        $fields['template_id'] = $value['template_id'];
        $fields['cert_id'] = $value['cert_id'];

        $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_cert_to_template.php?'.http_build_query($fields)), false, getContextCookies()), true);

        if (isset($json_add['success']) && $json_add['success']) {
            echo('<div class="alert alert-success">');
            echo('<p><strong>Success:</strong> Cert has been added to template</p>');
            echo('</div>');
        } else {
            echo('<div class="alert alert-danger">');
            echo('<p><strong>Error:</strong> Cert was unable to be added to template</p>');
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
