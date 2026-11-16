<?php
session_start();
require "dbconn.php";


// Auth

if(isset($_SESSION["username"]) && isset($_SESSION["role"])){ // Bawal bumalik if hindi naka logout

    if($_SESSION["role"] == "admin"){
        header("Location: /ISTILO_SYSTEM/admin/admin_panel.php");
        exit();
    }else if($_SESSION["role"] == "layout"){
        header("Location: /ISTILO_SYSTEM/layout/layout_work.php");
        exit();
    }else if($_SESSION["role"] == "production"){
        header("Location: /ISTILO_SYSTEM/production/production.php");
        exit();
    }else if($_SESSION["role"] == "qa"){
        header("Location: /ISTILO_SYSTEM/quality_inspect/quality_inspection.php");
        exit();
    }else if($_SESSION["role"] == "owner"){
        header("Location: /ISTILO_SYSTEM/owner_features/dashboard.php");
        exit();
    }
}

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
                    header("Location: /ISTILO_SYSTEM/admin/admin_panel.php");
                    exit();
                }else if($_SESSION["role"] == "layout"){
                    header("Location: /ISTILO_SYSTEM/layout/layout_work.php");
                    exit();
                }else if($_SESSION["role"] == "production"){
                    header("Location: /ISTILO_SYSTEM/production/production.php");
                    exit();
                }else if($_SESSION["role"] == "qa"){
                    header("Location: /ISTILO_SYSTEM/quality_inspect/quality_inspection.php");
                    exit();
                }else if($_SESSION["role"] == "owner"){
                    header("Location: /ISTILO_SYSTEM/owner_features/dashboard.php");
                    exit();
                }
            }
        }else{
            header("Location: login.php");
            exit();
        }
    }
}

// Sign up
$signup_error = "";
$signup_fullname = "";
$signup_username = "";
$signup_department = "";
$signup_role = "";

if (isset($_POST["signup"])) {
    $signup_fullname = is_string($_POST["fullname"] ?? null) ? trim($_POST["fullname"]) : "";
    $signup_username = is_string($_POST["username"] ?? null) ? trim($_POST["username"]) : "";
    $password = is_string($_POST["password"] ?? null) ? $_POST["password"] : "";
    $confirmPassword = is_string($_POST["confirm-password"] ?? null) ? $_POST["confirm-password"] : "";
    $signup_department = is_string($_POST["department"] ?? null)
        ? strtolower(trim($_POST["department"]))
        : "";
    $signup_role = is_string($_POST["role"] ?? null)
        ? strtolower(trim($_POST["role"]))
        : "";

    $departmentRoles = [
        "layout" => "layout",
        "production" => "production",
        "qa" => "qa",
    ];

    if ($signup_fullname === "" || $signup_username === "" || $password === "" || $confirmPassword === "") {
        $signup_error = "Complete all required fields.";
    } elseif (strlen($signup_fullname) > 255 || strlen($signup_username) > 255) {
        $signup_error = "Full name and username must be 255 characters or fewer.";
    } elseif (!isset($departmentRoles[$signup_department])) {
        $signup_error = "Admin accounts require an invitation. Choose Layout, Production, or QA.";
    } elseif ($signup_role !== $departmentRoles[$signup_department]) {
        $signup_error = "Role must match the selected department: " . $departmentRoles[$signup_department] . ".";
    } elseif ($password !== $confirmPassword) {
        $signup_error = "Password and confirmation do not match.";
    } else {
        try {
            $findUser = $conn->prepare("SELECT `id` FROM `users` WHERE `username` = ? LIMIT 1");
            if ($findUser === false) {
                throw new mysqli_sql_exception($conn->error);
            }
            $findUser->bind_param("s", $signup_username);
            if (!$findUser->execute()) {
                throw new mysqli_sql_exception($findUser->error);
            }
            $findUser->store_result();
            $usernameExists = $findUser->num_rows > 0;
            $findUser->close();

            if ($usernameExists) {
                $signup_error = "That username is already in use.";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                if ($hashedPassword === false) {
                    throw new RuntimeException("Password hashing failed.");
                }

                $insertUser = $conn->prepare(
                    "INSERT INTO `users` (`fullname`, `username`, `department`, `role`, `encrypted_password`) "
                    . "VALUES (?, ?, ?, ?, ?)"
                );
                if ($insertUser === false) {
                    throw new mysqli_sql_exception($conn->error);
                }
                $insertUser->bind_param(
                    "sssss",
                    $signup_fullname,
                    $signup_username,
                    $signup_department,
                    $signup_role,
                    $hashedPassword
                );
                if (!$insertUser->execute()) {
                    throw new mysqli_sql_exception($insertUser->error);
                }
                $insertUser->close();

                header("Location: /ISTILO_SYSTEM/Login/html/login.php");
                exit();
            }
        } catch (mysqli_sql_exception $exception) {
            error_log("Unable to create user account: " . $exception->getMessage());
            $signup_error = "The account could not be created. Check the users table and try again.";
        } catch (RuntimeException $exception) {
            error_log("Unable to create user account: " . $exception->getMessage());
            $signup_error = "The account could not be created. Try again.";
        }
    }
}
?>