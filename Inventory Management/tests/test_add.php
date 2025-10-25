<?php
// Test script for add.php SQL injection vulnerability
class AddTest {
    public function run() {
        // Simulate a malicious POST request
        $_POST = [
            'table_name' => 'users',
            'username' => 'testuser',
            'password' => 'testpass',
            'email' => 'test@example.com',
            'role' => 'user',
            // Malicious key to inject SQL
            'extra_col`) VALUES (1); -- ' => 'malicious_value'
        ];

        // Include the script to be tested
        ob_start();
        include __DIR__ . '/../database/add.php';
        $response_message = ob_get_clean();

        // Check the response
        if (strpos($response_message, "New record created successfully") !== false) {
            // Check if the malicious column was actually added
            $conn = new PDO("mysql:host=localhost;dbname=inventory", "testuser", "password");
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = 'testuser'");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (isset($result['extra_col']) || isset($result['extra_col`) VALUES (1); -- '])) {
                echo "Vulnerability confirmed: Malicious SQL was executed.\n";
                return false;
            } else {
                echo "Test passed: Malicious SQL was blocked.\n";
                return true;
            }
        } else {
            echo "Test passed: Malicious SQL was blocked.\n";
            return true;
        }
    }
}

$test = new AddTest();
$test->run();
?>
