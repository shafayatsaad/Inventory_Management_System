<?php
session_start();
// connection.php includes error_logger.php
include_once __DIR__ . '/database/connection.php';

// Default user name if not set in session (should ideally not happen if page requires login)
$first_name_display = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Guest';

// Initialize form variables from session if available (e.g., after a redirect due to validation error)
$form_data_session = $_SESSION['form_data'] ?? []; // Use a distinct variable name
if(isset($_SESSION['form_data'])) unset($_SESSION['form_data']); // Clear after use

// Assign to variables that will be used in the form's value attributes
$product_ID_form_value = $form_data_session['product_ID'] ?? '';
$quantity_form_value = $form_data_session['quantity'] ?? '';
$shelf_no_form_value = $form_data_session['shelf_no'] ?? '';
$buy_date_form_value = $form_data_session['buy_date'] ?? '';

// Retrieve and clear session messages for displaying on this page load
$form_error_message = $_SESSION['form_error'] ?? ''; // Use a distinct variable name
if(isset($_SESSION['form_error'])) unset($_SESSION['form_error']);

$form_success_message = $_SESSION['form_success'] ?? ''; // Use a distinct variable name
if(isset($_SESSION['form_success'])) unset($_SESSION['form_success']);


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Trim and retrieve POST data, providing empty string as default if not set
    $product_ID_posted = trim($_POST["product_ID"] ?? '');
    $quantity_posted = trim($_POST["quantity"] ?? '');
    $shelf_no_posted = trim($_POST["shelf_no"] ?? '');
    $buy_date_posted = trim($_POST["buy_date"] ?? '');

    // Store current POST data in session immediately for repopulation in case of any error/redirect
    $_SESSION['form_data'] = $_POST;

    $errors = []; // Array to accumulate validation errors
    if (empty($product_ID_posted)) $errors[] = "Product ID is required.";

    if ($quantity_posted === '') { // Check for empty string specifically for quantity
        $errors[] = "Quantity is required.";
    } elseif (!is_numeric($quantity_posted)) {
        $errors[] = "Quantity must be a numeric value.";
    } elseif ($quantity_posted <= 0) {
        $errors[] = "Quantity must be a positive number.";
    }

    // Shelf number might be optional depending on requirements, if so, remove from empty check
    if (empty($shelf_no_posted)) $errors[] = "Shelf number is required.";

    // Buy date might be optional, if so, this check should only apply if not empty
    if (empty($buy_date_posted)) {
        // $errors[] = "Buying date is required."; // Uncomment if date is mandatory
    } elseif (!DateTime::createFromFormat('Y-m-d', $buy_date_posted)) {
        $errors[] = "Invalid date format for Buying Date. Please use YYYY-MM-DD.";
    }


    if (!empty($errors)) {
        $_SESSION['form_error'] = implode("<br>", $errors); // Store errors in session
        // Log detailed validation failure
        $log_val_error_message = "Validation failed in create_inventory.php: " . implode("; ", $errors) . ". Submitted Data: " . print_r($_POST, true);
        log_error($log_val_error_message, "create_inventory.php - Validation Failure");
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF'])); // Redirect back to the form page
        exit;
    }

    try {
        $sql = "INSERT INTO manage (product_ID, quantity, shelf_no, buy_date)
                VALUES (:product_ID, :quantity, :shelf_no, :buy_date)";
        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ':product_ID' => $product_ID_posted,
            ':quantity' => (int)$quantity_posted, // Cast quantity to integer
            ':shelf_no' => $shelf_no_posted,
            // Handle empty buy_date: insert NULL if it's empty and column allows NULLs
            ':buy_date' => !empty($buy_date_posted) ? $buy_date_posted : null
        ]);

        unset($_SESSION['form_data']); // Clear form data from session on successful insert
        // Set a success message for the inventory page (after redirect)
        $_SESSION['inventory_action_success_message'] = "Inventory item for Product ID '" . htmlspecialchars($product_ID_posted) . "' has been successfully added!";

        // Log successful operation
        $log_success_message = "Inventory item added for Product ID: {$product_ID_posted} by user: {$first_name_display}. Data: " . print_r($_POST, true);
        log_error($log_success_message, "create_inventory.php - Success");

        header("location: inventory.php"); // Redirect to the main inventory listing page
        exit;

    } catch (PDOException $e) {
        $log_pdo_error_message = "PDOException in create_inventory.php. Attempted SQL: {$sql}. Error: " . $e->getMessage() . ". Submitted Data: " . print_r($_POST, true);
        log_error($log_pdo_error_message, "create_inventory.php - PDOException");

        if ($e->getCode() == '23000') { // SQLSTATE 23000: Integrity constraint violation
            $_SESSION['form_error'] = "Error: Could not add inventory item. The Product ID '" . htmlspecialchars($product_ID_posted) . "' might not exist in the products table, or this inventory entry already exists / violates another database rule.";
        } else {
            $_SESSION['form_error'] = "A database error occurred while adding the inventory item. Please check the system logs or contact support.";
        }
        // On PDO error, form_data is already in session, so just redirect
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUP Shop|Inventory Management - Add Inventory Item</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/inventory.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .user-profile { position: absolute; top: 10px; right: 10px; padding: 10px; color: white; text-align: center; z-index:1001; }
    .user-profile img { width: 50px; height: 50px; border-radius: 50%; display: block; margin: 0 auto 5px auto; }
    .user-profile h3 { margin: 0; color:aliceblue; font-size: 1.1em; }
    .back-button { position: absolute; top: 10px; left: 10px; z-index:1001;}
    .back-button img { width: 50px; height: 50px; }
    .title-bg{ background-color: rgba(238, 190, 78, 0.702); padding: 10px; display: inline-block; border-radius: 5px; margin-bottom:20px; }
    label.col-form-label { background-color: rgba(245, 200, 245, 0.8); padding: 8px; border-radius: 4px; }
    body { background-image: url("images/home.jpg"); background-repeat: no-repeat; background-attachment: fixed; background-size: cover; padding-top: 70px; /* Adjust if navbar is fixed */ }
    .container.my-5 { background-color: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.2); }
    .session-message { padding: 12px; margin-top:0; margin-bottom: 18px; border-radius: 5px; font-size: 0.95em; text-align: left; }
    .session-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .session-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; } /* For potential non-redirect success */
    </style>
