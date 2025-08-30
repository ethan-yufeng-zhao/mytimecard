<?php
// OS
//$ cat /etc/issue
//Ubuntu 18.04.6 LTS \n \l

// DB
//$ psql --version
//psql (PostgreSQL) 10.23 (Ubuntu 10.23-0ubuntu0.18.04.2)

//$ mysqld --version
//mysqld  Ver 5.7.42-0ubuntu0.18.04.1 for Linux on x86_64 ((Ubuntu))

//$ mysql --version
//mysql  Ver 14.14 Distrib 5.7.42, for Linux (x86_64) using  EditLine wrapper

// PDO
// $ sudo apt install php php-cli php-pdo php-pdo-mysql php-pdo-pgsql

//$ php --version
//PHP 7.2.24-0ubuntu0.18.04.17 (cli) (built: Feb 23 2023 13:29:25) ( NTS )
//Copyright (c) 1997-2018 The PHP Group
//Zend Engine v3.2.0, Copyright (c) 1998-2018 Zend Technologies
//    with Zend OPcache v7.2.24-0ubuntu0.18.04.17, Copyright (c) 1999-2018, by Zend Technologies
//    with Xdebug v2.6.0, Copyright (c) 2002-2018, by Derick Rethans

//$ python --version
//Python 2.7.17
//$ python3 --version
//Python 3.6.9
?>

