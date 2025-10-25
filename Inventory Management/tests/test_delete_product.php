<?php
// Test script for delete_product.php
class DeleteProductTest {
    public function run() {
        // Setup: Ensure a product exists to be deleted
        $conn = new PDO("mysql:host=localhost;dbname=inventory", "testuser", "password");
        $stmt = $conn->prepare("INSERT INTO product (product_name, description, price, quantity) VALUES ('Test Product', 'Test Description', 10.00, 100)");
        $stmt->execute();
        $product_id = $conn->lastInsertId();

        // Simulate the GET request
        $_GET['product_ID'] = $product_id;

        // Include the script to be tested
        ob_start();
        include __DIR__ . '/../database/delete_product.php';
        ob_end_clean();

        // Verification
        $stmt = $conn->prepare("SELECT * FROM product WHERE product_ID = :product_id");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            echo "Test failed: Product was not deleted.\n";
            return false;
        } else {
            echo "Test passed: Product was deleted successfully.\n";
            return true;
        }
    }
}

$test = new DeleteProductTest();
$test->run();
?>
