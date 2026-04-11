<?php
    //Check for seesions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
?>

<html>
    <head>
        <link rel="stylesheet" href="BookletCSS.css">
        
        <script>
            function toggleMenu() {
                let menu = document.querySelector(".Logout");
                menu.style.display = (menu.style.display === "block") ? "none" : "block";
            }
            
            /*function getUserInfo() {
                if (isset($_SESSION["firstName"]) && isset($_SESSION["lastName"])) {
                    return $_SESSION["firstName"] . " " . $_SESSION["lastName"] . " (" . $_SESSION["role"] . ")";
                }
                return "UnKnown";
            }echo getUserInfo(); */
        </script>
    </head>
    
    <body>
        <nav> 
            <img class="logo" src="images/Logo.png" alt="Booklet Logo">
            
            <ul class="nav-links2">
                <li><a href="">Home</a></li>
                <li><a href="">All Books</a></li>
                <li><a href="">My Books</a></li>
                <li><a href="">About Us</a></li>
                
                <li class="userInfo" onclick="toggleMenu()">🕮 Hoor Hasan - Creator
                    <ul class="Logout">
                        <li><a href="Logout.php">Logout</a></li>
                    </ul>
                </li>
                
            </ul>
            
        </nav>
    </body>
</html>

