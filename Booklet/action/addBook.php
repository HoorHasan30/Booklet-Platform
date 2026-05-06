<?php
    session_start();

    include("../DBConnection.php");

    $dbc = getConnection();

    $title       = trim($_POST['title'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $genreId     = intval($_POST['genreId'] ?? 0);
    $noPages     = trim($_POST['noPages'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $errors = [];

    /* VALIDATION */

    if ($title === '') {
        $errors['title'] = "Please enter the book name.";
    }

    if ($author === '') {
        $errors['author'] = "Please enter the author name.";
    }

    if ($genreId <= 0) {
        $errors['genre'] = "Please choose a genre.";
    }

    if ($noPages === '') {
        $errors['pages'] = "Please enter number of pages.";
    } 
    elseif (!is_numeric($noPages) || intval($noPages) <= 0) {
        $errors['pages'] = "Pages must be greater than 0.";
    }

    if ($description === '') {
        $errors['description'] = "Please enter the description.";
    }

    /* IMAGE UPLOAD */

    $bookCoverPath = '';

    if (
        isset($_FILES['bookCover']) &&
        $_FILES['bookCover']['error'] === UPLOAD_ERR_OK
    ) {

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp'];

        $ext = strtolower(
            pathinfo(
                $_FILES['bookCover']['name'],
                PATHINFO_EXTENSION
            )
        );

        if (!in_array($ext, $allowed)) {

            $errors['cover'] = "Please select a valid image file.";

        } 
        else {

            $filename = uniqid('cover_') . '.' . $ext;

            $uploadDir = __DIR__ . '/../BookCovers/';

            $uploadTarget = $uploadDir . $filename;

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (
                move_uploaded_file(
                    $_FILES['bookCover']['tmp_name'],
                    $uploadTarget
                )
            ) {

                $bookCoverPath = 'BookCovers/' . $filename;

            } 
            else {

                $errors['cover'] = "Failed to upload cover image.";
            }
        }

    } 
    else {

        $errors['cover'] = "Please add a book cover.";
    }

    /* IF ERRORS */

    if (!empty($errors)) {

        $_SESSION['addBookErrors'] = $errors;

        $_SESSION['addBookOld'] = [
            'title' => $title,
            'author' => $author,
            'genreId' => $genreId,
            'noPages' => $noPages,
            'description' => $description
        ];

        mysqli_close($dbc);

        header("Location: ../AddBook.php");

        exit();
    }

    /* INSERT BOOK */

    $userId = $_SESSION['userId'];

    $pagesInt = intval($noPages);

    // PREPARE QUERY
    $insertQuery = "INSERT INTO dbProj_books
                    (title, author, description, noPages, userId, bookCover, createdAt, genreId)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = mysqli_prepare($dbc, $insertQuery);

    // BIND PARAMETERS
    mysqli_stmt_bind_param(
        $stmt,
        "sssiisi",
        $title,
        $author,
        $description,
        $pagesInt,
        $userId,
        $bookCoverPath,
        $genreId
    );

    // EXECUTE QUERY
    if (mysqli_stmt_execute($stmt)) {

        $_SESSION['success'] = "New Book Added Successfully.";

        mysqli_stmt_close($stmt);

        mysqli_close($dbc);

        header("Location: ../MyBooks.php");

        exit();
    } 
    else {

        $_SESSION['addBookErrors'] = [
            'title' => "Database error. Please try again."
        ];

        mysqli_stmt_close($stmt);

        mysqli_close($dbc);

        header("Location: ../AddBook.php");

        exit();
    }
?>