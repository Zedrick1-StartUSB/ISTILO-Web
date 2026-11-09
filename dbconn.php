<?php
$hostname = "localhost";
$username = "root";
$password = "";
$dbname = "istilo_system";

$conn = new mysqli($hostname, $username, $password, $dbname);

if($conn->connect_error){
    die("Connection Failed" . $conn->connect_error);
}else{
    // echo "Successful Connection";
}


?>