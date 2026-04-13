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
        } else {
            include("VisitorNavBar.php");
        }
    }
    
    $dbc = getConnection();

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        die("Invalid book ID.");
    }

    $bookId = (int)$_GET['id'];

    function fixBookCoverPath($coverPath) {
        $fileName = basename($coverPath);
        return "BookCovers/" . $fileName;
    }

    /* UPDATE VIEW COUNT */
    $updateSql = "UPDATE dbProj_books 
                  SET viewCount = IFNULL(viewCount, 0) + 1
                  WHERE bookId = ?";
    $updateStmt = mysqli_prepare($dbc, $updateSql);

    if (!$updateStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($updateStmt, "i", $bookId);
    mysqli_stmt_execute($updateStmt);

    /* GET BOOK + GENRE + CREATOR */
    $bookSql = "SELECT b.*, g.genreName, u.firstName, u.lastName
                FROM dbProj_books b
                LEFT JOIN dbProj_Genres g ON b.genreId = g.genreId
                LEFT JOIN dbProj_users u ON b.userId = u.userId
                WHERE b.bookId = ?";

    $bookStmt = mysqli_prepare($dbc, $bookSql);

    if (!$bookStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($bookStmt, "i", $bookId);
    mysqli_stmt_execute($bookStmt);
    $bookResult = mysqli_stmt_get_result($bookStmt);
    $book = mysqli_fetch_assoc($bookResult);

    if (!$book) {
        die("Book not found.");
    }

    $book["bookCover"] = fixBookCoverPath($book["bookCover"]);

    /* GET AVG RATING (PROCEDURE) */
    $avgStmt = mysqli_prepare($dbc, "CALL GetAverageRating(?)");

    if (!$avgStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($avgStmt, "i", $bookId);
    mysqli_stmt_execute($avgStmt);
    $avgResult = mysqli_stmt_get_result($avgStmt);
    $avg = mysqli_fetch_assoc($avgResult);
    $avgRating = ($avg && isset($avg['AverageRating'])) ? round($avg['AverageRating'], 1) : 0.0;
    mysqli_next_result($dbc);

    /* REVIEWS COUNT */
    $countSql = "SELECT COUNT(*) as total 
                 FROM dbProj_reviews 
                 WHERE bookId = ?";
    $countStmt = mysqli_prepare($dbc, $countSql);

    if (!$countStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($countStmt, "i", $bookId);
    mysqli_stmt_execute($countStmt);
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);

    /* REVIEWS LIST */
    $reviewsSql = "SELECT r.*, u.firstName
                   FROM dbProj_reviews r
                   JOIN dbProj_users u ON r.userId = u.userId
                   WHERE r.bookId = ?
                   ORDER BY r.createdAt DESC";

    $reviewsStmt = mysqli_prepare($dbc, $reviewsSql);

    if (!$reviewsStmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($reviewsStmt, "i", $bookId);
    mysqli_stmt_execute($reviewsStmt);
    $reviews = mysqli_stmt_get_result($reviewsStmt);
?>

<!DOCTYPE html>
<html>
    <head>
    <title>Book Details</title>
    
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
    
    <style>
    body { background:#FFF7EE; font-family: Alice; color: #483434; }

    .container {
        width:65%;
        margin:auto;
    }

   .back-btn {
        font-size:25px;
        text-decoration:none;
        color: #483434;
        margin-left:-180px;
        margin-bottom: 30px;
    }

    .book-box {
        display:flex;
        gap:30px;
        margin-top:20px;
        align-items:center;
    }

    .reviews-list {
        margin-bottom: 60px;   
    }

    .book-img {
        width:150px;
        height:200px;
        border-radius:12px;
        object-fit:cover;
        border: 3px solid #D3C1B4;
    }

    .avg-rating {
        margin-top:10px;
        font-size:18px;
    }

    .review-card1 {
        background: #FFFDF8;
        padding: 20px;
        border-radius: 20px;
        margin-top: 15px;
        border: 2px solid #483434; 
    }

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

    .modal-content{
        background:#FFFDF8;
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

    .save-btn{
        background:#D3C1B4;
        border:none;
        padding:10px 20px;
        border-radius:20px;
        margin-top:15px;
        float:right;
    }
    .save-btn:hover {
        background: #FFFDF8;
        border: 2px solid #483434;
    }

    .review-btn:hover {
        background: #FFFDF8;
        border: 2px solid #483434;
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

    .section-title{
        margin-top:40px;
        margin-bottom:15px;
        font-size:22px;
    }

    .reviews-list{
        margin-top:10px;
    }

    .stars{
        color:gold;
        margin-bottom:8px;
    }

    .review-text{
        margin-bottom:10px;
    }

    .review-user{
        font-size:13px;
        color:#444;
    }

    .view-comments{
        display:inline-block;
        margin-top:8px;
        font-size:14px;
        color:#D3C1B4;
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

    .login-btn{
        background:#D3C1B4;
        padding:10px 22px;
        border-radius:20px;
        text-decoration:none;
        width: 90%;       
        margin: 20px auto;
        color:#6b4c3b; 
        text-align: center;
    }

    .login-btn:hover{
        background: #FFFDF8;
        border: 2px solid #483434;
    }

    .edit-btn,
    .review-btn {
        background:#D3C1B4;
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
        
        background: #FFFDF8;
        border: 2px solid #483434;
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

        <?php loadNavBar(); ?>

        <div class="container">

        <a href="javascript:history.back()" class="back-btn">←</a>

        <div class="top">
            <h2><?= htmlspecialchars($book['title']) ?></h2>

            <div class="top-actions">
                <?php 
                if (isset($_SESSION['role']) && 
                   ($_SESSION['role'] == 'Admin' || 
                   ($_SESSION['role'] == 'Creator' && $_SESSION['userId'] == $book['userId']))
                ) { 
                ?>
                    <button class="edit-btn" onclick="location.href='editBook.php?id=<?= $bookId ?>'">
                        Edit
                    </button>
                <?php } ?>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Creator') { ?>
                    <button class="review-btn" onclick="openReviewModal()">Add Review</button>
                <?php } elseif (!isset($_SESSION['role'])) { ?>
                    <button class="review-btn" onclick="openLogin()">Add Review</button>
                <?php } ?>
            </div>
        </div>

        <div class="book-box">
            <img src="<?= htmlspecialchars($book['bookCover']) ?>" class="book-img">   

            <div class="book-info">
                <p><span class="label">Author:</span> <?= htmlspecialchars($book['author']) ?></p>
                <p><span class="label">Genre:</span> <?= htmlspecialchars($book['genreName']) ?></p>
                <p><span class="label">Pages:</span> <?= htmlspecialchars($book['noPages']) ?></p>
                <p><span class="label">Average Rating:</span> <?= $avgRating ? $avgRating : "0.0" ?>/5 ⭐</p>
                <p><span class="label">Created By:</span> <?= htmlspecialchars($book['firstName'] . ' ' . $book['lastName']) ?></p> 
                <p><strong>Created:</strong> <?= $book['createdAt'] ?></p>
                <br>
                <div class="desc">
                    <span class="label">Description:</span>
                    <p><?= htmlspecialchars($book['description']) ?></p>
                </div>
                
                <br>
                <p><span class="label">Views:</span> <?= htmlspecialchars($book['viewCount'] ?? 0) ?></p> 
            </div>
        </div>

        <h2 class="section-title">(<?= htmlspecialchars($countRow['total'] ?? 0) ?>) Reviews</h2>

        <div class="reviews-list">
        <?php while($r = mysqli_fetch_assoc($reviews)) { ?>
            <div class="review-card1">
                <p class="stars"><?= str_repeat("⭐", (int)$r['rating']) ?></p>
                <p class="review-text"><?= htmlspecialchars($r['reviewText']) ?></p>
                <span class="review-user">By: <?= htmlspecialchars($r['firstName']) ?></span>
                <br>
                <a class="view-comments" href="reviewDetails.php?reviewId=<?= $r['reviewId'] ?>">
                    View Comments
                </a>
            </div>
        <?php } ?>
        </div>

        </div>

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
        <?php include("Footer.php"); ?>
    </body>
</html>