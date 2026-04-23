<?php
include("DBConnection.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin only
if (!isset($_SESSION["role"]) || $_SESSION["role"] != "Admin") {
    header("Location: Home.php");
    exit();
}

function loadNavBar() {
    include("AdminNavBar.php");
}

$dbc = getConnection();

/* TOTAL USERS */
$userCountQuery = "SELECT COUNT(*) AS totalUsers FROM dbProj_users";
$userCountResult = mysqli_query($dbc, $userCountQuery);
$userCountRow = mysqli_fetch_assoc($userCountResult);
$totalUsers = $userCountRow["totalUsers"] ?? 0;

/* TOTAL BOOKS */
$bookCountQuery = "SELECT COUNT(*) AS totalBooks FROM dbProj_books";
$bookCountResult = mysqli_query($dbc, $bookCountQuery);
$bookCountRow = mysqli_fetch_assoc($bookCountResult);
$totalBooks = $bookCountRow["totalBooks"] ?? 0;

/* TOTAL REVIEWS */
$reviewCountQuery = "SELECT COUNT(*) AS totalReviews FROM dbProj_reviews";
$reviewCountResult = mysqli_query($dbc, $reviewCountQuery);
$reviewCountRow = mysqli_fetch_assoc($reviewCountResult);
$totalReviews = $reviewCountRow["totalReviews"] ?? 0;

/* MOST POPULAR BOOKS */
$popularBooksQuery = "SELECT bookId, title, bookCover, viewCount 
                      FROM dbProj_books
                      ORDER BY viewCount DESC, createdAt DESC
                      LIMIT 5";
$popularBooksResult = mysqli_query($dbc, $popularBooksQuery);

/* USERS FOR DROPDOWN */
$usersQuery = "SELECT userId, firstName, lastName
               FROM dbProj_users
               ORDER BY userId ASC";
$usersResult = mysqli_query($dbc, $usersQuery);

/* SELECTED USER */
$selectedUserId = isset($_GET["userId"]) ? (int)$_GET["userId"] : 0;

/* BOOKS CREATED BY USER */
$userBooksResult = null;
if ($selectedUserId > 0) {
    $userBooksQuery = "SELECT b.title, b.author, g.genreName, b.createdAt
                       FROM dbProj_books b
                       JOIN dbProj_Genres g ON b.genreId = g.genreId
                       WHERE b.userId = ?
                       ORDER BY b.createdAt DESC";

    $stmt = mysqli_prepare($dbc, $userBooksQuery);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $selectedUserId);
        mysqli_stmt_execute($stmt);
        $userBooksResult = mysqli_stmt_get_result($stmt);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link rel="stylesheet" href="BookletCSS.css">

    <style>
        .dashboard-page{
            padding: 35px 30px 50px;
            background: #FFF7EE;
            min-height: 100vh;
        }

        .dashboard-title{
            font-size: 2.2rem;
            margin-bottom: 25px;
            color: #483434;
        }

        .stats-row{
            display: flex;
            gap: 35px;
            margin-bottom: 60px;
            flex-wrap: wrap;
        }

        .stat-card{
            width: 320px;
            min-height: 170px;
            border: 2px solid #b7a8a1;
            border-radius: 22px;
            background: #FFFDF8;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .stat-card h3{
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #483434;
            font-weight: normal;
        }

        .stat-card p{
            font-size: 4rem;
            line-height: 1;
            color: #483434;
        }

        .dashboard-section-title{
            font-size: 1.8rem;
            margin-bottom: 25px;
            color: #483434;
        }

        .popular-books-row{
            display: flex;
            gap: 35px;
            flex-wrap: wrap;
            margin-bottom: 70px;
        }

        .popular-book-card{
            width: 170px;
            border: 2px solid #d2c4bc;
            border-radius: 20px;
            background: #f3efea;
            overflow: hidden;
            text-align: center;
        }

        .popular-book-card img{
            width: 100%;
            height: 250px;
            object-fit: cover;
            display: block;
        }

        .popular-book-info{
            padding: 10px;
        }

        .popular-book-info p{
            margin: 0;
            font-size: 13px;
            color: #483434;
        }

        .popular-book-info .views{
            font-size: 12px;
            margin-top: 5px;
            color: #6b4f4f;
        }

        .books-created-section{
            margin-top: 10px;
        }

        .user-filter-row{
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .user-filter-row label{
            font-size: 1.2rem;
            font-weight: bold;
            color: #483434;
        }

        .user-filter-row select{
            width: 190px;
            height: 42px;
            border: 2px solid #D3C1B4;
            border-radius: 20px;
            background: #FFFDF8;
            padding: 0 12px;
            color: #483434;
            font-size: 15px;
            outline: none;
        }

        .dashboard-table{
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px 10px;
        }

        .dashboard-table th{
            background: #6b4f4f;
            color: #FFF7EE;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 1rem;
            font-weight: bold;
            text-align: center;
        }

        .dashboard-table td{
            background: #FFFDF8;
            border: 2px solid #c8b8af;
            border-radius: 18px;
            padding: 12px 18px;
            text-align: center;
            color: #483434;
        }

        .no-data{
            text-align: center;
            font-style: italic;
        }

        @media (max-width: 1100px){
            .stat-card{
                width: 100%;
                max-width: 320px;
            }
        }

        @media (max-width: 768px){
            .popular-book-card{
                width: 140px;
            }

            .popular-book-card img{
                height: 210px;
            }

            .dashboard-title{
                font-size: 1.8rem;
            }

            .dashboard-section-title{
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php loadNavBar(); ?>

    <div class="dashboard-page">

        <h1 class="dashboard-title">Dashboard</h1>

        <div class="stats-row">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo $totalUsers; ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Books</h3>
                <p><?php echo $totalBooks; ?></p>
            </div>

            <div class="stat-card">
                <h3>Total Reviews</h3>
                <p><?php echo $totalReviews; ?></p>
            </div>
        </div>

        <h2 class="dashboard-section-title">Most Popular Books</h2>

        <div class="popular-books-row">
            <?php if ($popularBooksResult && mysqli_num_rows($popularBooksResult) > 0) { ?>
                <?php while ($book = mysqli_fetch_assoc($popularBooksResult)) { ?>
                    <div class="popular-book-card">
                        <img src="<?php echo htmlspecialchars($book["bookCover"]); ?>" alt="<?php echo htmlspecialchars($book["title"]); ?>">
                        <div class="popular-book-info">
                            <p><?php echo htmlspecialchars($book["title"]); ?></p>
                            <p class="views">Views: <?php echo (int)$book["viewCount"]; ?></p>
                        </div>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p>No popular books found.</p>
            <?php } ?>
        </div>

        <div class="books-created-section">
            <h2 class="dashboard-section-title">Books Created By User</h2>

            <form method="GET" action="Dashboard.php">
                <div class="user-filter-row">
                    <label for="userId">User Id</label>

                    <select id="userId" name="userId" onchange="this.form.submit()">
                        <option value="">Choose Id</option>

                        <?php if ($usersResult && mysqli_num_rows($usersResult) > 0) { ?>
                            <?php while ($user = mysqli_fetch_assoc($usersResult)) { ?>
                                <option value="<?php echo $user["userId"]; ?>"
                                    <?php if ($selectedUserId == $user["userId"]) echo "selected"; ?>>
                                    <?php echo $user["userId"] . " - " . htmlspecialchars($user["firstName"] . " " . $user["lastName"]); ?>
                                </option>
                            <?php } ?>
                        <?php } ?>
                    </select>
                </div>
            </form>

            <table class="dashboard-table">
                <tr>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Created on</th>
                </tr>

                <?php if ($selectedUserId > 0 && $userBooksResult && mysqli_num_rows($userBooksResult) > 0) { ?>
                    <?php while ($row = mysqli_fetch_assoc($userBooksResult)) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["title"]); ?></td>
                            <td><?php echo htmlspecialchars($row["author"]); ?></td>
                            <td><?php echo htmlspecialchars($row["genreName"]); ?></td>
                            <td><?php echo htmlspecialchars($row["createdAt"]); ?></td>
                        </tr>
                    <?php } ?>
                <?php } elseif ($selectedUserId > 0) { ?>
                    <tr>
                        <td colspan="4" class="no-data">No books found for this user.</td>
                    </tr>
                <?php } else { ?>
                    <tr>
                        <td colspan="4" class="no-data">Please choose a user first.</td>
                    </tr>
                <?php } ?>
            </table>
        </div>

    </div>

    <?php include("Footer.php"); ?>
</body>
</html>