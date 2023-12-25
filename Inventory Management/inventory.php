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


        $sql = "SELECT p.product_name, p.price, m.quantity, 
                    CASE WHEN m.quantity > 0 THEN 'stocked' ELSE 'stock out' END AS stock_status
                FROM product p
                LEFT JOIN manage m ON p.product_ID = m.product_ID";
        $result = $connection->query($sql);

        if (!$result) {
            die("Invalid query: " . $connection->error);
        }
        $productQuery = "SELECT * FROM product";
        $productResult = $connection->query($productQuery);
        $products = [];

        while ($row = $productResult->fetch_assoc()) {
            $products[] = $row;
        }
        $connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUP Shop|Inventory management</title>
    <link rel="stylesheet" type="text/css" href="css/inventory.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: linear-gradient(to right,#EB71EB,rgba(238, 190, 78));
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #DA9ADA;
        }
        .expired {
        color: red;
        font-weight: bold;
        }
        .quantity{
        background: linear-gradient(to left,purple,rgba(255, 179, 0, 0.702));
        background-repeat: no-repeat;
        background-attachment: fixed;
        }
        .jafrin{
            border: 4px solid;
            border-style: outset;
            border-color: rgba(255, 234, 185, 0.702);
            width: 100px;
            height: 35px;
            padding: 5px;
            color: black;
            font-size: 16px;
            text-align: center;
            background:rgba(255, 179, 0, 0.702)
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <h2 class="quantity">Product quantites</h2>
        <div class="jafrin">
            <a class="btn btn-primary" href="create_inventory.php" role="button">+Add quantity of product</a>
        </div>
        <div class='box'>

            <table>
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Stock Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        echo "
                        <tr>
                            <td>{$row['product_name']}</td>
                            <td>{$row['price']}</td>
                            <td>" . ($row['quantity'] !== null ? $row['quantity'] : 0) . "</td>
                            <td>{$row['stock_status']}</td>
                            <td>
                                <a class='btn btn-primary btn-sm' href='add_inventory.php?product_name=$row[product_name]'>add</a>
                            </td>
                        </tr>
                        ";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <br>
        <h2>Expiration Status</h2>
        <table>
        <thead>
            <tr>
                <th>Product Name</th>
                <th>Product ID</th>
                <th>Price</th>
                <th>Mfg Date</th>
                <th>Exp Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product) : ?>
                <tr>
                    <td><?= $product['product_name'] ?></td>
                    <td><?= $product['product_ID'] ?></td>
                    <td><?= $product['price'] ?></td>
                    <td><?= $product['mfg_date'] ?></td>
                    <td <?= (strtotime($product['exp_date']) < time()) ? 'class="expired"' : '' ?>>
                        <?= $product['exp_date'] ?>
                    </td>
                    <td <?= (strtotime($product['exp_date']) < time()) ? 'class="expired"' : '' ?>>
                        <?= (strtotime($product['exp_date']) < time()) ? 'Expired' : 'Not Expired' ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</body>
</html>