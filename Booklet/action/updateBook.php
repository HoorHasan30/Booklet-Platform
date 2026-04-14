<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();
    include("../DBConnection.php");

    $dbc = getConnection();

    $bookId = (int)$_POST['bookId'];
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $genreId = (int)$_POST['genreId'];
    $noPages = (int)$_POST['noPages'];
    $description = trim($_POST['description']);

    /* Get current cover first */
    $currentSql = "SELECT bookCover FROM dbProj_books WHERE bookId = ?";
    $currentStmt = mysqli_prepare($dbc, $currentSql);

    if (!$currentStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($currentStmt, "i", $bookId);
    mysqli_stmt_execute($currentStmt);
    $currentResult = mysqli_stmt_get_result($currentStmt);
    $currentBook = mysqli_fetch_assoc($currentResult);

    $currentCover = $currentBook['bookCover'] ?? "";
    $newBookCover = $currentCover;

    /* IMAGE UPLOAD */
    if (isset($_FILES['bookCover']) && $_FILES['bookCover']['error'] === 0 && !empty($_FILES['bookCover']['name'])) {

        $tmpName = $_FILES['bookCover']['tmp_name'];
        $originalName = basename($_FILES['bookCover']['name']);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        /* Allow only image extensions */
        $allowedExtensions = array("jpg", "jpeg", "png", "gif", "webp", "jfif");

        if (!in_array($ext, $allowedExtensions)) {
            die("Invalid image file type.");
        }

        /* Create unique file name */
        $newFileName = "book_" . time() . "_" . $bookId . "." . $ext;

        /* Physical path in project folder */
        $uploadDir = dirname(__DIR__) . "/BookCovers/";
        $fullPath = $uploadDir . $newFileName;

        /* Path saved in database */
        $newBookCover = "BookCovers/" . $newFileName;

        /* Make sure folder exists */
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        /* Upload file */
        if (!move_uploaded_file($tmpName, $fullPath)) {
            die("Image upload failed.");
        }

        /* Delete old image if it exists */
        if (!empty($currentCover)) {
            $oldFullPath = dirname(__DIR__) . "/" . $currentCover;

            if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                @unlink($oldFullPath);
            }
        }
    }

    /* UPDATE BOOK */
    $updateSql = "UPDATE dbProj_books
                  SET title = ?, author = ?, genreId = ?, noPages = ?, description = ?, bookCover = ?, updatedAt = NOW()
                  WHERE bookId = ?";

    $updateStmt = mysqli_prepare($dbc, $updateSql);

    if (!$updateStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param(
        $updateStmt,
        "ssiissi",
        $title,
        $author,
        $genreId,
        $noPages,
        $description,
        $newBookCover,
        $bookId
    );

    if (!mysqli_stmt_execute($updateStmt)) {
        die("Update failed: " . mysqli_error($dbc));
    }

    /* REDIRECT */
    header("Location: ../bookDetails.php?id=" . $bookId);
    exit;
?>