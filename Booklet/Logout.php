<?php
    session_start();

    session_unset();   // remove all session variables
    session_destroy(); // destroy session

    header("Location: Login.php"); // redirect after logout
    exit();
?>
