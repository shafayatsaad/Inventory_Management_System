<?php
session_start();
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/error_logger.php';

$product_name = "";
$product_ID = "";
$price = "";
$mfg_date = "";
$exp_date = "";
$catagory = "";

if (!isset($_SESSION['first_name'])) {
    header('location: login.php');
    exit();
}

$first_name = $_SESSION['first_name'];

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!isset($_GET["product_ID"])) {
        redirect_with_error('index_product.php', 'Product ID not specified.');
    }
    $product_ID = $_GET["product_ID"];

    try {
        $stmt = $conn->prepare("SELECT * FROM product WHERE product_ID = :product_ID");
        $stmt->bindParam(':product_ID', $product_ID, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            redirect_with_error('index_product.php', 'Product not found.');
        }

        $product_name = $row["product_name"];
        $price = $row["price"];
        $mfg_date = $row["mfg_date"];
        $exp_date = $row["exp_date"];
        $catagory = $row["catagory"];
    } catch (PDOException $e) {
        log_error("Error fetching product data: " . $e->getMessage(), "edit_product.php");
        $errorMessage = "Error fetching product data.";
    }
} else {
    $product_name = $_POST["product_name"];
    $product_ID = $_POST["product_ID"];
    $price = $_POST["price"];
    $mfg_date = $_POST["mfg_date"];
    $exp_date = $_POST["exp_date"];
    $catagory = $_POST["catagory"];

    if (empty($product_name) || empty($product_ID) || empty($price) || empty($mfg_date) || empty($exp_date) || empty($catagory)) {
        $errorMessage = "All the fields are required";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE product SET product_name=:product_name, price=:price, mfg_date=:mfg_date, exp_date=:exp_date, catagory=:catagory WHERE product_ID=:product_ID");
            $stmt->bindParam(':product_name', $product_name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':mfg_date', $mfg_date);
            $stmt->bindParam(':exp_date', $exp_date);
            $stmt->bindParam(':catagory', $catagory);
            $stmt->bindParam(':product_ID', $product_ID, PDO::PARAM_INT);
            $stmt->execute();

            $_SESSION['success_message'] = "Product updated correctly";
            header("location: index_product.php");
            exit;
        } catch (PDOException $e) {
            log_error("Error updating product: " . $e->getMessage(), "edit_product.php");
            $errorMessage = "Error updating product.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUP Shop|Inventory Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body{
            background-image: url("../images/home.jpg");
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
        }
        h2 {
            background-color: rgba(238, 190, 78, 0.702);
        }
        label{
            background-color: rgba(245, 200, 245);
        }

        .my-5 {
            padding-left: 400px;
            padding-right: 60px;

        }
        h2 {
            margin-left: -5px;
            width: 73%;
        }
        .user-profile {
          position: absolute;
          top: 0;
          right: 0;
          padding: 10px;
          color: white;
        }
        .user-profile img {
          width: 50px; /* Adjust as needed */
          height: 50px; /* Adjust as needed */
          border-radius: 50%; /* This will make the image round */
          display: block; /* This will make the image a block element */
          margin-bottom: 5px;
        }

        .user-profile h2 {
          margin: 5px;
          color:aliceblue;
        }
        .back-button img {
            width: 50px;
            height: 50px;
        }



    </style>
</head>
<body>
    <div class="user-profile">
        <img src="/images/user.png" alt="User Image">
        <h4 class="h4"><?= htmlspecialchars($first_name) ?></h4>
    </div>
    <a href="index_product.php" class="back-button">
        <img src="/images/replay.png" alt="Back Button">
    </a>
    <div class="my-5">
        <h2>Edit Product</h2>
        <?php display_session_feedback(); ?>
        <?php
        if ( !empty($errorMessage) ) {
            echo "
            <div class='alert alert-warning alert-dismissible fade show' role='alert'>
                <strong>" . htmlspecialchars($errorMessage) . "</strong>
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
            </div>
            ";
        }
        ?>

        <form method="post">
            <input type="hidden" name="product_ID" value="<?php echo htmlspecialchars($product_ID); ?>">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Product Name</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Price</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="price" value="<?php echo htmlspecialchars($price); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Mfg Date</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="mfg_date" value="<?php echo htmlspecialchars($mfg_date); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Exp Date</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="exp_date" value="<?php echo htmlspecialchars($exp_date); ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Category</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="catagory" value="<?php echo htmlspecialchars($catagory); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                <div class="col-sm-3 d-grid">
                    <button class="btn btn-primary" type="button" onclick="resetForm()">Reset</button>
                </div>

                <script>
                function resetForm() {
                    document.querySelector('input[name="product_name"]').value = '';
                    document.querySelector('input[name="price"]').value = '';
                    document.querySelector('input[name="mfg_date"]').value = '';
                    document.querySelector('input[name="exp_date"]').value = '';
                    document.querySelector('input[name="catagory"]').value = '';
                }
                </script>
            </div>
        </form>
    </div>

</body>
</html>
