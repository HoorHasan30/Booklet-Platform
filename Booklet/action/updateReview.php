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

    // PREPARE QUERY
    $bookQuery = "SELECT bookId 
                  FROM dbProj_reviews 
                  WHERE reviewId = ?";

    $stmtBook = mysqli_prepare($dbc, $bookQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $stmtBook,
        "i",
        $reviewId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($stmtBook);

    // GET RESULT
    $resultBook = mysqli_stmt_get_result($stmtBook);

    $row = mysqli_fetch_assoc($resultBook);

    if (!$row) {
        die("Review not found.");
    }

    $bookId = $row['bookId'];

    /* UPDATE REVIEW */

    // PREPARE QUERY
    $updateQuery = "UPDATE dbProj_reviews
                    SET rating = ?, reviewText = ?
                    WHERE reviewId = ?";

    $stmtUpdate = mysqli_prepare($dbc, $updateQuery);

    // BIND PARAMETERS
    mysqli_stmt_bind_param(
        $stmtUpdate,
        "isi",
        $rating,
        $reviewText,
        $reviewId
    );

    // EXECUTE QUERY
    if (mysqli_stmt_execute($stmtUpdate)) {
        $_SESSION['success'] = "Review updated successfully.";
    } 
    else {
        $_SESSION['error'] = "Failed to update review.";
    }

    // CLOSE STATEMENTS
    mysqli_stmt_close($stmtBook);
    mysqli_stmt_close($stmtUpdate);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    header("Location: ../bookDetails.php?id=" . $bookId);

    exit;
?>