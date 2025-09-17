<?php
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
