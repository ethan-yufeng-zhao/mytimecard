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

        $apiUrl = '/JSON/JSON_rawdata.php?user_id=' . urlencode($requested_user['user_id'])
                . '&mode=' . urlencode($_GET['mode'] ?? 'balanced')
                . '&start=' . urlencode($_GET['start'] ?? date('Y-m-01'))
                . '&end=' . urlencode($_GET['end'] ?? date('Y-m-d'));

        $json = json_decode(file_get_contents(request_json_api($apiUrl), false, getContextCookies()), true);

        $meta = $json[$requested_user['user_id']]['meta'] ?? null;
        $rawdata = $json[$requested_user['user_id']]['rawdata'] ?? null;
        $vacations = $json[$requested_user['user_id']]['vacation'] ?? null;
        $daydata = $json[$requested_user['user_id']]['data'] ?? null;
        $noshow = $json[$requested_user['user_id']]['NoShow'] ?? null;
        $summary = $json[$requested_user['user_id']]['summary'] ?? null;

        /**
         * Begining of content -- certs
         */
        echo("<div id='jfabtable'>");
		echo('<table style="width:100%; border-collapse:collapse; text-align:left;"><tr><td style="width:18%; border:0px solid #ccc; padding:6px;">');
		echo('User: <a href="mailto:'.$requested_user['user_email'].'?subject='.$current_url.'?uid='.$requested_user['user_id'].'">');
		echo($requested_user['user_firstname'].' '.$requested_user['user_lastname']);
		echo('</a></td>');
        echo('<td style="width:18%; border:0px solid #ccc; padding:6px;">Type:&nbsp;'.($meta['employeetype'] ?? '')."</td>");
        echo('<td style="width:18%; border:0px solid #ccc; padding:6px;">Shift:&nbsp;'.'<span class="data-shifttype">'.(htmlspecialchars($meta['shifttype'] ?? '')).'</span>'."</td>");
		if(!empty($requested_user['user_supervisor_id'])){
			echo('<td style="width:18%; border:0px solid #ccc; padding:6px;">');
//            echo('Supervisor: <a href="mailto:'.$requested_user['user_supervisor_id'].'?subject='.$current_url.'">');
			echo('Supervisor:');
            if($user['user_is_admin'] || $user['user_is_supervisor']) {
                echo('<a href="' . $mybaseurl . '/index.php?uid=' . $requested_user['user_supervisor_id'] . '">');
            }
			echo($requested_user_supervisor['user_firstname'].' '.$requested_user_supervisor['user_lastname']);
            if($user['user_is_admin'] || $user['user_is_supervisor']) {
                echo('</a>');
            }
			echo('</td>');
		} else {
            echo('<td style="width:18%; border:0px solid #ccc; padding:6px;">&nbsp;</td>');
        }
		echo('<td style="width:18%; border:0px solid #ccc; padding:6px;">');
		echo('Time: '.date('Y-m-d H:i:s'));
		echo('</td>');
//		echo('</tr></table><p>');

