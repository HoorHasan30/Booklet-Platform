<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include("DBConnection.php");
    include(__DIR__ . "/action/mail_config.php");

    // Check for sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    $error = "";
    $success = "";

    // if button clicked
    if(isset($_POST["btnSend"])){

        $dbc = getConnection();

        // get email from input
        $email = trim($_POST["email"]);

        // validate
        if(empty($email)){
            $error = "Please enter your email";
        }
        else{

            // check if email exists
            $stmt = mysqli_prepare($dbc, "SELECT * FROM dbProj_users WHERE email = ?");

            if (!$stmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // email found
            if($row = mysqli_fetch_assoc($result)){

                // generate secure token
                $token = bin2hex(random_bytes(32));

                // set expiry time -> 1 hour
                $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // save token and expiry in MySQL
                $updateStmt = mysqli_prepare(
                    $dbc,
                    "UPDATE dbProj_users SET resetToken = ?, resetExpires = ? WHERE email = ?"
                );

                if (!$updateStmt) {
                    die("SQL Error: " . mysqli_error($dbc));
                }

                mysqli_stmt_bind_param($updateStmt, "sss", $token, $expiry, $email);
                mysqli_stmt_execute($updateStmt);

                $resetLink = "http://20.74.143.233/~u202301820/Booklet/ResetPassword.php?token=" . $token;

                // send email
                $mailResult = sendResetEmail($email, $resetLink);

                if($mailResult === true){
                    $success = "Reset link sent to your email";
                } 
                else {
                    $error = "Email failed: " . $mailResult;
                }

            }
            // email not found
            else{
                $error = "Email not found";
            }
        }
    }
?>

<html>
    <head>
        <title>Forgot Password</title>
        <link rel="stylesheet" href="/BookletCSS.css">
    </head>

    <body class="authBody">
        <?php include("VisitorNavBar.php");?>

        <div class="login-page">

            <div class="login-container">
                <img class="formLogo" src="images/LogoDark_1.png" alt="Booklet Logo">

                <h2>Forgot Password</h2>

                <?php if (!empty($error)) { ?>
                    <p class="error"><?php echo $error; ?></p>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <p class="error" style="color:green;"><?php echo $success; ?></p>
                <?php } ?>

                <form method="POST" action="ForgotPassword.php">
                    <!-- email -->
                    <input type="email" name="email" placeholder="Enter Your Email">
                    
                    <!-- send reset link button -->
                    <button type="submit" class="formBtn" name="btnSend">Send Reset Link</button>
                </form>

                <p class="regLink"><a href="Login.php">Back to Login</a></p>

            </div>

        </div>

        <?php include("Footer.php");?>
    </body>
</html>