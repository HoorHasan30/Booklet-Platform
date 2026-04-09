<?php
include("../DBConnection.php");
$dbc = getConnection();

$id = $_POST['reviewId'];

mysqli_query($dbc,"DELETE FROM dbProj_reviews WHERE reviewId = $id");

header("Location: ../bookDetails.php?id=".$_GET['id']);
?>