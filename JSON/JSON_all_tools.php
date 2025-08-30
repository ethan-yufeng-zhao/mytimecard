<?php
// JSON/JSON_all_templates.php
require_once('..'.DIRECTORY_SEPARATOR.'base.php');
require_once('..'.DIRECTORY_SEPARATOR.'DB'.DIRECTORY_SEPARATOR.'db.php');

$arr = array();

$querystring = '';
$db_pdo = db_connect();

if ($GLOBALS['DB_TYPE'] == 'pgsql') {
	$querystring = 'select * from tcs.entity e order by e.nent_entity, e.nent_entity_type, e.nent_ent_location';
} else { // mysql
	$querystring='';
}
$db_arr = db_query($db_pdo, $querystring);

foreach ($db_arr as $key => $data ) {
	$temp_arr = array();
	$temp_arr['tool_name'] = $data['nent_entity'];
	$temp_arr['tool_type'] = $data['nent_entity_type'];
	$temp_arr['tool_location'] = $data['nent_ent_location'];
	$arr[$temp_arr['tool_name']] = $temp_arr;
	unset($temp_arr);
}
// Close connection to DB
$db_pdo = null;

header('Content-Type: application/json');
echo(json_encode($arr));

