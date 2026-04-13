<?php
    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

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

    // 6 newest books
    $booksSql = "SELECT b.bookId, b.title, b.author, b.bookCover, b.createdAt
                 FROM dbProj_books b
                 ORDER BY b.createdAt DESC
                 LIMIT 6";
    $booksResult = mysqli_query($dbc, $booksSql);

    if (!$booksResult) {
        die("SQL Error (Books): " . mysqli_error($dbc));
    }

    // 6 latest reviews
    $reviewsSql = "SELECT r.reviewId, r.reviewText, r.rating, r.createdAt,
                          b.bookId, b.title, b.bookCover,
                          u.firstName, u.lastName
                   FROM dbProj_reviews r
                   JOIN dbProj_books b ON r.bookId = b.bookId
                   JOIN dbProj_users u ON r.userId = u.userId
                   ORDER BY r.createdAt DESC, r.reviewId DESC
                   LIMIT 6";
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
    <style>
        home-book-link {
            text-decoration: none;
            color: inherit;
        }

        .review-book-title a {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>

<body>
    <?php loadNavBar(); ?>

    <div class="home-hero">
        <div class="home-hero-overlay">
            <h1>Welcome To Booklet</h1>
            <p>Where Readers Share Their Thoughts</p>

            <form action="AllBooks.php" method="GET" class="home-search-form">
                <input type="text" name="search" placeholder="Title / Author">
                <button id="searchBtn" type="submit">Search</button>
            </form>
        </div>
    </div>

    <div class="home-section">
        <h2>Newest Books</h2>

        <div class="home-books-grid">
            <?php while ($book = mysqli_fetch_assoc($booksResult)) { ?>
                <a href="bookDetails.php?id=<?php echo $book['bookId']; ?>" class="home-book-link">
                    <div class="home-book-card">
                        <img src="<?php echo htmlspecialchars($book['bookCover']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                        <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                    </div>
                </a>
            <?php } ?>
        </div>
    </div>

    <div class="home-section reviews-section">
        <h2>Latest Reviews</h2>

        <div class="home-reviews-grid">
            <?php while ($review = mysqli_fetch_assoc($reviewsResult)) { ?>
                <div class="review-card">
                    <a href="bookDetails.php?id=<?php echo $review['bookId']; ?>" class="home-book-link">
                        <div class="review-book-cover">
                            <img src="<?php echo htmlspecialchars($review['bookCover']); ?>" alt="<?php echo htmlspecialchars($review['title']); ?>">
                        </div>
                    </a>

                    <div class="review-content">
                        <h3 class="review-book-title">
                            <a href="bookDetails.php?id=<?php echo $review['bookId']; ?>" class="home-book-link">
                                <?php echo htmlspecialchars($review['title']); ?>
                            </a>
                        </h3>

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