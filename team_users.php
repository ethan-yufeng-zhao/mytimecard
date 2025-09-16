<?php
// team_users.php
include_once('header.php');  // has everything up to the container div in the body

// Build API URL with toolbar params
$apiUrl = '/JSON/JSON_rawdata.php?uid=' . urlencode($REMOTE_USER[1])
        . '&mode=' . urlencode($mode)
        . '&start=' . urlencode($start)
        . '&end=' . urlencode($end);

$json = json_decode(file_get_contents(request_json_api($apiUrl), false, getContextCookies()), true);
?>

<script type="text/javascript">
    $(document).ready(function() {
        $(".table_col_0_with_labels").tablesorter({
            theme : "bootstrap",
            widthFixed: true,
            headerTemplate : '{content} {icon}',
            widgets : [ "uitheme", "filter" ],
            widgetOptions : {
                filter_reset : ".reset"
            },
            headers: {
                0: { sorter:'ignore_labels' }
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
    echo("</tr>\n");
    echo("</thead>");
    echo("<tbody>\n");

    if ($json) {
        $count = 0;
        foreach ($json as $member => $value) {
            if ($member === $REMOTE_USER[1]) {continue;}
            echo('<tr>');
            echo('<td>'.(++$count)."</td>\n");
            echo('<td><a target="_blank" href="'.$mybaseurl.'/index.php?uid='.$member.'">'.$member.'</a></td>');
            echo('<td>'.$value['meta']['givenname']."</td>\n");
            echo('<td>'.$value['meta']['sn']."</td>\n");
            echo('<td><a href="mailto:'.$value['meta']['mail'].'">'.$value['meta']['mail'].'</a></td>');
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
    echo('<div class="alert alert-danger"><p>Authorization failed</p></div>');
}

include_once('footer.php');
?>
