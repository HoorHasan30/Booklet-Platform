<?php
// ── Guard: Admin only ──────────────────────────────────────────
include("DBConnection.php");
if (session_status() == PHP_SESSION_NONE) session_start();
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "Admin") {
    header("Location: Login.php");
    exit();
}

$dbc = getConnection();

// ── Date range from form (default: current month) ─────────────
$date_from = isset($_GET['date_from']) && $_GET['date_from'] !== ''
    ? $_GET['date_from'] : date('Y-m-01');
$date_to   = isset($_GET['date_to']) && $_GET['date_to'] !== ''
    ? $_GET['date_to']   : date('Y-m-d');

// ── Query: most popular books by view count in date range ─────
// Uses: dbProj_books, dbProj_users, dbProj_Genres, dbProj_reviews
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
    WHERE DATE(b.createdAt) BETWEEN ? AND ?
    GROUP BY
        b.bookId, b.title, b.author, b.bookCover,
        b.viewCount, b.createdAt, g.genreName,
        u.firstName, u.lastName
    ORDER BY b.viewCount DESC
    LIMIT 50
");

mysqli_stmt_bind_param($stmt, "ss", $date_from, $date_to);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$rows   = [];
while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
mysqli_stmt_close($stmt);

// ── Summary totals ────────────────────────────────────────────
$total_views   = array_sum(array_column($rows, 'viewCount'));
$total_reviews = array_sum(array_column($rows, 'reviewCount'));
$total_books   = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Popular Content Report – Booklet Admin</title>
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
        }

        .filter-field label {
            font-size: 0.78rem;
            color: #483434;
            font-weight: bold;
        }

        .filter-field input[type="date"] {
            width: auto;
            padding: 8px 14px;
            border: 2px solid #D3C1B4;
            border-radius: 22px;
            background: #FFF7EE;
            color: #483434;
            font-size: 0.9rem;
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

        /* ── Stat boxes ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
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

        /* Rank badge */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-size: 0.82rem;
            font-weight: bold;
        }

        .rank-1 { background: #fef3c7; color: #92400e; }
        .rank-2 { background: #e2e8f0; color: #475569; }
        .rank-3 { background: #fee2e2; color: #991b1b; }
        .rank-n { background: #D3C1B4; color: #483434; }

        /* Book cover thumb */
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

        /* Date info line */
        .report-meta {
            font-size: 0.82rem;
            color: #D3C1B4;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 6px;
        }

        /* Switch between reports link */
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
        }
    </style>
</head>
<body>

    <?php include("AdminNavBar.php"); ?>

    <div class="report-page">

        <!-- Header -->
        <div class="report-header">
            <h1>📊 Most Popular Content</h1>
            <div class="report-actions no-print">
                <a href="report_by_user.php" class="switch-link">→ Report by User</a>
                <button class="report-btn report-btn-secondary" onclick="window.print()">🖨 Print / Save PDF</button>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-card no-print">
            <h2>Filter by Date Range</h2>
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
                    <button type="submit" class="report-btn report-btn-primary">Generate</button>
                </div>
            </form>
        </div>

        <?php if (!empty($rows)): ?>

        <!-- Summary stats -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-label">Books Found</div>
                <div class="stat-value"><?= $total_books ?></div>
                <div class="stat-sub">in selected range</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Views</div>
                <div class="stat-value"><?= number_format($total_views) ?></div>
                <div class="stat-sub">combined views</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Total Reviews</div>
                <div class="stat-value"><?= number_format($total_reviews) ?></div>
                <div class="stat-sub">across all books</div>
            </div>
            <div class="stat-box">
                <div class="stat-label">Date Range</div>
                <div class="stat-value" style="font-size:1rem; padding-top:6px;">
                    <?= date('M d, Y', strtotime($date_from)) ?>
                    <br>→ <?= date('M d, Y', strtotime($date_to)) ?>
                </div>
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
                    <tr>
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
                        <td><span class="genre-pill"><?= htmlspecialchars($row['genreName']) ?></span></td>
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

        <!-- Empty state -->
        <div class="report-table-wrap">
            <div class="empty-state">
                <div style="font-size:2.5rem;">📭</div>
                <p>No books found for the selected date range.</p>
                <p style="font-size:0.82rem; color:#D3C1B4; margin-top:6px;">
                    Try expanding the date range above.
                </p>
            </div>
        </div>

        <?php endif; ?>

    </div>

    <?php include("Footer.php"); ?>

</body>
</html>
<?php mysqli_close($dbc); ?>
