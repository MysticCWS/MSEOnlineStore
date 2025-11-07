<?php 
session_start();
include 'dbcon.php';
include 'includes\header.php'; 
echo ' | Login';
include 'includes\header2.php';
?>

<div class="content">
    <div class="col-md-4 mx-auto px-4 py-4 border rounded bg-white">
    <!--Show Status-->
        <?php
            if(isset($_SESSION['status'])){
                echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
                unset($_SESSION['status']);
            }
            
            // Show resend button if unverified email is stored
            if (isset($_SESSION['unverified_email'])) {
                $unverifiedEmail = $_SESSION['unverified_email'];
                echo "
                    <div class='row g-1 my-3 mx-2'>
                        <form action='resend_verification.php' method='POST' class=''>
                            <input type='hidden' name='user_email' value='$unverifiedEmail'>
                            <button type='submit' class='btn btn-outline-warning'>Resend Verification Email to $unverifiedEmail</button>
                        </form>
                    </div>
                ";
                unset($_SESSION['unverified_email']); // clear stored email from session after showing
            }
        ?>

        <form action="function_login.php" id="loginForm" class="was-validated" method="POST">
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <h3>Login</h3>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginEmail" class="form-control" type="text" name="user_email" placeholder="Email" value="" required="">
                        <label for="loginEmail">Email</label>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginPassword" class="form-control" type="password" name="user_password" placeholder="Password" value="" required="">
                        <label for="loginPassword">Password</label>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="forgot-password mt-10">
                        <p>Don't have an account yet? <a href="signup.php">Sign Up</a></p>
                        <p>Forgot password? <a href="forgot_password.php">Reset Password</a></p>
                    </div>
                </div>
            </div>
            <div class="submit-login">
                <button id="btnLogin" class="btn btn-outline-success my-2 my-sm-0" name="btnLogin" type="submit">Login</button>
                <a href="products.php" class="btn btn-outline-primary btn-my-sm-0">Browse as Guest</a>
            </div>
        </form>
    </div>
</div>

<?php
include 'includes\footer.php';
?>