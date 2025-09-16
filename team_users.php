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

    $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_rawdata.php?uid='.($REMOTE_USER[1])) , false, getContextCookies()), true);

	echo("<div id='jfabtable'>\n");
	echo("<table><tr><td style='width: 30%'><b>Team Users</b>[".(count($json)-1 ?? 0)."]</td><td style='width: 30%'>".date('Y-m-d H:i:s')."</td></tr></table>");

	if($authorized){
		echo("<table class='table_col_0_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>No.</th>");
		echo("<th>Member</th>");
		echo("<th>First Name</th>");
		echo("<th>Last Name</th>");
		echo("<th>Email</th>");
        echo("<th>Actual Workdays</th>");
        echo("<th>No Show Days</th>");
        echo("<th>Weekend Days</th>");
        echo("<th>Total Vacation</th>");
        echo("<th>Total Hours</th>");
        echo("<th>Avg. Hours</th>");

//		echo("<th>");
//		echo("Cert Count");
//		echo("</th>");

//		echo("<th>");
//		echo("Supervisor");
//		echo("</th>");

//		echo("<th>");
//		echo("Team Count");
//		echo("</th>");

		// echo("<th>");
		// echo("Admin");
		// echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");


        if ($json) {
            $count = 0;
            foreach ($json as $member => $value) {
                if ($member === $REMOTE_USER[1]) {continue;}
                echo('<tr>');

                echo('<td>');
                echo(++$count);
                echo("</td>\n");

                echo('<td>');
                echo('<a target="_blank" href="'.$mybaseurl.'/index.php?uid='.$member.'">');
                echo($member);
                echo('</a>');
//                if($value['meta']['role']) {
//                    echo('<span style="display:none;">||||</span> <span class="label label-info">Admin</span>');
//                }
                echo("</td>\n");

                echo('<td>');
                echo($value['meta']['givenname']);
                echo("</td>\n");

                echo('<td>');
                echo($value['meta']['sn']);
                echo("</td>\n");

                echo('<td>');
                echo('<a href="mailto:'.$value['meta']['mail'].'">'.$value['meta']['mail'].'</a>');
                echo("</td>\n");

//                echo('<td>');
//                echo($value['meta']['supervisor'] ?? '');
//                echo("</td>\n");

//                echo('<td>');
//                echo($value['meta']['team_count'] ?? 0);
//                echo("</td>\n");

                echo "<td>".($value['summary']['actual_workdays'] ?? 0)."</td>";
                echo "<td>".count($value['summary']['no_show_days'] ?? [])."</td>";
                echo "<td>".count($value['summary']['weekend_days'] ?? [])."</td>";
                echo "<td>".($value['summary']['total_vacation'] ?? 0)."</td>";
                echo "<td>".($value['summary']['total_hours'] ?? 0)."</td>";
                echo "<td>".($value['summary']['avg_hours'] ?? 0)."</td>";

                echo("</tr>\n");
            }
        } else {
            echo "<tr><td colspan='11'>No results found.</td></tr>\n";
        }
		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");

	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
