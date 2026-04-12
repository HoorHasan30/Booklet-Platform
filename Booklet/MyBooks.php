<?php
include("DBConnection.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["userId"]) || !isset($_SESSION["role"]) || $_SESSION["role"] != "Creator") {
    header("Location: Login.php");
    exit();
}

function loadNavBar() {
    include("CreatorNavBar.php");
}

$dbc = getConnection();

$userId = (int)$_SESSION["userId"];
$sortBy = isset($_GET["sort"]) ? trim($_GET["sort"]) : "newest";
$ratingFilter = isset($_GET["rating"]) ? trim($_GET["rating"]) : "";

$sql = "SELECT b.*, 
               COALESCE(AVG(r.rating), 0) AS avgRating
        FROM dbProj_books b
        LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
        WHERE b.userId = ?";

$params = [$userId];
$types = "i";

$sql .= " GROUP BY b.bookId";

if ($ratingFilter !== "") {
    $sql .= " HAVING ROUND(avgRating) = ?";
    $params[] = (int)$ratingFilter;
    $types .= "i";
}

if ($sortBy == "oldest") {
    $sql .= " ORDER BY b.createdAt ASC";
} else {
    $sql .= " ORDER BY b.createdAt DESC";
}

$stmt = mysqli_prepare($dbc, $sql);

if (!$stmt) {
    die("SQL Error: " . mysqli_error($dbc));
}

mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Books</title>
    <link rel="stylesheet" href="BookletCSS.css">
    <style>
        .view-more {
            display: inline-block;
            margin-top: 8px;
            font-weight: bold;
            color: #6b4c3b;
            text-decoration: none;
        }
        .view-more:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<?php loadNavBar(); ?>

<div class="mybooks-page">

    <form method="GET" action="MyBooks.php" class="mybooks-topbar">
        <div class="mybooks-left-controls">
            <select name="sort" class="mybooks-select">
                <option value="newest" <?php if ($sortBy == "newest") echo "selected"; ?>>Newest To Oldest</option>
                <option value="oldest" <?php if ($sortBy == "oldest") echo "selected"; ?>>Oldest To Newest</option>
            </select>

            <select name="rating" class="mybooks-select">
                <option value="">Rating</option>
                <option value="5" <?php if ($ratingFilter == "5") echo "selected"; ?>>5 Stars</option>
                <option value="4" <?php if ($ratingFilter == "4") echo "selected"; ?>>4 Stars</option>
                <option value="3" <?php if ($ratingFilter == "3") echo "selected"; ?>>3 Stars</option>
                <option value="2" <?php if ($ratingFilter == "2") echo "selected"; ?>>2 Stars</option>
                <option value="1" <?php if ($ratingFilter == "1") echo "selected"; ?>>1 Star</option>
                <option value="0" <?php if ($ratingFilter == "0") echo "selected"; ?>>0 Star</option>
            </select>

            <button type="submit" class="mybooks-small-btn">Apply</button>
        </div>

        <a href="AddBook.php" class="mybooks-add-btn">Add Book</a>
    </form>

    <div class="mybooks-grid">
        <?php if (mysqli_num_rows($result) > 0) { ?>
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="mybooks-card">
                    <div class="mybooks-image-box">
                        <img src="<?php echo htmlspecialchars($row["bookCover"]); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>">
                    </div>

                    <div class="mybooks-info">
                        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>
                        <p class="mybooks-author">by <?php echo htmlspecialchars($row["author"]); ?></p>
                        <p>Pages: <?php echo htmlspecialchars($row["noPages"]); ?></p>
                        <p>Rating: <?php echo number_format($row["avgRating"], 1); ?></p>

                        <a href="bookDetails.php?id=<?php echo $row['bookId']; ?>" class="view-more">View Details</a>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p>No books found.</p>
        <?php } ?>
    </div>

</div>

<?php include("Footer.php"); ?>

</body>
</html>