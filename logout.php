<?php

if(isset($_POST["logout"])){
    session_unset();
    session_destroy();
    header("Location: \ISTILO_SYSTEM\Login\html\login.php");
}


?>