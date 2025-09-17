<?php
if (DEBUG) {
    $_SERVER["REMOTE_USER"] = "\\csturgeo"; // "\\aj.ty"; // "\\aaliyah.harrison"; // "\\dnickles";
//	if(!isset($_SERVER["REMOTE_USER"]) || $_SERVER["REMOTE_USER"] == '') {
//		header('HTTP/1.1 401 Unauthorized');
//		header('WWW-Authenticate: Negotiate');
//		header('WWW-Authenticate: NTLM', false);
//		exit();
//	}
//
    $REMOTE_USER = explode("\\", $_SERVER["REMOTE_USER"]);
    logit('$REMOTE_USER = '.$REMOTE_USER[1]);
} else {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])){
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: NTLM');
        exit;
    }

    $auth = $headers['Authorization'];
    if (substr($auth,0,5) == 'NTLM ') {
        $msg = base64_decode(substr($auth, 5));
        if (substr($msg, 0, 8) != "NTLMSSP\x00") {
            die('error header not recognised');
        }

        if ($msg[8] == "\x01") {
            $msg2 = "NTLMSSP\x00\x02\x00\x00\x00".
                "\x00\x00\x00\x00". // target name len/alloc
                "\x00\x00\x00\x00". // target name offset
                "\x01\x02\x81\x00". // flags
                "\x00\x00\x00\x00\x00\x00\x00\x00". // challenge
                "\x00\x00\x00\x00\x00\x00\x00\x00". // context
                "\x00\x00\x00\x00\x00\x00\x00\x00"; // target info len/alloc/offset
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: NTLM ' . trim(base64_encode($msg2)));
            exit;
        } else if ($msg[8] == "\x03") {
            function get_msg_str($msg, $start, $unicode = true) {
                $len = (ord($msg[$start+1]) * 256) + ord($msg[$start]);
                $off = (ord($msg[$start+5]) * 256) + ord($msg[$start+4]);
                if ($unicode) {
                    return str_replace("\0", '', substr($msg, $off, $len));
                } else {
                    return substr($msg, $off, $len);
                }
            }
            $username = strtolower(get_msg_str($msg, 36));
            $REMOTE_USER[1] = strtolower(get_msg_str($msg, 36));
            /* Kent
            print_r( $REMOTE_USER );
            $REMOTE_USER[1] = 'dkennedy';
            print_r( $REMOTE_USER );
             */

            //  $domain = strtolower(get_msg_str($msg, 28));
            //  $workstation = strtolower(get_msg_str($msg, 44));
            //  $logger->debug("NTLM authenticated: username=$username, domain=$domain, workstation=$workstation");
        }
    }
}
$loggedInUser = $REMOTE_USER[1];

$user['user_is_admin'] = false;
$user['user_is_supervisor'] = false;

$json_meta = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_meta.php?uid='.$loggedInUser), false, getContextCookies()), true);
if ($json_meta) {
    $login_role = $json_meta[$loggedInUser]['meta']['role'] ?? '';
    $user['user_firstname'] = $json_meta[$loggedInUser]['meta']['givenname'] ?? '';
    $user['user_lastname'] = $json_meta[$loggedInUser]['meta']['sn'] ?? '';
    $user['manager'] = $json_meta[$loggedInUser]['meta']['manager'] ?? '';
} else {
    $login_role = '';
}
if ($login_role === 'admin') {
    $user['user_is_admin'] = true;
} else if ($login_role === 'supervisor') {
    $user['user_is_supervisor'] = true;
}

if(isset($_GET['uid']) && strlen($_GET['uid']) > 0) {
    $requested_user_id = $_GET['uid'];
} else {
    $requested_user_id = $loggedInUser;
}

$authorized = false;

if(($requested_user_id === $loggedInUser) || $user['user_is_admin']){ // A user can always view themselves
    $authorized = true;
} else {
    if ($user['user_is_supervisor']) {
        $req_meta = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_meta.php?uid='.$requested_user_id), false, getContextCookies()), true);
        $user_supervisor_id = $req_meta[$requested_user_id]['meta']['manager'] ?? '';
        if ($user_supervisor_id === $loggedInUser)  {
            $authorized = true;
        } else {
            $all_supervisors = $req_meta[$requested_user_id]['meta']['all_supervisors'] ?? [];
            if (in_array($loggedInUser, $all_supervisors)) {
                $authorized = true;
            }
        }
    }
}
