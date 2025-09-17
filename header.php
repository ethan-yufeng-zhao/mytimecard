<?php
// header.php
include_once('base.php');
set_the_cookies();
include('auth.php');
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