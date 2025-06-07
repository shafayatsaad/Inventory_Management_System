<?php
session_start();
require_once __DIR__ . '/connection.php'; // Includes error_logger.php

$login_page_url = '../login.php';

// General security alert for password handling status
log_error("Processing login. Review password handling: current state involves checking for plain text and migrating to hash.", "process.php - System Status");

if (isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password_posted = $_POST['password']; // Password from user form, not trimmed

    if (empty($username) || empty($password_posted)) {
        log_error("Login attempt with empty username or password. Username provided: '{$username}'", "process.php - Validation Failure");
        $_SESSION['login_error_message'] = 'Username and password are required.';
        header("Location: " . $login_page_url . "?error=empty_credentials");
        exit;
    }

    try {
        // Assuming table name is 'Users'. Adjust if it's 'users'.
        $stmt = $conn->prepare('SELECT user_ID, first_name, last_name, username, password, user_type FROM Users WHERE username = :username');
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $login_successful = false;
        $password_handling_info = "No user found"; // Default status

        if ($user) {
            $stored_password = $user['password']; // Password from database
            $password_handling_info = "User found. ";

            // Check if the stored password appears to be a BCRYPT hash
            // Standard BCRYPT hashes are 60 characters long and start with $2y$ (or $2a$, $2b$).
            if (strlen($stored_password) === 60 && (strpos($stored_password, '$2y$') === 0 || strpos($stored_password, '$2a$') === 0 || strpos($stored_password, '$2b$') === 0)) {
                $password_handling_info .= "Stored password appears to be hashed. Verifying...";
                if (password_verify($password_posted, $stored_password)) {
                    $login_successful = true;
                    $password_handling_info .= " Hash verified.";

                    // Optional: Check if the hash needs re-hashing due to algorithm updates (e.g., cost factor change)
                    if (password_needs_rehash($stored_password, PASSWORD_BCRYPT)) {
                        $new_rehash = password_hash($password_posted, PASSWORD_BCRYPT);
                        if ($new_rehash === false) {
                            log_error("Password REHASHING FAILED (hashing part) for user '{$username}'. Login proceeded with old hash.", "process.php - Rehash Failed");
                            $password_handling_info .= " Rehash needed but new hash generation failed.";
                        } else {
                            try {
                                $rehash_update_stmt = $conn->prepare("UPDATE Users SET password = :password WHERE user_ID = :user_ID");
                                $rehash_update_stmt->execute([':password' => $new_rehash, ':user_ID' => $user['user_ID']]);
                                log_error("Password automatically rehashed upon login for user '{$username}'.", "process.php - Rehash Success");
                                $password_handling_info .= " Password rehashed to new standard.";
                            } catch (PDOException $e_rehash) {
                                log_error("PDOException during password REHASH update for user '{$username}'. Error: " . $e_rehash->getMessage(), "process.php - Rehash PDOException");
                                $password_handling_info .= " Rehash DB update failed.";
                            }
                        }
                    }
                } else {
                    $password_handling_info .= " Hash verification failed.";
                }
            } else {
                // Stored password does not look like a hash - assume plain text (LEGACY)
                $password_handling_info .= "Stored password appears to be PLAIN TEXT. Comparing directly.";
                log_error("CRITICAL SECURITY WARNING: Plain text password detected in DB for user '{$username}'. Attempting direct comparison and migration.", "process.php - Plain Text Password Found");

                if ($password_posted === $stored_password) {
                    $login_successful = true;
                    $password_handling_info .= " Plain text matched.";

                    // MIGRATE PLAIN TEXT PASSWORD TO HASH
                    $new_migrated_hash = password_hash($password_posted, PASSWORD_BCRYPT);
                    if ($new_migrated_hash === false) {
                        log_error("Password hashing FAILED during plain text migration for user '{$username}'. Login proceeded, but password REMAINS PLAIN TEXT.", "process.php - Migration Hashing Failed");
                        $password_handling_info .= " Hashing for migration failed!";
                    } else {
                        try {
                            $migrate_update_stmt = $conn->prepare("UPDATE Users SET password = :hashed_password WHERE user_ID = :user_ID");
                            $migrate_update_stmt->bindParam(':hashed_password', $new_migrated_hash);
                            $migrate_update_stmt->bindParam(':user_ID', $user['user_ID'], PDO::PARAM_INT);
                            if ($migrate_update_stmt->execute()) {
                                log_error("Plain text password successfully migrated to hash for user '{$username}'.", "process.php - Migration Success");
                                $password_handling_info .= " Successfully migrated to hash.";
                            } else {
                                log_error("Password migration to hash FAILED (DB update error) for user '{$username}'. Error: " . print_r($migrate_update_stmt->errorInfo(), true) . ". Password REMAINS PLAIN TEXT.", "process.php - Migration DB Update Failed");
                                $password_handling_info .= " Migration DB update failed!";
                            }
                        } catch (PDOException $e_migrate) {
                             log_error("PDOException during password migration DB update for user '{$username}'. Error: " . $e_migrate->getMessage() . ". Password REMAINS PLAIN TEXT.", "process.php - Migration PDOException");
                             $password_handling_info .= " Migration DB update caused PDOException!";
                        }
                    }
                } else {
                     $password_handling_info .= " Plain text did not match.";
                }
            }
        }

        if ($login_successful) {
            // Regenerate session ID on successful login
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_ID'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'] ?? '';
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            log_error("User '{$username}' logged in successfully. User type: {$user['user_type']}. " . $password_handling_info, "process.php - Login Success Audit");
            header('Location: ../dashboard.php');
            exit;
        } else {
            log_error("Failed login attempt for username: '{$username}'. " . $password_handling_info, "process.php - Login Failure Audit");
            $_SESSION['login_error_message'] = 'Invalid username or password.';
            header("Location: " . $login_page_url . "?error=invalid_credentials");
            exit;
        }
    } catch (PDOException $e) {
        log_error("PDOException during login process for username: '{$username}'. Error: " . $e->getMessage(), "process.php - General PDOException");
        $_SESSION['login_error_message'] = 'A database error occurred. Please try again later.';
        header("Location: " . $login_page_url . "?error=db_error");
        exit;
    }
} else {
    log_error("Direct access or missing credentials for process.php. Request Method: {$_SERVER['REQUEST_METHOD']}", "process.php - Access Violation");
    $_SESSION['login_error_message'] = 'Please provide your credentials.';
    header("Location: " . $login_page_url . "?error=missing_credentials");
    exit;
}
?>
