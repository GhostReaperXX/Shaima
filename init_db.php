<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

$host = 'localhost';
$db = 'sys_academy';
$targetPassword = '1234';

try {
    $pdo = null;
    $connected = false;
    
    // Try to connect with password 1234 first
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", 'root', $targetPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $connected = true;
        echo "OK: Connected with password\n";
    } catch (PDOException $e) {
        // Try without password
        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", 'root', '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $connected = true;
            
            // Set password to 1234
            try {
                $pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY '$targetPassword'");
                $pdo->exec("FLUSH PRIVILEGES");
                
                // Reconnect with new password to verify
                $pdo = new PDO("mysql:host=$host;charset=utf8mb4", 'root', $targetPassword, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                echo "OK: Password set to 1234\n";
            } catch (PDOException $e3) {
                // Password setting failed, but we're connected - continue
                echo "OK: Connected (password setting skipped)\n";
            }
        } catch (PDOException $e2) {
            // Both attempts failed
            throw new Exception("Cannot connect to MySQL: " . $e2->getMessage());
        }
    }
    
    if (!$connected || !$pdo) {
        throw new Exception("Failed to establish MySQL connection");
    }
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    
    // Initialize database using db_connection.php
    require_once __DIR__ . '/db_connection.php';
    
    echo "OK: Database initialized\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>
