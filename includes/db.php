<?php
// Prevent multiple includes/redefinitions
if (!defined('DB_HOST')) {
    // Database configuration
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'Wattzelec'); // Updated database name
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');
}

// Enable error reporting during development (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    // Check if the connection already exists
    if (!isset($conn)) {
        // Create PDO connection
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays by default
            PDO::ATTR_EMULATE_PREPARES => false, // Disable emulation for better security
            PDO::ATTR_PERSISTENT => true // Use persistent connections for performance
        ];

        $conn = new PDO($dsn, DB_USER, DB_PASS, $options);

        // Set timezone (optional, adjust as needed)
        $conn->exec("SET time_zone = '+00:00'");
    }
} catch (PDOException $e) {
    // Handle connection errors gracefully
    error_log("Database connection failed: " . $e->getMessage()); // Log errors for debugging
    die("An error occurred while connecting to the database. Please try again later.");
}