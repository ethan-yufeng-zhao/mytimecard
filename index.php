<?php
	// index.php
	// "Now remember, this is only a temporary fix - unless it works." - Red Green
	include_once('header.php');  // has everything up to the container div in the body
?>

<script type="text/javascript">
	$(document).ready(function() {
		$("#user_cert_date_granted").datepicker({
			showAnim: "",
			minDate: new Date(2008, 1-1, 1),
			maxDate: '+0d'
		});
		$("#user_cert_date_granted").datepicker("setDate", (new Date()));

		$(".table_col_2_with_labels").tablesorter({
			theme : "bootstrap",
			widthFixed: true,
			headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
			widgets : [ "uitheme", "filter" ],
			widgetOptions : {
				filter_reset : ".reset"
			},
			headers: {
				2: {
					sorter:'ignore_labels'
				}
			}
		});
		// $( "#modal_cert_picker_data" ).draggable();
	});

	function validateAddCert() {
		 	var mycertname = $("#add_user_cert_cert_name").val();
		 	var mycertid = $("#add_user_cert_cert_id").val();
		 	var myusername = $("#add_user_cert_username").val();
		 	var mycertstartdate = $("#user_cert_date_granted").val();
		 	var user_cert_exception = $("#user_cert_exception").val();
		 	// alert(user_cert_exception);
			if(mycertid == 0) {
				alert("ERROR: Invalid certificate id");
				return false;
			}
			if(myusername.length < 1) {
				alert("ERROR: Invalid username");
				return false;
			}
			if(mycertstartdate.length < 1) {
				alert("ERROR: Invalid date length");
				return false;
			}
			var confirmtext = "Cert Name: "+mycertname+"\n\n";
			confirmtext += "User Name: "+myusername+"\n\n";
			confirmtext += "Cert Start Date: "+mycertstartdate+"\n\n";
			if ($('#user_cert_exception').is(':checked')) {
				confirmtext += "This is an Exception.\n\n";
			}
			confirmtext += "\nAdd this Certificate?";
			if (confirm(confirmtext)) {
				return true;
			} else {
				return false;
			}
	}

	function chooseCert(cert_id, cert_name) {
		$("#add_user_cert_cert_id").val(cert_id);
		$("#add_user_cert_cert_name").val(cert_name);
		$("#date_modal_cert").html(cert_name);
		$("#cert_name_confirm").html('<p>Cert name: '+cert_name+'</p>');
		$('#modal_cert_picker_data').modal('hide');
		$('#modal_date_picker_data').modal('show');
	}
</script>


