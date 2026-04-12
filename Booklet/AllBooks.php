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

    $dbc = getConnection();

    $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
    $authorFilter = isset($_GET["author"]) ? trim($_GET["author"]) : "";
    $genreFilter = isset($_GET["genre"]) ? trim($_GET["genre"]) : "";
    $ratingFilter = isset($_GET["rating"]) ? trim($_GET["rating"]) : "";
    $sortDate = isset($_GET["date"]) ? trim($_GET["date"]) : "";

    $sql = "SELECT b.*, g.genreName, u.firstName, u.lastName,
                   COALESCE(AVG(r.rating), 0) AS avgRating
            FROM dbProj_books b
            JOIN dbProj_Genres g ON b.genreId = g.genreId
            JOIN dbProj_users u ON b.userId = u.userId
            LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
            WHERE 1=1";

    $params = [];
    $types = "";

    if ($search !== "") {
        $sql .= " AND (b.title LIKE ? OR b.author LIKE ?)";
        $searchLike = "%" . $search . "%";
        $params[] = $searchLike;
        $params[] = $searchLike;
        $types .= "ss";
    }

    if ($authorFilter !== "") {
        $sql .= " AND b.author = ?";
        $params[] = $authorFilter;
        $types .= "s";
    }

    if ($genreFilter !== "") {
        $sql .= " AND g.genreName = ?";
        $params[] = $genreFilter;
        $types .= "s";
    }

    $sql .= " GROUP BY b.bookId, g.genreName, u.firstName, u.lastName";

    if ($ratingFilter !== "") {
        $sql .= " HAVING ROUND(avgRating) = ?";
        $params[] = (int)$ratingFilter;
        $types .= "i";
    }

    if ($sortDate === "oldest") {
        $sql .= " ORDER BY b.createdAt ASC";
    } else {
        $sql .= " ORDER BY b.createdAt DESC";
    }

    $stmt = mysqli_prepare($dbc, $sql);

    if (!$stmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $genreQuery = "SELECT genreName FROM dbProj_Genres ORDER BY genreName ASC";
    $genreResult = mysqli_query($dbc, $genreQuery);

    $authorQuery = "SELECT DISTINCT author FROM dbProj_books ORDER BY author ASC";
    $authorResult = mysqli_query($dbc, $authorQuery);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>All Books</title>
        <link rel="stylesheet" href="BookletCSS.css">
        <style>
            .book-link {
                text-decoration: none;
                color: inherit;
            }
            .view-more {
                display: inline-block;
                margin-top: 8px;
                font-weight: bold;
                color: #D3C1B4;
                text-decoration: none;
            }
            .view-more:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <?php loadNavBar(); ?>

        <div class="allBooks-page">
            <h1 class="page-title">All Books</h1>

            <form method="GET" action="AllBooks.php" class="allbooks-filter-form">
                <div class="search-row">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input"
                        placeholder="Title / Author"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="search-btn">Search</button>
                </div>

                <div class="filter-row">
                    <select name="author" class="filter-select">
                        <option value="">Author</option>
                        <?php while ($authorRow = mysqli_fetch_assoc($authorResult)) { ?>
                            <option value="<?php echo htmlspecialchars($authorRow['author']); ?>" <?php if ($authorFilter == $authorRow['author']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($authorRow['author']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="rating" class="filter-select">
                        <option value="">Rating</option>
                        <option value="5" <?php if ($ratingFilter == "5") echo "selected"; ?>>5 Stars</option>
                        <option value="4" <?php if ($ratingFilter == "4") echo "selected"; ?>>4 Stars</option>
                        <option value="3" <?php if ($ratingFilter == "3") echo "selected"; ?>>3 Stars</option>
                        <option value="2" <?php if ($ratingFilter == "2") echo "selected"; ?>>2 Stars</option>
                        <option value="1" <?php if ($ratingFilter == "1") echo "selected"; ?>>1 Star</option>
                        <option value="0" <?php if ($ratingFilter == "0") echo "selected"; ?>>0 Star</option>
                    </select>

                    <select name="genre" class="filter-select">
                        <option value="">Genre</option>
                        <?php while ($genreRow = mysqli_fetch_assoc($genreResult)) { ?>
                            <option value="<?php echo htmlspecialchars($genreRow['genreName']); ?>" <?php if ($genreFilter == $genreRow['genreName']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($genreRow['genreName']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="date" class="filter-select">
                        <option value="">Date</option>
                        <option value="newest" <?php if ($sortDate == "newest") echo "selected"; ?>>Newest to Oldest</option>
                        <option value="oldest" <?php if ($sortDate == "oldest") echo "selected"; ?>>Oldest to Newest</option>
                    </select>

                    <a href="AllBooks.php" class="reset-btn">Reset</a>
                    <button type="submit" class="filter-btn">Filter</button>
                </div>
            </form>

            <div class="books-container">
                <?php if (mysqli_num_rows($result) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="book-card">
                            <div class="book-image-box">
                                <img src="<?php echo htmlspecialchars($row['bookCover']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                            </div>

                            <div class="book-info">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="author">by <?php echo htmlspecialchars($row['author']); ?></p>

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