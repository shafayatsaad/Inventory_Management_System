<?php
require_once __DIR__ . '/error_logger.php';
$servername = 'localhost';
$username = 'root';
$password = '';
$dbname = 'inventory';

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\Exception $e) {
    log_error("Database Connection Failed: " . $e->getMessage(), "PDO Connection");
    // Display a user-friendly message instead of echoing the raw error
    // This might be tricky here as it's a connection file.
    // For now, we'll keep it simple and just log.
    // Or, you could throw a custom exception to be caught by the calling script.
    // For this subtask, just logging is fine. Let the calling script handle user display.
    echo "Database connection error. Please check logs."; // Or a more generic site-wide error page redirect
    exit; // Stop script execution if connection fails
}

return $conn;
?>
