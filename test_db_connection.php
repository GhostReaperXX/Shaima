<?php
error_reporting(0);
ini_set('display_errors', 0);

$host = 'localhost';
$maxRetries = 5;
$delaySeconds = 2;

for ($i = 0; $i < $maxRetries; $i++) {
    try {
        // Try with password first
        $pdo = new PDO("mysql:host=$host", 'root', '1234', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 2,
        ]);
        
        // Test query
        $pdo->query("SELECT 1");
        $pdo = null;
        
        // Success - output OK
        echo "OK";
        exit(0);
    } catch (PDOException $e) {
        $errorCode = $e->getCode();
        
        // If access denied, try without password
        if ($errorCode == 1045 && $i == 0) {
            try {
                $pdo = new PDO("mysql:host=$host", 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                ]);
                $pdo->query("SELECT 1");
                $pdo = null;
                echo "OK";
                exit(0);
            } catch (PDOException $e2) {
                // Continue with retry logic
            }
        }
        
        // If connection refused and not last retry, wait and retry
        if ($errorCode == 2002 && $i < $maxRetries - 1) {
            sleep($delaySeconds);
            continue;
        }
        
        // Last retry or other error - output failure
        if ($i >= $maxRetries - 1) {
            echo "FAILED: " . $e->getMessage();
            exit(1);
        }
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage();
        exit(1);
    }
}

echo "FAILED: Timeout after $maxRetries attempts";
exit(1);
?>
