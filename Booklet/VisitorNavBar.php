<?php
    //Check for sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>

<html>
    <head>
        <title>Vistitor Navbar</title>
        <link rel="stylesheet" href="BookletCSS.css">
    </head>
    
    <body>
        <nav> 
            <img class="logo" src="images/Logo.png" alt="Booklet Logo">
            
            <ul class="nav-links">
                <li><a href="">Home</a></li>
                <li><a href="">All Books</a></li>
                <li><a href="AboutUs.php">About Us</a></li>
            </ul>
            
            <div class="navBtn">
                <a href="Login.php"><button class="LoginRegBtn">Register/Login</button></a>
            </div>
        </nav>
    </body>
</html>

