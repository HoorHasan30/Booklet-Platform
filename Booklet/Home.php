<?php
    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    function loadNavBar() {
        if (isset($_SESSION["role"])) {
            if ($_SESSION["role"] == "Admin") {
                include("AdminNavBar.php");
            } elseif ($_SESSION["role"] == "Creator") {
                include("CreatorNavBar.php");
            } else {
                include("VisitorNavBar.php");
            }
        } else {
            include("VisitorNavBar.php");
        }
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

    // Newest books
    $booksSql = "SELECT b.bookId, b.title, b.author, b.bookCover
                 FROM dbProj_books b
                 ORDER BY b.createdAt DESC
                 LIMIT 5";
    $booksResult = mysqli_query($dbc, $booksSql);

    if (!$booksResult) {
        die("SQL Error (Books): " . mysqli_error($dbc));
    }

    // Latest reviews
    $reviewsSql = "SELECT r.reviewText, r.rating, r.createdAt,
                          b.title, b.bookCover,
                          u.firstName, u.lastName
                   FROM dbProj_reviews r
                   JOIN dbProj_books b ON r.bookId = b.bookId
                   JOIN dbProj_users u ON r.userId = u.userId
                   ORDER BY r.createdAt DESC, r.reviewId DESC
                   LIMIT 4";
    $reviewsResult = mysqli_query($dbc, $reviewsSql);

    if (!$reviewsResult) {
        die("SQL Error (Reviews): " . mysqli_error($dbc));
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <link rel="stylesheet" href="BookletCSS.css">
</head>

<body>
    <?php loadNavBar(); ?>

    <div class="home-hero">
        <div class="home-hero-overlay">
            <h1>Welcome To Booklet</h1>
            <p>Where Readers Share Their Thoughts</p>

            <form action="AllBooks.php" method="GET" class="home-search-form">
                <input type="text" name="search" placeholder="Title / Author">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="home-section">
        <h2>Newest Books</h2>

        <div class="home-books-grid">
            <?php while ($book = mysqli_fetch_assoc($booksResult)) { ?>
                <div class="home-book-card">
                    <img src="<?php echo fixBookCoverPath($book['bookCover']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="home-section reviews-section">
        <h2>Latest Reviews</h2>

        <div class="home-reviews-grid">
            <?php while ($review = mysqli_fetch_assoc($reviewsResult)) { ?>
                <div class="review-card">
                    <div class="review-book-cover">
                        <img src="<?php echo fixBookCoverPath($review['bookCover']); ?>" alt="<?php echo htmlspecialchars($review['title']); ?>">
                    </div>

                    <div class="review-content">
                        <div class="review-stars">
                            <?php
                                $rating = (int)$review['rating'];
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? "★" : "☆";
                                }
                            ?>
                        </div>

                        <p class="review-text">
                            <?php
                                $text = trim($review['reviewText']);
                                if ($text === "") {
                                    echo "No review text available.";
                                } else {
                                    echo htmlspecialchars(substr($text, 0, 85));
                                }
                            ?>
                        </p>

                        <p class="review-by">
                            By: <?php echo htmlspecialchars($review['firstName'] . " " . $review['lastName']); ?>
                        </p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php include("Footer.php"); ?>
</body>
</html>