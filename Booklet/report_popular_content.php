<?php
    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) session_start();

    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
        header("Location: Login.php");
        exit();
    }

    $dbc = getConnection();

    // Date range from form
    // Default is no filter
    $date_from = isset($_GET['date_from']) && $_GET['date_from'] !== ''
        ? $_GET['date_from'] : '';

    $date_to = isset($_GET['date_to']) && $_GET['date_to'] !== ''
        ? $_GET['date_to'] : '';

    $dateCondition = "";
    $useDateFilter = ($date_from !== '' && $date_to !== '');

    if ($useDateFilter) {
        $dateCondition = "WHERE DATE(b.createdAt) BETWEEN ? AND ?";
    }

    // Query: most popular books by view count
    $stmt = mysqli_prepare($dbc, "
        SELECT
            b.bookId,
            b.title,
            b.author,
            b.bookCover,
            b.viewCount,
            b.createdAt,
            g.genreName,
            u.firstName,
            u.lastName,
            COUNT(r.reviewId)          AS reviewCount,
            COALESCE(AVG(r.rating), 0) AS avgRating
        FROM dbProj_books b
        JOIN dbProj_users  u ON b.userId  = u.userId
        JOIN dbProj_Genres g ON b.genreId = g.genreId
        LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
        $dateCondition
        GROUP BY
            b.bookId, b.title, b.author, b.bookCover,
            b.viewCount, b.createdAt, g.genreName,
            u.firstName, u.lastName
        ORDER BY b.viewCount DESC
        LIMIT 50
    ");

    if ($useDateFilter) {
        mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }

    mysqli_stmt_close($stmt);

    // Summary totals
    $total_views   = array_sum(array_column($rows, 'viewCount'));
    $total_reviews = array_sum(array_column($rows, 'reviewCount'));
    $total_books   = count($rows);

    // Chart data
    $genreCounts = [];
    $topBookTitles = [];
    $topBookViews = [];

    foreach ($rows as $row) {
        $genre = $row['genreName'];

        if (!isset($genreCounts[$genre])) {
            $genreCounts[$genre] = 0;
        }

        $genreCounts[$genre]++;
    }

    $topBooks = array_slice($rows, 0, 5);

    foreach ($topBooks as $row) {
        $topBookTitles[] = $row['title'];
        $topBookViews[] = (int)$row['viewCount'];
    }

    $genreLabels = array_keys($genreCounts);
    $genreValues = array_values($genreCounts);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Popular Content Report</title>
    <link rel="stylesheet" href="BookletCSS.css">

    <!-- Chart.js library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Page charts -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const genreLabels = <?= json_encode($genreLabels) ?>;
            const genreValues = <?= json_encode($genreValues) ?>;

            const topBookTitles = <?= json_encode($topBookTitles) ?>;
            const topBookViews = <?= json_encode($topBookViews) ?>;

            const genreCanvas = document.getElementById("genreChart");
            const topViewsCanvas = document.getElementById("topViewsChart");

            if (genreCanvas) {
                new Chart(genreCanvas, {
                    type: "doughnut",
                    data: {
                        labels: genreLabels,
                        datasets: [{
                            data: genreValues,
                            backgroundColor: [
                                "#483434",
                                "#D3C1B4",
                                "#A98F80",
                                "#FFF7EE",
                                "#7A5C58",
                                "#C8AA9A"
                            ],
                            borderColor: "#FFFDF8",
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "62%",
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: {
                                    color: "#483434",
                                    font: {
                                        family: "Alice",
                                        size: 13
                                    }
                                }
                            }
                        }
                    }
                });
            }

            if (topViewsCanvas) {
                new Chart(topViewsCanvas, {
                    type: "bar",
                    data: {
                        labels: topBookTitles,
                        datasets: [{
                            label: "Views",
                            data: topBookViews,
                            backgroundColor: "#D3C1B4",
                            borderColor: "#483434",
                            borderWidth: 2,
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: "#483434",
                                    maxRotation: 35,
                                    minRotation: 0
                                }
                            },
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: "#eee3d8"
                                },
                                ticks: {
                                    color: "#483434",
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

        });
    </script>
</head>

<body>

    <?php include("AdminNavBar.php"); ?>

    <div class="report-page">

        <div class="report-title">
            <h1>All Books Report</h1>
        </div>

        <div class="report-top-row no-print">

            <div class="filter-card">
                <form method="GET" action="report_popular_content.php">
                    <div class="filter-row">

                        <div class="filter-field">
                            <label>From</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        </div>

                        <div class="filter-field">
                            <label>To</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                        </div>

                        <button type="submit" class="report-btn report-btn-primary">Apply</button>

                        <a href="report_popular_content.php" class="report-clear-btn">Clear</a>

                    </div>
                </form>
            </div>

            <div class="report-actions">
                <a href="report_by_user.php" class="switch-link">← Users Report</a>
                <button class="report-btn report-btn-secondary" onclick="window.print()">🗎 Print / Save PDF</button>
            </div>

        </div>

        <?php if (!empty($rows)): ?>

        <!-- Summary stats -->
        <div class="stats-row">

            <div class="stat-box">
                <div class="stat-value"><?= $total_books ?></div>
                <div class="stat-label">Books</div>
            </div>

            <div class="stat-box">
                <div class="stat-value"><?= number_format($total_views) ?></div>
                <div class="stat-label">Views</div>
            </div>

            <div class="stat-box">
                <div class="stat-value"><?= number_format($total_reviews) ?></div>
                <div class="stat-label">Reviews</div>
            </div>

        </div>

        <!-- Charts -->
        <div class="report-charts-row no-print">

            <div class="report-chart-box">
                <h3>Genres</h3>
                <canvas id="genreChart"></canvas>
            </div>

            <div class="report-chart-box">
                <h3>Top 5 Books by Views</h3>
                <canvas id="topViewsChart"></canvas>
            </div>

        </div>

        <!-- Meta line -->
        <div class="report-meta">
            <span>Showing top <?= $total_books ?> books ordered by views</span>
            <span>Generated: <?= date('F j, Y \a\t g:i A') ?></span>
        </div>

        <!-- Table -->
        <div class="report-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Creator</th>
                        <th>Views</th>
                        <th>Reviews</th>
                        <th>Avg Rating</th>
                        <th>Added On</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($rows as $i => $row):
                        $rank = $i + 1;
                        $rankClass = $rank <= 3 ? 'rank-' . $rank : 'rank-n';

                        $avg = round((float)$row['avgRating'], 1);
                        $stars = str_repeat('★', (int)round($avg)) . str_repeat('☆', 5 - (int)round($avg));
                    ?>
                    <tr class="clickable-row" onclick="window.location.href='bookDetails.php?id=<?= $row['bookId'] ?>'">
                        <td>
                            <span class="rank-badge <?= $rankClass ?>"><?= $rank ?></span>
                        </td>

                        <td>
                            <img class="cover-thumb"
                                 src="<?= htmlspecialchars($row['bookCover']) ?>"
                                 alt="<?= htmlspecialchars($row['title']) ?>">
                        </td>

                        <td><strong><?= htmlspecialchars($row['title']) ?></strong></td>

                        <td><?= htmlspecialchars($row['author']) ?></td>

                        <td>
                            <span class="genre-pill"><?= htmlspecialchars($row['genreName']) ?></span>
                        </td>

                        <td><?= htmlspecialchars($row['firstName'] . ' ' . $row['lastName']) ?></td>

                        <td><strong><?= number_format($row['viewCount']) ?></strong></td>

                        <td><?= (int)$row['reviewCount'] ?></td>

                        <td>
                            <span class="stars-gold"><?= $stars ?></span>
                            <small>(<?= $avg ?>)</small>
                        </td>

                        <td><?= date('M d, Y', strtotime($row['createdAt'])) ?></td>
                    </tr>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php else: ?>

        <div class="report-table-wrap">
            <div class="empty-state">
                <p>No books found.</p>
            </div>
        </div>

        <?php endif; ?>

    </div>

    <?php include("Footer.php"); ?>

</body>
</html>

<?php mysqli_close($dbc); ?>