<?php
// team_users.php
include_once('header.php');  // has everything up to the container div in the body
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

if(($requested_user_id === $loggedInUser) || $user['user_is_admin']){ // A user can always view themselves
    $authorized = true;
} else {
    if ($user['user_is_supervisor']) {
        $req_meta = json_decode(file_get_contents(request_json_api('/JSON/JSON_user_meta.php?uid='.$requested_user_id), false, getContextCookies()), true);
        $user_supervisor_id = $req_meta[$requested_user_id]['meta']['manager'] ?? '';
        if ($user_supervisor_id === $loggedInUser)  {
            $authorized = true;
        }
    }
}

if ($authorized) {
    // --- Build API URL with helper ---
    $apiUrl = buildQueryUrl(
            '/JSON/JSON_rawdata.php',
            $REMOTE_USER[1],
            $_GET['mode']  ?? 'balanced',
            $_GET['start'] ?? date('Y-m-01'),
            $_GET['end']   ?? date('Y-m-d'),
            $_GET['quickRange'] ?? 'thisMonth',
            $_GET['team'] ?? 'mine',
    );

    $json = json_decode(file_get_contents(request_json_api($apiUrl), false, getContextCookies()), true);

    echo("<div id='jfabtable'>\n");
    echo("<table><tr><td style='width: 30%'><b>Team Users</b>[".(count($json)-1 ?? 0)."]</td><td style='width: 30%'>".date('Y-m-d H:i:s')."</td></tr></table>");

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
            if ($member === $REMOTE_USER[1]) { continue; }
            echo('<tr>');
            echo('<td>'.(++$count)."</td>");

            $params = [
                    'uid'        => $member,
                    'mode'       => $_GET['mode']       ?? 'balanced',
                    'start'      => $_GET['start']      ?? date('Y-m-01'),
                    'end'        => $_GET['end']        ?? date('Y-m-d'),
                    'quickRange' => $_GET['quickRange'] ?? 'thisMonth',
                    'team'       => '',
            ];
            $memberUrl = $mybaseurl . '/index.php?' . http_build_query($params);
            echo('<td><a target="_blank" href="'.$memberUrl.'">'.$member.'</a></td>');

            echo('<td>'.($value['meta']['givenname'] ?? '')."</td>");
            echo('<td>'.($value['meta']['sn'] ?? '')."</td>");
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
    echo('<div class="alert alert-danger">');
    echo('<p>Authorization failed</p>');
    echo('</div>');
}

include_once('footer.php');
?>
