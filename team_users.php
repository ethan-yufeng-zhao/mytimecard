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
if ($authorized) {
    // --- Build API URL with helper ---
    $apiUrl = buildQueryUrl(
            '/JSON/JSON_rawdata.php',
            $loggedInUser,
            $_GET['mode']  ?? 'balanced',
            $_GET['start'] ?? date('Y-m-01'),
            $_GET['end']   ?? date('Y-m-d'),
            $_GET['quickRange'] ?? 'thisMonth',
            $_GET['team'] ?? 'direct',
    );

    $json = json_decode(file_get_contents(request_json_api($apiUrl), false, getContextCookies()), true);
    $department = $json[$loggedInUser]['meta']['department'] ?? '';
    $departmentnumber = $json[$loggedInUser]['meta']['departmentnumber'] ?? '';
    echo("<div id='jfabtable'>\n");
    // title table
    echo('<table class="employee-info-table"><tr>');
//    echo("<td><b>".$department."(".$departmentnumber.")</b></td>");
    $title_name = $_GET['team'] ?? '';
    echo("<td><b>".strtoupper($title_name).": </b>".(count($json)-1 ?? 0)."</td>");
    echo("<td>".date('Y-m-d H:i:s')."</td>");
    echo("</tr></table>");
    // data table
    echo("<table class='table_col_0_with_labels'>");
    echo("<thead>");
    echo("<tr>");

    echo("<th>No.</th>");
    echo("<th>Member</th>");
//    echo("<th>First Name</th>");
//    echo("<th>Last Name</th>");
//    echo("<th>Email</th>");
    echo("<th>Actual Workdays</th>");
    echo("<th>No Show Days</th>");
    echo("<th>Weekend Days</th>");
    echo("<th>Avg. Onsite</th>");
    echo("<th>Avg. in Building</th>");
    echo("<th>Avg. out of Building</th>");
    echo("<th>Avg. in Fab</th>");
    echo("<th>Avg. in Subfab</th>");
    echo("<th>Avg. in Facilities</th>");
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

//            echo('<td>'.($value['meta']['givenname'] ?? '')."</td>");
//            echo('<td>'.($value['meta']['sn'] ?? '')."</td>");
//            echo('<td><a href="mailto:'.$value['meta']['mail'].'">'.$value['meta']['mail'].'</a></td>');

            echo "<td>".($value['summary']['actual_workdays'] ?? 0)."</td>";
            echo "<td>".count($value['summary']['no_show_days'] ?? [])."</td>";
            echo "<td>".count($value['summary']['weekend_days'] ?? [])."</td>";
            echo "<td>".($value['summary']['avg_tos'] ?? 0)."</td>";
            echo "<td>".($value['summary']['avg_tib'] ?? 0)."</td>";
            echo "<td>".($value['summary']['avg_tob'] ?? 0)."</td>";
            echo "<td>".($value['summary']['avg_tif'] ?? 0)."</td>";
            echo "<td>".($value['summary']['avg_tisf'] ?? 0)."</td>";
            echo "<td>".($value['summary']['avg_tifac'] ?? 0)."</td>";
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
