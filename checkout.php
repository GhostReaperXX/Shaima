<?php
session_start();
require 'error_handler.php';
require 'db_connection.php';

$course_id = (int)($_GET['course_id'] ?? 0);
$course = null;

$courses_data = [
    1 => ['name' => 'الأمن السيبراني المتقدم', 'fees' => 450, 'trainer_name' => 'د. محمد الأحمد', 'total_hours' => 120, 'type' => 'متقدم'],
    2 => ['name' => 'أساسيات الأمن السيبراني للمبتدئين', 'fees' => 250, 'trainer_name' => 'أ. سارة الخالد', 'total_hours' => 60, 'type' => 'مبتدئ'],
    3 => ['name' => 'هندسة البرمجيات الاحترافية', 'fees' => 500, 'trainer_name' => 'م. أحمد علي', 'total_hours' => 150, 'type' => 'متقدم'],
    4 => ['name' => 'تطوير تطبيقات الويب الكاملة', 'fees' => 400, 'trainer_name' => 'م. خالد الدوسري', 'total_hours' => 100, 'type' => 'متوسط'],
    5 => ['name' => 'علوم الحاسوب الأساسية', 'fees' => 300, 'trainer_name' => 'د. فاطمة النور', 'total_hours' => 80, 'type' => 'مبتدئ'],
    6 => ['name' => 'الخوارزميات وهياكل البيانات المتقدمة', 'fees' => 450, 'trainer_name' => 'د. يوسف المالكي', 'total_hours' => 120, 'type' => 'متقدم'],
    7 => ['name' => 'الذكاء الاصطناعي والتعلم الآلي', 'fees' => 550, 'trainer_name' => 'د. علي الحسين', 'total_hours' => 140, 'type' => 'متقدم'],
    8 => ['name' => 'معالجة اللغة الطبيعية', 'fees' => 480, 'trainer_name' => 'د. نورا السعيد', 'total_hours' => 110, 'type' => 'متوسط'],
    9 => ['name' => 'المحاسبة المالية المتقدمة', 'fees' => 350, 'trainer_name' => 'د. محمود العلي', 'total_hours' => 90, 'type' => 'متقدم'],
    10 => ['name' => 'أساسيات المحاسبة للمبتدئين', 'fees' => 200, 'trainer_name' => 'أ. لينا سلامة', 'total_hours' => 50, 'type' => 'مبتدئ'],
    11 => ['name' => 'المحاسبة الإدارية والتكاليف', 'fees' => 380, 'trainer_name' => 'د. سامي القاضي', 'total_hours' => 100, 'type' => 'متوسط'],
    12 => ['name' => 'إدارة الأعمال الاستراتيجية', 'fees' => 420, 'trainer_name' => 'د. ريم العبدالله', 'total_hours' => 110, 'type' => 'متقدم'],
    13 => ['name' => 'ريادة الأعمال وإدارة المشاريع', 'fees' => 400, 'trainer_name' => 'م. خالد المطيري', 'total_hours' => 95, 'type' => 'متوسط'],
    14 => ['name' => 'التسويق الرقمي والإلكتروني', 'fees' => 350, 'trainer_name' => 'أ. نورا الأحمد', 'total_hours' => 85, 'type' => 'متوسط'],
    15 => ['name' => 'القانون التجاري والشركات', 'fees' => 450, 'trainer_name' => 'د. فهد السالم', 'total_hours' => 120, 'type' => 'متقدم'],
    16 => ['name' => 'القانون المدني والعقود', 'fees' => 380, 'trainer_name' => 'د. منى الحسن', 'total_hours' => 100, 'type' => 'متوسط'],
];

if ($course_id > 0 && isset($courses_data[$course_id])) {
    $course = $courses_data[$course_id];
    $course['id'] = $course_id;
} else {
    if ($course_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $course = $stmt->fetch();
    }
}

