<?php
	// cert.php
	include_once('header.php');  // has everything up to the container div in the body
?>

<script type="text/javascript">
    function neverExpires(){
        if ($('#cert_never_expires').is(':checked')) {
            $("#div_tag_cert_days_active").hide();
        } else {
            $("#div_tag_cert_days_active").show();
        }
    }

    function certValidateEdit(){ // This function is for adding or deleting
        if($('#cert_name').length > 0) { // This nest is only for the add form
            if($('#cert_name').val().length < 1) {
                alert('The Certs Name must be set.');
                $("#cert_name").focus();
                return false;
            }
        }
        if (!$('#cert_never_expires').is(':checked')) {
            if($('#cert_days_active').val().length < 1) {
                alert('This cert expires so the number of days must be set.');
                $("#cert_days_active").focus();
                return false;
            }
            if($('#cert_days_active').val() > 18250) {
                alert('The maximum number of days a Cert can last is 18250 days.');
                $("#cert_days_active").focus();
                return false;
            }
            if($('#cert_days_active').val() < 1) {
                alert('The mainimum number of days a Cert can last is 1 day.');
                $("#cert_days_active").focus();
                return false;
            }
        }
        if($('#cert_description').val().length < 1) {
            alert('A description must be set.');
            $("#cert_description").focus();
            return false;
        }
        if(confirm("Submit this Certificate?")) {
            return true;
        } else {
            return false;
        }
    }

    function certValidateDelete(){
        let confirmMsg;
        //alert($('#delete_cert').val());
        if ($('#delete_cert').val() < 1) {
            confirmMsg = "Disable this Certificate?";
        } else {
            confirmMsg = "Reactivate this Certificate?";
        }
        return confirm(confirmMsg);
    }

    function certValidateTrueDelete(){
        let confirmMsg = "Delete this Certificate?";
        return confirm(confirmMsg);
    }

    function warningValidate(){
        if($('#warning_number_of_days').val().length < 1) {
            alert('The warning value must be set.');
            $("#warning_number_of_days").focus();
            return false;
        }
        if ($('#cert_never_expires').is(':checked')) {
            alert('Can not set a warning on a cert that never expires.');
            return false;
        }

        // commented out so negatives could be added on 2014-09-16
        // if($('#warning_number_of_days').val() < 0) {
        // 	alert('The warning value must more than 0.');
        // 	$("#warning_number_of_days").focus();
        // 	return false;
        // }
        if($('#warning_number_of_days').val() > 18251) {
            alert('The warning value is too large.');
            $("#warning_number_of_days").focus();
            return false;
        }
        if(confirm("Add this warning?")) {
            return true;
        } else {
            return false;
        }
    }

    function toolValidate(){
        const selectElement = document.querySelector('select');
        const selectedOptions = Array.from(selectElement.selectedOptions).map(option => option.value);
        console.log(selectedOptions);
        let combinedArray = selectedOptions;

        const temp_new_tool = document.getElementById("temp_new_tool");
        const temp_new_tool_string = temp_new_tool.value.trim().toUpperCase();
        if (temp_new_tool_string.length === 0) {
            // TBD
        } else {
            const new_tools = temp_new_tool_string.split(',').map((item) => item.trim());
            combinedArray = selectedOptions.concat(new_tools);
        }

        const selected_Tools = document.getElementById("selected_Tools");
        selected_Tools.value = combinedArray;
        if (combinedArray.length === 0) {
            return false;
        } else {
            return confirm("Add tools: " + selected_Tools.value + " ?");
        }
    }
</script>

