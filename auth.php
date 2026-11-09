<?php
session_start();
require "dbconn.php";


// Auth

// Login

// SignUp

if(isset($_POST["signup"])){
    $fullname = $_POST["fullname"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm-password"];

    // hashing password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    //storing to database

    $sql_insert = "INSERT INTO users(fullname, username, encrypted_password) VALUES(?,?,?)";
    $sql_bind = $conn->prepare($sql_insert);

    if($sql_bind){

        $sql_bind->bind_param("sss", $fullname, $username, $hashed_password);
        $sql_bind->execute();
    }
    header("Location: login.php");
    exit();
}
?>