<?php
	// user_cert_history.php
	include_once('header.php');  // has everything up to the container div in the body
?>


<script type="text/javascript">
    $(document).ready(function() {
        $(".table_col_4_with_labels").tablesorter({
            theme : "bootstrap",
            widthFixed: true,
            headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
            widgets : [ "uitheme", "filter" ],
            widgetOptions : {
                filter_reset : ".reset"
            },
            headers: {
                3: {
                    sorter:'ignore_labels'
                }
            }
        });
    });

    function certValidateDelete(){
        if(confirm("Delete this Certificate from this user?")) {
            return true;
        } else {
            return false;
        }
    }

    function deleteCert(user_cert_id, cert_name, cert_description, user_cert_date_granted, user_cert_date_granted_ymd, expiration_string) {
        if(confirm("Cert name: "+cert_name+"\n\nCert Description: "+cert_description+"\n\nDate granted: "+user_cert_date_granted_ymd+"\n\nExpiration: "+expiration_string+"\n\n\nRemove this certification from this user?")) {
            $("#delete_user_cert_id").val(user_cert_id);
            $("#delete_user_cert_date").val(user_cert_date_granted);
            $('#delete_user_cert_form').submit();
        }
    }
</script>


<?php
	if(isset($_GET['uid']) && strlen($_GET['uid']) > 0) {
		$requested_user_id = $_GET['uid'];
	} else {
		$requested_user_id = $user['user_id'];
	}

	if($requested_user_id == $user['user_id']) { // A user can always view themselves
		$authorized = true;
		$requested_user = $user;
	} else {
		$authorized = false;
		$requested_user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$requested_user_id) , false, getContextCookies()), true); // get the person in questions info
	}

	if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
		$authorized = true;
	}

	$running_manager_id = $requested_user['user_supervisor_id'];
	if(!$authorized && $user['user_id'] == $running_manager_id){ // immediate supervisor
		$authorized = true;
	}

	while(!$authorized && $running_manager_id != 0){ // Now check for supervisors of supervisor (and keep doing so until there is no supervisor)
		$running_supervisor = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$running_manager_id) , false, getContextCookies()), true); // get the supervisors supervisor
		$running_manager_id = $running_supervisor['user_supervisor_id'];
		if($running_manager_id == $user['user_id']) { // the requested persons supervisor is the current user
			$authorized = true;
		}
		unset($running_supervisor);
	}

	$requested_user_supervisor = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$requested_user['user_supervisor_id']) , false, getContextCookies()), true); // get supervisor information
	// echo('<div class="span-24 last" style="margin-top:1em;">');
	if($authorized) {
        // echo('<input name="user_cert_last_user" id="user_cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'">');
        // echo('<input name="user_cert_id" id="user_cert_id" type="hidden" value="0">');
        if (isset($_POST['delete_user_cert_id']) && is_numeric($_POST['delete_user_cert_id']) && $_POST['delete_user_cert_id'] > 0
            && isset($_POST['user_cert_last_user']) && strlen($_POST['user_cert_last_user']) > 0
            && isset($_POST['delete_user_account']) && strlen($_POST['delete_user_account']) > 0
            && isset($_POST['delete_user_cert_date']) && strlen($_POST['delete_user_cert_date']) > 0) { // Do a delete from the database
            $post_remove_arr = array();
            // $post_remove_arr['delete_user_cert_id'] = $_POST['user_cert_id'];
            $post_remove_arr['user_cert_id'] = $_POST['delete_user_cert_id'];
            $post_remove_arr['user_cert_last_user'] = $_POST['user_cert_last_user'];
            $post_remove_arr['cert_history_user'] = $_POST['delete_user_account'];
            $post_remove_arr['user_cert_date'] = $_POST['delete_user_cert_date'];

            $json_remove_user_cert = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_user_cert.php?'.http_build_query($post_remove_arr)) , false, getContextCookies()), true);
            if (isset($json_remove_user_cert['success']) && $json_remove_user_cert['success'] == true) {
                echo('<div class="alert alert-dismissable alert-success">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<strong>Success:</strong> '.$json_remove_user_cert['message']);
                echo('</div>');
                // echo('<div style="padding: .7em;"><div class="ui-state-highlight ui-corner-all" style="padding: .7em;"><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><strong>Success:</strong> '.$json_remove_user_cert['message'].'</div></div>');
            } else {
                echo('<div class="alert alert-dismissable alert-danger">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<p><strong>Error:</strong> '.$json_remove_user_cert['error'].'</p>');
                if(isset($json_remove_user_cert['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_remove_user_cert['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
                // echo('<div style="padding: .7em;"><div class="ui-state-error ui-corner-all" style="padding: .7em;"><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><p style="margin:0; padding:0;"><strong>Error:</strong> '.$json_remove_user_cert['error'].'<br>'.$json_remove_user_cert['error'].'</p></div></div>');
            }
            unset($json_remove_user_cert);
        }

		if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id'])) {
			// JSON/JSON_user_cert_history.php?user_id=1&cert_id=1
			$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_cert_history.php?user_id='.$requested_user['user_id'].'&cert_id='.$_GET['cert_id']) , false, getContextCookies()), true);
			if($json['success']) {
				echo('<table>');

				echo('<tr>');
				echo('<td>');
				echo('<h2 style="margin-top:0px; margin-bottom:0px;">');
				echo('Certification History for one user and one certification');
				echo('</h2>');
				echo('</td>');
				echo('</tr>');

				echo('<tr>');
				echo('<td>');
				echo('<p style="margin-top:0px; margin-bottom:0px;">');
				echo('<strong>User:</strong> ');
                $cert_history_user = '';
                if ($GLOBALS['DB_TYPE'] == 'pgsql') {
                    $cert_history_user = $json['items']['user_samaccountname'];
                } else {
                    $cert_history_user = $json['items']['user_firstname'].' '.$json['items']['user_lastname'];
                }
                echo($cert_history_user);
				echo('</p>');
				echo('</td>');
				echo('<tr><td>');
				echo(date('Y-m-d H:i:s'));
				echo('</td>');
				echo('</tr></table>');


				echo("<table class='table_col_4_with_labels'>");
				echo("<thead>");
				echo("<tr>");

                echo("<th>");
                echo("No.");
                echo("</th>");

				echo("<th>");
				echo("Cert Name");
				echo("</th>");

				echo("<th>");
				echo("Description");
				echo("</th>");

				echo("<th>");
				echo("Date Granted");
				echo("</th>");

				echo("<th>");
				echo("Granted By");
				echo("</th>");

				echo("<th>");
				echo("Expiration");
				echo("</th>");

				echo("</tr>\n");
				echo("</thead>");

				echo("<tbody>");

                $count = 0;
				foreach ($json['items']['certs'] as $value) {
					$notification_certs_arr[] = array('cert_id' => $value['cert_id'], 'user_cert_id' => $value['user_cert_id']);
					echo('<tr>');

                    echo("\n<td>");
                    echo(++$count);
                    echo('</td>');

					echo("\n<td>");
					echo($value['cert_name']);
					echo('</td>');

					echo("\n<td>");
					echo($value['cert_description']);
					echo('</td>');

					echo('<td>');
//					echo(date('Y-m-d H:i:s', $value['user_cert_date_granted']));
                    echo($value['user_cert_date_granted_ymd']);
					echo('</td>');

					echo('<td>');
					echo($value['user_cert_last_user']);
					echo('</td>');

					echo("\n<td>");
					$expiration_string = '';
					if($value['cert_never_expires'] == 0) {
						echo($value['calculated_expire_ymd']);
						$expiration_string .= $value['calculated_expire_ymd'];

						if(intval($value['calculated_days_until_expire']) < -31) {
							echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
						} elseif(intval($value['calculated_days_until_expire']) < 0) {
                            $expiration_string .= ' - (';
							echo('<span style="display:none;">||||</span> <span class="label" style="background-color:#E17572;">Now Due</span>');
							$expiration_string .= "expired)";
						} elseif(intval($value['calculated_days_until_expire']) < 30) {
                            $expiration_string .= ' - (';
							echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$value['calculated_days_until_expire'].' days</span>');
							$expiration_string .= $value['calculated_days_until_expire'];
							$expiration_string .= ' days)';
						} else {
                            $expiration_string .= ' - (';
							echo('<span style="display:none;">||||</span> <span class="label label-success">'.$value['calculated_days_until_expire'].' days</span>'); // The 4 bars are for parsing in javascript.  Content comes before them.
							$expiration_string .= $value['calculated_days_until_expire'];
							$expiration_string .= ' days)';
						}
					} else {
						echo('does not expire');
						$expiration_string .= 'does not expire';
					}
					if($value['user_cert_exception'] == 1) {
						echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
					}

					if(isset($_GET['enable_edit']) && $_GET['enable_edit'] == 1) {
						echo('<span style="display:none;">||||</span>');

						echo(" <a href='javascript:void(0);' onclick='deleteCert(".$value['user_cert_id'].", \"".$value['cert_name']."\", \"".$value['cert_description']."\", \"".$value['user_cert_date_granted']."\", \"".$value['user_cert_date_granted_ymd']."\", \"".$expiration_string."\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Remove this certification from this user'>");
						echo('<span class="glyphicon glyphicon-remove"></span>');
						echo('</a>');
					}
					echo('</td>');

					echo('</tr>');
				}
				echo("</tbody>");
				echo("</table>\n");

                if(!isset($_GET['enable_edit']) || $_GET['enable_edit'] != 1) {
                    $removefields = $_GET;
                    $removefields['enable_edit'] = 1;
                    echo('<p><a href="'.$mybaseurl.'/user_cert_history.php?'.http_build_query($removefields).'" class="btn btn-danger btn-sm btn-group hidden-print">Enable remove mode</a></p>');
                } else {
                    $removefields = $_GET;
                    $removefields['enable_edit'] = 0;
                    echo('<p><a href="'.$mybaseurl.'/user_cert_history.php?'.http_build_query($removefields).'" class="btn btn-success btn-sm btn-group hidden-print">Disable remove mode</a></p>');
                }

				echo('<form name="delete_user_cert_form" id="delete_user_cert_form" action="'.$mybaseurl.'/user_cert_history.php?'.http_build_query($_GET).'" method="post">');
				echo('<input name="user_cert_last_user" id="user_cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');
				echo('<input name="delete_user_cert_id" id="delete_user_cert_id" type="hidden" value="0" />');
                echo('<input name="delete_user_account" id="delete_user_account" type="hidden" value="'.$cert_history_user.'" />');
                echo('<input name="delete_user_cert_date" id="delete_user_cert_date" type="hidden" value="0" />');
				echo('</form>');
			} else {
				echo('<h1>No certifications to display</h1>');
			}
		} else {
			echo('<div class="alert alert-danger">');
			echo('<p>cert_id must be set.</p>');
			echo('</div>');
		}
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
