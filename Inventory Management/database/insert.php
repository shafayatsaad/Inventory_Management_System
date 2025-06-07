<?php
session_start();
// connection.php includes error_logger.php
include_once __DIR__ . '/connection.php';

// Define default redirect URL for errors or non-POST access.
// create_inventory.php is likely the form page that submits to this script.
$response_redirect_url = '../create_inventory.php';

// --- Authorization Check (Example) ---
// Ensure the user is logged in and has the appropriate role (e.g., 'admin' or 'inventory_manager')
// Adjust 'user_id' and 'user_type' to match your actual session variable names.
if (!isset($_SESSION['user_id'])) { // Check if user is logged in
    log_error("Unauthorized access attempt to insert.php: User not logged in.", "insert.php - Auth Check");
    $_SESSION['form_error'] = "You must be logged in to perform this action.";
    header("Location: ../login.php?error=unauthorized"); // Redirect to login page
    exit;
}
// Example: Role-based access control
// if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'inventory_manager') {
//     log_error("Permission denied for insert.php. User ID: " . $_SESSION['user_id'] . ", User Type: " . $_SESSION['user_type'], "insert.php - Auth Check");
//     $_SESSION['form_error'] = "You do not have permission to perform this action.";
//     header("Location: ../index.php?error=forbidden"); // Redirect to a general page or dashboard
//     exit;
// }


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Null coalescing operator (??) to provide default null if not set
    $product_ID = trim($_POST['product_ID'] ?? '');
    $supplier_ID = trim($_POST['supplier_ID'] ?? '');
    $quantity = trim($_POST['quantity'] ?? '');
    $shelf_no = trim($_POST['shelf_no'] ?? '');
    $category = trim($_POST['category'] ?? ''); // Assuming 'category' is a field in 'manage' table
    $buy_date = trim($_POST['buy_date'] ?? '');

    // --- Input Validation ---
    $validation_errors = [];
    if (empty($product_ID)) $validation_errors[] = "Product ID is required.";
    if (empty($supplier_ID)) $validation_errors[] = "Supplier ID is required.";
    if ($quantity === '') $validation_errors[] = "Quantity is required."; // Can be 0, but not empty string
    elseif (!is_numeric($quantity)) $validation_errors[] = "Quantity must be a numeric value.";
    elseif ($quantity < 0) $validation_errors[] = "Quantity cannot be negative."; // Or $quantity <= 0 if 0 is not allowed

    if (empty($shelf_no)) $validation_errors[] = "Shelf number is required.";
    if (empty($category)) $validation_errors[] = "Category is required.";
    if (empty($buy_date)) $validation_errors[] = "Buying date is required.";
    elseif (!DateTime::createFromFormat('Y-m-d', $buy_date)) $validation_errors[] = "Invalid buying date format. Please use YYYY-MM-DD.";


    if (!empty($validation_errors)) {
        $error_summary = implode(" ", $validation_errors);
        log_error("Validation failed in insert.php: " . $error_summary . ". Data: " . print_r($_POST, true), "insert.php - Validation");
        $_SESSION['form_error'] = "Please correct the following errors: " . $error_summary;
        $_SESSION['form_data'] = $_POST; // Store submitted data in session to repopulate form
        header("Location: " . $response_redirect_url . "?status=validation_error");
        exit;
    }

    // At this point, $quantity is numeric. Cast to int if appropriate for DB.
    $quantity_int = (int)$quantity;

    try {
        // Assuming 'manage' table has these columns. Adjust if your schema is different.
        $sql = "INSERT INTO manage (product_ID, supplier_ID, quantity, shelf_no, category, buy_date)
                VALUES (:product_ID, :supplier_ID, :quantity, :shelf_no, :category, :buy_date)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':product_ID', $product_ID);
        $stmt->bindParam(':supplier_ID', $supplier_ID);
        $stmt->bindParam(':quantity', $quantity_int, PDO::PARAM_INT);
        $stmt->bindParam(':shelf_no', $shelf_no);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':buy_date', $buy_date);

        $stmt->execute();

        $_SESSION['form_success'] = "Inventory record created successfully for Product ID: " . htmlspecialchars($product_ID);
        // Redirect to a page showing inventory list or success message
        header("Location: ../inventory.php?status=success_insert");
        exit;

    } catch (PDOException $e) {
        $error_details_for_log = "PDOException in insert.php. SQL attempt: {$sql}. Error: " . $e->getMessage() . ". Submitted Data: " . print_r($_POST, true);
        log_error($error_details_for_log, "insert.php - PDOException");

        // Set user-friendly error message and repopulate form data
        $_SESSION['form_error'] = "A database error occurred while saving the inventory data. Please ensure all IDs are correct and try again. If the problem persists, contact support.";
        // Specific error for duplicate entry if applicable (e.g. if manage table has unique constraints beyond PK)
        // if ($e->getCode() == '23000') { // Integrity constraint violation
        //    $_SESSION['form_error'] = "Error: This record might already exist or violate a database rule (e.g., duplicate or non-existent Product/Supplier ID).";
        // }
        $_SESSION['form_data'] = $_POST;
        header("Location: " . $response_redirect_url . "?status=db_error");
        exit;
    }
} else {
    // Request method is not POST
    log_error("Non-POST access attempt to insert.php", "insert.php - Access Control");
    // Redirect to the form page or homepage, perhaps with a generic message
    // Not setting a session error here as it's not a form submission error.
    header("Location: " . $response_redirect_url);
    exit;
}
?>
