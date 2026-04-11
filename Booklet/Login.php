<?php
    //Check for seesions
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    include("DBConnection.php");
    
    
    $message=""; //For error massages
    
    //if button clicked
    if(isset($_POST["btnLogin"])){
        
        //get values
    }
?>

<html>
    <head>
        <title>Login</title>
        <link rel="stylesheet" href="BookletCSS.css">
        
        <script>
            //Form JS Validation
            function validateForm() {
                let email = document.getElementById("email").value.trim();
                let password = document.getElementById("password").value.trim();
                
                //if either of the inputs empty
                if (email === "" || password === "") {
                    alert("Please fill in all fields.");
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
        
        <style>
            
            body{
                display: flex;
                flex-direction: column;
                min-height: 100vh; 
                background: #FFF7EE;
            }
            
            .login-container{
                width: 600px;
                background: #FFF7EE;
                border: 3px solid #483434;
                border-radius: 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                margin: 12px;
                padding: 10px;
                
                display: flex;
                flex: 1;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
            }
            
            .formLogo{
                height: 40%; 
                width: 40%;
                display: block;
                margin: 15px auto 15px auto;
            }

            .login-container h2{
                margin-bottom: 20px;
                color: #483434;
            }

            .error {
                color: red;
                margin-bottom: 10px;
                font-size: 14px;
            }

            input{
               width: 400px;
               padding: 10px;
               margin: 5px;
               border: 1px solid #D3C1B4;
               border-radius: 20px; 
            }

            .passBox{
                position: relative;
            }

            .passEye{
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #483434;
                cursor: pointer;
            }

            .formBtn{
                padding: 0.5rem 1.5rem;
                border-radius: 20px;
                font-weight: 500;
                font-size: 1.05rem;
                background: #D3C1B4;
                color: #483434; 
                border: 1px solid #483434;
                width: 400px;
                transition: 0.3s;
                margin: 5px;
                cursor: pointer;
            }

            .formBtn:hover{
                background: #FFF7EE;
                color: #483434;
                border: 1px solid #483434;
            }

            .regLink p{
                margin-top:15px;
                margin-bottom: 15px
                font-size: 14px;
                color: #D3C1B4;
            }

            .regLink a {
                color: #483434;
                text-decoration: none;
                font-weight: bold;
            }
        </style>
    </head>
    
    <body>
        <?php include("VisitorNavBar.php");?>
        
        <div class="login-container">
            <img class="formLogo" src="images/logoDark.png" alt="Booklet Logo">
            
            <h2>Login</h2>
            
            <form id="loginForm" method="POST" onsubmit="return validateForm()">
                <!-- email -->
                <input type="email" id="email" name="email" placeholder="Enter Your Email">

                <!-- password -->
                <div class="passBox">
                    <input type="password" id="password" name="password" placeholder="Enter Your Password">
                    <span class="passEye" onclick="seePassword()">👁</span>
                </div>
                <!-- Login Button -->
                <button type="submit" Class="formBtn" name="btnLogin">Login</button>
            </form>

            <!-- Register -->
            <p class="regLink">Don't have an account?  <a href="Register.php">Register</a></p>
        </div>
        
        <?php include("Footer.php");?>
    </body>
</html>
