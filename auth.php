<?php
session_start();
require "dbconn.php";


// Auth

// Login

if(isset($_POST["login"])){
    $login_username = $_POST["username"];
    $login_password = $_POST["password"];

    $find_account = "SELECT * FROM users WHERE username = ?";
    $find_account_bind = $conn->prepare($find_account);

    if($find_account_bind){
        $find_account_bind->bind_param("s", $login_username);
        $find_account_bind->execute();

        $result = $find_account_bind->get_result();

        if($result->num_rows === 1){
            $user = $result->fetch_assoc();

            if(password_verify($login_password, $user['encrypted_password'])) {
                echo "Success";

                $_SESSION["user_id"]  = $user['id'];
                $_SESSION["username"] = $user['username'];
                $_SESSION["fullname"] = $user['fullname'];
                $_SESSION["role"] = $user["role"];

                if($_SESSION["role"] == "admin"){
                    header("Location: /ISTILO_SYSTEM/admin/admin_panel.html");
                    exit();
                }else if($_SESSION["role"] == "layout"){
                    header("Location: /ISTILO_SYSTEM/layout/layout_artist.html");
                    exit();
                }else if($_SESSION["role"] == "production"){
                    header("Location: /ISTILO_SYSTEM/production/production.html");
                    exit();
                }else if($_SESSION["role"] == "qa"){
                    header("Location: /ISTILO_SYSTEM/quality_inspect/quality_inspection.html");
                    exit();
                }else if($_SESSION["role"] == "owner"){
                    header("Location: /ISTILO_SYSTEM/owner_features/dashboard.html");
                    exit();
                }
                
            }
        }else{
            header("Location: login.html");
            exit();
        }
    }
}

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