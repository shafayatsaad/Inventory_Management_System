<?php
// Test script for edit_product.php
class EditProductTest {
    public function run() {
        // Setup: Ensure a product exists to be edited
        $conn = new PDO("mysql:host=localhost;dbname=inventory", "testuser", "password");
        $stmt = $conn->prepare("INSERT INTO product (product_name, description, price, quantity) VALUES ('Test Product', 'Test Description', 10.00, 100)");
        $stmt->execute();
        $product_id = $conn->lastInsertId();

        // Simulate the POST request
        $_POST['product_ID'] = $product_id;
        $_POST['product_name'] = 'Updated Product';
        $_POST['price'] = 20.00;
        $_POST['mfg_date'] = '2023-01-01';
        $_POST['exp_date'] = '2024-01-01';
        $_POST['catagory'] = 'Updated Category';

        // Simulate the GET request
        $_GET['product_ID'] = $product_id;


        // Include the script to be tested
        ob_start();
        include __DIR__ . '/../edit_product.php';
        ob_end_clean();

        // Verification
        $stmt = $conn->prepare("SELECT * FROM product WHERE product_ID = :product_id");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['product_name'] === 'Updated Product' && $result['price'] == 20.00) {
            echo "Test passed: Product was updated successfully.\n";
            return true;
        } else {
            echo "Test failed: Product was not updated.\n";
            return false;
        }
    }
}

$test = new EditProductTest();
$test->run();
?>
