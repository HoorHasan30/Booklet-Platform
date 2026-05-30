<?php
include("DBConnection.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Protect page: only logged-in creators can access
if (!isset($_SESSION["userId"]) || !isset($_SESSION["role"]) || $_SESSION["role"] != "Creator") {
    header("Location: Login.php");
    exit();
}

function loadNavBar() {
    include("CreatorNavBar.php");
}

$dbc = getConnection();
$userId = (int)$_SESSION["userId"];

// Read filters safely from URL
$search       = isset($_GET["search"]) ? trim($_GET["search"]) : "";
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
            b.createdAt,
            g.genreName,
            COALESCE(AVG(r.rating), 0) AS avgRating
        FROM dbProj_books b
        JOIN dbProj_Genres g ON b.genreId = g.genreId
        LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
        WHERE b.userId = ?";

$params = [$userId];
$types = "i";

/* FULLTEXT SEARCH: title + author */
if ($search !== "") {
    $sql .= " AND MATCH(b.title, b.author) AGAINST (? IN BOOLEAN MODE)";
    $params[] = $search;
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
            b.createdAt,
            g.genreName";

/* RATING FILTER */
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

// PREPARE QUERY
$stmt = mysqli_prepare($dbc, $sql);

// BIND PARAMETERS
mysqli_stmt_bind_param($stmt, $types, ...$params);

// EXECUTE QUERY
mysqli_stmt_execute($stmt);

// GET RESULT
$result = mysqli_stmt_get_result($stmt);

// GET GENRES
$genreQuery = "SELECT genreName 
               FROM dbProj_Genres 
               ORDER BY genreName ASC";

$genreStmt = mysqli_prepare($dbc, $genreQuery);
mysqli_stmt_execute($genreStmt);
$genreResult = mysqli_stmt_get_result($genreStmt);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Books</title>
    <link rel="stylesheet" href="BookletCSS.css">

    <script>
        function openFilterSidebar() {
            document.getElementById("filterSidebar").classList.add("active");
            document.getElementById("filterOverlay").classList.add("active");
        }

        function closeFilterSidebar() {
            document.getElementById("filterSidebar").classList.remove("active");
            document.getElementById("filterOverlay").classList.remove("active");
        }
    </script>
</head>

<body>
    <?php loadNavBar(); ?>

    <div class="mybooks-page">

        <?php if(isset($_SESSION['success'])) { ?>
            <p class="success-msg">
                <?php
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </p>
        <?php } ?>

        <h1 class="page-title">My Books</h1>

        <a href="AddBook.php" class="mybooks-fixed-add-btn">+ Add Book</a>

        <form method="GET" action="MyBooks.php" class="allbooks-filter-form">
            <div class="search-row">
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

        <div id="filterOverlay" class="filter-overlay" onclick="closeFilterSidebar()"></div>

        <div id="filterSidebar" class="filter-sidebar">

            <button type="button" class="close-filter" onclick="closeFilterSidebar()">×</button>

            <h2 class="filter-title">Filter Books</h2>

            <form method="GET" action="MyBooks.php" class="sidebar-filter-form">

                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

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
                        Newest
                    </option>
                    <option value="oldest" <?php if ($sortDate == "oldest") echo "selected"; ?>>
                        Oldest
                    </option>
                </select>

                <div class="sidebar-actions">
                    <a href="MyBooks.php" class="reset-btn">Reset</a>
                    <button type="submit" class="filter-btn">Apply</button>
                </div>

            </form>
        </div>

        <div class="mybooks-grid">

            <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <div class="mybooks-card">

                        <div class="mybooks-image-box">
                            <img src="<?php echo htmlspecialchars($row["bookCover"]); ?>"
                                 alt="<?php echo htmlspecialchars($row["title"]); ?>">
                        </div>

                        <div class="mybooks-info">
                            <h3><?php echo htmlspecialchars($row["title"]); ?></h3>

                            <p class="mybooks-author">
                                by <?php echo htmlspecialchars($row["author"]); ?>
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

    <?php
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($genreStmt);
        mysqli_close($dbc);
    ?>
</body>
</html>