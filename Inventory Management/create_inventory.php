<?php
//  This is create.php file
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


$product_ID = "";
$quantity = "";
$shelf_no = "";
$buy_date = "";

$errorMessage = "";
$successMessage="";


if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    $product_ID = $_POST["product_ID"];
    $quantity = $_POST["quantity"];
    $shelf_no = $_POST["shelf_no"];
    $buy_date = $_POST["buy_date"];

    do {
        if (empty($product_ID) || empty($quantity) ) {
            $errorMessage = "ID and quantity fields are required";
            break;
        }
        $sql = "INSERT INTO manage  ( product_ID, quantity, shelf_no, buy_date) " . 
                "VALUES ( '$product_ID', '$quantity', '$shelf_no', '$buy_date')";
        $result = $connection->query($sql);

        if (!$result) {
            $errorMessage = "Invalid query: " . $connection->error;
            break;
        }

        $product_ID = "";
        $quantity = "";
        $shelf_no = "";
        $buy_date = "";
        
        $successMessage ="Product added successfully";

        header("location: inventory.php");
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
    <link rel="stylesheet" type="text/css" href="css/inventory.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
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

    .user-profile h3 {
        margin: -2px; 
        color:aliceblue;
      }

    .back-button img {
        width: 50px;
        height: 50px;
    }
    .quantity{
         background-color: rgba(238, 190, 78, 0.702);
    }
    label{
         background-color: rgba(245, 200, 245);
    }
    </style>
</head>
<body>
    <div class="user-profile">
        <img src="images/user.png" alt="User Image">
        <h3><?= $first_name ?></h3>
    </div>
    <a href="inventory.php" class="back-button">
        <img src="images/replay.png" alt="Back Button">
    </a>
    <div class="container my-5">
        <div class="name_box">
            <h2 class="quantity">quantity of the product</h2>
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
                <label class="col-sm-3 col-form-label">Product ID</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="product_ID" value="<?php echo $product_ID; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">quantity</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="quantity" value="<?php echo $quantity; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Shelf No.</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="shelf_no" value="<?php echo $shelf_no; ?>">
                </div>
            </div>
            <div class="row mb-3">
                <label class="col-sm-3 col-form-label">Buying Date</label>
                <div class="col-sm-6">
                    <input type="text" class="form-control" name="buy_date" value="<?php echo $buy_date; ?>">
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
                    <a class="btn btn-outline-primary" href="inventory.php" role="button">Cancel</a>
                </div>
            </div>
        </form>
    </div>
    
</body>
</html>
