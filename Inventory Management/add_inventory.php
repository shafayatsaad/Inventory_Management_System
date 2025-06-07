<?php
session_start();
include_once __DIR__ . '/database/connection.php'; // This now includes error_logger.php

$userErrorMessage = ""; // Variable to hold user-friendly error messages
$userSuccessMessage = ""; // Variable to hold user-friendly success messages (e.g. from session)

// Access the first name from the session for display
$first_name_display = isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'Guest';

// Initialize form variables
$product_name_form = ""; // Holds the product name for the form (especially on GET)
$quantity_form = "";
$shelf_no_form = "";
$buy_date_form = "";

// Check for messages from session (e.g., after redirect)
if (isset($_SESSION['user_error_message'])) {
    $userErrorMessage = $_SESSION['user_error_message'];
    unset($_SESSION['user_error_message']);
}
if (isset($_SESSION['user_success_message'])) {
    $userSuccessMessage = $_SESSION['user_success_message'];
    unset($_SESSION['user_success_message']);
}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET["product_name"])) {
        // If product_name is not in GET, it's an invalid request for this page context
        $_SESSION['user_error_message'] = "No product specified for editing.";
        header("location: inventory.php");
        exit;
    }
    $product_name_get = $_GET["product_name"];
    $product_name_form = $product_name_get; // Set for display in h2 and hidden field

    try {
        $sql = "SELECT p.product_name, m.quantity, m.shelf_no, m.buy_date
                FROM product p
                LEFT JOIN manage m ON p.product_ID = m.product_ID
                WHERE p.product_name = :product_name";
        $stmt = $conn->prepare($sql);
        // bindParam is fine, or execute with array: $stmt->execute([':product_name' => $product_name_get]);
        $stmt->bindParam(':product_name', $product_name_get);
        $stmt->execute();

        // Use fetch instead of rowCount to check if a row exists and get data
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Product found, populate form variables
            // $product_name_form is already set from GET param
            $quantity_form = $row["quantity"];
            $shelf_no_form = $row["shelf_no"];
            $buy_date_form = $row["buy_date"];
        } else {
            // Product not found in 'manage' or 'product' table with this name
            log_error("Product not found on GET: " . htmlspecialchars($product_name_get), "add_inventory.php - GET");
            $_SESSION['user_error_message'] = "Product details not found for '" . htmlspecialchars($product_name_get) . "'. It might not exist or have inventory data.";
            header("location: inventory.php");
            exit;
        }
    } catch (PDOException $e) {
        log_error("PDOException in GET: " . $e->getMessage(), "add_inventory.php - GET");
        $userErrorMessage = "An error occurred while fetching product details. Please try again or contact support.";
        // We are on the edit page, so we display the error here rather than redirecting immediately
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from POST
    $product_name_post = $_POST["product_name"]; // This is the key to identify the product
    $quantity_post = $_POST["quantity"];
    $shelf_no_post = $_POST["shelf_no"];
    $buy_date_post = $_POST["buy_date"];

    // Repopulate form variables in case of error and redisplay
    $product_name_form = $product_name_post;
    $quantity_form = $quantity_post;
    $shelf_no_form = $shelf_no_post;
    $buy_date_form = $buy_date_post;

    // Basic Validation
    if (empty($product_name_post) || $quantity_post === '' || empty($shelf_no_post) || empty($buy_date_post) ) {
        $userErrorMessage = "All fields are required. Quantity can be 0, but not empty.";
    } elseif (!is_numeric($quantity_post)) {
        $userErrorMessage = "Quantity must be a number.";
    } else {
        try {
            // Check if product exists and has an entry in manage table to decide between UPDATE and INSERT
            $checkSql = "SELECT m.product_ID
                         FROM manage m
                         JOIN product p ON m.product_ID = p.product_ID
                         WHERE p.product_name = :product_name_check";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':product_name_check' => $product_name_post]);
            $existingEntry = $checkStmt->fetch();

            if ($existingEntry) {
                 $sql = "UPDATE manage
                         SET quantity=:quantity, shelf_no=:shelf_no, buy_date=:buy_date
                         WHERE product_ID = (SELECT product_ID FROM product WHERE product_name = :product_name_key)";
            } else {
                // If no entry in 'manage' table, insert a new one.
                // This assumes product_name is unique in 'product' table and product_ID can be reliably fetched.
                $sql = "INSERT INTO manage (product_ID, quantity, shelf_no, buy_date)
                        VALUES ((SELECT product_ID FROM product WHERE product_name = :product_name_key), :quantity, :shelf_no, :buy_date)";
            }

            $stmt = $conn->prepare($sql);

            // Bind parameters
            $stmt->bindParam(':quantity', $quantity_post, PDO::PARAM_INT);
            $stmt->bindParam(':shelf_no', $shelf_no_post);
            $stmt->bindParam(':buy_date', $buy_date_post);
            $stmt->bindParam(':product_name_key', $product_name_post);

            $stmt->execute();

            $_SESSION['user_success_message'] = "Inventory for '" . htmlspecialchars($product_name_post) . "' updated successfully.";
            header("location: inventory.php");
            exit;

        } catch (PDOException $e) {
            log_error("PDOException in POST: " . $e->getMessage() . " for product: " . htmlspecialchars($product_name_post), "add_inventory.php - POST");
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 $userErrorMessage = "Error: Could not update inventory. The product ID for '" . htmlspecialchars($product_name_post) . "' might not exist in the products table, or another database constraint was violated.";
            } else {
                $userErrorMessage = "An error occurred while updating the inventory. Please check the details and try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory for <?= htmlspecialchars($product_name_form) // Display product name in title ?></title>
    <link rel="stylesheet" type="text/css" href="css/add_inventory.css">
    <style>
        /* Basic styling for error/success messages */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-weight: bold; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>
    <div class="user-profile">
        <img src="images/user.png" alt="User Image"> <!-- Ensure images/user.png exists -->
        <h3><?= $first_name_display ?></h3>
    </div>
    <a href="inventory.php" class="back-button">
        <img src="images/replay.png" alt="Back Button"> <!-- Ensure images/replay.png exists -->
    </a>
    <div class="edit">
        <h2>Edit Inventory for: <?= htmlspecialchars($product_name_form) ?></h2>

        <?php if (!empty($userErrorMessage)): ?>
            <div class="message error"><?= $userErrorMessage ?></div>
        <?php endif; ?>
        <?php if (!empty($userSuccessMessage)): ?> <!-- Display session success message if any -->
            <div class="message success"><?= $userSuccessMessage ?></div>
        <?php endif; ?>

        <form method="post" action="add_inventory.php"> <!-- Action points to self -->
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="<?= htmlspecialchars($quantity_form ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="shelf_no">Shelf No.:</label>
                <input type="text" name="shelf_no" id="shelf_no" value="<?= htmlspecialchars($shelf_no_form ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="buy_date">Buying Date:</label>
                <input type="date" name="buy_date" id="buy_date" value="<?= htmlspecialchars($buy_date_form ?? '') ?>" required>
            </div>
            <!-- Hidden field to pass product_name, as it's the key for update and not directly editable here -->
            <input type="hidden" name="product_name" value="<?= htmlspecialchars($product_name_form) ?>">
            <button type="submit">Update Inventory</button>
        </form>
    </div>
</body>
</html>
