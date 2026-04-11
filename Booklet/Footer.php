<?php
    //Check for seesions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>

<html>
    <head>
        <style>
            #pageFooter{
                background: #483434;
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 2rem;
                min-height: 50px;
                margin-top: auto;
            }

            #footer-left, #footer-right{
                font-size: 1rem;
                color: #FFF7EE;
            }
         </style>
    </head>
    
    <body>
        <footer id="pageFooter">
            <p id="footer-right">© All Rights Reserved to Booklet <?php echo date("Y");?></p>
            <p id="footer-left">June</p>
        </footer>
    </body>
</html>