<?php
include 'database/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];

    // Calculate total
    $total = $quantity * $price;

    // Insert into 'bill' table
    $conn->query("INSERT INTO bill (product_name, quantity, total) VALUES ('$product', $quantity, $total)");

    // Redirect to index.php
    header("Location: index.php");
    exit();
}
?>
