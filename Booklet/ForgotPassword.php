<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include("DBConnection.php");
    include(__DIR__ . "/action/mail_config.php");

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    function loadNavBar(){
        if (isset($_SESSION["role"])) {

            if ($_SESSION["role"] == "Admin") {
                include("AdminNavBar.php");
            }
            elseif ($_SESSION["role"] == "Creator") {
                include("CreatorNavBar.php");
            }
        } 
        else {
            include("VisitorNavBar.php");
        }
    }

    $error = "";
    $success = "";

    if(isset($_POST["btnSend"])){

        $dbc = getConnection();

        $email = trim($_POST["email"]);

        if(empty($email)){
            $error = "Please enter your email";
        }
        else{

            // PREPARE SELECT QUERY
            $query = "SELECT * 
                      FROM dbProj_users 
                      WHERE email = ?";

            $stmt = mysqli_prepare($dbc, $query);

            // BIND PARAMETER
            mysqli_stmt_bind_param($stmt, "s", $email);

            // EXECUTE QUERY
            mysqli_stmt_execute($stmt);

            // GET RESULT
            $result = mysqli_stmt_get_result($stmt);

            if($row = mysqli_fetch_assoc($result)){

                $token = bin2hex(random_bytes(32));

                $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

                // PREPARE UPDATE QUERY
                $updateQuery = "UPDATE dbProj_users 
                                SET resetToken = ?, resetExpires = ?
                                WHERE email = ?";

                $updateStmt = mysqli_prepare($dbc, $updateQuery);

                // BIND PARAMETERS
                mysqli_stmt_bind_param(
                    $updateStmt,
                    "sss",
                    $token,
                    $expiry,
                    $email
                );

                // EXECUTE UPDATE
                mysqli_stmt_execute($updateStmt);

                $resetLink = "http://20.74.143.233/~u202301820/Booklet/ResetPassword.php?token=" . $token;

                $mailResult = sendResetEmail($email, $resetLink);

                if($mailResult === true){
                    $success = "Reset link sent to your email";
                }
                else{
                    $error = "Email failed: " . $mailResult;
                }

                mysqli_stmt_close($updateStmt);
            }
            else{
                $error = "Email not found";
            }

            mysqli_stmt_close($stmt);

            mysqli_close($dbc);
        }
    }
?>

<html>
    <head>
        <title>Forgot Password</title>
        <link rel="stylesheet" href="/BookletCSS.css">
    </head>

    <body class="authBody">
        
        <?php loadNavBar();?>
        
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