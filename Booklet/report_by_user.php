<?php
// ── Guard: Admin only ──────────────────────────────────────────
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
while ($u = mysqli_fetch_assoc($usersResult)) $allUsers[] = $u;

// ── Selected user & their books ───────────────────────────────
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$selectedUser     = null;
$books            = [];
$userStats        = ['totalBooks' => 0, 'totalViews' => 0, 'totalReviews' => 0, 'avgRating' => 0];

if ($selected_user_id > 0) {

    // Get user info
    $uStmt = mysqli_prepare($dbc, "
        SELECT userId, firstName, lastName, email
        FROM dbProj_users
        WHERE userId = ? AND role = 'Creator'
    ");
    mysqli_stmt_bind_param($uStmt, "i", $selected_user_id);
    mysqli_stmt_execute($uStmt);
    $uRes         = mysqli_stmt_get_result($uStmt);
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
        while ($b = mysqli_fetch_assoc($bRes)) $books[] = $b;
        mysqli_stmt_close($bStmt);

        // Summary stats for this user
        if (!empty($books)) {
            $userStats['totalBooks']   = count($books);
            $userStats['totalViews']   = array_sum(array_column($books, 'viewCount'));
            $userStats['totalReviews'] = array_sum(array_column($books, 'reviewCount'));
            $allRatings = array_filter(array_column($books, 'avgRating'));
            $userStats['avgRating'] = !empty($allRatings)
                ? round(array_sum($allRatings) / count($allRatings), 1) : 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Content by User Report – Booklet Admin</title>
    <link rel="stylesheet" href="BookletCSS.css">
    <style>
        /* ── Page wrapper ── */
        .report-page {
            padding: 34px 40px 60px;
            min-height: 100vh;
            background: #FFF7EE;
        }

        /* ── Page header ── */
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .report-header h1 {
            font-size: 2rem;
            color: #483434;
        }

        .report-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* ── Filter card ── */
        .filter-card {
            background: #FFFDF8;
            border: 2px solid #D3C1B4;
            border-radius: 18px;
            padding: 22px 28px;
            margin-bottom: 28px;
        }

        .filter-card h2 {
            font-size: 1.1rem;
            color: #483434;
            margin-bottom: 16px;
        }

        .filter-row {
            display: flex;
            gap: 14px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex: 1;
            min-width: 220px;
        }

        .filter-field label {
            font-size: 0.78rem;
            color: #483434;
            font-weight: bold;
        }

        .filter-field select {
            width: 100%;
            padding: 9px 34px 9px 14px;
            border: 2px solid #D3C1B4;
            border-radius: 22px;
            background: #FFF7EE;
            color: #483434;
            font-size: 0.9rem;
            font-family: Alice;
            appearance: none;
            background-image: url("data:image/svg+xml;utf8,<svg fill='%23483434' height='18' viewBox='0 0 24 24' width='18' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
        }

        .report-btn {
            padding: 9px 22px;
            border-radius: 22px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-family: Alice;
            transition: 0.3s;
        }

        .report-btn-primary {
            background: #483434;
            color: #FFF7EE;
        }

        .report-btn-primary:hover {
            background: #FFFDF8;
            color: #483434;
            border: 2px solid #483434;
        }

        .report-btn-secondary {
            background: #D3C1B4;
            color: #483434;
        }

        .report-btn-secondary:hover {
            background: #FFFDF8;
            border: 2px solid #483434;
        }

        /* ── User info banner ── */
        .user-banner {
            background: #483434;
            border-radius: 18px;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 22px;
            flex-wrap: wrap;
        }

        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: #D3C1B4;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #483434;
            font-weight: bold;
            flex-shrink: 0;
        }

        .user-banner-info h2 {
            color: #FFF7EE;
            font-size: 1.3rem;
            margin-bottom: 3px;
        }

        .user-banner-info p {
            color: #D3C1B4;
            font-size: 0.85rem;
        }

        /* ── Stat boxes ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-box {
            background: #FFFDF8;
            border: 2px solid #D3C1B4;
            border-radius: 18px;
            padding: 20px 22px;
            text-align: center;
        }

        .stat-box .stat-label {
            font-size: 0.8rem;
            color: #483434;
            margin-bottom: 6px;
        }

        .stat-box .stat-value {
            font-size: 2rem;
            color: #483434;
            font-weight: bold;
        }

        .stat-box .stat-sub {
            font-size: 0.75rem;
            color: #D3C1B4;
            margin-top: 2px;
        }

        /* ── Table ── */
        .report-table-wrap {
            background: #FFFDF8;
            border: 2px solid #D3C1B4;
            border-radius: 18px;
            overflow: hidden;
        }

        .report-table-wrap table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table-wrap thead {
            background: #483434;
        }

        .report-table-wrap thead th {
            padding: 13px 16px;
            text-align: left;
            color: #FFF7EE;
            font-size: 0.85rem;
        }

        .report-table-wrap tbody tr {
            border-bottom: 1px solid #D3C1B4;
            transition: background 0.15s;
        }

        .report-table-wrap tbody tr:last-child {
            border-bottom: none;
        }

        .report-table-wrap tbody tr:hover {
            background: #FFF7EE;
        }

        .report-table-wrap tbody td {
            padding: 12px 16px;
            color: #483434;
            font-size: 0.88rem;
            vertical-align: middle;
        }

        /* Cover thumb */
        .cover-thumb {
            width: 38px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #D3C1B4;
        }

        /* Genre pill */
        .genre-pill {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 20px;
            background: #D3C1B4;
            color: #483434;
            font-size: 0.78rem;
        }

        /* Stars */
        .stars-gold { color: gold; }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #483434;
        }

        .empty-state p { margin-top: 8px; font-size: 0.95rem; }

        /* Prompt to select user */
        .select-prompt {
            text-align: center;
            padding: 60px 20px;
            background: #FFFDF8;
            border: 2px solid #D3C1B4;
            border-radius: 18px;
            color: #483434;
        }

        .select-prompt p {
            font-size: 1rem;
            margin-top: 10px;
            color: #D3C1B4;
        }

        /* Meta line */
        .report-meta {
            font-size: 0.82rem;
            color: #D3C1B4;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 6px;
        }

        /* Switch link */
        .switch-link {
            display: inline-block;
            padding: 9px 22px;
            border-radius: 22px;
            background: #D3C1B4;
            color: #483434;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
        }

        .switch-link:hover {
            background: #FFFDF8;
            border: 2px solid #483434;
        }

        /* ── Print ── */
        @media print {
            .no-print { display: none !important; }
            nav, #pageFooter { display: none !important; }
            .report-page { padding: 10px; }
            .report-table-wrap { border: 1px solid #aaa; }
            .user-banner { background: #483434 !important; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <?php include("AdminNavBar.php"); ?>

    <div class="report-page">

        <!-- Header -->
        <div class="report-header">
            <h1>👤 Content by User</h1>
            <div class="report-actions no-print">
                <a href="report_popular_content.php" class="switch-link">← Popular Content Report</a>
                <?php if ($selectedUser && !empty($books)): ?>
                    <button class="report-btn report-btn-secondary" onclick="window.print()">🖨 Print / Save PDF</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-card no-print">
            <h2>Select a Creator</h2>
            <form method="GET" action="report_by_user.php">
                <div class="filter-row">
                    <div class="filter-field">
                        <label>Creator</label>
                        <select name="user_id">
                            <option value="">-- Choose a creator --</option>
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

        <?php if ($selected_user_id === 0): ?>

            <!-- Prompt: no user selected yet -->
            <div class="select-prompt">
                <div style="font-size:2.5rem;">📋</div>
                <p>Select a creator from the dropdown above to generate their content report.</p>
            </div>

        <?php elseif (!$selectedUser): ?>

            <!-- User not found -->
            <div class="select-prompt">
                <div style="font-size:2.5rem;">❓</div>
                <p>User not found. Please select a valid creator.</p>
            </div>

        <?php else: ?>

            <!-- User banner -->
            <div class="user-banner">
                <div class="user-avatar">
                    <?= strtoupper(substr($selectedUser['firstName'], 0, 1)) ?>
                </div>
                <div class="user-banner-info">
                    <h2><?= htmlspecialchars($selectedUser['firstName'] . ' ' . $selectedUser['lastName']) ?></h2>
                    <p><?= htmlspecialchars($selectedUser['email']) ?> &nbsp;·&nbsp; Creator</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Books Added</div>
                    <div class="stat-value"><?= $userStats['totalBooks'] ?></div>
                    <div class="stat-sub">total booklets</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Views</div>
                    <div class="stat-value"><?= number_format($userStats['totalViews']) ?></div>
                    <div class="stat-sub">across all books</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Total Reviews</div>
                    <div class="stat-value"><?= number_format($userStats['totalReviews']) ?></div>
                    <div class="stat-sub">received</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Avg Rating</div>
                    <div class="stat-value"><?= $userStats['avgRating'] ?></div>
                    <div class="stat-sub">out of 5 ⭐</div>
                </div>
            </div>

            <?php if (!empty($books)): ?>

                <!-- Meta line -->
                <div class="report-meta">
                    <span><?= $userStats['totalBooks'] ?> book(s) by <?= htmlspecialchars($selectedUser['firstName'] . ' ' . $selectedUser['lastName']) ?></span>
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
                                <th>Pages</th>
                                <th>Views</th>
                                <th>Reviews</th>
                                <th>Avg Rating</th>
                                <th>Added On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $i => $book):
                                $avg   = round((float)$book['avgRating'], 1);
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
                                <td><span class="genre-pill"><?= htmlspecialchars($book['genreName']) ?></span></td>
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

                <!-- No books for this user -->
                <div class="report-table-wrap">
                    <div class="empty-state">
                        <div style="font-size:2.5rem;">📭</div>
                        <p>This creator hasn't added any books yet.</p>
                    </div>
                </div>

            <?php endif; ?>

        <?php endif; ?>

    </div>

    <?php include("Footer.php"); ?>

</body>
</html>
<?php mysqli_close($dbc); ?>
