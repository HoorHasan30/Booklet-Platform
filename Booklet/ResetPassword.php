<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("DBConnection.php");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$token = $_GET["token"] ?? "";

if (isset($_POST["btnReset"])) {

    $dbc = getConnection();

    $token = $_POST["token"];
    $password = $_POST["password"];
    $confirmPassword = $_POST["confirmPassword"];

    if (empty($password) || empty($confirmPassword)) {
        $message = "Please fill in all fields.";
    } 
    elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
    } 
    else {
        $sql = "SELECT userId FROM dbProj_users 
                WHERE resetToken = ? AND resetExpires > NOW()";
        $stmt = mysqli_prepare($dbc, $sql);

        if (!$stmt) {
            die("SQL Error: " . mysqli_error($dbc));
        }

        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $updateSql = "UPDATE dbProj_users
                          SET password = ?, resetToken = NULL, resetExpires = NULL
                          WHERE userId = ?";
            $updateStmt = mysqli_prepare($dbc, $updateSql);

            if (!$updateStmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }

            mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $user["userId"]);
            mysqli_stmt_execute($updateStmt);

            $message = "Password reset successfully. You can now login.";

        } else {
            $message = "Invalid or expired reset link.";
        }
    }
}
?>

<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="/BookletCSS.css">

    <script>
        function validateForm() {
            let password = document.getElementById("password").value.trim();
            let confirmPassword = document.getElementById("confirmPassword").value.trim();
            let errorBox = document.getElementById("formError");

            errorBox.textContent = "";

            if (password === "" || confirmPassword === "") {
                errorBox.textContent = "Please fill in all fields.";
                return false;
            }

            if (password !== confirmPassword) {
                errorBox.textContent = "Passwords do not match.";
                return false;
            }

            return true;
        }

        function seePassword(icon){
            let pass = icon.parentElement.querySelector("input");

            if(pass.type === "password"){
                pass.type = "text";
                icon.textContent = "⌣";
            } else {
                pass.type = "password";
                icon.textContent = "👁";
            }
        }
    </script>
</head>

<body class="authBody">
    <?php include("VisitorNavBar.php"); ?>

    <div class="login-page">
        <div class="login-container">
            <img class="formLogo" src="images/LogoDark_1.png" alt="Booklet Logo">

            <h2>Reset Password</h2>

            <?php if (!empty($message)) { ?>
                <p class="error" id="formError"><?php echo $message; ?></p>
            <?php } else { ?>
                <p class="error" id="formError"></p>
            <?php } ?>

            <form method="POST" action="ResetPassword.php?token=<?php echo htmlspecialchars($token); ?>" onsubmit="return validateForm()">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="passBox">
                    <input type="password" id="password" name="password" placeholder="Enter New Password">
                    <span class="passEye" onclick="seePassword(this)">👁</span>
                </div>

                <div class="passBox">
                    <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm New Password">
                    <span class="passEye" onclick="seePassword(this)">👁</span>
                </div>

                <button type="submit" class="formBtn" name="btnReset">Reset Password</button>
            </form>

            <p class="regLink"><a href="Login.php">Back to Login</a></p>
        </div>
    </div>

    <?php include("Footer.php"); ?>
</body>
</html>