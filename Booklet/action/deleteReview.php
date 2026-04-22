<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

$reviewId = $_POST['id'];

/* GET bookId BEFORE DELETE */
$result = mysqli_query($dbc, "
SELECT bookId FROM dbProj_reviews WHERE reviewId = $reviewId
");

$row = mysqli_fetch_assoc($result);
$bookId = $row['bookId'];

/* DELETE REVIEW */
mysqli_query($dbc, "
DELETE FROM dbProj_reviews WHERE reviewId = $reviewId
");

/* SUCCESS MESSAGE */
$_SESSION['success'] = "Review Deleted Successfully.";

/* REDIRECT */
header("Location: ../bookDetails.php?id=$bookId");
exit;
?>