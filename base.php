<?php

	//error reporting
	error_reporting(E_ALL|E_STRICT);
	ini_set("display_errors", "on");
    ini_set("memory_limit","-1"); //some memory alloc issue when decoding big json

//    ini_set('session.use_trans_sid', "1");
//    ini_set('session.use_cookies', "0");
//    ini_set('session.use_only_cookies', "0");

	date_default_timezone_set('America/Los_Angeles');
    // date_default_timezone_set('UTC'); // For working with the Unix timestamps

    // allow requests
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    $start_time = microtime(true);

// session
// session_start();
//    if (!isset($_SESSION['DB_TYPE'])) {
//        $_SESSION['DB_TYPE'] = 'pgsql';
//    }
//    db_switch($_SESSION['DB_TYPE']);

//    $lifetime=600;
    //session_start();
    //setcookie(session_name(),session_id(),time()+$lifetime);

    require_once "const.php";

//    $myurl = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
//    if ($_SERVER["SERVER_PORT"] != "80"){
//        $myurl .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["SCRIPT_NAME"];
//    } else {
//        $myurl .= $_SERVER["SERVER_NAME"].$_SERVER["SCRIPT_NAME"];
//    }
//    $mybaseurl = dirname($myurl);
//    logit('$myurl = '.$myurl);
//    logit('$mybaseurl = '.$mybaseurl);

//    $HTTP_OR_HTTPS = $_SERVER['REQUEST_SCHEME'];
//    $HTTP_HOST = $_SERVER["HTTP_HOST"];
//    $HTTP_PORT = $_SERVER['SERVER_PORT'];

    $mybaseurl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["HTTP_HOST"].'/'.WWW_PATH;
    //$mybaseurl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["HTTP_HOST"].':'.$_SERVER['SERVER_PORT'].'/'.WWW_PATH;
    //$mybaseurl = 'http://192.168.10.88:80/'.WWW_PATH; // for xdebug
    //$HTTP_ROOTURL = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER["HTTP_HOST"].':'.$_SERVER['SERVER_PORT'];
    // HTTP_HOST = SERVER_NAME:SERVER_PORT
    $HTTP_ROOTURL = dirname($mybaseurl);
    define("HTTP_BASEURL", $mybaseurl);

    function session_clean(){
        session_start();
        $_SESSION = array();
        if(isset($_COOKIE[session_name()])) {
            setcookie(session_name(),'',time()-3600, '/');
        }
        session_destroy();
    }

    function getContextCookies()
    {
        if (!isset($_COOKIE['DB_TYPE'])) {
            $db_type = 'pgsql';
        } else {
            $db_type = $_COOKIE['DB_TYPE'];
        }
        // Create a stream
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"Accept-language: en\r\n" .
                    "Cookie: DB_TYPE=". $db_type."\r\n"
            ),
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        );

        return stream_context_create($opts);
    }

    function set_the_cookies()
    {
        // cookie
        if (!isset($_COOKIE['DB_TYPE'])) {
            setcookie("DB_TYPE", "pgsql");//, time()+20*24*60*60);
        }
    }

    function db_switch(){
        if (!isset($_COOKIE['DB_TYPE'])){
            $which_db = 'pgsql';
        } else {
            $which_db = $_COOKIE['DB_TYPE'];
        }
        if ($which_db == 'pgsql') {
            $GLOBALS['DB_TYPE'] = 'pgsql';
            if (DEBUG) {
                $GLOBALS['DB_HOST'] = 'h2otest-vip';
            } else {
                $GLOBALS['DB_HOST'] = 'h2o';
            }
            $GLOBALS['DB_NAME'] = 'h2o';
            $GLOBALS['DB_USERNAME'] = 'tcs'; //postgres
            $GLOBALS['DB_PASSWORD'] = '1e9451afa8de4ec7272087866c07ad12'; //?
        } else {
            $GLOBALS['DB_TYPE'] = 'mysql';
            $GLOBALS['DB_HOST'] = 'jfabtcs1';
            $GLOBALS['DB_NAME'] = 'tcs';
            $GLOBALS['DB_USERNAME'] = 'root';
            $GLOBALS['DB_PASSWORD'] = 't0rtur3d';
        }
    }

    function logit($msg, $defautlhead=' => '){
        $file = $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR.WWW_PATH.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'tcs_'.date("Y-m-d").".log";
        // xdebug //privilege issue !!
        //$file = '/var/www/html'.DIRECTORY_SEPARATOR.WWW_PATH.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'tcs_'.date("Y-m-d").".log";
        file_put_contents($file, date("Y-m-d H:i:s").$defautlhead.$msg."\n",FILE_APPEND);
    }

    //logit('[REQ] '.$_SERVER["SCRIPT_FILENAME"]);
    //logit($HTTP_ROOTURL.$_SERVER["PHP_SELF"], '  [REQ] ');

    function request_ldap_api($req_url){
        $api_url = 'http://jfabweb2.jfab.aosmd.com/ldap'.$req_url;
        logit($api_url);
        return $api_url;
    }

    function request_json_api($req_url){
        $api_url = HTTP_BASEURL.$req_url;
        logit($api_url);
        return $api_url;
    }

    //ends with
    function endsWith($str, $suffix)
    {
        // Ensure both are strings
        if (!is_string($str) || !is_string($suffix)) {
            return false; // or throw an exception if that's better for your logic
        }

        $length = strlen($suffix);
        if ($length === 0) {
            return true;
        }

        return substr($str, -$length) === $suffix;
    }

//    function getHttpsContents($url) {
//        $options = [
//            'ssl' => [
//                'verify_peer' => false,
//                'verify_peer_name' => false,
//            ],
//        ];
//        $context  = stream_context_create($options);
//        file_get_contents($url, false, getContextCookies())
//        $response = file_get_contents($url, false, $context);
//        $response_header = $http_response_header[0];
//        preg_match('{HTTP\/\S*\s(\d{3})}', $http_response_header[0], $match);
//        $status = $match[1];
//        if ($status !== '200') {
//            throw new RuntimeException($response_header);
//        }
//        return $response;
//    }

require_once "func.php";

db_switch();
