<?php
    // JSON/JSON_rebuild_template_certs.php
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    $arr = array();
    $out = array();
    $cert_list = array();

    $querystring='';
    $db_pdo = db_connect();

    // select * from tcs.user where departmentnumber is not null order by departmentnumber asc, ad_account asc;
    $querystring='SELECT cert_id FROM tcs.cert order by cert_id;';
    $cert_arr = db_query($db_pdo, $querystring);

    foreach ($cert_arr as $ck => $cv){
        $cert_list[]=$cert_arr[$ck]['cert_id'];
    }

    $querystring='SELECT template_id, certs FROM tcs.template order by template_id;';
    $db_arr = db_query($db_pdo, $querystring);

    foreach ($db_arr as $key => $data ) {
        $temp_arr = array();

        $temp_arr['template_id'] = (int)$data['template_id'];
        $temp_arr['certs'] = json_decode($data['certs'] ?? '{}', true);

        $arr[$temp_arr['template_id']] = array();

        foreach ($temp_arr['certs'] as $k => $v) {
            if (in_array($v, $cert_list)){
                $arr[$temp_arr['template_id']][] = $v;
            }
        }
        unset($temp_arr);
    }

    //-- Auto-generated SQL script #202402291117
    //UPDATE tcs."template"
    //	SET certs='[12, 48, 51, 52, 64, 74, 81, 82, 95, 239]'::jsonb
    //	WHERE template_id=11;
    foreach ($arr as $template_id => $certs) {
        $updatestring = 'update tcs.template set certs=';
        $updatestring .= "'".json_encode($certs)."'::jsonb";
        $updatestring .= ' where template_id='. $template_id;

        $db_arr = db_update($db_pdo, $updatestring);
        if ($db_arr) {
            $out[$template_id] = count($certs);
        } else {
            $out[$template_id] = -1;
        }
    }

    // Close connection to DB
    $db_pdo = null;

    header('Content-Type: application/json');
    echo(json_encode($out));
