<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();
    include("../DBConnection.php");
    $dbc = getConnection();

    $reviewId = $_POST['reviewId'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $reviewText = trim($_POST['reviewText'] ?? '');

    if (!$reviewId) {
        die("Review ID missing.");
    }

    /* GET BOOK ID */
    $stmtBook = mysqli_prepare($dbc, "SELECT bookId FROM dbProj_reviews WHERE reviewId = ?");
    mysqli_stmt_bind_param($stmtBook, "i", $reviewId);
    mysqli_stmt_execute($stmtBook);
    $resultBook = mysqli_stmt_get_result($stmtBook);
    $row = mysqli_fetch_assoc($resultBook);

    if (!$row) {
        die("Review not found.");
    }

    $bookId = $row['bookId'];

    /* UPDATE */
    $stmtUpdate = mysqli_prepare($dbc, "
        UPDATE dbProj_reviews
        SET rating = ?, reviewText = ?
        WHERE reviewId = ?
    ");
    mysqli_stmt_bind_param($stmtUpdate, "isi", $rating, $reviewText, $reviewId);

    if (mysqli_stmt_execute($stmtUpdate)) {
        $_SESSION['success'] = "Review updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update review.";
    }

    header("Location: ../bookDetails.php?id=" . $bookId);
    exit;
?>