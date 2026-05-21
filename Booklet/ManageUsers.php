<?php
    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        header("Location: Login.php");
        exit();
    }

    $dbc = getConnection();

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    $sql = "SELECT userId, firstName, lastName, email, role
            FROM dbProj_users
            WHERE 1=1";

    $params = [];
    $types  = "";

    if ($search !== "") {
        $sql .= " AND (firstName LIKE ? OR lastName LIKE ? OR email LIKE ?
                  OR CONCAT(firstName, ' ', lastName) LIKE ?)";
        $like     = "%" . $search . "%";
        $params   = [$like, $like, $like, $like];
        $types    = "ssss";
    }

    $sql .= " ORDER BY userId ASC";

    $stmt = mysqli_prepare($dbc, $sql);

    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Manage Users</title>
        <link rel="stylesheet" href="BookletCSS.css">
        <style>
            .manage-users-page {
                flex: 1;
                padding: 30px 40px;
                max-width: 1000px;
                margin: 0 auto;
                width: 100%;
            }

            .users-search-row {
                display: flex;
                gap: 12px;
                margin-bottom: 30px;
            }

            .users-search-row .search-input {
                flex: 1;
            }

            .users-search-row .search-btn {
                width: 120px;
                flex-shrink: 0;
            }

            .users-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .users-table thead tr th {
                background: #483434;
                color: #FFF7EE;
                padding: 12px 16px;
                text-align: left;
                font-size: 15px;
            }

            .users-table thead tr th:first-child {
                border-radius: 22px 0 0 22px;
            }

            .users-table thead tr th:last-child {
                border-radius: 0 22px 22px 0;
            }

            .users-table tbody tr td {
                background: #FFFDF8;
                padding: 10px 16px;
                border: 1.5px solid #D3C1B4;
                font-size: 14px;
            }

            .users-table tbody tr td:first-child {
                border-radius: 22px 0 0 22px;
                border-right: none;
            }

            .users-table tbody tr td:not(:first-child):not(:last-child) {
                border-left: none;
                border-right: none;
            }

            .users-table tbody tr td:last-child {
                border-radius: 0 22px 22px 0;
                border-left: none;
                text-align: right;
            }

            .delete-user-btn {
                background: #D3C1B4;
                color: #483434;
                border: none;
                border-radius: 22px;
                padding: 7px 18px;
                font-size: 13px;
                cursor: pointer;
                transition: 0.3s;
            }

            .delete-user-btn:hover {
                background: #483434;
                color: #FFF7EE;
            }

            .no-users-msg {
                text-align: center;
                margin-top: 30px;
                color: #483434;
                font-size: 15px;
            }

            .modal-overlay {
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(72, 52, 52, 0.4);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            .modal-box {
                background: #FFFDF8;
                border-radius: 18px;
                padding: 36px 40px;
                max-width: 420px;
                width: 90%;
                box-shadow: 0 8px 32px rgba(72, 52, 52, 0.18);
                text-align: center;
            }

            .modal-title {
                color: #483434;
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 12px;
            }

            .modal-msg {
                color: #6b4f4f;
                font-size: 15px;
                margin-bottom: 28px;
            }

            .modal-actions {
                display: flex;
                gap: 12px;
                justify-content: center;
            }

            .modal-cancel-btn {
                background: #D3C1B4;
                color: #483434;
                border: none;
                border-radius: 22px;
                padding: 10px 28px;
                font-size: 14px;
                cursor: pointer;
                transition: 0.3s;
            }

            .modal-cancel-btn:hover {
                background: #c0aca0;
            }

            .modal-confirm-btn {
                background: #483434;
                color: #FFF7EE;
                border: none;
                border-radius: 22px;
                padding: 10px 28px;
                font-size: 14px;
                cursor: pointer;
                transition: 0.3s;
            }

            .modal-confirm-btn:hover {
                background: #6b4f4f;
            }
        </style>
    </head>

    <body>
        <?php include("AdminNavBar.php"); ?>

        <div class="manage-users-page">

            <?php if (isset($_SESSION['success'])) { ?>
                <p class="success-msg">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </p>
            <?php } ?>

            <h1 class="page-title">Manage Users</h1>

            <form method="GET" action="ManageUsers.php">
                <div class="users-search-row">
                    <input
                        type="text"
                        name="search"
                        class="search-input"
                        placeholder="Name/Email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>

            <?php if ($result && mysqli_num_rows($result) > 0) { ?>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User Id</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>-</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['userId']); ?></td>
                                <td><?php echo htmlspecialchars($row['firstName']); ?></td>
                                <td><?php echo htmlspecialchars($row['lastName']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['role']); ?></td>
                                <td>
                                    <?php if ($row['role'] !== 'Admin') { ?>
                                        <form method="POST" action="action/deleteUser.php"
                                              onsubmit="openDeleteModal(this); return false;">
                                            <input type="hidden" name="userId" value="<?php echo $row['userId']; ?>">
                                            <button type="submit" class="delete-user-btn">Delete User</button>
                                        </form>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

            <?php } else { ?>
                <p class="no-users-msg">No users found.</p>
            <?php } ?>

        </div>

        <?php include("Footer.php"); ?>

        <!-- Custom Delete Confirmation Modal -->
        <div id="deleteModal" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <p class="modal-title">Delete User</p>
                <p class="modal-msg">Are you sure you want to delete this user and all their data? This cannot be undone.</p>
                <div class="modal-actions">
                    <button class="modal-cancel-btn" onclick="closeDeleteModal()">Cancel</button>
                    <button class="modal-confirm-btn" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>

        <script>
            let pendingForm = null;

            function openDeleteModal(form) {
                pendingForm = form;
                document.getElementById('deleteModal').style.display = 'flex';
            }

            function closeDeleteModal() {
                document.getElementById('deleteModal').style.display = 'none';
                pendingForm = null;
            }

            function confirmDelete() {
                if (pendingForm) {
                    pendingForm.submit();
                }
            }
        </script>

        <?php
            mysqli_stmt_close($stmt);
            mysqli_close($dbc);
        ?>
    </body>
</html>
