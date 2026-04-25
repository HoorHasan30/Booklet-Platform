<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    include("DBConnection.php");

    // Check for sessions
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

    // check token from url
    if(!isset($_GET["token"]) || empty($_GET["token"])){
        die("Invalid link");
    }

    $token = $_GET["token"];
    $dbc = getConnection();

    // check if token exists
    $stmt = mysqli_prepare($dbc, "SELECT * FROM dbProj_users WHERE resetToken = ?");

    if (!$stmt) {
        die("SQL Error: " . mysqli_error($dbc));
    }

    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // if token not found
    if(!$row = mysqli_fetch_assoc($result)){
        die("Invalid or expired token");
    }

    // check expiry
    if(strtotime($row["resetExpires"]) < time()){
        die("Link expired");
    }

    // if button clicked
    if(isset($_POST["btnReset"])){

        $password = $_POST["password"];
        $confirmPassword = $_POST["confirmPassword"];

        // validate fields
        if(empty($password) || empty($confirmPassword)){
            $error = "Please fill in all fields.";
        }
        elseif(strlen($password) < 8){
            $error = "Password must be at least 8 characters";
        }
        elseif($password !== $confirmPassword){
            $error = "Passwords do not match";
        }
        else{

            // hash new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // update password and clear reset fields
            $updateStmt = mysqli_prepare(
                $dbc,
                "UPDATE dbProj_users 
                 SET password = ?, resetToken = NULL, resetExpires = NULL
                 WHERE resetToken = ?"
            );

            if (!$updateStmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }

            mysqli_stmt_bind_param($updateStmt, "ss", $hashedPassword, $token);
            mysqli_stmt_execute($updateStmt);

            $success = "Password reset successful";
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

                if (password.length < 6) {
                    errorBox.textContent = "Password must be at least 8 characters";
                    return false;
                }

                if (password !== confirmPassword) {
                    errorBox.textContent = "Passwords do not match";
                    return false;
                }

                return true;
            }

            // view hidden password
            function seePassword(icon, inputId){
                let pass = document.getElementById(inputId);

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
        <?php loadNavBar();?>

        <div class="login-page">

            <div class="login-container">
                <img class="formLogo" src="images/LogoDark_1.png" alt="Booklet Logo">

                <h2>Reset Password</h2>

                <?php if (!empty($error)) { ?>
                    <p class="error" id="formError"><?php echo $error; ?></p>
                <?php } ?>

                <?php if (!empty($success)) { ?>
                    <p class="error" id="formError" style="color:green;"><?php echo $success; ?></p>
                <?php } ?>

                <?php if (empty($error) && empty($success)) { ?>
                    <p class="error" id="formError"></p>
                <?php } ?>

                <?php if (empty($success)) { ?>

                <form method="POST" action="" onsubmit="return validateForm()">

                    <!-- new password -->
                    <div class="passBox">
                        <input type="password" id="password" name="password" placeholder="Enter New Password">
                        <span class="passEye" onclick="seePassword(this, 'password')">👁</span>
                    </div>

                    <!-- confirm password -->
                    <div class="passBox">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm New Password">
                        <span class="passEye" onclick="seePassword(this, 'confirmPassword')">👁</span>
                    </div>

                    <!-- reset button -->
                    <button type="submit" class="formBtn" name="btnReset">Reset Password</button>

                </form>

                <?php } else { ?>

                    <p class="regLink"><a href="Login.php">Go to Login</a></p>

                <?php } ?>

            </div>

        </div>

        <?php include("Footer.php");?>
    </body>
</html>