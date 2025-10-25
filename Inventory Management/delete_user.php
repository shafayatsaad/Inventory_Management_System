<?php
session_start();
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/error_logger.php';

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$management_page_url = '../supplier.php';

if (!isset($_SESSION['user_id'])) {
    log_error("Unauthorized attempt to access delete_user.php: User not logged in.", "delete_user.php - Auth Check");
    redirect_with_error('../login.php', 'Please log in to continue.');
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    log_error("Permission denied for delete_user.php. User ID: " . $_SESSION['user_id'] . ", User Type: " . $_SESSION['user_type'], "delete_user.php - Auth Check");
    redirect_with_error('../dashboard.php', 'You are not authorized to perform this action.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        log_error("CSRF token mismatch.", "delete_user.php - CSRF Check");
        redirect_with_error($management_page_url, 'Invalid request. Please try again.');
    }

    if (isset($_POST['user_ID'])) {
        $user_ID_to_delete = trim($_POST['user_ID']);

        if (!filter_var($user_ID_to_delete, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
            log_error("Invalid user_ID format for deletion: '" . htmlspecialchars($user_ID_to_delete) . "'. Admin ID: " . $_SESSION['user_id'], "delete_user.php - Validation");
            redirect_with_error($management_page_url, 'Invalid user ID format provided.');
        }

        if (isset($_SESSION['user_id']) && $user_ID_to_delete == $_SESSION['user_id']) {
            log_error("Admin user (ID: {$_SESSION['user_id']}) attempted to delete their own account.", "delete_user.php - Self Delete Attempt");
            redirect_with_error($management_page_url, 'Action not allowed: You cannot delete your own account.');
        }

        try {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_ID = :user_ID");
            $stmt->bindParam(':user_ID', $user_ID_to_delete, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                log_error("User with ID: {$user_ID_to_delete} deleted successfully by Admin ID: {$_SESSION['user_id']}.", "delete_user.php - Deletion Success");
                $_SESSION['success_message'] = "User (ID: " . htmlspecialchars($user_ID_to_delete) . ") has been deleted successfully.";
                header("Location: " . $management_page_url);
                exit;
            } else {
                log_error("Attempt to delete non-existent user or user already deleted. User ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}.", "delete_user.php - User Not Found");
                redirect_with_error($management_page_url, 'User not found or could not be deleted.');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                log_error("PDOException (Foreign Key Constraint) when trying to delete user ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}. Error: " . $e->getMessage(), "delete_user.php - PDOException FK");
                redirect_with_error($management_page_url, "Cannot delete user as they have related records.");
            } else {
                log_error("PDOException when trying to delete user ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}. Error: " . $e->getMessage(), "delete_user.php - PDOException General");
                redirect_with_error($management_page_url, "A database error occurred.");
            }
        }
    } else {
        log_error("User ID not provided for deletion attempt. Admin ID: " . ($_SESSION['user_id'] ?? 'N/A'), "delete_user.php - Missing ID Parameter");
        redirect_with_error($management_page_url, 'No User ID was specified for deletion.');
    }
} else {
    log_error("Invalid request method.", "delete_user.php - Invalid Method");
    redirect_with_error($management_page_url, 'Invalid request method.');
}
?>
