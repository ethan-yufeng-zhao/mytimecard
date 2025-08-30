<?php
	// all_tools.php
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

	echo("<div id='jfabtable'>\n");
	echo('<table><tr>');
	echo('<td><h2 style="margin:0px;">');

	echo('All Tools');
	echo('</h2></td></tr>');
	echo('<tr><td>');
	echo(date('Y-m-d H:i:s'));
	echo('</td>');
	echo('</tr></table>');

	if($authorized) {
		echo("<table class='table_col_0_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>");
        echo("No.");
        echo("</th>");

		echo("<th>");
		echo("Tool Name");
		echo("</th>");

		 echo("<th>");
		 echo("Tool Type");
		 echo("</th>");

		echo("<th>");
		echo("Tool Location");
		echo("</th>");

		echo("<th>");
		echo("Certs");
		echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");

		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_tools.php'), false, getContextCookies()), true);
        $count = 0;
        $json_certs = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_certs.php'), false, getContextCookies()), true);
		foreach ($json as $key => $value) {
			echo('<tr>');

            echo('<td>');
            echo(++$count);
            echo("</td>\n");

			echo('<td>');
			echo($value['tool_name']);
            echo("</td>\n");

            echo('<td>');
            echo($value['tool_type']);
            echo("</td>\n");

            echo('<td>');
            echo($value['tool_location']);
            echo("</td>\n");

            echo('<td>');
            if ($json_certs) {
                $certs = '';
                foreach ($json_certs as $k => $v) {
                    if($v['tool'] != null && in_array($value['tool_name'], $v['tool'])){
                        $certs .= $v['cert_name'].',';
                    }
                }
                echo(strlen($certs) > 0 ? substr($certs, 0, -1) : $certs);
            }
            echo("</td>\n");

			echo("</tr>\n");
		}

		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");
		echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
		echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' target='_blank' onsubmit='return saveToExcel();'>\n");
		echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
		echo("<input type='hidden' id='filename' name='filename' value='all_tools.xls'>");
		echo("</form>");
		echo('<p>&nbsp;</p>');
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	include_once('footer.php');
?>
