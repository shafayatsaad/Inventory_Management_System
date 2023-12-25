<?php
    // Enable error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    //starting session.
    session_start();

    // Check if the first name is set in the session
    if (!isset($_SESSION['first_name'])) {
        // Redirect the user to the login page
        header('Location: login.php');
        exit();
    }

    include 'database/connection.php';

    // Access the first name from the session
    $first_name = $_SESSION['first_name'];

    // Now you can use the $first_name variable in your page
    //echo "" . $first_name;

    // Add order
    if(isset($_POST['add'])){
        $product_id = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        $supplier_name = $_POST['supplier_name'];
        $created_at = date('Y-m-d H:i:s'); // Get the current date and time
        $user_id = $_SESSION['user_id']; // Get the user_id from the session
    
        $sql = "INSERT INTO purchase (product_id, quantity, supplier_name, created_at, user_id) VALUES (:product_id, :quantity, :supplier_name, :created_at, :user_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':supplier_name', $supplier_name);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':user_id', $user_id); // Bind the user_id parameter
        $stmt->execute();
    
        // Redirect to a new page after the form is submitted
        header('Location: purchase.php');
        exit();
    }

    // Remove order
    if(isset($_POST['remove'])){
        $product_id = $_POST['product_id'];
        $supplier_name = $_POST['supplier_name'];

        $sql = "DELETE FROM purchase WHERE product_id = :product_id AND supplier_name = :supplier_name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':supplier_name', $supplier_name);
        $stmt->execute();

        // Redirect to a new page after the form is submitted
        header('Location: purchase.php');
        exit();
    }
        // Fetch orders
        $sql = "SELECT product.product_name, purchase.quantity, supplier.supplierName, purchase.created_at, users.first_name as ordered_by, purchase.status, product.product_id, purchase.user_id as order_id
        FROM purchase
        INNER JOIN product ON purchase.product_id = product.product_ID
        INNER JOIN supplier ON purchase.supplier_name = supplier.supplierName
        INNER JOIN users ON purchase.user_id = users.id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" type="text/css" href="css/purchase.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <!-- New Purchase Order Form -->
    <div class="form-container">
        <form action="purchase.php" method="post">
            <h2>Purchase Order Form</h2>
            <div class="form-group">
                <label for="product_id">Product ID:</label>
                <input type="text" id="product_id" name="product_id" required>
            </div>
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" required>
            </div>
            <div class="form-group">
                <label for="supplier_name">Supplier Name:</label>
                <input type="text" id="supplier_name" name="supplier_name" required>
            </div>
            <button type="submit" name="add" class="add-button">Add</button>
            <button type="submit" name="remove" class="remove-button">Remove</button>
        </form>
    </div>

    <!-- List of Purchase Orders -->
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
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['product_id'] ?></td>
                    <td><?= $order['product_name'] ?></td>
                    <td><?= $order['quantity'] ?></td>
                    <td><?= $order['supplierName'] ?></td>
                    <td><?= $order['ordered_by'] ?></td>
                    <td><?= $order['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="scripts.js"></script>
</body>
</html>
