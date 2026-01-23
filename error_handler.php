<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $error = date('[Y-m-d H:i:s]') . " Error #$errno: $errstr in $errfile:$errline\n";
    @file_put_contents(__DIR__ . '/php_errors.log', $error, FILE_APPEND);
    return true;
});

set_exception_handler(function($exception) {
    $error = date('[Y-m-d H:i:s]') . " Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine() . "\n";
    @file_put_contents(__DIR__ . '/php_errors.log', $error, FILE_APPEND);
    
    if (!headers_sent() && php_sapi_name() !== 'cli') {
        http_response_code(500);
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'login.php') !== false) {
            die("Database Error: " . htmlspecialchars($exception->getMessage()) . "<br><br>Please check that MySQL is running and the database is set up correctly.");
        }
    }
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $errorMsg = date('[Y-m-d H:i:s]') . " Fatal: {$error['message']} in {$error['file']}:{$error['line']}\n";
        @file_put_contents(__DIR__ . '/php_errors.log', $errorMsg, FILE_APPEND);
    }
});
?>

