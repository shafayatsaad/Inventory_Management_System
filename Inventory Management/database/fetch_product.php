<?php
// Include your database connection file here
include 'database/connection.php';

// Check if the search term is set
if (isset($_GET['search_term'])) {
    $search_term = $_GET['search_term'];

    // Prepare a SQL statement to fetch the product
    $stmt = $conn->prepare("SELECT * FROM product WHERE product_name LIKE ?");
    $stmt->execute([$search_term . '%']);

    // Fetch all the matching products
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the products as a JSON response
    echo json_encode($products);
}
?>
