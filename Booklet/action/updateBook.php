<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../DBConnection.php");
$dbc = getConnection();

/* GET DATA */
$bookId = $_POST['bookId'] ?? null;
$title = $_POST['title'] ?? '';
$author = $_POST['author'] ?? '';
$genreId = $_POST['genreId'] ?? null;
$pages = $_POST['noPages'] ?? null;
$description = $_POST['description'] ?? '';

/* VALIDATION */
if(!$bookId){
    die("Book ID missing.");
}


$title = str_replace("'", "\\'", $title);
$author = str_replace("'", "\\'", $author);
$description = str_replace("'", "\\'", $description);

/* UPDATE BOOK */
mysqli_query($dbc, "
UPDATE dbProj_books 
SET title = '$title',
    author = '$author',
    genreId = $genreId,
    noPages = $pages,
    description = '$description'
WHERE bookId = $bookId
") or die("SQL ERROR: " . mysqli_error($dbc));

/* SUCCESS MESSAGE */
$_SESSION['success'] = "Book updated successfully.";

/* REDIRECT BACK */
header("Location: ../bookDetails.php?id=".$bookId);
exit;
?>