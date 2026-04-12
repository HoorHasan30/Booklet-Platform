<?php
session_start();
include("DBConnection.php");
$dbc = getConnection();

$bookId = intval($_GET['id']);

// BOOK
$book = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT * FROM dbProj_books WHERE bookId = $bookId
"));


//genre
$book = mysqli_fetch_assoc(mysqli_query($dbc,"
SELECT b.*, g.genreName
FROM dbProj_books b
JOIN dbProj_Genres g ON b.genreId = g.genreId
WHERE b.bookId = $bookId
"));


// AVG RATING (PROCEDURE)
$result = mysqli_query($dbc, "CALL GetAverageRating($bookId)");
$avg = mysqli_fetch_assoc($result);
$avgRating = round($avg['AverageRating'],1);
mysqli_next_result($dbc);

// REVIEWS
$reviews = mysqli_query($dbc,"
SELECT r.*, u.firstName
FROM dbProj_reviews r
JOIN dbProj_users u ON r.userId = u.userId
WHERE r.bookId = $bookId
ORDER BY r.createdAt DESC
");

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
    color: #D3C1B4;
   margin-left:-180px;
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
     border: 3px solid #b8a08d;
}



/* RATING */
.avg-rating {
    margin-top:10px;
    font-size:18px;
}

/* REVIEW CARD */
.review-card1 {
    background: #EFE7DF;
    padding: 20px;
    border-radius: 20px;
    margin-top: 15px;
    border: 2px solid #D3C1B4; 
}

/* MODAL */

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
    padding: 40px 30px 30px 30px;
    text-align: center;
}

/* POPUP BOX */
.modal-content{
    background:#F5EDE6;
    width:400px;
    padding:40px;
    border-radius:25px;
    position:relative;
    border: 3px solid #6b4c3b; 
    gap: 15px;
}

.close {
    position:absolute;
    top:10px;
    left:10px;
    bottom: 10px;
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

/* SAVE */
.save-btn{
    background:#CBB6A6;
    border:none;
    padding:10px 20px;
    border-radius:20px;
    margin-top:15px;
    float:right;
}
.save-btn:hover {
    color: #483434;
    background: #EFE7DF;
     border: 2px solid #b8a08d;
}



.review-btn:hover {
    color: #483434;
    background: #EFE7DF;
     border: 2px solid #b8a08d;
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

.login-title{
    margin-bottom:10px;
}

.login-text{
    margin-bottom:25px; 
    color:#555;
}

.login-actions{
    display:flex;
    justify-content:center;
}

/* LOGIN BUTTON */
.login-btn{
    background:#CBB6A6;
    padding:10px 22px;
    border-radius:20px;
    text-decoration:none;
    width: 90%;       
    margin: 20px auto;
    color:#6b4c3b; 
    text-align: center;
}

/* HOVER */
.login-btn:hover{
   color: #483434;
    background: #EFE7DF;
     border: 2px solid #b8a08d;
}

/* EDIT, REVIEW  BUTTON */
.edit-btn,
.review-btn {
    background:#CBB6A6;
    border:none;
    padding:10px 25px;
    border-radius:20px;
    cursor:pointer;
    color:#6b4c3b;

    display:inline-flex;
    align-items:center;
    justify-content:center;
}

.edit-btn:hover{
   color: #483434;
    background: #EFE7DF;
     border: 2px solid #b8a08d;
}

.top {
       display:flex;
       align-items:center;
       margin-top:30px;
}

.top-actions {
    margin-left: auto;
    display: flex;
    gap: 25px;
    align-items: center;
}
</style>
</head>

<body>
    
   <div id="loginModal" class="modal">
  <div class="modal-content">

    <span class="close" onclick="closeLogin()">✖</span>

   <h3 class="login-title">You need to sign in</h3>
<p class="login-text">Please login to continue</p>

<div class="login-actions">
    <a href="Login.php" class="login-btn">Login</a>

  </div>
</div>
       </div>
       
    
<?php
if(isset($_SESSION['role'])){
    if($_SESSION['role'] == 'Creator'){
        include("CreatorNavBar.php");
    } 
    elseif($_SESSION['role'] == 'Admin'){
        include("AdminNavBar.php");
    } 
    else {
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

    <div class="top-actions">

        <?php 
        if(isset($_SESSION['role']) && 
           ($_SESSION['role'] == 'Admin' || 
           ($_SESSION['role'] == 'Creator' && $_SESSION['userId'] == $book['userId']))
        ){ 
        ?>
        <button class="edit-btn" onclick="location.href='editBook.php?id=<?= $bookId ?>'">
            Edit
        </button>
        <?php } ?>

        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'Creator'){ ?>
            <button class="review-btn" onclick="openReviewModal()">Add Review</button>
        <?php } elseif(!isset($_SESSION['role'])) { ?>
            <button class="review-btn" onclick="openLogin()">Add Review</button>
        <?php } ?>

    </div>

</div>

<div class="book-box">

    <!-- IMAGE -->
<img src="<?= $book['bookCover'] ?>" class="book-img">   
   

  <div class="book-info">

    <p><span class="label">Author:</span> <?= $book['author'] ?></p>
   <p><span class="label">Genre:</span> <?= $book['genreName'] ?></p>

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
    <div class="review-card1">
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


function openLogin(){
    document.getElementById("loginModal").style.display = "flex";
}

function closeLogin(){
    document.getElementById("loginModal").style.display = "none";
}

</script>

</body>
</html>