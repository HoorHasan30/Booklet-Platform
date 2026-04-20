<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include("DBConnection.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $message = "";

    if (isset($_POST["btnSendReset"])) {

        $dbc = getConnection();
        $email = trim($_POST["email"]);

        if (empty($email)) {
            $message = "Please enter your email.";
        } 
        else {
            $sql = "SELECT userId, firstName, email FROM dbProj_users WHERE email = ?";
            $stmt = mysqli_prepare($dbc, $sql);

            if (!$stmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {

                $token = bin2hex(random_bytes(32));
                $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

                $updateSql = "UPDATE dbProj_users SET resetToken = ?, resetExpires = ? WHERE email = ?";
                $updateStmt = mysqli_prepare($dbc, $updateSql);

                if (!$updateStmt) {
                    die("SQL Error: " . mysqli_error($dbc));
                }

                mysqli_stmt_bind_param($updateStmt, "sss", $token, $expires, $email);
                mysqli_stmt_execute($updateStmt);

                $resetLink = "http://20.74.143.233/~u202301820/Booklet/ResetPassword.php?token=" . $token;

                $subject = "Booklet Password Reset";
                $body  = "Hello " . $user["firstName"] . ",\n\n";
                $body .= "Click the link below to reset your password:\n";
                $body .= $resetLink . "\n\n";
                $body .= "This link will expire in 1 hour.";

                $headers = "From: no-reply@booklet.com\r\n";
                $headers .= "Reply-To: no-reply@booklet.com\r\n";

                if (mail($email, $subject, $body, $headers)) {
                    $message = "Password reset email has been sent.";
                } else {
                    $message = "Email could not be sent.";
                }

            } else {
                $message = "Email not found.";
            }
        }
    }
?>

<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/BookletCSS.css">

    <script>
        function validateForm() {
            let email = document.getElementById("email").value.trim();
            let errorBox = document.getElementById("formError");

            errorBox.textContent = "";

            if (email === "") {
                errorBox.textContent = "Please enter your email.";
                return false;
            }

            return true;
        }
    </script>
</head>

<body class="authBody">
    <?php include("VisitorNavBar.php"); ?>

    <div class="login-page">
        <div class="login-container">
            <img class="formLogo" src="images/LogoDark_1.png" alt="Booklet Logo">

            <h2>Forgot Password</h2>

            <?php if (!empty($message)) { ?>
                <p class="error" id="formError"><?php echo $message; ?></p>
            <?php } 
            else { ?>
                <p class="error" id="formError"></p>
            <?php } ?>

            <form method="POST" action="ForgotPassword.php" onsubmit="return validateForm()">
                <input type="email" id="email" name="email" placeholder="Enter Your Email">
                <button type="submit" class="formBtn" name="btnSendReset">Send Reset Email</button>
            </form>

            <p class="regLink"><a href="Login.php">Back to Login</a></p>
        </div>
    </div>

    <?php include("Footer.php"); ?>
</body>
</html>