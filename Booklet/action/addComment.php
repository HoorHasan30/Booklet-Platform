<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

if(!isset($_SESSION['userId'])){
    die("You must login first");
}

$commentText = mysqli_real_escape_string($dbc, $_POST['commentText']);
$reviewId = intval($_POST['reviewId']);
$userId = $_SESSION['userId'];

mysqli_query($dbc,"
INSERT INTO dbProj_comments (commentText, reviewId, userId, createdAt)
VALUES ('$commentText', $reviewId, $userId, CURDATE())
");
$_SESSION['success'] = "Comment Added Successfully.";
header("Location: ../reviewDetails.php?reviewId=$reviewId");
?>