<?php
session_start();
include("DBConnection.php");
$dbc = getConnection();

if(!isset($_GET['reviewId'])) die("Review not found");

$reviewId = $_GET['reviewId'];

// REVIEW
$review = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT r.*, u.firstName
FROM dbProj_reviews r
JOIN dbProj_users u ON r.userId = u.userId
WHERE r.reviewId = $reviewId
"));

// COMMENTS
$comments = mysqli_query($dbc,"
SELECT c.*, u.firstName
FROM dbProj_comments c
JOIN dbProj_users u ON c.userId = u.userId
WHERE c.reviewId = $reviewId
");
?>

<!DOCTYPE html>
<html>
<head>
<style>
body { background:#F5EDE6; font-family:Arial; }

.container {
    width:65%;
    margin:auto;
}

/* BACK */
.back-btn {
    font-size:25px;
    text-decoration:none;
    color:black;
}

/* CARDS */
.card {
    background:#EFE7DF;
    border-radius:25px;
    padding:25px;
    margin-top:25px;
}

/* BUTTON */
button {
    background:#CBB6A6;
    border:none;
    padding:10px 20px;
    border-radius:20px;
    cursor:pointer;
}

/* ALIGN BUTTON RIGHT */
.comment-btn {
    display:flex;
    justify-content:flex-end;
    margin-top:10px;
}

/* MODAL */
.modal {
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

.modal-content {
    background:#F5EDE6;
    width:400px;
    padding:25px;
    border-radius:25px;
    position:relative;
}

/* CLOSE BUTTON */
.close {
    position:absolute;
    top:10px;
    left:10px;
    cursor:pointer;
}

/* TEXTAREA */
textarea {
    width:100%;
    padding:12px;
    margin-top:15px;
    border-radius:12px;
}

/* SAVE BUTTON */
.save-btn {
    margin-top:15px;
    float:right;
}
</style>
</head>

<body>

<?php
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

<a href="javascript:history.back()" class="back-btn">←</a>

<!-- REVIEW -->
<div class="card">
    <p><?= $review['reviewText'] ?></p>
    <small><?= $review['firstName'] ?></small>
</div>

<h3>Comments</h3>

<!-- BUTTON RIGHT -->
<div class="comment-btn">
    <button onclick="openComment()">Add Comment</button>
</div>

<!-- COMMENTS -->
<?php while($c = mysqli_fetch_assoc($comments)){ ?>
<div class="card">
    <p><?= $c['commentText'] ?></p>
    <small><?= $c['firstName'] ?></small>
</div>
<?php } ?>

</div>

<!-- MODAL -->
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

window.onclick = function(e){
    let modal = document.getElementById("commentModal");
    if(e.target == modal){
        modal.style.display = "none";
    }
}
</script>

</body>
</html>