<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

$commentId = $_POST['id'];
$reviewId = $_POST['reviewId'];

/* DELETE COMMENT */
mysqli_query($dbc, "
DELETE FROM dbProj_comments WHERE commentId = $commentId
");

/* SUCCESS MESSAGE */
$_SESSION['success'] = "Comment deleted successfully.";

/* REDIRECT BACK TO SAME REVIEW */
header("Location: ../reviewDetails.php?reviewId=$reviewId");
exit;
?>