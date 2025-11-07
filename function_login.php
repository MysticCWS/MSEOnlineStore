<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

session_start();
include 'dbcon.php';

if (isset($_POST['btnLogin'])){
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];
    
    // Check if the email is in valid format first
    if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['status'] = "Invalid email format.";
        header("Location: login.php");
        exit();
    }
    
    try {
        $user = $auth->getUserByEmail("$user_email");
        
        // Check if email is verified
        if (!$user->emailVerified) {
            $_SESSION['status'] = "Please verify your email before logging in.";
            $_SESSION['unverified_email'] = $user_email; // store email temporarily
            header("Location: login.php");
            exit();
        }
        
        try {
            $signInResult = $auth->signInWithEmailAndPassword($user_email, $user_password);
            $idTokenString = $signInResult->idToken();
            
            try {
                $verifiedIdToken = $auth->verifyIdToken($idTokenString);
                $uid = $verifiedIdToken->claims()->get('sub');
                
                $_SESSION['verified_user_id'] = $uid;
                $_SESSION['idTokenString'] = $idTokenString;
                
                $_SESSION['status'] = "Logged in successfully.";
                
                // Check admin role
                $claims = $auth->getUser($uid)->customClaims;
                if (!empty($claims['admin']) && $claims['admin'] === true) {
                    $_SESSION['is_admin'] = true;
                    header("Location: admin_home.php");
                    exit();
                } else {
                    header("Location: home.php");
                    exit();
                }
                
            } catch (InvalidToken $e) {
                echo 'The token is invalid: '.$e->getMessage();
                
            } catch (\InvalidArgumentException $e) {
                echo 'The token could not be parsed: '.$e->getMessage();
                        
            }
        } catch (Exception $e) {
            $_SESSION['status'] = "Wrong Password";
            header("Location: login.php");
            die();
            
        }
    } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
        $_SESSION['status'] = "Invalid Email";
        header("Location: login.php");
        die();
        
    }
} else {
    $_SESSION['status'] = "Not Allowed";
    header("Location: login.php");
    die();
}