<?php
try {
    $dbh = new PDO('mysql:host=localhost;dbname=test', $user, $pass);
    foreach($dbh->query('SELECT * from FOO') as $row) {
        print_r($row);
    }
    $dbh = null;
} catch (PDOException $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>

<?php
$dbh = new PDO('mysql:host=localhost;dbname=test', $user, $pass, array(
    PDO::ATTR_PERSISTENT => true
));
?>


<?php
$dbh = new PDO('mysql:host=localhost;dbname=test', $user, $pass);
// 在此使用连接
$sth = $dbh->query('SELECT * FROM foo');

// 使用完毕，关闭连接
$sth = null;
$dbh = null;
?>

<?php
try {
  $dbh = new PDO('odbc:SAMPLE', 'db2inst1', 'ibmdb2',
      array(PDO::ATTR_PERSISTENT => true));
  echo "Connected\n";
} catch (Exception $e) {
  die("Unable to connect: " . $e->getMessage());
}

try {
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $dbh->beginTransaction();
  $dbh->exec("insert into staff (id, first, last) values (23, 'Joe', 'Bloggs')");
  $dbh->exec("insert into salarychange (id, amount, changedate) 
      values (23, 50000, NOW())");
  $dbh->commit();

} catch (Exception $e) {
  $dbh->rollBack();
  echo "Failed: " . $e->getMessage();
}
?>

<?php
$stmt = $dbh->prepare("INSERT INTO REGISTRY (name, value) VALUES (:name, :value)");
$stmt->bindParam(':name', $name);
$stmt->bindParam(':value', $value);

// 插入一行
$name = 'one';
$value = 1;
$stmt->execute();

//  用不同的值插入另一行
$name = 'two';
$value = 2;
$stmt->execute();
?>


<?php
$stmt = $dbh->prepare("INSERT INTO REGISTRY (name, value) VALUES (?, ?)");
$stmt->bindParam(1, $name);
$stmt->bindParam(2, $value);

// 插入一行
$name = 'one';
$value = 1;
$stmt->execute();

// 用不同的值插入另一行
$name = 'two';
$value = 2;
$stmt->execute();
?>



<?php
$stmt = $dbh->prepare("SELECT * FROM REGISTRY where name = ?");
$stmt->execute([$_GET['name']]);
foreach ($stmt as $row) {
  print_r($row);
}
?>

<?php
$stmt = $dbh->prepare("CALL sp_returns_string(?)");
$stmt->bindParam(1, $return_value, PDO::PARAM_STR, 4000);

// 调用存储过程
$stmt->execute();

print "procedure returned $return_value\n";
?>

<?php
$stmt = $dbh->prepare("CALL sp_takes_string_returns_string(?)");
$value = 'hello';
$stmt->bindParam(1, $value, PDO::PARAM_STR|PDO::PARAM_INPUT_OUTPUT, 4000);

// 调用存储过程
$stmt->execute();

print "procedure returned $value\n";
?>

<?php
$stmt = $dbh->prepare("SELECT * FROM REGISTRY where name LIKE '%?%'");
$stmt->execute([$_GET['name']]);

// 占位符必须被用在整个值的位置
$stmt = $dbh->prepare("SELECT * FROM REGISTRY where name LIKE ?");
$stmt->execute(["%$_GET[name]%"]);
?>

<?php
$dsn = 'mysql:dbname=testdb;host=127.0.0.1';
$user = 'dbuser';
$password = 'dbpass';

$dbh = new PDO($dsn, $user, $password);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// This will cause PDO to throw a PDOException (when the table doesn't exist)
$dbh->query("SELECT wrongcolumn FROM wrongtable");
?>

<?php
$dsn = 'mysql:dbname=test;host=127.0.0.1';
$user = 'googleguy';
$password = 'googleguy';

$dbh = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

// 这里将导致 PDO 抛出一个 E_WARNING 级别的错误，而不是 一个异常 （当数据表不存在时）
$dbh->query("SELECT wrongcolumn FROM wrongtable");
?>

<?php
$db = new PDO('odbc:SAMPLE', 'db2inst1', 'ibmdb2');
$stmt = $db->prepare("select contenttype, imagedata from images where id=?");
$stmt->execute(array($_GET['id']));
$stmt->bindColumn(1, $type, PDO::PARAM_STR, 256);
$stmt->bindColumn(2, $lob, PDO::PARAM_LOB);
$stmt->fetch(PDO::FETCH_BOUND);

header("Content-Type: $type");
fpassthru($lob);
?>

<?php
$db = new PDO('odbc:SAMPLE', 'db2inst1', 'ibmdb2');
$stmt = $db->prepare("insert into images (id, contenttype, imagedata) values (?, ?, ?)");
$id = get_new_id(); // 调用某个函数来分配一个新 ID

// 假设处理一个文件上传
// 可以在 PHP 文档中找到更多的信息

$fp = fopen($_FILES['file']['tmp_name'], 'rb');

$stmt->bindParam(1, $id);
$stmt->bindParam(2, $_FILES['file']['type']);
$stmt->bindParam(3, $fp, PDO::PARAM_LOB);

$db->beginTransaction();
$stmt->execute();
$db->commit();
?>

<?php
$db = new PDO('oci:', 'scott', 'tiger');
$stmt = $db->prepare("insert into images (id, contenttype, imagedata) " .
"VALUES (?, ?, EMPTY_BLOB()) RETURNING imagedata INTO ?");
$id = get_new_id(); // 调用某个函数来分配一个新 ID

// 假设处理一个文件上传
// 可以在 PHP 文档中找到更多的信息

$fp = fopen($_FILES['file']['tmp_name'], 'rb');

$stmt->bindParam(1, $id);
$stmt->bindParam(2, $_FILES['file']['type']);
$stmt->bindParam(3, $fp, PDO::PARAM_LOB);

$db->beginTransaction();
$stmt->execute();
$db->commit();
?>


//sqlie
<?php
// ....
$stmt = $PDO->prepare('SELECT * FROM `X` WHERE `TimeUpdated`+?>?');
$stmt->execute([3600, time()]);
$data = $stmt->fetchAll();
print_r($data);
?>

//mysql

<?php
$pdo = new PDO("mysql:host=localhost;dbname=world", 'my_user', 'my_password');
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

$unbufferedResult = $pdo->query("SELECT Name FROM City");
foreach ($unbufferedResult as $row) {
    echo $row['Name'] . PHP_EOL;
}
?>
