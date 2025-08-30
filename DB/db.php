<?php
	// DB/db.php

    function db_connect(){
        try{
            $pdo_sql = new PDO($GLOBALS['DB_TYPE'].':host='.$GLOBALS['DB_HOST'].';dbname='.$GLOBALS['DB_NAME'], $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD'], array(PDO::ATTR_PERSISTENT => true));
            $pdo_sql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            return($pdo_sql);
        } catch( PDOException $e ) {
            logit("Unable to connect: " . $e->getMessage());
//            exit($e->getMessage());
            die("Unable to connect: " . $e->getMessage());
        }
    }

//    function db_close(){
//        // Close connection to DB
//        //unset($pdo);
//        $pdo_sql = null;
//    }

    function db_query($pdo, $querystring, $params=[]){
        logit($querystring);
        $sth_sql = $pdo->prepare($querystring);
        if ($params){
            foreach ($params as $k => $v ) {
                $sth_sql->bindParam($k, $v);
            }
        }
//        $sth_sql->bindParam(':name', $name);
//        $sth_sql->bindParam(':value', $value);
        $sth_sql->execute();
        $db_arr = $sth_sql->fetchall(PDO::FETCH_ASSOC);
//        $data = $stmt->fetchAll();
//        print_r($data);
        $sth_sql = null;
        return($db_arr);
    }

    function db_update($pdo, $updatestring, $params=[]): bool
    {
        logit($updatestring);
        $sth_sql = $pdo->prepare($updatestring);
        if ($params){
            foreach ($params as $k => $v ) {
                $sth_sql->bindParam($k, $v);
            }
        }
//        $sth_sql->bindParam(':name', $name);
//        $sth_sql->bindParam(':value', $value);
        if ($sth_sql->execute()){
            $sth_sql = null;
            return true;
        } else {
            $sth_sql = null;
            return false;
        }
    }

    function db_insert($pdo, $insertstring, $params=[]): bool
    {
        logit($insertstring);
        $sth_sql = $pdo->prepare($insertstring);
        if ($params){
            foreach ($params as $k => $v ) {
                $sth_sql->bindParam($k, $v);
            }
        }
//        $sth_sql->bindParam(':name', $name);
//        $sth_sql->bindParam(':value', $value);
        if ($sth_sql->execute()){
            $sth_sql = null;
            return true;
        } else {
            $sth_sql = null;
            return false;
        }
    }