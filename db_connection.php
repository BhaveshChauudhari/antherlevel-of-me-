<?php
// db_connection.php

// Database connection variables (replace with your actual credentials)
$servername = "localhost";         // Or your DB host (e.g., an IP address or domain)
$username   = "sssonaje_01";  // Your database username
$password   = "Gaurav@2709";  // Your database password
$dbname     = "sssonaje_01";      // The name of your database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // It's usually better to log this error and show a generic message to the user
    // For development, die() is okay to see the exact error.
    // For production, consider a more graceful error handling.
    error_log("Database Connection Failed: " . $conn->connect_error); // Log the actual error
    die("Sorry, we are experiencing some technical difficulties. Please try again later."); // User-friendly message
}

// Optional: Set character set to utf8mb4 for full Unicode support (good for Marathi/Devanagari)
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // You might choose to die here as well if charset is critical
}

// The $conn variable is now established and ready to be used by any script that includes this file.
?>