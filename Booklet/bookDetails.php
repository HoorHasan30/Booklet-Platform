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
    $reviewError = $_SESSION['reviewError'] ?? '';
    $reviewOld = $_SESSION['reviewOld'] ?? [];

    unset($_SESSION['reviewError'], $_SESSION['reviewOld']);

    /* UPDATE VIEW COUNT */
    // PREPARE QUERY
        $updateQuery = "UPDATE dbProj_books 
                        SET viewCount = IFNULL(viewCount, 0) + 1
                        WHERE bookId = ?";

        $updateStmt = mysqli_prepare($dbc, $updateQuery);

        // BIND PARAMETER
        mysqli_stmt_bind_param($updateStmt, "i", $bookId);

        // EXECUTE QUERY
        mysqli_stmt_execute($updateStmt);

    /* GET BOOK + GENRE + CREATOR */
    $bookSql = "SELECT b.*, g.genreName, u.firstName, u.lastName
                FROM dbProj_books b
                LEFT JOIN dbProj_Genres g ON b.genreId = g.genreId
                LEFT JOIN dbProj_users u ON b.userId = u.userId
                WHERE b.bookId = ?";

  // PREPARE QUERY
        $bookStmt = mysqli_prepare($dbc, $bookSql);

        // BIND PARAMETER
        mysqli_stmt_bind_param($bookStmt, "i", $bookId);

        // EXECUTE QUERY
        mysqli_stmt_execute($bookStmt);

        // GET RESULT
        $bookResult = mysqli_stmt_get_result($bookStmt);
    $book = mysqli_fetch_assoc($bookResult);

    if (!$book) {
        die("Book not found.");
    }

    //GET AVG RATING 
    // PREPARE QUERY
        $avgQuery = "CALL GetAverageRating(?)";

        $avgStmt = mysqli_prepare($dbc, $avgQuery);

        // BIND PARAMETER
        mysqli_stmt_bind_param($avgStmt, "i", $bookId);

        // EXECUTE QUERY
        mysqli_stmt_execute($avgStmt);

// GET RESULT
$avgResult = mysqli_stmt_get_result($avgStmt);
    $avg = mysqli_fetch_assoc($avgResult);
    $avgRating = ($avg && isset($avg['AverageRating'])) ? round($avg['AverageRating'], 1) : 0.0;
    mysqli_next_result($dbc);

    /* REVIEWS COUNT */
    $countSql = "SELECT COUNT(*) as total 
                 FROM dbProj_reviews 
                 WHERE bookId = ?";
   // PREPARE QUERY
    $countStmt = mysqli_prepare($dbc, $countSql);

    // BIND PARAMETER
    mysqli_stmt_bind_param($countStmt, "i", $bookId);

    // EXECUTE QUERY
    mysqli_stmt_execute($countStmt);

    // GET RESULT
    $countResult = mysqli_stmt_get_result($countStmt);
    $countRow = mysqli_fetch_assoc($countResult);

    /* REVIEWS LIST */
    $reviewsSql = "SELECT r.*, u.firstName
                   FROM dbProj_reviews r
                   JOIN dbProj_users u ON r.userId = u.userId
                   WHERE r.bookId = ?
                   ORDER BY r.createdAt DESC";

   // PREPARE QUERY
        $reviewsStmt = mysqli_prepare($dbc, $reviewsSql);

        // BIND PARAMETER
        mysqli_stmt_bind_param($reviewsStmt, "i", $bookId);

        // EXECUTE QUERY
        mysqli_stmt_execute($reviewsStmt);

        // GET RESULT
        $reviews = mysqli_stmt_get_result($reviewsStmt);

    /* CHECK IF CURRENT USER ALREADY REVIEWED */
    $alreadyReviewed = false;
    $userReview = null;

    if (isset($_SESSION['userId'])) {
        $uid = (int)$_SESSION['userId'];

      // PREPARE QUERY
        $checkQuery = "SELECT * 
                       FROM dbProj_reviews
                       WHERE userId = ? AND bookId = ?";

        $checkStmt = mysqli_prepare($dbc, $checkQuery);

        // BIND PARAMETERS
        mysqli_stmt_bind_param($checkStmt, "ii", $uid, $bookId);

        // EXECUTE QUERY
        mysqli_stmt_execute($checkStmt);

        // GET RESULT
        $check = mysqli_stmt_get_result($checkStmt);

        if ($row = mysqli_fetch_assoc($check)) {
            $alreadyReviewed = true;
            $userReview = $row;
        }
    }

    /* BOOK COVER */
    $coverPath = !empty($book['bookCover']) 
        ? htmlspecialchars($book['bookCover']) . '?v=' . time()
    : 'BookCovers/default.png';
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Book Details</title>
        <link rel="stylesheet" href="/BookletCSS.css">

        <script>
              // Open the review modal 
            function openReviewModal(rating = 0, text = "", isEdit = false, reviewId = null) {
                document.getElementById("reviewModal").style.display = "flex";
  // Set rating and review text
                document.getElementById("ratingValue").value = rating;
                document.getElementById("reviewText").value = text;
                document.getElementById("reviewModalError").textContent = "";
// Highlight selected stars
                let stars = document.querySelectorAll(".star-input span");
                stars.forEach((s, i) => {
                    s.style.color = i < rating ? "gold" : "lightgray";
                });

                let form = document.getElementById("reviewForm");
   // Switch between add or update review
                if (isEdit) {
                    form.action = "action/updateReview.php";
                    document.getElementById("reviewIdInput").value = reviewId;
                } else {
                    form.action = "action/addReview.php";
                    document.getElementById("reviewIdInput").value = "";
                }
            }
 // Close the review modal and clear error messages
            function closeReviewModal() {
                document.getElementById("reviewModal").style.display = "none";
                document.getElementById("reviewModalError").textContent = "";
            }
