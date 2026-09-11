<?php

// Database connection settings
$host = "localhost";
$dbname = "frostcore_db";
$username = "root";
$password = "";

try {

    // Connect to MySQL using PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Show database errors while developing
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // Return database results as associative arrays
    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    // Use real prepared statements
    $pdo->setAttribute(
        PDO::ATTR_EMULATE_PREPARES,
        false
    );

} catch (PDOException $e) {

    // Don't expose database details to website visitors
    die("Database connection failed.");

}

?>