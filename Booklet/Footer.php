<?php
    //Check for seesions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>

<html>
    <head>
        <link rel="stylesheet" href="/BookletCSS.css">
    </head>
    
    <body>
        <footer id="pageFooter">
            <p id="footer-right">© All Rights Reserved to Booklet <?php echo date("Y");?></p>
            <p id="footer-left">June</p>
        </footer>
    </body>
</html>