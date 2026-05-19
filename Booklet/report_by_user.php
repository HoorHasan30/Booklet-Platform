<?php
    include("DBConnection.php");
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
        header("Location: Login.php");
        exit();
    }

    $dbc = getConnection();

    // ── Load all creators for dropdown ────────────────────────────
    $usersResult = mysqli_query($dbc, "
        SELECT userId, firstName, lastName, email
        FROM dbProj_users
        WHERE role = 'Creator'
        ORDER BY firstName, lastName ASC
    ");

    $allUsers = [];
    while ($u = mysqli_fetch_assoc($usersResult)) {
        $allUsers[] = $u;
    }

    // ── Selected user & their books ───────────────────────────────
    $selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $selectedUser = null;
    $books = [];
    $userStats = [
        'totalBooks' => 0,
        'totalViews' => 0,
        'totalReviews' => 0,
        'avgRating' => 0
    ];

    if ($selected_user_id > 0) {

        // Get user info
        $uStmt = mysqli_prepare($dbc, "
            SELECT userId, firstName, lastName, email
            FROM dbProj_users
            WHERE userId = ? AND role = 'Creator'
        ");

        mysqli_stmt_bind_param($uStmt, "i", $selected_user_id);
        mysqli_stmt_execute($uStmt);
        $uRes = mysqli_stmt_get_result($uStmt);
        $selectedUser = mysqli_fetch_assoc($uRes);
        mysqli_stmt_close($uStmt);

        if ($selectedUser) {

            // Get all books by this user with stats
            $bStmt = mysqli_prepare($dbc, "
                SELECT
                    b.bookId,
                    b.title,
                    b.author,
                    b.bookCover,
                    b.viewCount,
                    b.createdAt,
                    b.noPages,
                    g.genreName,
                    COUNT(r.reviewId)          AS reviewCount,
                    COALESCE(AVG(r.rating), 0) AS avgRating
                FROM dbProj_books b
                JOIN dbProj_Genres g ON b.genreId = g.genreId
                LEFT JOIN dbProj_reviews r ON b.bookId = r.bookId
                WHERE b.userId = ?
                GROUP BY
                    b.bookId, b.title, b.author, b.bookCover,
                    b.viewCount, b.createdAt, b.noPages, g.genreName
                ORDER BY b.createdAt DESC
            ");

            mysqli_stmt_bind_param($bStmt, "i", $selected_user_id);
            mysqli_stmt_execute($bStmt);
            $bRes = mysqli_stmt_get_result($bStmt);

            while ($b = mysqli_fetch_assoc($bRes)) {
                $books[] = $b;
            }

            mysqli_stmt_close($bStmt);

            // Summary stats for this user
            if (!empty($books)) {
                $userStats['totalBooks'] = count($books);
                $userStats['totalViews'] = array_sum(array_column($books, 'viewCount'));
                $userStats['totalReviews'] = array_sum(array_column($books, 'reviewCount'));

                $allRatings = array_filter(array_column($books, 'avgRating'));
                $userStats['avgRating'] = !empty($allRatings)
                    ? round(array_sum($allRatings) / count($allRatings), 1)
                    : 0;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users Content Report</title>
    <link rel="stylesheet" href="BookletCSS.css">
</head>

<body>

    <?php include("AdminNavBar.php"); ?>

    <div class="report-page">

        <div class="report-title">
            <h1>Users Content Report</h1>
        </div>

        <div class="report-top-row no-print">

            <div class="filter-card">
                <form method="GET" action="report_by_user.php">
                    <div class="filter-row">

                        <div class="filter-field">
                            <label>Creator</label>

                            <select name="user_id" class="report-select">
                                <option value="">Choose a creator</option>

                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?= $u['userId'] ?>"
                                        <?= ($selected_user_id == $u['userId']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['firstName'] . ' ' . $u['lastName']) ?>
                                        (<?= htmlspecialchars($u['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" class="report-btn report-btn-primary">Generate</button>

                    </div>
                </form>
            </div>

            <div class="report-actions">
                <a href="report_popular_content.php" class="switch-link">← All Books Report</a>

                <?php if ($selectedUser && !empty($books)): ?>
                    <button class="report-btn report-btn-secondary" onclick="window.print()">🗎 Print / Save PDF</button>
                <?php endif; ?>
            </div>

        </div>

        <?php if ($selected_user_id === 0): ?>

            <div class="select-prompt">
                <p>Select a creator from the drop-down above to generate their content report.</p>
            </div>

        <?php elseif (!$selectedUser): ?>

            <div class="select-prompt">
                <p>User not found. Please select a valid creator.</p>
            </div>

        <?php else: ?>

            <div class="user-banner">
                <div class="user-avatar">
                    <?= strtoupper(substr($selectedUser['firstName'], 0, 1)) ?>
                </div>

                <div class="user-banner-info">
                    <h2><?= htmlspecialchars($selectedUser['firstName'] . ' ' . $selectedUser['lastName']) ?></h2>
                    <p><?= htmlspecialchars($selectedUser['email']) ?> &nbsp;·&nbsp; Creator</p>
                </div>
            </div>

            <div class="stats-row user-stats-row">
                <div class="stat-box">
                    <div class="stat-value"><?= $userStats['totalBooks'] ?></div>
                    <div class="stat-label">Books Added</div>
                </div>

                <div class="stat-box">
                    <div class="stat-value"><?= number_format($userStats['totalViews']) ?></div>
                    <div class="stat-label">Views</div>
                </div>

                <div class="stat-box">  
                    <div class="stat-value"><?= number_format($userStats['totalReviews']) ?></div>
                    <div class="stat-label">Reviews</div>
                </div>

                <div class="stat-box">
                    <div class="stat-value"><?= $userStats['avgRating'] ?>/5</div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>

            <?php if (!empty($books)): ?>

                <div class="report-meta">
                    <span><?= $userStats['totalBooks'] ?> book(s) by <?= htmlspecialchars($selectedUser['firstName'] . ' ' . $selectedUser['lastName']) ?></span>
                    <span>Generated: <?= date('F j, Y \a\t g:i A') ?></span>
                </div>

                <div class="report-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cover</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Genre</th>
                                <th>Pages</th>
                                <th>Views</th>
                                <th>Reviews</th>
                                <th>Avg Rating</th>
                                <th>Added On</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($books as $i => $book):
                                $avg = round((float)$book['avgRating'], 1);
                                $stars = str_repeat('★', (int)round($avg)) . str_repeat('☆', 5 - (int)round($avg));
                            ?>

                            <tr>
                                <td><?= $i + 1 ?></td>

                                <td>
                                    <img class="cover-thumb"
                                         src="<?= htmlspecialchars($book['bookCover']) ?>"
                                         alt="<?= htmlspecialchars($book['title']) ?>">
                                </td>

                                <td><strong><?= htmlspecialchars($book['title']) ?></strong></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>

                                <td>
                                    <span class="genre-pill"><?= htmlspecialchars($book['genreName']) ?></span>
                                </td>

                                <td><?= number_format($book['noPages']) ?> pg</td>
                                <td><strong><?= number_format($book['viewCount']) ?></strong></td>
                                <td><?= (int)$book['reviewCount'] ?></td>

                                <td>
                                    <span class="stars-gold"><?= $stars ?></span>
                                    <small>(<?= $avg ?>)</small>
                                </td>

                                <td><?= date('M d, Y', strtotime($book['createdAt'])) ?></td>
                            </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="report-table-wrap">
                    <div class="empty-state">
                        <p>This creator has not added any books yet.</p>
                    </div>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <?php include("Footer.php"); ?>

</body>
</html>

<?php mysqli_close($dbc); ?>