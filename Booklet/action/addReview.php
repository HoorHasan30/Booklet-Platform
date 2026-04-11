<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

$rating = intval($_POST['rating']);
$bookId = intval($_POST['bookId']);
$userId = $_SESSION['userId'];
$reviewText = mysqli_real_escape_string($dbc, $_POST['reviewText']);

// CALL PROCEDURE
mysqli_query($dbc,"
CALL AddReview($rating, $bookId, $userId, '$reviewText')
");

// IMPORTANT
mysqli_next_result($dbc);

header("Location: ../bookDetails.php?id=$bookId");
?>