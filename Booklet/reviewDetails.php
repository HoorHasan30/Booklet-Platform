<?php
session_start();
include("DBConnection.php");
$dbc = getConnection();

if(!isset($_GET['reviewId'])){
    die("Review not found");
}

$reviewId = $_GET['reviewId'];

/* GET REVIEW */
$review = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT r.*, u.firstName
FROM dbProj_reviews r
JOIN dbProj_users u ON r.userId = u.userId
WHERE r.reviewId = $reviewId
"));

/* GET COMMENTS */
$comments = mysqli_query($dbc,"
SELECT c.*, u.firstName
FROM dbProj_comments c
JOIN dbProj_users u ON c.userId = u.userId
WHERE c.reviewId = $reviewId
ORDER BY c.createdAt ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Review Details</title>

<style>
body{
    background:#F5EDE6;
    font-family:Arial;
    margin:0;
}

.container{
    width:65%;
    margin:50px auto;
}

/* BACK */
.back-btn {
    font-size:25px;
    text-decoration:none;
    color: #D3C1B4;
    margin-left:-180px;
}

/* REVIEW CARD */
.review-card1{
    background:#EFE7DF;
    border-radius:25px;
    padding:25px 30px;
    margin-bottom:25px;
    border: 2px solid #6b4c3b;
    position: relative;
}

/* STARS */
.review-stars{
    color:gold;
    margin-bottom:10px;
}

.review-text{
    margin-bottom:10px;
}

.review-user{
    font-size:13px;
    color:#444;
}

/* COMMENTS */
.comments-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}

.comment-btn{
    background:#CBB6A6;
    border:none;
    padding:10px 22px;
    border-radius:20px;
    cursor:pointer;
}

/* COMMENT CARD */
.comment-card{
    background:#EFE7DF;
    padding:18px 25px;
    border-radius:20px;
    margin-bottom:15px;
    border: 2px solid #D3C1B4;
    display:block;
}

.comment-user{
    font-size:13px;
    color:#444;
}

/* DELETE BUTTON */
.delete-btn{
    background:#CBB6A6;
    border:none;
    padding:8px 18px;
    border-radius:20px;
    cursor:pointer;
    bottom: 15px; 
    margin-left: auto;
     display: block; 
}

.delete-btn:hover{
    color:#483434;
    background:#EFE7DF;
    border:2px solid #b8a08d;
}

/* MODAL */
.modal{
    display:none;
    position:fixed;
    width:100%;
    height:100%;
    top:0;
    left:0;
    background:rgba(0,0,0,0.4);
    justify-content:center;
    align-items:center;
    z-index: 9999; 
}

.modal-content{
    background:#F5EDE6;
    width:400px;
    padding:40px;
    border-radius:25px;
    border:3px solid #6b4c3b;
    text-align:center;
    position:relative;
}

.close{
    position:absolute;
    top:15px;
    left:20px;
    cursor:pointer;
}

/* TEXTAREA */
textarea{
    width:100%;
    height:80px;
    border-radius:10px;
    padding:10px;
    margin-top:10px;
}

/* SAVE */
.save-btn{
    background:#CBB6A6;
    border:none;
    padding:10px 20px;
    border-radius:20px;
    margin-top:15px;
    float:right;
}

.save-btn:hover{
    color:#483434;
    background:#EFE7DF;
    border:2px solid #b8a08d;
}

/* LOGIN */
.login-btn{
    background:#CBB6A6;
    padding:10px 22px;
    border-radius:20px;
    text-decoration:none;
    width:90%;
    display:block;
    margin:20px auto;
    color:#6b4c3b;
} 
    
.delete-confirm:hover{
     color:#483434;
    background:#EFE7DF;
    border:2px solid #b8a08d;
}


.cancel-btn:hover{
    color:#483434;
    background:#EFE7DF;
    border:2px solid #b8a08d;
}

.modal-actions{
    display:flex;
    justify-content:center;
    gap:15px;
    margin-top:20px;
}

