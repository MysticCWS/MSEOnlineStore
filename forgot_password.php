<?php 
session_start();
include 'dbcon.php';
include 'includes/header.php'; 
echo ' | Forgot Password';
include 'includes/header2.php';
?>

<div class="content">
    <div class="col-md-4 mx-auto px-4 py-4 border rounded bg-white">
        <?php
            if(isset($_SESSION['status'])){
                echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
                unset($_SESSION['status']);
            }
        ?>

        <form action="function_forgot_password.php" method="POST">
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <h3>Reset Password</h3>
                </div>
            </div>

            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="forgotEmail" class="form-control" type="email" name="user_email" placeholder="Registered Email" required>
                        <label for="forgotEmail">Registered Email</label>
                    </div>
                </div>
            </div>
            
            <div class="row g-2 my-3 mx-2">
                <!-- Dummy div to space form and button evenly -->
            </div>

            <div class="submit-login">
                <button class="btn btn-outline-primary my-2 my-sm-0" name="btnForgot" type="submit">Send Reset Link</button>
                <a href="login.php" class="btn btn-outline-secondary">Back to Login</a>
            </div>
        </form>
    </div>
</div>

<?php 
include 'includes/footer.php'; 
?>