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

    if (
        $title === "" ||
        $author === "" ||
        $pages === "" ||
        !is_numeric($pages) ||
        $pages <= 0
    ) {
        die("Invalid input.");
    }

    $bookId = (int)$bookId;
    $genreId = (int)$genreId;
    $pages = (int)$pages;

    /* GET OLD COVER */

    // PREPARE QUERY
    $coverQuery = "SELECT bookCover
                   FROM dbProj_books
                   WHERE bookId = ?";

    $coverStmt = mysqli_prepare($dbc, $coverQuery);

    // BIND PARAMETER
    mysqli_stmt_bind_param(
        $coverStmt,
        "i",
        $bookId
    );

    // EXECUTE QUERY
    mysqli_stmt_execute($coverStmt);

    // GET RESULT
    $coverResult = mysqli_stmt_get_result($coverStmt);

    $coverRow = mysqli_fetch_assoc($coverResult);

    $oldCover = $coverRow['bookCover'] ?? '';

    /* HANDLE BOOK COVER UPLOAD */

    $dbPath = null;

    if (isset($_FILES['bookCover']) && $_FILES['bookCover']['error'] === 0) {

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/jpg',
            'image/webp'
        ];

        $fileType = $_FILES['bookCover']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            die("Invalid image type. Only JPG, JPEG, PNG, and WEBP are allowed.");
        }

       $uploadDir = __DIR__ . '/../BookCovers/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(
            pathinfo(
                $_FILES['bookCover']['name'],
                PATHINFO_EXTENSION
            )
        );

        $newFileName = "book_" . $bookId . "_" . time() . "." . $ext;

        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($_FILES['bookCover']['tmp_name'], $targetPath)) {

            $dbPath = "BookCovers/" . $newFileName;

            /* DELETE OLD COVER */
            if (!empty($oldCover) && file_exists(__DIR__ . '/../' . $oldCover)) {
    unlink(__DIR__ . '/../' . $oldCover);

            }

        } 
        else {
            die("Failed to upload book cover.");
        }
    }

    /* UPDATE BOOK */

    if ($dbPath) {

        // PREPARE QUERY
        $updateQuery = "UPDATE dbProj_books
                        SET title = ?,
                            author = ?,
                            genreId = ?,
                            noPages = ?,
                            description = ?,
                            bookCover = ?
                        WHERE bookId = ?";

        $updateStmt = mysqli_prepare($dbc, $updateQuery);

        // BIND PARAMETERS
        mysqli_stmt_bind_param(
            $updateStmt,
            "ssiissi",
            $title,
            $author,
            $genreId,
            $pages,
            $description,
            $dbPath,
            $bookId
        );

    } 
    else {

        // PREPARE QUERY
        $updateQuery = "UPDATE dbProj_books
                        SET title = ?,
                            author = ?,
                            genreId = ?,
                            noPages = ?,
                            description = ?
                        WHERE bookId = ?";

        $updateStmt = mysqli_prepare($dbc, $updateQuery);

        // BIND PARAMETERS
        mysqli_stmt_bind_param(
            $updateStmt,
            "ssiisi",
            $title,
            $author,
            $genreId,
            $pages,
            $description,
            $bookId
        );
    }

    // EXECUTE QUERY
    mysqli_stmt_execute($updateStmt);

    $_SESSION['success'] = "Book Updated Successfully.";

    // CLOSE STATEMENTS
    mysqli_stmt_close($coverStmt);
    mysqli_stmt_close($updateStmt);

    // CLOSE CONNECTION
    mysqli_close($dbc);

    header("Location: ../bookDetails.php?id=" . $bookId);

    exit;
?>