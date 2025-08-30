#!C:\Program Files (x86)\PHP\php.exe
<?php

	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");

	date_default_timezone_set('America/Los_Angeles');

	if(!defined('STDIN') ){
		exit(' - Sorry, this script is designed to only be run from the CLI (Command Line Interface).');
	}
	// TODO: check supervisors to make sure that they are not terminated


	// ldap_email_check/index.php
	$mytitle = 'LDAP Problems Found - '.date('Y-m-d H:i');
	$htmlcontent = "<p>A daily check is done in LDAP for users with no deparment number set, department name mismatches, users without the telephone field properly set, and useraccountcontrol problems.  The below issues were found as problems in LDAP.  Please review and correct the issues.</p>";
	$htmlcontent .= '<ul>';
	$json = json_decode(file_get_contents(request_ldap_api("/JSON_find_jfab_ldap_problems.php")), true);
	foreach($json['problems'] as $value){
		$htmlcontent .= '<li>';
		$htmlcontent .= $value;
		$htmlcontent .= '</li>';
	}
	$htmlcontent .= '</ul>';
	$htmlcontent .= "<p>For troubleshooting you can consult the below resources:</p>";
	$htmlcontent .= '<ul>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'Users and Computers';
	$htmlcontent .= '</li>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'ADSI Edit';
	$htmlcontent .= '</li>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'User phone numbers report: ';
	$htmlcontent .= '<a href="'.request_ldap_api('/find_jfab_phonenumber.php').'" target ="_blank" title="Styling Links" style="color: blue; text-decoration: none;">'.request_ldap_api('/find_jfab_phonenumber.php').'</a>';
	$htmlcontent .= '</li>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'JSON LDAP problem report checking script: ';
	$htmlcontent .= '<a href="'.request_ldap_api('/JSON_find_jfab_ldap_problems.php').'" target ="_blank" title="Styling Links" style="color: blue; text-decoration: none;">'.request_ldap_api('/JSON_find_jfab_ldap_problems.php').'</a>';
	$htmlcontent .= '</li>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'JSON LDAP user info script: ';
	$htmlcontent .= '<a href="'.request_ldap_api('/JSON_list_jfab_users.php').'" target ="_blank" title="Styling Links" style="color: blue; text-decoration: none;">'.request_ldap_api('/JSON_list_jfab_users.php').'</a>';
	$htmlcontent .= '</li>';
	$htmlcontent .= '<li>';
	$htmlcontent .= 'This script is run as a Scheduled Task from jfabweb3: C:\inetpub\wwwroot\Training_Cert_System\ldap_email_check\daily_ldap_check.php';
	$htmlcontent .= '</li>';
	$htmlcontent .= '</ul>';


	$htmlcontent .= '<p>The below LDAP users have been excluded from deparment checks.  This is set inside the "JSON_find_jfab_ldap_problems.php" script.</p>';
	$htmlcontent .= '<ul>';
	foreach($json['excluded_users'] as $value){
		$htmlcontent .= '<li>';
		$htmlcontent .= $value;
		$htmlcontent .= '</li>';
	}
	$htmlcontent .= '</ul>';






	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	$html .= '<html xmlns="http://www.w3.org/1999/xhtml">';
	$html .= '<head>';
	$html .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>';
	$html .= '<title>'.$mytitle.'</title>';
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
	/*
	 * End of wrapper table
	 */
	$html .= '</body>';
	$html .= '</html>';

	//echo($html);

	$recipients = array();
	$recipients[] = "Jason.Cubic@jfab.aosmd.com";
	$recipients[] = "Reid.Raymond@jfab.aosmd.com";


	if(count($json['problems']) > 0){

		require_once '../../Swift-4.1.7/lib/swift_required.php';
		$transport = Swift_SmtpTransport::newInstance('172.26.15.109', 25);
		$mailer = Swift_Mailer::newInstance($transport);
		$message = Swift_Message::newInstance($mytitle)
			->setFrom(array('donotreply@jfab.aosmd.com' => 'LDAP checking system'))
			->setTo($recipients)
			->setContentType('text/html')
			->setBody($html);
		$result = $mailer->send($message);
	}

?>

