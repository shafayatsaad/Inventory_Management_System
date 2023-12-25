<?php
session_start(); // Start the session
$servername = "localhost";
$username = "root";
$password = "";
$database = "inventory";

$connection = new mysqli($servername, $username, $password, $database);

// Check if the first name is set in the session
if (!isset($_SESSION['first_name'])) {
}

// Access the first name from the session
$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : '';

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$product_name = "";
$quantity = "";
$shelf_no = "";
$buy_date = "";

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET["product_name"])) {
        header("location: inventory.php");
        exit;
    }
    $product_name = $_GET["product_name"];

    $sql = "SELECT p.product_name, m.quantity, m.shelf_no, m.buy_date 
            FROM product p
            LEFT JOIN manage m ON p.product_ID = m.product_ID
            WHERE p.product_name = ?";
    $stmt = $connection->prepare($sql);
    $stmt->bind_param("s", $product_name);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($product_name, $quantity, $shelf_no, $buy_date);
        $stmt->fetch();
    } else {
        header("location: inventory.php");
        exit;
    }
} else {
    $product_name = $_POST["product_name"];
    $quantity = $_POST["quantity"];
    $shelf_no = $_POST["shelf_no"];
    $buy_date = $_POST["buy_date"];

    $stmt = $connection->prepare("UPDATE manage 
                                  SET quantity=?, shelf_no=?, buy_date=? 
                                  WHERE product_ID = (SELECT product_ID FROM product WHERE product_name = ?)");
    $stmt->bind_param("isss", $quantity, $shelf_no, $buy_date, $product_name);

    $stmt->execute();

    if ($stmt->error) {
        die("Invalid query: " . $stmt->error);
    }

    header("location: inventory.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" type="text/css" href="css/add_inventory.css">
</head>
<body>
    <div class="user-profile">
        <img src="images/user.png" alt="User Image">
        <h3><?= $first_name ?></h3>
    </div>
    <a href="inventory.php" class="back-button">
        <img src="images/replay.png" alt="Back Button">
    </a>
    <div class="edit">
        <h2>Edit Product: <?= $product_name ?></h2>
        <form method="post">
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" name="quantity" id="quantity" value="<?= $quantity ?>" required>
            </div>
            <div class="form-group">
                <label for="shelf_no">Shelf No.:</label>
                <input type="text" name="shelf_no" id="shelf_no" value="<?= $shelf_no ?>" required>
            </div>
            <div class="form-group">
                <label for="buy_date">Buying Date:</label>
                <input type="date" name="buy_date" id="buy_date" value="<?= $buy_date ?>" required> 
            </div>
            <input type="hidden" name="product_name" value="<?= $product_name ?>">
            <button type="submit">Update</button>
        </form>
    </div>
</body>
</html>
