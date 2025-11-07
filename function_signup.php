<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

session_start();
include 'dbcon.php';

if (isset($_POST['btnCreate'])){
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];
    $user_name = $_POST['user_name'];
    $country_code = $_POST['country_code'];
    $user_contact = $_POST['user_contact'];
    
    try {
        $userProperties = [
            'email' => $user_email,
            'emailVerified' => false,
            'password' => $user_password,
            'displayName' => $user_name,
            'photoUrl' => '',
            'phoneNumber' => $country_code . $user_contact,
        ];

        $createdUser = $auth->createUser($userProperties);

        // Send email verification
        $auth->sendEmailVerificationLink($user_email);

        $_SESSION['status'] = "Sign Up Successful. Please check your email and verify before logging in.";
        header("Location: login.php");
        exit();

    } catch (\Kreait\Firebase\Exception\Auth\EmailExists $e) {
        // Specific: user already registered
        $_SESSION['status'] = "Email already exists. Please log in or use another email.";
        header("Location: signup.php");
        exit();

    } catch (Exception $e) {
        // Catch any other unexpected error
        $_SESSION['status'] = "Error Signing Up: " . $e->getMessage();
        header("Location: signup.php");
        exit();
    }
}