<?php
if (!function_exists('log_error')) {
    function log_error($errorMessage, $errorContext = '') {
        $logFile = __DIR__ . '/../../error_log.txt'; // Logs to project root
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[{$timestamp}]";
        if (!empty($errorContext)) {
            $logMessage .= " [Context: {$errorContext}]";
        }
        $logMessage .= " Error: {$errorMessage}
";

        // Append to the log file
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

if (!function_exists('display_user_error')) {
    function display_user_error($customMessage = "An unexpected error occurred. Please try again later.") {
        // You can make this more sophisticated, e.g., set a session variable
        // and redirect to an error page, or echo directly.
        // For simplicity, this example echoes a generic message.
        // Ensure this is called before any significant HTML output if you plan to echo directly,
        // or use it in a way that doesn't break page rendering.
        echo "<p style='color: red; border: 1px solid red; padding: 10px;'>" . htmlspecialchars($customMessage) . "</p>";
    }
}
?>
