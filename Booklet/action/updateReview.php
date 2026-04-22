<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../DBConnection.php");
$dbc = getConnection();

/* GET DATA */
$reviewId = $_POST['reviewId'] ?? null;
$rating = $_POST['rating'] ?? null;
$reviewText = $_POST['reviewText'] ?? '';

if(!$reviewId){
    die("Review ID missing.");
}

$reviewText = str_replace("'", "\\'", $reviewText);

/* UPDATE */
mysqli_query($dbc, "
UPDATE dbProj_reviews
SET rating = $rating,
    reviewText = '$reviewText'
WHERE reviewId = $reviewId
") or die(mysqli_error($dbc));

/* GET BOOK ID */
$result = mysqli_query($dbc, "
SELECT bookId FROM dbProj_reviews WHERE reviewId = $reviewId
");

$row = mysqli_fetch_assoc($result);
$bookId = $row['bookId'];

/* SUCCESS MESSAGE */
$_SESSION['success'] = "Review updated successfully.";

header("Location: ../bookDetails.php?id=".$bookId);
exit;
?>