if (!$course) {
    header("Location: index.html");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $national_id = trim($_POST['national_id'] ?? '');
    
    if ($name && $email && $phone && $national_id) {
        try {
            // Get course fees
            $course_fees = isset($course['fees']) ? $course['fees'] : (isset($course['total_amount']) ? $course['total_amount'] : 0);
            $course_name = isset($course['name']) ? $course['name'] : '';
            
            // Check if course exists in database, if not create it
            $stmt = $pdo->prepare("SELECT id FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $db_course = $stmt->fetch();
            
            $actual_course_id = $course_id;
            
            if (!$db_course && isset($course['name'])) {
                // Course doesn't exist in database, create it
                $stmt = $pdo->prepare("INSERT INTO courses (name, type, description, trainer_name, total_hours, start_date, end_date, days, session_duration, session_time, fees, trainer_fees) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                // Map Arabic course types to database enum values
                $arabic_type = isset($course['type']) ? $course['type'] : 'مبتدئ';
                $type = 'Short Course'; // Default to Short Course
                if (strpos($arabic_type, 'دبلوم') !== false || strpos($arabic_type, 'Diploma') !== false) {
                    $type = 'Diploma';
                }
                
                $trainer = isset($course['trainer_name']) ? $course['trainer_name'] : 'غير محدد';
                $hours = isset($course['total_hours']) ? $course['total_hours'] : 0;
                $start_date = date('Y-m-d', strtotime('+1 week'));
                $end_date = date('Y-m-d', strtotime('+3 months'));
                $days = 'السبت والأحد';
                $session_duration = '3 ساعات';
                $session_time = '10:00:00';
                $fees = $course_fees;
                $trainer_fees = 0;
                
                $stmt->execute([$course_name, $type, '', $trainer, $hours, $start_date, $end_date, $days, $session_duration, $session_time, $fees, $trainer_fees]);
                $actual_course_id = $pdo->lastInsertId();
            } else if ($db_course) {
                $actual_course_id = $db_course['id'];
            } else {
                // Course doesn't exist and we can't create it
                throw new PDOException("Course not found in database");
            }
            
            // Create pending enrollment
            $stmt = $pdo->prepare("INSERT INTO pending_enrollments (full_name, national_id, phone, email, course_id, course_name, course_fees, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
            $stmt->execute([$name, $national_id, $phone, $email, $actual_course_id, $course_name, $course_fees]);
            
            $success = 'تم إرسال طلب التسجيل بنجاح! سيتم التواصل معك من أجل إكمال اجراءات التسجيل.';
            $_POST = [];
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء إرسال طلب التسجيل. يرجى المحاولة مرة أخرى.';
            error_log("Enrollment error: " . $e->getMessage());
        }
    } else {
        $error = 'يرجى تعبئة جميع الحقول المطلوبة.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل في الدورة - <?= htmlspecialchars($course['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tajawal', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        .course-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        .checkout-form {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        .course-title {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .price {
            font-size: 2.5rem;
            font-weight: 900;
            color: #667eea;
            margin: 1rem 0;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="checkout-form">
            <h1>تسجيل في الدورة</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">الاسم الكامل *</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">البريد الإلكتروني *</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">رقم الهاتف *</label>
                    <input type="tel" id="phone" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="national_id">الرقم الوطني *</label>
                    <input type="text" id="national_id" name="national_id" required value="<?= htmlspecialchars($_POST['national_id'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn-submit">تأكيد التسجيل</button>
            </form>
        </div>
        
        <div class="course-summary">
            <div class="course-title"><?= htmlspecialchars($course['name']) ?></div>
            <div class="price"><?= number_format(isset($course['fees']) ? $course['fees'] : (isset($course['total_amount']) ? $course['total_amount'] : 0), 2) ?> د.أ</div>
            <p><strong>المدرب:</strong> <?= htmlspecialchars(isset($course['trainer_name']) ? $course['trainer_name'] : (isset($course['trainer']) ? $course['trainer'] : 'غير محدد')) ?></p>
            <p><strong>عدد الساعات:</strong> <?= isset($course['total_hours']) ? $course['total_hours'] : (isset($course['hours']) ? $course['hours'] : 'غير محدد') ?> ساعة</p>
            <p><strong>النوع:</strong> <?= htmlspecialchars(isset($course['type']) ? $course['type'] : 'غير محدد') ?></p>
            <a href="index.html" style="display: block; text-align: center; margin-top: 1rem; color: #667eea;">← العودة</a>
        </div>
    </div>
</body>
</html>

