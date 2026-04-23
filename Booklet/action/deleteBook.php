<?php
    session_start();
    include("../DBConnection.php");
    $dbc = getConnection();

    $bookId = $_POST['bookId'] ?? $_GET['id'] ?? null;

    if(!$bookId){
        die("Invalid book ID");
    }

    /* DELETE */
    mysqli_query($dbc, "
    DELETE FROM dbProj_books WHERE bookId = $bookId
    ") or die(mysqli_error($dbc));
    
    /* SUCCESS MESSAGE */
    $_SESSION['success'] = "Book Deleted Successfully.";
    
    /* REDIRECT */
    header("Location: ../AllBooks.php");
    
    exit;
?>