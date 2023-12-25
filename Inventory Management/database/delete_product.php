<?php
// This is delete.php file
if (isset($_GET["product_ID"])) {
    $product_ID = $_GET["product_ID"];

    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "inventory";

    $connection = new mysqli($servername, $username, $password, $database);

    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    $stmt = $connection->prepare("DELETE FROM product WHERE product_ID = ?");
    $stmt->bind_param("i", $product_ID);

    $stmt->execute();

    $stmt->close();
    $connection->close();
}

header("location: index.php");
exit;
?>