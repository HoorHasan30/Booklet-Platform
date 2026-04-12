<?php
    session_start();

    session_unset();   // remove all session variables
    session_destroy(); // destroy session

    header("Location: Home.php"); // redirect after logout
    exit();
?>
