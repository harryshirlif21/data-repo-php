<?php
// db_connection.php
// This file establishes a connection to your MySQL database.

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration constants
// *** IMPORTANT: Replace these with your actual database credentials ***
define('DB_SERVER', 'localhost'); // Your database server, usually 'localhost'
define('DB_USERNAME', 'root'); // Your database username
define('DB_PASSWORD', ''); // Your database password
define('DB_NAME', 'data_repository'); // The name of your database

// Attempt to connect to the MySQL database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
// The line below was causing issues. It sends output which breaks header redirects.
// die("database connected " . $mysqli->connect_error);

// Check if the connection was successful
if ($mysqli->connect_errno) {
    // If connection fails, terminate script and display error
    die("ERROR: Could not connect to database. " . $mysqli->connect_error);
}

// Set character set to UTF-8 for proper handling of various characters
$mysqli->set_charset("utf8mb4");

// You can uncomment the line below for debugging purposes to confirm connection
// However, keep it commented out when using this file with scripts that redirect (like admin_actions.php)
// echo "Database connected successfully!";

?>