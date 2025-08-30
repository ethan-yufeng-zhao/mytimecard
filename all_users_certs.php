<?php
	// all_users_certs.php
	include_once('header.php');  // has everything up to the container div in the body

	$authorized = false;
	if(!$authorized && $user['user_is_admin']){ // Admin users can view anyone
		$authorized = true;
	}
?>

<script type="text/javascript">
    $(document).ready(function() {
        $(".table_col_3_with_labels").tablesorter({
            theme : "bootstrap",
            widthFixed: true,
            headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!
            widgets : [ "uitheme", "filter" ],
            widgetOptions : {
                filter_reset : ".reset"
            },
            headers: {
                3: {
                    sorter:'ignore_labels'
                }
            }
        });
    });
</script>


<?php
	if($authorized) {
		echo("<div id='jfabtable'>\n");
		echo('<table><tr>');
		echo('<td><h2 style="margin:0px;">');

		echo('All Granted User Certifications');
		echo('</h2></td></tr>');
		echo('<tr><td>');
		echo(date('Y-m-d H:i:s'));
		echo('</td>');
		echo('</tr></table>');

		echo('<p><div class="btn-toolbar">');
		echo('<button type="button" class="filter btn btn-primary btn-sm btn-group hidden-print" data-column="4" data-filter="/ [1-2][0-9] days| [0-9] days/">Show Certifications expiring in the next 30 days</button>');
		echo('<button type="button" class="reset btn btn-primary btn-sm btn-group hidden-print" data-column="0" data-filter="">Reset filters</button>');
		echo('</div></p>');

		echo("<table class='table_col_3_with_labels'>");
		echo("<thead>");
		echo("<tr>");

        echo("<th>");
        echo("No.");
        echo("</th>");

		echo("<th>");
		echo("User");
		echo("</th>");

		echo("<th>");
		echo("Cert");
		echo("</th>");

		echo("<th>");
		echo("Description");
		echo("</th>");

		echo("<th>");
		// echo("Expiration");
		echo("Status");
		echo("</th>");

		// echo("<th>");
		// echo("Never Expires");
		// echo("</th>");

		echo("<th>");
		echo("History Count");
		echo("</th>");

        echo("<th>");
        echo("Supervisor");
        echo("</th>");

		echo("</tr>\n");
		echo("</thead>");

		echo("<tbody>\n");

		$json = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users_certs.php') , false, getContextCookies()), true);
        //$loop1=0;
        //$loop2=0;
        $count = 0;
        if ($GLOBALS['DB_TYPE'] == 'pgsql'){
            foreach ($json as $uid_key => $user_value) {
                //logit('loop1: '.$loop1++);
                foreach($user_value as $key => $value) {
                    //logit('loop2: '.$loop2++);
//                    $largest_key = max(array_keys($value)); // This makes sure that we are always looking at the newest certification
                    echo('<tr>');

                    echo('<td>');
                    echo(++$count);
                    echo("</td>");

                    echo('<td>');
                    echo('<a href="'.$mybaseurl.'/index.php?uid='.$uid_key.'">');
                    echo($uid_key);
                    echo('</a>');
                    echo("</td>");

                    echo('<td>');
                    echo($value['cert_name']);
                    echo("</td>");

                    echo('<td>');
                    echo($value['cert_description']);
                    echo("</td>");

                    echo("<td>");
                    if($value['cert_never_expires']) {
                        echo('Never expires');
                    } else {
                        $value_expire = reset($value['expire']);
                        echo($value_expire['calculated_expire_ymd']);
                        if(intval($value_expire['calculated_days_until_expire']) < -31) {
                            echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
                        } elseif(intval($value_expire['calculated_days_until_expire']) < 0) {
                            echo('<span style="display:none;">||||</span> <span class="label label-default" style="background-color:#E17572;">Now Due</span>');
                        } elseif(intval($value_expire['calculated_days_until_expire']) <= 30) {
                            echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$value_expire['calculated_days_until_expire'].' days</span>');
                        } else {
                            echo('<span style="display:none;">||||</span> <span class="label label-success">'.$value_expire['calculated_days_until_expire'].' days</span>'); // The 4 bars are for parsing in javascript.  Content comes before them.
                        }

                    }
                    if($value['user_cert_exception'] == 1) {
                        echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
                    }
                    echo('</td>');

                    echo('<td>');
                    echo('<a href="'.$mybaseurl.'/user_cert_history.php?cert_id='.$key.'&uid='.$uid_key.'">');
                    echo(count($value['cert_date']));
                    echo('</a>');
                    echo("</td>");

                    echo('<td>');
                    echo('<a href="'.$mybaseurl.'/index.php?uid='.$value['supervisor'].'">');
                    echo($value['supervisor']);
                    echo('</a>');
                    echo("</td>");

                    echo("</tr>\n");
                }
            }
        } else {
            foreach ($json as $uid_key => $user_value) {
                // var_dump($uid_key);
                // exit();
                foreach($user_value['certs'] as $value) {
                    $largest_key = max(array_keys($value)); // This makes sure that we are always looking at the newest certification
                    echo('<tr>');

                    echo('<td>');
                    echo(++$count);
                    echo("</td>\n");

                    echo('<td>');
                    echo('<a href="'.$mybaseurl.'/index.php?uid='.$user_value['user_id'].'">');
                    echo($user_value['user_samaccountname']);
                    echo('</a>');
                    echo("</td>\n");

                    echo('<td>');
                    echo($value[$largest_key]['cert_name']);
                    echo("</td>\n");

                    echo('<td>');
                    echo($value[$largest_key]['cert_description']);
                    echo("</td>\n");



                    echo("\n<td>");
                    if($value[$largest_key]['cert_never_expires'] == 0) {
                        echo($value[$largest_key]['calculated_expire_ymd']);


                        if(intval($value[$largest_key]['calculated_days_until_expire']) < -31) {
                            echo('<span style="display:none;">||||</span> <span class="label label-danger">Expired</span>');
                        } elseif(intval($value[$largest_key]['calculated_days_until_expire']) < 0) {
                            echo('<span style="display:none;">||||</span> <span class="label label-default" style="background-color:#E17572;">Now Due</span>');
                        } elseif(intval($value[$largest_key]['calculated_days_until_expire']) <= 30) {
                            echo('<span style="display:none;">||||</span> <span class="label label-warning">'.$value[$largest_key]['calculated_days_until_expire'].' days</span>');
                        } else {
                            echo('<span style="display:none;">||||</span> <span class="label label-success">'.$value[$largest_key]['calculated_days_until_expire'].' days</span>'); // The 4 bars are for parsing in javascript.  Content comes before them.
                        }
                    } else {
                        echo('Never expires');
                    }
                    if($value[$largest_key]['user_cert_exception'] == 1) {
                        echo('<span style="display:none;">||||</span> <span class="label label-danger">Exception</span>');
                    }
                    echo('</td>');


                    // echo('<td>');
                    // if($value[$largest_key]['cert_never_expires'] == 0){
                    // 	echo($value[$largest_key]['calculated_expire_ymd']);
                    // 	echo(' - (');
                    // 	echo($value[$largest_key]['calculated_days_until_expire']);
                    // 	echo(')');
                    // }
                    // echo("</td>\n");

                    // echo('<td>');
                    // if($value[$largest_key]['cert_never_expires'] == 1){
                    // 	echo('Y');
                    // }
                    // echo("</td>\n");

                    echo('<td>');
                    echo('<a href="'.$mybaseurl.'/user_cert_history.php?cert_id='.$value[$largest_key]['cert_id'].'&uid='.$user_value['user_id'].'">');
                    echo(count($value));
                    echo('</a>');
                    echo("</td>\n");

                    echo("</tr>\n");
                }
            }
        }
		echo("</tbody>");

		echo("</table>\n");
		echo("</div>\n");

		echo("<p><a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();' class='btn btn-primary btn-sm hidden-print'>Save to Excel</a></p>");
		// echo('<p>');
		// echo("<a href='javascript:void(0);' onclick='$(\"#savetoexcelform\").submit();'>");
		// echo('Save to Excel');
		// echo('</a>');
		// echo('</p>');
		echo("<form action='SaveToExcel.php' name='savetoexcelform' id='savetoexcelform' method='post' onsubmit='return saveToExcel();'>\n");
		echo("<input type='hidden' id='dataToDisplay' name='dataToDisplay'>");
		echo("<input type='hidden' id='filename' name='filename' value='all_users_certs.xls'>");
		echo("</form>");
		echo('<p>&nbsp;</p>');
	} else {
		echo('<div class="alert alert-danger">');
		echo('<p>Authorization failed</p>');
		echo('</div>');
	}

	// echo('</div>');
	include_once('footer.php');
?>
