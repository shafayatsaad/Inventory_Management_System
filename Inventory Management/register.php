<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['first_name'])) {
    header('Location: login.php');
    exit();
}

$first_name = $_SESSION['first_name'];

require_once 'database/connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['first_name'])) {
    $firstname = $_POST['first_name'] ?? '';
    $lastname = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone_no'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($phone) || empty($password)) {
        $_SESSION['response'] = [
            'success' => false,
            'message' => 'All fields are required.'
        ];
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO Users (first_name, last_name, email, phone_no, password)
                VALUES (:first_name, :last_name, :email, :phone_no, :password)";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':first_name', $firstname);
            $stmt->bindParam(':last_name', $lastname);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone_no', $phone);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                $_SESSION['response'] = [
                    'success' => true,
                    'message' => 'User registered successfully.'
                ];
            }
        } catch (PDOException $e) {
            log_error("User registration failed: " . $e->getMessage(), "register.php");
            $_SESSION['response'] = [
                'success' => false,
                'message' => 'An error occurred during registration. Please try again.'
            ];
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetching all users from the database
try {
    $sql = "SELECT * FROM Users";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    log_error("Failed to fetch users: " . $e->getMessage(), "register.php");
    $users = [];
    $fetch_error = "Could not retrieve user data.";
}
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
        <h2><?= htmlspecialchars($first_name) ?></h2>
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
        if (isset($_SESSION['response'])) {
            $response_message = $_SESSION['response']['message'];
            $is_success = $_SESSION['response']['success'];
        ?>
            <div class="responseMessage">
                <p class="responseMessage <?= $is_success ? 'responseMessage__success' : 'responseMessage__error' ?>">
                    <?= htmlspecialchars($response_message) ?>
                </p>
            </div>
        <?php unset($_SESSION['response']);
        } ?>
    </div>
    <div class="user-info">
        <section class="users-container">
            <header>All Users</header>
            <?php if (isset($fetch_error)) : ?>
                <p><?= htmlspecialchars($fetch_error) ?></p>
            <?php else : ?>
                <table>
                    <tr>
                        <th>No.</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Action</th>
                    </tr>
                    <?php $i = 1;
                    foreach ($users as $user) : ?>
                        <tr>
                            <td><?= $i ?></td>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone_no']) ?></td>
                            <td>
                                <form action="delete_user.php" method="post">
                                    <input type="hidden" name="user_ID" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php $i++;
                    endforeach; ?>
                </table>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>
