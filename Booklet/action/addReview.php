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

    // PREPARE QUERY
    $checkQuery = "SELECT reviewId 
                   FROM dbProj_reviews 
                   WHERE userId = ? AND bookId = ?";

    $checkStmt = mysqli_prepare($dbc, $checkQuery);

    // BIND PARAMETERS
    mysqli_stmt_bind_param(
        $checkStmt,
        "ii",
        $userId,
        $bookId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($checkStmt);

    // GET RESULT
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {

        $_SESSION['error'] = "You already made a review for this book.";

        mysqli_stmt_close($checkStmt);
        mysqli_close($dbc);

        header("Location: ../bookDetails.php?id=" . $bookId);
        exit;
    }

    /* INSERT REVIEW */

    // PREPARE QUERY
    $insertQuery = "INSERT INTO dbProj_reviews
                    (userId, bookId, rating, reviewText, createdAt)
                    VALUES (?, ?, ?, ?, NOW())";

    $insertStmt = mysqli_prepare($dbc, $insertQuery);

    // BIND PARAMETERS
    mysqli_stmt_bind_param(
        $insertStmt,
        "iiis",
        $userId,
        $bookId,
        $rating,
        $reviewText
    );

    // EXECUTE QUERY
    if (mysqli_stmt_execute($insertStmt)) {
        $_SESSION['success'] = "Review added successfully.";
    } 
    else {
        $_SESSION['error'] = "Failed to add review.";
    }

    // CLOSE STATEMENTS
    mysqli_stmt_close($checkStmt);
    mysqli_stmt_close($insertStmt);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    header("Location: ../bookDetails.php?id=" . $bookId);
    exit;
?>