<?php
	if(isset($_GET['uid']) && strlen($_GET['uid']) > 0) {
		$requested_user_id = $_GET['uid'];
	} else {
		$requested_user_id = $user['user_id'];
	}

	if($requested_user_id == $user['user_id']){ // A user can always view themselves
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
		if($running_manager_id == $user['user_id']) { // the the requested persons suprvisor is the current user
			$authorized = true;
		}
		unset($running_supervisor);
	}

	$requested_user_supervisor = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$requested_user['user_supervisor_id']) , false, getContextCookies()), true); // get supervisor information

	if($authorized){
		if (isset($_POST['add_user_cert']) && $_POST['add_user_cert'] == 1) { // this is an update, add a user cert
			// echo($mybaseurl.'/JSON/JSON_ACTION_add_user_cert.php?'.http_build_query($_POST)); // TODO: Remove this line when finished developing
			$json_add_user_cert = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user_cert.php?'.http_build_query($_POST)) , false, getContextCookies()), true);
			if (isset($json_add_user_cert['success']) && $json_add_user_cert['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<strong>Success:</strong> User Cert has been added.');
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> User Cert was unable to be added.</p>');
				if(isset($json_add_user_cert['error'])) {
					echo('<p style="margin-left:2em;">'.$json_add_user_cert['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
			unset($json_add_user_cert);
		}

        /**
         * Begining of content -- certs
         */
        echo("<div id='jfabtable'>\n");
		echo('<table><tr><td>');
		echo('User: <a href="mailto:'.$requested_user['user_email'].'">');
		echo($requested_user['user_firstname'].' '.$requested_user['user_lastname']);
		echo('</a>');
		if(!empty($requested_user['user_supervisor_id'])){
			echo('<tr><td>');
			echo('Supervisor: <a href="mailto:'.$requested_user_supervisor['user_email'].'">');
			echo($requested_user_supervisor['user_firstname'].' '.$requested_user_supervisor['user_lastname']);
			echo('</a>');
			echo('</td></tr>');
		}
		echo('<tr><td>');
		echo(date('Y-m-d H:i:s'));
		echo('</td>');
		echo('</tr></table><p>');

		if($user['user_is_admin']) { // || ($authorized && $user['user_id'] != $requested_user['user_id'])) {
			echo('<a data-toggle="modal" href="#modal_cert_picker_data" class="btn btn-primary btn-sm hidden-print">Grant '.$requested_user['user_samaccountname'].' a new Certification</a>&nbsp;&nbsp;');
		}
        echo("<a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
        echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
        echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
        echo("<input type='hidden' id='filename' name='filename' value='Certs_".$requested_user['user_firstname'].'.'.$requested_user['user_lastname'].'_'.date('Ymd').".xls'>");
        echo("</form>");

		$certs = array();
		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_certs_by_user_id.php?user_id='.$requested_user['user_id']) , false, getContextCookies()), true);

		if($json != null && count($json) > 0) {
			foreach ($json as $key => $value) {
				$largest_key = max(array_keys($value)); // This makes sure that we are always looking at the newest certification

                $dummy = array();
				$dummy['cert_name'] = $value[$largest_key]['cert_name'];
				$dummy['cert_id'] = $value[$largest_key]['cert_id'];
				$dummy['user_cert_id'] = $value[$largest_key]['user_cert_id'];
				$dummy['cert_description'] = $value[$largest_key]['cert_description'];
				$dummy['cert_never_expires'] = $value[$largest_key]['cert_never_expires'];
				$dummy['user_cert_date_granted_ymd'] = $value[$largest_key]['user_cert_date_granted_ymd'];
				if(isset($value[$largest_key]['calculated_expire_ymd'])) {
					$dummy['calculated_expire_ymd'] = $value[$largest_key]['calculated_expire_ymd'];
				}
				if(isset($value[$largest_key]['calculated_days_until_expire'])) {
					$dummy['calculated_days_until_expire'] = $value[$largest_key]['calculated_days_until_expire'];
				}
				$dummy['cert_notes'] = $value[$largest_key]['cert_notes'];
				$dummy['user_cert_exception'] = $value[$largest_key]['user_cert_exception'];
                $dummy['cert_points'] = $value[$largest_key]['cert_points'];
                $dummy['proficiency'] = $value[$largest_key]['proficiency'];
                $dummy['user_cert_date'] = $value[$largest_key]['user_cert_date'];
                $dummy['in_template'] = $value[$largest_key]['in_template'];

                if ($dummy['in_template'] !== 1 && floatval($dummy['proficiency']) !== 0.5) {
                    $postData = http_build_query([
                            'cert_id'           => $dummy['cert_id'],
                            'proficiency'       => '0.50',
                            'ad_account'        => $requested_user['user_samaccountname'],
                            'modified_user'     => $user['user_samaccountname'],
                            'modified_comments' => 'Auto default to 0.5'
                    ]);

                    $context_options = [
                            'http' => [
                                    'method'  => 'POST',
                                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n"
                                            . "Content-Length: " . strlen($postData) . "\r\n",
                                    'content' => $postData,
                                    'timeout' => 300,
                                    'ignore_errors' => true
                            ]
                    ];

                    $update_url = HTTP_BASEURL . '/JSON/JSON_ACTION_update_proficiency.php';
                    logit("Sending form POST: $update_url\n" . print_r($context_options, true));

                    $update_rslt = get_json($update_url, null, $context_options);
                    if (isset($update_rslt['success']) && $update_rslt['success']) {
                        $dummy['proficiency'] = 0.5;
                    }
                }

                $certs[$dummy['cert_id']] = $dummy;
				unset($dummy);
			}
			unset($json);

			// echo('<div style="margin-bottom:1em;" class="bootstrap_buttons container">');
			// echo('<button type="button" class="reset btn btn-primary" data-column="0" data-filter=""><i class="icon-white icon-refresh"></i> Reset filters</button>');
			// echo('</div>');
			echo("<table class='table_col_2_with_labels'>");
			echo("<thead>");
			echo("<tr>");

//            echo("<th>");
//            echo("No.");
//            echo("</th>");

            echo("<th>");
            echo("Cert Id");
            echo("</th>");

			echo("<th>");
			echo("Cert Name");
			echo("</th>");

			echo("<th>");
			echo("Description");
			echo("</th>");

            echo("<th>");
            echo("Grant Date");
            echo("</th>");

			echo("<th>");
			// echo("Expiration");
			echo("Status");
			echo("</th>");

            echo("<th>");
            echo("In Template");
            echo("</th>");

            echo("<th>");
            echo("Cert Points");
            echo("</th>");

            echo("<th>");
            echo("Proficiency");
            echo("</th>");

            echo("<th>");
            echo("Points");
            echo("</th>");

			echo("</tr>\n");
			echo("</thead>");

			echo("<tbody>");

            $count_c = 0;
            $totalpoints = 0;
			foreach ($certs as $value) {
                ++$count_c;
				$notification_certs_arr[] = array('cert_id' => $value['cert_id'], 'user_cert_id' => $value['user_cert_id']);
				echo('<tr>');

//                echo("<td>");
//                echo(++$count_c);
//                echo('</td>');

                echo("<td>");
                echo('<span class="cert-id">' . htmlspecialchars($value['cert_id']) . '</span>');
                echo("</td>");

				echo("<td>");
				echo($value['cert_name']);
				echo('</td>');

                echo("<td>");
                echo($value['cert_description']);
                echo('</td>');

                echo("<td>");
                echo('<span class="user_cert_date">' . htmlspecialchars($value['user_cert_date']) . '</span>');
                echo('</td>');

				echo("<td>");
				if($value['cert_never_expires'] == 0) {
                    echo($value['calculated_expire_ymd']);
					if(intval($value['calculated_days_until_expire']) < -31) {
						echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
					} elseif(intval($value['calculated_days_until_expire']) < 0) {
						echo('<span style="display:none;">||||</span> <span class="label" style="background-color:#E17572;">Now Due</span>');
					} elseif(intval($value['calculated_days_until_expire']) < 31) {
						echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$value['calculated_days_until_expire'].' days</span>');
					} else {
						echo('<span style="display:none;">||||</span> <span class="label label-success">'.$value['calculated_days_until_expire'].' days</span>'); // The 4 bars are for parsing in javascript.  Content comes before them.
					}
				} else {
					echo('does not expire');
				}
				if($value['user_cert_exception'] == 1) {
					echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
				}
				echo('</td>');

                echo("<td>");
                if ($value['in_template'] == 1) {
                    echo("Y");
                } else {
                    echo("N");
                }
                echo('</td>');

                echo("<td>");
                echo('<span class="cert_points">'.$value['cert_points'].'</span>');
                echo('</td>');

                echo("<td>");
                echo('<span class="proficiency-value">' . htmlspecialchars($value['proficiency']) . '</span>&nbsp;&nbsp;');
                if ($user['user_is_admin'] || $user['user_is_supervisor']) {
                    echo('<span class="edit-proficiency-icon glyphicon glyphicon-pencil text-primary" style="cursor: pointer;" data-proficiency="0.0"></span>&nbsp;&nbsp;');
                    if ($value['proficiency'] > 0) {
                        echo('<span class="view-history-icon glyphicon glyphicon-time text-secondary" style="cursor: pointer;" title="View Change History" data-cert-id="' . htmlspecialchars($value['cert_id']) . '"></span>');
                    }
                }
                echo("</td>");

                $pointsbycert = floatval($value['cert_points'])*floatval($value['proficiency']);
                $totalpoints += $pointsbycert;
                echo("<td>");
                echo('<span class="points-by-cert">'.$pointsbycert.'</span>');
                echo('</td>');

				echo('</tr>');
			}
			echo("</tbody>");
            echo("</tfoot>");
            echo('<tr><th colspan="6">Count:'.$count_c.'</th><th colspan="2">Total Points:</th><td id="total-points" class="hltext">'.number_format($totalpoints,2).'</td></tr>');
            echo("</tfoot>");
			echo("</table>\n");
            echo("</div>\n");
		} else {
			echo('<div class="alert alert-danger">');
			echo('<p>No certifications assigned to: "'.$requested_user['user_samaccountname'].'"</p>');
			echo('</div>');
		}

        /**
         * Templates
         */
//        if ($GLOBALS['DB_TYPE'] == 'pgsql'){
//            $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_template_info_by_user_id.php?user_id='.$requested_user['user_samaccountname']) , false, getContextCookies()), true);
//        } else {
        $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_template_info_by_user_id.php?user_id='.$requested_user['user_id']) , false, getContextCookies()), true);
//        }
		if($json['count'] > 0) {
			foreach ($json['items'] as $templates) {
                $count_template = 0;
				echo('<table><tr>');
				echo('<td><h2 style="margin:0px;">');
				echo('Template: ');
				echo($templates['template_name']);
				echo('</h2></td>');
				echo('</tr></table>');
				if($templates['certcount'] > 0) {
					echo("<table class='table_col_2_with_labels'>");
					echo("<thead>");
					echo("<tr>");

                    echo("<th>");
                    echo("No.");
                    echo("</th>");

                    echo("<th>");
                    echo("Cert Id");
                    echo("</th>");

					echo("<th>");
					echo("Cert Name");
					echo("</th>");

					echo("<th>");
					echo("Description");
					echo("</th>");

					echo("<th>");
					echo("Status");
					echo("</th>");

                    echo("<th>");
                    echo("Points");
                    echo("</th>");

					echo("</tr>\n");
					echo("</thead>");

					echo("<tbody>\n");
					foreach ($templates['certs'] as $value) {
						echo('<tr>');

                        echo('<td>');
                        echo(++$count_template);
                        echo("</td>\n");

                        echo('<td>');
                        echo($value['cert_id']);
                        echo("</td>\n");

						echo('<td>');
						echo($value['cert_name']);
						echo("</td>\n");

						echo('<td>');
						echo($value['cert_description']);
						echo("</td>\n");

						echo('<td>');
						if(isset($certs[$value['cert_id']])) {
							if($certs[$value['cert_id']]['cert_never_expires'] == 0) {
								echo($certs[$value['cert_id']]['calculated_expire_ymd']);

								if(intval($certs[$value['cert_id']]['calculated_days_until_expire']) < -31) {
									echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
								} elseif(intval($certs[$value['cert_id']]['calculated_days_until_expire']) < 0) {
									echo('<span style="display:none;">||||</span> <span class="label" style="background-color:#E17572;">Now Due</span>');
								} elseif(intval($certs[$value['cert_id']]['calculated_days_until_expire']) < 31) {
									echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$certs[$value['cert_id']]['calculated_days_until_expire'].' days</span>');
								} else {
									echo('<span style="display:none;">||||</span> <span class="label label-success">'.$certs[$value['cert_id']]['calculated_days_until_expire'].' days</span>'); // The 4 bars are for parsing in javascript.  Content comes before them.
								}

							} else {
								echo('does not expire');
							}
							if($certs[$value['cert_id']]['user_cert_exception'] == 1) {
								echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
							}
						} else {
							echo('Needed<span style="display:none;">||||</span> <span class="glyphicon glyphicon-exclamation-sign text-danger"></span>');
						}
						echo("</td>\n");

                        echo('<td>');
                        echo($value['cert_points']);
                        echo("</td>");

						echo("</tr>\n");
					}
					echo("</tbody>");
					echo("</table>\n");
				} else {
					echo('<div class="alert alert-warning">');
					echo('<p><strong>Notice:</strong> The "'.$templates['template_name'].'" Template currently has no Certifications assigned to it.</p>');
					echo('</div>');
				}
			}
		}

        /**
         * Notifications
         */
        $count_notification = 0;
		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_notifications.php?user_id='.$requested_user['user_id']) , false, getContextCookies()), true);
		if($json['count'] > 0){
			echo('<hr>');
			echo('<table><tr>');
			echo('<td><h2 style="margin:0px;">');
			echo('Notifications Sent by e-mail');
			echo('</h2></td>');
			echo('</tr></table>');

			echo("<table class='tablesorter' style='width:95%; margin-left:2em;'>");
			echo("<thead>");
			echo("<tr>");

            echo("<th>");
            echo("No.");
            echo("</th>");

			echo("<th>");
			echo("Cert");
			echo("</th>");

			echo("<th>");
			echo("Description");
			echo("</th>");

			echo("<th>");
			echo("Notification Sent");
			echo("</th>");

			echo("</tr>\n");
			echo("</thead>");

			echo("<tbody>\n");


			foreach ($json['items'] as $key => $value) {
				echo('<tr>');

                echo('<td>');
                echo(++$count_notification);
                echo("</td>\n");

				echo('<td>');
				echo($value['cert_name']);
				echo("</td>\n");

				echo('<td>');
				echo($value['cert_description']);
				echo("</td>\n");

				echo('<td>');
				echo($value['notification_sent_date_YMD']);
				echo("</td>\n");

				echo("</tr>\n");

			}
			echo("</tbody>");

			echo("</table>\n");
		}
		unset($json);
		// echo('</div>');


        /**
         * Bootstrap Modals
         */
		echo("\n");
		echo('<div class="modal none" id="modal_cert_picker_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="font-size:80%">');
		echo('<div class="modal-dialog wider-modal">');
		echo('<div class="modal-content">');
		echo('<div class="modal-header">');
		echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
		echo('<h4 class="modal-title">Please click on a Cert Name to select it</h4>');
		echo('</div>');
		echo('<div class="modal-body">');
		echo("<table class='tablesorter'>");
		echo("<thead>");
		echo('<tr>');

        echo('<th>');
        echo('No.');
        echo('</th>');

		echo('<th>');
		echo('Cert Name');
		echo('</th>');

		echo('<th>');
		echo('Description');
		echo('</th>');

		echo('<th>');
		echo('Expires');
		echo('</th>');

        echo('<th>');
        echo('Pts');
        echo('</th>');

		echo('</tr>');
		echo("</thead>");
		echo("<tbody>");
		$json_all_certs = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php') , false, getContextCookies()), true);
        $count_cert = 0;
		foreach ($json_all_certs as $key => $value) {
			echo('<tr>');

            echo('<td>');
            echo(++$count_cert);
            echo('</td>');

			echo('<td>');
			echo('<a href="javascript:void(0);" onclick="chooseCert('.$key.', \''.$value['cert_name'].'\');">'.$value['cert_name'].'</a>');
			echo('</td>');

			echo('<td>');
			echo($value['cert_description']);
			echo('</td>');

			echo('<td>');
			if($value['cert_never_expires'] == 1) {
				echo('Never');
			} else {
				echo($value['cert_days_active'].' Days');
			}
			echo('</td>');

            echo('<td>');
            echo($value['cert_points']);
            echo('</td>');

			echo('</tr>');
		}
		echo("</tbody>");
		echo('</table>');
		echo('</div>');
		echo('<div class="modal-footer">');
		echo('<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>');
		echo('</div>');
		echo('</div>'); // end of modal-content
		echo('</div>'); // end of modal-dialog
		echo('</div>'); // end of modal

		echo('<div class="modal none hidden-print" id="modal_date_picker_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
		echo('<div class="modal-dialog">');
		echo('<div class="modal-content">');
		echo('<form name="add_user_cert_form" id="add_user_cert_form" action="'.$mybaseurl.'/index.php?'.http_build_query($_GET).'" method="post" onsubmit="return validateAddCert();">');
		echo('<div class="modal-header">');
		echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
		echo('<h4 class="modal-title">Choose a starting date and press submit</h4>');
		echo('</div>');
		echo('<div class="modal-body">');
		echo('<p>Cert: <span id="date_modal_cert"></span></p>');
		echo('<p>User: '.$requested_user['user_samaccountname'].'</p>');
		echo('<input name="add_user_cert" type="hidden" value="1">');
		echo('<input name="cert_id" id="add_user_cert_cert_id" type="hidden" value="0">');
		echo('<input name="add_user_cert_cert_name" id="add_user_cert_cert_name" type="hidden" value="">');
		echo('<input name="add_user_cert_username" id="add_user_cert_username" type="hidden" value="'.$requested_user['user_samaccountname'].'">');
		echo('<input name="user_id" type="hidden" value="'.$requested_user['user_id'].'">');
		echo('<input name="user_cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'">');
		echo('<div class="form-group">');
		echo('<label for="user_cert_date_granted">Date:</label>');
		echo('<input type="text" class="form-control" id="user_cert_date_granted" name="user_cert_date_granted" placeholder="Enter Date">');
		echo('</div>');
		echo('<p>&nbsp;</p>');

		if ($user['user_is_admin']) {
			echo('<div class="form-group">');
			echo('<div class="checkbox">');
			echo('<label>');
			echo('<input id="user_cert_exception" name="user_cert_exception" type="checkbox" /> ');
			echo('This is not a Certification, this is an exception.');
			echo('</label>');
			echo('</div>');
			echo('</div>');
		} else {
			echo('<input id="user_cert_exception" name="user_cert_exception" type="hidden" value="" /> ');
		}

		echo('<p>&nbsp;</p>');
		echo('</div>'); // end of modal-body
		echo('<div class="modal-footer">');
		echo('<button type="submit" class="btn btn-default">Submit</button>');
		echo('<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>');
		echo('</div>');
		echo('</form>');
		echo('</div>'); // end of modal-content
		echo('</div>'); // end of modal-dialog
		echo('</div>'); // end of modal

		echo("\n");
        echo('<div id="edit-dialog" class="modal">');
        echo('<div class="modal-dialog">');
        echo('<div class="modal-content">');
        echo('<div class="modal-header">');
        echo('<h5 class="modal-title">Edit Proficiency</h5>');
        echo('</div>');
        echo('<div class="modal-body" >');
        echo('<label for="edit-proficiency-input">Proficiency[0.7, 1.1]:</label>');
        echo('<input type="text" id="edit-proficiency-input" />');
        echo('<br><label for="edit-comments">Comments[Non-empty. DO NOT USE \']:</label>');
        echo('<textarea id="edit-comments" rows="3" style="width:100%;"></textarea>');
        echo('</div>');
        echo('<div class="modal-footer">');
        echo('<button type="button" id="cancel-edit-btn" class="btn btn-secondary">Cancel</button>');
        echo('<button type="button" id="save-edit-btn" class="btn btn-primary">Save</button>');
        echo('</div>');
        echo('</div>');
        echo('</div>');
        echo('</div>');

        echo('<div id="history-dialog" class="modal">');
        echo('<div class="modal-dialog modal-lg" style="width: 60%">');
        echo('<div class="modal-content">');
        echo('<div class="modal-header">');
        echo("<h4 class='modal-title'>Change History </h4>");
//        echo('<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>');
        echo('</div>');
        echo('<div class="modal-body">');
        echo('<p>Loading history...</p>');
        echo('</div>');
        echo('<div class="modal-footer">');
        echo('<button type="button" id="close-history-btn">Close</button>');
        echo('</div>');
        echo('</div>');
        echo('</div>');
        echo('</div>');

    } else {
		// echo('<div class="span-24 last" style="margin-top:1em;">');
		// echo('<p>Authorization failed to view user id '.$requested_user['user_id'].'</p>');
		// echo('</div>');
		echo('<div class="alert alert-danger">');
		// echo('<p>Authorization failed</p>');
		echo('<p>Authorization failed to view user id '.$requested_user['user_id'].'</p>');
		echo('</div>');
	}
?>

<script>
    // Track the current icon
    let currentIcon = null;

    document.querySelectorAll('.view-history-icon').forEach(icon => {
        icon.addEventListener('click', function () {
            const certId = this.getAttribute('data-cert-id');
            const adAccount = "<?php echo $requested_user['user_samaccountname']; ?>";
            if (!certId || !adAccount) {
                alert('Certification ID or AD account is missing.');
                return;
            }

            console.log(`Fetching: JSON/JSON_ACTION_fetch_history.php?cert_id=${certId}&ad_account=${adAccount}`);
            // Fetch the history via AJAX
            fetch(`JSON/JSON_ACTION_fetch_history.php?cert_id=${certId}&ad_account=${adAccount}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Construct the history table
                        let historyTable = `
                        <h4> ${adAccount} - ${certId}</h4>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Modified Time</th>
                                    <th>Proficiency</th>
                                    <th>Modified By</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                        // Loop through the history object and create table rows
                        for (const key in data.history) {
                            const entry = data.history[key];
                            historyTable += `
                            <tr>
                                <td>${entry.modified_time}</td>
                                <td>${entry.proficiency}</td>
                                <td>${entry.modified_user}</td>
                                <td>${entry.modified_comments}</td>
                            </tr>
                        `;
                        }

                        historyTable += `
                            </tbody>
                        </table>
                    `;

                        // Display the table in the modal
                        const historyDialog = document.getElementById('history-dialog');
                        historyDialog.querySelector('.modal-body').innerHTML = historyTable;
                        historyDialog.style.display = 'block';
                    } else {
                        alert('Failed to fetch history: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching history:', error);
                    alert('Unable to fetch change history. Please try again later.');
                });
        });
    });

    document.getElementById('close-history-btn').addEventListener('click', function () {
        const historyDialog = document.getElementById('history-dialog');
        historyDialog.style.display = 'none';
    });

    // Show the modal when the edit icon is clicked
    document.querySelectorAll('.glyphicon-pencil').forEach(icon => {
        icon.addEventListener('click', function () {
            currentIcon = this; // Save reference to the clicked icon
            const proficiencyValue = parseFloat(this.getAttribute('data-proficiency'));
            const inputField = document.getElementById('edit-proficiency-input');
            inputField.value = proficiencyValue.toFixed(2); // Set the current value in the input field

            const commentsField = document.getElementById('edit-comments');
            commentsField.value = ''; // Clear the comments field

            const saveButton = document.getElementById('save-edit-btn');
            saveButton.disabled = true; // Initially disable the Save button

            const dialog = document.getElementById('edit-dialog');
            dialog.style.display = 'block'; // Show the modal
        });
    });

    // Enable/Disable Save button based on comments input
    document.getElementById('edit-comments').addEventListener('input', function () {
        const commentsValue = this.value.trim();
        const saveButton = document.getElementById('save-edit-btn');
        saveButton.disabled = commentsValue.length === 0; // Disable if comments are empty
    });

    // Save changes when the Save button is clicked
    document.getElementById('save-edit-btn').addEventListener('click', function () {
        const inputField = document.getElementById('edit-proficiency-input');
        const commentsField = document.getElementById('edit-comments');
        const newProficiency = parseFloat(inputField.value);
        const modifiedComments = commentsField.value.trim();

        // Validate the proficiency range
        if (isNaN(newProficiency) || newProficiency < 0.7 || newProficiency > 1.1) {
            alert('Proficiency must be a number between 0.7 and 1.1.');
            return;
        }

        const adAccount = "<?php echo $requested_user['user_samaccountname']; ?>";
        const modifiedUser = "<?php echo $user['user_samaccountname']; ?>";
        const currentRow = currentIcon.closest('tr'); // Find the parent row
        const certId = currentRow.querySelector('.cert-id')?.textContent.trim();
        const userCertDate = currentRow.querySelector('.user_cert_date')?.textContent.trim();
        const certPointsSpan = currentIcon.closest('td').previousElementSibling.querySelector('.cert_points');
        const certPoints = parseFloat(certPointsSpan.textContent);

        console.log(adAccount);
        console.log('Cert ID:', certId);
        console.log('User Cert Date:', userCertDate);
        console.log(newProficiency);
        console.log(modifiedUser);
        console.log(modifiedComments);

        if (!certId || !adAccount || !userCertDate) {
            alert('Cert ID | AD ACCOUNT | USER CERT DATE is missing.');
            return;
        }

        // Send the updated proficiency value and comments to the server
        fetch('JSON/JSON_ACTION_update_proficiency.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                cert_id: certId,
                proficiency: newProficiency.toFixed(2),
                ad_account: adAccount,
                modified_user: modifiedUser,
                modified_comments: modifiedComments
            }),
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the proficiency value in the table
                    const proficiencySpan = currentIcon.previousElementSibling;
                    if (proficiencySpan) {
                        proficiencySpan.textContent = newProficiency.toFixed(2); // Update the displayed proficiency value
                        currentIcon.setAttribute('data-proficiency', newProficiency.toFixed(2)); // Update the icon's data attribute
                    }

                    // Update the points-by-cert value
                    const pointsByCertCell = currentIcon.closest('td').nextElementSibling.querySelector('.points-by-cert');
                    if (!isNaN(certPoints) && pointsByCertCell) {
                        pointsByCertCell.textContent = (certPoints * newProficiency).toFixed(2);
                    }

                    // Recalculate the total points
                    recalculateTotalPoints();

                    // Hide the modal
                    const dialog = document.getElementById('edit-dialog');
                    dialog.style.display = 'none';

                    alert('Proficiency updated successfully.');
                } else {
                    alert(`Error: ${data.message}`);
                }
            }).catch(error => {
            console.error('Error:', error);
            alert('Failed to update proficiency. Please try again.');
        });
    });

    // Hide the modal when the Cancel button is clicked
    document.getElementById('cancel-edit-btn').addEventListener('click', function () {
        const dialog = document.getElementById('edit-dialog');
        dialog.style.display = 'none';
    });

    // Function to recalculate total points and update the table footer
    function recalculateTotalPoints() {
        let totalPoints = 0;
        document.querySelectorAll('.points-by-cert').forEach(cell => {
            const value = parseFloat(cell.textContent);
            if (!isNaN(value)) {
                totalPoints += value;
            }
        });

        // Update the total points in the footer
        const totalPointsCell = document.querySelector('#total-points');
        if (totalPointsCell) {
            totalPointsCell.textContent = totalPoints.toFixed(2);
        }
    }
</script>


<?php
	include_once('footer.php');
?>