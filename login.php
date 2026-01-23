<?php
session_start();
require 'error_handler.php';
require 'db_connection.php';

$error = '';
$username = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "يرجى إدخال اسم المستخدم وكلمة المرور.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, password, role, full_name FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "اسم المستخدم غير صحيح.";
            } elseif (!password_verify($password, $user['password'])) {
                $error = "كلمة المرور غير صحيحة.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                
                if (in_array($user['role'], ['manager','accountant','employee'], true)) {
                    if (!file_exists('accountant_dashboard.php')) {
                        die("Error: accountant_dashboard.php not found. Please check the file exists.");
                    }
                    header("Location: accountant_dashboard.php");
                    exit;
                }
                session_unset();
                session_destroy();
                $error = "الصلاحيات غير معروفة للحساب. تواصل مع الإدارة.";
            }
        } catch (PDOException $e) {
            $error = "خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage());
        } catch (Exception $e) {
            $error = "حدث خطأ: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>بوابة تسجيل الدخول | النظام الداخلي - أكاديمية معا نمضي للتدريب والتطوير</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-dark: #232946;
            --main-blue: #2563eb;
            --main-gray: #f2f4f8;
            --main-white: #fff;
            --main-border: #e0e6ed;
            --accent: #08a6e4;
        }
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Tajawal', 'Roboto', Arial, sans-serif;
            background: linear-gradient(120deg, #232946 0%, #232946 48%, #2563eb 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .background-cube {
            position: fixed;
            width: 250px;
            height: 250px;
            top: 10%;
            left: -70px;
            background: rgba(37, 99, 235, 0.08);
            border-radius: 30px;
            filter: blur(2px);
            z-index: 0;
        }
        .background-cube2 {
            position: fixed;
            width: 260px;
            height: 260px;
            bottom: 10%;
            right: -80px;
            background: rgba(37, 99, 235, 0.13);
            border-radius: 28px;
            filter: blur(3px);
            z-index: 0;
        }
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .login-card {
            width: 400px;
            max-width: 92vw;
            padding: 44px 40px 38px 40px;
            background: var(--main-white);
            border-radius: 22px;
            box-shadow: 0 10px 36px rgba(35, 41, 70, 0.13), 0 2px 4px 0 rgba(35,41,70,0.10);
            display: flex;
            flex-direction: column;
            gap: 19px;
            position: relative;
            animation: loginpop 1.2s cubic-bezier(.7,0,.18,1);
        }
        @keyframes loginpop {
            0% { opacity: 0; transform: scale(0.96) translateY(40px);}
            100% { opacity: 1; transform: none;}
        }
        .login-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .login-title {
            font-family: 'Tajawal', Arial, sans-serif;
            color: var(--main-dark);
            font-weight: 900;
            letter-spacing: 1px;
            font-size: 1.3em;
            margin-bottom: 6px;
        }
        .login-subtitle {
            color: #2563eb;
            font-size: 1.13em;
            margin-bottom: 8px;
            font-weight: 700;
        }
        .login-desc {
            font-size: 1em;
            color: #6b7280;
            margin-bottom: 7px;
        }
        .input-group {
            margin-bottom: 18px;
            position: relative;
        }
        .input-group label {
            font-size: 1.05em;
            font-weight: 700;
            color: #333;
            margin-bottom: 7px;
            display: block;
            letter-spacing: 1px;
        }
        .input-group input {
            width: 100%;
            padding: 13px 13px 13px 45px;
            border-radius: 11px;
            border: 1.6px solid var(--main-border);
            font-size: 1.14em;
            font-family: inherit;
            background: var(--main-gray);
            outline: none;
            transition: border .16s, box-shadow .19s;
        }
        .input-group input:focus {
            border: 1.7px solid var(--main-blue);
            background: #fff;
            box-shadow: 0 2px 11px 0 rgba(37, 99, 235, 0.11);
        }
        .btn-login {
            width: 100%;
            margin-top: 3px;
            padding: 14px 0;
            border-radius: 12px;
            background: linear-gradient(92deg, #232946 0%, #2563eb 100%);
            color: #fff;
            border: none;
            font-size: 1.18em;
            font-family: 'Tajawal', Arial, sans-serif;
            font-weight: 900;
            letter-spacing: 2.1px;
            box-shadow: 0 5px 18px 0 rgba(37, 99, 235, 0.14);
            cursor: pointer;
            transition: background .13s, transform .09s;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #2563eb 0%, #232946 100%);
            transform: translateY(-2px) scale(1.013);
        }
        .error {
            color: #b90027;
            background: #f7e9eb;
            border: 1.4px solid #e23553;
            font-weight: bold;
            padding: 13px 15px;
            border-radius: 9px;
            margin-bottom: 8px;
            text-align: center;
            font-size: 1.04em;
            animation: shake .28s;
        }
        @keyframes shake {
            10%, 90% {transform: translateX(-1.5px);}
            20%, 80% {transform: translateX(2.7px);}
            30%, 50%, 70% {transform: translateX(-6px);}
            40%, 60% {transform: translateX(6px);}
        }
        @media (max-width:520px) {
            .login-card { padding: 21px 8vw 18px 8vw;}
        }
    </style>
</head>
<body>
    <div class="background-cube"></div>
    <div class="background-cube2"></div>
    <div class="login-wrap">
        <form class="login-card" method="post" autocomplete="off">
            <div class="login-header">
                <div class="login-title">بوابة تسجيل الدخول | النظام الداخلي</div>
                <div class="login-subtitle">أكاديمية معا نمضي للتدريب والتطوير</div>
                <div class="login-desc">يرجى تسجيل الدخول للمتابعة إلى لوحة النظام.</div>
            </div>
            <?php if($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <div class="input-group">
                <label for="username">اسم المستخدم</label>
                <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" required autofocus autocomplete="username">
            </div>
            <div class="input-group">
                <label for="password">كلمة المرور</label>
                <input type="password" name="password" id="password" value="<?= htmlspecialchars($password) ?>" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-login">دخول</button>
        </form>
    </div>
</body>
</html>
