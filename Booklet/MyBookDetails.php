<?php
session_start();
// TODO: restore this after login page is ready
// if (!isset($_SESSION['userId'])) { header("Location: index.php"); exit(); }
if (!isset($_SESSION['userId'])) {
    $_SESSION['userId']    = 2;
    $_SESSION['firstName'] = 'Sara';
    $_SESSION['lastName']  = 'Ahmed';
    $_SESSION['role']      = 'Creator';
}
include("DBConnection.php");

$bookId = isset($_GET['bookId']) ? intval($_GET['bookId']) : 0;
$userId = $_SESSION['userId'];

if ($bookId <= 0) {
    header("Location: MyBooks.php");
    exit();
}

$dbc = getConnection();

// fetch book (must belong to this creator)
$stmt = mysqli_prepare($dbc,
    "SELECT b.bookId, b.title, b.author, b.description, b.noPages,
            b.bookCover, b.createdAt, b.viewCount,
            g.genreName
     FROM dbProj_books b
     JOIN dbProj_Genres g ON b.genreId = g.genreId
     WHERE b.bookId = ? AND b.userId = ?"
);
mysqli_stmt_bind_param($stmt, "ii", $bookId, $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$book   = mysqli_fetch_assoc($result);

if (!$book) {
    mysqli_close($dbc);
    header("Location: MyBooks.php");
    exit();
}

// increment view count
$updateStmt = mysqli_prepare($dbc, "UPDATE dbProj_books SET viewCount = COALESCE(viewCount,0) + 1 WHERE bookId = ?");
mysqli_stmt_bind_param($updateStmt, "i", $bookId);
mysqli_stmt_execute($updateStmt);

// get average rating via stored procedure
mysqli_query($dbc, "CALL GetAverageRating($bookId)");
$avgResult = mysqli_store_result($dbc);
$avgRow    = $avgResult ? mysqli_fetch_assoc($avgResult) : null;
$avgRating = $avgRow ? round(floatval($avgRow['AverageRating']), 2) : 0;
if ($avgResult) mysqli_free_result($avgResult);
mysqli_next_result($dbc);

// fetch reviews with reviewer names
$stmt2 = mysqli_prepare($dbc,
    "SELECT r.reviewId, r.rating, r.reviewText, r.createdAt,
            u.firstName, u.lastName
     FROM dbProj_reviews r
     JOIN dbProj_users u ON r.userId = u.userId
     WHERE r.bookId = ?
     ORDER BY r.createdAt DESC"
);
mysqli_stmt_bind_param($stmt2, "i", $bookId);
mysqli_stmt_execute($stmt2);
$reviewResult = mysqli_stmt_get_result($stmt2);
$reviews      = mysqli_fetch_all($reviewResult, MYSQLI_ASSOC);

mysqli_close($dbc);

function renderStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '&#9733;' : '&#9734;';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['title']) ?> – Booklet</title>
    <link rel="stylesheet" href="BookletCSS.css">
    <style>
        body { background: #FFF7EE; }
        .page-wrapper { padding: 1.5rem 2.5rem; }

        .back-arrow {
            font-size: 1.4rem;
            color: #483434;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.5rem;
            transition: opacity 0.2s;
        }
        .back-arrow:hover { opacity: 0.6; }

        .title-row {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        .title-row h1 {
            font-size: 1.8rem;
            color: #483434;
            flex: 1;
        }
        .btn-review, .btn-edit {
            padding: 0.4rem 1.5rem;
            border-radius: 20px;
            border: none;
            font-family: Alice, serif;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.3s, color 0.3s;
        }
        .btn-review {
            background: #FFF7EE;
            color: #483434;
            border: 1px solid #D3C1B4;
        }
        .btn-review:hover { background: #D3C1B4; }
        .btn-edit {
            background: #483434;
            color: #FFF7EE;
        }
        .btn-edit:hover { background: #D3C1B4; color: #483434; }

        .details-section {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .book-cover-img {
            flex: 0 0 140px;
            height: 200px;
            border-radius: 10px;
            object-fit: cover;
            background: #D3C1B4;
        }
        .book-info { flex: 1; color: #483434; }
        .book-info p { margin-bottom: 0.5rem; font-size: 1rem; }
        .book-info .label { font-weight: bold; }
        .stars-avg { color: #C9A84C; font-size: 1.1rem; }
        .description-text { margin-top: 0.8rem; line-height: 1.6; }

        .reviews-section { margin-top: 1rem; }
        .review-card {
            background: #F0E6DC;
            border-radius: 10px;
            padding: 0.9rem 1.2rem;
            margin-bottom: 1rem;
        }
        .review-stars { color: #C9A84C; font-size: 1.2rem; margin-bottom: 0.3rem; }
        .review-text { color: #483434; font-size: 0.95rem; margin-bottom: 0.5rem; line-height: 1.5; }
        .review-by { font-size: 0.85rem; color: #7a6055; }
        .no-reviews { color: #483434; font-style: italic; margin-top: 1rem; }
    </style>
</head>
<body>

<?php include("CreatorNavBar.php"); ?>

<div class="page-wrapper">
    <a href="MyBooks.php" class="back-arrow">&#8592;</a>

    <div class="title-row">
        <h1><?= htmlspecialchars($book['title']) ?></h1>
        <a href="ReviewBook.php?bookId=<?= $bookId ?>" class="btn-review">Review</a>
        <a href="EditBook.php?bookId=<?= $bookId ?>" class="btn-edit">Edit</a>
    </div>

    <div class="details-section">
        <img class="book-cover-img"
             src="../BookCovers/<?= htmlspecialchars(basename($book['bookCover'])) ?>"
             alt="<?= htmlspecialchars($book['title']) ?>"
             onerror="this.style.visibility='hidden'">

        <div class="book-info">
            <p><span class="label">Author:</span> <?= htmlspecialchars($book['author']) ?></p>
            <p><span class="label">Genre:</span> <?= htmlspecialchars($book['genreName']) ?></p>
            <p><span class="label">No. Pages:</span> <?= $book['noPages'] ?> pg</p>
            <p>
                <span class="label">Rating:</span>
                <?= number_format($avgRating, 2) ?>/5
                <span class="stars-avg">&#9733;</span>
            </p>
            <p class="description-text">
                <span class="label">Description:</span><br>
                <?= nl2br(htmlspecialchars($book['description'])) ?>
            </p>
        </div>
    </div>

    <div class="reviews-section">
        <?php if (empty($reviews)): ?>
            <p class="no-reviews">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
            <div class="review-card">
                <div class="review-stars"><?= renderStars($rev['rating']) ?></div>
                <p class="review-text"><?= nl2br(htmlspecialchars($rev['reviewText'])) ?></p>
                <p class="review-by">By: <?= htmlspecialchars($rev['firstName'] . ' ' . $rev['lastName']) ?></p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
