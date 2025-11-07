<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

session_start();
include 'dbcon.php';

unset($_SESSION['verified_user_id']);
unset($_SESSION['idTokenString']);
    
$_SESSION['status'] = "Logged out successfully.";
header("Location: login.php");
die();
