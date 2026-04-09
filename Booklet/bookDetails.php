<?php
session_start();
include("DBConnection.php");
$dbc = getConnection();

if(!isset($_GET['id'])) die("Book not found");

$bookId = $_GET['id'];

// BOOK
$book = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT * FROM dbProj_books WHERE bookId = $bookId
"));

// AVG RATING
$avg = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT AVG(rating) as avgRating FROM dbProj_reviews WHERE bookId = $bookId
"));
$avgRating = round($avg['avgRating'],1);

// REVIEWS
$reviews = mysqli_query($dbc,"
SELECT r.*, u.firstName
FROM dbProj_reviews r
JOIN dbProj_users u ON r.userId = u.userId
WHERE r.bookId = $bookId
ORDER BY r.createdAt DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Book Details</title>

<style>
body { background:#F5EDE6; font-family:Arial; }

.container {
    width:65%;
    margin:auto;
}

/* BACK BUTTON */
.back-btn {
    font-size:25px;
    text-decoration:none;
    color:black;
}

/* HEADER */
.top {
    display:flex;
    justify-content:space-between;
    align-items:center;
}

/* BOOK */
.book-box {
    display:flex;
    gap:30px;
    margin-top:20px;
}

.book-img {
    width:150px;
    height:200px;
    border-radius:12px;
    object-fit:cover;
}

/* BUTTON */
.review-btn {
    background:#CBB6A6;
    border:none;
    padding:10px 25px;
    border-radius:20px;
    cursor:pointer;
}

/* RATING */
.avg-rating {
    margin-top:10px;
    font-size:18px;
}

/* REVIEW CARD */
.review-card {
    background:#EFE7DF;
    border-radius:25px;
    padding:25px;
    margin-top:25px;
}

/* STARS */
.stars { color:gold; }

/* MODAL */
.modal {
    display:none;
    position:fixed;
    top:0; left:0;
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

.close {
    position:absolute;
    top:10px;
    left:10px;
    cursor:pointer;
}

.star-input span {
    font-size:25px;
    cursor:pointer;
    color:lightgray;
}

.star-input .active { color:gold; }

textarea {
    width:100%;
    padding:12px;
    margin-top:15px;
    border-radius:12px;
}

.save-btn {
    background:#CBB6A6;
    border:none;
    padding:10px 25px;
    border-radius:20px;
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

<div class="top">
    <h2><?= $book['title'] ?></h2>
    <button class="review-btn" onclick="openReviewModal()">Add Review</button>
</div>

<div class="book-box">

    <!-- IMAGE -->
    <img src="BookCovers/<?= $book['bookCovers'] ?>" class="book-img">

    <div>
        <p><b>Author:</b> <?= $book['author'] ?></p>
        <p><b>Pages:</b> <?= $book['noPages'] ?></p>

        <div class="avg-rating">
            Rating: <?= $avgRating ? $avgRating : "0.0" ?>/5 ⭐
        </div>

        <p style="margin-top:15px;">
            <?= $book['description'] ?>
        </p>
    </div>

</div>

<!-- REVIEWS -->
<?php while($r = mysqli_fetch_assoc($reviews)){ ?>
<div class="review-card">

    <div class="stars">
        <?= str_repeat("★",$r['rating']) ?>
    </div>

    <p><?= $r['reviewText'] ?></p>
    <small>By: <?= $r['firstName'] ?></small><br>

    <a href="reviewDetails.php?reviewId=<?= $r['reviewId'] ?>">Comments</a>
</div>
<?php } ?>

</div>

<!-- MODAL -->
<div id="reviewModal" class="modal">
<div class="modal-content">

<span class="close" onclick="closeReviewModal()">✖</span>

<form method="POST" action="action/addReview.php">

<input type="hidden" name="bookId" value="<?= $bookId ?>">
<input type="hidden" name="rating" id="ratingValue">

<div class="star-input">
    <span onclick="setRating(1)">★</span>
    <span onclick="setRating(2)">★</span>
    <span onclick="setRating(3)">★</span>
    <span onclick="setRating(4)">★</span>
    <span onclick="setRating(5)">★</span>
</div>

<textarea name="reviewText" required placeholder="Write a review"></textarea>

<div style="overflow:hidden;">
    <button class="save-btn">Save</button>
</div>

</form>

</div>
</div>

<script>
function openReviewModal(){
    document.getElementById("reviewModal").style.display="flex";
}
function closeReviewModal(){
    document.getElementById("reviewModal").style.display="none";
}

function setRating(value){
    document.getElementById("ratingValue").value=value;
    let stars=document.querySelectorAll(".star-input span");

    stars.forEach((s,i)=>{
        if(i<value) s.classList.add("active");
        else s.classList.remove("active");
    });
}

window.onclick=function(e){
let m=document.getElementById("reviewModal");
if(e.target==m) m.style.display="none";
}
</script>

</body>
</html>