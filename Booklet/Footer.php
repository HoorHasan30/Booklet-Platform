<?php
    //Check for sessions
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
            <p id="footer-text">© All Rights Reserved to Booklet <?php echo date("Y");?></p>
        </footer>
    </body>
</html>