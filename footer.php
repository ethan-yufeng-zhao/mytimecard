</div>

<div class="container" id="footer" style="text-align: center;">
<?php
$end_time = microtime(true);
$execution_time = round($end_time - $start_time, 2);

//$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
//$url = urlencode($protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
$APP_HOST = $_SERVER["SERVER_NAME"] ? $_SERVER["SERVER_NAME"] : $_SERVER["HTTP_HOST"];
$APP_PORT = $_SERVER["SERVER_PORT"] == 80 ? '' : ':'.$_SERVER["SERVER_PORT"];
$url = urlencode($_SERVER['REQUEST_SCHEME'].'://'.$APP_HOST.$APP_PORT.$_SERVER['REQUEST_URI']);

$currentYear = date('Y');

echo "Loaded: <b>$execution_time</b>s";
if (isset($counter)) {
    echo " | Rows: <b>$counter</b>";
} else if (isset($count)) {
    echo " | Rows: <b>$count</b>";
}
$current_time = new DateTime();
$refresh_time = convertTime($current_time);
//$refresh_time = date('Y-m-d H:i:s', time());
echo " | Refreshed: <b>".$refresh_time."</b>"; // microtime has more precision, time is enough
echo " | Ver: <b>1.7#01/16/2025</b>";
if (isset($remoteUser)) {
    $current_user = $remoteUser . '@' . $remoteDomain . '#' . $remoteWorkstation;
    echo " | User: <b>".$current_user."</b>";
}

echo "<br>BACKEND: <b>".($APP_HOST?extractSubdomainOrIP($APP_HOST):'')."</b>";
echo " | DB: <b>".($GLOBALS['DB_HOST']??$APP_HOST)."</b>";
echo " | <a href='mailto:ethan.zhao@jfab.aosmd.com?subject=".$url."'>Report Issue</a>";
echo " | &#169; JFAB (<b>$currentYear</b>)";
//echo "Page Viewed: <strike>N/A</strike>";

echo "<br>PHP: <b>".phpversion()."</b>";
if (file_exists('/.dockerenv')) {
    $dockerVersion = shell_exec('docker --version');
// Check if the command was successful and output the result
    if ($dockerVersion) {
        echo " | Docker(V): <b>" . $dockerVersion ."</b>";
    } else {
        echo " | Docker(V): <b>Y</b>";
    }
} else {
    $dockerVersion = shell_exec('docker --version');
    // Check if the command was successful and output the result
    if ($dockerVersion) {
        // Use a regular expression to extract the version number
        preg_match('/(\d+\.\d+\.\d+)/', $dockerVersion, $versionMatches);
        $version = $versionMatches[0] ?? 'unknown'; // Default to 'unknown' if not found
        echo " | Docker(H): <b>" . $version."</b>";
    } else {
        echo " | Docker(H): <b>N/A</b>";
    }

    // Execute the command to get Git version
    $gitVersion = shell_exec('git --version');
    // Check if the command was successful and output the result
    if ($gitVersion) {
        // Use a regular expression to extract the version number
            preg_match('/(\d+\.\d+\.\d+)/', $gitVersion, $versionMatches);
            $version = $versionMatches[0] ?? 'unknown'; // Default to 'unknown' if not found

        echo " | Git: <b>" . $version."</b>";
    } else {
        echo " | Git: <b>N/A</b>";
    }
}

// Execute the command to get Ubuntu version
$osVersion = shell_exec('cat /etc/os-release');
// Check if the command was successful and output the result
if ($osVersion) {
    // Use a regular expression to extract the version number
    preg_match('/NAME="([^"]+)"/', $osVersion, $nameMatches);
//        preg_match('/VERSION="([^"]+)"/', $osVersion, $versionMatches);
    $osname = $nameMatches[1] ?? 'N/A';
//        $version = $versionMatches[1] ?? 'N/A'; // Default to 'unknown' if not found

    echo " | OS: <b>" . $osname."</b>";
} else {
    echo " | OS: <b>" . php_uname("s")."</b>";
}

?>
</div>

</body>
</html>
