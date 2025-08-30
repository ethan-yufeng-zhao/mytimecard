<?php

// Notifications/notify_email_and_log.php

// "Now remember, this is only a temporary fix - unless it works." - Red Green

//error reporting
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", "on");

date_default_timezone_set('America/Los_Angeles');

if(!defined('STDIN') ){
    exit(' - Sorry, this script is designed to only be run from the CLI (Command Line Interface).');
}

function logger( $message ) {
    $log_filename = '../logs/notify_email_and_log_' . date('Y_m') . '.txt';
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
        logger( 'Failed to get the JSON data from url: ' . $json_url );
        return false;
    }
    if( isset( $json['errors'] ) ) {
        foreach( $json['errors'] as $error_value ) {
            logger( $error_value );
        }
    }
    return $json;
}

function getEmailsToSend() {
    $return_arr = array();
    $json_url = $mybaseurl.'/JSON/JSON_todays_email_work.php';
    foreach( getJson( $json_url ) as $email_key => $email_value ) {
        if( $email_value['notification_needs_to_be_done'] ) {
            $return_arr[] = $email_value;
        }
    }
    // header('Content-Type: application/json');
    // echo(json_encode($return_arr));
    // exit();
    return $return_arr;
}


function updateDbNotificationSent( $cert_id, $user_cert_id, $notification_sent_date ) {
    $query_arr = array();
    $query_arr['cert_id'] = $cert_id;
    $query_arr['user_cert_id'] = $user_cert_id;
    $query_arr['notification_sent_date'] = $notification_sent_date;
    $json_url = $mybaseurl.'/JSON/JSON_ACTION_update_notification.php?' . http_build_query( $query_arr );
    if( getJson( $json_url )['success'] == false ) {
        logger( date('l F Y-m-d H:i:s').' Error updating notification in database: '.$json['error'] );
    }
}

require_once '/Mustache/Autoloader.php';
Mustache_Autoloader::register();
$mustache_template = new Mustache_Engine( array( 'loader' => new Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) . '/views' ), ) );

require_once '../../Swift-4.1.7/lib/swift_required.php';
$transport = Swift_SmtpTransport::newInstance('172.26.15.109', 25);
$mailer = Swift_Mailer::newInstance($transport);


$table_style = 'border: 1px solid #DFDFDF; background-color: #F9F9F9; width: 100%; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; font-family: Arial,&quot;Bitstream Vera Sans&quot;,Helvetica,Verdana,sans-serif;';

$th_style = 'text-shadow: rgba(255, 255, 255, 0.796875) 0px 1px 0px; font-family: Georgia,"Times New Roman","Bitstream Charter",Times,serif; font-weight: normal; padding: 7px 7px 8px; text-align: left; line-height: 1.3em; font-size: 14px; border-top-color: white; border-bottom: 2px solid #666666; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC;';

$td_style = 'font-size: 12px; padding: 4px 7px 2px; vertical-align: top; border-top-color: white; border-bottom: 1px solid #CCCCCC; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC;';



foreach( getEmailsToSend() as $email ) {
    $view_arr = $email;
    $mytitle = 'Certifications expiring soon';
    $view_arr['defaultViewTitle'] = $mytitle;
    $view_arr['userName'] = $view_arr['firstname'] . ' ' . $view_arr['lastname'];
    $view_arr['table_style'] = $table_style;
    $view_arr['th_style'] = $th_style;
    $view_arr['td_style'] = $td_style;
    if( count( $email['notes'] ) > 0 ) {
        $view_arr['has_notes'] = true;
    }
    $html = $mustache_template->render( 'email_header', $view_arr );
    $html .= $mustache_template->render( 'default_email_body_view', $view_arr );
    $html .= $mustache_template->render( 'email_footer', array() );
    $message = Swift_Message::newInstance( $mytitle )
        ->setFrom( array( 'donotreply@jfab.aosmd.com' => 'Training and Certification System' ) )
        ->setTo( $view_arr['to'] )
        ->setContentType( 'text/html' )
        ->setBody( $html )
        ->setCc( $view_arr['cc'] )
        ->setBcc( $view_arr['bcc'] );
    $current_time = time();
    $result = $mailer->send( $message );
    if( $result ) {
        foreach( $email['certs'] as $notif_value ) {
            updateDbNotificationSent( $notif_value['cert_id'], $notif_value['user_cert_id'], $current_time );
        }
        logger( date( 'l F Y-m-d H:i:s' ) . ' e-mail succesfully send to: ' . $view_arr['userName'] );
    } else {
        logger( date( 'l F Y-m-d H:i:s' ) . ' ERROR: Failed to send e-mail to: ' . $view_arr['userName'] );
    }
    unset( $view_arr );
}

