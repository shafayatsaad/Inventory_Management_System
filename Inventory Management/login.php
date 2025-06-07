<?php
    // start the session.
    // It's good practice to ensure session_start() is called only once and at the very top.
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    // If already logged in (e.g., user_id is set in session), redirect to dashboard.
    if (isset($_SESSION['user_id'])) {
        header('Location: dashboard.php');
        exit(); // Always exit after a header redirect
    }

    // Initialize variable to hold any error message to be displayed
    $display_error_message = '';

    // Check for login error messages passed from process.php via session
    if (isset($_SESSION['login_error_message'])) {
        $display_error_message = $_SESSION['login_error_message'];
        unset($_SESSION['login_error_message']); // Clear the message once retrieved
    }
    // Optional: Fallback for error messages passed via GET (less secure for sensitive details)
    // elseif (isset($_GET['error'])) {
    //     $error_code = $_GET['error'];
    //     if ($error_code === 'invalid_credentials') {
    //         $display_error_message = 'Invalid username or password provided.';
    //     } elseif ($error_code === 'db_error') {
    //         $display_error_message = 'A database error occurred. Please try again later.';
    //     } elseif ($error_code === 'empty_credentials') {
    //         $display_error_message = 'Username and password are required.';
    //     } elseif ($error_code === 'missing_credentials') {
    //         $display_error_message = 'Please provide your credentials.';
    //     } else {
    //         $display_error_message = 'An unspecified error occurred during login.';
    //     }
    // }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory Management System</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
    <style>
        /* Basic style for error messages banner */
        .error-banner {
            background-color: #f8d7da; /* Light red */
            color: #721c24; /* Dark red */
            padding: 12px 15px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb; /* Slightly darker red border */
            border-radius: 5px; /* Optional: rounded corners */
            width: 80%; /* Or specific width */
            margin-left: auto;
            margin-right: auto;
            box-sizing: border-box;
        }
        /* Ensure the container for the login form is visible and centered if needed */
        body {
            display: flex;
            flex-direction: column; /* Stack error banner and container */
            align-items: center; /* Center items horizontally */
            /* Other body styles from login.css will apply */
        }
        .container {
            /* Styles from login.css will apply, ensure it's not hidden by error banner if banner is outside */
        }
    </style>
</head>
<body>
    <?php if(!empty($display_error_message)): ?>
        <div class="error-banner">
            <!-- htmlspecialchars is important here to prevent XSS if error messages could contain user input -->
            <p><?= htmlspecialchars($display_error_message, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    <?php endif; ?>

    <div class="container">
        <div class="loginHeader">
            <h1>Sup Shop</h1>
            <p>Inventory Management System</p>
        </div>
        <div class="loginBody">
            <div id="absoluteCenteredDiv">
                <!-- Form action changed to point to the centralized login processing script -->
                <form action="database/process.php" method="post">
                    <div class="box">
                        <h1>Login Form</h1>
                        <!--
                            Input field 'name' changed from 'email' to 'username'
                            to match what database/process.php expects.
                            The placeholder can still say "Username or Email" for user guidance.
                        -->
                        <input class="email" name="username" type="text" placeholder="Username or Email" required autofocus>
                        <input class="email" name="password" type="password" placeholder="Password" required>
                        <input type="submit" value="Sign In" class="button">
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
