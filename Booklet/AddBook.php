<?php
    session_start();

    include("DBConnection.php");

    $dbc = getConnection();
    // Fetch all genres from database to display in dropdown

    $genres = mysqli_fetch_all(
        mysqli_query($dbc, "SELECT genreId, genreName FROM dbProj_Genres ORDER BY genreName"),
        MYSQLI_ASSOC
    );

    mysqli_close($dbc);

    $errors = $_SESSION['addBookErrors'] ?? [];
    $old    = $_SESSION['addBookOld'] ?? [];

    unset($_SESSION['addBookErrors'], $_SESSION['addBookOld']);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add Book</title>
        <link rel="stylesheet" href="BookletCSS.css">

        <script>
                // Function to validate form before submitting

            function validateForm() {
                let title = document.getElementById("title").value.trim();
                let author = document.getElementById("author").value.trim();
                let genreId = document.getElementById("genreId").value;
                let pages = document.getElementById("noPages").value.trim();
                let description = document.getElementById("description").value.trim();
                let bookCover = document.getElementById("bookCoverInput").files.length;

                document.getElementById("titleError").textContent = "";
                document.getElementById("authorError").textContent = "";
                document.getElementById("genreError").textContent = "";
                document.getElementById("pagesError").textContent = "";
                document.getElementById("descriptionError").textContent = "";
                document.getElementById("bookCoverError").textContent = "";

                let isValid = true;

                if (title === "") {
                    document.getElementById("titleError").textContent = "Please enter the book name.";
                    isValid = false;
                }

                if (author === "") {
                    document.getElementById("authorError").textContent = "Please enter the author name.";
                    isValid = false;
                }

                if (genreId === "0") {
                    document.getElementById("genreError").textContent = "Please choose a genre.";
                    isValid = false;
                }

                if (pages === "") {
                    document.getElementById("pagesError").textContent = "Please enter number of pages.";
                    isValid = false;
                } else if (parseInt(pages) <= 0) {
                    document.getElementById("pagesError").textContent = "Pages must be greater than 0.";
                    isValid = false;
                }

                if (description === "") {
                    document.getElementById("descriptionError").textContent = "Please enter the description.";
                    isValid = false;
                }

                if (document.getElementById("hasRealImage").value === "0") {
                    document.getElementById("bookCoverError").textContent = "Please add a book cover.";
                    isValid = false;
                }

                return isValid;
            }

            document.addEventListener("DOMContentLoaded", function () {
                const input = document.getElementById("bookCoverInput");
                const preview = document.getElementById("previewImage");
                const hasRealImage = document.getElementById("hasRealImage");

                input.addEventListener("change", function () {
                    const file = this.files[0];

                    document.getElementById("bookCoverError").textContent = "";

                    if (!file) return;
            // Validate file type (must be image)

                    if (!file.type.startsWith("image/")) {
                        document.getElementById("bookCoverError").textContent = "Please select a valid image file.";
                        this.value = "";
                        hasRealImage.value = "0";
                        preview.src = "images/noCoverYet.png"; // reset
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        hasRealImage.value = "1"; // ✅ real image selected
                    };

                    reader.readAsDataURL(file);
                });
            });
        </script>
    </head>

    <body>

        <?php include("CreatorNavBar.php"); ?>
 <!-- Main container for Add book page -->
        <div class="edit-container">
            <a href="MyBooks.php" class="back-btn">←</a>
              <!-- Form for adding a new book (enctype for file upload) -->
            <form method="POST" action="action/addBook.php" enctype="multipart/form-data" onsubmit="return validateForm()">

                <div class="edit-layout">
 <!-- Left side: image preview and upload -->
                    <div class="left-side">
                        <!-- Image preview box -->
                        <div class="upload-box">
                            <img id="previewImage" src="images/noCoverYet.png" alt="Book Cover Preview" value="0">
                        </div>

                        <label class="upload-btn">
                            Add Cover
                            <input type="file" id="bookCoverInput" name="bookCover" hidden>
                        </label>
                        <p class="error" id="bookCoverError"><?= htmlspecialchars($errors['cover'] ?? '') ?></p>
                    </div>

  <!-- Right side: form fields -->
                    <div class="form-side">

                        <div class="form-row">
                            <label>Book Name:</label>
                               <!-- Input container -->
                            <div>
                                <input type="text" placeholder="Enter Book Name" id="title" name="title" value="<?= htmlspecialchars($old['title'] ?? '') ?>">
                                <p class="error" id="titleError"><?= htmlspecialchars($errors['title'] ?? '') ?></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Author:</label>
                               <!-- Input container -->
                            <div>
                                <input type="text" placeholder="Enter Book Author" id="author" name="author" value="<?= htmlspecialchars($old['author'] ?? '') ?>">
                                <p class="error" id="authorError"><?= htmlspecialchars($errors['author'] ?? '') ?></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Genre:</label>
                            <!-- Select container -->
                            <div>
                                <select name="genreId" id="genreId">
                                    <option value="0">Choose Genre</option>
                                    <?php foreach ($genres as $g): ?>
                                        <option value="<?= $g['genreId'] ?>" <?= (($old['genreId'] ?? 0) == $g['genreId']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($g['genreName']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="error" id="genreError"><?= htmlspecialchars($errors['genre'] ?? '') ?></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>No. Pages:</label>
                             <!-- Input container -->
                            <div>
                                <input type="number" placeholder="Enter Number of Pages" id="noPages" name="noPages" value="<?= htmlspecialchars($old['noPages'] ?? '') ?>">
                                <p class="error" id="pagesError"><?= htmlspecialchars($errors['pages'] ?? '') ?></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Description:</label>
                            <!-- Textarea container -->
                            <div>
                                <textarea name="description" placeholder="Enter Book Description" id="description"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                                <p class="error" id="descriptionError"><?= htmlspecialchars($errors['description'] ?? '') ?></p>
                            </div>
                        </div>
  <!-- Form action buttons -->
                        <div class="form-actions">
                            <button type="button" class="btn" onclick="location.href='MyBooks.php'">Cancel</button>
                            <button type="submit" class="btn">Add</button>
                        </div>

                    </div>
                </div>
            </form>
        </div>

        <?php include("Footer.php"); ?>

    </body>
</html>