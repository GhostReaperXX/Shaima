<?php
require 'error_handler.php';

$host = 'localhost';
$db = 'sys_academy';
$user = 'root';
$pass = '1234';

// PDO connection options
$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// Generate secure random passwords
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

$manager_password = generatePassword(12);
$accountant_password = generatePassword(12);
$employee_password = generatePassword(12);

try {
    // First try to connect without password (XAMPP default)
    // If that fails, try with password 1234
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", 'root', '', $pdoOptions);
    } catch (PDOException $e) {
        // If no password fails, try with password 1234
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", 'root', '1234', $pdoOptions);
    }
    
    // Drop database if exists (WARNING: This deletes all data!)
    $pdo->exec("DROP DATABASE IF EXISTS `$db`");
    
    // Create fresh database
    $pdo->exec("CREATE DATABASE `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db`");
    
    // Create all tables
    echo "Creating database tables...\n";
    
    // Users table
    $pdo->exec("CREATE TABLE `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(50) NOT NULL,
        `password` varchar(255) NOT NULL,
        `full_name` varchar(100) NOT NULL,
        `role` enum('manager','accountant','employee') NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Students table
    $pdo->exec("CREATE TABLE `students` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `full_name` varchar(150) NOT NULL,
        `national_id` varchar(50) NOT NULL,
        `nationality` varchar(80) DEFAULT NULL,
        `specialization` varchar(120) DEFAULT NULL,
        `phone` varchar(50) NOT NULL,
        `email` varchar(150) DEFAULT NULL,
        `address` varchar(255) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_students_national` (`national_id`),
        KEY `full_name` (`full_name`),
        KEY `nationality` (`nationality`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Courses table
    $pdo->exec("CREATE TABLE `courses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(200) NOT NULL,
        `type` enum('Diploma','Short Course') NOT NULL DEFAULT 'Short Course',
        `description` text DEFAULT NULL,
        `trainer_name` varchar(150) NOT NULL,
        `total_hours` int(11) NOT NULL,
        `start_date` date NOT NULL,
        `end_date` date NOT NULL,
        `days` varchar(120) NOT NULL,
        `session_duration` varchar(60) NOT NULL,
        `session_time` time NOT NULL,
        `fees` decimal(12,2) NOT NULL DEFAULT 0.00,
        `trainer_fees` decimal(12,2) NOT NULL DEFAULT 0.00,
        `trainer_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `trainer_name` (`trainer_name`),
        KEY `type` (`type`),
        KEY `start_date` (`start_date`),
        KEY `end_date` (`end_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Student courses table
    $pdo->exec("CREATE TABLE `student_courses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `enrollment_date` date NOT NULL DEFAULT curdate(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `uk_stu_course` (`student_id`,`course_id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `fk_sc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_sc_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Invoices table
    $pdo->exec("CREATE TABLE `invoices` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `invoice_number` varchar(64) NOT NULL,
        `total_amount` decimal(12,2) NOT NULL,
        `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
        `remaining_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
        `due_date` date NOT NULL,
        `status` enum('Paid','Partial','Unpaid') NOT NULL DEFAULT 'Unpaid',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `invoice_number` (`invoice_number`),
        KEY `student_id` (`student_id`),
        KEY `course_id` (`course_id`),
        KEY `status` (`status`),
        KEY `due_date` (`due_date`),
        KEY `idx_invoices_status_due` (`status`,`due_date`),
        CONSTRAINT `fk_inv_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_inv_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Payments table
    $pdo->exec("CREATE TABLE `payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `amount` decimal(12,2) NOT NULL,
        `payment_date` date NOT NULL DEFAULT curdate(),
        `payment_method` varchar(60) NOT NULL,
        `notes` varchar(255) DEFAULT NULL,
        `status` enum('Completed','Voided') NOT NULL DEFAULT 'Completed',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `course_id` (`course_id`),
        KEY `payment_date` (`payment_date`),
        KEY `idx_payments_date` (`payment_date`),
        CONSTRAINT `fk_pay_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_pay_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Tasks table
    $pdo->exec("CREATE TABLE `tasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(200) NOT NULL,
        `description` text NOT NULL,
        `employee_code` varchar(80) NOT NULL,
        `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
        `due_date` date DEFAULT NULL,
        `status` enum('Open','In Progress','Done','Archived') NOT NULL DEFAULT 'Open',
        `created_by_user_id` int(11) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `employee_code` (`employee_code`),
        KEY `status` (`status`),
        KEY `priority` (`priority`),
        KEY `due_date` (`due_date`),
        KEY `created_by_user_id` (`created_by_user_id`),
        KEY `idx_tasks_emp_status` (`employee_code`,`status`),
        CONSTRAINT `fk_tasks_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Task files table
    $pdo->exec("CREATE TABLE `task_files` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `task_id` int(11) NOT NULL,
        `file_path` varchar(255) NOT NULL,
        `uploaded_by_role` enum('manager','accountant','employee') NOT NULL DEFAULT 'manager',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        CONSTRAINT `fk_tf_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Task submissions table
    $pdo->exec("CREATE TABLE `task_submissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `task_id` int(11) NOT NULL,
        `employee_code` varchar(80) NOT NULL,
        `text_notes` text DEFAULT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `checked` tinyint(1) NOT NULL DEFAULT 0,
        `status` enum('Submitted','Approved','Rejected') NOT NULL DEFAULT 'Submitted',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        KEY `employee_code` (`employee_code`),
        KEY `status` (`status`),
        CONSTRAINT `fk_ts_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Daily tasks table
    $pdo->exec("CREATE TABLE `daily_tasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `task_id` int(11) NOT NULL,
        `note` varchar(255) DEFAULT '',
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `task_id` (`task_id`),
        CONSTRAINT `daily_tasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Trainer payments table
    $pdo->exec("CREATE TABLE `trainer_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `amount` decimal(12,2) NOT NULL,
        `payment_date` date NOT NULL DEFAULT curdate(),
        `payment_method` varchar(60) NOT NULL,
        `notes` varchar(255) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        KEY `payment_date` (`payment_date`),
        CONSTRAINT `fk_tp_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Contacts table
    $pdo->exec("CREATE TABLE `contacts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(150) NOT NULL,
        `email` varchar(150) NOT NULL,
        `phone` varchar(50) DEFAULT NULL,
        `subject` varchar(200) DEFAULT NULL,
        `message` text NOT NULL,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "Tables created successfully!\n";
    
    // Create users with new passwords
    echo "Creating users...\n";
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
    
    $stmt->execute(['manager', password_hash($manager_password, PASSWORD_DEFAULT), 'Manager', 'manager']);
    $stmt->execute(['accountant', password_hash($accountant_password, PASSWORD_DEFAULT), 'Accountant', 'accountant']);
    $stmt->execute(['employee', password_hash($employee_password, PASSWORD_DEFAULT), 'Employee', 'employee']);
    
    echo "Users created successfully!\n";
    
    // Set root password to 1234 if not already set
    try {
        $pdo->exec("ALTER USER 'root'@'localhost' IDENTIFIED BY '1234'");
        $pdo->exec("FLUSH PRIVILEGES");
        echo "MySQL root password set to 1234!\n";
    } catch (PDOException $e) {
        // Password may already be set, continue
        echo "Note: Root password configuration: " . $e->getMessage() . "\n";
    }
    
    // Save credentials to file
    $credentials = "========================================\n";
    $credentials .= "ACADEMY PLATFORM - LOGIN CREDENTIALS\n";
    $credentials .= "========================================\n\n";
    $credentials .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $credentials .= "MANAGER:\n";
    $credentials .= "  Username: manager\n";
    $credentials .= "  Password: $manager_password\n\n";
    $credentials .= "ACCOUNTANT:\n";
    $credentials .= "  Username: accountant\n";
    $credentials .= "  Password: $accountant_password\n\n";
    $credentials .= "EMPLOYEE:\n";
    $credentials .= "  Username: employee\n";
    $credentials .= "  Password: $employee_password\n\n";
    $credentials .= "========================================\n";
    $credentials .= "IMPORTANT: Save this file securely!\n";
    $credentials .= "========================================\n";
    
    file_put_contents('credentials.txt', $credentials);
    
    echo "\n========================================\n";
    echo "SETUP COMPLETE!\n";
    echo "========================================\n\n";
    echo "Login Credentials:\n\n";
    echo "MANAGER:\n";
    echo "  Username: manager\n";
    echo "  Password: $manager_password\n\n";
    echo "ACCOUNTANT:\n";
    echo "  Username: accountant\n";
    echo "  Password: $accountant_password\n\n";
    echo "EMPLOYEE:\n";
    echo "  Username: employee\n";
    echo "  Password: $employee_password\n\n";
    echo "Credentials have been saved to: credentials.txt\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    exit(1);
}
?>
