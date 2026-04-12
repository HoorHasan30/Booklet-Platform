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

    function fixBookCoverPath($coverPath) {
        $fileName = basename($coverPath);

        $fixedNames = array(
            "HarryPoter.jpg" => "HarryPotter.jpg",
            "TheMidnightLibrary.jpg" => "MidnightLibrary.jpg",
            "BeforeTheCoffeeGetsCold.jpg" => "BeforeTheCofeeGetsCold.jpg",
            "AndThenThereWereNone.jpg" => "WereNone.jpg",
            "TheKiteRunner.jfif" => "KiteRunner.jfif",
            "TheSilentPatient.jpg" => "SilentPatient.jpg",
            "TheConvenienceStoreByTheSea.jpg" => "StoreByTheSea.jpg",
            "wonder.jpg" => "Wonder.jpeg",
            "Wonder.jpg" => "Wonder.jpeg"
        );

        if (array_key_exists($fileName, $fixedNames)) {
            return "BookCovers/" . $fixedNames[$fileName];
        }

        return $coverPath;
    }

    $dbc = getConnection();

    $userId = $_SESSION["userId"];
    $sortBy = isset($_GET["sort"]) ? trim($_GET["sort"]) : "newest";
    $ratingFilter = isset($_GET["rating"]) ? trim($_GET["rating"]) : "";

    $sql = "SELECT b.*, 
                   COALESCE(AVG(r.rating), 0) AS avgRating
            FROM dbProj_books b
            LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
            WHERE b.userId = $userId
            GROUP BY b.bookId";

    if ($ratingFilter !== "") {
        $safeRating = (int)$ratingFilter;
        $sql .= " HAVING ROUND(avgRating) = $safeRating";
    }

    if ($sortBy == "oldest") {
        $sql .= " ORDER BY b.createdAt ASC";
    } else {
        $sql .= " ORDER BY b.createdAt DESC";
    }

    $result = mysqli_query($dbc, $sql);

    if (!$result) {
        die("SQL Error: " . mysqli_error($dbc));
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Books</title>
    <link rel="stylesheet" href="BookletCSS.css">
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
                </select>

                <button type="submit" class="mybooks-small-btn">Apply</button>
            </div>

            <a href="#" class="mybooks-add-btn">Add Book</a>
        </form>

        <div class="mybooks-grid">
            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                <div class="mybooks-card">
                    <div class="mybooks-image-box">
                        <img src="<?php echo fixBookCoverPath($row["bookCover"]); ?>" alt="<?php echo htmlspecialchars($row["title"]); ?>">
                    </div>

                    <div class="mybooks-info">
                        <h3><?php echo htmlspecialchars($row["title"]); ?></h3>
                        <p class="mybooks-author">by <?php echo htmlspecialchars($row["author"]); ?></p>
                        <p>Pages: <?php echo htmlspecialchars($row["noPages"]); ?></p>
                        <p>Rating: <?php echo number_format($row["avgRating"], 1); ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>

    </div>

    <?php include("Footer.php"); ?>

</body>
</html>