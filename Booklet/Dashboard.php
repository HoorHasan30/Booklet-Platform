<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function loadNavBar() {
    if (isset($_SESSION["role"])) {
        if ($_SESSION["role"] == "Admin") {
            include("AdminNavBar.php");
        } elseif ($_SESSION["role"] == "Creator") {
            include("CreatorNavBar.php");
        } else {
            include("VisitorNavBar.php");
        }
    } else {
        include("VisitorNavBar.php");
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Page</title>
    <link rel="stylesheet" href="BookletCSS.css">
</head>
<body>

<?php loadNavBar(); ?>

<!-- EMPTY WHITE PAGE -->
<div style="height: 80vh;"></div>

<?php include("Footer.php"); ?>

</body>
</html>