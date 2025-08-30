<?php

// Notifications/todays_email_work_simple_report.php


// "Now remember, this is only a temporary fix - unless it works." - Red Green

//error reporting
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", "on");

date_default_timezone_set('America/Los_Angeles');





function logger( $message ) {
    $log_filename = '../logs/todays_email_info_' . date('Y_m') . '.txt';
    file_put_contents( $log_filename, $message . PHP_EOL, FILE_APPEND );
}

function getJson( $json_url ) {
    $json = array();
    try {
        if( $raw_file_data = file_get_contents( $json_url ) ) {
            $json = json_decode( $raw_file_data, true );
        } else {
            logger( 'Failed to get the JSON data from url: ' . $json_url );
            return false;
        }
    } catch ( Exception $e ) {
        logger( 'Exception getting getJson JSON information: ' . $e->getMessage() );
        return false;
    }
    if( isset( $json['errors'] ) ) {
        foreach( $json['errors'] as $error_value ) {
            logger( $error_value );
        }
    }
    return $json;
}


$json_url = $mybaseurl.'/JSON/JSON_todays_email_work.php';
$arr = getJson( $json_url );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>TCS Todays Email Work</title>
    <!--[if IE]>
        <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
</head>

<body>
    <h1>TCS - Todays Email Work</h1>
    <p>This report shows the state of todays TCS email work. This report does not send email, it only reports the state of the TCS email worker.  This report can not be run for any time other time period than the current day.</p>
    <p>As of 2014-09-29 e-mail's are sent out once per day at 5am.  The e-mail notification script *can* be run as many times a day as wanted, because it only sends notifications out when needed (see the "Notification needs to be done" value).</p>
    <p>&nbsp;</p>
    <hr>
    <?php
    foreach( $arr as $email ) {
        echo( '<ul>' );
        echo( '<li>' . $email['firstname'] . ' ' . $email['lastname'] );
        echo( '<ul>' );
        echo( '<li>to: ' . implode( ', ', $email['to'] ) . '</li> ' );
        echo( '<li>cc: ' . implode( ', ', $email['cc'] ) . '</li> ' );
        echo( '<li>bcc: ' . implode( ', ', $email['bcc'] ) . '</li> ' );
        if( $email['notification_needs_to_be_done'] ) {
            echo( '<li>Notification needs to be done: True</li> ' );
        } else {
            echo( '<li>Notification needs to be done: False</li> ' );
        }
        if( count( $email['notes'] ) > 0 ) {
            echo( '<li>Notes:' );
            echo( '<ul>' );
            foreach( $email['notes'] as $note ) {
                echo( '<li> ' . $note . '</li>' );
            }
            echo( '</ul>' );
            echo( '</li>' );
        }
        echo( '</ul>' );
        echo( '</li>' );
        echo( '</ul>' );
        echo( '<table border="1" cellpadding="10" style="margin-left:4em;">' );
        echo( '<tr>' );
        echo( '<th>Cert Name</th>' );
        echo( '<th>Description</th>' );
        echo( '<th>Expiration</th>' );
        echo( '<th>Already Sent Email Today</th>' );
        echo( '</tr>' );
        foreach( $email['certs'] as $cert_instance ) {
            echo( '<tr>' );
            echo( '<td>' . $cert_instance['cert_name'] . '</td>' );
            echo( '<td>' . $cert_instance['cert_description'] . '</td>' );
            echo( '<td>' . $cert_instance['calculated_expire_ymd'] . ' - ( ' . $cert_instance['calculated_days_until_expire'] . ' )</td>' );
            if( $cert_instance['already_notified_today'] ) {
                echo( '<td>True</td>' );
            } else {
                echo( '<td>False</td>' );
            }
            echo( '</tr>' );
        }
        echo( '</table>' );
        echo( '<p>&nbsp;</p>' );
        echo( '<hr>' );
    }
    ?>
</body>
</html>



