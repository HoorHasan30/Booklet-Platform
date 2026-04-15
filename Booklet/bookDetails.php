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

<?php
$alreadyReviewed = false;

if(isset($_SESSION['userId'])){
    $uid = $_SESSION['userId'];

    $checkUserReview = mysqli_query($dbc, "
    SELECT reviewId FROM dbProj_reviews 
    WHERE userId = $uid AND bookId = $bookId
    ");

    if(mysqli_num_rows($checkUserReview) > 0){
        $alreadyReviewed = true;
    }
}
?>
<!DOCTYPE html>
<html>
    <head>
    <title>Book Details</title>
    <link rel="stylesheet" href="/BookletCSS.css">
    
<script>
    function openReviewModal(){
        document.getElementById("reviewModal").style.display="flex";
    }

    function closeReviewModal(){
        document.getElementById("reviewModal").style.display="none";
    }

  function handleReviewClick(alreadyReviewed){
    if(alreadyReviewed){
        document.getElementById("reviewExistsModal").style.display="flex";
    } else {
        openReviewModal();
    }
}

function closeReviewExists(){
    document.getElementById("reviewExistsModal").style.display="none";
}

   function setRating(value){
    document.getElementById("ratingValue").value = value;
    let stars=document.querySelectorAll(".star-input span");
    stars.forEach((s,i)=>{ s.style.color=i<value?"gold":"lightgray"; });
}

    function openLogin(){
        document.getElementById("loginModal").style.display = "flex";
    }

    function closeLogin(){
        document.getElementById("loginModal").style.display = "none";
    }

    window.onclick=function(e){
        let m=document.getElementById("reviewModal");
        if(e.target==m) m.style.display="none";

        let m2=document.getElementById("reviewExistsModal"); // NEW
        if(e.target==m2) m2.style.display="none";
    }
</script>
    
    
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
        
        <?php if(isset($_SESSION['success'])){ ?>
    
    <div id="success-msg" class="success-msg">
        <?= $_SESSION['success'] ?>
    </div>

<?php unset($_SESSION['success']); } ?>
        

        <div class="container">

        <a href="AllBooks.php" class="back-btn">←</a>

        <div class="top">
            <h2 class="title-row"><?= htmlspecialchars($book['title']) ?></h2>
            <p class="views"><span>👁 </span> <?= htmlspecialchars($book['viewCount'] ?? 0) ?></p>

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

                    <button class="review-btn" onclick="handleReviewClick(<?= $alreadyReviewed ? 'true' : 'false' ?>)">
                        Add Review
                    </button>

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
                <p><strong>Created On:</strong> <?= $book['createdAt'] ?></p>
                <br>
                <div class="desc">
                    <span class="label">Description:</span>
                    <p><?= htmlspecialchars($book['description']) ?></p>
                </div>
                
        </div>
        </div>

        <h2 class="space">(<?= htmlspecialchars($countRow['total'] ?? 0) ?>) Reviews</h2>

<div class="reviews-list">

<?php if(mysqli_num_rows($reviews) == 0){ ?>

    <p class="no-reviews">There are no reviews for this book.</p>

<?php } else { ?>

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

<?php } ?>

</div>

        </div>

        <div id="reviewModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeReviewModal()">✖</span>

                <form method="POST" action="action/addReview.php">

    <input type="hidden" name="bookId" value="<?= $bookId ?>">  
    <input type="hidden" name="rating" id="ratingValue" value="1">

    <div class="star-input">
        <span onclick="setRating(1)">★</span>
        <span onclick="setRating(2)">★</span>
        <span onclick="setRating(3)">★</span>
        <span onclick="setRating(4)">★</span>
        <span onclick="setRating(5)">★</span>
    </div>

    <textarea name="reviewText" required placeholder="  Write a review"></textarea>

    <button type="submit" class="save-btn">Save</button>

</form>
         </div>
        </div>
        <div id="reviewExistsModal" class="modal">
<div class="modal-content" onclick="event.stopPropagation()">
<span class="close" onclick="closeReviewExists()">✖</span>
<h3>You already reviewed this book</h3>
<button class="ok-btn" onclick="closeReviewExists()">OK</button>
</div>
</div>
        <?php include("Footer.php"); ?>
    </body>
</html>