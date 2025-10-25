<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/error_logger.php';

if (!isset($_SESSION['first_name'])) {
    header('Location: login.php');
    exit();
}

$first_name = $_SESSION['first_name'];
?>
<!doctype html>
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
                                        <input type="text" name="search" required value="<?php if(isset($_GET['search'])){echo htmlspecialchars($_GET['search']); } ?>" class="form-control" placeholder="Search data">
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
                        <?php display_session_feedback(); ?>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Product ID</th>
                                    <th>Price</th>
                                    <th>Mfg Date</th>
                                    <th>Exp Date</th>
                                    <th>category</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (isset($_GET['search'])) {
                                    $filtervalues = $_GET['search'];
                                    try {
                                        $stmt = $conn->prepare("SELECT * FROM product WHERE CONCAT(product_name,product_ID,catagory) LIKE :search");
                                        $search_param = "%$filtervalues%";
                                        $stmt->bindParam(':search', $search_param);
                                        $stmt->execute();
                                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        if (count($result) > 0) {
                                            foreach ($result as $items) {
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($items['product_name']) . "</td>
                                                        <td>" . htmlspecialchars($items['product_ID']) . "</td>
                                                        <td>" . htmlspecialchars($items['price']) . "</td>
                                                        <td>" . htmlspecialchars($items['mfg_date']) . "</td>
                                                        <td>" . htmlspecialchars($items['exp_date']) . "</td>
                                                        <td>" . htmlspecialchars($items['catagory']) . "</td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6'>No Record Found</td></tr>";
                                        }
                                    } catch (PDOException $e) {
                                        log_error("Search failed: " . $e->getMessage(), "index_product.php");
                                        echo "<tr><td colspan='6'>An error occurred during search.</td></tr>";
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
                    try {
                        $stmt = $conn->prepare("SELECT * FROM product");
                        $stmt->execute();
                        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($result as $row) {
                            echo "
                            <tr>
                                <td>" . htmlspecialchars($row['product_name']) . "</td>
                                <td>" . htmlspecialchars($row['product_ID']) . "</td>
                                <td>" . htmlspecialchars($row['price']) . "</td>
                                <td>" . htmlspecialchars($row['mfg_date']) . "</td>
                                <td>" . htmlspecialchars($row['exp_date']) . "</td>
                                <td>" . htmlspecialchars($row['catagory']) . "</td>
                                <td>
                                    <a class='btn btn-primary btn-sm' href='edit_product.php?product_ID=" . htmlspecialchars($row['product_ID']) . "'>Edit</a>
                                    <a class='btn btn-primary btn-sm' href='database/delete_product.php?product_ID=" . htmlspecialchars($row['product_ID']) . "'>Delete</a>
                                </td>
                            </tr>
                            ";
                        }
                    } catch (PDOException $e) {
                        log_error("Failed to fetch products: " . $e->getMessage(), "index_product.php");
                        echo "<tr><td colspan='7'>Error fetching product list.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
