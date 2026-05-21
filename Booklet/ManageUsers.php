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
