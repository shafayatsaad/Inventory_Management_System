<?php
    // Start the session
    session_start();

    // Check if the first name is set in the session
    if (!isset($_SESSION['first_name'])) {
        // Redirect the user to the login page
        header('Location: login.php');
        exit();
    }

    $first_name = $_SESSION['first_name']; 

    $user = ""; // Define $user with a default value

    include 'database/connection.php';

    if (isset($_SESSION['user'])) {
        $user = $_SESSION['user'];
    } else {
        header('location: login.php');
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory Mangement System</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<?php include 'navbar.php'; ?>

</body>
</html>
