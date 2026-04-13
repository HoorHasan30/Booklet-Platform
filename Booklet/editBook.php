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

<style>
body { background:#FFF7EE; font-family: Alice; }

/* CONTAINER */
.edit-container {
    width: 70%;
    margin: 50px auto;
}

/* BACK */
.back-btn {
    font-size:25px;
    text-decoration:none;
    color:#483434;
    display:inline-block;
    margin-bottom:30px;
}

/* LAYOUT */
.edit-layout {
    display:flex;
    gap:50px;
}

/* LEFT SIDE */
.left-side{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:15px;
}

/* IMAGE */
.upload-box{
    width:180px;
    height:220px;
    background:#FFFDF8;
    border-radius:20px;
    border:2px solid #483434;
    overflow:hidden;
}

.upload-box img{
    width:100%;
    height:100%;
    object-fit:cover;
}

/* BUTTON UNDER IMAGE */
.upload-btn{
    background:#D3C1B4;
    padding:8px 20px;
    border-radius:20px;
    cursor:pointer;
    font-size:14px;
    border:1px solid #483434;
}

.upload-btn:hover{
    background:#FFFDF8;
}

/* RIGHT FORM */
.form-side {
    flex:1;
}

/* ROW */
.form-row {
    display:flex;
    align-items:center;
    margin-bottom:20px;
}

.form-row label {
    width:140px;
    font-weight:bold;
}

/* INPUT */
.form-row input {
    width:70%;
    padding:10px;
    border-radius:20px;
    border:1px solid #D3C1B4;
    
}

/* NICE DROPDOWN */
.form-row select {
    width:70%;
    padding:10px 15px;
    border-radius:20px;
    border:1px solid #D3C1B4;
    background:#FFFDF8;
    appearance:none;
    cursor:pointer;

    background-image: url("data:image/svg+xml;utf8,<svg fill='%23483434' height='20' viewBox='0 0 20 20' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M5 7l5 5 5-5z'/></svg>");
    background-repeat:no-repeat;
    background-position:right 15px center;
    background-size:15px;
}

/* TEXTAREA */
textarea {
    width:70%;
    height:120px;
    padding:10px;
    border-radius:15px;
    background:#FFFDF8;
}

/* BUTTONS */
.form-actions {
    display:flex;
    justify-content:flex-end;
    gap:15px;
    margin-top:30px;
}

.btn {
    padding:10px 25px;
    border-radius:25px;
    border:1px solid #483434;
    background:#D3C1B4;
    cursor:pointer;
}

.btn:hover {
    background:#FFFDF8;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:#FFF7EE;
    padding:30px;
    border-radius:25px;
    border:3px solid #483434;
    text-align:center;
}

.modal-actions{
    display:flex;
    justify-content:center;
    gap:15px;
    margin-top:20px;
}

.delete-confirm{
    background:#D3C1B4 ;
    border:2px solid #483434;
    padding:10px 20px;
    border-radius:20px;
    cursor:pointer;
}
.cancel-btn{
    background:#D3C1B4;
    border:2px solid #483434;
    padding:10px 20px;
    border-radius:20px;
    cursor:pointer;
}

.modal-actions{
    display:flex;
    justify-content:center;
    gap:15px;
    margin-top:20px;
}

.delete-confirm:hover{
       background: #FFFDF8;
        border: 2px solid #483434;
}


.cancel-btn:hover{
     background: #FFFDF8;
        border: 2px solid #483434;
}
</style>

<script>
// VALIDATION
function validateForm(){
    let title = document.getElementById("title").value.trim();
    let author = document.getElementById("author").value.trim();
    let pages = document.getElementById("pages").value.trim();

    if(title === "" || author === "" || pages === ""){
        alert("Please fill all fields.");
        return false;
    }

    if(pages <= 0){
        alert("Pages must be greater than 0");
        return false;
    }

    return true;
}

// DELETE POPUP
function openDelete(id){
    document.getElementById("deleteModal").style.display="flex";
    document.getElementById("deleteBookId").value=id;
}

function closeDelete(){
    document.getElementById("deleteModal").style.display="none";
}

// IMAGE PREVIEW
function previewImage(input){
    const file = input.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById("previewImage").src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}
</script>

</head>

<body>

<?php loadNavBar(); ?>

<div class="edit-container">

<a href="javascript:history.back()" class="back-btn">←</a>

<form method="POST" action="action/updateBook.php"
      enctype="multipart/form-data"
      onsubmit="return validateForm()">

<input type="hidden" name="bookId" value="<?= $bookId ?>">

<div class="edit-layout">

<!-- LEFT SIDE (UNCHANGED) -->
<div class="left-side">

    <div class="upload-box">
        <!-- cache fix only -->
        <img id="previewImage"
             src="<?= $book['bookCover'] ?>?v=<?= time() ?>">
    </div>

    <label class="upload-btn">
        Add New Cover
        <input type="file" name="bookCover" hidden onchange="previewImage(this)">
    </label>

</div>

<!-- RIGHT SIDE (UNCHANGED) -->
<div class="form-side">

<div class="form-row">
<label>Book Name:</label>
<input type="text" id="title" name="title" value="<?= $book['title'] ?>">
</div>

<div class="form-row">
<label>Author:</label>
<input type="text" id="author" name="author" value="<?= $book['author'] ?>">
</div>

<div class="form-row">
<label>Genre:</label>
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

<div class="form-row">
<label>No. Pages:</label>
<input type="number" id="pages" name="noPages" value="<?= $book['noPages'] ?>">
</div>

<div class="form-row">
<label>Description:</label>
<textarea name="description"><?= $book['description'] ?></textarea>
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

<!-- ✅ DELETE MODAL (FIXED BUTTONS ONLY) -->
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