<?php
// config.php

// Database credentials
define('DB_HOST', 'mysql');        // The MySQL container hostname
define('DB_NAME', 'pdf_db');       // The database name
define('DB_USER', 'myuser');       // The database username
define('DB_PASS', 'mypassword');  // The database password

// Function to get PDO connection
function getPDO() {
    try {
        // Create PDO connection
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ]
        );
    } catch (PDOException $e) {
        // Log the error and provide a generic error message to the user
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection error. Please try again later.");
    }

    return $pdo;
}
