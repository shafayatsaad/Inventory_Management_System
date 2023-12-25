<?php
    // Start the session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // Include the file with your database connection information
    include 'database/connection.php';

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check if user_id is set in POST
        if (isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];

            // Prepare the SQL statement
            $sql = "DELETE FROM Users WHERE id = :user_id";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $user_id);

            // Execute the statement
            if ($stmt->execute()) {
                // Redirect to the register page after successful deletion
                header('Location: register.php');
                exit;
            } else {
                // Handle error here
                echo "Error: Failed to delete user";
            }
        }
    }
?>
