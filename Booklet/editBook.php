<?php
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

// GET BOOK DATA
$book = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT * FROM dbProj_books WHERE bookId = $bookId
"));
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Book</title>

<style>
body { background:#F5EDE6; font-family:Arial; }

/* CONTAINER */
.edit-container {
    width: 60%;
    margin: 50px auto;
}

/* ROW */
.form-row {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

/* LABEL */
.form-row label {
    width: 150px;
    font-weight: bold;
}

/* INPUTS */
.form-row input,
.form-row select {
    width: 60%;
    padding: 10px;
    border-radius: 20px;
    border: 1px solid #aaa;
}

/* TEXTAREA */
.textarea-row {
    align-items: flex-start;
}

.textarea-row textarea {
    width: 60%;
    height: 120px;
    padding: 10px;
    border-radius: 15px;
}

/* BUTTONS */
.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
}

.btn {
    padding: 10px 25px;
    border-radius: 25px;
    border: 1px solid #aaa;
    background: #EFE7DF;
    cursor: pointer;
}

.btn.primary {
    background: #CBB6A6;
}

.btn:hover {
    background: #EFE7DF;
    border: 2px solid #b8a08d;
}
</style>
</head>

<body>
<?php loadNavBar(); ?>

<div class="edit-container">

<form method="POST" action="action/updateBook.php" enctype="multipart/form-data">

<input type="hidden" name="bookId" value="<?= $bookId ?>">

<!-- COVER -->
<div class="form-row">
    <label>Book Cover:</label>
    <input type="file" name="bookCover">
</div>

<!-- TITLE -->
<div class="form-row">
    <label>Book Name:</label>
    <input type="text" name="title" value="<?= $book['title'] ?>" required>
</div>

<!-- AUTHOR -->
<div class="form-row">
    <label>Author:</label>
    <input type="text" name="author" value="<?= $book['author'] ?>" required>
</div>

<!-- GENRE -->
<div class="form-row">
    <label>Genre:</label>
    <select name="genreId" required>
        <option value="">Choose Genre</option>
        <?php
        $genres = mysqli_query($dbc,"SELECT * FROM dbProj_Genres");
        while($g = mysqli_fetch_assoc($genres)){
            $selected = ($g['genreId'] == $book['genreId']) ? "selected" : "";
            echo "<option value='{$g['genreId']}' $selected>{$g['genreName']}</option>";
        }
        ?>
    </select>
</div>

<!-- PAGES -->
<div class="form-row">
    <label>No. Pages:</label>
    <input type="number" name="noPages" value="<?= $book['noPages'] ?>" required>
</div>

<!-- DESCRIPTION -->
<div class="form-row textarea-row">
    <label>Description:</label>
    <textarea name="description"><?= $book['description'] ?></textarea>
</div>

<!-- BUTTONS -->
<div class="form-actions">

<?php if($role == 'Admin'){ ?>

  <button type="button" class="btn"
    onclick="confirmDelete(<?= $bookId ?>)">
    Delete Book
</button>

    <button class="btn primary">Save</button>

<?php } else { ?>

    <button type="button" class="btn"
        onclick="location.href='bookDetails.php?id=<?= $bookId ?>'">
        Cancel
    </button>

    <button class="btn primary">Save</button>

<?php } ?>

</div>

</form>

</div>
    <script id="y6z0sm">
function confirmDelete(bookId){
    if(confirm("Are you sure you want to delete this book?")){
        window.location.href = "action/deleteBook.php?id=" + bookId;
    }
}
</script>

<!-- FOOTER -->
<?php include("Footer.php"); ?>

</body>
</html>