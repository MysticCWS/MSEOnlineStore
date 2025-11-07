<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require __DIR__.'/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Kreait\Firebase\Auth\UserQuery;
use Kreait\Firebase\Auth\SignInResult\SignInResult;
use Kreait\Firebase\Contract\Storage;

//Realtime Database
$factory = (new Factory)
    ->withServiceAccount('mseonlinestore-44d53-firebase-adminsdk-fbsvc-dd0ced71c1.json')
    ->withDatabaseUri('https://mseonlinestore-44d53-default-rtdb.asia-southeast1.firebasedatabase.app/');

$database = $factory->createDatabase();

//Auth Users
$auth = $factory->createAuth();

//Storage
$storage = (new Factory())
    ->withServiceAccount('mseonlinestore-44d53-firebase-adminsdk-fbsvc-dd0ced71c1.json')
    ->withDefaultStorageBucket('mseonlinestore-44d53.firebasestorage.app')
    ->createStorage();

$storageClient = $storage->getStorageClient();
$defaultBucket = $storage->getBucket();
$anotherBucket = $storage->getBucket('another-bucket');


//Check if a URL exists
function URLcheck($url){
   $headers=get_headers($url);
   return stripos($headers[0],"200 OK")?true:false;
}