// Close the "review already exists" modal
            function closeReviewExists() {
                document.getElementById("reviewExistsModal").style.display = "none";
            }
 // Set selected rating and update star colors
            function setRating(value) {
                document.getElementById("ratingValue").value = value;

                let stars = document.querySelectorAll(".star-input span");
                stars.forEach((s, i) => {
                    s.style.color = i < value ? "gold" : "lightgray";
                });

                document.getElementById("reviewModalError").textContent = "";
            }
 // Validate review form before submission
            function validateReviewForm() {
                const rating = document.getElementById("ratingValue").value;
                const reviewText = document.getElementById("reviewText").value.trim();
                const reviewId = document.getElementById("reviewIdInput").value;
                const errorBox = document.getElementById("reviewModalError");
                  // Check if user already reviewed (from PHP)
                const alreadyReviewed = <?= $alreadyReviewed ? 'true' : 'false' ?>;

                errorBox.textContent = "";

                if (!rating || parseInt(rating) < 1) {
                    errorBox.textContent = "Please select a rating.";
                    return false;
                }

                if (reviewText === "") {
                    errorBox.textContent = "Please write a review.";
                    return false;
                }

                // prevent adding a second review without refresh
                if (alreadyReviewed && reviewId === "") {
                    errorBox.textContent = "You already made a review for this book.";
                    return false;
                }

                return true;
            }
  // Open login modal if user is not logged in
            function openLogin() {
                document.getElementById("loginModal").style.display = "flex";
            }
// Close login modal
            function closeLogin() {
                document.getElementById("loginModal").style.display = "none";
            }
  // Close modals when clicking outside them
            window.onclick = function(e) {
                let m1 = document.getElementById("reviewModal");
                let m2 = document.getElementById("reviewExistsModal");
                let m3 = document.getElementById("loginModal");

                if (e.target == m1) m1.style.display = "none";
                if (e.target == m2) m2.style.display = "none";
                if (e.target == m3) m3.style.display = "none";
            }
        </script>
    </head>
    <body>
  <!-- Login modal content -->
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
<!-- Success message container -->
        <?php if (isset($_SESSION['success'])) { ?>
            <div id="success-msg" class="success-msg">
                <?= $_SESSION['success'] ?>
            </div>
        <?php unset($_SESSION['success']); } ?>

