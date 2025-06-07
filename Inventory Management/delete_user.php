<?php
session_start();
// delete_user.php is in 'Inventory Management/' root, so connection is in 'database/'
include_once __DIR__ . '/database/connection.php';

// Define the URL for redirection, typically where users are listed or managed.
// The original prompt used supplier.php, let's assume that's correct.
$management_page_url = '../supplier.php';
// If users are managed elsewhere, like an admin_users.php, adjust this:
// $management_page_url = '../admin_users.php';

// --- Authorization Check ---
// Ensure the user is logged in and has an 'admin' role.
// Adjust session variable names ('user_id', 'user_type') if yours are different.
if (!isset($_SESSION['user_id'])) {
    log_error("Unauthorized attempt to access delete_user.php: User not logged in.", "delete_user.php - Auth Check");
    // Set a generic message for the login page if it can display them
    $_SESSION['login_error_message'] = "Please log in to continue.";
    header("Location: ../login.php?error=auth_required");
    exit;
}
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    log_error("Permission denied for delete_user.php. User ID: " . $_SESSION['user_id'] . ", User Type: " . $_SESSION['user_type'], "delete_user.php - Auth Check");
    $_SESSION['user_management_error'] = "You are not authorized to perform this action.";
    // Redirect to a dashboard or a page indicating lack of permission
    header("Location: ../dashboard.php?error=forbidden");
    exit;
}

// --- Process Deletion Request ---
if (isset($_GET['user_ID'])) {
    $user_ID_to_delete = trim($_GET['user_ID']);

    // --- Validate user_ID format ---
    // Ensure it's a positive integer. Adjust if user IDs can be other formats (e.g., UUIDs).
    if (!filter_var($user_ID_to_delete, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
        log_error("Invalid user_ID format for deletion: '" . htmlspecialchars($user_ID_to_delete) . "'. Admin ID: " . $_SESSION['user_id'], "delete_user.php - Validation");
        $_SESSION['user_management_error'] = "Invalid user ID format provided.";
        header("Location: " . $management_page_url . "?error=invalid_id_format");
        exit;
    }

    // --- Business Rule: Prevent Self-Deletion ---
    if (isset($_SESSION['user_id']) && $user_ID_to_delete == $_SESSION['user_id']) {
        log_error("Admin user (ID: {$_SESSION['user_id']}) attempted to delete their own account.", "delete_user.php - Self Delete Attempt");
        $_SESSION['user_management_error'] = "Action not allowed: You cannot delete your own account.";
        header("Location: " . $management_page_url . "?error=cannot_delete_self");
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_ID = :user_ID");
        // Bind as integer
        $stmt->bindParam(':user_ID', $user_ID_to_delete, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            log_error("User with ID: {$user_ID_to_delete} deleted successfully by Admin ID: {$_SESSION['user_id']}.", "delete_user.php - Deletion Success");
            $_SESSION['user_management_success'] = "User (ID: " . htmlspecialchars($user_ID_to_delete) . ") has been deleted successfully.";
            header("Location: " . $management_page_url . "?status=user_deleted");
            exit;
        } else {
            // No rows affected means user_ID was not found (or already deleted)
            log_error("Attempt to delete non-existent user or user already deleted. User ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}.", "delete_user.php - User Not Found");
            $_SESSION['user_management_error'] = "User (ID: " . htmlspecialchars($user_ID_to_delete) . ") not found or could not be deleted. It may have already been removed.";
            header("Location: " . $management_page_url . "?error=user_not_found");
            exit;
        }
    } catch (PDOException $e) {
        // Specific check for foreign key constraint violation (e.g., if user has related records)
        if ($e->getCode() == '23000') { // SQLSTATE 23000: Integrity constraint violation
            log_error("PDOException (Foreign Key Constraint) when trying to delete user ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}. Error: " . $e->getMessage(), "delete_user.php - PDOException FK");
            $_SESSION['user_management_error'] = "Cannot delete user (ID: " . htmlspecialchars($user_ID_to_delete) . ") as they have related records in the system. Please reassign or delete those records first.";
        } else {
            log_error("PDOException when trying to delete user ID: {$user_ID_to_delete}. Admin ID: {$_SESSION['user_id']}. Error: " . $e->getMessage(), "delete_user.php - PDOException General");
            $_SESSION['user_management_error'] = "A database error occurred while trying to delete the user. Please check system logs or contact support.";
        }
        header("Location: " . $management_page_url . "?error=db_error");
        exit;
    }
} else {
    // user_ID parameter not provided in GET request
    log_error("User ID not provided for deletion attempt. Admin ID: " . ($_SESSION['user_id'] ?? 'N/A'), "delete_user.php - Missing ID Parameter");
    $_SESSION['user_management_error'] = "No User ID was specified for deletion.";
    header("Location: " . $management_page_url . "?error=no_id_provided");
    exit;
}
?>
