<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;
use Fiuu\Payment;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load(); 

$fiuu = new Payment($_ENV['FIUU_MERCHANT_ID'], $_ENV['FIUU_VERIFY_KEY'], $_ENV['FIUU_SECRET_KEY'], $_ENV['FIUU_ENVIRONMENT']);

// Safely expose the secret key separately
$FIUU_SECRET_KEY = $_ENV['FIUU_SECRET_KEY'];