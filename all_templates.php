<?php
	// all_templates.php
	include_once('header.php');  // has everything up to the container div in the body
?>

<script type="text/javascript">
    $(document).ready(function() {
        $(".table_col_0_with_labels").tablesorter({
            theme : "bootstrap",
            widthFixed: true,
            headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
            widgets : [ "uitheme", "filter" ],
            widgetOptions : {
                filter_reset : ".reset"
            },
            headers: {
                0: {
                    sorter:'ignore_labels'
                }
            }
        });
    });

    function certValidateCreateTemplate(){ // This function is for adding or deleting
        if($('#template_name').val().length < 1) {
            alert('The Template Name must be set.');
            $("#template_name").focus();
            return false;
        }
        if($('#template_name').val().length < 3) {
            alert('The Template Name must be at least 3 characters.');
            $("#template_name").focus();
            return false;
        }
        if(confirm("Create the \""+$('#template_name').val()+"\" Template?")) {
            return true;
        } else {
            return false;
        }
    }

    function removeTemplate(remove_template_id, remove_template_last_user, remove_template_name) {
        if(confirm("Template Name: "+remove_template_name+"\nUser Name: "+remove_template_last_user+"\n\nRemove this Template?")) {
            $("#remove_template_id").val(remove_template_id);
            // $("#remove_template_last_user").val(remove_template_last_user);
            $('#remove_template_form').submit();
        }
    }

    function editTemplateName(edit_template_id, edit_template_name) {
        // alert(edit_template_id);
        $("#edit_template_id").val(edit_template_id);
        $("#edit_template_name").val(edit_template_name);
        $('#modal_template_edit_name_data').modal('show');
        $("#edit_template_name").focus();
    }

    function certValidateNewTemplateName(){ // This function is for adding or deleting
        if($('#edit_template_name').val().length < 1) {
            alert('The Template Name must be set.');
            $("#edit_template_name").focus();
            return false;
        }
        if($('#edit_template_name').val().length < 3) {
            alert('The Template Name must be at least 3 characters.');
            $("#edit_template_name").focus();
            return false;
        }
        if(confirm("Change the Template Name?")) {
            return true;
        } else {
            return false;
        }
    }
</script>