<?php
	$authorized = false;

	if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
		$authorized = true;
	}

	if($authorized){
        // update - add tool
        if (isset($_POST['add_tool']) && $_POST['add_tool'] == 1) { // this is an update
            $json_add_tool = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_tool.php?cert_id='.$_POST['cert_id'].'&selected_Tools='.$_POST['selected_Tools'].'&tool_last_user='.$_POST['tool_last_user']) , false, getContextCookies()), true);
            if (isset($json_add_tool['success']) && $json_add_tool['success'] == true) {
                echo('<div class="alert alert-dismissable alert-success">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<strong>Success:</strong> Tools have been added to the cert');
                echo('</div>');
            } else {
                echo('<div class="alert alert-dismissable alert-danger">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<p><strong>Error:</strong> Tool was unable to be added to the cert</p>');
                if(isset($json_add_tool['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_add_tool['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
            }
        }
        // update - delete tool
        if (isset($_POST['delete_tool']) && is_numeric($_POST['delete_tool'])) { // this is an update
            $json_delete_tool = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_tool_from_cert.php?cert_id='.$_POST['cert_id'].'&tool_name='.$_POST['tool_name'].'&tool_last_user='.$_POST['tool_last_user']) , false, getContextCookies()), true);
            if (isset($json_delete_tool['success']) && $json_delete_tool['success'] == true) {
                echo('<div class="alert alert-dismissable alert-success">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<p><strong>Success:</strong> Tool has been deleted</p>');
                echo('</div>');
            } else {
                echo('<div class="alert alert-dismissable alert-danger">');
                echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                echo('<p><strong>Error:</strong> Tool was unable to be added</p>');
                if(isset($json_delete_tool['error'])) {
                    echo('<p style="margin-left:2em;">'.$json_delete_tool['error'].'</p>');
                } else {
                    echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                }
                echo('</div>');
            }
            unset($json_delete_tool);
        }
        // update - add warning
		if (isset($_POST['add_warning']) && $_POST['add_warning'] == 1) { // this is an update
			$json_add_warning = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_warning.php?cert_id='.$_POST['cert_id'].'&warning_number_of_days='.$_POST['warning_number_of_days'].'&warning_last_user='.$_POST['warning_last_user']) , false, getContextCookies()), true);
			if (isset($json_add_warning['success']) && $json_add_warning['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<strong>Success:</strong> Warning has been added');
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Warning was unable to be added</p>');
				if(isset($json_add_warning['error'])) {
					echo('<p style="margin-left:2em;">'.$json_add_warning['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
		}
        // update - delete warning
		if (isset($_POST['delete_warning']) && is_numeric($_POST['delete_warning'])) { // this is an update
			$json_delete_warning = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_warning.php?warning_id='.$_POST['warning_id'].'&delete_warning='.$_POST['delete_warning'].'&warning_last_user='.$_POST['warning_last_user']) , false, getContextCookies()), true);
			if (isset($json_delete_warning['success']) && $json_delete_warning['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Success:</strong> Warning has been deleted</p>');
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Warning was unable to be added</p>');
				if(isset($json_delete_warning['error'])) {
					echo('<p style="margin-left:2em;">'.$json_delete_warning['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
			unset($json_delete_warning);
		}
        // update - edit cert
		if (isset($_POST['edit_cert']) && $_POST['edit_cert'] == 1) { // this is an update
			$fields = array();
			$fields['cert_id'] = $_POST['cert_id'];
			$fields['cert_days_active'] = $_POST['cert_days_active'];
			$fields['cert_description'] = $_POST['cert_description'];
			if(isset($_POST['cert_never_expires'])) {
				$fields['cert_never_expires'] = $_POST['cert_never_expires'];
			}
			if(isset($_POST['cert_is_ert'])) {
				$fields['cert_is_ert'] = $_POST['cert_is_ert'];
			}
            if(isset($_POST['cert_is_iso'])) {
                $fields['cert_is_iso'] = $_POST['cert_is_iso'];
            }
			if(isset($_POST['cert_is_safety'])) {
				$fields['cert_is_safety'] = $_POST['cert_is_safety'];
			}
			$fields['cert_last_user'] = $_POST['cert_last_user'];
			$fields['cert_notes'] = $_POST['cert_notes'];  // Because this is a text field we are going to send it via POST
            $fields['cert_points'] = $_POST['cert_points'];

			// var_dump($fields);
			// exit();

			$curl = curl_init($mybaseurl.'/JSON/JSON_ACTION_update_cert.php');
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$json_update = json_decode(curl_exec($curl), true);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ( $status != 200 ) {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Cert was unable to be updated</p>');
				echo('<p style="margin-left:2em;">');
				echo("<strong>Error:</strong> call to update URL failed with status ".$status.", curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				echo('</p>');
				echo('</div>');
			} else {
				if (isset($json_update['success']) && $json_update['success'] == true) {
					echo('<div class="alert alert-dismissable alert-success">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Success:</strong> Cert has been updated</p>');
					echo('</div>');
				} else {
					echo('<div class="alert alert-dismissable alert-danger">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Error:</strong> Cert was unable to be updated</p>');
					if(isset($json_update['error'])) {
						echo('<p style="margin-left:2em;">'.$json_update['error'].'</p>');
					} else {
						echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
					}
					echo('</div>');
				}
			}
			curl_close($curl);
		}
        // query - by cert id
		if(isset($_GET['cert_id']) && strlen($_GET['cert_id']) > 0 && is_numeric($_GET['cert_id'])) {
			$cert_id = $_GET['cert_id'];
			$cert = json_decode(file_get_contents(request_json_api('/JSON/JSON_cert_by_cert_id.php?cert_id='.$cert_id) , false, getContextCookies()), true);
			$is_new_cert = false;
			$cert_name = $cert['cert_name'];
			$cert_description = $cert['cert_description'];
			$cert_days_active = $cert['cert_days_active'];
			$cert_notes = $cert['cert_notes'];
			if($cert['cert_never_expires'] == 1) {
				$cert_never_expires = ' checked';
			} else {
				$cert_never_expires = '';
			}
			if($cert['cert_is_ert'] == 1) {
				$cert_is_ert = ' checked';
			} else {
				$cert_is_ert = '';
			}
            if($cert['cert_is_iso'] == 1) {
                $cert_is_iso = ' checked';
            } else {
                $cert_is_iso = '';
            }
			if($cert['cert_is_safety'] == 1) {
				$cert_is_safety = ' checked';
			} else {
				$cert_is_safety = '';
			}
            $cert_points = $cert['cert_points'];
			$cert_when_set = $cert['cert_when_set'];
			$cert_when_modified = $cert['cert_when_modified'];
			$cert_last_user = $cert['cert_last_user'];
			if($cert['is_active'] == 1) {
				$cert_is_active = true;
			} else {
				$cert_is_active = false;
			}
			$warning = $cert['warning'];
            $tool =$cert['tool'];
			unset($cert);
		} elseif (isset($_POST['new_cert']) && $_POST['new_cert'] == 1) { // Adding new cert
			$fields = array();
			$fields['cert_name'] = $_POST['cert_name'];
			$fields['cert_days_active'] = $_POST['cert_days_active'];
			$fields['cert_description'] = $_POST['cert_description'];
			if(isset($_POST['cert_never_expires'])) {
				$fields['cert_never_expires'] = $_POST['cert_never_expires'];
			}
			if(isset($_POST['cert_is_ert'])) {
				$fields['cert_is_ert'] = $_POST['cert_is_ert'];
			}
            if(isset($_POST['cert_is_iso'])) {
                $fields['cert_is_iso'] = $_POST['cert_is_iso'];
            }
			if(isset($_POST['cert_is_safety'])) {
				$fields['cert_is_safety'] = $_POST['cert_is_safety'];
			}
			$fields['cert_last_user'] = $_POST['cert_last_user'];
			$fields['cert_notes'] = $_POST['cert_notes'];  // Because this is a text field we are going to send it via POST
            $fields['cert_points'] = $_POST['cert_points'];

			$curl = curl_init($mybaseurl.'/JSON/JSON_ACTION_add_cert.php');
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$json_add = json_decode(curl_exec($curl), true);
			$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ( $status != 200 ) {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Cert was unable to be added</p>');
				echo('<p style="margin-left:2em;">');
				echo("<strong>Error:</strong> call to add URL failed with status ".$status.", curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
				echo('</p>');
				echo('</div>');
				$is_new_cert = true;
				$cert_name = '';
				$cert_description = '';
				$cert_days_active = 0;
				$cert_notes = '';//'default warning text goes here.';
				$cert_never_expires = '';
				$cert_is_ert = '';
                $cert_is_iso = '';
				$cert_is_safety = '';
                $cert_points = 0;
			} else {
				if (isset($json_add['success']) && $json_add['success'] == true) {
					echo('<div class="alert alert-dismissable alert-success">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Success:</strong> Cert has been added</p>');
					echo('</div>');
					$is_new_cert = false;
					$cert_id = $json_add['cert_id'];
					$cert_name = $json_add['cert_name'];
					$cert_description = $json_add['cert_description'];
					$cert_days_active = $json_add['cert_days_active'];
					$cert_notes = $json_add['cert_notes'];
					$cert_never_expires = $json_add['cert_never_expires'];
					$cert_is_ert = $json_add['cert_is_ert'];
                    $cert_is_iso = $json_add['cert_is_iso'];
					$cert_is_safety = $json_add['cert_is_safety'];
					$cert_when_set = $json_add['cert_when_set'];
					$cert_when_modified = $json_add['cert_when_modified'];
					$cert_last_user = $json_add['cert_last_user'];
					$cert_is_active = true;
                    $cert_points = $json_add['cert_points'];
				} else {
					echo('<div class="alert alert-dismissable alert-danger">');
					echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
					echo('<p><strong>Error:</strong> Cert was unable to be added</p>');
					if(isset($json_add['error'])) {
						echo('<p style="margin-left:2em;">'.$json_add['error'].'</p>');
					} else {
						echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
					}
					echo('</div>');
					$is_new_cert = true;
					$cert_name = '';
					$cert_description = '';
					$cert_days_active = 0;
					$cert_notes = '';//'default warning text goes here.';
					$cert_never_expires = '';
					$cert_is_ert = '';
                    $cert_is_iso = '';
					$cert_is_safety = '';
                    $cert_points = 0;
				}
			}
			curl_close($curl);
		} else { // cert name
			$is_new_cert = true;
			if(isset($_GET['cert_name'])) {
				$cert_name = $_GET['cert_name'];
			} else {
				$cert_name = '';
			}

			if(isset($_GET['cert_description'])) {
				$cert_description = $_GET['cert_description'];
			} else {
				$cert_description = '';
			}

			if(isset($_GET['cert_days_active'])) {
				$cert_days_active = $_GET['cert_days_active'];
			} else {
				$cert_days_active = '';
			}

			if(isset($_GET['cert_notes'])) {
				$cert_notes = $_GET['cert_notes'];
			} else {
				$cert_notes = '';//'default warning text goes here.';
			}

			if(isset($_GET['cert_never_expires']) && $_GET['cert_never_expires'] == 1) {
				$cert_never_expires = ' checked';
			} else {
				$cert_never_expires = '';
			}

			if(isset($_GET['cert_is_ert']) && $_GET['cert_is_ert'] == 1) {
				$cert_is_ert = ' checked';
			} else {
				$cert_is_ert = '';
			}

            if(isset($_GET['cert_is_iso']) && $_GET['cert_is_iso'] == 1) {
                $cert_is_iso = ' checked';
            } else {
                $cert_is_iso = '';
            }

			if(isset($_GET['cert_is_safety']) && $_GET['cert_is_safety'] == 1) {
				$cert_is_safety = ' checked';
			} else {
				$cert_is_safety = '';
			}

            if(isset($_GET['cert_points'])) {
                $cert_points = intval($_GET['cert_points']);
            } else {
                $cert_points = 0;
            }
		}
        // edit an old cert
		if(isset($_GET['edit']) && $_GET['edit'] == 1) {
			$edit_cert = true;
			$edit_cert_text = '';
		} else {
			$edit_cert = false;
			$edit_cert_text = ' disabled="disabled"';
		}
		// echo('<div class="span-22 prepend-1 append-1 last" style="margin-top:1em;">');

        /*
         * Start of actual content
         */
		if(isset($cert_id) && strlen($cert_id) > 0 && is_numeric($cert_id)) {
			$url = $mybaseurl.'/cert.php?cert_id='.$cert_id;
		} else {
			$url = $mybaseurl.'/cert.php'; // this is the URL used when a new Cert is added
		}
        // main form
		echo('<form name="cert" id="cert" class="form-horizontal" role="form" action="'.$url.'" method="post" onsubmit="return certValidateEdit();">');
		echo('<input name="cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');

		if($is_new_cert) {
			echo('<h3 class="col-lg-offset-2">This is a new Certificate.</h3>');

			echo('<div class="form-group">');
			echo('<label for="cert_name" id="cert_name_label" class="col-md-2 control-label">Cert Name</label>');
			echo('<div class="col-md-6">');
			echo('<input type="text" class="form-control" id="cert_name" name="cert_name" placeholder="Cert Name" maxlength="45" value="'.$cert_name.'"'.$edit_cert_text.'" />');
			echo('</div>');
			echo('</div>');
		} else {
			echo('<input name="edit_cert" type="hidden" value="1" />');
			if($cert_is_active) {
				echo('<h3 class="col-lg-offset-2">This is an active Certificate.</h3>');
			} else {
				echo('<h3 class="col-lg-offset-2"><strong>This is not an active Certificate.</strong></h3>');
			}
			echo('<div class="form-group">');
			echo('<label class="col-lg-2 control-label">Certificate:</label>');
			echo('<div class="col-lg-6">');
			echo('<p class="form-control-static">'.$cert_name.'</p>');
			echo('</div>');
			echo('</div>');
		}
		if($is_new_cert) {
			echo('<input name="new_cert" type="hidden" value="1" />');
		} else {
			echo('<input name="cert_id" type="hidden" value="'.$cert_id.'" />');
		}

		echo('<div class="form-group">');
		echo('<label for="cert_description" id="cert_description_label" class="col-lg-2 control-label">Cert Description:</label>');
		echo('<div class="col-lg-6">');
		echo('<input type="text" class="form-control" id="cert_description" name="cert_description" placeholder="Cert Description" maxlength="200" value="'.$cert_description.'"'.$edit_cert_text.' />');
		echo('</div>');
		echo('</div>');

		echo('<div class="form-group">');
		echo('<div class="col-lg-offset-2 col-lg-6">');
		echo('<div class="checkbox">');
		echo('<label>');
		echo('<input id="cert_never_expires" onClick="neverExpires();" name="cert_never_expires" type="checkbox"'.$cert_never_expires.$edit_cert_text.' /> ');
		echo('Cert Never Expires');
		echo('</label>');
		echo('</div>');
		echo('</div>');
		echo('</div>');

		echo('<div class="form-group" id="div_tag_cert_days_active"');
		if(strlen($cert_never_expires) > 0){
			echo(' style="display: none;"');
		}
		echo('>');
		echo('<label for="cert_days_active" id="cert_description_label" class="col-lg-2 control-label">Cert Days Active:</label>');
		echo('<div class="col-lg-6">');
		echo('<input type="text" class="form-control" id="cert_days_active" name="cert_days_active" placeholder="Cert Days Active" maxlength="6" value="'.$cert_days_active.'"'.$edit_cert_text.' />');
		echo('</div>');
		echo('</div>');

		echo('<div class="form-group">');
		echo('<div class="col-lg-offset-2 col-lg-6">');
		echo('<div class="checkbox">');
		echo('<label>');
		echo('<input id="cert_is_ert" name="cert_is_ert" type="checkbox"'.$cert_is_ert.$edit_cert_text.' /> ');
		echo('Cert is ERT');
		echo('</label>');
		echo('</div>');
		echo('</div>');
		echo('</div>');

        echo('<div class="form-group">');
        echo('<div class="col-lg-offset-2 col-lg-6">');
        echo('<div class="checkbox">');
        echo('<label>');
        echo('<input id="cert_is_iso" name="cert_is_iso" type="checkbox"'.$cert_is_iso.$edit_cert_text.' /> ');
        echo('Cert is ISO');
        echo('</label>');
        echo('</div>');
        echo('</div>');
        echo('</div>');

		echo('<div class="form-group">');
		echo('<div class="col-lg-offset-2 col-lg-6">');
		echo('<div class="checkbox">');
		echo('<label>');
		echo('<input id="cert_is_safety" name="cert_is_safety" type="checkbox"'.$cert_is_safety.$edit_cert_text.' /> ');
		echo('Cert is Safety');
		echo('</label>');
		echo('</div>');
		echo('</div>');
		echo('</div>');

		echo('<div class="form-group">');
		echo('<label for="cert_notes" id="cert_notes_label" class="col-lg-2 control-label">E-mail Notes:</label>');
		echo('<div class="col-lg-6">');
		echo('<textarea class="form-control" rows="3" id="cert_notes" name="cert_notes" placeholder="Cert Notes."'.$edit_cert_text.'>');
		echo($cert_notes);
		echo('</textarea>');
		echo('</div>');
		echo('</div>');

        echo('<div class="form-group" id="div_tag_cert_points">');
        echo('<label for="cert_points" id="cert_points_label" class="col-lg-2 control-label">Cert Points:</label>');
        echo('<div class="col-lg-6">');
        echo('<input type="text" class="form-control" id="cert_points" name="cert_points" placeholder="0" maxlength="3" value="'.$cert_points.'"'.$edit_cert_text.' />');
        echo('</div>');
        echo('</div>');

		if(!$is_new_cert) {
			echo('<div class="form-group">');
			echo('<label class="col-lg-2 control-label">Cert Set:</label>');
			echo('<div class="col-lg-6">');
            echo('<p class="form-control-static">'.date('Y-m-d H:i:s', $cert_when_set).'</p>');
			echo('</div>');
			echo('</div>');

			echo('<div class="form-group">');
			echo('<label class="col-lg-2 control-label">Cert Last Modified:</label>');
			echo('<div class="col-lg-6">');
			echo('<p class="form-control-static">');
            echo(date('Y-m-d H:i:s', $cert_when_modified));
			echo(' - (by ');
			echo($cert_last_user);
			echo(')');

			echo('</p>');
			echo('</div>');
			echo('</div>');
		}

		echo('<div class="col-lg-offset-2 btn-toolbar">');

		if($is_new_cert) {
			echo('<button type="submit" class="btn btn-primary btn-sm btn-group hidden-print">Add Certificate</button>');
		} elseif($edit_cert) {
			echo('<button type="submit" class="btn btn-info btn-sm btn-group hidden-print">Update Certificate</button>');
		}

		echo('</form>'); // End of main form

		if(!$is_new_cert) {
			if(!$edit_cert) {
				echo('<form name="edit_cert" id="edit_cert" action="'.$mybaseurl.'/cert.php?cert_id='.$cert_id.'&edit=1" method="get">');
				echo('<input name="edit" type="hidden" value="1" />');
				echo('<input name="cert_id" type="hidden" value="'.$cert_id.'" />');
				echo('<button type="submit" style="margin-left:5px;" class="btn btn-primary btn-sm btn-group hidden-print">Enable Editing</button>');
				echo('</form>');
			} else {
                // disable cert
				echo('<form name="delete_cert_form" id="delete_cert_form" action="'.$mybaseurl.'/all_certs.php" method="post" onsubmit="return certValidateDelete();">');
				echo('<input name="cert_id" type="hidden" value="'.$cert_id.'" />');
				if($cert_is_active) {
					echo('<input name="delete_cert" id="delete_cert" type="hidden" value="0" />'); // this becomes the value of is_active in the cert table
                    echo('<button type="submit" style="margin-left:5px;" class="btn btn-warning btn-sm btn-group hidden-print">Disable Certificate</button>');
				} else {
					echo('<input name="delete_cert" id="delete_cert" type="hidden" value="1" />'); // this becomes the value of is_active in the cert table
                    echo('<button type="submit" style="margin-left:5px;" class="btn btn-warning btn-sm btn-group hidden-print">Reactivate Certificate</button>');
				}
				echo('<input name="cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');
				echo('</form>');
                // delete cert
                echo('<form name="true_delete_cert_form" id="true_delete_cert_form" action="'.$mybaseurl.'/all_certs.php" method="post" onsubmit="return certValidateTrueDelete();">');
                echo('<input name="cert_id" type="hidden" value="'.$cert_id.'" />');
                echo('<input name="true_delete_cert" id="true_delete_cert" type="hidden" value="1" />'); // this becomes the value of is_active in the cert table
                echo('<button type="submit" style="margin-left:5px;" class="btn btn-danger btn-sm btn-group hidden-print">Delete Certificate</button>');
                echo('<input name="cert_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');
                echo('</form>');
			}
		}
		echo('</div>');
        /*
         * End of main form begining extra content
         */
		echo('<hr>');
        // show tool
        if(isset($tool) && count($tool) > 0 && $tool[0] != null) {  // tools go here
            echo('<div class=row>');
            echo('<div class="col-lg-offset-2"><h3>Tool List</h3></div>');
            echo('</div>');
            echo('<div class=row>');
            echo('<label class="col-lg-2 control-label">Mapping Tools:</label>');
            echo('<ul class="col-lg-6 list-group">');
            foreach($tool[0] as $key => $value) {
                echo('<li style="margin-left:15px;" class="list-group-item">');
                echo('<form class="form-horizontal" name="delete_tool_form" action="'.$url.'" method="post">');
                echo($value);
                echo("&nbsp;&nbsp");
                if($edit_cert) {
                    echo('<input name="delete_tool" type="hidden" value="0" />'); // this becomes the value of is_active in the warning table
                    echo('<input name="cert_id" type="hidden" value="'.$cert_id.'" />');
                    echo('<input name="tool_name" type="hidden" value="'.$value.'" />');
                    echo('<input name="tool_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');
                    echo('<button type="submit" class="btn btn-danger btn-xs btn-group hidden-print" title="Delete '.$value.' "><span class="glyphicon glyphicon-remove"></span></button>');
                }
                echo('</form>');
                echo('</li>');
            }
            echo('</ul>');
            echo('</div>');
        }
        // show warning
		if(isset($warning) && count($warning) > 0) {  // Warnings go here
			echo('<div class=row>');
			echo('<div class="col-lg-offset-2"><h3>Warnings</h3></div>');
			echo('</div>');
			echo('<div class=row>');
			echo('<label class="col-lg-2 control-label">Send warning when:</label>');
			echo('<ul class="col-lg-6 list-group">');
			foreach($warning as $value) {
				echo('<li style="margin-left:15px;" class="list-group-item">');
				echo('<form class="form-horizontal" name="delete_warning_form" action="'.$url.'" method="post">');
				echo($value['warning_number_of_days']);
				echo(' days to go ');
				if($edit_cert) {
					echo('<input name="delete_warning" type="hidden" value="0" />'); // this becomes the value of is_active in the warning table
					echo('<input name="warning_id" type="hidden" value="'.$value['warning_id'].'" />');
					echo('<input name="warning_last_user" type="hidden" value="'.$user['user_samaccountname'].'" />');
					echo('<button type="submit" class="btn btn-danger btn-xs btn-group hidden-print" title="Delete '.$value['warning_number_of_days'].' days to go"><span class="glyphicon glyphicon-remove"></span></button>');
				}
				echo('</form>');
				echo('</li>');
			}
			echo('</ul>');
			echo('</div>');
		}

        if ($GLOBALS['DB_TYPE'] == 'pgsql') {
            if(!$is_new_cert && $edit_cert) {
                echo('<form class="form-horizontal" name="add_tool_form" id="add_tool_form" action="' . $url . '" method="post" onsubmit="return toolValidate();">');
                echo('<div class="form-group">');
                echo('<label class="col-lg-2 control-label">New Tool:</label>');
                echo('<div class="col-lg-6">');
                echo('<div class="input-group">');
                echo('<input type="text" style="text-transform: uppercase;" class="form-control" id="temp_new_tool" name="temp_new_tool" placeholder="New tools not in the list" maxlength="200" value="">');

//                echo('<input class="form-control" id="tool_name" name="tool_name" placeholder="Enter Tool Name" type="text" />');
                // use combobox instead
                $tool_list = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_tools.php'), false, getContextCookies()), true);
                echo('<select multiple class="form-control" id="tool_name" name="tool_name">');
//                echo('<optgroup label="Tools"');
                foreach ($tool_list as $k => $v) {
                    echo('<option value="' . $k . '">');
                    echo($k);
                    echo('</option>');
                }
//                echo('</optgroup>');
                echo('</select>');

                echo('<span class="input-group-btn">');
                echo('<button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-plus text-success"></span> Add Tool</button>');
                echo('</span>');
                echo('</div>');
                echo('<input name="add_tool" type="hidden" value="1" />');
                echo('<input name="selected_Tools" id="selected_Tools" type="hidden" value="" />');
                echo('<input name="cert_id" type="hidden" value="' . $cert_id . '" />');
                echo('<input name="tool_last_user" type="hidden" value="' . $user['user_samaccountname'] . '" />');
                echo('</div>');
                echo('</div>');
                echo('</form>');
            }
        } else {
            if(!$is_new_cert && $edit_cert && !$cert_never_expires) { // its not a new cert (no cert_id) and the page is set to edit mode
                echo('<form class="form-horizontal" name="add_warning_form" id="add_warning_form" action="' . $url . '" method="post" onsubmit="return warningValidate();">');
                echo('<div class="form-group">');
                echo('<label class="col-lg-2 control-label">New Warning:</label>');
                echo('<div class="col-lg-6">');
                echo('<div class="input-group">');
                echo('<input class="form-control" id="warning_number_of_days" name="warning_number_of_days" placeholder="Number of Days" type="text" maxlength="5" />');
                echo('<span class="input-group-btn">');
                echo('<button class="btn btn-default" type="submit"><span class="glyphicon glyphicon-plus text-success"></span> Add Warning</button>');
                echo('</span>');
                echo('</div>');
                echo('<input name="add_warning" type="hidden" value="1" />');
                echo('<input name="cert_id" type="hidden" value="' . $cert_id . '" />');
                echo('<input name="warning_last_user" type="hidden" value="' . $user['user_samaccountname'] . '" />');
                echo('</div>');
                echo('</div>');
                echo('</form>');
            }
        }

		echo('<p>&nbsp;</p>');
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}
	// echo('</div>');
	include_once('footer.php');
?>
