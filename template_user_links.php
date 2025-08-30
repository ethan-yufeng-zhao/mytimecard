<?php
    // template_user_links.php?template_id=2
    include_once('header.php');  // has everything up to the container div in the body
    $json_all_users = json_decode(file_get_contents(request_json_api('/JSON/JSON_all_users.php') , false, getContextCookies()), true);
?>

<script type="text/javascript">
    function removeUserFromTemplate(template_user_links_id, user_samaccountname, template_name) {
        if(confirm("Template Name: "+template_name+"\nUser Name: "+user_samaccountname+"\n\nRemove this user from this Template?")) {
            $("#template_user_links_id").val(template_user_links_id);
            $("#remove_user_id").val(user_samaccountname);
            $('#remove_user_from_template_form').submit();
        }
    }
    function chooseUser(template_id, user_samaccountname, template_name) {
        if(confirm("Add user: "+user_samaccountname+"\n\nTo Template: "+template_name)) {
            $("#template_user_links_user_id").val(user_samaccountname);
            $('#template_user_links_add_user_form').submit();
        }
    }
</script>

<?php
    $authorized = false;
    if(!$authorized && $user['user_is_admin']) { // Admin users can view anyone
        $authorized = true;
    }

    if(isset($_GET['template_id']) && strlen($_GET['template_id']) > 0 && is_numeric($_GET['template_id'])) {
        // echo('<div class="span-22 append-1 prepend-1 last" style="margin-top:1em;">');
        // echo('<div class="span-24 last" style="margin-top:1em;">');
        if($authorized) {
            if (isset($_POST['template_user_links_template_id']) && is_numeric($_POST['template_user_links_template_id']) && $_POST['template_user_links_template_id'] > 0
                && isset($_POST['template_user_links_user_id']) && strlen($_POST['template_user_links_user_id']) > 0) {
                $post_add_user = array();
                $post_add_user['template_id'] = $_POST['template_user_links_template_id'];
                $post_add_user['user_id'] = $_POST['template_user_links_user_id'];
                $json_add_user_to_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user_to_template.php?'.http_build_query($post_add_user)) , false, getContextCookies()), true);
                if (isset($json_add_user_to_template['success']) && $json_add_user_to_template['success'] == true) {
                    echo('<div class="alert alert-dismissable alert-success">');
                    echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                    echo('<strong>Success:</strong> '.$json_add_user_to_template['message']);
                    echo('</div>');
                } else {
                    echo('<div class="alert alert-dismissable alert-danger">');
                    echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                    echo('<p><strong>Error:</strong> Failed to add user to template</p>');
                    if(isset($json_add_user_to_template['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_add_user_to_template['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');
                }
                unset($json_add_user_to_template);
            }

            if (isset($_POST['remove_user_from_template']) && $_POST['remove_user_from_template'] == 1) { // Do a delete from the database
                $post_remove_arr = array();
                $post_remove_arr['template_user_links_id'] = $_POST['template_user_links_id'];
                $post_remove_arr['remove_user_id'] = $_POST['remove_user_id'];

                $json_remove_user_from_template = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_delete_template_user_links.php?'.http_build_query($post_remove_arr)) , false, getContextCookies()), true);
                if (isset($json_remove_user_from_template['success']) && $json_remove_user_from_template['success'] == true) {
                    // echo('<div style="padding: .7em;"><div class="ui-state-highlight ui-corner-all" style="padding: .7em;"><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><strong>Success:</strong> '.$json_remove_user_from_template['message'].'</div></div>');
                    echo('<div class="alert alert-dismissable alert-success">');
                    echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                    echo('<strong>Success:</strong> '.$json_remove_user_from_template['message']);
                    echo('</div>');
                } else {
                    // echo('<div style="padding: .7em;"><div class="ui-state-error ui-corner-all" style="padding: .7em;"><span class="ui-icon ui-icon-alert" style="float: left; margin-right: .3em;"></span><p style="margin:0; padding:0;"><strong>Error:</strong> '.$json_remove_user_from_template['error'].'<br>'.$json_remove_user_from_template['error'].'</p></div></div>');
                    echo('<div class="alert alert-dismissable alert-danger">');
                    echo('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
                    echo('<p><strong>Error:</strong> Failed to remove user from template</p>');
                    if(isset($json_remove_user_from_template['error'])) {
                        echo('<p style="margin-left:2em;">'.$json_remove_user_from_template['error'].'</p>');
                    } else {
                        echo('<p style="margin-left:2em;">No error was returned from JSON model controller</p>');
                    }
                    echo('</div>');
                }
                unset($json_remove_user_from_template);
            }

            /**
             * Begining of content
             */
            $json = json_decode(file_get_contents(request_json_api('/JSON/JSON_users_in_template_by_template_id.php?template_id='.$_GET['template_id']) , false, getContextCookies()), true);
            if (!is_null($json)) {
                foreach($json['items'] as $template_id => $value) {
                    echo("<div id='jfabtable'>\n");

                    echo('<table><tr>');
                    echo('<td><h2 style="margin:0px;">');

                    $template_name = $value['template_name'];
                    echo('Template: '.$template_name);
                    echo('</h2></td></tr>');
                    echo('<tr><td>');
                    if($value['template_is_default_for_department']) {
                        echo('<tr><td>');
                        echo('This is the default template for department number '.$value['template_department_number'].' (Default templates user list may not be changed here)');
                        echo('</td></tr>');
                    }
                    if($template_id == 1) {
                        echo('<tr><td>');
                        echo('The All Employees template has all users in it.  You may not add or remove users.');
                        echo('</td></tr>');
                    }

                    echo('</td></tr>');
                    echo('<tr><td>');
                    echo(date('Y-m-d H:i:s'));
                    echo('</td>');
                    echo('</tr></table>');

                    // echo('<table><tr>');
                    // echo('<td><h2 style="margin:0;">');
                    // $template_name = $value['template_name'];
                    // echo('Template: '.$template_name);
                    // echo('</h2></td></tr>');
                    // if($value['template_is_default_for_department']) {
                    //     echo('<tr><td>');
                    //     echo('This is the default template for department number '.$value['template_department_number'].' (Default templates user list may not be changed here)');
                    //     echo('</td></tr>');

                    // }
                    // echo('</table>');
                    if(!$value['template_is_default_for_department'] && $template_id !== 1) { // default templates may not be changed here
                        echo('<p><a data-toggle="modal" href="#modal_template_add_user_data" class="btn btn-primary btn-sm hidden-print">Add User to this Template</a></p>');
                    }
                    if($value['usercount'] > 0) {
                        echo("<table class='tablesorter'>");
                        echo("<thead>");
                        echo("<tr>");

                        echo("<th>");
                        echo("No.");
                        echo("</th>");

                        echo("<th>");
                        echo("User");
                        echo("</th>");

                        echo("<th>");
                        echo("WS Acount");
                        echo("</th>");

                        echo("<th>");
                        echo("First Name");
                        echo("</th>");

                        echo("<th>");
                        echo("Last Name");
                        echo("</th>");

                        echo("<th>");
                        echo("email");
                        echo("</th>");

                        echo("<th>");
                        echo("Supervisor");
                        echo("</th>");

                        echo("</tr>\n");
                        echo("</thead>");

                        echo("<tbody>\n");
                        $count = 0;
                        foreach ($value['users'] as $uservalue) {
                            echo('<tr>');

                            echo('<td>');
                            echo(++$count);
                            echo("</td>\n");

                            echo('<td>');
                            echo($uservalue['user_samaccountname']);
                            if(isset($_GET['enable_edit']) && $_GET['enable_edit'] == 1) {
                                echo('<span style="display:none;">||||</span> ');
                                if ($GLOBALS['DB_TYPE'] == 'pgsql') {
                                    echo("<a href='javascript:void(0);' onclick='removeUserFromTemplate(".$value['template_id'].", \"".$uservalue['user_samaccountname']."\", \"".$value['template_name']."\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Remove ".$uservalue['user_samaccountname']." from Template'>");
                                } else {
                                    echo("<a href='javascript:void(0);' onclick='removeUserFromTemplate(".$uservalue['template_user_links_id'].", \"".$uservalue['user_samaccountname']."\", \"".$value['template_name']."\");' class='btn btn-danger btn-xs btn-group hidden-print' title='Remove ".$uservalue['user_samaccountname']." from Template'>");
                                }
                                echo('<span class="glyphicon glyphicon-remove"></span></a>');
                            }
                            echo("</td>\n");

                            echo('<td>');
                            echo($uservalue['user_wsaccount']);
                            echo("</td>\n");

                            echo('<td>');
                            echo($uservalue['user_firstname']);
                            echo("</td>\n");

                            echo('<td>');
                            echo($uservalue['user_lastname']);
                            echo("</td>\n");

                            echo('<td>');
                            echo($uservalue['user_email']);
                            echo("</td>\n");

                            echo('<td>');
                            if ($GLOBALS['DB_TYPE'] == 'pgsql'){
                                echo($uservalue['user_supervisor_id']);
                            } else {
                                if($uservalue['user_supervisor_id'] != 0){
                                    echo($json_all_users[$uservalue['user_supervisor_id']]['user_samaccountname']);
                                }
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
                        echo("<input type='hidden' id='filename' name='filename' value='template_user.xls'>");
                        echo("</form>");
                    } else {
                        echo('<p>There are no users currently assigned to this template.</p>');

                    }
                }
            }

            if(!is_null($json) && !$value['template_is_default_for_department'] && $value['usercount'] > 0 && $template_id !== 1) { // default templates may not be changed here
                $removefields = $_GET;

                if(!isset($_GET['enable_edit']) || $_GET['enable_edit'] != 1) {
                    $removefields['enable_edit'] = 1;
                    echo('<p><a href="'.$mybaseurl.'/template_user_links.php?'.http_build_query($removefields).'" class="btn btn-danger btn-sm btn-group hidden-print">Enable remove mode</a></p>');
                } else {
                    $removefields['enable_edit'] = 0;
                    echo('<p><a href="'.$mybaseurl.'/template_user_links.php?'.http_build_query($removefields).'" class="btn btn-success btn-sm btn-group hidden-print">Disable remove mode</a></p>');
                }
            }
            echo('<p>&nbsp;</p>');

            $formfields = $_GET;

            $formfields['enable_edit'] = 0;
            // remote user from template
            echo('<form name="remove_user_from_template_form" id="remove_user_from_template_form" action="'.$mybaseurl.'/template_user_links.php?'.http_build_query($formfields).'" method="post">');
            echo('<input name="remove_user_from_template" type="hidden" value="1">'); // trigger flag
            echo('<input name="template_user_links_id" id="template_user_links_id" type="hidden" value="0">'); // template id
            echo('<input name="remove_user_id" id="remove_user_id" type="hidden" value="0">'); // user id
            echo('</form>');

            // add user to template
            echo('<form name="template_user_links_add_user_form" id="template_user_links_add_user_form" action="'.$mybaseurl.'/template_user_links.php?'.http_build_query($_GET).'" method="post">');
            echo('<input name="template_user_links_template_id" id="template_user_links_template_id" type="hidden" value="'.$_GET['template_id'].'">');
            echo('<input name="template_user_links_user_id" id="template_user_links_user_id" type="hidden" value="0">');
            echo('</form>');

            /**
             * Bootstrap Modals
             */
            echo('<div class="modal none hidden-print" id="modal_template_add_user_data" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">');
            echo('<div class="modal-dialog wider-modal">');
            echo('<div class="modal-content">');
            echo('<div class="modal-header">');
            echo('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>');
            echo('<h4 class="modal-title">Add a new user to current template</h4>');
            echo('</div>');
            echo('<div class="modal-body">');
            echo("<table class='tablesorter'>");
            echo("<thead>");
            echo("<tr>");

            echo("<th>");
            echo("User");
            echo("</th>");

            echo("<th>");
            echo("First");
            echo("</th>");

            echo("<th>");
            echo("Last");
            echo("</th>");

            echo("<th>");
            echo("Supervisor");
            echo("</th>");

            echo("</tr>\n");
            echo("</thead>");
            echo("<tbody>\n");

            foreach ($json_all_users as $user_value) {
                echo('<tr>');

                echo('<td>');
                echo('<a href="javascript:void(0);" onclick="chooseUser(\''.$template_id.'\', \''.$user_value['user_samaccountname'].'\', \''.$template_name.'\');">'.$user_value['user_samaccountname'].'</a>');
                echo("</td>\n");

                echo('<td>');
                echo($user_value['user_firstname']);
                echo("</td>\n");

                echo('<td>');
                echo($user_value['user_lastname']);
                echo("</td>\n");

                echo('<td>');
                if($user_value['user_supervisor_id'] != 0){
                    echo($json_all_users[$user_value['user_supervisor_id']]['user_samaccountname']);
                }
                echo("</td>\n");

                echo("</tr>\n");
            }
            echo("</tbody>");
            echo("</table>\n");
            echo('</div>'); // end of modal-body
            echo('</div>'); // end of modal-content
            echo('</div>'); // end of modal-dialog
            echo('</div>'); // end of modal
        } else {
            echo('<div class="alert alert-danger">');
            echo('<p>Authorization failed</p>');
            echo('</div>');
        }
    } else {
        // echo('<p>');
        // echo('Please set a valid template_id');
        // echo('</p>');
        echo('<div class="alert alert-danger">');
        echo('<p>Please set a valid template_id</p>');
        echo('</div>');
    }

    // echo('</div>');
    include_once('footer.php');

