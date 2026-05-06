<?php
session_start();
include("../DBConnection.php");
$dbc = getConnection();

if(!isset($_SESSION['userId'])){
    die("You must login first");
}

    $commentText = trim($_POST['commentText']);
    $reviewId = intval($_POST['reviewId']);
    $userId = $_SESSION['userId'];

    // PREPARE QUERY
    $insertQuery = "INSERT INTO dbProj_comments
                    (commentText, reviewId, userId, createdAt)
                    VALUES (?, ?, ?, CURDATE())";

    $stmt = mysqli_prepare($dbc, $insertQuery);

    // BIND PARAMETERS
    mysqli_stmt_bind_param(
        $stmt,
        "sii",
        $commentText,
        $reviewId,
        $userId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($stmt);

    // CLOSE STATEMENT
    mysqli_stmt_close($stmt);
$_SESSION['success'] = "Comment Added Successfully.";
mysqli_close($dbc);
header("Location: ../reviewDetails.php?reviewId=$reviewId");
?>