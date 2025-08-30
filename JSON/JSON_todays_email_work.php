<?php
    // JSON/JSON_todays_email_work.php
    // "Now remember, this is only a temporary fix - unless it works." - Red Green
    require_once('..'.DIRECTORY_SEPARATOR.'base.php');

    function logger( $message ) {
        $log_filename = '../logs/JSON_todays_email_work_' . date('Y_m') . '.txt';
        file_put_contents( $log_filename, $message . PHP_EOL, FILE_APPEND );
    }

    function getJson( $json_url ) {
        $json = array();
        try {
            if( $raw_file_data = file_get_contents( $json_url ) ) {
                $json = json_decode( $raw_file_data, true );
            } else {
                logger( 'Failed to get the JSON data from url: ' . $json_url );
                return $json;
            }
        } catch ( Exception $e ) {
            logger( 'Exception getting getJson JSON information: ' . $e->getMessage() );
            return $json;
        }
        if( isset( $json['errors'] ) ) {
            foreach( $json['errors'] as $error_value ) {
                logger( $error_value );
            }
        }
        return $json;
    }

    /*
     * get warning information this has the number of days ahead of time to warn someone
     */
    function getWarningArr() {
        $return_arr = array();
        $json_url = request_json_api('/JSON/JSON_all_certs.php');
        foreach( getJson( $json_url ) as $certkey => $certvalue ) {
            foreach( $certvalue['warning'] as $value ) {
                $return_arr[$certkey][] = $value['warning_number_of_days'];
            }
        }
        return $return_arr;
    }

    function getAlreadyNotifiedTodayUserCertsArr() {
        $return_arr = array();
        $json_url = request_json_api('/JSON/JSON_todays_notifications.php');
        foreach( getJson( $json_url )['items'] as $user_cert_id => $value ) {
            $return_arr[] = $user_cert_id;
        }
        return $return_arr;
    }

    function needNotificationOnThisCertToday( $cert_instance, $warning_arr ) {
        $cert_id = $cert_instance['cert_id'];
        if( !isset( $warning_arr[$cert_id] ) ) {
            return false;
        }
        if( filter_var( $cert_instance['cert_never_expires'], FILTER_VALIDATE_BOOLEAN ) === true ) {
            return false;
        }
        if( !isset( $cert_instance['calculated_days_until_expire'] ) ) {
            return false;
        }
        $calculated_days_until_expire = $cert_instance['calculated_days_until_expire'];
        if( !in_array( $calculated_days_until_expire, $warning_arr[$cert_id] ) ) {
            return false;
        }
        return true;
    }

    function getExpiringUserCerts( $warning_arr ) {
        $notified_today_arr = getAlreadyNotifiedTodayUserCertsArr();
        $return_arr = array();
        $json_url = request_json_api('/JSON/JSON_all_users_certs.php');
        foreach( getJson( $json_url ) as $userkey => $uservalue ) {
            foreach ($uservalue['certs'] as $value) {
                $largest_key = max(array_keys($value)); // Always look at the newest certification
                $cert_instance = $value[$largest_key];
                if( needNotificationOnThisCertToday( $cert_instance, $warning_arr ) ) {
                    $temp_arr = array();
                    $temp_arr['cert_name'] = $cert_instance['cert_name'];
                    $temp_arr['cert_id'] = $cert_instance['cert_id'];
                    $temp_arr['user_cert_id'] = $cert_instance['user_cert_id'];
                    $temp_arr['already_notified_today'] = in_array( $temp_arr['user_cert_id'], $notified_today_arr );
                    $temp_arr['cert_description'] = $cert_instance['cert_description'];
                    $temp_arr['user_cert_date_granted_ymd'] = $cert_instance['user_cert_date_granted_ymd'];
                    $temp_arr['calculated_expire_ymd'] = $cert_instance['calculated_expire_ymd'];
                    $temp_arr['calculated_days_until_expire'] = $cert_instance['calculated_days_until_expire'];
                    $temp_arr['cert_notes'] = $cert_instance['cert_notes'];
                    $return_arr[$uservalue['user_samaccountname']][] = $temp_arr;
                    unset($temp_arr);
                }
            }
        }
        return $return_arr;
    }

    function getUserLdapArr() {
        $return_arr = array();
        $json_url = request_ldap_api('/JSON_list_jfab_users.php');
        if (getJson( $json_url )) {
            foreach( getJson( $json_url )['items'] as $value ) {
                if( isset( $value['samaccountname'] ) && strlen( trim($value['samaccountname'] ) ) > 0 ) {
                    $temp_arr = array();
                    $temp_arr['firstname'] = trim( $value['givenname'] );
                    $temp_arr['lastname'] = trim( $value['sn'] );
                    $temp_arr['email'] = trim( $value['mail'] );
                    $temp_arr['manager_samaccountname'] = trim( $value['manager_samaccountname'] );
                    $samaccountname = trim( $value['samaccountname'] );
                    $return_arr[$samaccountname] = $temp_arr;
                    unset( $temp_arr );
                }
            }
        }
        return $return_arr;
    }

    function getEmails() {
        $user_ldap_arr = getUserLdapArr();
        $return_arr = array();
        foreach( getExpiringUserCerts( getWarningArr() ) as $samkey => $certs ) {
            if( isset( $user_ldap_arr[$samkey] ) ) {
                $temp_arr = array();
                $temp_arr['firstname'] = $user_ldap_arr[$samkey]['firstname'];
                $temp_arr['lastname'] = $user_ldap_arr[$samkey]['lastname'];
                $temp_arr['to'] = array();
                $temp_arr['to'][] = $user_ldap_arr[$samkey]['email'];
                $temp_arr['cc'] = array();
                if( isset( $user_ldap_arr[$samkey]['manager_samaccountname'] )
                    && strlen( $user_ldap_arr[$samkey]['manager_samaccountname'] ) > 0 ) {
                    $temp_arr['cc'][] = $user_ldap_arr[$user_ldap_arr[$samkey]['manager_samaccountname']]['email'];
                }
                $temp_arr['bcc'] = array();
                $temp_arr['bcc'][] = 'Michael.Wright@jfab.aosmd.com';
                $temp_arr['notification_needs_to_be_done'] = false;
                $temp_arr['notes'] = array();
                foreach( $certs as $cert_instance ) {
                    if( $cert_instance['calculated_days_until_expire'] < 0 ) {
                        $temp_arr['cc'][] = 'Charley.Stanton@jfab.aosmd.com';
                        $temp_arr['bcc'][] = 'jason.cubic@jfab.aosmd.com';
                    }
                    if( !$cert_instance['already_notified_today'] ) {
                        $temp_arr['notification_needs_to_be_done'] = true;
                    }
                    if( isset( $cert_instance['cert_notes'] ) && strlen( $cert_instance['cert_notes'] ) > 0 ) {
                        $temp_arr['notes'][] = $cert_instance['cert_name'] . ' - ' . $cert_instance['cert_notes'];
                    }
                }
                $temp_arr['certs'] = $certs;
                $return_arr[] = $temp_arr;
                unset( $temp_arr );
            }
        }
        return $return_arr;
    }

    header( 'Content-Type: application/json' );
    $emails = getEmails();
    echo( json_encode( $emails ) );
