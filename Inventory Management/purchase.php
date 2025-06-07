<?php
    // Enable error reporting for development - consider removing or adjusting for production
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if (session_status() == PHP_SESSION_NONE) { // Ensure session is started
        session_start();
    }

    // connection.php now includes error_logger.php
    require_once __DIR__ . '/database/connection.php';

    // --- Authentication Check ---
    if (!isset($_SESSION['user_id'])) {
        log_error("Unauthorized access attempt to purchase.php: User not logged in.", "purchase.php - Auth Check");
        $_SESSION['login_error_message'] = "Please log in to access the purchase management page.";
        header('Location: login.php');
        exit();
    }

    // Retrieve user details from session
    $first_name_display = $_SESSION['first_name'] ?? 'User'; // Used in navbar.php
    $user_id_session = $_SESSION['user_id']; // For logging who performed action

    // --- Retrieve and Clear Session Messages ---
    $purchase_error_message = $_SESSION['purchase_error'] ?? '';
    if(isset($_SESSION['purchase_error'])) unset($_SESSION['purchase_error']);

    $purchase_success_message = $_SESSION['purchase_success'] ?? '';
    if(isset($_SESSION['purchase_success'])) unset($_SESSION['purchase_success']);

    // --- Handle 'Add Purchase' POST Request ---
    if (isset($_POST['add'])) {
        $product_id = trim($_POST['product_id'] ?? '');
        $quantity = trim($_POST['quantity'] ?? '');
        $supplier_name = trim($_POST['supplier_name'] ?? ''); // This should ideally be supplier_id from a select dropdown
        $created_at = date('Y-m-d H:i:s'); // Current timestamp for creation

        $validation_errors_add = [];
        if (empty($product_id)) $validation_errors_add[] = "Product ID is required.";
        // Add check if product_id exists in 'product' table if necessary

        if ($quantity === '') { // Check specifically for empty string, as '0' could be valid if allowed
            $validation_errors_add[] = "Quantity is required.";
        } elseif (!is_numeric($quantity) || $quantity <= 0) {
            $validation_errors_add[] = "Quantity must be a positive whole number.";
        }
        if (empty($supplier_name)) $validation_errors_add[] = "Supplier Name is required.";
        // Add check if supplier_name exists in 'supplier' table if necessary

        if (!empty($validation_errors_add)) {
            $_SESSION['purchase_error'] = implode("<br>", $validation_errors_add);
            $log_val_error_add = "Validation failed for Add Purchase: " . implode("; ", $validation_errors_add) . ". Submitted Data: " . print_r($_POST, true);
            log_error($log_val_error_add, "purchase.php - Add Purchase Validation Failure");
        } else {
            try {
                $sql_add = "INSERT INTO purchase (product_id, quantity, supplier_name, created_at, user_id) VALUES (:product_id, :quantity, :supplier_name, :created_at, :user_id)";
                $stmt_add = $conn->prepare($sql_add);
                $stmt_add->bindParam(':product_id', $product_id); // Assuming product_id in purchase table is FK to product.product_ID (often VARCHAR or INT)
                $stmt_add->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                $stmt_add->bindParam(':supplier_name', $supplier_name); // Assuming supplier_name in purchase table is FK to supplier.supplierName (often VARCHAR)
                $stmt_add->bindParam(':created_at', $created_at);
                $stmt_add->bindParam(':user_id', $user_id_session, PDO::PARAM_INT);

                if ($stmt_add->execute()) {
                    $_SESSION['purchase_success'] = "Purchase order for Product ID '" . htmlspecialchars($product_id) . "' added successfully.";
                    log_error("Purchase added: ProductID {$product_id}, Quantity {$quantity}, SupplierName {$supplier_name} by UserID {$user_id_session}", "purchase.php - Add Purchase Success");
                } else {
                    $errorInfo_add = $stmt_add->errorInfo();
                    $_SESSION['purchase_error'] = "Failed to add purchase order. Please check system logs.";
                    log_error("Failed to execute Add Purchase. ProductID {$product_id}. DB Error: " . ($errorInfo_add[2] ?? 'Unknown error'), "purchase.php - Add Purchase Execute Failure");
                }
            } catch (PDOException $e_add) {
                $_SESSION['purchase_error'] = "A database error occurred while adding the purchase. Please check system logs.";
                log_error("PDOException on Add Purchase: " . $e_add->getMessage() . ". Submitted Data: " . print_r($_POST, true), "purchase.php - Add Purchase PDOException");
            }
        }
        header('Location: purchase.php'); // Redirect to refresh page, clear POST, and show messages
        exit();
    }

    // --- Handle 'Remove Purchase' POST Request ---
    if (isset($_POST['remove'])) {
        $product_id_remove = trim($_POST['product_id'] ?? '');
        $supplier_name_remove = trim($_POST['supplier_name'] ?? '');

        $validation_errors_remove = [];
        if (empty($product_id_remove)) $validation_errors_remove[] = "Product ID is required for removal.";
        if (empty($supplier_name_remove)) $validation_errors_remove[] = "Supplier Name is required for removal.";

        if (!empty($validation_errors_remove)) {
             $_SESSION['purchase_error'] = implode("<br>", $validation_errors_remove);
             $log_val_error_remove = "Validation failed for Remove Purchase: " . implode("; ", $validation_errors_remove) . ". Submitted Data: " . print_r($_POST, true);
             log_error($log_val_error_remove, "purchase.php - Remove Purchase Validation Failure");
        } else {
            try {
                // Deleting based on product_id and supplier_name. If there are multiple, this deletes the most recent.
                // A purchase_id primary key would be better for specific deletion.
                // Assuming `id` is the primary key of the `purchase` table.
                $sql_delete = "DELETE FROM purchase WHERE product_id = :product_id AND supplier_name = :supplier_name ORDER BY id DESC LIMIT 1";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bindParam(':product_id', $product_id_remove);
                $stmt_delete->bindParam(':supplier_name', $supplier_name_remove);

                if ($stmt_delete->execute()) {
                    if ($stmt_delete->rowCount() > 0) {
                        $_SESSION['purchase_success'] = "One purchase order for Product ID '" . htmlspecialchars($product_id_remove) . "' from supplier '" . htmlspecialchars($supplier_name_remove) . "' was removed (most recent if multiple matched).";
                        log_error("Purchase removed: ProductID {$product_id_remove}, SupplierName {$supplier_name_remove} by UserID {$user_id_session}", "purchase.php - Remove Purchase Success");
                    } else {
                        $_SESSION['purchase_error'] = "No matching purchase order found to remove for Product ID '" . htmlspecialchars($product_id_remove) . "' from supplier '" . htmlspecialchars($supplier_name_remove) . "'.";
                        log_error("No purchase found to remove: ProductID {$product_id_remove}, SupplierName {$supplier_name_remove}. Action by UserID {$user_id_session}", "purchase.php - Remove Purchase Not Found");
                    }
                } else {
                     $errorInfo_delete = $stmt_delete->errorInfo();
                     $_SESSION['purchase_error'] = "Failed to remove purchase order. Please check system logs.";
                     log_error("Failed to execute Remove Purchase. ProductID {$product_id_remove}. DB Error: " . ($errorInfo_delete[2] ?? 'Unknown error'), "purchase.php - Remove Purchase Execute Failure");
                }
            } catch (PDOException $e_delete) {
                $_SESSION['purchase_error'] = "A database error occurred while removing the purchase. Please check system logs.";
                log_error("PDOException on Remove Purchase: " . $e_delete->getMessage() . ". Submitted Data: " . print_r($_POST, true), "purchase.php - Remove Purchase PDOException");
            }
        }
        header('Location: purchase.php'); // Redirect to refresh
        exit();
    }

    // --- Fetch Purchase Orders for Display ---
    $orders_list = []; // Renamed to avoid conflict with any single $order variable
    try {
        // Query to fetch purchase details along with product name and user who ordered.
        // Make sure table and column names (Users.user_ID, product.product_ID, supplier.supplierName) match your schema.
        $sql_fetch_orders = "SELECT
                                p.product_name,
                                pr.quantity,
                                s.supplierName,
                                pr.created_at,
                                u.first_name AS ordered_by_first_name,
                                u.last_name AS ordered_by_last_name,
                                p.product_ID AS product_id_display_from_table
                            FROM purchase pr
                            INNER JOIN product p ON pr.product_id = p.product_ID
                            INNER JOIN supplier s ON pr.supplier_name = s.supplierName
                            INNER JOIN Users u ON pr.user_id = u.user_ID
                            ORDER BY pr.id DESC"; // Assuming 'id' is PK of 'purchase'
        $stmt_fetch_orders = $conn->prepare($sql_fetch_orders);
        $stmt_fetch_orders->execute();
        $orders_list = $stmt_fetch_orders->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_fetch) {
        // This error will be displayed on the page if it happens during initial load.
        // If it's a POST request, the redirect would have already happened.
        $purchase_error_message = "Error fetching purchase orders. Please check system logs or contact support.";
        log_error("PDOException on Fetching Purchase List: " . $e_fetch->getMessage(), "purchase.php - Fetch Purchase List PDOException");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Purchase Orders - Inventory System</title>
    <link rel="stylesheet" type="text/css" href="css/purchase.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <style> /* Basic style for session messages and page layout */
        body { font-family: Arial, sans-serif; margin: 0; padding-top: 70px; /* Adjust if navbar is fixed and has height */ background-color: #f9f9f9; }
        .session-message { padding: 12px; margin: 15px auto; border-radius: 5px; text-align: center; width: 90%; max-width: 800px; box-sizing: border-box; }
        .session-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .session-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .form-container, .purchase-list { background-color: #fff; padding: 20px; margin: 20px auto; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 90%; max-width: 800px; }
        h2 { color: #333; margin-bottom: 15px; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"], .form-group input[type="number"] { width: calc(100% - 22px); padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .form-buttons { display: flex; justify-content: space-between; margin-top:15px;}
        .form-buttons button { padding: 10px 15px; border-radius: 4px; border: none; cursor: pointer; }
        .add-button { background-color: #28a745; color: white; }
        .remove-button { background-color: #dc3545; color: white; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        .font-color { /* Applied to tbody in original, assuming it means default text color */ color: #212529; }
    </style>
</head>
<body>
<?php include 'navbar.php'; // $first_name_display is used in navbar.php ?>

    <!-- Display Session Messages -->
    <?php if(!empty($purchase_error_message)): ?>
        <div class="session-message error">
            <p><?= nl2br(htmlspecialchars($purchase_error_message, ENT_QUOTES, 'UTF-8')) ?></p>
        </div>
    <?php endif; ?>
    <?php if(!empty($purchase_success_message)): ?>
        <div class="session-message success">
            <p><?= htmlspecialchars($purchase_success_message, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form action="purchase.php" method="post">
            <h2>New Purchase Order</h2>
            <div class="form-group">
                <label for="product_id">Product ID:</label>
                <input type="text" id="product_id" name="product_id" required value="<?= htmlspecialchars($_POST['product_id'] ?? '', ENT_QUOTES, 'UTF-8') /* For repopulation if form had error, though current logic redirects */ ?>">
            </div>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" required min="1" value="<?= htmlspecialchars($_POST['quantity'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label for="supplier_name">Supplier Name (Must match an existing supplier's name):</label>
                <input type="text" id="supplier_name" name="supplier_name" required value="<?= htmlspecialchars($_POST['supplier_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-buttons">
                <button type="submit" name="add" class="add-button">Add Purchase</button>
                <button type="submit" name="remove" class="remove-button" onclick="return confirm('This will attempt to remove the MOST RECENT purchase matching the Product ID and Supplier Name. Are you sure?');">Remove Last Matching Purchase</button>
            </div>
        </form>
    </div>

    <div class="purchase-list">
        <h2>List of Purchase Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Supplier Name</th>
                    <th>Ordered By</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody class="font-color">
                <?php if (!empty($orders_list)): ?>
                    <?php foreach ($orders_list as $order_item): // Renamed $order to $order_item ?>
                    <tr>
                        <td><?= htmlspecialchars($order_item['product_id_display_from_table'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order_item['product_name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order_item['quantity'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order_item['supplierName'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(($order_item['ordered_by_first_name'] ?? '') . ' ' . ($order_item['ordered_by_last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($order_item['created_at'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No purchase orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
