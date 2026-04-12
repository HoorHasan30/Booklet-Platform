<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

$commentId = intval($_POST['commentId']);

mysqli_query($dbc, "DELETE FROM dbProj_comments WHERE commentId = $commentId");

header("Location: " . $_SERVER['HTTP_REFERER']);
?>