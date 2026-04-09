<?php
include("../DBConnection.php");
$dbc = getConnection();

$id = $_POST['commentId'];

mysqli_query($dbc,"DELETE FROM dbProj_comments WHERE commentId = $id");

header("Location: ".$_SERVER['HTTP_REFERER']);
?>