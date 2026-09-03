<?php

// Database connection settings
$host = "localhost";
$dbname = "frostcore_db";
$username = "root";
$password = "";

// Connect to MySQL using PDO
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );

    // Show database errors clearly while developing
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Database connected successfully!";

} catch (PDOException $e) {

    // Show the connection error
    die("Database connection failed: " . $e->getMessage());

}
?>