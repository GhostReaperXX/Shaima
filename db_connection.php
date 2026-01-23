<?php
$host = 'localhost';
$db = 'sys_academy';
$user = 'root';
$pass = '1234';
$pdo = null;

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function createPDO($dsn, $username, $password, $options) {
    return new PDO($dsn, $username, $password, $options);
}
function initializeDefaultUsers($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username IN ('manager', 'accountant', 'employee')");
        $userCount = $stmt->fetchColumn();
        
        if ($userCount == 0) {
            $users = [
                ['manager', 'manager', 'Manager', 'manager'],
                ['accountant', 'accountant', 'Accountant', 'accountant'],
                ['employee', 'employee', 'Employee', 'employee']
            ];
            
            $insert_stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            foreach ($users as $user_data) {
                $hash = password_hash($user_data[1], PASSWORD_DEFAULT);
                $insert_stmt->execute([$user_data[0], $hash, $user_data[2], $user_data[3]]);
            }
        }
    } catch (PDOException $e) {
        $errorLog = date('[Y-m-d H:i:s]') . " User initialization error: " . $e->getMessage() . "\n";
        @file_put_contents(__DIR__ . '/php_errors.log', $errorLog, FILE_APPEND);
    }
}

function initializeTables($pdo) {
    try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` varchar(50) NOT NULL,
                    `password` varchar(255) NOT NULL,
                    `full_name` varchar(100) NOT NULL,
                    `role` enum('manager','accountant','employee') NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `username` (`username`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                
        $pdo->exec("CREATE TABLE IF NOT EXISTS `students` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `courses` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `student_courses` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `invoices` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `payments` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `tasks` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `task_files` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `task_id` int(11) NOT NULL,
            `file_path` varchar(255) NOT NULL,
            `uploaded_by_role` enum('manager','accountant','employee') NOT NULL DEFAULT 'manager',
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `task_id` (`task_id`),
            CONSTRAINT `fk_tf_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `task_submissions` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `daily_tasks` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `task_id` int(11) NOT NULL,
            `note` varchar(255) DEFAULT '',
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `task_id` (`task_id`),
            CONSTRAINT `daily_tasks_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `trainer_payments` (
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
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `contacts` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(150) NOT NULL,
            `email` varchar(150) NOT NULL,
            `phone` varchar(50) DEFAULT NULL,
            `subject` varchar(200) DEFAULT NULL,
            `message` text NOT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `pending_enrollments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `full_name` varchar(150) NOT NULL,
            `national_id` varchar(50) NOT NULL,
            `phone` varchar(50) NOT NULL,
            `email` varchar(150) NOT NULL,
            `course_id` int(11) NOT NULL,
            `course_name` varchar(200) DEFAULT NULL,
            `course_fees` decimal(12,2) DEFAULT NULL,
            `status` enum('Pending','Contacted','Approved','Rejected') NOT NULL DEFAULT 'Pending',
            `notes` text DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `processed_at` timestamp NULL DEFAULT NULL,
            `processed_by` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `course_id` (`course_id`),
            KEY `status` (`status`),
            KEY `created_at` (`created_at`),
            CONSTRAINT `fk_pe_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        initializeDefaultUsers($pdo);
    } catch (PDOException $e) {
        $errorLog = date('[Y-m-d H:i:s]') . " Table initialization error: " . $e->getMessage() . "\n";
        @file_put_contents(__DIR__ . '/php_errors.log', $errorLog, FILE_APPEND);
    }
}
if (!function_exists('waitForMySQL')) {
function waitForMySQL($host, $maxRetries = 10, $delaySeconds = 2) {
    $pdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2,
    ];
    
    for ($i = 0; $i < $maxRetries; $i++) {
        try {
            $testPdo = new PDO("mysql:host=$host", 'root', '1234', $pdoOptions);
            $testPdo = null; // Close connection
            return true;
        } catch (PDOException $e) {
            if ($i < $maxRetries - 1) {
                sleep($delaySeconds);
            }
        }
    }
    return false;
}
}

$maxConnectionRetries = 5;
$connectionRetryDelay = 2;
$connectionEstablished = false;

for ($retry = 0; $retry < $maxConnectionRetries && !$connectionEstablished; $retry++) {
    try {
        // Attempt primary connection with webuser credentials
        $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $pdoOptions);
        
        // Connection successful - verify all tables exist
        initializeTables($pdo);
        $connectionEstablished = true;
        break; // Exit retry loop on success
        
    } catch (PDOException $e) {
        // Primary connection failed - log error and attempt fallback
        $errorCode = $e->getCode();
        $errorMsg = $e->getMessage();
        
        if ($errorCode == 2002 && $retry < $maxConnectionRetries - 1) {
            $errorLog = date('[Y-m-d H:i:s]') . " DB Connection attempt " . ($retry + 1) . "/$maxConnectionRetries: MySQL not ready yet, waiting...\n";
            @file_put_contents(__DIR__ . '/php_errors.log', $errorLog, FILE_APPEND);
            sleep($connectionRetryDelay);
            continue;
        }
        
        $errorLog = date('[Y-m-d H:i:s]') . " DB Error: " . $errorMsg . " (Code: " . $errorCode . ")\n";
        @file_put_contents(__DIR__ . '/php_errors.log', $errorLog, FILE_APPEND);
        
        if ($errorCode == 2002 || $errorCode == 1045 || $errorCode == 1049) {
            try {
                if ($errorCode == 2002 && $retry < $maxConnectionRetries - 1) {
                    waitForMySQL($host, 5, 2);
                }
                
                try {
                    $pdo = createPDO("mysql:host=$host;charset=utf8mb4", 'root', '1234', $pdoOptions);
                } catch (PDOException $e3) {
                    if ($e3->getCode() == 1045) {
                        $pdo = createPDO("mysql:host=$host;charset=utf8mb4", 'root', '', $pdoOptions);
                    } else {
                        throw $e3;
                    }
                }
                
                $stmt = $pdo->query("SHOW DATABASES LIKE '$db'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                }
                
                $pdo->exec("USE `$db`");
                initializeTables($pdo);
                $connectionEstablished = true;
                
                // Attempt to create MySQL user for future connections
                try {
                    $pdo->exec("CREATE USER IF NOT EXISTS '$user'@'localhost' IDENTIFIED BY '$pass'");
                    $pdo->exec("GRANT ALL PRIVILEGES ON `$db`.* TO '$user'@'localhost'");
                    $pdo->exec("FLUSH PRIVILEGES");
                    
                    // Try reconnecting with webuser credentials
                    try {
                        $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, $pdoOptions);
                        initializeTables($pdo);
                    } catch (PDOException $e2) {
                        try {
                            $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", 'root', '1234', $pdoOptions);
                        } catch (PDOException $e4) {
                            $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", 'root', '', $pdoOptions);
                        }
                        initializeTables($pdo);
                    }
                } catch (PDOException $userError) {
                    try {
                        $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", 'root', '1234', $pdoOptions);
                    } catch (PDOException $e4) {
                        $pdo = createPDO("mysql:host=$host;dbname=$db;charset=utf8mb4", 'root', '', $pdoOptions);
                    }
                    initializeTables($pdo);
                }
            } catch (PDOException $rootError) {
                if ($retry >= $maxConnectionRetries - 1) {
                    if ($errorCode == 2002) {
                        die("Database connection failed: Cannot connect to MySQL server.\n\n" .
                            "The system attempted to connect " . ($retry + 1) . " times.\n\n" .
                            "Please ensure:\n" .
                            "1. MySQL is running (check XAMPP Control Panel)\n" .
                            "2. MySQL service is started\n" .
                            "3. Port 3306 is not blocked\n\n" .
                            "The page will automatically retry when you refresh. " .
                            "If MySQL was just started, wait 10-15 seconds and refresh.");
                    } else {
                        die("Database connection failed: " . htmlspecialchars($errorMsg) . "\n\nError Code: $errorCode\n\nPlease check MySQL is running and try again.");
                    }
                }
                sleep($connectionRetryDelay);
            }
        } else {
            if ($retry >= $maxConnectionRetries - 1) {
                die("Database connection failed: " . htmlspecialchars($errorMsg) . "\n\nError Code: $errorCode");
            }
            sleep($connectionRetryDelay);
        }
    }
}

if (!$connectionEstablished) {
    die("Database connection failed after " . $maxConnectionRetries . " attempts.\n\n" .
        "Please ensure MySQL is running and try again.");
}

?>
