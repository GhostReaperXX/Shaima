<?php
require 'error_handler.php';
require 'db_connection.php';

$new_password = '1234@1234';
$users = ['manager', 'accountant', 'employee'];

try {
    $pdo->beginTransaction();
    
    foreach ($users as $username) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ Password reset for user: $username\n";
        } else {
            // User doesn't exist, create it
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $full_name = ucfirst($username);
            $stmt->execute([$username, $hashed_password, $full_name, $username]);
            echo "✓ Created user with password reset: $username\n";
        }
    }
    
    $pdo->commit();
    
    echo "\n========================================\n";
    echo "PASSWORD RESET COMPLETE!\n";
    echo "========================================\n\n";
    echo "All users now have password: $new_password\n\n";
    echo "MANAGER:\n";
    echo "  Username: manager\n";
    echo "  Password: $new_password\n\n";
    echo "ACCOUNTANT:\n";
    echo "  Username: accountant\n";
    echo "  Password: $new_password\n\n";
    echo "EMPLOYEE:\n";
    echo "  Username: employee\n";
    echo "  Password: $new_password\n\n";
    
    // Update credentials.txt
    $credentials = "========================================\n";
    $credentials .= "ACADEMY PLATFORM - LOGIN CREDENTIALS\n";
    $credentials .= "========================================\n\n";
    $credentials .= "Updated: " . date('Y-m-d H:i:s') . "\n\n";
    $credentials .= "MANAGER:\n";
    $credentials .= "  Username: manager\n";
    $credentials .= "  Password: $new_password\n\n";
    $credentials .= "ACCOUNTANT:\n";
    $credentials .= "  Username: accountant\n";
    $credentials .= "  Password: $new_password\n\n";
    $credentials .= "EMPLOYEE:\n";
    $credentials .= "  Username: employee\n";
    $credentials .= "  Password: $new_password\n\n";
    $credentials .= "========================================\n";
    $credentials .= "IMPORTANT: Save this file securely!\n";
    $credentials .= "========================================\n";
    
    file_put_contents('credentials.txt', $credentials);
    echo "Credentials saved to credentials.txt\n";
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
