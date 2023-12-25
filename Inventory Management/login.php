<?php
    // start the session.
    session_start();
    if(isset($_SESSION['user'])) header('location: dashboard.php');

    $error_message = '';

    if($_POST){
        $conn = include('database/connection.php');

        $username = $_POST['email'];
        $password = $_POST['password'];

        $query = 'SELECT * FROM users WHERE users.email="'. $username .'" AND users.password="'.$password.'"';
        $stmt = $conn ->prepare($query);
        $stmt ->execute();

        if($stmt->rowCount()>0){
            $stmt ->setFetchMode(PDO::FETCH_ASSOC);
            $user = $stmt ->fetchAll()[0];
            $_SESSION['user'] = $user;

            // Store the first name in the session
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['user_id'] = $user['id'];

            header('Location: dashboard.php');
        } else $error_message = 'Please make sure that username and password are correct.';
        
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Mangement System</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
</head>
<body>
    <?php if(!empty($error_message)) { ?>
        <div>
            <p><strong>Error:</strong> <?= $error_message ?></p>
        </div>
    <?php } ?>
    <div class="container">
        <div class="loginHeader">
            <h1>Sup Shop</h1>
            <p>Inventory Management System</p>
        </div>
        <div class="loginBody">
            <div id="absoluteCenteredDiv">
                <form action="login.php" method="post">
                    <div class="box">
                        <h1>Login Form</h1>
                        <input class="email" name="email" type="text" placeholder="email">
                        <input class="email" name="password" type="password" placeholder="Password">
                        <input type="submit" value="Sign In" class="button">
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
