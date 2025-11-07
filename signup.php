<?php 
session_start();
include 'dbcon.php';
include 'includes\header.php'; 
echo ' | Sign Up';
include 'includes\header2.php';
?>

<div class="content">
    <div class="col-md-4 mx-auto px-4 py-5 border rounded bg-white">
        <!-- Show Status -->
        <?php
            if(isset($_SESSION['status'])){
                echo "<h5 id='statusMessage' class='alert alert-success'>".$_SESSION['status']."</h5>";
                unset($_SESSION['status']);
            }
        ?>

        <form action="function_signup.php" id="signupForm" method="POST" onsubmit="return validateForm()">
            <div class="row g-2 mb-3 mx-2">
                <div class="col-md">
                    <h3>Sign Up</h3>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginEmail" class="form-control" type="text" name="user_email" placeholder="Email" value="" required>
                        <label for="loginEmail">Email</label>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginPassword" class="form-control" type="password" name="user_password" placeholder="Password" required>
                        <label for="loginPassword">Password</label>
                        <div class="invalid-feedback">Password must be at least 8 characters, including one uppercase and one lowercase letter.</div>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="loginConfirmPassword" class="form-control" type="password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="loginConfirmPassword">Confirm Password</label>
                        <div class="invalid-feedback">Passwords do not match.</div>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="form-floating">
                        <input id="displayName" class="form-control" type="text" name="user_name" placeholder="Display Name" required>
                        <label for="displayName">Display Name</label>
                        <div class="invalid-feedback">Please enter your display name.</div>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    
                    <div class="input-group">
                        <!-- Country Code Dropdown -->
                        <select id="countryCode" name="country_code" class="form-select" style="max-width: 90px;" required>
                            <option value="+60" selected>+60</option>
                        </select>

                        <!-- Form-floating for Phone Number -->
                        <div class="form-floating flex-grow-1">
                            <input id="contact" class="form-control" type="text" name="user_contact" placeholder="1XXXXXXXX" required>
                            <label for="contact">Contact Number (1XXXXXXXX)</label>
                            <div class="invalid-feedback">Phone number must start with 1 and be 8 or 9 digits.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row g-2 my-3 mx-2">
                <div class="col-md">
                    <div class="forgot-password mt-10">
                        <p>Already have an account? <a href="login.php">Login</a></p>
                    </div>
                </div>
            </div>
            <div class="submit-login">
                <button id="btnCreate" class="btn btn-outline-success my-2 my-sm-0" name="btnCreate" type="submit">Create</button>
            </div>
        </form>
    </div>

    <script>
    function validateForm() {
        let isValid = true;

        // Email validation
        const email = document.getElementById('loginEmail');
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        email.classList.remove('is-valid', 'is-invalid'); // Reset classes first
        if (!emailPattern.test(email.value)) {
            email.classList.add('is-invalid');
            isValid = false;
        } else {
            email.classList.add('is-valid');
        }

        // Password validation
        const password = document.getElementById('loginPassword');
        const confirmPassword = document.getElementById('loginConfirmPassword');
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z]).{8,}$/;

        password.classList.remove('is-valid', 'is-invalid'); // Reset classes first
        confirmPassword.classList.remove('is-valid', 'is-invalid'); // Reset classes first

        if (!passwordPattern.test(password.value)) {
            password.classList.add('is-invalid');
            isValid = false;
        } else {
            password.classList.add('is-valid');
        }

        if (password.value !== confirmPassword.value) {
            confirmPassword.classList.add('is-invalid');
            isValid = false;
        } else {
            confirmPassword.classList.add('is-valid');
        }

        // Phone number validation
        const contact = document.getElementById('contact');
        const phonePattern = /^1\d{7,8}$/;
        contact.classList.remove('is-valid', 'is-invalid'); // Reset classes first

        if (!phonePattern.test(contact.value)) {
            contact.classList.add('is-invalid');
            isValid = false;
        } else {
            contact.classList.add('is-valid');
        }

        // Name validation
        const displayName = document.getElementById('displayName');
        const namePattern = /^[A-Za-z\s]{2,}$/; // At least 2 characters, letters only
        displayName.classList.remove('is-valid', 'is-invalid'); // Reset classes first

        if (!namePattern.test(displayName.value)) {
            displayName.classList.add('is-invalid');
            isValid = false;
        } else {
            displayName.classList.add('is-valid');
        }

        // Add 'was-validated' class to show validation feedback after submit attempt
        if (!isValid) {
            document.getElementById('signupForm').classList.add('was-validated');
        }

        return isValid;
    }
    </script>
</div>



<?php
include 'includes\footer.php';
?>
