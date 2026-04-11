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
   margin-left:-180px;
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
    align-items:flex-start;
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
    background: #EFE7DF;
    padding: 20px;
    border-radius: 20px;
    margin-top: 15px;

    border: 2px solid #D3C1B4; 
}

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


.book-info {
    display:flex;
    flex-direction:column;
    gap:12px; 
    margin-top:10px;
}
.book-info p {
    margin:0;
}
.label {
    font-weight:bold;
}

.desc p {
    margin-top:5px;
    font-weight:normal; 
}

/* SECTION TITLE */
.section-title{
    margin-top:40px;
    margin-bottom:15px;
    font-size:22px;
}

/* REVIEW LIST */
.reviews-list{
    margin-top:10px;
}

/* STARS */
.stars{
    color:gold;
    margin-bottom:8px;
}

/* TEXT */
.review-text{
    margin-bottom:10px;
}

/* USER */
.review-user{
    font-size:13px;
    color:#444;
}

.view-comments{
    display:inline-block;
    margin-top:8px;
    font-size:14px;
    color:#6b4c3b;
    text-decoration:none;
    font-weight:bold;
}

.view-comments:hover{
    text-decoration:underline;
}
</style>
</head>

<body>
<?php
if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'Creator'){
        include("CreatorNavBar.php");
    } else {
        include("AdminNavBar.php");
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
<img src="<?= $book['bookCover'] ?>" class="book-img">   
   

  <div class="book-info">

    <p><span class="label">Author:</span> <?= $book['author'] ?></p>

    <p><span class="label">Pages:</span> <?= $book['noPages'] ?></p>

    <p><span class="label">Average Rating:</span> 
        <?= $avgRating ? $avgRating : "0.0" ?>/5 ⭐
    </p>

    <div class="desc">
        <span class="label">Description:</span>
        <p><?= $book['description'] ?></p>
    </div>

</div>

</div>

<!-- REVIEWS -->
<h2 class="section-title">Reviews</h2>

<div class="reviews-list">
<?php while($r = mysqli_fetch_assoc($reviews)){ ?>
    <div class="review-card">
        <p class="stars">
            <?= str_repeat("⭐", $r['rating']) ?>
        </p>

        <p class="review-text"><?= $r['reviewText'] ?></p>

        <span class="review-user">By: <?= $r['firstName'] ?></span>
        
        <!-- VIEW COMMENTS -->
    <br>
    <a class="view-comments" href="reviewDetails.php?reviewId=<?= $r['reviewId'] ?>">
        View Comments
    </a>
    
    </div>
<?php } ?>
</div>

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