</head>
<body>
    <div class="user-profile">
        <img src="images/user.png" alt="User Image"> <!-- Ensure path is correct -->
        <h3><?= htmlspecialchars($first_name_display) ?></h3>
    </div>
    <a href="inventory.php" class="back-button">
        <img src="images/replay.png" alt="Back Button"> <!-- Ensure path is correct -->
    </a>
    <div class="container my-5">
        <div>
            <h2 class="title-bg">Add New Inventory Item</h2>
            <?php if (!empty($form_error_message)): ?>
                <div class='session-message error alert alert-danger alert-dismissible fade show' role='alert'>
                    <strong>Errors:</strong><br><?= $form_error_message /* Contains <br> tags, so not double-escaped */ ?>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($form_success_message)): /* For success messages displayed on this page itself (if any) */ ?>
                 <div class='session-message success alert alert-success alert-dismissible fade show' role='alert'>
                    <strong><?= htmlspecialchars($form_success_message) ?></strong>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <div class="row mb-3 mt-3">
                <label for="product_ID" class="col-sm-3 col-form-label">Product ID</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="product_ID" name="product_ID" value="<?= htmlspecialchars($product_ID_form_value); ?>" required>
                </div>
            </div>
            <div class="row mb-3">
                <label for="quantity" class="col-sm-3 col-form-label">Quantity</label>
                <div class="col-sm-9">
                    <input type="number" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars($quantity_form_value); ?>" required min="1">
                </div>
            </div>
            <div class="row mb-3">
                <label for="shelf_no" class="col-sm-3 col-form-label">Shelf No.</label>
                <div class="col-sm-9">
                    <input type="text" class="form-control" id="shelf_no" name="shelf_no" value="<?= htmlspecialchars($shelf_no_form_value); ?>" required> <!-- Assuming shelf_no is required -->
                </div>
            </div>
            <div class="row mb-3">
                <label for="buy_date" class="col-sm-3 col-form-label">Buying Date</label>
                <div class="col-sm-9">
                    <input type="date" class="form-control" id="buy_date" name="buy_date" value="<?= htmlspecialchars($buy_date_form_value); ?>"> <!-- Assuming date can be optional, remove required if so -->
                </div>
            </div>

            <div class="row mb-3">
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-primary">Add to Inventory</button>
                </div>
                <div class="col-sm-3 d-grid">
                    <a class="btn btn-outline-secondary" href="inventory.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</body>
</html>
