#!C:\Program Files (x86)\PHP\php.exe
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




// get warning information this has the number of days ahead of time to warn someone
$warning_arr = array();
$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php')), true);
foreach ($json['items'] as $certkey => $certvalue) {
    foreach ($certvalue['warning'] as $value) {
        $warning_arr[$certkey][] = $value['warning_number_of_days'];
    }
}
unset($json);

// header('Content-Type: application/json');
// echo(json_encode($warning_arr));
// exit();

$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_todays_notifications.php'), false, getContextCookies()), true);
$notif_arr = array();
foreach ($json['items'] as $user_cert_id => $value) {
    $notif_arr[] = $user_cert_id;
}
unset($json);

// in_array(needle, haystack)

$expiring_user_certs = array();
$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_certs.php'), false, getContextCookies()), true);
foreach ($json['items'] as $userkey => $uservalue) {
    foreach ($uservalue['certs'] as $value) {
        $largest_key = max(array_keys($value)); // This makes sure that we are always looking at the newest certification
        if(isset($value[$largest_key]['calculated_days_until_expire']) && $value[$largest_key]['cert_never_expires'] == 0 && in_array($value[$largest_key]['calculated_days_until_expire'], $warning_arr[$value[$largest_key]['cert_id']])) {
            $dummy = array();
            $dummy['cert_name'] = $value[$largest_key]['cert_name'];
            $dummy['cert_id'] = $value[$largest_key]['cert_id'];
            $dummy['user_cert_id'] = $value[$largest_key]['user_cert_id'];
            $dummy['cert_description'] = $value[$largest_key]['cert_description'];
            $dummy['user_cert_date_granted_ymd'] = $value[$largest_key]['user_cert_date_granted_ymd'];
            $dummy['calculated_expire_ymd'] = $value[$largest_key]['calculated_expire_ymd'];
            $dummy['calculated_days_until_expire'] = $value[$largest_key]['calculated_days_until_expire'];
            $dummy['cert_notes'] = $value[$largest_key]['cert_notes'];



            $expiring_user_certs[$uservalue['user_samaccountname']][] = $dummy;
            unset($dummy);
        }
    }
}
unset($json);

//Get this information from LDAP so that we are not sending emails to terminated employee's
$json = json_decode(file_get_contents(request_ldap_api('/JSON_list_jfab_users.php')), true);
$user_ldap_arr = array();

foreach ($json['items'] as $value) { // Get all user info and order it by samaccountname
    if(isset($value['samaccountname']) && strlen(trim($value['samaccountname'])) > 0){
        $user_ldap_arr[trim($value['samaccountname'])] = array('firstname' => trim($value['givenname']), 'lastname' => trim($value['sn']), 'email' => trim($value['mail']), 'manager_samaccountname' => trim($value['manager_samaccountname']));
    }
}
unset($json);

// header('Content-Type: application/json');
// echo(json_encode($expiring_user_certs));
// exit();



$th_style = 'text-shadow: rgba(255, 255, 255, 0.796875) 0px 1px 0px; font-family: Georgia,"Times New Roman","Bitstream Charter",Times,serif; font-weight: normal; padding: 7px 7px 8px; text-align: left; line-height: 1.3em; font-size: 14px; border-top-color: white; border-bottom: 2px solid #666666; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC;';

$td_style = 'font-size: 12px; padding: 4px 7px 2px; vertical-align: top; border-top-color: white; border-bottom: 1px solid #CCCCCC; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC;';