<!-- Main page container -->
        <div class="container">

            <a href="AllBooks.php" class="back-btn">←</a>
  <!-- Top section (title + actions) -->
            <div class="top">
                <h2 class="title-row"><?= htmlspecialchars($book['title']) ?></h2>
                <p class="views"><span>👁 </span> <?= htmlspecialchars($book['viewCount'] ?? 0) ?></p>

                <!-- Buttons container -->
                <div class="top-actions">
                    <?php 
                    if (
                        isset($_SESSION['role']) &&
                        (
                            $_SESSION['role'] == 'Admin' ||
                            ($_SESSION['role'] == 'Creator' && $_SESSION['userId'] == $book['userId'])
                        )
                    ) { 
                    ?>
                        <button class="edit-btn" onclick="location.href='editBook.php?id=<?= $bookId ?>'">
                            🕮 Edit Book
                        </button>
                    <?php } ?>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Creator') { ?>
                        <button class="review-btn"
                            onclick="openReviewModal(
                                <?= $alreadyReviewed ? (int)$userReview['rating'] : 0 ?>,
                                `<?= $alreadyReviewed ? htmlspecialchars($userReview['reviewText'], ENT_QUOTES) : '' ?>`,
                                <?= $alreadyReviewed ? 'true' : 'false' ?>,
                                <?= $alreadyReviewed ? (int)$userReview['reviewId'] : 'null' ?>
                            )">
                            <?= $alreadyReviewed ? '✏ Edit Review' : 'Add Review' ?>
                        </button>
                    <?php } elseif (!isset($_SESSION['role'])) { ?>
                        <button class="review-btn" onclick="openLogin()">🖊 Add Review</button>
                    <?php } ?>
                </div>
            </div>

  <!-- Book details container -->
            <div class="book-box">
                <img src="<?= $coverPath ?>" class="book-img" alt="Book Cover">
 <!-- Book information section -->
                <div class="book-info">
                    <p><span class="label">Author:</span> <?= htmlspecialchars($book['author']) ?></p>
                    <p><span class="label">Genre:</span> <?= htmlspecialchars($book['genreName']) ?></p>
                    <p><span class="label">Pages:</span> <?= htmlspecialchars($book['noPages']) ?></p>
                    <p><span class="label">Average Rating:</span> <?= $avgRating ? $avgRating : "0.0" ?>/5 ⭐</p>
                    <p><span class="label">Created By:</span> <?= htmlspecialchars($book['firstName'] . ' ' . $book['lastName']) ?></p>
                    <p><strong>Created On:</strong> <?= htmlspecialchars($book['createdAt']) ?></p>
                    <br>

                    <div class="desc">
                        <span class="label">Description:</span>
                        <p><?= htmlspecialchars($book['description']) ?></p>
                    </div>
                </div>
            </div>

            <h2 class="space">(<?= htmlspecialchars($countRow['total'] ?? 0) ?>) Reviews</h2>
  <!-- Reviews list container -->
            <div class="reviews-list">
                <?php if (mysqli_num_rows($reviews) == 0) { ?>
                    <p class="no-reviews">There are no reviews for this book.</p>
                <?php } else { ?>
                    <?php while ($r = mysqli_fetch_assoc($reviews)) { ?>
                        <div class="review-card1">
                            <p class="stars"><?= str_repeat("★", (int)$r['rating']) ?></p>
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
  <!-- Review modal container -->
        <div id="reviewModal" class="modal">
              <!-- Review modal content -->
            <div class="modal-content">
                <span class="close" onclick="closeReviewModal()">✖</span>

                <form method="POST" action="action/addReview.php" id="reviewForm" onsubmit="return validateReviewForm()">
                    <input type="hidden" name="reviewId" id="reviewIdInput">
                    <input type="hidden" name="bookId" value="<?= $bookId ?>">
                    <input type="hidden" name="rating" id="ratingValue" value="0">
 <!-- Star rating input -->
                    <div class="star-input">
                        <span onclick="setRating(1)">★</span>
                        <span onclick="setRating(2)">★</span>
                        <span onclick="setRating(3)">★</span>
                        <span onclick="setRating(4)">★</span>
                        <span onclick="setRating(5)">★</span>
                    </div>

                    <p class="error" id="reviewModalError"></p>

                    <textarea name="reviewText" id="reviewText" placeholder="  Write a review"></textarea>

                    <button type="submit" class="save-btn">Save</button>
                </form>
            </div>
        </div>
  <!-- Review exists modal container -->
        <div id="reviewExistsModal" class="modal">
             <!-- Review exists modal content -->
            <div class="modal-content" onclick="event.stopPropagation()">
                <span class="close" onclick="closeReviewExists()">✖</span>
                <h3>You already reviewed this book</h3>
                <button class="ok-btn" onclick="closeReviewExists()">OK</button>
            </div>
        </div>

        <?php include("Footer.php"); ?>
  <?php
    mysqli_stmt_close($updateStmt);
    mysqli_stmt_close($bookStmt);
    mysqli_stmt_close($avgStmt);
    mysqli_stmt_close($countStmt);
    mysqli_stmt_close($reviewsStmt);

    if (isset($checkStmt)) {
        mysqli_stmt_close($checkStmt);
    }

    mysqli_close($dbc);
?>
    </body>
</html>