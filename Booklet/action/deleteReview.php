<?php
include("../DBConnection.php");
$dbc = getConnection();

$reviewId = $_POST['id'];

// DELETE COMMENTS FIRST
mysqli_query($dbc,"
DELETE FROM dbProj_comments 
WHERE reviewId = $reviewId
");

// THEN DELETE REVIEW
mysqli_query($dbc,"
DELETE FROM dbProj_reviews 
WHERE reviewId = $reviewId
");

// REDIRECT BACK
header("Location: ../AllBooks.php");
exit;