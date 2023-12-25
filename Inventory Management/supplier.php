<?php
    // Start the session
    session_start();

    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "inventory";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check if the first name is set in the session
    if (!isset($_SESSION['first_name'])) {
        // Redirect the user to the login page
        header('Location: login.php');
        exit();
    }

    $first_name = $_SESSION['first_name']; // Now you can safely access $_SESSION['first_name']

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplierID']) && isset($_POST['supplierName']) && isset($_POST['supplierEmail']) && isset($_POST['supplierPhone']) && isset($_POST['supplierLocation']) && isset($_POST['productID'])) {
        $supplierID = $_POST['supplierID'];
        $supplierName = $_POST['supplierName'];
        $supplierEmail = $_POST['supplierEmail'];
        $supplierPhone = $_POST['supplierPhone'];
        $supplierLocation = $_POST['supplierLocation'];
        $productID = $_POST['productID'];
    
        $sql = "INSERT INTO supplier (supplierID, supplierName, supplierEmail, supplierPhone, supplierLocation, productID) VALUES (?, ?, ?, ?, ?, ?)";
    
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssi", $supplierID, $supplierName, $supplierEmail, $supplierPhone, $supplierLocation, $productID);
    
        if ($stmt->execute()) {
            echo "";
        } else {
            echo "Error: " . $stmt->error;
        }
    
        // Redirect to the same page
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Handle remove selected suppliers
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['removeSelected']) && isset($_POST['selectedIDs'])) {
        $selectedIDs = $_POST['selectedIDs'];
    
        foreach ($selectedIDs as $id) {
            $sql = "DELETE FROM supplier WHERE supplierID = ?";
        
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
        
            if ($stmt->execute()) {
                echo "";
            } else {
                echo "Error: " . $stmt->error;
            }
        }
    }
    // Fetch all suppliers from the database
    $sql = "SELECT * FROM supplier";
    $result = $conn->query($sql);
    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Page</title>
    <link rel="stylesheet" href="css/supplier.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="container">
        <header>
            <h1>Supplier Registry</h1>
        </header>
        
        <form action="supplier.php" method="post">
            <label for="supplierID">Supplier ID:</label>
            <input type="number" id="supplierID" name="supplierID" required>

            <label for="supplierName">Name:</label>
            <input type="text" id="supplierName" name="supplierName" required>

            <label for="supplierEmail">Email:</label>
            <input type="email" id="supplierEmail" name="supplierEmail" required>

            <label for="supplierPhone">Phone:</label>
            <input type="tel" id="supplierPhone" name="supplierPhone" required>

            <label for="supplierLocation">Location:</label>
            <input type="text" id="supplierLocation" name="supplierLocation" required>

            <label for="productID">Product ID:</label>
            <input type="number" id="productID" name="productID" required>

            <button type="submit" class="add-btn">Add Supplier</button>
            <button type="reset" class="reset-btn">Reset</button>
        </form>
        <!--<form action="supplier.php" method="get">
            <input type="text" id="searchSupplier" name="searchSupplier" placeholder="Search by Supplier ID">
            <button type="submit" class="search-btn">Search</button>
        </form>-->
        <form action="supplier.php" method="post">
            <div id="supplier-list">
                <h2>Supplier List</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Select</th>
                            <th>Supplier ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Product ID</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <!-- Loop through the suppliers and create a table row for each one -->
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><input type="checkbox" name="selectedIDs[]" value="<?php echo $row['supplierID']; ?>"></td>
                                <td><?php echo $row['supplierID']; ?></td>
                                <td><?php echo $row['supplierName']; ?></td>
                                <td><?php echo $row['supplierEmail']; ?></td>
                                <td><?php echo $row['supplierPhone']; ?></td>
                                <td><?php echo $row['supplierLocation']; ?></td>
                                <td><?php echo $row['productID']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button type="submit" name="removeSelected" class="remove-btn">Remove Selected</button>
            </div>
        </form>
    </div>
</body>
</html>
