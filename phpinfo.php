<?php
if (extension_loaded('pdo_pgsql')) {
    echo "pdo pgsql is enabled<br>";
} else {
    echo "pdo pgsql is not enabled<br>";
}
if (extension_loaded('pdo_informix')) {
    echo "pdo informix is loaded.<br>";
} else {
    echo "pdo informix is not loaded.<br>";
}

$currentTimezone = date_default_timezone_get();
$currentTime = date('Y-m-d H:i:s');  // Adjust the format as needed

echo "Current Timezone: $currentTimezone<br>";
echo "Current Time: $currentTime<br>";
echo "REQUEST URI: ".$_SERVER['REQUEST_URI']."<br>";
$url = $_SERVER['REQUEST_SCHEME'] .'://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
echo $url."<br>";

$to = "knights@catwomen.jfab.aosmd.com";
$subject = "Test email from ".gethostname()." PHP(".phpversion().")";
$message = "This is a test email sent using PHP mail: ".$url;
$headers = "From: yufeng.zhao@sh.jfab.aosmd.com";

if (mail($to, $subject, $message, $headers)) {
    echo "Email sent successfully!";
} else {
    echo "Email failed to send.";
}

phpinfo();

