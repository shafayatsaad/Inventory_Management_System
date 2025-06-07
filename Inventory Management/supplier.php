<?php
    if (session_status() == PHP_SESSION_NONE) { // Ensure session is started at the very top
        session_start();
    }

    // connection.php now includes error_logger.php
    require_once __DIR__ . '/database/connection.php';

    // --- Authentication Check ---
    // Ensure user is logged in. For roles, you might check $_SESSION['user_type'] === 'admin' etc.
    if (!isset($_SESSION['user_id'])) {
        log_error("Unauthorized access attempt to supplier.php: User not logged in.", "supplier.php - Auth Check");
        $_SESSION['login_error_message'] = "Please log in to manage suppliers.";
        header('Location: login.php');
        exit();
    }
    // Retrieve user details from session for logging or display purposes
    $logged_in_user_first_name = $_SESSION['first_name'] ?? 'User'; // Used in navbar.php
    $logged_in_user_id = $_SESSION['user_id'];

    // --- Retrieve and Clear Session Messages ---
    $supplier_error_message = $_SESSION['supplier_error'] ?? '';
    if(isset($_SESSION['supplier_error'])) unset($_SESSION['supplier_error']);

    $supplier_success_message = $_SESSION['supplier_success'] ?? '';
    if(isset($_SESSION['supplier_success'])) unset($_SESSION['supplier_success']);

    // --- Handle Add Supplier Form Submission ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addSupplier'])) {
        $supplierID = trim($_POST['supplierID'] ?? '');
        $supplierName = trim($_POST['supplierName'] ?? '');
        $supplierEmail = trim($_POST['supplierEmail'] ?? '');
        $supplierPhone = trim($_POST['supplierPhone'] ?? '');
        $supplierLocation = trim($_POST['supplierLocation'] ?? '');
        $productID = trim($_POST['productID'] ?? ''); // Foreign key to product table

        $validation_errors_add = [];
        if (empty($supplierID)) {
            $validation_errors_add[] = "Supplier ID is required.";
        } elseif (!is_numeric($supplierID) || $supplierID <= 0) { // Assuming supplierID is a positive integer
            $validation_errors_add[] = "Supplier ID must be a positive number.";
        }
        if (empty($supplierName)) $validation_errors_add[] = "Supplier Name is required.";
        if (empty($supplierEmail)) {
            $validation_errors_add[] = "Supplier Email is required.";
        } elseif (!filter_var($supplierEmail, FILTER_VALIDATE_EMAIL)) {
            $validation_errors_add[] = "Invalid Supplier Email format.";
        }
        if (empty($supplierPhone)) $validation_errors_add[] = "Supplier Phone is required."; // Add more specific phone validation if needed
        if (empty($supplierLocation)) $validation_errors_add[] = "Supplier Location is required.";
        if (empty($productID)) {
            $validation_errors_add[] = "Associated Product ID is required.";
        } elseif (!is_numeric($productID) || $productID <= 0) { // Assuming productID is a positive integer FK
            $validation_errors_add[] = "Associated Product ID must be a positive number.";
        }


        if (!empty($validation_errors_add)) {
            $_SESSION['supplier_error'] = implode("<br>", $validation_errors_add);
            $log_val_error_add = "Validation failed for Add Supplier: " . implode("; ", $validation_errors_add) . ". Submitted Data: " . print_r($_POST, true);
            log_error($log_val_error_add, "supplier.php - Add Supplier Validation Failure");
        } else {
            try {
                // Check if supplierID or supplierEmail already exists to prevent duplicates
                $checkSql = "SELECT supplierID FROM supplier WHERE supplierID = :supplierID OR supplierEmail = :supplierEmail";
                $checkStmt = $conn->prepare($checkSql);
                $checkStmt->execute([':supplierID' => $supplierID, ':supplierEmail' => $supplierEmail]);
                if ($checkStmt->fetch()) {
                    $_SESSION['supplier_error'] = "Error: A supplier with this ID or Email already exists.";
                    log_error("Attempt to add duplicate supplier. ID: {$supplierID} or Email: {$supplierEmail}", "supplier.php - Add Duplicate Check");
                } else {
                    $sql_add = "INSERT INTO supplier (supplierID, supplierName, supplierEmail, supplierPhone, supplierLocation, productID)
                            VALUES (:supplierID, :supplierName, :supplierEmail, :supplierPhone, :supplierLocation, :productID)";
                    $stmt_add = $conn->prepare($sql_add);
                    // Bind parameters (assuming supplierID and productID are integers)
                    $stmt_add->bindParam(':supplierID', $supplierID, PDO::PARAM_INT);
                    $stmt_add->bindParam(':supplierName', $supplierName);
                    $stmt_add->bindParam(':supplierEmail', $supplierEmail);
                    $stmt_add->bindParam(':supplierPhone', $supplierPhone);
                    $stmt_add->bindParam(':supplierLocation', $supplierLocation);
                    $stmt_add->bindParam(':productID', $productID, PDO::PARAM_INT);

                    if ($stmt_add->execute()) {
                        $_SESSION['supplier_success'] = "Supplier '" . htmlspecialchars($supplierName) . "' (ID: " . htmlspecialchars($supplierID) . ") added successfully.";
                        log_error("Supplier added: ID {$supplierID}, Name {$supplierName}, ProductID {$productID} by UserID {$logged_in_user_id}", "supplier.php - Add Supplier Success");
                    } else {
                        $errorInfo_add = $stmt_add->errorInfo();
                        $_SESSION['supplier_error'] = "Failed to add supplier. Please check system logs. Possible issue: Product ID may not exist.";
                        log_error("Failed to execute Add Supplier. ID {$supplierID}. DB Error: " . ($errorInfo_add[2] ?? 'Unknown error'), "supplier.php - Add Supplier Execute Failure");
                    }
                }
            } catch (PDOException $e_add) {
                // Catch specific integrity constraint violations (like non-existent productID)
                if ($e_add->getCode() == '23000') {
                     $_SESSION['supplier_error'] = "Error adding supplier: The specified Product ID ('" . htmlspecialchars($productID) . "') may not exist, or Supplier ID ('" . htmlspecialchars($supplierID) . "') already taken.";
                } else {
                    $_SESSION['supplier_error'] = "A database error occurred while adding the supplier. Please check system logs.";
                }
                log_error("PDOException on Add Supplier: " . $e_add->getMessage() . ". Submitted Data: " . print_r($_POST, true), "supplier.php - Add Supplier PDOException");
            }
        }
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF'])); // Redirect to refresh and show messages
        exit;
    }

    // --- Handle Remove Selected Suppliers ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removeSelected']) && !empty($_POST['selectedIDs'])) {
        $selectedIDs = $_POST['selectedIDs']; // Array of supplierIDs to delete
        $deleted_count = 0;
        $error_messages_delete = [];
        $placeholder_errors = 0; // Count how many selected IDs were invalid

        if (!is_array($selectedIDs)) {
            $_SESSION['supplier_error'] = "Invalid selection for removal.";
            log_error("Invalid data type for selectedIDs in Remove Selected. UserID {$logged_in_user_id}", "supplier.php - Remove Invalid Selection");
        } else {
            try {
                $sql_delete = "DELETE FROM supplier WHERE supplierID = :supplierID";
                $stmt_delete = $conn->prepare($sql_delete);

                foreach ($selectedIDs as $id_to_delete) {
                    $id_to_delete = trim($id_to_delete);
                    if (!is_numeric($id_to_delete) || $id_to_delete <= 0) {
                        $placeholder_errors++; // Count invalid IDs
                        log_error("Invalid supplier ID '{$id_to_delete}' in selection for removal. UserID {$logged_in_user_id}", "supplier.php - Remove Invalid ID in Batch");
                        continue; // Skip this invalid ID
                    }

                    $stmt_delete->bindParam(':supplierID', $id_to_delete, PDO::PARAM_INT);
                    if ($stmt_delete->execute()) {
                        if ($stmt_delete->rowCount() > 0) {
                            $deleted_count++;
                        }
                    } else {
                        $errorInfo_delete_item = $stmt_delete->errorInfo();
                        $error_messages_delete[] = "Could not delete supplier ID " . htmlspecialchars($id_to_delete) . ".";
                        log_error("Error deleting supplier ID {$id_to_delete}. DB Error: " . ($errorInfo_delete_item[2] ?? 'Unknown error') . ". UserID {$logged_in_user_id}", "supplier.php - Delete Item Failure in Batch");
                    }
                }

                if ($deleted_count > 0) {
                    $_SESSION['supplier_success'] = $deleted_count . " supplier(s) removed successfully.";
                    log_error("{$deleted_count} supplier(s) removed by UserID {$logged_in_user_id}. IDs: " . implode(", ", array_map('htmlspecialchars', $selectedIDs)), "supplier.php - Delete Batch Success");
                }
                if (!empty($error_messages_delete)) {
                     $_SESSION['supplier_error'] = ($_SESSION['supplier_error'] ?? '') . ($deleted_count > 0 ? "<br>" : "") . "Some suppliers could not be deleted: " . implode("<br>", $error_messages_delete);
                }
                if ($placeholder_errors > 0) {
                    $_SESSION['supplier_error'] = ($_SESSION['supplier_error'] ?? '') . ($deleted_count > 0 || !empty($error_messages_delete) ? "<br>" : "") . $placeholder_errors . " invalid ID(s) skipped during removal.";
                }
                if ($deleted_count === 0 && empty($error_messages_delete) && $placeholder_errors === 0 && !empty($selectedIDs)) {
                     $_SESSION['supplier_error'] = "No suppliers found matching the selected IDs for removal, or they were already deleted.";
                }


            } catch (PDOException $e_delete_batch) {
                $_SESSION['supplier_error'] = "A database error occurred while removing suppliers. Please check system logs.";
                log_error("PDOException on Remove Selected Suppliers: " . $e_delete_batch->getMessage() . ". Selected IDs: " . implode(", ", array_map('htmlspecialchars', $selectedIDs)) . ". UserID {$logged_in_user_id}", "supplier.php - Delete Batch PDOException");
            }
        }
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removeSelected']) && empty($_POST['selectedIDs'])) {
        $_SESSION['supplier_error'] = "No suppliers were selected for removal.";
        log_error("Remove Selected clicked but no IDs provided. UserID {$logged_in_user_id}", "supplier.php - Remove No Selection");
        header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
        exit;
    }

    // --- Fetch All Suppliers for Display ---
    $suppliers_list = []; // Renamed to avoid conflicts
    try {
        $sql_fetch_all = "SELECT supplierID, supplierName, supplierEmail, supplierPhone, supplierLocation, productID FROM supplier ORDER BY supplierName ASC";
        $stmt_fetch_all = $conn->prepare($sql_fetch_all);
        $stmt_fetch_all->execute();
        $suppliers_list = $stmt_fetch_all->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e_fetch_all) {
        // This error message will be displayed on the page if fetching fails
        $supplier_error_message = "Error fetching supplier list. Please check system logs or contact support.";
        log_error("PDOException on Fetching Supplier List: " . $e_fetch_all->getMessage(), "supplier.php - Fetch Supplier List PDOException");
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - Inventory System</title>
    <link rel="stylesheet" href="css/supplier.css"> <!-- Ensure this path is correct -->
    <link rel="stylesheet" type="text/css" href="css/navbar.css"> <!-- Ensure this path is correct -->
    <style> /* Basic styling for session messages and page layout */
        body { font-family: Arial, sans-serif; margin: 0; padding-top: 70px; background-color: #f9f9f9; }
        .container { width: 90%; max-width: 1200px; margin: 20px auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        header h1 { text-align: center; color: #333; margin-bottom: 20px; }
        .session-message { padding: 12px; margin-bottom: 18px; border-radius: 5px; font-size: 0.95em; text-align: left; }
        .session-message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .session-message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        form h2 { margin-top: 20px; margin-bottom: 10px; color: #007bff; }
        label { display: block; margin-top: 10px; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="tel"], input[type="number"] {
            width: calc(100% - 22px); padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px;
        }
        button[type="submit"], button[type="reset"] { padding: 10px 15px; margin-top: 10px; margin-right: 5px; border-radius: 4px; border: none; cursor: pointer; }
        .add-btn { background-color: #28a745; color: white; }
        .reset-btn { background-color: #ffc107; color: black; }
        .remove-btn { background-color: #dc3545; color: white; margin-top: 15px; }
        #supplier-list h2 { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e9ecef; }
        td input[type="checkbox"] { margin-right: 5px; }
    </style>
</head>
<body>
<?php include 'navbar.php'; // $first_name in navbar will be $logged_in_user_first_name ?>
    <div class="container">
        <header>
            <h1>Supplier Registry</h1>
        </header>

        <!-- Display Session Messages -->
        <?php if(!empty($supplier_error_message)): ?>
            <div class="session-message error">
                <p><?= nl2br(htmlspecialchars($supplier_error_message, ENT_QUOTES, 'UTF-8')) /* Allow <br> from implode */ ?></p>
            </div>
        <?php endif; ?>
        <?php if(!empty($supplier_success_message)): ?>
            <div class="session-message success">
                <p><?= htmlspecialchars($supplier_success_message, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="post">
            <h2>Add New Supplier</h2>
            <label for="supplierID">Supplier ID:</label>
            <input type="number" id="supplierID" name="supplierID" required value="<?= htmlspecialchars($_POST['supplierID'] ?? '', ENT_QUOTES, 'UTF-8') /* Repopulate for sticky form, though current logic redirects often */ ?>">

            <label for="supplierName">Name:</label>
            <input type="text" id="supplierName" name="supplierName" required value="<?= htmlspecialchars($_POST['supplierName'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="supplierEmail">Email:</label>
            <input type="email" id="supplierEmail" name="supplierEmail" required value="<?= htmlspecialchars($_POST['supplierEmail'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="supplierPhone">Phone:</label>
            <input type="tel" id="supplierPhone" name="supplierPhone" required value="<?= htmlspecialchars($_POST['supplierPhone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="supplierLocation">Location:</label>
            <input type="text" id="supplierLocation" name="supplierLocation" required value="<?= htmlspecialchars($_POST['supplierLocation'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label for="productID">Associated Product ID (Must exist in Products table):</label>
            <input type="number" id="productID" name="productID" required value="<?= htmlspecialchars($_POST['productID'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit" name="addSupplier" class="add-btn">Add Supplier</button>
            <button type="reset" class="reset-btn">Reset Form</button>
        </form>

        <hr style="margin-top: 30px; margin-bottom: 30px;">

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') ?>" method="post">
            <div id="supplier-list">
                <h2>Current Supplier List</h2>
                <?php if (!empty($suppliers_list)): ?>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllCheckboxes" title="Select/Deselect All"></th>
                            <th>Supplier ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Product ID</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <?php foreach($suppliers_list as $supplier_row): // Renamed $row to $supplier_row ?>
                            <tr>
                                <td><input type="checkbox" name="selectedIDs[]" value="<?= htmlspecialchars($supplier_row['supplierID'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td><?= htmlspecialchars($supplier_row['supplierID'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($supplier_row['supplierName'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($supplier_row['supplierEmail'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($supplier_row['supplierPhone'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($supplier_row['supplierLocation'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($supplier_row['productID'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="removeSelected" class="remove-btn" onclick="return confirm('Are you sure you want to remove the selected suppliers? This action cannot be undone.');">Remove Selected Suppliers</button>
                <?php else: ?>
                    <p>No suppliers found in the database. Use the form above to add new suppliers.</p>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <script>
        // JavaScript for "Select All" checkbox functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('selectAllCheckboxes');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function(event) {
                    const checkboxes = document.querySelectorAll('#supplierTableBody input[type="checkbox"][name="selectedIDs[]"]');
                    checkboxes.forEach(function(checkbox) {
                        checkbox.checked = event.target.checked;
                    });
                });
            }
        });
    </script>
</body>
</html>
