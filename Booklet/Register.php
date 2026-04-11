<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    include("DBConnection.php");
    
    //Check for sessions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $message=""; //For error messages
    
    //if button clicked
    if(isset($_POST["btnRegister"])){
        
        $dbc = getConnection();
        
        //get values from input
        $firstName = trim($_POST["firstName"]);
        $lastName = trim($_POST["lastName"]);
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        $confirmPassword = $_POST["confirmPassword"];
        
        $errors = array();
        
        //check if fields are empty
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $errors[] = "Please fill in all fields.";
        }
        
        //check password and confirmed password match
        if ($password !== $confirmPassword){
            $errors[] = "Passwords do not match";
        }
        
        //if empty (no errors) --> validate & insert
        if(empty($errors)){
            
            // Check if email already exists
            $sql = "SELECT * FROM dbProj_users WHERE email = ?";
            $stmt = mysqli_prepare($dbc, $sql);

            if (!$stmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }

            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            //if existed
            if (mysqli_fetch_assoc($result)) {
                $message = "Email already exists";
            }
            //if not
            else {
                //Hash the password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                //role
                $role = "Creator";
                
                //Insert new user
                $insertSql = "INSERT INTO dbProj_users (firstName, lastName, email, password, role) VALUES (?, ?, ?, ?, ?)";
                $insertStmt = mysqli_prepare($dbc, $insertSql);
                
                if (!$insertStmt) {
                    die("SQL Error: " . mysqli_error($dbc));
                }
                
                mysqli_stmt_bind_param($insertStmt, "sssss", $firstName, $lastName, $email, $hashedPassword, $role);
                
                if (mysqli_stmt_execute($insertStmt)) {
                    
                    // Get inserted user ID
                    $userId = mysqli_insert_id($dbc);

                    // Save session (same as login)
                    $_SESSION["userId"] = $userId;
                    $_SESSION["firstName"] = $firstName;
                    $_SESSION["lastName"] = $lastName;
                    $_SESSION["role"] = $role;
                            
                    header("Location: index.php");
                    exit();
                }
                else {
                    $message = "Registration failed";
                }
            } 
        }
        //there are errors
        else {
            $message = implode("<br>", $errors);
        }
    }
?>

<html>
    <head>
        <title>Registration</title>
        <link rel="stylesheet" href="/BookletCSS.css">
        
        <script>
            //Form JS Validation
            function validateForm() {
                let firstName = document.getElementById("firstName").value.trim();
                let lastName = document.getElementById("lastName").value.trim();
                let email = document.getElementById("email").value.trim();
                let password = document.getElementById("password").value.trim();
                let confirmPassword = document.getElementById("confirmPassword").value.trim();

                if (firstName === "" || lastName === "" || email === "" || password === "" || confirmPassword === "") {
                    alert("Please fill all fields.");
                    return false;
                }

                if (password !== confirmPassword) {
                    alert("Passwords do not match.");
                    return false;
                }

                return true;
            }

            //view hidden password
            function seePassword(id) {
                let pass = document.getElementById(id);

                if (pass.type === "password") {
                    pass.type = "text";
                }
                else {
                    pass.type = "password";
                }
            }
        </script>
        
    </head>
    
    <body class="authBody">
        
        <?php include("VisitorNavBar.php");?>
        
        <div class="login-page">
            
            <div class="login-container">
                
                <img class="formLogo" src="images/LogoDark_1.png" alt="Booklet Logo">

                <h2>Register</h2>
                
                <?php if (!empty($message)) { ?>
                <p class="error"><?php echo $message; ?></p>
                <?php } ?>
            
                <form id="registerForm" method="POST" action="Register.php" onsubmit="return validateForm()">
                    <!-- first name -->
                    <input type="text" id="firstName" name="firstName" placeholder="Enter Your First Name">

                    <!-- last name -->
                    <input type="text" id="lastName" name="lastName" placeholder="Enter Your Last Name">

                    <!-- email -->
                    <input type="email" id="email" name="email" placeholder="Enter Your Email">

                    <!-- password -->
                    <div class="passBox">
                        <input type="password" id="password" name="password" placeholder="Enter Your Password">
                        <span class="passEye" onclick="seePassword('password')">👁</span>
                    </div>

                    <!-- confirm password -->
                    <div class="passBox">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Rewrite Your Password">
                        <span class="passEye" onclick="seePassword('confirmPassword')">👁</span>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" class="formBtn" name="btnRegister">Register</button>
                </form>

                <!-- Register -->
                <p class="regLink">Already have an account?<a href="Login.php"> Login</a></p>
            
            </div>
            
        </div>
        
        <?php include("Footer.php");?>
    </body>
</html>

