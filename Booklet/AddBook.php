<?php
session_start();
// TODO: restore this after login page is ready
// if (!isset($_SESSION['userId'])) { header("Location: index.php"); exit(); }
if (!isset($_SESSION['userId'])) {
    $_SESSION['userId']    = 2;
    $_SESSION['firstName'] = 'Sara';
    $_SESSION['lastName']  = 'Ahmed';
    $_SESSION['role']      = 'Creator';
}
include("DBConnection.php");

chmod(__DIR__ . '/BookCovers/', 0777);

$errors  = [];
$success = false;

// fetch genres for dropdown
$dbc    = getConnection();
$genres = mysqli_fetch_all(mysqli_query($dbc, "SELECT genreId, genreName FROM dbProj_Genres ORDER BY genreName"), MYSQLI_ASSOC);

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- server-side validation ---
    $title       = trim($_POST['title']       ?? '');
    $author      = trim($_POST['author']      ?? '');
    $genreId     = intval($_POST['genreId']   ?? 0);
    $noPages     = intval($_POST['noPages']   ?? 0);
    $description = trim($_POST['description'] ?? '');

    if ($title === '')       $errors[] = "Book name is required.";
    if ($author === '')      $errors[] = "Author is required.";
    if ($genreId <= 0)       $errors[] = "Please choose a genre.";
    if ($noPages <= 0)       $errors[] = "Number of pages must be greater than 0.";
    if ($description === '') $errors[] = "Description is required.";

    // --- cover upload ---
    $bookCoverPath = '';
    if (isset($_FILES['bookCover']) && $_FILES['bookCover']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp'];
        $ext     = strtolower(pathinfo($_FILES['bookCover']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Only image files are allowed (jpg, png, gif).";
        } else {
            $filename      = uniqid('cover_') . '.' . $ext;
            $uploadDir     = __DIR__ . '/BookCovers/';
            $uploadTarget  = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['bookCover']['tmp_name'], $uploadTarget)) {
                $bookCoverPath = 'BookCovers/' . $filename;
            } else {
                $errors[] = "Failed to upload cover image. Dir: " . $uploadDir . " | Writable: " . (is_writable($uploadDir) ? 'yes' : 'no') . " | Exists: " . (is_dir($uploadDir) ? 'yes' : 'no');
            }
        }
    } else {
        $errors[] = "A book cover image is required.";
    }

    // --- insert ---
    if (empty($errors)) {
        $userId = $_SESSION['userId'];
        $stmt = mysqli_prepare($dbc,
            "INSERT INTO dbProj_books (title, author, description, noPages, userId, bookCover, createdAt, genreId)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)"
        );
        mysqli_stmt_bind_param($stmt, "sssiisi", $title, $author, $description, $noPages, $userId, $bookCoverPath, $genreId);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_close($dbc);
            header("Location: MyBooks.php");
            exit();
        } else {
            $errors[] = "Database error. Please try again.";
        }
    }
}

