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
    if(isset($_POST["btnLogin"])){
        
        $dbc= getConnection();
        
        //get values from input
        $email = trim($_POST["email"]);
        $password = $_POST["password"];
        
        $errors = array();
        
        // Check if fields are empty
        if (empty($email) || empty($password)) {
            $errors[] = "Please fill in all fields.";
        } 
        
        //if empty (no errors) --> validate
        if(empty($errors)){
            
            //Find user
            $sql = "SELECT * FROM dbProj_users WHERE email = ?";
            $stmt = mysqli_prepare($dbc, $sql);
            
            if (!$stmt) {
                die("SQL Error: " . mysqli_error($dbc));
            }
            
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            //check if email existed
            if($row = mysqli_fetch_assoc($result)){
                
                // if existed -> verify password (hashed)
                if(password_verify($password, $row["password"])){
                    
                    // Save user data in session
                    $_SESSION["userId"] = $row["userId"];
                    $_SESSION["firstName"] = $row["firstName"];
                    $_SESSION["lastName"] = $row["lastName"];
                    $_SESSION["role"] = $row["role"];
                    
                    //redirect to home page
                    header("Location: Home.php");
                    exit();
                }
                //worng password 
                else {
                  $message = "Incorrect Password";  
                } 
            }
            //user not found
            else{
                $message = "Email not found";
            }    
        }
        else {
            $message = implode("<br>", $errors);
        }
    }
?>

<html>
    <head>
        <title>Login</title>
        <link rel="stylesheet" href="/BookletCSS.css">
        
        <script>
            //Form JS Validation
            function validateForm() {
                let email = document.getElementById("email").value.trim();
                let password = document.getElementById("password").value.trim();
                
                //if either of the inputs empty
                if (email === "" || password === "") {
                    alert("Please fill all fields.");
                    return false;
                }
                return true;
            }
            
            //view hidden password
            function seePassword(){
                let pass = document.getElementById("password");
                
                if(pass.type === "password"){
                    pass.type = "text";
                }
                else{
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

                <h2>Login</h2>
                
                <?php if (!empty($message)) { ?>
                <p class="error"><?php echo $message; ?></p>
                <?php } ?>
            
                <form id="loginForm" method="POST" action="Login.php" onsubmit="return validateForm()">
                    <!-- email -->
                    <input type="email" id="email" name="email" placeholder="Enter Your Email">

                    <!-- password -->
                    <div class="passBox">
                        <input type="password" id="password" name="password" placeholder="Enter Your Password">
                        <span class="passEye" onclick="seePassword()">👁</span>
                    </div>
                    
                    <!-- Login Button -->
                    <button type="submit" class="formBtn" name="btnLogin">Login</button>
                </form>

                <!-- Register -->
                <p class="regLink">Don't have an account?<a href="Register.php"> Register</a></p>
            
            </div>
            
        </div>
        
        <?php include("Footer.php");?>
    </body>
</html>
