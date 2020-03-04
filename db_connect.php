<?php
    /**
    * This file make connection to database using following parameters.
    */
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "naiveBayes";
    $charset = 'utf8mb4';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=$charset", $username, $password, $options);
?>