<?php
	$authorized = false;
	if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
		$authorized = true;
	}

	echo("<div id='jfabtable'>\n");
	echo('<table><tr>');
	echo('<td><h2 style="margin:0px;">');

	echo('All Templates');
	echo('</h2></td></tr>');
	echo('<tr><td>');
	echo(date('Y-m-d H:i:s'));
	echo('</td>');
	echo('</tr></table>');

	if($authorized) {
		echo('<p><a data-toggle="modal" href="#modal_template_creator_data" class="btn btn-primary btn-sm hidden-print">Create a new Template</a></p>');

		if(isset($_POST['template_name']) && strlen($_POST['template_name']) > 2 && isset($_POST['template_last_user']) && strlen($_POST['template_last_user']) > 0) { // adding a new template
			$json_add_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_template.php?'.http_build_query($_POST)), false, getContextCookies()), true);
			if (isset($json_add_template['success']) && $json_add_template['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<strong>Success:</strong> New template has been added');
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Template was unable to be added</p>');
				if(isset($json_add_warning['error'])) {
					echo('<p style="margin-left:2em;">'.$json_add_template['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
		}



		if(isset($_POST['remove_template_id']) && strlen($_POST['remove_template_id']) > 0 && isset($_POST['remove_template_last_user']) && strlen($_POST['remove_template_last_user']) > 0) { // Do a delete from the database
			$json_remove_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_template.php?'.http_build_query($_POST)),false, getContextCookies()), true);
			if (isset($json_remove_template['success']) && $json_remove_template['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<strong>Success:</strong> '.$json_remove_template['message']);
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Unable to remove template');
				if(isset($json_remove_template['error'])) {
					echo('<p style="margin-left:2em;">'.$json_remove_template['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
			unset($json_remove_template);
		}

        /**
         * Begining of content
         */
		if(isset($_POST['edit_template_id']) && strlen($_POST['edit_template_id']) > 0 && is_numeric($_POST['edit_template_id']) && isset($_POST['edit_template_last_user']) && strlen($_POST['edit_template_last_user']) > 0 && isset($_POST['edit_template_name']) && strlen($_POST['edit_template_name']) > 0) {
			$json_edit_template_name = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_update_template_name.php?'.http_build_query($_POST)),false, getContextCookies()), true);
			if (isset($json_edit_template_name['success']) && $json_edit_template_name['success'] == true) {
				echo('<div class="alert alert-dismissable alert-success">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<strong>Success:</strong> '.$json_edit_template_name['message']);
				echo('</div>');
			} else {
				echo('<div class="alert alert-dismissable alert-danger">');
				echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
				echo('<p><strong>Error:</strong> Unable to edit template name');
				if(isset($json_edit_template_name['error'])) {
					echo('<p style="margin-left:2em;">'.$json_edit_template_name['error'].'</p>');
				} else {
					echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
				}
				echo('</div>');
			}
			unset($json_edit_template_name);
		}

		echo("<table class='table_col_0_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>");
        echo("Template ID");
        echo("</th>");

		echo("<th>");
		echo("Template Name");
		echo("</th>");

		 echo("<th>");
		 echo("Department");
		 echo("</th>");

		echo("<th>");
		echo("User Count");
		echo("</th>");

		echo("<th>");
		echo("Cert Count");
		echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");

		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_templates.php'), false, getContextCookies()), true);

        $count = 0;
		foreach ($json as $key => $value) {
            $count++;
			echo('<tr>');

            echo('<td>');
            echo($value['template_id']);
            echo("</td>\n");

			echo('<td>');
			if($value['usercount'] > 0 && $value['certcount'] > 0) {
				echo('<a href="'.$mybaseurl.'/template_audit.php?template_id='.$value['template_id'].'">');
				echo($value['template_name']);
				echo('</a>');
			} else {
				echo($value['template_name']);
			}
            if(!$value['template_is_active']) {
                echo('<span style="display:none;">||||</span> <span class="label label-warning">Inactive</span>');
            } else {
                if ($value['template_is_default_for_department'] != 1) {
                    echo('<span style="display:none;">||||</span> ');
                    echo("<a href='javascript:void(0);' onclick='editTemplateName(" . $value['template_id'] . ", \"" . $value['template_name'] . "\");' class='btn btn-primary btn-xs btn-group hidden-print' title='Edit " . $value['template_name'] . " Template Name'>");
                    echo('<span class="glyphicon glyphicon-edit"></span></a>');
                } else {
                    //				move to department column//echo('<span style="display:none;">||||</span> <span class="label label-warning">Department</span>');
                }
                // if($value['template_is_default_for_department'] != 1 && $value['usercount'] < 1 && $value['certcount'] < 1) {
                if ($value['usercount'] < 1 && $value['certcount'] < 1) {
                    echo("<a href='javascript:void(0);' style='margin-left:5px;' onclick='removeTemplate(" . $value['template_id'] . ", \"" . $user['user_samaccountname'] . "\", \"" . $value['template_name'] . "\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Delete " . $value['template_name'] . " Template'>");
                    echo('<span class="glyphicon glyphicon-remove"></span></a>');
                }
            }
			echo("</td>\n");

            echo('<td>');
            if($value['template_is_default_for_department'] == 1) {
                echo('<span style="display:none;">||||</span> <span class="label label-info">Dept.</span>');
                echo($value['template_department_number'].'('.$value['template_department_name'].')');
            }
            echo("</td>\n");

			echo('<td>');
            if ($value['usercount'] > 0) {
                echo('<a href="'.$mybaseurl.'/template_user_links.php?template_id='.$value['template_id'].'">');
                echo($value['usercount']);
                echo('</a>');
            } else {
                echo("0");
            }

			echo("</td>\n");

			echo('<td>');
            echo('<a href="'.$mybaseurl.'/template_cert_links.php?template_id='.$value['template_id'].'">');
            if ($value['certcount'] > 0) {
                echo($value['certcount']);
            } else {
                echo("0");
            }
            echo('</a>');

			echo("</td>\n");
			echo("</tr>\n");
		}

		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");
		echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
		echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
		echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
		echo("<input type='hidden' id='filename' name='filename' value='all_templates.xls'>");
		echo("</form>");
		echo('<p>&nbsp;</p>');

		echo('<form name="remove_template_form" id="remove_template_form" action="'.$mybaseurl.'/all_templates.php" method="post">');
		echo('<input name="remove_template_last_user" id="remove_template_last_user" type="hidden" value="'.$user['user_samaccountname'].'">');
		echo('<input name="remove_template_id" id="remove_template_id" type="hidden" value="0">');
		echo('</form>');

        /**
         * Bootstrap Modals
         */
		echo('<div class="modal none hidden-print" id="modal_template_creator_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
		echo('<div class="modal-dialog">');
		echo('<div class="modal-content">');
		echo('<form role="form" name="create_template" id="create_template" action="'.$mybaseurl.'/all_templates.php" method="post" onsubmit="return certValidateCreateTemplate();">');
		echo('<div class="modal-header">');
		echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
		echo('<h4 class="modal-title">Create a new Template</h4>');
		echo('</div>');
		echo('<div class="modal-body">');
		echo('<div class="form-group">');
		echo('<input name="template_last_user" type="hidden" value="'.$user['user_samaccountname'].'">');
		echo('<label for="template_name" id="template_name_label">Template Name:</label>');
		echo('<input type="text" class="form-control" id="template_name" name="template_name" placeholder="Enter Template Name" maxlength="35">');
		echo('</div>'); // end of form-group
		echo('</div>');
		echo('<div class="modal-footer">');
		echo('<button type="submit" class="btn btn-primary btn-sm btn-group hidden-print">Add Template</button>');
		echo('</div>');
		echo('</form>');
		echo('</div>'); // end of modal-content
		echo('</div>'); // end of modal-dialog
		echo('</div>'); // end of modal

		echo('<div class="modal none hidden-print" id="modal_template_edit_name_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
		echo('<div class="modal-dialog">');
		echo('<div class="modal-content">');
		echo('<form role="form" name="edit_template_name_form" id="edit_template_name_form" action="'.$mybaseurl.'/all_templates.php" method="post" onsubmit="return certValidateNewTemplateName();">');
		echo('<div class="modal-header">');
		echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
		echo('<h4 class="modal-title">Edit Template Name</h4>');
		echo('</div>');
		echo('<div class="modal-body">');
		echo('<div class="form-group">');
		echo('<input name="edit_template_id" id="edit_template_id" type="hidden">');
		echo('<input name="edit_template_last_user" id="edit_template_last_user" type="hidden" value="'.$user['user_samaccountname'].'">');
		echo('<label for="edit_template_name" id="edit_template_name_label">Template Name:</label>');
		echo('<input type="text" class="form-control" id="edit_template_name" name="edit_template_name" placeholder="Enter New Template Name" maxlength="35">');
		echo('</div>'); // end of form-group
		echo('</div>');
		echo('<div class="modal-footer">');
        echo('<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>');
        echo('<button type="submit" class="btn btn-primary">Submit Changes</button>');
		echo('</div>');
		echo('</form>');
		echo('</div>'); // end of modal-content
		echo('</div>'); // end of modal-dialog
		echo('</div>'); // end of modal
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
