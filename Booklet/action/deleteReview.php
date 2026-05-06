<?php
    session_start();

    include("../DBConnection.php");

    $dbc = getConnection();

    $reviewId = $_POST['id'];

    /* GET bookId BEFORE DELETE */

    // PREPARE QUERY
    $selectQuery = "SELECT bookId 
                    FROM dbProj_reviews 
                    WHERE reviewId = ?";

    $selectStmt = mysqli_prepare($dbc, $selectQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $selectStmt,
        "i",
        $reviewId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($selectStmt);

    // GET RESULT
    $result = mysqli_stmt_get_result($selectStmt);

    $row = mysqli_fetch_assoc($result);

    $bookId = $row['bookId'];

    /* DELETE REVIEW */

    // PREPARE QUERY
    $deleteQuery = "DELETE FROM dbProj_reviews 
                    WHERE reviewId = ?";

    $deleteStmt = mysqli_prepare($dbc, $deleteQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $deleteStmt,
        "i",
        $reviewId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($deleteStmt);

    /* SUCCESS MESSAGE */
    $_SESSION['success'] = "Review Deleted Successfully.";

    // CLOSE STATEMENTS
    mysqli_stmt_close($selectStmt);
    mysqli_stmt_close($deleteStmt);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    /* REDIRECT */
    header("Location: ../bookDetails.php?id=$bookId");

    exit;
?>