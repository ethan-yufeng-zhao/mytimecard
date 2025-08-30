<?php
	// team_users_templates.php
	include_once('header.php');  // has everything up to the container div in the body

	$authorized = false;

	if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
		$authorized = true;
	}

	if(isset($_GET['user_supervisor_id']) && $user['user_is_admin']) {
		$user_supervisor_id = $_GET['user_supervisor_id'];
	} else {
		$user_supervisor_id = $user['user_id'];
		$authorized = true;
	}

?>
	<script type="text/javascript">
		$(document).ready(function() {
			$(".table_col_1_with_labels").tablesorter({
				theme : "bootstrap",
				widthFixed: true,
				headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
				widgets : [ "uitheme", "filter" ],
				widgetOptions : {
					filter_reset : ".reset"
				},
				headers: {
					1: {
						sorter:'ignore_labels'
					}
				}
			});
		});
	</script>
<?php
	$team_leader = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_id='.$user_supervisor_id) , false, getContextCookies()), true);
	echo("<div id='jfabtable'>\n");
	echo('<table><tr>');
	echo('<td><h2 style="margin:0px;">');
	echo($team_leader['user_firstname'].' '.$team_leader['user_lastname']);
	echo(' - Team Users Templates');
	echo('</h2></td></tr>');
	echo('<tr><td>');
	echo(date('Y-m-d H:i:s'));
	echo('</td>');
	echo('</tr></table>');

	if($authorized) {
		echo("<table class='table_col_1_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>");
        echo("No.");
        echo("</th>");

		echo("<th>");
		echo("User");
		echo("</th>");

		echo("<th>");
		echo("Template");
		echo("</th>");

		// echo("<th>");
		// echo("Department");
		// echo("</th>");

		echo("<th>");
		echo("Certs in Template");
		echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");

		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_team_users_templates.php?user_supervisor_id='.$user_supervisor_id) , false, getContextCookies()), true);
        $count = 0;
        if ($GLOBALS['DB_TYPE'] == 'pgsql') {
            foreach ($json as $key => $data) {
                foreach ($data as $id=>$value) {
                    echo('<tr>');

                    echo('<td>');
                    echo(++$count);
                    echo("</td>\n");

                    echo('<td>');
                    echo('<a href="' . $mybaseurl . '/index.php?uid=' . $key . '">');
                    echo($key);
                    echo('</a>');
                    echo("</td>\n");

                    echo('<td>');
                    // echo($value['template_name']);

                    echo($value['template_name']);

                    if ($value['template_is_default_for_department'] == 1) {
                        echo('<span style="display:none;">||||</span> <span class="label label-warning">Department</span>');
                    }
                    echo("</td>\n");

                    // echo('<td>');
                    // if($value['template_is_default_for_department'] == 1){
                    // 	echo('Y');
                    // }
                    // echo("</td>\n");

                    echo('<td>');
                    if ($value['template_cert_count'] > 0) {
                        echo('<a href="' . $mybaseurl . '/template_audit.php?template_id=' . $id . '" title="Audit this template">');
                        echo($value['template_cert_count']);
                        echo('</a>');
                    } else {
                        echo($value['template_cert_count']);
                    }

                    echo("</td>\n");

                    echo("</tr>\n");
                }
            }
        } else { //mysql
            foreach ($json as $key => $value) {
                echo('<tr>');

                echo('<td>');
                echo(++$count);
                echo("</td>\n");

                echo('<td>');
                echo('<a href="'.$mybaseurl.'/index.php?uid='.$value['user_id'].'">');
                echo($value['user_samaccountname']);
                echo('</a>');
                echo("</td>\n");

                echo('<td>');
                // echo($value['template_name']);

                echo($value['template_name']);

                if($value['template_is_default_for_department'] == 1){
                    echo('<span style="display:none;">||||</span> <span class="label label-warning">Department</span>');
                }
                echo("</td>\n");

                // echo('<td>');
                // if($value['template_is_default_for_department'] == 1){
                // 	echo('Y');
                // }
                // echo("</td>\n");

                echo('<td>');
                if($value['template_cert_count'] > 0) {
                    echo('<a href="'.$mybaseurl.'/template_audit.php?template_id='.$value['template_id'].'" title="Audit this template">');
                    echo($value['template_cert_count']);
                    echo('</a>');
                } else {
                    echo($value['template_cert_count']);
                }
                echo("</td>\n");
                echo("</tr>\n");
            }
        }
		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");
		echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
		echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
		echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
		echo("<input type='hidden' id='filename' name='filename' value='team_users_templates.xls'>");
		echo("</form>");
		echo('<p>&nbsp;</p>');
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}


	include_once('footer.php');
?>
