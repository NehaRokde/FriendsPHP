<?php

$dbh = "mysql:host=localhost; dbname=friendstor";
$username = "root";
$password = "";

try{
    $pdo = new PDO($dbh, $username, $password);
}catch(Exception $e){
    echo json_encode(array('status' => 500, 'message'=>'Database connection error'));
    die();
}

?>  