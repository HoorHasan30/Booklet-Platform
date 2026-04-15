<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

/* GET DATA */
$userId = $_SESSION['userId'] ?? null;
$bookId = $_POST['bookId'] ?? null;
$rating = $_POST['rating'] ?? null;
$reviewText = trim($_POST['reviewText'] ?? '');

/* VALIDATION */
if(!$userId || !$bookId){
    die("Missing user or book ID.");
}

/* REQUIRE RATING */
if(empty($rating)){
    $_SESSION['error'] = "Please select a rating.";
    header("Location: ../bookDetails.php?id=".$bookId);
    exit;
}

/* REQUIRE TEXT */
if(empty($reviewText)){
    $_SESSION['error'] = "Please write a review.";
    header("Location: ../bookDetails.php?id=".$bookId);
    exit;
}

/* CHECK IF USER ALREADY REVIEWED THIS BOOK */
$check = mysqli_query($dbc, "
SELECT reviewId FROM dbProj_reviews 
WHERE userId = $userId AND bookId = $bookId
");

if(mysqli_num_rows($check) > 0){

    $_SESSION['error'] = "You already made a review for this book.";

} else {

    /* INSERT REVIEW */
    mysqli_query($dbc, "
INSERT INTO dbProj_reviews (userId, bookId, rating, reviewText, createdAt)
VALUES ($userId, $bookId, $rating, '$reviewText', NOW())
") or die(mysqli_error($dbc));

    $_SESSION['success'] = "Review added successfully.";
}

/*  REDIRECT */
header("Location: ../bookDetails.php?id=$bookId");
exit;
?>