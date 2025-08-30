<?php
	// template_cert_links.php?template_id=2
	include_once('header.php');  // has everything up to the container div in the body
	$json_all_certs = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php') , false, getContextCookies()), true);
?>

<script type="text/javascript">
	function removeSpecFromTemplate(template_cert_links_id, cert_name, template_id, template_name) {
		if(confirm("Template Name: "+template_name+"\nCert Name: "+cert_name+"\n\nRemove this Cert from this Template?")) {
			$("#template_cert_links_id").val(template_cert_links_id);
            $("#template_id").val(template_id);
			$('#remove_cert_from_template_form').submit();
		}
	}
	function chooseCertForTemplate(cert_id, cert_name, template_name) {
		if(confirm("Add Cert: "+cert_name+"\n\nTo Template: "+template_name)) {
			$("#template_cert_links_cert_id").val(cert_id);
			$('#template_cert_links_add_cert_form').submit();
		}
	}
</script>

<?php
	$authorized = false;
	if(!$authorized && $user['user_is_admin']) { // Admin users can view anyone
		$authorized = true;
	}

	if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
		if($authorized) {
			if (isset($_POST['template_cert_links_template_id']) && is_numeric($_POST['template_cert_links_template_id']) && $_POST['template_cert_links_template_id'] > 0
                && isset($_POST['template_cert_links_cert_id']) && is_numeric($_POST['template_cert_links_cert_id']) && $_POST['template_cert_links_cert_id'] > 0) {
				$post_add_cert = array();
				$post_add_cert['template_id'] = $_POST['template_cert_links_template_id'];
				$post_add_cert['cert_id'] = $_POST['template_cert_links_cert_id'];
				$json_add_cert_to_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_cert_to_template.php?'.http_build_query($post_add_cert)) , false, getContextCookies()), true);
				if (isset($json_add_cert_to_template['success']) && $json_add_cert_to_template['success'] == true) {
					echo('<div class="alert alert-dismissable alert-success">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<strong>Success:</strong> '.$json_add_cert_to_template['message']);
					echo('</div>');
				} else {
					echo('<div class="alert alert-dismissable alert-danger">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Error:</strong> Failed to add cert to template</p>');
					if(isset($json_add_cert_to_template['error'])) {
						echo('<p style="margin-left:2em;">'.$json_add_cert_to_template['error'].'</p>');
					} else {
						echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
					}
					echo('</div>');
				}
				unset($json_add_cert_to_template);
			}

			if (isset($_POST['remove_cert_from_template']) && $_POST['remove_cert_from_template'] == 1) { // Do a delete from the database
				$post_remove_arr = array();
				$post_remove_arr['template_cert_links_id'] = $_POST['template_cert_links_id'];
                $post_remove_arr['template_id'] = $_GET['template_id'];
				$json_remove_cert_from_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_template_cert_links.php?'.http_build_query($post_remove_arr)) , false, getContextCookies()), true);
				if (isset($json_remove_cert_from_template['success']) && $json_remove_cert_from_template['success'] == true) {
					echo('<div class="alert alert-dismissable alert-success">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<strong>Success:</strong> '.$json_remove_cert_from_template['message']);
					echo('</div>');
				} else {
					echo('<div class="alert alert-dismissable alert-danger">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Error:</strong> Unable to remove cert</p>');
					if(isset($json_remove_cert_from_template['error'])) {
						echo('<p style="margin-left:2em;">'.$json_remove_cert_from_template['error'].'</p>');
					} else {
						echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
					}
					echo('</div>');
				}
				unset($json_add_user_cert);
			}

            /**
             * Begining of content
             */
			$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_template_info_by_template_id.php?template_id='.$_GET['template_id']) , false, getContextCookies()), true);
			foreach($json['items'] as $value) {
				echo("<div id='jfabtable'>\n");
				// echo('<table><tr>');
				// echo('<td><h2 style="margin:0;">');
				// $template_name = $value['template_name'];
				// echo('Template: '.$template_name);
				// echo('</h2></td></tr>');
				// if($value['template_is_default_for_department']) {
				// 	echo('<tr><td>');
				// 	echo('This is the default template for department number '.$value['template_department_number']);
				// 	echo('</td></tr>');
				// }
				// echo('</table>');

				echo('<table><tr>');
				echo('<td><h2 style="margin:0px;">');
				$template_name = $value['template_name'];
				echo('Template: '.$template_name);
				echo('</h2></td></tr>');
				echo('<tr><td>');
				if($value['template_is_default_for_department']) {
					echo('<tr><td>');
					echo('This is the default template for department number '.$value['template_department_number']);
					echo('</td></tr>');
				}
				echo('</td></tr>');
				echo('<tr><td>');
				echo(date('Y-m-d H:i:s'));
				echo('</td>');
				echo('</tr></table>');

				echo('<p><a data-toggle="modal" href="#modal_template_add_cert_data" class="btn btn-primary btn-sm hidden-print">Add Certification to this Template</a></p>');

				if($value['certcount'] > 0) {
					echo("<table class='tablesorter'>");
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
					echo("Days Cert is Active");
					echo("</th>");

					echo("</tr>\n");
					echo("</thead>");

					echo("<tbody>\n");
                    $count_1 = 0;
					foreach ($value['certs'] as $certvalue) {
						echo('<tr>');

                        echo('<td>');
                        echo(++$count_1);
                        echo("</td>\n");

                        echo('<td>');
                        echo($certvalue['cert_id']);
                        echo("</td>\n");

						echo('<td>');
						echo($certvalue['cert_name']);
						if(isset($_GET['enable_edit']) && $_GET['enable_edit'] == 1) {
							echo('<span style="display:none;">||||</span> ');
                            if ($GLOBALS['DB_TYPE'] == 'pgsql') {
                                echo("<a href='javascript:void(0);' onclick='removeSpecFromTemplate(".$certvalue['cert_id'].", \"".$certvalue['cert_name']."\", ".$_GET['template_id'].", \"".$value['template_name']."\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Delete ".$value['template_name']." Template'>");
                            } else {
                                echo("<a href='javascript:void(0);' onclick='removeSpecFromTemplate(".$certvalue['template_cert_links_id'].", \"".$certvalue['cert_name']."\", ".$_GET['template_id'].", \"".$value['template_name']."\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Delete ".$value['template_name']." Template'>");
                            }
							echo('<span class="glyphicon glyphicon-remove"></span></a>');
						}
						echo("</td>\n");

						echo('<td>');
						echo($certvalue['cert_description']);
						echo("</td>\n");

						echo('<td>');
						echo($certvalue['cert_days_active']);
						echo("</td>\n");

						echo("</tr>\n");
					}
					echo("</tbody>");
					echo("</table>\n");
					echo("</div>\n"); // end of jfabtable

					echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
					// echo('<p>');
					// echo("<a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();'>");
					// echo('Save to Excel');
					// echo('</a>');
					// echo('</p>');
					echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
					echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
					echo("<input type='hidden' id='filename' name='filename' value='template_cert.xls'>");
					echo("</form>");

                    $removefields = $_GET;
                    if(!isset($_GET['enable_edit']) || $_GET['enable_edit'] != 1) {
                        $removefields['enable_edit'] = 1;
                        echo('<p><a href="'.$mybaseurl.'/template_cert_links.php?'.http_build_query($removefields).'" class="btn btn-danger btn-sm btn-group hidden-print">Enable remove mode</a></p>');
                    } else {
                        $removefields['enable_edit'] = 0;
                        echo('<p><a href="'.$mybaseurl.'/template_cert_links.php?'.http_build_query($removefields).'" class="btn btn-success btn-sm btn-group hidden-print">Disable remove mode</a></p>');
                    }
				} else {
					echo('<div class="alert alert-danger">');
					echo('<p>There are currently no certifications associated with this template</p>');
					echo('</div>');
				}
			}

			echo('<p>&nbsp;</p>');

			$formfields = $_GET;
			$formfields['enable_edit'] = 0;
			echo('<form name="remove_cert_from_template_form" id="remove_cert_from_template_form" action="'.$mybaseurl.'/template_cert_links.php?'.http_build_query($formfields).'" method="post">');
			echo('<input name="remove_cert_from_template" type="hidden" value="1">');
			echo('<input name="template_cert_links_id" id="template_cert_links_id" type="hidden" value="0">');
			echo('</form>');

			echo('<form name="template_cert_links_add_cert_form" id="template_cert_links_add_cert_form" action="'.$mybaseurl.'/template_cert_links.php?'.http_build_query($formfields).'" method="post">');
			echo('<input name="template_cert_links_template_id" id="template_cert_links_template_id" type="hidden" value="'.$_GET['template_id'].'">');
			echo('<input name="template_cert_links_cert_id" id="template_cert_links_cert_id" type="hidden" value="0">');
			echo('</form>');

            /**
             * Bootstrap Modals
             */
			echo('<div class="modal none hidden-print" id="modal_template_add_cert_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
			echo('<div class="modal-dialog wider-modal">');
			echo('<div class="modal-content">');
			echo('<form role="form" name="create_template" id="create_template" action="'.$mybaseurl.'/all_templates.php" method="post" onsubmit="return certValidateCreateTemplate();">');
			echo('<div class="modal-header">');
			echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
			echo('<h4 class="modal-title">Please click on a Cert Name to select it</h4>');
			echo('</div>');
			echo('<div class="modal-body">');

			echo("<table class='tablesorter'>");
			echo("<thead>");
			echo('<tr>');

//            echo('<th>');
//            echo('No.');
//            echo('</th>');

            echo('<th>');
            echo('Cert Id');
            echo('</th>');

			echo('<th>');
			echo('Cert Name');
			echo('</th>');

			echo('<th>');
			echo('Cert Description');
			echo('</th>');

			echo('<th>');
			echo('Expires');
			echo('</th>');

			echo('</tr>');
			echo("</thead>");
			echo("<tbody>");

//            $count_cert = 0;
			foreach ($json_all_certs as $key => $value) {
				echo('<tr>');

//                echo('<td>');
//                echo(++$count_cert);
//                echo('</td>');

                echo('<td>');
                echo($value['cert_id']);
                echo('</td>');

				echo('<td>');
				echo('<a href="javascript:void(0);" onclick="chooseCertForTemplate('.$key.', \''.$value['cert_name'].'\', \''.$template_name.'\');">'.$value['cert_name'].'</a>');
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

				echo('</tr>');
			}
			echo("</tbody>");
			echo('</table>');

			echo('</div>');
			// echo('<div class="modal-footer">');
			// echo('<button type="submit" class="btn btn-primary btn-sm btn-group hidden-print">Add Template</button>');
			// echo('</div>');
			echo('</form>');
			echo('</div>'); // end of modal-content
			echo('</div>'); // end of modal-dialog
			echo('</div>'); // end of modal

			// echo('<div style="display:none;" class="hidden">');
			// echo('<div id="hidden_template_add_cert_data">');
			// echo('<div class="span-16 prepend-1">');
			// echo('<h2>Please click on a Cert Name to select it.</h2>');

			// echo("<table class='tablesorter'>");
			// echo("<thead>");
			// echo('<tr>');
			// echo('<th>');
			// echo('Cert Name');
			// echo('</th>');
			// echo('<th>');
			// echo('Cert Description');
			// echo('</th>');
			// echo('<th>');
			// echo('Expires');
			// echo('</th>');
			// echo('</tr>');
			// echo("</thead>");
			// echo("<tbody>");
			// foreach ($json_all_certs['items'] as $key => $value) {
			// 	echo('<tr>');
			// 	echo('<td>');
			// 	echo('<a href="javascript:void(0);" onclick="chooseCertForTemplate('.$key.', \''.$value['cert_name'].'\', \''.$template_name.'\');">'.$value['cert_name'].'</a>');
			// 	echo('</td>');
			// 	echo('<td>');
			// 	echo($value['cert_description']);
			// 	echo('</td>');
			// 	echo('<td>');
			// 	if($value['cert_never_expires'] == 1) {
			// 		echo('Never');
			// 	} else {
			// 		echo($value['cert_days_active'].' Days');
			// 	}
			// 	echo('</td>');
			// 	echo('</tr>');
			// }
			// echo("</tbody>");
			// echo('</table>');
			// echo('</div>');
			// echo('</div>');

			// echo('</div>');
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

	// echo('</div>');
	include_once('footer.php');
?>
