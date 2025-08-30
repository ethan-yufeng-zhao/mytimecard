<?php
	// template_audit.php?template_id=3
	include_once('header.php');  // has everything up to the container div in the body
?>


<script type="text/javascript">
    $(document).ready(function() {
        $(".table_col_3_with_labels").tablesorter({
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
</script>


<?php
	$authorized = false;

	if(!$authorized && $user['user_is_admin']) { // Admin users can view anyone
		$authorized = true;
		$limited_supervisor_access = false;
	}

	if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
		$template_id = intval($_GET['template_id']);

		if(!$authorized) { // Check if they are a supervisor
			$limited_supervisor_access = true;
			$supervisor_user_id_arr = array();
			$json_team_info = json_decode(file_get_contents(request_json_api('/JSON/JSON_team_users_templates.php?user_supervisor_id='.$user['user_id']) , false, getContextCookies()), true);
			if(count($json_team_info) > 0) {
				foreach ($json_team_info as $template_user_links_id => $value) {
					$supervisor_user_id_arr[] = $value['user_id'];
					if($value['template_id'] == $template_id) {
						$authorized = true;
					}
				}
				if($limited_supervisor_access && !$authorized) {
					echo('<div class="alert alert-danger">');
					echo('<p><strong>Supervisor Error:</strong> You do not have any users who are assigned to this template</p>');
					echo('</div>');
				}
				if($authorized) {
					echo('<div class="alert alert-dismissable alert-warning">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Supervisor Notice:</strong> only employee\'s that report directly to you are visible.</p>');
					echo('</div>');
				}
			}
		}

		if($authorized) {
			$json_certs_in_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_template_info_by_template_id.php?template_id='.$template_id) , false, getContextCookies()), true);

			echo("<div id='jfabtable'>\n");

			echo('<table><tr>');
			echo('<td><h2 style="margin:0px;">');
			$template_name = $json_certs_in_template['items'][$template_id]['template_name'];
			echo('Template Audit: '.$template_name);
			echo('</h2></td></tr>');
			if($json_certs_in_template['items'][$template_id]['template_is_default_for_department']) {
				echo('<tr><td>');
				echo('This is the default template for department number '.$json_certs_in_template['items'][$template_id]['template_department_number']);
				echo('</td></tr>');

			}
			echo('<tr><td>');
			echo(date('Y-m-d H:i:s'));
			echo('</td>');
			echo('</tr></table>');

			if($json_certs_in_template['items'][$template_id]['usercount'] > 0 && $json_certs_in_template['items'][$template_id]['certcount']) {
				echo("<table class='table_col_3_with_labels'>");
				echo("<thead>");
				echo("<tr>");

                echo("<th>");
                echo("No.");
                echo("</th>");

//				echo("<th>");
//				echo("User");
//				echo("</th>");

                echo("<th>");
                echo("Cert Id");
                echo("</th>");

				echo("<th>");
				echo("Cert Name");
				echo("</th>");

				echo("<th>");
				echo("Cert Description");
				echo("</th>");

				echo("<th>");
				echo("Expiration");
				echo("</th>");

				// echo("<th>");
				// echo("Never Expires");
				// echo("</th>");

				echo("<th>");
				echo("History Count");
				echo("</th>");

				echo("</tr>\n");
				echo("</thead>");

				echo("<tbody>\n");

				$json_users_in_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_users_in_template_by_template_id.php?template_id='.$template_id) , false, getContextCookies()), true);
				$json_user_certs_that_match_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_certs.php?template_id='.$template_id) , false, getContextCookies()), true);
                $count = 0;
				foreach($json_users_in_template['items'][$template_id]['users'] as $template_user_links_id => $user_value) {
					if($GLOBALS['DB_TYPE'] == 'pgsql') {
                        $user_id = $user_value;
                    } else {
                        $user_id = $user_value['user_id'];
                    }

					if($user['user_is_admin'] || ($limited_supervisor_access && in_array($user_id, $supervisor_user_id_arr))) {
					// if($user['user_is_admin']) {
					// if(true) {
						foreach($json_certs_in_template['items'][$template_id]['certs'] as $template_cert_links_id => $cert_value) {
							$cert_id = $cert_value['cert_id'];

							echo('<tr>');

                            echo('<td>');
                            echo(++$count);
                            echo("</td>\n");

//							echo('<td>');
//							echo('<a href="'.$mybaseurl.'/index.php?uid='.$user_id.'" title="'.$user_value['user_firstname'].' '.$user_value['user_lastname'].'">');
//							echo($user_value['user_samaccountname']);
//							echo('</a>');
//							echo("</td>\n");

                            echo('<td>');
                            echo($cert_value['cert_id']);
                            echo("</td>\n");

							echo('<td>');
							echo($cert_value['cert_name']);
							echo("</td>\n");

							echo('<td>');
							echo($cert_value['cert_description']);
							echo("</td>\n");

							if(isset($json_user_certs_that_match_template['items'][$user_id]) && isset($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id])) {
								$largest_key = max(array_keys($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id])); // This makes sure that we are always looking at the newest certification
								echo('<td>');
								if($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['cert_never_expires'] == 0){
									echo($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['calculated_expire_ymd']);
									$calculated_days_until_expire = intval($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['calculated_days_until_expire']);

									if( $calculated_days_until_expire < -31 ) {
										echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
									} elseif($calculated_days_until_expire < 0) {
										echo('<span style="display:none;">||||</span> <span class="label" style="background-color:#E17572;">Now Due</span>');
									} elseif($calculated_days_until_expire < 30) {
										echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$calculated_days_until_expire.' days</span>');
									} else {
										echo('<span style="display:none;">||||</span> <span class="label label-success">'.$calculated_days_until_expire.' days</span>');
									}

									// if($cert_value['cert_never_expires'] == 1){
									// 	echo(' <span class="label label-success">Never Expires</span>');
									// }

									// echo(' - (');
									// echo($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['calculated_days_until_expire']);
									// echo(')');
								} else {
									echo('<span style="display:none;">||||</span> <span class="label label-success">Never Expires</span>');
								}
								if($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['user_cert_exception'] == 1) {
									echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
								}
								echo("</td>\n");

								// echo('<td>');
								// if($cert_value['cert_never_expires'] == 1){
								// 	echo('Y');
								// }
								// echo("</td>\n");

								echo('<td>');
								echo('<a href="'.$mybaseurl.'/user_cert_history.php?cert_id='.$json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id][$largest_key]['cert_id'].'&uid='.$user_id.'">');
								echo(count($json_user_certs_that_match_template['items'][$user_id]['certs'][$cert_id]));
								echo('</a>');
								echo("</td>\n");
							} else {
								// echo("<td>Needed</td>\n");
								echo('<td>Needed<span style="display:none;">||||</span> <span class="glyphicon glyphicon-exclamation-sign text-danger"></span></td>');
								// echo('<td>');
								// if($cert_value['cert_never_expires'] == 1){
								// 	echo('Y');
								// }
								// echo("</td>\n");
								echo("<td>0</td>\n");
							}
							echo("</tr>\n");
						}
					}
                    break;
				}

				echo("</tbody>");

				echo("</table>\n");
			} else {
				echo('<div class="alert alert-danger">');
				echo('<p>There must be at least one user and one cert tied to this template to generate this report.</p>');
				echo('</div>');
			}
			echo("</div>\n"); // end of jfabtable
			echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");

			echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
			echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
			echo("<input type='hidden' id='filename' name='filename' value='template_audit.xls'>");
			echo("</form>");
			echo('<p>&nbsp;</p>');
		} else {
			echo('<div class="alert alert-danger">');
			echo('<p>Authorization failed</p>');
			echo('</div>');
		}
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Please set a valid template_id</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
