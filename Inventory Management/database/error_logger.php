<?php
if (!function_exists('log_error')) {
    function log_error($errorMessage, $errorContext = '') {
        $logFile = __DIR__ . '/../../error_log.txt'; // Logs to project root
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[{$timestamp}]";
        if (!empty($errorContext)) {
            $logMessage .= " [Context: {$errorContext}]";
        }
        $logMessage .= " Error: {$errorMessage}\n";

        // Append to the log file
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('display_user_error')) {
    function display_user_error($customMessage = "An unexpected error occurred. Please try again later.") {
        echo "<p style='color: red; border: 1px solid red; padding: 10px;'>" . htmlspecialchars($customMessage) . "</p>";
    }
}

if (!function_exists('redirect_with_error')) {
    function redirect_with_error($location, $message) {
        $_SESSION['error_message'] = $message;
        header("Location: $location");
        exit;
    }
}

if (!function_exists('display_session_feedback')) {
    function display_session_feedback() {
        if (isset($_SESSION['error_message'])) {
            echo "<div class='alert alert-danger'>" . htmlspecialchars($_SESSION['error_message']) . "</div>";
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo "<div class='alert alert-success'>" . htmlspecialchars($_SESSION['success_message']) . "</div>";
            unset($_SESSION['success_message']);
        }
    }
}
?>
