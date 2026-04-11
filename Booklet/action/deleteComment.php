<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

if(isset($_POST['commentId']) && $_SESSION['role'] == 'Admin'){

    $commentId = $_POST['commentId'];

    mysqli_query($dbc, "DELETE FROM dbProj_comments WHERE commentId = $commentId");

    header("Location: " . $_SERVER['HTTP_REFERER']);
}
?>