<?php
session_start(); // Start the session
header('Content-Type: application/json'); // Set content type to JSON

// Include your database connection file here
include 'connection.php'; // Corrected path for connection.php

$response = []; // Initialize response array

// Check if the search term is set
if (isset($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);

    if (empty($search_term)) {
        $response = ['error' => 'Search term cannot be empty.'];
        echo json_encode($response);
        exit;
    }

    try {
        // Ensure PDO error mode is set to exception, if not already done in connection.php
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare a SQL statement to fetch the product
        // Using LIKE for a broader search. Concatenate '%' within the execute array for safety.
        $stmt = $conn->prepare("SELECT product_ID, product_name, price FROM product WHERE product_name LIKE :search_term OR product_ID LIKE :search_term_id");

        // Execute the statement
        $stmt->execute([
            ':search_term' => '%' . $search_term . '%', // For product name
            ':search_term_id' => $search_term . '%' // For product ID (assuming ID might be searched as string)
        ]);

        // Fetch all the matching products
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($products) {
            $response = ['success' => true, 'products' => $products];
        } else {
            $response = ['success' => false, 'message' => 'No products found matching your search term.'];
        }

    } catch (PDOException $e) {
        // Log error and return a generic error message
        error_log("PDOException in fetch_product.php: " . $e->getMessage());
        $response = ['error' => 'Failed to fetch products due to a database error.'];
        http_response_code(500); // Internal Server Error
    } catch (Exception $e) {
        error_log("Exception in fetch_product.php: " . $e->getMessage());
        $response = ['error' => 'An unexpected error occurred.'];
        http_response_code(500); // Internal Server Error
    }

} else {
    $response = ['error' => 'No search term provided.'];
    http_response_code(400); // Bad Request
}

echo json_encode($response);
exit;
?>
