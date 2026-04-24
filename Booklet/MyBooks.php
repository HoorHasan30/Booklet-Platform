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

if ($genreFilter !== "") {
    $sql .= " AND g.genreName = ?";
    $params[] = $genreFilter;
    $types .= "s";
}

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

mysqli_stmt_bind_param($stmt, $types, ...$params);

if (!mysqli_stmt_execute($stmt)) {
    die("Execute Error: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);

$genreQuery = "SELECT genreName FROM dbProj_Genres ORDER BY genreName ASC";
$genreResult = mysqli_query($dbc, $genreQuery);
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
            color: #D3C1B4;
            text-decoration: none;
        }

        .view-more:hover {
            text-decoration: underline;
        }

        .mybooks-top-area {
            margin: 35px 34px 25px 34px;
        }

        .add-book-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 25px;
        }

        .mybooks-add-btn {
            background-color: #D3C1B4;
            color: #483434;
            padding: 12px 45px;
            border-radius: 25px;
            text-decoration: none;
            border: none;
            font-size: 15px;
        }

        .mybooks-filter-form {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: nowrap;
            width: 100%;
        }

        .filter-select {
            height: 42px;
            border-radius: 22px;
            border: 1px solid #D3C1B4;
            background-color: #FFFDF8;
            padding: 0 14px;
            font-size: 14px;
            color: #483434;
        }

        .small-select {
            width: 130px;
        }

        .date-input {
            width: 145px;
        }

        .date-inline {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #483434;
            font-size: 14px;
        }

        .reset-btn,
        .filter-btn {
            height: 42px;
            width: 110px;
            border-radius: 22px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            text-align: center;
        }

        .reset-btn {
            background-color: #483434;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .filter-btn {
            background-color: #D3C1B4;
            color: #483434;
        }
    </style>
</head>

<body>

<?php loadNavBar(); ?>

<div class="mybooks-page">

    <div class="mybooks-top-area">

        <div class="add-book-row">
            <a href="AddBook.php" class="mybooks-add-btn">Add Book</a>
        </div>

        <form method="GET" action="MyBooks.php" class="mybooks-filter-form">

            <select name="rating" class="filter-select small-select">
                <option value="">Rating</option>
                <option value="5" <?php if ($ratingFilter == "5") echo "selected"; ?>>5 Stars</option>
                <option value="4" <?php if ($ratingFilter == "4") echo "selected"; ?>>4 Stars</option>
                <option value="3" <?php if ($ratingFilter == "3") echo "selected"; ?>>3 Stars</option>
                <option value="2" <?php if ($ratingFilter == "2") echo "selected"; ?>>2 Stars</option>
                <option value="1" <?php if ($ratingFilter == "1") echo "selected"; ?>>1 Star</option>
                <option value="0" <?php if ($ratingFilter == "0") echo "selected"; ?>>0 Star</option>
            </select>

            <select name="genre" class="filter-select small-select">
                <option value="">Genre</option>
                <?php while ($genreRow = mysqli_fetch_assoc($genreResult)) { ?>
                    <option value="<?php echo htmlspecialchars($genreRow['genreName']); ?>"
                        <?php if ($genreFilter == $genreRow['genreName']) echo "selected"; ?>>
                        <?php echo htmlspecialchars($genreRow['genreName']); ?>
                    </option>
                <?php } ?>
            </select>

            <div class="date-inline">
                <span>From</span>
                <input type="date" name="dateFrom" class="filter-select date-input"
                       value="<?php echo htmlspecialchars($dateFrom); ?>">
            </div>

            <div class="date-inline">
                <span>To</span>
                <input type="date" name="dateTo" class="filter-select date-input"
                       value="<?php echo htmlspecialchars($dateTo); ?>">
            </div>

            <select name="date" class="filter-select small-select">
                <option value="">Sort By</option>
                <option value="newest" <?php if ($sortDate == "newest") echo "selected"; ?>>
                    Newest
                </option>
                <option value="oldest" <?php if ($sortDate == "oldest") echo "selected"; ?>>
                    Oldest
                </option>
            </select>

            <a href="MyBooks.php" class="reset-btn">Reset</a>

            <button type="submit" class="filter-btn">Apply</button>

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
                            View Details
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