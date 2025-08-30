<?php
    // test api from jfabweb2
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');
    require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

    $out = array();

    $updatestring = '';
    $db_pdo = db_connect();

    $templateid = 32;
    $templatename = 'ERT';
    $group_name = '**** JFAB - ERT Members';

    /**
     * @param string $group_name
     * @param array $users
     * @param int $templateid
     * @param string $templatename
     * @param PDO $db_pdo
     * @param array $out
     * @return array
     */
    function getOut(string $group_name, int $templateid, string $templatename, PDO $db_pdo):array
    {
        global $out;
        $users = array();

    //    $group_name = 'HILUSERS_INTL';
        // /JSON/JSON_ldap_get_members_by_group.php?group_name=HILUSERS_INTL

        $json_string = file_get_contents(request_json_api('/JSON/JSON_ldap_get_members_by_group.php?group_name=' . urlencode($group_name)), false, getContextCookies());
    //    $json_string = file_get_contents(request_json_api('/JSON/JSON_ldap_get_members_by_group.php'), false, getContextCookies());
        // cannot decode
        //$json_string = json_decode(file_get_contents(request_json_api('/JSON/JSON_ldap_get_members_by_group.php?group_name='.urlencode($group_name)), false, getContextCookies()), true);

        // Byte sequence for ZWNBSP
        $json_string = preg_replace('/\xEF\xBB\xBF/', '', $json_string);
        $data = json_decode($json_string, true);
        // \uFEFF doesnot work
        //$json_string = str_replace("\uFEFF", "", $json_string);
        //$data = json_decode($json_string, true);

        if (!is_null($data)) {
            foreach ($data['member'] as $key => $value) {
                $users[] = $value['samaccountname'];
            }

            $updatestring = 'update tcs.template set users=';
            $updatestring .= "'" . json_encode($users) . "'::jsonb";
            $updatestring .= ' where template_id=' . $templateid;
            $updatestring .= ' and template_name=\'' . $templatename . '\'';

            $db_arr = db_update($db_pdo, $updatestring);
            if ($db_arr) {
                $out[$templatename] = count($users);
            } else {
                $out[$templatename] = -1;
            }
        }
        return $out;
    }

//    $out = getOut($group_name, $templateid, $templatename, $db_pdo, $out);
    getOut($group_name, $templateid, $templatename, $db_pdo);

//    $templateid = 32;
//    $templatename = 'ERT';
//    $group_name = '**** JFAB - ERT Members';
//    getOut($group_name, $templateid, $templatename, $db_pdo);

    // Close connection to DB
    $db_pdo = null;

    header('Content-Type: application/json');
    echo(json_encode($out));

