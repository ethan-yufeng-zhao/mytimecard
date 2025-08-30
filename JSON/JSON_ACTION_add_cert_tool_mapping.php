<?php
    // JSON/JSON_ACTION_add_cert.php // Requires POST data to return a value
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    $arr = array();

    if (isset($_GET['cert_name']) && strlen($_GET['cert_name']) > 0
        && isset($_GET['cert_tool']) && strlen($_GET['cert_tool']) > 0) {
        $cert_name = $_GET['cert_name'];
        $cert_tool = $_GET['cert_tool'];
        $parameters_found = true;
    } else {
        $parameters_found = false;
    }

    if ($parameters_found) {
        $querystring = '';
        $updatestring = '';
        $db_pdo = db_connect();
        $tool_arr = array();
        $tools = array();

        $querystring = 'select c.entities from tcs.cert c where c.cert_name = \''. $cert_name. '\';';
        $tool_arr = db_query($db_pdo, $querystring);
        if ($tool_arr == null) {
            $arr['success'] = false;
            $arr['error'] = 'Cannot find the cert with name: '.$cert_name;
        } else {
            if ($tool_arr[0]['entities'] != null) {
                $tools = json_decode($tool_arr[0]['entities']);
            }
            if (!in_array($cert_tool, $tools, true)) {
                $updatestring = "UPDATE tcs.cert set entities='";
                $tools[] = $cert_tool;
                sort($tools);
                $updatestring .= json_encode($tools);
                $updatestring .= "'::jsonb where cert_name = ";
                $updatestring .= '\''.$cert_name.'\';';

                if (db_update($db_pdo, $updatestring)) {
                    $arr['success'] = true;
                    $arr['message'] = 'Tool: '.$cert_tool.' has been added to the cert: '.$cert_name;
                } else {
                    $arr['success'] = false;
                    $arr['error'] = 'Database execute failed';
                }
            } else {
                $arr['success'] = false;
                $arr['error'] = 'Tool:'.$cert_tool.' is already assigned to the cert: '.$cert_name;
            }
        }
        // Close connection to DB
        $db_pdo = null;
    } else {
        $arr['success'] = false;
        $arr['error'] = 'invalid POST values passed for cert update';
    }

    header('Content-Type: application/json');
    echo(json_encode($arr));
