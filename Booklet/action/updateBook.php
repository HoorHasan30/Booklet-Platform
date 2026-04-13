<?php
include("../DBConnection.php");
$dbc = getConnection();

$bookId = $_POST['bookId'];
$title = $_POST['title'];
$author = $_POST['author'];
$genreId = $_POST['genreId'];
$noPages = $_POST['noPages'];
$description = $_POST['description'];

/* IMAGE UPLOAD */
if(!empty($_FILES['bookCover']['name'])){

    $imageName = $_FILES['bookCover']['name'];
    $tmpName = $_FILES['bookCover']['tmp_name'];

    $uploadDir = dirname(__DIR__) . "/BookCovers/";
    $imagePath = "BookCovers/" . $imageName;

    // MOVE FILE 
    move_uploaded_file($tmpName, $uploadDir . $imageName);

    // UPDATE WITH IMAGE
    mysqli_query($dbc,"
    UPDATE dbProj_books 
    SET title='$title',
        author='$author',
        genreId='$genreId',
        noPages='$noPages',
        description='$description',
        bookCover='$imagePath'
    WHERE bookId=$bookId
    ");

}else{

    // UPDATE WITHOUT IMAGE
    mysqli_query($dbc,"
    UPDATE dbProj_books 
    SET title='$title',
        author='$author',
        genreId='$genreId',
        noPages='$noPages',
        description='$description'
    WHERE bookId=$bookId
    ");
}

/* REDIRECT */
header("Location: ../bookDetails.php?id=$bookId");
exit;