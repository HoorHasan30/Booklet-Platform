<?php
    session_start();

    include("../DBConnection.php");

    $dbc = getConnection();

    $commentId = $_POST['id'];
    $reviewId = $_POST['reviewId'];

    /* DELETE COMMENT */

    // PREPARE QUERY
    $deleteQuery = "DELETE FROM dbProj_comments 
                    WHERE commentId = ?";

    $deleteStmt = mysqli_prepare($dbc, $deleteQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $deleteStmt,
        "i",
        $commentId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($deleteStmt);

    /* SUCCESS MESSAGE */
    $_SESSION['success'] = "Comment Deleted Successfully.";

    // CLOSE STATEMENT
    mysqli_stmt_close($deleteStmt);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    /* REDIRECT BACK TO SAME REVIEW */
    header("Location: ../reviewDetails.php?reviewId=$reviewId");

    exit;
?>