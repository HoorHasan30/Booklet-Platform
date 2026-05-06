<?php
    //Check for sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    function loadNavBar(){
        if (isset($_SESSION["role"])) {

            if ($_SESSION["role"] == "Admin") {
                include("AdminNavBar.php");
            }
            elseif ($_SESSION["role"] == "Creator") {
                include("CreatorNavBar.php");
            }
        } 
        else {
            include("VisitorNavBar.php");
        }
    }     
?>

<html>
    <head>
        <title>About Us</title>
        <link rel="stylesheet" href="/BookletCSS.css">
    </head>
    
    <body>
        <!--Show navBar according to the user role-->
        <?php loadNavBar();?>
        
        <div class="about">
            
            <div class="logoBackground">
                <img class="aboutLogo" src="images/Logo.png">
            </div>
        
            <div class="about-content">
                <h1>About us</h1>
                
                <p>We’re a platform where readers can discover books, explore reviews, and share their opinions.
                   <br>Our goal is to create a simple, enjoyable reading community for everyone.</p>
                
                <div class="contactInfo">
                    
                    <div class="contact-Item">
                        <img class="contactImg" src="images/mailIcon.png">
                        <p>
                            <a href="mailto:Booklet8415@gmail.com" id="emailTo">Booklet8415@gmail.com</a>
                        </p>
                    </div>
                    
                    <div class="contact-Item">
                        <img class="contactImg" src="images/InstagramIcon.png">
                        <p>Booklet</p>
                    </div>
                    
                </div>
            </div>
        </div>
       
        <?php include("Footer.php");?>
    </body>
</html>
