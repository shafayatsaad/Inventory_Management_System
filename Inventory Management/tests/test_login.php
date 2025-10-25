<?php
define('TEST_MODE', true);

class LoginTest {
    public function run() {
        // Setup: Ensure a user exists to login
        $conn = new PDO("mysql:host=localhost;dbname=inventory", "testuser", "password");

        // Clean up previous test user
        $conn->exec("DELETE FROM users WHERE email = 'test@example.com'");

        $password = 'password';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, first_name) VALUES ('testuser', :password, 'test@example.com', 'user', 'Test')");
        $stmt->bindParam(':password', $hashed_password);
        $stmt->execute();

        // Simulate the POST request
        $_POST['email'] = 'test@example.com';
        $_POST['password'] = $password;

        // Include the script to be tested
        ob_start();
        include __DIR__ . '/../login.php';
        ob_end_clean();

        // Verification
        if (isset($_SESSION['user']) && $_SESSION['user']['email'] === 'test@example.com') {
            echo "Test passed: User logged in successfully.\n";
            session_destroy();
            return true;
        } else {
            echo "Test failed: User login failed.\n";
            return false;
        }
    }
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$test = new LoginTest();
$test->run();
?>
