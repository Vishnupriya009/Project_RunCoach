<?php

$serverName = "development-database.cpqaqw6bggfw.us-west-2.rds.amazonaws.com";
$userName = "renga";
$password = "rcPass1234!";
$dbName = "renga_fnfprod";

$conn = mysqli_connect($serverName, $userName, $password, $dbName);

if($conn->connect_error){
    die("connection failed:" .$conn->connection_error);
}

?>
