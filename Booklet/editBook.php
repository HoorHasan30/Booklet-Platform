<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    session_start();
    include("DBConnection.php");

    function loadNavBar() {
        if (isset($_SESSION["role"])) {
            if ($_SESSION["role"] == "Admin") {
                include("AdminNavBar.php");
            } 
            elseif ($_SESSION["role"] == "Creator") {
                include("CreatorNavBar.php");
            }
        } 
        else {
            include("VisitorNavBar.php");
        }
    }

    $dbc = getConnection();
    $bookId = intval($_GET['id']);

    $book = mysqli_fetch_assoc(mysqli_query($dbc,"
    SELECT * FROM dbProj_books WHERE bookId = $bookId
    "));

    $role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Edit Book</title>
        <link rel="stylesheet" href="BookletCSS.css">
        
        <script>
            function validateForm(){
                let title = document.getElementById("title").value.trim();
                let author = document.getElementById("author").value.trim();
                let pages = document.getElementById("pages").value.trim();

                document.getElementById("titleError").textContent = "";
                document.getElementById("authorError").textContent = "";
                document.getElementById("pagesError").textContent = "";
                document.getElementById("bookCoverError").textContent = "";

                let isValid = true;

                if(title === ""){
                    document.getElementById("titleError").textContent = "Please enter the book name.";
                    isValid = false;
                }

                if(author === ""){
                    document.getElementById("authorError").textContent = "Please enter the author name.";
                    isValid = false;
                }

                if(pages === ""){
                    document.getElementById("pagesError").textContent = "Please enter number of pages.";
                    isValid = false;
                }
                else if(parseInt(pages) <= 0){
                    document.getElementById("pagesError").textContent = "Pages must be greater than 0.";
                    isValid = false;
                }

                return isValid;
            }

            function openDelete(id){
                document.getElementById("deleteModal").style.display="flex";
                document.getElementById("deleteBookId").value=id;
            }

            function closeDelete(){
                document.getElementById("deleteModal").style.display="none";
            }

            document.addEventListener("DOMContentLoaded", function(){
                const input = document.getElementById("bookCoverInput");
                const preview = document.getElementById("previewImage");

                input.addEventListener("change", function () {
                    const file = this.files[0];
                    document.getElementById("bookCoverError").textContent = "";

                    if (!file) return;

                    if (!file.type.startsWith("image/")) {
                        document.getElementById("bookCoverError").textContent = "Please select a valid image file.";
                        this.value = "";
                        return;
                    }

                    const reader = new FileReader();

                    reader.onload = function (e) {
                        preview.src = e.target.result;
                    };

                    reader.readAsDataURL(file);
                });
            });
        </script>
    </head>

    <body>

        <?php loadNavBar(); ?>

        <div class="edit-container">
            <a href="bookDetails.php?id=<?= $bookId ?>" class="back-btn">←</a>

            <form method="POST" action="action/updateBook.php"
                  enctype="multipart/form-data"
                  onsubmit="return validateForm()">

                <input type="hidden" name="bookId" value="<?= $bookId ?>">

                <div class="edit-layout">

                    <div class="left-side">
                        <div class="upload-box">
                            <img id="previewImage"
                                 src="<?= htmlspecialchars($book['bookCover']) ?>?v=<?= time() ?>">
                        </div>
                        
                        <label class="upload-btn">
                            Add New Cover
                            <input type="file" id="bookCoverInput" name="bookCover" hidden>
                        </label>
                        <p class="error" id="bookCoverError"></p>
                    </div>

                    <div class="form-side">

                        <div class="form-row">
                            <label>Book Name:</label>
                            <div>
                                <input type="text" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>">
                                <p class="error" id="titleError"></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Author:</label>
                            <div>
                                <input type="text" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>">
                                <p class="error" id="authorError"></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Genre:</label>
                            <div>
                                <select name="genreId">
                                    <?php
                                    $genres = mysqli_query($dbc,"SELECT * FROM dbProj_Genres");
                                    while($g = mysqli_fetch_assoc($genres)){
                                        $selected = ($g['genreId']==$book['genreId']) ? "selected":"";
                                        echo "<option value='{$g['genreId']}' $selected>{$g['genreName']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>No. Pages:</label>
                            <div>
                                <input type="number" id="pages" name="noPages" value="<?= htmlspecialchars($book['noPages']) ?>">
                                <p class="error" id="pagesError"></p>
                            </div>
                        </div>

                        <div class="form-row">
                            <label>Description:</label>
                            <div>
                                <textarea name="description"><?= htmlspecialchars($book['description']) ?></textarea>
                            </div>
                        </div>

                        <div class="form-actions">

                            <?php if($role == 'Admin'){ ?>
                                <button type="button" class="btn"
                                onclick="openDelete(<?= $bookId ?>)">Delete</button>
                            <?php } else { ?>
                                <button type="button" class="btn"
                                onclick="location.href='bookDetails.php?id=<?= $bookId ?>'">Cancel</button>
                            <?php } ?>

                            <button class="btn">Save</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div id="deleteModal" class="modal">
            <div class="modal-content">

                <h3>Are you sure?</h3>
                <p>This action cannot be undone.</p>

                <form method="POST" action="action/deleteBook.php">
                    <input type="hidden" name="bookId" id="deleteBookId">

                    <br>

                    <div class="modal-actions">
                        <button type="submit" class="delete-confirm">Yes, Delete</button>
                        <button type="button" class="cancel-btn" onclick="closeDelete()">Cancel</button>
                    </div>

                </form>

            </div>
        </div>

        <?php include("Footer.php"); ?>

    </body>
</html>