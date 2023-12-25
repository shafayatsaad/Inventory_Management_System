<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
//  This is create.php file
$servername = "localhost";
$username = "root";
$password = "";
$database = "inventory";

$connection = new mysqli($servername, $username, $password, $database);

$product_name = "";
$product_ID = "";
$price = "";
$mfg_date = "";
$exp_date = "";
$catagory = "";

$errorMessage = "";
$successMessage="";

// Check if the first name is set in the session
if (!isset($_SESSION['first_name'])) {
    // Redirect the user to the login page
    exit();
}

$first_name = $_SESSION['first_name']; // Now you can safely access $_SESSION['first_name']


if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $product_name = $_POST["product_name"];
    $product_ID = $_POST["product_ID"];
    $price = $_POST["price"];
    $mfg_date = $_POST["mfg_date"];
    $exp_date = $_POST["exp_date"];
    $catagory = $_POST["catagory"];

    do {
        if (empty($product_name) || empty($product_ID) || empty($price) || empty($mfg_date) || empty($exp_date) || empty($catagory) ) {
            $errorMessage = "All the fields are required";
            break;
        }
        $sql = "INSERT INTO product  (product_name, product_ID, price, mfg_date, exp_date, catagory) " . 
                "VALUES ('$product_name', '$product_ID', '$price', '$mfg_date', '$exp_date', '$catagory')";
        $result = $connection->query($sql);

        if (!$result) {
            $errorMessage = "Invalid query: " . $connection->error;
            break;
        }

        $product_name = "";
        $product_ID = "";
        $price = "";
        $mfg_date = "";
        $exp_date = "";
        $catagory = "";
        
        $successMessage ="Product added successfully";

        header("location: index.php");
        exit;

    } while (false);
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
    <script>
        function resetForm() {
            document.querySelector('form').reset();
        }
    </script>
</head>
<body>
    <div class="user-profile">
        <img src="/images/user.png" alt="User Image">
        <h4 class="h4"><?= $first_name ?></h4>
    </div>
    <a href="index_product.php" class="back-button">
        <img src="/images/replay.png" alt="Back Button">
    </a>
    <div class="container my-5">
        <div class="name_box">
            <h2>New Product</h2>
            <?php
            if ( !empty($errorMessage) ) {
                echo "
                <div class='alert alert-warning alert-dismissible fade show' role='alert'>
                    <strong>$errorMessage</strong>
                    <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>
                ";
            }
            ?>
        </div>

        <form method="post">
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Product Name</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="product_name" value="<?php echo $product_name; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Product ID</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="product_ID" value="<?php echo $product_ID; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Price</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="price" value="<?php echo $price; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Mfg Date</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="mfg_date" value="<?php echo $mfg_date; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Exp Date</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="exp_date" value="<?php echo $exp_date; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Category</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="catagory" value="<?php echo $catagory; ?>">
                </div>
            </div>

            <?php
            if ( !empty($successMessage) ) {
                echo "
                <div class='row mb-3'>
                    <div class='offset-sm-3 col-sm-6'>
                        <div class='alert alert-success alert-dismissible fade show' role='alert'>
                            <strong>$successMessage</strong>
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>
                    </div>
                </div>
                ";
            }
            ?>
            <div class="row mb-3">
                <div class="offset-sm-3 col-sm-3 d-grid">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                <div class="col-sm-3 d-grid">
                    <button class="btn btn-primary" type="button" onclick="resetForm()">Reset</button>
                </div>

            </div>
        </form>
    </div>
    
</body>
</html>