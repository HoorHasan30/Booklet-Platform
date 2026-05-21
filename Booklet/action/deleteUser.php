<?php
    session_start();
    include("../DBConnection.php");

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../Login.php");
        exit();
    }

    $userId = isset($_POST['userId']) ? intval($_POST['userId']) : 0;

    if (!$userId) {
        die("Invalid user ID.");
    }

    $dbc = getConnection();

    // Block deletion of Admin accounts
    $check = mysqli_prepare($dbc, "SELECT role FROM dbProj_users WHERE userId = ?");
    mysqli_stmt_bind_param($check, "i", $userId);
    mysqli_stmt_execute($check);
    $checkResult = mysqli_stmt_get_result($check);
    $targetUser = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($check);

    if (!$targetUser || $targetUser['role'] === 'Admin') {
        header("Location: ../ManageUsers.php");
        exit();
    }

    // 1. Delete comments written by the user
    $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_comments WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 2. Delete comments on reviews of the user's books
    $stmt = mysqli_prepare($dbc,
        "DELETE c FROM dbProj_comments c
         JOIN dbProj_reviews r ON c.reviewId = r.reviewId
         JOIN dbProj_books b ON r.bookId = b.bookId
         WHERE b.userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 3. Delete reviews written by the user
    $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_reviews WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 4. Delete reviews on the user's books
    $stmt = mysqli_prepare($dbc,
        "DELETE r FROM dbProj_reviews r
         JOIN dbProj_books b ON r.bookId = b.bookId
         WHERE b.userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 5. Delete the user's books
    $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_books WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 6. Delete the user
    $stmt = mysqli_prepare($dbc, "DELETE FROM dbProj_users WHERE userId = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_close($dbc);

    $_SESSION['success'] = "User deleted successfully.";
    header("Location: ../ManageUsers.php");
    exit();
?>
