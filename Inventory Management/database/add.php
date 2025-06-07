<?php
// session_start(); // Start session if you plan to use $_SESSION for messages, though this script echoes directly.
// connection.php already includes error_logger.php
include_once __DIR__ . '/connection.php';

$response_message = ""; // To hold a simple response message

// Check if table_name is provided in the POST request
if (isset($_POST['table_name'])) {
    $table_name = trim($_POST['table_name']);

    // Whitelist table names to prevent SQL injection on table names
    // Adjust this list based on your actual database schema and needs
    $allowed_tables = ['users', 'product', 'manage', 'supplier', 'bill']; // Example tables from the project

    if (empty($table_name)) {
        log_error("Table name was empty in POST request.", "add.php");
        $response_message = "Error: Table name not specified or empty.";
        echo $response_message;
        exit;
    }

    if (!in_array($table_name, $allowed_tables)) {
        log_error("Invalid table name specified: " . htmlspecialchars($table_name), "add.php - Table Whitelist Check");
        $response_message = "Error: Invalid table operation attempted."; // Generic message for security
        echo $response_message;
        exit;
    }

    $data = [];
    // Prepare data for insertion, excluding 'table_name' itself
    // And also excluding any submit button names if they are POSTed
    $excluded_keys = ['table_name', 'submit']; // Add other keys to exclude if necessary

    foreach ($_POST as $key => $value) {
        if (!in_array($key, $excluded_keys)) {
            // Basic sanitization: strip tags. For more robust security, consider per-column validation.
            // htmlspecialchars is more for output, strip_tags for input here is a basic measure.
            $data[$key] = strip_tags($value);
        }
    }

    if (!empty($data)) {
        // Dynamically build columns and placeholders
        // Ensure that keys in $data are actual column names in your database tables
        $columns = implode(", ", array_map(function($col) use ($conn, $table_name) {
            // Optionally, you could query table metadata to ensure columns exist,
            // but this adds overhead. Rely on good input validation and whitelisted table names.
            // For now, just backtick column names if they might contain spaces or reserved words (good practice).
            return "`" . str_replace("`", "``", $col) . "`";
        }, array_keys($data)));

        $placeholders = ":" . implode(", :", array_keys($data));

        $sql = "INSERT INTO `{$table_name}` ({$columns}) VALUES ({$placeholders})";

        try {
            $stmt = $conn->prepare($sql);
            // PDO will handle quoting of values in $data
            $stmt->execute($data);
            $response_message = "New record created successfully in " . htmlspecialchars($table_name) . ".";
            // You might want to return the ID of the inserted row:
            // $lastId = $conn->lastInsertId();
            // $response_message .= " New record ID: " . $lastId;

        } catch (PDOException $e) {
            // Log detailed error, including which table and what data was attempted
            $logData = print_r($data, true); // Convert array to string for logging
            log_error("PDOException in add.php for table `{$table_name}`: " . $e->getMessage() . " - SQL: {$sql} - Data: {$logData}", "add.php - INSERT");

            // Provide a generic error message to the client
            $response_message = "Error: Could not create new record. An issue occurred with the database operation.";
            // If you want to be more specific for certain errors (e.g., duplicate entry):
            // if ($e->getCode() == '23000') { // Integrity constraint violation
            //    $response_message = "Error: Could not create new record. It might be a duplicate entry or violate a database rule.";
            // }
        }
    } else {
        log_error("No valid data provided to insert into table: " . htmlspecialchars($table_name), "add.php - Data Check");
        $response_message = "Error: No data provided for insertion.";
    }
} else {
    log_error("Table name not specified in POST request.", "add.php - Initial Check");
    $response_message = "Error: Required parameter 'table_name' not specified.";
}

// Output the response message
// This script is likely an endpoint. Consider JSON for more structured responses:
// header('Content-Type: application/json');
// echo json_encode(['message' => $response_message]);
echo $response_message; // For simple text response

?>
