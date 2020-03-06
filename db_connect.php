<?php
    require_once('config.php');
    /**
    * This file make connection to database using following parameters.
    */
    $servername = $_ENV['DB_SERVER'];
    $username = $_ENV['DB_USER'];
    $password = $_ENV['DB_PASS'];
    $dbname = $_ENV['DB_NAME'];
    $charset = $_ENV['DB_CHARSET'];

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ];
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=$charset", $username, $password, $options);
?>