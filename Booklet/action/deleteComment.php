<?php
include("../DBConnection.php");
$dbc = getConnection();

$commentId = $_POST['id'];
$reviewId = $_POST['reviewId'];   

// DELETE COMMENT
mysqli_query($dbc,"
DELETE FROM dbProj_comments 
WHERE commentId = $commentId
");

header("Location: ../reviewDetails.php?reviewId=$reviewId");
exit;