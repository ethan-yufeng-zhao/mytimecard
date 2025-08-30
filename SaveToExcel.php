<?php
    try{
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$_POST['filename'].'"');
        header('Cache-Control: max-age=0');
        header("Pragma: public");
        //echo(str_replace('�', '', strip_tags($_POST['dataToDisplay'], '<table><thead><th><tr><td><tbody>')));

        setlocale(LC_ALL, 'en_US.UTF8');
        $mycontent = mb_convert_encoding($_POST['dataToDisplay'], "HTML-ENTITIES", "UTF-8"); // Converts unusual symbols to the html entities

        $mycontent = strip_tags($mycontent, '<table><thead><th><tr><td><tbody>'); //removes everything but these tags

        $mycontent = str_replace('&raquo;', '', $mycontent);  //We want to remove these not convert them for this
        $mycontent = str_replace('&#2013266107;', '', $mycontent);  //We want to remove these not convert them for this

        // Convert the rest to appropriate characters for excel
        $mycontent = str_replace('&quot;', '"', $mycontent);
        $mycontent = str_replace('&amp;', '&', $mycontent);
        $mycontent = str_replace('&amp;', '&', $mycontent);
        $mycontent = str_replace('&apos;', "'", $mycontent);
        $mycontent = str_replace('&lt;', '<', $mycontent);
        $mycontent = str_replace('&gt;', '>', $mycontent);
        $mycontent = str_replace('&nbsp;', ' ', $mycontent);
        $mycontent = str_replace('&iexcl;', 'i', $mycontent);
        $mycontent = str_replace('&cent;', 'c', $mycontent);
        $mycontent = str_replace('&brvbar;', '|', $mycontent);
        $mycontent = str_replace('&uml;', '...', $mycontent);
        $mycontent = str_replace('&copy;', 'c', $mycontent);
        $mycontent = str_replace('&ordf;', 'a', $mycontent);
        $mycontent = str_replace('&laquo;', '<<', $mycontent);
        $mycontent = str_replace('&shy;', ' ', $mycontent);
        $mycontent = str_replace('&plusmn;', '+-', $mycontent);
        $mycontent = str_replace('&acute;', "'", $mycontent);
        $mycontent = str_replace('&raquo;', '>>', $mycontent);
        $mycontent = str_replace('&frac14;', '1/4', $mycontent);
        $mycontent = str_replace('&frac12;', '1/2', $mycontent);
        $mycontent = str_replace('&frac34;', '3/4', $mycontent);
        $mycontent = str_replace('&circ;', '^', $mycontent);
        $mycontent = str_replace('&tilde;', '~', $mycontent);
        $mycontent = str_replace('&ensp;', ' ', $mycontent);
        $mycontent = str_replace('&emsp;', ' ', $mycontent);
        $mycontent = str_replace('&thinsp;', ' ', $mycontent);
        $mycontent = str_replace('&zwnj;', ' ', $mycontent);
        $mycontent = str_replace('&zwj;', ' ', $mycontent);
        $mycontent = str_replace('&lrm;', ' ', $mycontent);
        $mycontent = str_replace('&rlm;', ' ', $mycontent);
        $mycontent = str_replace('&ndash;', '-', $mycontent);
        $mycontent = str_replace('&mdash;', '-', $mycontent);
        $mycontent = str_replace('&lsquo;', "'", $mycontent);
        $mycontent = str_replace('&rsquo;', "'", $mycontent);
        $mycontent = str_replace('&sbquo;', "'", $mycontent);
        $mycontent = str_replace('&ldquo;', '"', $mycontent);
        $mycontent = str_replace('&rdquo;', '"', $mycontent);
        $mycontent = str_replace('&bdquo;', '"', $mycontent);
        $mycontent = str_replace('&dagger;', '?', $mycontent);
        $mycontent = str_replace('&Dagger;', '?', $mycontent);
        $mycontent = str_replace('&bull;', '*', $mycontent);
        $mycontent = str_replace('&hellip;', '...', $mycontent);
        $mycontent = str_replace('&permil;', '%', $mycontent);
        $mycontent = str_replace('&prime;', "'", $mycontent);
        $mycontent = str_replace('&Prime;', '"', $mycontent);
        $mycontent = str_replace('&lsaquo;', '(', $mycontent);
        $mycontent = str_replace('&rsaquo;', ')', $mycontent);
        $mycontent = str_replace('&oline;', '-', $mycontent);
        $mycontent = str_replace('&frasl;', '/', $mycontent);

        $mycontent = str_replace('||||', ' -- ', $mycontent); // for the way that labels are being displayed using tablesorter
    } catch( Exception $e ) {
        logit("Fail to save the excel file : " . $e->getMessage());
        $mycontent = '';
    }
?>

<html>
<head></head>
<body><?php echo($mycontent); ?></body>
</html>