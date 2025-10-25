<?php
session_start();
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/error_logger.php';

if (isset($_GET["product_ID"])) {
    $product_ID = $_GET["product_ID"];

    try {
        $stmt = $conn->prepare("DELETE FROM product WHERE product_ID = :product_ID");
        $stmt->bindParam(':product_ID', $product_ID, PDO::PARAM_INT);
        $stmt->execute();
        $_SESSION['success_message'] = "Product deleted successfully.";
    } catch (PDOException $e) {
        log_error("Error deleting product: " . $e->getMessage(), "delete_product.php");
        $_SESSION['error_message'] = "Failed to delete product. Please try again.";
    }
} else {
    $_SESSION['error_message'] = "Product ID not specified.";
}

header("location: ../index_product.php");
exit;
?>
