<?php
session_start();

require "../logout.php";

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "layout") {
    header("Location: /ISTILO_SYSTEM/Login/html/login.php");
    exit();
}

header("Location: layout_work.php");
exit();