//		if($user['user_is_admin']) { // || ($authorized && $user['user_id'] != $requested_user['user_id'])) {
//			echo('<a data-toggle="modal" href="#modal_cert_picker_data" class="btn btn-primary btn-sm hidden-print">Report missing data for '.$requested_user['user_samaccountname'].' </a>&nbsp;&nbsp;');
//		}
        echo('<td style="width:10%; border:0px solid #ccc; padding:6px; text-align: right">');
        echo("<a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></td></tr></table>");
        echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
        echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
        echo("<input type='hidden' id='filename' name='filename' value='MyTimecard_".$requested_user['user_firstname'].'.'.$requested_user['user_lastname'].'_'.date('Ymd').".xls'>");
        echo("</form>");

		$certs = array();

		if($daydata && $summary) {
			echo("<table class='table_col_2_with_labels'>");
			echo("<thead>");
			echo("<tr>");

            echo("<th>");
            echo("Day of Month");
            echo("</th>");

            echo("<th>");
            echo("What day");
            echo("</th>");

			echo("<th>");
			echo("Time on Site");
			echo("</th>");

			echo("<th>");
			echo("Time in Building");
			echo("</th>");

            echo("<th>");
            echo("Time out of Building");
            echo("</th>");

            echo("<th>");
            echo("Time in Fab");
            echo("</th>");

            echo("<th>");
            echo("Time in Subfab");
            echo("</th>");

            echo("<th>");
            echo("Time in Facilities");
            echo("</th>");

            echo("<th>");
            echo("Vacation");
            echo("</th>");

            echo("<th>");
            echo("Total Hours");
            echo("</th>");

//            echo("<th>");
//            echo("Missing Exit Flag");
//            echo("</th>");
//
//            echo("<th>");
//            echo("Missing Entry Flag");
//            echo("</th>");

			echo("</tr>\n");
			echo("</thead>");

			echo("<tbody>");

            foreach ($daydata as $day => $value) {
                $inWorkdays = in_array($day, $summary['workdaysList']);
                if ($inWorkdays) {
                    echo('<tr>');
                } else {
                    echo('<tr style="color:mediumorchid;">');
                }

                echo("<td>");
                echo('<span class="day-of-month">' . htmlspecialchars($day) . '</span>');
                echo("</td>");

                echo("<td>");
                echo(date('D', strtotime($day)));
                echo("</td>");

				echo("<td>");
				echo($value['tos'] ?? 0);
				echo('</td>');

                echo("<td>");
                echo($value['tib'] ?? 0);
                echo('</td>');

                echo("<td>");
                echo($value['tob'] ?? 0);
                echo('</td>');

                echo("<td>");
                echo($value['tif'] ?? 0);
                echo('</td>');

                echo("<td>");
                echo($value['tisf'] ?? 0);
                echo('</td>');

                echo("<td>");
                echo($value['tifac'] ?? 0);
                echo('</td>');

                echo("<td>");
                echo('<span class="vacation-value">' . htmlspecialchars($value['vacation'] ?? 0) . '</span>&nbsp;&nbsp;');
                if ($user['user_is_admin'] || $user['user_is_supervisor']) {
                    if ($inWorkdays) {
                        echo('<span class="edit-vacation-icon glyphicon glyphicon-pencil text-primary" style="cursor: pointer;" data-vacation="'.$value['vacation'].'"></span>&nbsp;&nbsp;');
                    }
                }
                echo("</td>");

                echo("<td style='display: flex; justify-content: space-between; align-items: center;'>");
                echo('<span style="text-align: left;">' . ($value['subtotal'] ?? 0) . '</span>');
//                if ($user['user_is_admin'] || $user['user_is_supervisor']) {
                    if ($value['subtotal'] > 0) {
                        echo('<span class="view-history-icon glyphicon glyphicon-list-alt text-secondary" style="cursor: pointer;" title="View/Edit Badging His" data-day_of_month="' . htmlspecialchars($day) . '"></span>');
                    }
//                }
                echo('</td>');

//                echo("<td>");
//                echo($value['missingexitflag'] ?? 0);
//                echo('</td>');
//
//                echo("<td>");
//                echo($value['missingentryflag'] ?? 0);
//                echo('</td>');

				echo('</tr>');
			}
            if ($noshow) {
                foreach ($noshow as $day => $value) {
                    echo('<tr style="color:red;">');

                    echo("<td>");
                    echo('<span class="day-of-month">' . htmlspecialchars($day) . '</span>');
                    echo("</td>");

                    echo("<td>");
                    echo(date('D', strtotime($day)));
                    echo("</td>");

                    echo("<td>");
                    echo($value['tos'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo($value['tib'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo($value['tob'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo($value['tif'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo($value['tisf'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo($value['tifac'] ?? 'No Show');
                    echo('</td>');

                    echo("<td>");
                    echo('<span class="vacation-value">' . htmlspecialchars($value['vacation'] ?? 0) . '</span>&nbsp;&nbsp;');
                    if ($user['user_is_admin'] || $user['user_is_supervisor']) {
                        if ($inWorkdays) {
                            echo('<span class="edit-vacation-icon glyphicon glyphicon-pencil text-primary" style="cursor: pointer;" data-vacation="'.$value['vacation'].'"></span>&nbsp;&nbsp;');
                        }
                    }
                    echo("</td>");

                    echo("<td>");
                    echo($value['subtotal'] ?? 0);
                    echo('</td>');

                    //                echo("<td>");
                    //                echo($value['missingexitflag'] ?? 0);
                    //                echo('</td>');
                    //
                    //                echo("<td>");
                    //                echo($value['missingentryflag'] ?? 0);
                    //                echo('</td>');

                    echo('</tr>');
                }
            }
			echo("</tbody>");
            echo("</tfoot>");
            echo("<tr><th>Total:</th><th>".$summary['actual_workdays']."</th><th>".$summary['total_tos']."</th><th>".$summary['total_tib']."</th><th>".$summary['total_tob']."</th>");
            echo("<th>".$summary['total_tif']."</th><th>".$summary['total_tisf']."</th><th>".$summary['total_tifac']."</th><th>".$summary['total_vacation']."</th><th>".$summary['total_hours']."</th></tr>");
            echo("<tr><th>Average:</th><th>".$summary['workdays']."</th><th>".$summary['avg_tos']."</th><th>".$summary['avg_tib']."</th><th>".$summary['avg_tob']."</th>");
            echo("<th>".$summary['avg_tif']."</th><th>".$summary['avg_tisf']."</th><th>".$summary['avg_tifac']."</th><th>".$summary['avg_vacation']."</th><th>".$summary['avg_hours']."</th></tr>");
            echo("</tfoot>");
			echo("</table>\n");
            echo("</div>\n");
		} else {
			echo('<div class="alert alert-danger">');
			echo('<p>No certifications assigned to: "'.$requested_user['user_samaccountname'].'"</p>');
			echo('</div>');
		}

//        /**
//         * Bootstrap Modals
//         */
//		echo("\n");
//		echo('<div class="modal none" id="modal_cert_picker_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="font-size:80%">');
//		echo('<div class="modal-dialog wider-modal">');
//		echo('<div class="modal-content">');
//		echo('<div class="modal-header">');
//		echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
//		echo('<h4 class="modal-title">Please click on a Cert Name to select it</h4>');
//		echo('</div>');
//		echo('<div class="modal-body">');
//		echo("<table class='tablesorter'>");
//		echo("<thead>");
//		echo('<tr>');
//
//        echo('<th>');
//        echo('No.');
//        echo('</th>');
//
//		echo('<th>');
//		echo('Cert Name');
//		echo('</th>');
//
//		echo('<th>');
//		echo('Description');
//		echo('</th>');
//
//		echo('<th>');
//		echo('Expires');
//		echo('</th>');
//
//        echo('<th>');
//        echo('Pts');
//        echo('</th>');
//
//		echo('</tr>');
//		echo("</thead>");
//		echo("<tbody>");
//		$json_all_certs = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php') , false, getContextCookies()), true);
//        $count_cert = 0;
//		foreach ($json_all_certs as $key => $value) {
//			echo('<tr>');
//
//            echo('<td>');
//            echo(++$count_cert);
//            echo('</td>');
//
//			echo('<td>');
//			echo('<a href="javascript:void(0);" onclick="chooseCert('.$key.', \''.$value['cert_name'].'\');">'.$value['cert_name'].'</a>');
//			echo('</td>');
//
//			echo('<td>');
//			echo($value['cert_description']);
//			echo('</td>');
//
//			echo('<td>');
//			if($value['cert_never_expires'] == 1) {
//				echo('Never');
//			} else {
//				echo($value['cert_days_active'].' Days');
//			}
//			echo('</td>');
//
//            echo('<td>');
//            echo($value['cert_points']);
//            echo('</td>');
//
//			echo('</tr>');
//		}
//		echo("</tbody>");
//		echo('</table>');
//		echo('</div>');
//		echo('<div class="modal-footer">');
//		echo('<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>');
//		echo('</div>');
//		echo('</div>'); // end of modal-content
//		echo('</div>'); // end of modal-dialog
//		echo('</div>'); // end of modal

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
        echo('<h5 class="modal-title">Edit Vacation</h5>');
        echo('</div>');
        echo('<div class="modal-body" >');
        echo('<label for="edit-vacation-input">Vacation[0, 8]:&nbsp;</label>');
        echo('<input type="text" id="edit-vacation-input" min="0" max="8" step="1" value=4 autocomplete="off" style="width:100%; max-width:140px;" />');
        echo('<br><label for="edit-comments">Comments:</label>');
        echo('<textarea id="edit-comments" rows="3" style="width:100%;" placeholder="Update Vacation. Do not use \'. This can be empty."></textarea>');
        echo('</div>');
        echo('<div class="modal-footer">');
        echo('<button type="button" id="cancel-edit-btn" class="btn btn-secondary">Cancel</button>');
        echo('<button type="button" id="save-edit-btn" class="btn btn-primary">Save</button>');
        echo('</div>');
        echo('</div>');
        echo('</div>');
        echo('</div>');


        echo('<div class="modal fade" id="history-dialog" tabindex="-1" role="dialog">');
        echo('<div class="modal-dialog modal-lg wider-modal">');  // modal-lg for bigger size
        echo('<div class="modal-content">');

        echo('<div class="modal-header bg-secondary text-white d-flex align-items-center justify-content-between">');
        echo('<h4 class="modal-title mb-0">'.$requested_user['user_samaccountname'].'</h4>');
//        echo('<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="font-size:2rem; line-height:1;">');
//        echo('<span aria-hidden="true">&times;</span>');
//        echo('</button>');
        echo('</div>'); // modal-header

        echo('<div class="modal-body">');
        echo('<form id="history-form" method="post" action="update_badge_records.php">');
        echo('<input type="hidden" name="day" id="history-day">');
        echo('<div id="history-table-container"></div>');
        echo('</form>');
        echo('</div>'); // modal-body

        echo('<div class="modal-footer">');
//        echo('<button type="submit" form="history-form" class="btn btn-primary" id="save-btn" disabled>Save Changes</button>');
        echo('<button type="button" id="close-history-btn" class="btn btn-secondary" data-dismiss="modal">Close</button>');
        echo('</div>'); // modal-footer

        echo('</div>'); // modal-content
        echo('</div>'); // modal-dialog
        echo('</div>'); // modal


//        echo('<div id="history-dialog" class="modal">');
//        echo('<div class="modal-dialog modal-lg" style="width: 60%">');
//        echo('<div class="modal-content">');
//        echo('<div class="modal-header">');
//        echo("<h4 class='modal-title'>Change History </h4>");
////        echo('<button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>');
//        echo('</div>');
//        echo('<div class="modal-body">');
//        echo('<p>Loading history...</p>');
//        echo('</div>');
//        echo('<div class="modal-footer">');
//        echo('<button type="button" id="close-history-btn">Close</button>');
//        echo('</div>');
//        echo('</div>');
//        echo('</div>');
//        echo('</div>');

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

    const rawdata = <?php echo json_encode($rawdata); ?>;
    document.querySelectorAll('.view-history-icon').forEach(icon => {
        icon.addEventListener('click', function () {
            const dayOfMonth = this.getAttribute('data-day_of_month');
            const shiftType = this.getAttribute('data-shifttype');
            const adAccount = "<?php echo $requested_user['user_samaccountname']; ?>";
            const modifiedUser = "<?php echo $user['user_samaccountname']; ?>";
            // console.log(dayOfMonth + " " + adAccount);
            if (!dayOfMonth || !adAccount) {
                alert('Day of month or AD account is missing.');
                return;
            }

            const entries = rawdata[dayOfMonth] || [];
            document.getElementById('history-day').value = dayOfMonth;

            let tableHtml = `
            <table class="table table-bordered table-sm history-rawdata-table">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Source Name</th>
                        <th>Source Type</th>
                        <th>In & Out Time</th>
                        <th>Assumed?</th>
                    </tr>
                </thead>
                <tbody>
        `;

            entries.forEach((entry, idx) => {
                const combinedSource = `${entry.sourcename || ''}`;
                const trxDate = new Date(entry.trx_timestamp || '');
                const dayStart = new Date(dayOfMonth + 'T00:00:00');
                const dayEnd = new Date(dayOfMonth + 'T23:59:59');

                const isOvernight = trxDate < dayStart || trxDate > dayEnd;
                const assumedValue = entry.assumed_id ?? entry.assumed ?? idx;
                const isAssumed = (assumedValue === true || assumedValue === 'true' || assumedValue === 1 || assumedValue === '1');
                //console.log(idx, assumedValue, isAssumed);
                const isDayShift = (shiftType === 'Days');

                // Decide row class (no inline style)
                let rowClass = '';
                if (isAssumed) {
                    rowClass = 'assumed-row';
                } else if (isOvernight && isDayShift) {
                    rowClass = 'overnight-row';
                }

                tableHtml += `
                <tr class="${rowClass}">
                    <td>${idx + 1}</td>
                    <td>
                        <input type="text" readonly value="${(combinedSource).replace(/"/g,'&quot;')}" class="form-control form-control-sm">
                        <input type="hidden" name="sourcename[]" value="${(combinedSource).replace(/"/g,'&quot;')}">
                    </td>
                    <td>
                        <input type="text" readonly value="${(entry.normalizedname || '').replace(/"/g,'&quot;')}" class="form-control form-control-sm">
                        <input type="hidden" name="normalizedname[]" value="${(entry.normalizedname || '').replace(/"/g,'&quot;')}">
                    </td>
                    <td>
                        <input type="text" readonly value="${(entry.trx_timestamp || '').replace(/"/g,'&quot;')}" class="form-control form-control-sm">
                        <input type="hidden" name="inandout[]" value="${(entry.trx_timestamp || '').replace(/"/g,'&quot;')}">
                    </td>
                    <td class="text-center">
                        ${isAssumed ? `<input type="checkbox" name="assumed_ids[]" value="${assumedValue}" checked disabled>` : ''}
                    </td>
                </tr>
            `;
            });

            tableHtml += `</tbody></table>`;
            document.getElementById('history-table-container').innerHTML = tableHtml;

            $('#history-dialog').modal('show');
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
            const vacationValue = parseFloat(this.getAttribute('data-vacation'));
            const inputField = document.getElementById('edit-vacation-input');
            inputField.value = vacationValue.toFixed(0); // Set the current value in the input field

            const commentsField = document.getElementById('edit-comments');
            commentsField.value = ''; // Clear the comments field

            const saveButton = document.getElementById('save-edit-btn');
            saveButton.disabled = false;//vacationValue < 1 || vacationValue > 8;

            const dialog = document.getElementById('edit-dialog');
            dialog.style.display = 'block'; // Show the modal
        });
    });

    // Enable/Disable Save button based on comments input
    document.getElementById('edit-comments').addEventListener('input', function () {
        const commentsValue = this.value.trim();
        const saveButton = document.getElementById('save-edit-btn');
        saveButton.disabled = false; // Disable if comments are empty
    });

    // Save changes when the Save button is clicked
    document.getElementById('save-edit-btn').addEventListener('click', function () {
        const inputField = document.getElementById('edit-vacation-input');
        const commentsField = document.getElementById('edit-comments');
        const newVacation = parseFloat(inputField.value);
        const modifiedComments = commentsField.value.trim();

        // Validate the vacation range
        if (isNaN(newVacation) || newVacation < 0 || newVacation > 8) {
            alert('Vacation must be a number between 0 and 8');
            return;
        }

        const adAccount = "<?php echo $requested_user['user_samaccountname']; ?>";
        const modifiedUser = "<?php echo $user['user_samaccountname']; ?>";
        const currentRow = currentIcon.closest('tr'); // Find the parent row
        const day_of_month = currentRow.querySelector('.day-of-month')?.textContent.trim();

        console.log(adAccount);
        console.log('Day of Month:',day_of_month);

        console.log(newVacation);
        console.log(modifiedUser);
        console.log(modifiedComments);

        if (!day_of_month || !adAccount) {
            alert('Day of Month | AD Account is missing.');
            return;
        }

        // Send the updated vacation value and comments to the server
        fetch('JSON/JSON_ACTION_update_vacation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                ad_account: adAccount,
                day_of_month: day_of_month,
                vacation: newVacation.toFixed(0),
                modified_user: modifiedUser,
                modified_comments: modifiedComments
            }),
        }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the vacation value in the table
                    const vacationSpan = currentIcon.previousElementSibling;
                    if (vacationSpan) {
                        vacationSpan.textContent = newVacation.toFixed(0); // Update the displayed vacation value
                        currentIcon.setAttribute('data-vacation', newVacation.toFixed(0)); // Update the icon's data attribute
                    }

                    // Update the points-by-cert value
                    // const pointsByCertCell = currentIcon.closest('td').nextElementSibling.querySelector('.points-by-cert');
                    // if (!isNaN(certPoints) && pointsByCertCell) {
                    //     pointsByCertCell.textContent = (certPoints * newVacation).toFixed(0);
                    // }

                    // Recalculate the total points
                    // recalculateTotalPoints();

                    // Hide the modal
                    const dialog = document.getElementById('edit-dialog');
                    dialog.style.display = 'none';

                    alert('Vacation updated successfully.');
                    window.location.reload();
                } else {
                    alert(`Error: ${data.message}`);
                }
            }).catch(error => {
            console.error('Error:', error);
            alert('Failed to update vacation. Please try again.');
        });
    });

    // Hide the modal when the Cancel button is clicked
    document.getElementById('cancel-edit-btn').addEventListener('click', function () {
        const dialog = document.getElementById('edit-dialog');
        dialog.style.display = 'none';
    });

    // Function to recalculate total points and update the table footer
    // function recalculateTotalPoints() {
    //     let totalPoints = 0;
    //     document.querySelectorAll('.points-by-cert').forEach(cell => {
    //         const value = parseFloat(cell.textContent);
    //         if (!isNaN(value)) {
    //             totalPoints += value;
    //         }
    //     });
    //
    //     // Update the total points in the footer
    //     const totalPointsCell = document.querySelector('#total-points');
    //     if (totalPointsCell) {
    //         totalPointsCell.textContent = totalPoints.toFixed(2);
    //     }
    // }
</script>


<?php
	include_once('footer.php');
?>