<?php
    //start the session
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    if (session_status() == PHP_SESSION_NONE) {
        session_start();

    }

    if (!isset($_SESSION['first_name'])) {
        header('Location: login.php');
        exit();
    }

    $first_name = $_SESSION['first_name']; 



    include 'database/connection.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     

        $firstname = isset($_POST['first_name']) ? $_POST['first_name'] : '';
        $lastname = isset($_POST['last_name']) ? $_POST['last_name'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone = isset($_POST['phone_no']) ? $_POST['phone_no'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        $sql = "INSERT INTO Users (first_name, last_name, email, phone_no, password)
            VALUES (:first_name, :last_name, :email, :phone_no, :password)";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':first_name', $firstname);
        $stmt->bindParam(':last_name', $lastname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone_no', $phone);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            //echo "success";
        } else {
            
        }

   
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // Fetching all users from the database
    $sql = "SELECT * FROM Users";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <link rel="stylesheet" type="text/css" href="css/navbar.css">
</head>
<body id="register-body">
    <div class="user-profile">
        <img src="/images/user.png" alt="User Image">
        <h2><?= $first_name ?></h2>
    </div>
    <a href="dashboard.php" class="back-button">
        <img src="/images/replay.png" alt="Back Button">
    </a>

    <div class="register-container">
        <div class="register-form">
            <header>Registration Form</header>
            <form action="register.php" method="post" class="register-form">
                <div class="input-box">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="First Name" required />
                </div>
                <div class="input-box">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Last Name" required />
                </div>
                <div class="input-box">
                    <label>Email Address</label>
                    <input type="text" name="email" placeholder="Enter email address" required />
                </div>
                <div class="column">
                    <div class="input-box">
                        <label>Phone Number</label>
                        <input type="number" name="phone_no" placeholder="Enter phone number" required />
                    </div>
                </div>
                <div class="input-box">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Password" required />
                </div>       
            <button type="submit">register</button>
        </form>
    </div>
        <?php
            if(isset($_SESSION['response'])){
                $response_message = $_SESSION['response']['message'];
                $is_success = $_SESSION['response']['success'];
        ?>
            <div class="responseMessage">
                <p class="responseMessage <?=$is_success ? 'responseMessage__success': 'responseMessage__error' ?>" >
                <?= $response_message ?>
                </p>
            </div> 
        <?php unset($_SESSION['response']); } ?>
    </section>
    <div class="user-info">
        <section class="users-container">
            <header>All Users
            </header>
            <table>
            <tr>
                <th>No.</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Action</th>
            </tr>
            <?php $i = 1; foreach ($users as $user): ?>
                <?php if (!empty($user['first_name']) && !empty($user['last_name']) && !empty($user['email']) && !empty($user['phone_no'])): ?>
                    <tr>
                        <td><?= $i ?></td>
                        <td><?= htmlspecialchars($user['first_name']) ?></td>
                        <td><?= htmlspecialchars($user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone_no']) ?></td>
                        <td>
                            <form action="delete_user.php" method="post">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php $i++; ?>
                <?php endif; ?>
            <?php endforeach; ?>
            </table>
        </section>
    </div>
    </div>
    </div>
                            <
</body>

</html>