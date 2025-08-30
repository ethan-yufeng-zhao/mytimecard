<!DOCTYPE html>
<html>
<head>
    <title>Training Certification System</title>
    <meta charset="utf-8">
    <link href="../css/bootstrap.min.css" rel="stylesheet">
</head>
<body>


<?php
    // parse_xlsx/import_certs.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    $json = json_decode(file_get_contents(request_json_api('/parse_xlsx/JSON_xlsx_parse_certs.php'), false, getContextCookies()), true);
    foreach ($json['certs'] as $cert_name => $value) {
        $fields = array();
        $fields['cert_name'] = $value['cert_name'];
        $fields['cert_days_active'] = $value['cert_days_active'];
        $fields['cert_description'] = $value['cert_description'];
        $fields['cert_never_expires'] = $value['cert_never_expires'];
        $fields['cert_is_iso'] = $value['cert_is_iso'];
        $fields['cert_is_safety'] = $value['cert_is_safety'];
        $fields['cert_is_ert'] = '';
        $fields['cert_last_user'] = 'auto';
        $fields['cert_notes'] = '';  // Because this is a text field we are going to send it via POST
        // echo($mybaseurl.'/JSON/JSON_ACTION_add_cert.php?'.http_build_query($fields));

        $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_cert.php?'.http_build_query($fields)), false, getContextCookies()), true);
        if (isset($json_add['success']) && $json_add['success'] == true) {
            echo('<div class="alert alert-success">');
            echo('<p><strong>Success:</strong> Cert has been added</p>');
            echo('</div>');

            if($json_add['cert_never_expires'] == 0) {
                $json_add_warning_30 = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_warning.php?cert_id='.$json_add['cert_id'].'&warning_number_of_days=30&warning_last_user=auto'), false, getContextCookies()), true);
                if (isset($json_add_warning_30['success']) && $json_add_warning_30['success'] == true) {
                    echo('<div class="alert alert-success">');
                    echo('<strong>Success:</strong> 30 Day Warning has been added');
                    echo('</div>');
                } else {
                    echo('<div class="alert alert-danger">');
                    echo('<p><strong>Error:</strong> 30 day Warning was unable to be added</p>');
                    if(isset($json_add_warning_30['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_add_warning_30['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');
                }

                $json_add_warning_7 = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_warning.php?cert_id='.$json_add['cert_id'].'&warning_number_of_days=7&warning_last_user=auto'), false, getContextCookies()), true);
                if (isset($json_add_warning_7['success']) && $json_add_warning_7['success'] == true) {
                    echo('<div class="alert alert-success">');
                    echo('<strong>Success:</strong> 7 Day Warning has been added');
                    echo('</div>');
                } else {
                    echo('<div class="alert alert-danger">');
                    echo('<p><strong>Error:</strong> 7 day Warning was unable to be added</p>');
                    if(isset($json_add_warning_7['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_add_warning_7['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');
                }

                // $json_add_warning_1 = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_warning.php?cert_id='.$json_add['cert_id'].'&warning_number_of_days=1&warning_last_user=auto'), true);
                // if (isset($json_add_warning_1['success']) && $json_add_warning_1['success'] == true) {
                //     echo('<div class="alert alert-success">');
                //     echo('<strong>Success:</strong> 1 Day Warning has been added');
                //     echo('</div>');
                // } else {
                //     echo('<div class="alert alert-danger">');
                //     echo('<p><strong>Error:</strong> 1 day Warning was unable to be added</p>');
                //     if(isset($json_add_warning_1['error'])) {
                //         echo('<p style="margin-left:2em;">'.$json_add_warning_1['error'].'</p>');
                //     } else {
                //         echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                //     }
                //     echo('</div>');
                // }

                $json_add_warning_0 = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_warning.php?cert_id='.$json_add['cert_id'].'&warning_number_of_days=0&warning_last_user=auto'), false, getContextCookies()), true);
                if (isset($json_add_warning_0['success']) && $json_add_warning_0['success'] == true) {
                    echo('<div class="alert alert-success">');
                    echo('<strong>Success:</strong> 0 Day Warning has been added');
                    echo('</div>');
                } else {
                    echo('<div class="alert alert-danger">');
                    echo('<p><strong>Error:</strong> 0 day Warning was unable to be added</p>');
                    if(isset($json_add_warning_0['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_add_warning_0['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');
                }
            }
        } else {
            echo('<div class="alert alert-danger">');
            echo('<p><strong>Error:</strong> Cert was unable to be added</p>');
            if(isset($json_add['error'])) {
                echo('<p style="margin-left:2em;">'.$json_add['error'].'</p>');
            } else {
                echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
            }
            echo('</div>');
        }

        // var_dump($fields);
        // exit();

        // $curl = curl_init($mybaseurl.'/JSON/JSON_ACTION_add_cert.php');
        // curl_setopt($curl, CURLOPT_POST, true);
        // curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // $json_add = json_decode(curl_exec($curl), true);

        // $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        // var_dump($json_add);
        // if ( $status != 200 ) {
        //     echo('<div class="alert alert-danger">');
        //     echo('<p><strong>Error:</strong> Cert was unable to be added</p>');
        //     echo('<p style="margin-left:2em;">');
        //     echo("<strong>Error:</strong> call to add URL failed with status " . $status . ", curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
        //     echo('</p>');
        //     echo('</div>');
        // } else {
        //     if (isset($json_add['success']) && $json_add['success'] == true) {
        //         echo('<div class="alert alert-success">');
        //         echo('<p><strong>Success:</strong> Cert has been added</p>');
        //         echo('</div>');
        //     } else {
        //         echo('<div class="alert alert-danger">');
        //         echo('<p><strong>Error:</strong> Cert was unable to be added</p>');
        //         if(isset($json_add['error'])) {
        //             echo('<p style="margin-left:2em;">'.$json_add['error'].'</p>');
        //         } else {
        //             echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
        //         }
        //         echo('</div>');
        //     }
        // }
        // curl_close($curl);
    }
?>

</body>
</html>