mysqli_close($dbc);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book – Booklet</title>
    <link rel="stylesheet" href="BookletCSS.css">
    <style>
        body { background: #FFF7EE; }
        .page-wrapper { padding: 1.5rem 2.5rem; }

        .back-arrow {
            font-size: 1.4rem;
            color: #483434;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.8rem;
            transition: opacity 0.2s;
        }
        .back-arrow:hover { opacity: 0.6; }

        .form-layout {
            display: flex;
            gap: 2.5rem;
            align-items: flex-start;
        }

        /* cover upload box */
        .cover-upload-box {
            flex: 0 0 160px;
            height: 220px;
            border-radius: 12px;
            background: #D3C1B4;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            position: relative;
        }
        .cover-upload-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0; left: 0;
            border-radius: 12px;
        }
        .cover-upload-box .upload-icon {
            font-size: 2rem;
            color: #483434;
            z-index: 1;
        }
        .cover-upload-box input[type="file"] {
            position: absolute;
            width: 100%; height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        /* fields */
        .form-fields { flex: 1; }

        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        .form-row label {
            width: 120px;
            font-size: 1rem;
            color: #483434;
            flex-shrink: 0;
        }
        .form-row input[type="text"],
        .form-row input[type="number"],
        .form-row select,
        .form-row textarea {
            flex: 1;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            border: 1px solid #D3C1B4;
            background: #FFF7EE;
            font-family: Alice, serif;
            font-size: 0.95rem;
            color: #483434;
            outline: none;
        }
        .form-row textarea {
            height: 100px;
            resize: vertical;
            align-self: flex-start;
        }
        .form-row select { cursor: pointer; }

        .btn-row {
            display: flex;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .btn-add {
            padding: 0.5rem 2rem;
            border-radius: 20px;
            background: #D3C1B4;
            color: #483434;
            border: none;
            font-family: Alice, serif;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s, color 0.3s;
        }
        .btn-add:hover { background: #483434; color: #FFF7EE; }

        /* errors */
        .error-list {
            background: #fde8e8;
            border: 1px solid #e57373;
            border-radius: 8px;
            padding: 0.8rem 1.2rem;
            margin-bottom: 1.2rem;
            color: #c62828;
            font-size: 0.9rem;
        }
        .error-list li { margin-bottom: 0.3rem; }
    </style>
</head>
<body>

<?php include("CreatorNavBar.php"); ?>

<div class="page-wrapper">
    <a href="MyBooks.php" class="back-arrow">&#8592;</a>

    <?php if (!empty($errors)): ?>
    <ul class="error-list">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="addBookForm" novalidate>
        <div class="form-layout">

            <!-- cover upload -->
            <div class="cover-upload-box" id="coverBox">
                <span class="upload-icon">&#x2B06;</span>
                <img id="coverPreview" src="" alt="" style="display:none;">
                <input type="file" name="bookCover" id="bookCoverInput" accept="image/*">
            </div>

            <!-- fields -->
            <div class="form-fields">
                <div class="form-row">
                    <label for="title">Book Name:</label>
                    <input type="text" name="title" id="title"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label for="author">Author:</label>
                    <input type="text" name="author" id="author"
                           value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <label for="genreId">Genre:</label>
                    <select name="genreId" id="genreId">
                        <option value="0">Choose Genre</option>
                        <?php foreach ($genres as $g): ?>
                        <option value="<?= $g['genreId'] ?>"
                            <?= (isset($_POST['genreId']) && $_POST['genreId'] == $g['genreId']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g['genreName']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="noPages">No. Pages:</label>
                    <input type="number" name="noPages" id="noPages" min="1"
                           value="<?= htmlspecialchars($_POST['noPages'] ?? '0') ?>">
                </div>
                <div class="form-row">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description"
                              placeholder="Write a description..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-add">Add</button>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
// live cover preview
document.getElementById('bookCoverInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        const preview = document.getElementById('coverPreview');
        preview.src = e.target.result;
        preview.style.display = 'block';
        document.querySelector('.upload-icon').style.display = 'none';
    };
    reader.readAsDataURL(file);
});

// JS validation
document.getElementById('addBookForm').addEventListener('submit', function (e) {
    const title   = document.getElementById('title').value.trim();
    const author  = document.getElementById('author').value.trim();
    const genre   = document.getElementById('genreId').value;
    const pages   = parseInt(document.getElementById('noPages').value);
    const desc    = document.getElementById('description').value.trim();
    const cover   = document.getElementById('bookCoverInput').files.length;
    const msgs    = [];

    if (!title)          msgs.push("Book name is required.");
    if (!author)         msgs.push("Author is required.");
    if (genre === '0')   msgs.push("Please choose a genre.");
    if (!pages || pages < 1) msgs.push("Number of pages must be greater than 0.");
    if (!desc)           msgs.push("Description is required.");
    if (!cover)          msgs.push("A book cover image is required.");

    if (msgs.length > 0) {
        e.preventDefault();
        alert(msgs.join('\n'));
    }
});
</script>
</body>
</html>
