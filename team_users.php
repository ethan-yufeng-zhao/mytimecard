<?php
	// team_users.php
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
</script>

<?php
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

	echo("<div id='jfabtable'>\n");
	echo('<table><tr>');
	echo('<td><h2 style="margin:0px;">');

	echo('Team Users');
	echo('</h2></td></tr>');
	echo('<tr><td>');
	echo(date('Y-m-d H:i:s'));
	echo('</td>');
	echo('</tr></table>');

	if($authorized){
		echo("<table class='table_col_0_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>");
        echo("No.");
        echo("</th>");

		echo("<th>");
		echo("User");
		echo("</th>");

		echo("<th>");
		echo("First Name");
		echo("</th>");

		echo("<th>");
		echo("Last Name");
		echo("</th>");

		echo("<th>");
		echo("Email");
		echo("</th>");

//		echo("<th>");
//		echo("Cert Count");
//		echo("</th>");

		echo("<th>");
		echo("Supervisor");
		echo("</th>");

		echo("<th>");
		echo("Team Count");
		echo("</th>");

		// echo("<th>");
		// echo("Admin");
		// echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");

		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php?manager='.($REMOTE_USER[1] ?? $user['user_id'])) , false, getContextCookies()), true);
        $count = 0;
		foreach ($json as $value) {
			echo('<tr>');

            echo('<td>');
            echo(++$count);
            echo("</td>\n");

			echo('<td>');
			echo('<a href="'.$mybaseurl.'/index.php?uid='.$value['user_id'].'">');
			echo($value['user_samaccountname']);
			echo('</a>');
//			if($value['user_is_admin']) {
//				echo('<span style="display:none;">||||</span> <span class="label label-info">Admin</span>');
//			}
			echo("</td>\n");

			echo('<td>');
			echo($value['user_firstname']);
			echo("</td>\n");

			echo('<td>');
			echo($value['user_lastname']);
			echo("</td>\n");

			echo('<td>');
			echo('<a href="mailto:'.$value['user_email'].'">'.$value['user_email'].'</a>');
			echo("</td>\n");

//			echo('<td>');
//            if($value['certcount'] > 0){
//                echo($value['certcount']);
//            }
//			echo("</td>\n");

			echo('<td>');
            if($value['user_supervisor_id']) {
                if ($GLOBALS['DB_TYPE'] == 'pgsql') {
                    echo('<a href="' . $mybaseurl . '/index.php?uid=' . $value['user_supervisor_id'] . '">');
                    echo($value['user_supervisor_id']);
                } else {
                    $manager= $json[$value['user_supervisor_id']]['user_samaccountname'];
                    echo('<a href="' . $mybaseurl . '/index.php?uid=' . $manager . '">');
                    echo($manager);
                }
                echo('</a>');
            }
			echo("</td>\n");


			echo('<td>');
			if($value['teamcount'] > 0){
				echo($value['teamcount']);
			}
			echo("</td>\n");

			// echo('<td>');
			// if($value['user_is_admin'] > 0){
			// 	echo('Y');
			// }
			// echo("</td>\n");

			echo("</tr>\n");
		}
		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");
		echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' type='button' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
		echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' onsubmit='return saveToExcel();'>\n");
		echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
		echo("<input type='hidden' id='filename' name='filename' value='all_users.xls'>");
		echo("</form>");
		echo('<p>&nbsp;</p>');
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
