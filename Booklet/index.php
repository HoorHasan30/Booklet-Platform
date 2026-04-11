<?php
    session_start();

    session_unset();   // remove all session variables
    session_destroy(); // destroy session

    header("Location: Login.php"); // redirect after logout
    exit();
?>

<!DOCTYPE html>
<html>
    <head>
        
    </head>
    
    <body>

        <?php include("VisitorNavBar.php"); ?>

        <h1>Welcome to Booklet</h1>

        <?php include("Footer.php"); ?>
    </body>
</html>