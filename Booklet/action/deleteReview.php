<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

if(isset($_POST['reviewId']) && $_SESSION['role'] == 'Admin'){

    $reviewId = $_POST['reviewId'];

    // delete comments first (important)
    mysqli_query($dbc, "DELETE FROM dbProj_comments WHERE reviewId = $reviewId");

    // delete review
    mysqli_query($dbc, "DELETE FROM dbProj_reviews WHERE reviewId = $reviewId");

    header("Location: ../index.php");
}
?>