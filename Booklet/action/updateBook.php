<?php
include("../DBConnection.php");
$dbc = getConnection();

$bookId = $_POST['bookId'];
$title = $_POST['title'];
$author = $_POST['author'];
$genreId = $_POST['genreId'];
$pages = $_POST['noPages'];
$description = $_POST['description'];

// UPDATE QUERY
mysqli_query($dbc,"
UPDATE dbProj_books SET
    title='$title',
    author='$author',
    genreId=$genreId,
    noPages=$pages,
    description='$description'
WHERE bookId=$bookId
");

// REDIRECT BACK
header("Location: ../bookDetails.php?id=$bookId");
exit;