<?php
    //start the session
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Check if the first name is set in the session
    if (!isset($_SESSION['first_name'])) {
        // Redirect the user to the login page
        header('Location: login.php');
        exit();
    }

    $first_name = $_SESSION['first_name']; // Now you can safely access $_SESSION['first_name']


?>
<!doctype html>
<!-- This is index_product.php file -->
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <title>SUP Shop|Inventory Management</title>
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">-->
    
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="bootstrap-content">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="card mt-4">
                        <div class="card-header">
                            <h2 id="h2">Product Information </h2>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-7">

                                    <form action="" method="GET">
                                        <div class="input-group mb-3">
                                            <input type="text" name="search" required value="<?php if(isset($_GET['search'])){echo $_GET['search']; } ?>" class="form-control" placeholder="Search data">
                                            <button type="submit" class="btn btn-primary">Search</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="card mt-4">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Product ID</th>
                                        <th>Price</th>
                                        <th>Mfg Date</th>
                                        <th>Exp Date</th>
                                        <th>catagory</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                        $con = mysqli_connect("localhost","root","","inventory");

                                        if(isset($_GET['search']))
                                        {
                                            $filtervalues = $_GET['search'];
                                            $query = "SELECT * FROM product WHERE CONCAT(product_name,product_ID,catagory) LIKE '%$filtervalues%' ";
                                            $query_run = mysqli_query($con, $query);

                                            if(mysqli_num_rows($query_run) > 0)
                                            {
                                                foreach($query_run as $items)
                                                {
                                                    ?>
                                                    <tr>
                                                        <td><?= $items['product_name']; ?></td>
                                                        <td><?= $items['product_ID']; ?></td>
                                                        <td><?= $items['price']; ?></td>
                                                        <td><?= $items['mfg_date']; ?></td>
                                                        <td><?= $items['exp_date']; ?></td>
                                                        <td><?= $items['catagory']; ?></td>
                                                    </tr>
                                                    <?php
                                                }
                                            }
                                            else
                                            {
                                                ?>
                                                    <tr>
                                                        <td colspan="6">No Record Found</td>
                                                    </tr>
                                                <?php
                                            }
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <br>
    <!--search part end, now add product part started, could be help for ziana-->

            <h2 id="h2">Lists of Products</h2>
            <a class="btn btn-primary" href="create_product.php" role="button">+Add new product</a>
            <br>
            <div class="box">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Product ID</th>
                            <th>Price</th>
                            <th>Mfg Date</th>
                            <th>Exp Date</th>
                            <th>category</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $servername = "localhost";
                        $username = "root";
                        $password = "";
                        $database = "inventory";

                        $connection = new mysqli($servername, $username, $password, $database);

                        if ($connection->connect_error) {
                            die("Connection failed: " . $connection->connect_error);
                        }
                        $sql = "SELECT * FROM product";
                        $result = $connection->query($sql);

                        if (!$result) {
                            die("Invalid query: " . $connection->error);
                        }
                        while ($row = $result->fetch_assoc()) {
                            echo "
                            <tr>
                                <td>$row[product_name]</td>
                                <td>$row[product_ID]</td>
                                <td>$row[price]</td>
                                <td>$row[mfg_date]</td>
                                <td>$row[exp_date]</td>
                                <td>$row[catagory]</td>
                                <td>
                                    <a class='btn btn-primary btn-sm' href='edit_product.php?product_ID=$row[product_ID]'>Edit</a>
                                    <a class='btn btn-primary btn-sm' href='delete_product.php?product_ID=$row[product_ID]'>Delete</a>
                                </td>
                            </tr>
                            ";
                        }
                        ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>