/* YES DELETE */
.delete-confirm{
    background:#D3C1B4 ;
    border:2px solid #b8a08d;
    padding:10px 20px;
    border-radius:20px;
    cursor:pointer;
}

/* CANCEL */
.cancel-btn{
    background:#CBB6A6;
    border:2px solid #b8a08d;
    padding:10px 20px;
    border-radius:20px;
    cursor:pointer;
}


</style>

</head>
<body>

<!-- LOGIN MODAL -->
<div id="loginModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeLogin()">✖</span>
    <h3>You need to sign in</h3>
    <p>Please login to continue</p>
    <a href="Login.php" class="login-btn">Login</a>
  </div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeDelete()">✖</span>
    <h3>Are you sure?</h3>
    <p>This action cannot be undone.</p>

   <form method="POST" action="action/deleteComment.php">
    <input type="hidden" name="commentId" id="deleteId">

    <div class="modal-actions">
        <button type="submit" class="delete-confirm">Yes, Delete</button>
        <button type="button" class="cancel-btn" onclick="closeDelete()">Cancel</button>
    </div>
</form>
  </div>
</div>

<?php
if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'Creator'){
        include("CreatorNavBar.php");
    } elseif($_SESSION['role'] == 'Admin'){
        include("AdminNavBar.php");
    } else {
        include("VisitorNavBar.php");
    }
} else {
    include("VisitorNavBar.php");
}
?>

<div class="container">

<a href="javascript:history.back()" class="back-btn">←</a>

<!-- REVIEW -->
<div class="review-card1">

    <div class="review-stars">
        <?= str_repeat("⭐", $review['rating']) ?>
    </div>

    <p class="review-text"><?= $review['reviewText'] ?></p>
    <small class="review-user">By: <?= $review['firstName'] ?></small>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
        <button class="delete-btn" onclick="openDelete(<?= $review['reviewId'] ?>)">Delete</button>
    <?php } ?>

</div>

<!-- COMMENTS -->
<div class="comments-header">
    <h3>Comments</h3>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Creator'){ ?>
    <button class="comment-btn" onclick="openComment()">Add Comment</button>
<?php } elseif(!isset($_SESSION['role'])) { ?>
    <button class="comment-btn" onclick="openLogin()">Add Comment</button>
<?php } ?>

</div>

<?php while($c = mysqli_fetch_assoc($comments)){ ?>
<div class="comment-card">

    <p><?= $c['commentText'] ?></p>
    <small>By: <?= $c['firstName'] ?></small>

    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
        <button class="delete-btn" onclick="openDelete(<?= $c['commentId'] ?>)">Delete</button>
    <?php } ?>

</div>
<?php } ?>

</div>

<!-- COMMENT MODAL -->
<div id="commentModal" class="modal">
<div class="modal-content">

<span class="close" onclick="closeComment()">✖</span>

<form method="POST" action="action/addComment.php">
    <input type="hidden" name="reviewId" value="<?= $reviewId ?>">
    <textarea name="commentText" placeholder="Write a comment..." required></textarea>
    <button class="save-btn">Save</button>
</form>

</div>
</div>

<script>
function openComment(){
    document.getElementById("commentModal").style.display = "flex";
}

function closeComment(){
    document.getElementById("commentModal").style.display = "none";
}

function openLogin(){
    document.getElementById("loginModal").style.display = "flex";
}

function closeLogin(){
    document.getElementById("loginModal").style.display = "none";
}

function openDelete(id){
    document.getElementById("deleteModal").style.display = "flex";
    document.getElementById("deleteId").value = id;
}

function closeDelete(){
    document.getElementById("deleteModal").style.display = "none";
}

window.onclick = function(e){
    let commentModal = document.getElementById("commentModal");
    let deleteModal = document.getElementById("deleteModal");
    let loginModal = document.getElementById("loginModal");

    if(e.target == commentModal) commentModal.style.display = "none";
    if(e.target == deleteModal) deleteModal.style.display = "none";
    if(e.target == loginModal) loginModal.style.display = "none";
}
</script>

</body>
</html>