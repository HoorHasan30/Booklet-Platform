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



/* BACK BUTTON */
.back-btn{
    font-size:26px;
    text-decoration:none;
    color:black;
    margin-left:-180px;
}

.review-text{
    margin-bottom:10px; 
}

.review-user{
    display:block;
    margin-top:5px;
    font-size:13px;
    color:#444;
}

.review-stars{
    color: gold;
    font-size:18px;
    margin-bottom:10px; 
}
/* COMMENTS HEADER */
.comments-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-top:30px;
}

/* BUTTON */
.comment-btn{
    background:#CBB6A6;
    border:none;
    padding:10px 22px;
    border-radius:20px;
    cursor:pointer;
    font-size:14px;
}

.comment-btn:hover{
    background:#b8a08d;
}

/* COMMENT LIST */
.review-card,
.comment-card {
    background: #EFE7DF;
    padding: 20px;
    border-radius: 20px;
    margin-top: 15px;

    border: 2px solid #D3C1B4; 
}
.review-card:hover,
.comment-card:hover {
    transform: scale(1.01);
    transition: 0.2s;
}
/* TEXT */
.comment-text{
    margin-bottom:8px;
}

.comment-user{
    font-size:13px;
    color:#444;
}

/* MODAL BACKGROUND */
.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.4);
    justify-content:center;
    align-items:center;
}

/* MODAL BOX */
.modal-content{
    background:#F5EDE6;
    width:400px;
    padding:20px;
    border-radius:20px;
    position:relative;
}

/* CLOSE BUTTON */
.close{
    position:absolute;
    top:10px;
    right:15px;
    font-size:20px;
    cursor:pointer;
}

/* TEXTAREA */
textarea{
    width:100%;
    height:80px;
    border-radius:10px;
    padding:10px;
    margin-top:20px;
}

/* SAVE BUTTON */
.save-btn{
    background:#CBB6A6;
    border:none;
    padding:10px 20px;
    border-radius:20px;
    margin-top:15px;
    float:right;
    cursor:pointer;
}

.save-btn:hover{
    background:#b8a08d;
}

.delete-form{
    margin-top:10px;
}

.delete-btn{
    background:#d9534f;
    color:white;
    border:none;
    padding:6px 14px;
    border-radius:15px;
    cursor:pointer;
    font-size:12px;
}

.delete-btn:hover{
    background:#c9302c;
}

</style>
</head>

<body>

<?php
/* NAVBAR */
if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'Creator'){
        include("CreatorNavBar.php");
    } else {
        include("VisitorNavBar.php");
    }
} else {
    include("VisitorNavBar.php");
}
?>

<div class="container">

<!-- BACK -->
<a href="javascript:history.back()" class="back-btn">←</a>

<div class="review-card">

    <!-- STARS -->
    <div class="review-stars">
        <?= str_repeat("⭐", $review['rating']) ?>
    </div>

    <!-- TEXT -->
    <p class="review-text"><?= $review['reviewText'] ?></p>

    <!-- USER -->
    <small class="review-user">By: <?= $review['firstName'] ?></small>
    
      <!-- ADMIN DELETE -->
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
        <form method="POST" action="action/deleteReview.php" class="delete-form">
            <input type="hidden" name="reviewId" value="<?= $reviewId ?>">
            <button class="delete-btn">Delete</button>
        </form>
    <?php } ?>

</div>

<!-- COMMENTS -->
<div class="comments-header">
    <h3>Comments</h3>
    <button class="comment-btn" onclick="openComment()">Add Comment</button>
</div>

<?php while($c = mysqli_fetch_assoc($comments)){ ?>
<div class="comment-card">

    <p class="comment-text"><?= $c['commentText'] ?></p>
    <span class="comment-user">By: <?= $c['firstName'] ?></span>

    <!-- ADMIN DELETE -->
    <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'){ ?>
        <form method="POST" action="action/deleteComment.php" class="delete-form">
            <input type="hidden" name="commentId" value="<?= $c['commentId'] ?>">
            <button class="delete-btn">Delete</button>
        </form>
    <?php } ?>

</div>
<?php } ?>

</div>

<!-- MODAL -->
<div id="commentModal" class="modal">
<div class="modal-content">

<span class="close" onclick="closeComment()">×</span>

<form method="POST" action="action/addComment.php">
    <input type="hidden" name="reviewId" value="<?= $reviewId ?>">

    <textarea name="commentText" placeholder="Write a comment..." required></textarea>

    <button class="save-btn">Save</button>
</form>

</div>
</div>

<script>

/* OPEN */
function openComment(){
    document.getElementById("commentModal").style.display = "flex";
}

/* CLOSE */
function closeComment(){
    document.getElementById("commentModal").style.display = "none";
}

/* CLICK OUTSIDE */
window.onclick = function(e){
    let modal = document.getElementById("commentModal");
    if(e.target == modal){
        modal.style.display = "none";
    }
}

</script>

</body>
</html>