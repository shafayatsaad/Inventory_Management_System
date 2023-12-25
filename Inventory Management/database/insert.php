<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Starting session.
session_start();
include 'database/connection.php';
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $productName = $_POST['product_name'];
        $quantity = $_POST['quantity'];
        $netPayable = $_POST['net_payable'];
        $stmt = $conn->prepare("INSERT INTO bill (product_name, quantity, net_payable) VALUES (?, ?, ?)");
        $stmt->execute([$productName, $quantity, $netPayable]);
        echo "Data inserted successfully!";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?>
