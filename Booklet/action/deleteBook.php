<?php
include("../DBConnection.php");
$dbc = getConnection();

$bookId = $_GET['id'];

mysqli_query($dbc,"DELETE FROM dbProj_books WHERE bookId = $bookId");

header("Location: ../AllBooks.php");
exit;