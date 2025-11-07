<?php
session_start();
include 'dbcon.php';

if (isset($_POST['btnForgot'])) {
    $user_email = $_POST['user_email'];

    try {
        $link = $auth->getPasswordResetLink($user_email);
        $auth->sendPasswordResetLink($user_email);
        
        $_SESSION['status'] = "Password reset link has been sent to <b>$user_email</b>. Please check your inbox.";
        header("Location: forgot_password.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['status'] = "Error: " . $e->getMessage();
        header("Location: forgot_password.php");
        exit();
    }
} else {
    $_SESSION['status'] = "Invalid request.";
    header("Location: forgot_password.php");
    exit();
}