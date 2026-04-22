<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include("../DBConnection.php");
$dbc = getConnection();

/* GET DATA */
$bookId = $_POST['bookId'] ?? null;
$title = trim($_POST['title'] ?? '');
$author = trim($_POST['author'] ?? '');
$genreId = $_POST['genreId'] ?? null;
$pages = $_POST['noPages'] ?? null;
$description = trim($_POST['description'] ?? '');

/* VALIDATION */
if (!$bookId) {
    die("Book ID missing.");
}

if ($title === "" || $author === "" || $pages === "" || !is_numeric($pages) || $pages <= 0) {
    die("Invalid input.");
}

$bookId = (int)$bookId;
$genreId = (int)$genreId;
$pages = (int)$pages;

$title = mysqli_real_escape_string($dbc, $title);
$author = mysqli_real_escape_string($dbc, $author);
$description = mysqli_real_escape_string($dbc, $description);

$coverSql = "";

/* HANDLE BOOK COVER UPLOAD */
if (isset($_FILES['bookCover']) && $_FILES['bookCover']['error'] === 0) {

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $fileType = $_FILES['bookCover']['type'];

    if (!in_array($fileType, $allowedTypes)) {
        die("Invalid image type. Only JPG, JPEG, PNG, and WEBP are allowed.");
    }

    $uploadDir = "../BookCovers/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['bookCover']['name'], PATHINFO_EXTENSION));
    $newFileName = "book_" . $bookId . "_" . time() . "." . $ext;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($_FILES['bookCover']['tmp_name'], $targetPath)) {

        $dbPath = "BookCovers/" . $newFileName;
        $coverSql = ", bookCover = '" . mysqli_real_escape_string($dbc, $dbPath) . "'";

        /* DELETE OLD COVER IF EXISTS */
        if (!empty($oldCover) && file_exists("../" . $oldCover)) {
            unlink("../" . $oldCover);
        }

    } else {
        die("Failed to upload book cover.");
    }
}

/* UPDATE BOOK */
$sql = "
    UPDATE dbProj_books 
    SET title = '$title',
        author = '$author',
        genreId = $genreId,
        noPages = $pages,
        description = '$description'
        $coverSql
    WHERE bookId = $bookId
";

mysqli_query($dbc, $sql) or die("SQL ERROR: " . mysqli_error($dbc));

$_SESSION['success'] = "Book Updated Successfully.";

header("Location: ../bookDetails.php?id=" . $bookId);
exit;
?>