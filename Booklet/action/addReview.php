<?php 
    session_start();
    include("../DBConnection.php");
    $dbc = getConnection();

    $userId = $_SESSION['userId'] ?? null;
    $bookId = $_POST['bookId'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $reviewText = trim($_POST['reviewText'] ?? '');

    if (!$userId || !$bookId) {
        die("Missing user or book ID.");
    }

    /* CHECK IF USER ALREADY REVIEWED THIS BOOK */
    $checkStmt = mysqli_prepare($dbc, "
        SELECT reviewId FROM dbProj_reviews 
        WHERE userId = ? AND bookId = ?
    ");
    mysqli_stmt_bind_param($checkStmt, "ii", $userId, $bookId);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {
        $_SESSION['error'] = "You already made a review for this book.";
        header("Location: ../bookDetails.php?id=" . $bookId);
        exit;
    }

    /* INSERT REVIEW */
    $insertStmt = mysqli_prepare($dbc, "
        INSERT INTO dbProj_reviews (userId, bookId, rating, reviewText, createdAt)
        VALUES (?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param($insertStmt, "iiis", $userId, $bookId, $rating, $reviewText);

    if (mysqli_stmt_execute($insertStmt)) {
        $_SESSION['success'] = "Review added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add review.";
    }

    header("Location: ../bookDetails.php?id=" . $bookId);
    exit;
?>