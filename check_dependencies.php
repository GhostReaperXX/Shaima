<?php
/**
 * Dependency Checker Script
 * Run this file in your browser to verify all dependencies are installed correctly
 * URL: http://localhost/Academy-platform/check_dependencies.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dependency Checker - Academy Platform</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .check-item.success {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .icon {
            font-size: 24px;
            font-weight: bold;
        }
        .check-content {
            flex: 1;
        }
        .check-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .check-message {
            color: #666;
            font-size: 0.9em;
        }
        .fix-instructions {
            background: #e7f3ff;
            border: 2px solid #2196F3;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        .fix-instructions h3 {
            color: #1976D2;
            margin-bottom: 10px;
        }
        .fix-instructions code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .fix-instructions ol {
            margin-left: 20px;
            line-height: 2;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
        }
        .summary.success {
            background: #d4edda;
            color: #155724;
        }
        .summary.error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Dependency Checker</h1>
        <p class="subtitle">Verifying all required dependencies for Academy Platform</p>
        
        <?php
        $allPassed = true;
        $checks = [];
        
        // Check 1: PHP Version
        $phpVersion = PHP_VERSION;
        $phpRequired = '7.4';
        $phpOk = version_compare($phpVersion, $phpRequired, '>=');
        $checks[] = [
            'title' => 'PHP Version',
            'status' => $phpOk ? 'success' : 'error',
            'message' => $phpOk 
                ? "PHP {$phpVersion} is installed (Required: {$phpRequired}+)" 
                : "PHP {$phpVersion} is installed, but {$phpRequired}+ is required",
            'icon' => $phpOk ? '✅' : '❌'
        ];
        if (!$phpOk) $allPassed = false;
        
        // Check 2: Composer vendor directory
        $vendorExists = file_exists(__DIR__ . '/vendor/autoload.php');
        $checks[] = [
            'title' => 'Composer Dependencies',
            'status' => $vendorExists ? 'success' : 'error',
            'message' => $vendorExists 
                ? 'All PHP dependencies are installed via Composer' 
                : 'vendor/autoload.php not found. Run "composer install"',
            'icon' => $vendorExists ? '✅' : '❌'
        ];
        if (!$vendorExists) $allPassed = false;
        
        // Check 3: mPDF Library
        $mpdfExists = false;
        if ($vendorExists) {
            try {
                require_once __DIR__ . '/vendor/autoload.php';
                if (class_exists('Mpdf\Mpdf')) {
                    $mpdfExists = true;
                }
            } catch (Exception $e) {
                $mpdfExists = false;
            }
        }
        $checks[] = [
            'title' => 'mPDF Library',
            'status' => $mpdfExists ? 'success' : 'error',
            'message' => $mpdfExists 
                ? 'mPDF library is installed (required for syllabus PDF generation)' 
                : 'mPDF library is missing. Run "composer install"',
            'icon' => $mpdfExists ? '✅' : '❌'
        ];
        if (!$mpdfExists) $allPassed = false;
        
        // Check 4: Database Connection
        $dbOk = false;
        $dbMessage = '';
        try {
            require_once __DIR__ . '/db_connection.php';
            if (isset($pdo) && $pdo instanceof PDO) {
                $dbOk = true;
                $dbMessage = 'Database connection is configured';
            } else {
                $dbMessage = 'Database connection file exists but PDO is not initialized';
            }
        } catch (Exception $e) {
            $dbMessage = 'Database connection error: ' . $e->getMessage();
        }
        $checks[] = [
            'title' => 'Database Connection',
            'status' => $dbOk ? 'success' : 'warning',
            'message' => $dbMessage,
            'icon' => $dbOk ? '✅' : '⚠️'
        ];
        
        // Check 5: tmp directory (for mPDF)
        $tmpDir = __DIR__ . '/tmp';
        $tmpExists = is_dir($tmpDir);
        $tmpWritable = $tmpExists && is_writable($tmpDir);
        $checks[] = [
            'title' => 'Temporary Directory',
            'status' => $tmpWritable ? 'success' : ($tmpExists ? 'warning' : 'error'),
            'message' => $tmpWritable 
                ? 'tmp/ directory exists and is writable' 
                : ($tmpExists 
                    ? 'tmp/ directory exists but is not writable' 
                    : 'tmp/ directory does not exist (will be created automatically)'),
            'icon' => $tmpWritable ? '✅' : '⚠️'
        ];
        
        // Display checks
        foreach ($checks as $check) {
            echo '<div class="check-item ' . $check['status'] . '">';
            echo '<span class="icon">' . $check['icon'] . '</span>';
            echo '<div class="check-content">';
            echo '<div class="check-title">' . htmlspecialchars($check['title']) . '</div>';
            echo '<div class="check-message">' . htmlspecialchars($check['message']) . '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        // Display summary
        if ($allPassed) {
            echo '<div class="summary success">';
            echo '✅ All critical dependencies are installed! The platform should work correctly.';
            echo '</div>';
        } else {
            echo '<div class="summary error">';
            echo '❌ Some dependencies are missing. Please follow the instructions below.';
            echo '</div>';
            
            echo '<div class="fix-instructions">';
            echo '<h3>How to Fix Missing Dependencies</h3>';
            echo '<ol>';
            echo '<li>Open terminal/command prompt in the project directory</li>';
            echo '<li>Run: <code>composer install</code></li>';
            echo '<li>If Composer is not installed, download it from: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></li>';
            echo '<li>After installation, refresh this page to verify</li>';
            echo '</ol>';
            echo '<p><strong>Note:</strong> The syllabus download feature requires mPDF library which is installed via Composer.</p>';
            echo '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center; color: #666;">
            <p>Academy Platform Dependency Checker</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Run this check after cloning/downloading the repository</p>
        </div>
    </div>
</body>
</html>
