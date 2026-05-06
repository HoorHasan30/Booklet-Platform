<?php
    session_start();

    include("../DBConnection.php");

    $dbc = getConnection();

    $bookId = $_POST['bookId'] ?? $_GET['id'] ?? null;

    if(!$bookId){
        die("Invalid book ID");
    }

    /* DELETE BOOK */

    // PREPARE QUERY
    $deleteQuery = "DELETE FROM dbProj_books 
                    WHERE bookId = ?";

    $deleteStmt = mysqli_prepare($dbc, $deleteQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $deleteStmt,
        "i",
        $bookId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($deleteStmt);

    /* SUCCESS MESSAGE */
    $_SESSION['success'] = "Book Deleted Successfully.";

    // CLOSE STATEMENT
    mysqli_stmt_close($deleteStmt);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    /* REDIRECT */
    header("Location: ../AllBooks.php");

    exit;
?>