<?php
    session_start();

    //remove all session variables
    session_unset();

    //destroy
    session_destroy();

    //redirect to login.php
    header('location:..\login.php');
    exit;
?>