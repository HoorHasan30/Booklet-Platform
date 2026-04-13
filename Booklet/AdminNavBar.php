<?php
    //Check for sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    function getUserInfo() {
                if (isset($_SESSION["firstName"]) && isset($_SESSION["lastName"])) {
                    return $_SESSION["firstName"] . " " . $_SESSION["lastName"] . " (" . $_SESSION["role"] . ")";
                }
                return "UnKnown";
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
        </script>
    </head>
    
    <body>
        <nav> 
            <img class="logo" src="images/Logo.png" alt="Booklet Logo">
            
            <ul class="nav-links2">
                <li><a>Dashboard</a></li>
                <li><a href="Home.php">Home</a></li>
                <li><a href="AllBooks.php">All Books</a></li>
                <li><a href="">All Users</a></li>
                <li><a href="AboutUs.php">About Us</a></li>
                
                <li class="userInfo" onclick="toggleMenu()">🕮 <?php echo getUserInfo(); ?>
                    <ul class="Logout">
                        <li><a href="Logout.php">Logout</a></li>
                    </ul>
                </li>
                
            </ul>
            
        </nav>
    </body>
</html>

