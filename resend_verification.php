<?php
session_start();
include 'dbcon.php';

if (isset($_POST['user_email'])) {
    $email = $_POST['user_email'];

    try {
        // Send verification link
        $auth->sendEmailVerificationLink($email);

        $_SESSION['status'] = "A new verification email has been sent to <b>$email</b>. Please check your inbox.";
        header("Location: login.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['status'] = "Could not send verification email. Error: " . $e->getMessage();
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['status'] = "Invalid request.";
    header("Location: login.php");
    exit();
}