foreach ($expiring_user_certs as $samkey => $certs) {
    $has_cert_with_negative_days = false;
    $notification_certs_arr = array();
    $cert_notification_count = 0;
    if (isset($user_ldap_arr[$samkey])) {

        foreach ($certs as $value) {
            if (!in_array($value['user_cert_id'], $notif_arr)) {
                if( $value['calculated_expire_ymd'] < 0 ) {
                    $has_cert_with_negative_days = true;
                }
            }
        }

        $mytitle = 'Certifications expiring soon';
        // <b><font face="Arial" color="red">        <h3>            ATTENTION!</h3>    </font></b><p></p><font face="Arial" color="#0000ff"><b>If you are receiving this e-mail you have training certifications and/or ERT training that is expiring in the next 31days or has already expired.&nbsp;</b></font><p><font face="Arial" color="#0000ff"><b> Please open the         attached document to view the details.</b></font></p><font face="Arial" color="blue"><b>For a listing of all your certifications please go         to the</b> <a href="http://hilweb2.oregon.idt.com/ocr"><b>Online     Certifications Report </font></A></B><p><FONT face="Arial" size="2">For a listing of classes that can be recertified     online, go to the <b><a href="http://hilweb5/?page_id=388">            Training Website</a></b></FONT><p><font face="Arial"><FONT size="2">Online learning courses are available at:</FONT><b>             H:\CBRT</b></font><p><FONT face="Arial"><font size="2">Click here for </font><b><a href="http://hilweb.oregon.idt.com/learninglab/login.asp">            Learning Lab</a></b></FONT><P>    <font face="Arial"><font size="2">ERT    training schedules are available at:</font><b>&nbsp; H:\CBRT\ERT Competency    Training</b></font></P><p><FONT face="Arial" size="2"><b>For questions or concerns please contact Michael Wright or             Tim Feeder.</b></FONT></p>

        $htmlcontent = '<h4 style="color:red;">Attention ' . $user_ldap_arr[$samkey]['firstname'] . ' ' . $user_ldap_arr[$samkey]['lastname'] . ',</h4>';
        $htmlcontent .= '<p>You are receiving this e-mail because the below certifications are expired or expiring soon.</p>';
        $htmlcontent .= '<p>For a listing of all your certifications log onto the Online <a href="'.$mybaseurl.'/index.php">Training and Certification System</a>.</p>';

        $htmlcontent .= '<p>For questions or concerns please contact <a href="mailto:Michael.Wright@jfab.aosmd.com">Michael Wright</a> or <a href="mailto:Mike.Weiby@jfab.aosmd.com">Mike Weiby</a>.</p>';
        //$htmlcontent .= "<p>&nbsp;</p>\n";
        $htmlcontent .= "<table style='border: 1px solid #DFDFDF; background-color: #F9F9F9; width: 100%; -moz-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; font-family: Arial,\"Bitstream Vera Sans\",Helvetica,Verdana,sans-serif;'>";
        $htmlcontent .= "<thead>";
        $htmlcontent .= "<tr>";

        $htmlcontent .= "<th style='".$th_style."'>";
        $htmlcontent .= "Cert Name";
        $htmlcontent .= "</th>";

        $htmlcontent .= "<th style='".$th_style."'>";
        $htmlcontent .= "Description";
        $htmlcontent .= "</th>";

        // $htmlcontent .= "<th>";
        // $htmlcontent .= "Date Granted";
        // $htmlcontent .= "</th>";

        $htmlcontent .= "<th style='".$th_style."'>";
        // $htmlcontent .= "Expiration";
        $htmlcontent .= "Due Date";
        $htmlcontent .= "</th>";

        // $htmlcontent .= "<th>";
        // $htmlcontent .= "Notes";
        // $htmlcontent .= "</th>";

        $htmlcontent .= "</tr>\n";
        $htmlcontent .= "</thead>";

        $htmlcontent .= "<tbody>";


        $htmlnotes = '<h4 style="color:red;">Notes:</h4>';
        $htmlnotes .= '<ul>';
        $htmlnotecount = 0;
        foreach ($certs as $value) {
            if (!in_array($value['user_cert_id'], $notif_arr)) {
                $cert_notification_count++;
                $notif_arr[] = $value['user_cert_id'];
                $notification_certs_arr[] = array('cert_id' => $value['cert_id'], 'user_cert_id' => $value['user_cert_id']);

                if (strlen(trim($value['cert_notes'])) > 0) {
                    $htmlnotecount++;
                    $htmlnotes .= '<li>';
                    $htmlnotes .= $value['cert_name'] . ' - ' . $value['cert_notes'];
                    $htmlnotes .= '</li>';
                }



                $htmlcontent .= '<tr>';

                $htmlcontent .= "\n<td style='".$td_style."'>";
                $htmlcontent .= $value['cert_name'];
                $htmlcontent .= '</td>';

                $htmlcontent .= "\n<td style='".$td_style."'>";
                $htmlcontent .= $value['cert_description'];
                $htmlcontent .= '</td>';

                // $htmlcontent .= '<td>';
                // $htmlcontent .= $value['user_cert_date_granted_ymd'];
                // $htmlcontent .= '</td>';

                $htmlcontent .= "\n<td style='".$td_style."'>";
                $htmlcontent .= $value['calculated_expire_ymd'];
                $htmlcontent .= ' - (';
                $htmlcontent .= $value['calculated_days_until_expire'];
                $htmlcontent .= ' days)';
                $htmlcontent .= '</td>';


                // $htmlcontent .= '<td>';
                // $htmlcontent .= $value['cert_notes'];
                // $htmlcontent .= '</td>';

                $htmlcontent .= '</tr>';
            }


        }
        $htmlnotes .= '</ul>';
        $htmlcontent .= "</tbody>";

        $htmlcontent .= "</table>";

        if ($cert_notification_count > 0) { // only send an email if their are cert grants that have not been notified about today

            $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
            $html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
            $html .= '<head>';
            $html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
            $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
            $html .= '<title>' . $mytitle . '</title>';
            $html .= file_get_contents('boilerplate_email_styles.html');
            $html .= '</head>';
            $html .= '<body>';
            /*
            * Wrapper/Container Table: Use a wrapper table to control the width and the background color consistently of your email. Use this approach instead of setting attributes on the body tag.
            */
            $html .= '<table cellpadding="0" cellspacing="0" border="0" id="backgroundTable">';
            $html .= '<tr>';
            $html .= '<td>';
            $html .= $htmlcontent;
            /*
            * Tables are the most common way to format your email consistently. Set your table widths inside cells and in most cases reset cellpadding, cellspacing, and border to zero. Use nested tables as a way to space effectively in your message.
            */
            // $html .= '<table cellpadding="0" cellspacing="0" border="0" align="center">';
            // $html .= '<tr>';
            // $html .= '<td width="200" valign="top">1</td>';
            // $html .= '<td width="200" valign="top">2</td>';
            // $html .= '<td width="200" valign="top">3</td>';
            // $html .= '</tr>';
            // $html .= '</table>';
            /*
            * End example table
            */

            /*
            * Yahoo Link color fix updated: Simply bring your link styling inline.
            */
            //$html .= '<a href="http://htmlemailboilerplate.com" target ="_blank" title="Styling Links" style="color: orange; text-decoration: none;">Coloring Links appropriately</a>';

            /*
            * Gmail/Hotmail image display fix: Gmail and Hotmail unwantedly adds in an extra space below images when using non IE browsers.  This can be especially painful when you putting images on top of each other or putting back together an image you spliced for formatting reasons.  Either way, you can add the 'image_fix' class to remove that space below the image.  Make sure to set alignment (don't use float) on your images if you are placing them inline with text.
            */
            //$html .= '<img class="image_fix" src="full path to image" alt="Your alt text" title="Your title text" width="x" height="x" />';

            /*
            * Step 2: Working with telephone numbers (including sms prompts).  Use the "mobile_link" class with a span tag to control what number links and what doesn't in mobile clients.
            */
            //$html .= '<span class="mobile_link">123-456-7890</span>';


            $html .= '</td>';
            $html .= '</tr>';
            $html .= '</table>';

            $html .= '<p>&nbsp;</p>';
            if($htmlnotecount > 0) {
                $html .= $htmlnotes;
            }

            /*
            * End of wrapper table
            */
            $html .= '</body>';
            $html .= '</html>';

            // echo($html);
            // exit();


            $recipients = array();
            $recipients[] = $user_ldap_arr[$samkey]['email'];


            $carboncopy = array();  // This is for setting a person's manager as a cc on the email
            if (isset($user_ldap_arr[$samkey]['manager_samaccountname']) && strlen($user_ldap_arr[$samkey]['manager_samaccountname']) > 0) {
                $carboncopy[] = $user_ldap_arr[$user_ldap_arr[$samkey]['manager_samaccountname']]['email'];
            }

            $blindcopy = array();

            if( $has_cert_with_negative_days ) {
                $carboncopy[] = 'Charley.Stanton@jfab.aosmd.com';
                $blindcopy[] = 'jason.cubic@jfab.aosmd.com';
            }
            $blindcopy[] = 'Michael.Wright@jfab.aosmd.com';
            // $blindcopy[] = 'Dave.Nickles@jfab.aosmd.com';


            require_once '../../Swift-4.1.7/lib/swift_required.php';
            $transport = Swift_SmtpTransport::newInstance('172.26.15.109', 25);
            $mailer = Swift_Mailer::newInstance($transport);
            $message = Swift_Message::newInstance($mytitle)
                ->setFrom(array('donotreply@jfab.aosmd.com' => 'Training and Certification System'))
                ->setTo($recipients)
                ->setContentType('text/html')
                ->setBody($html)
                ->setCc($carboncopy)
                ->setBcc($blindcopy);
            $current_time = time();
            $result = $mailer->send($message);
            $log_name = "../logs/email_notification_script_log_".date('m_Y').".txt";
            if ($result) {
                foreach($notification_certs_arr as $notif_value){
                    $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_update_notification.php?cert_id='.$notif_value['cert_id'].'&user_cert_id='.$notif_value['user_cert_id'].'&notification_sent_date='.$current_time), false, getContextCookies()), true);
                    if($json['success'] == false){
                        file_put_contents($log_name, date('l F Y-m-d H:i:s').' Error updating notification in database: '.$json['error']."\n", FILE_APPEND);
                    }
                }
                file_put_contents($log_name, date('l F Y-m-d H:i:s').' email succesfully send to: '.$user_ldap_arr[$samkey]['email']."\n", FILE_APPEND);
                unset($notification_certs_arr);
            } else {
                $error_log_text = date('l F Y-m-d H:i:s').' ERROR: Failed to send email to: '.$user_ldap_arr[$samkey]['email'];
                file_put_contents($log_name, $error_log_text."\n", FILE_APPEND);
            }
            // exit();
        }
    }
}
?>
