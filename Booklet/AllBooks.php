<?php
    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    function loadNavBar(){
        if (isset($_SESSION["role"])) {

            if ($_SESSION["role"] == "Admin") {
                include("AdminNavBar.php");
            }
            elseif ($_SESSION["role"] == "Creator") {
                include("CreatorNavBar.php");
            }
        } 
        else {
            include("VisitorNavBar.php");
        }
    }

    $dbc = getConnection();

    $search       = isset($_GET["search"]) ? trim($_GET["search"]) : "";
    $authorFilter = isset($_GET["author"]) ? trim($_GET["author"]) : "";
    $genreFilter  = isset($_GET["genre"]) ? trim($_GET["genre"]) : "";
    $ratingFilter = isset($_GET["rating"]) ? trim($_GET["rating"]) : "";
    $dateFrom     = isset($_GET["dateFrom"]) ? trim($_GET["dateFrom"]) : "";
    $dateTo       = isset($_GET["dateTo"]) ? trim($_GET["dateTo"]) : "";
    $sortDate     = isset($_GET["date"]) ? trim($_GET["date"]) : "";

    $sql = "SELECT 
                b.bookId,
                b.title,
                b.author,
                b.bookCover,
                b.description,
                b.createdAt,
                g.genreName,
                u.firstName,
                u.lastName,
                COALESCE(AVG(r.rating), 0) AS avgRating
            FROM dbProj_books b
            JOIN dbProj_Genres g ON b.genreId = g.genreId
            JOIN dbProj_users u ON b.userId = u.userId
            LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
            WHERE 1=1";

    $params = [];
    $types = "";

    /* FULLTEXT SEARCH: title + author */
    if ($search !== "") {
        $sql .= " AND MATCH(b.title, b.author) AGAINST (? IN BOOLEAN MODE)";
        $params[] = $search;
        $types .= "s";
    }

    /* AUTHOR FILTER */
    if ($authorFilter !== "") {
        $sql .= " AND b.author = ?";
        $params[] = $authorFilter;
        $types .= "s";
    }

    /* GENRE FILTER */
    if ($genreFilter !== "") {
        $sql .= " AND g.genreName = ?";
        $params[] = $genreFilter;
        $types .= "s";
    }

    /* DATE RANGE */
    if ($dateFrom !== "") {
        $sql .= " AND DATE(b.createdAt) >= ?";
        $params[] = $dateFrom;
        $types .= "s";
    }

    if ($dateTo !== "") {
        $sql .= " AND DATE(b.createdAt) <= ?";
        $params[] = $dateTo;
        $types .= "s";
    }

    $sql .= " GROUP BY 
                b.bookId,
                b.title,
                b.author,
                b.bookCover,
                b.description,
                b.createdAt,
                g.genreName,
                u.firstName,
                u.lastName";

    /* POPULARITY / RATING FILTER */
    if ($ratingFilter !== "") {
        $sql .= " HAVING ROUND(avgRating) = ?";
        $params[] = (int)$ratingFilter;
        $types .= "i";
    }

    /* SORT */
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

    if (!mysqli_stmt_execute($stmt)) {
        die("Execute Error: " . mysqli_stmt_error($stmt));
    }

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
        
        <script>
             // Open filter sidebar and show overlay
            function openFilterSidebar() {
                document.getElementById("filterSidebar").classList.add("active");
                document.getElementById("filterOverlay").classList.add("active");
            }
   // Close filter sidebar and hide overlay
            function closeFilterSidebar() {
                document.getElementById("filterSidebar").classList.remove("active");
                document.getElementById("filterOverlay").classList.remove("active");
            }
        </script>
    </head>


    <body>
        <?php loadNavBar(); ?>

        <div class="allBooks-page">

            <h1 class="page-title">All Books</h1>

            <form method="GET" action="AllBooks.php" class="allbooks-filter-form">

            <div class="search-row">
        <!-- Search input (keeps old value after submit) -->

                <input 
                    type="text" 
                    name="search" 
                    class="search-input"
                    placeholder="Search by Author / Title"
                    value="<?php echo htmlspecialchars($search); ?>"
                >

                <button type="submit" class="search-btn">⌕ Search</button>

                <button type="button" class="filter-icon-btn" onclick="openFilterSidebar()">
                    ☰ Filter
                </button>

            </div>

            </form>
            <!-- Overlay background (click to close sidebar) -->

            <div id="filterOverlay" class="filter-overlay" onclick="closeFilterSidebar()"></div>

            <div id="filterSidebar" class="filter-sidebar">

                <button type="button" class="close-filter" onclick="closeFilterSidebar()">×</button>

                <h2 class="filter-title">Filter Books</h2>

                <form method="GET" action="AllBooks.php" class="sidebar-filter-form">
    <!-- Keep search value when filtering -->

                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                    <select name="author" class="filter-select">
                        <option value="">Author</option>
                            <!-- Loop through authors from DB -->
                        <?php while ($authorRow = mysqli_fetch_assoc($authorResult)) { ?>
                            <option value="<?php echo htmlspecialchars($authorRow['author']); ?>"
                                <?php if ($authorFilter == $authorRow['author']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($authorRow['author']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <select name="rating" class="filter-select">
                        <option value="">Rating</option>
                            <!-- Each option checks if selected -->
                        <option value="5" <?php if ($ratingFilter == "5") echo "selected"; ?>>5 Stars</option>
                        <option value="4" <?php if ($ratingFilter == "4") echo "selected"; ?>>4 Stars</option>
                        <option value="3" <?php if ($ratingFilter == "3") echo "selected"; ?>>3 Stars</option>
                        <option value="2" <?php if ($ratingFilter == "2") echo "selected"; ?>>2 Stars</option>
                        <option value="1" <?php if ($ratingFilter == "1") echo "selected"; ?>>1 Star</option>
                        <option value="0" <?php if ($ratingFilter == "0") echo "selected"; ?>>0 Star</option>
                    </select>

                    <select name="genre" class="filter-select">
                        <option value="">Genre</option>
                            <!-- Loop through genres -->
                        <?php while ($genreRow = mysqli_fetch_assoc($genreResult)) { ?>
                            <option value="<?php echo htmlspecialchars($genreRow['genreName']); ?>"
                                <?php if ($genreFilter == $genreRow['genreName']) echo "selected"; ?>>
                                <?php echo htmlspecialchars($genreRow['genreName']); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <div class="sidebar-date">
                        <span>From</span>
                        <input type="date" name="dateFrom" class="filter-select"
                               value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>

                    <div class="sidebar-date">
                        <span>To</span>
                        <input type="date" name="dateTo" class="filter-select"
                               value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>

                    <select name="date" class="filter-select">
                        <option value="">Sort By</option>
                        <option value="newest" <?php if ($sortDate == "newest") echo "selected"; ?>>
                            Newest to Oldest
                        </option>
                        <option value="oldest" <?php if ($sortDate == "oldest") echo "selected"; ?>>
                            Oldest to Newest
                        </option>
                    </select>

                    <div class="sidebar-actions">
                            <!-- Reset filters -->
                        <a href="AllBooks.php" class="reset-btn">Reset</a>
                            <!-- Apply filters -->
                        <button type="submit" class="filter-btn">Apply</button>
                    </div>

                </form>
            </div>

            <div class="books-container">
    <!-- Check if there are books -->
                <?php if ($result && mysqli_num_rows($result) > 0) { ?>
        <!-- Loop through books -->

                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                        <div class="book-card">

                            <div class="book-image-box">
                                <img 
                                    src="<?php echo htmlspecialchars($row['bookCover']); ?>"
                                    alt="<?php echo htmlspecialchars($row['title']); ?>"
                                >
                            </div>

                            <div class="book-info">
                                <h3><?php echo htmlspecialchars($row['title']); ?></h3>

                                <p class="author">
                                    by <?php echo htmlspecialchars($row['author']); ?>
                                </p>

                                <a href="bookDetails.php?id=<?php echo $row['bookId']; ?>" class="view-more">
                                    → View Details
                                </a>
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