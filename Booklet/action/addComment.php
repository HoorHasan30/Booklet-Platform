<?php
include("../DBConnection.php");

if(!isset($_SESSION['userId'])){
    die("You must login first");
}

$commentText = $_POST['commentText'];
$reviewId = $_POST['reviewId'];
$userId = $_SESSION['userId'];

$stmt = $conn->prepare("
    INSERT INTO dbProj_comments (userId, commentText, createdAt, reviewId)
    VALUES (?, ?, NOW(), ?)
");

$stmt->bind_param("isi", $userId, $commentText, $reviewId);
$stmt->execute();

// return to same page
header("Location: ../pages/reviewDetails.php?reviewId=".$reviewId);
exit();
?>