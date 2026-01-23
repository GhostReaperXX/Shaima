<?php
session_start();
if (!isset($_SESSION['role'])) { 
    header("Location: login.php"); 
    exit; 
}

$ROLE = $_SESSION['role'];
if (!in_array($ROLE, ['manager','accountant','employee'], true)) { 
    http_response_code(403); 
    die('Access denied.'); 
}

header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer-when-downgrade");

require 'error_handler.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php';

$composer_loaded = false;
if (file_exists('vendor/autoload.php')) {
    try {
        // Suppress errors during autoload (platform_check.php now uses warnings, but still suppress for safety)
        $old_error_handler = set_error_handler(function($errno, $errstr, $errfile, $errline) {
            // Suppress platform check warnings/errors
            if (strpos($errfile, 'platform_check.php') !== false) {
                return true; // Suppress the error
            }
            return false; // Let other errors through
        }, E_ALL | E_STRICT);
        
        @require 'vendor/autoload.php';
        
        // Restore error handler
        restore_error_handler();
        
        if (class_exists('Mpdf\Mpdf') && class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            $composer_loaded = true;
        }
    } catch (Throwable $e) {
        error_log("Composer autoload failed: " . $e->getMessage());
        $composer_loaded = false;
        // Restore error handler if exception occurred
        restore_error_handler();
    }
}

if ($composer_loaded) {
    if (!class_exists('Mpdf\Mpdf') || !class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        $composer_loaded = false;
    }
}

if (empty($_SESSION['csrf'])) { 
    $_SESSION['csrf'] = bin2hex(random_bytes(16)); 
}

function csrf_field(){ 
    echo '<input type="hidden" name="csrf" value="'.htmlspecialchars($_SESSION['csrf']).'">'; 
}

function check_csrf(){ 
    if($_SERVER['REQUEST_METHOD']==='POST'){ 
        $t=$_POST['csrf']??''; 
        if(!$t||!hash_equals($_SESSION['csrf'],$t)){ 
            http_response_code(400); 
            die('CSRF validation failed.'); 
        } 
    } 
}

function h($v){ 
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); 
}

function is_manager(){ 
    return ($_SESSION['role']??'')==='manager'; 
}

function is_accountant(){ 
    return ($_SESSION['role']??'')==='accountant'; 
}

function is_employee(){ 
    return ($_SESSION['role']??'')==='employee'; 
}

function u_id(){ 
    return (int)($_SESSION['user_id']??0); 
}

function u_name(){ 
    return trim((string)($_SESSION['full_name']??'')); 
}

function hello_by_role(){ 
    return is_manager()?'مرحباً أيها المدير':(is_accountant()?'مرحباً أيها المحاسب':'مرحباً أيها الموظف'); 
}

$CURRENCY='د.أ'; 

function money_jod($n){ 
    return number_format((float)$n,2).' د.أ'; 
}

$flash=""; 

function set_flash($m){ 
    global $flash; 
    $flash=$m; 
}

function show_flash(){ 
    global $flash; 
    if($flash) echo "<div class='flash'>".h($flash)."</div>"; 
}

function safe_query_all(PDO $pdo,$sql,$params=[]){ 
    try{
        $st=$pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }catch(Throwable $e){
        return ['__error__'=>$e->getMessage()];
    } 
}

function safe_query_val(PDO $pdo,$sql,$params=[],$default=null){ 
    try{
        $st=$pdo->prepare($sql);
        $st->execute($params);
        $v=$st->fetchColumn();
        return ($v===false?$default:$v);
    }catch(Throwable $e){
        return $default;
    } 
}

function safe_exec(PDO $pdo,$sql,$params=[]){ 
    try{
        $st=$pdo->prepare($sql);
        return $st->execute($params);
    }catch(Throwable $e){ 
        set_flash('خطأ في قاعدة البيانات: '.$e->getMessage()); 
        return false; 
    } 
}

$upload_dir = __DIR__.'/uploads';
if (!is_dir($upload_dir)) { 
    @mkdir($upload_dir, 0775, true); 
}

$ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','txt','zip'];
$MAX_UPLOAD = 30*1024*1024;

function save_uploaded_files($field, $multi=true){
    global $upload_dir,$ALLOWED_EXT,$MAX_UPLOAD;
    $saved=[]; $errs=[];
    
    if ($multi) {
        if (empty($_FILES[$field]['name'])) return [$saved,$errs];
        $names=$_FILES[$field]['name']; 
        $tmps=$_FILES[$field]['tmp_name'];
        
        for($i=0;$i<count((array)$names);$i++){
            if (empty($tmps[$i])) continue;
            if (filesize($tmps[$i])>$MAX_UPLOAD){ 
                $errs[]='ملف أكبر من 30MB.'; 
                continue; 
            }
            
            $ext=strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
            if (!in_array($ext,$ALLOWED_EXT,true)){ 
                $errs[]='امتداد غير مسموح: '.$ext; 
                continue; 
            }
            
            $safe=preg_replace('/[^A-Za-z0-9\.\-_]/','_', basename($names[$i]));
            $fname='task_'.time().'_'.mt_rand(1000,9999).'_'.$safe; 
            $dest=$upload_dir.'/'.$fname;
            
            if (!move_uploaded_file($tmps[$i],$dest)){ 
                $errs[]='فشل رفع '.$safe; 
                continue; 
            }
            
            $saved[]='uploads/'.$fname;
        }
    } else {
        if (empty($_FILES[$field]['tmp_name'])) return [null,null];
        if (filesize($_FILES[$field]['tmp_name'])>$MAX_UPLOAD) return [null,'الملف أكبر من 30MB.'];
        
        $ext=strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
        if (!in_array($ext,$ALLOWED_EXT,true)) return [null,'امتداد غير مسموح.'];
        
        $safe=preg_replace('/[^A-Za-z0-9\.\-_]/','_', basename($_FILES[$field]['name']));
        $fname='deliver_'.time().'_'.mt_rand(1000,9999).'_'.$safe; 
        $dest=$upload_dir.'/'.$fname;
        
        if (!move_uploaded_file($_FILES[$field]['tmp_name'],$dest)) return [null,'فشل رفع الملف.'];
        return ['uploads/'.$fname, null];
    }
    
    return [$saved,$errs];
}

check_csrf();

if (is_accountant()) {
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_course'])){
        $name = trim($_POST['course_name'] ?? '');
        $type = $_POST['course_type'] ?? 'Short Course';
        $desc = trim($_POST['course_desc'] ?? '');
        $trainer = trim($_POST['trainer_name'] ?? '');
        $hours = (int)($_POST['total_hours'] ?? 0);
        $start = $_POST['start_date'] ?? '';
        $end = $_POST['end_date'] ?? '';
        $days = trim($_POST['days'] ?? '');
        $sd = trim($_POST['session_duration'] ?? '');
        $st = $_POST['session_time'] ?? '';
        $fees = (float)($_POST['fees'] ?? 0);
        $tf = (float)($_POST['trainer_fees'] ?? 0);
        
        if($name && $trainer && $hours>0 && $start && $end && $days && $sd && $st){
            $ok = safe_exec($pdo,"INSERT INTO courses (name,type,description,trainer_name,total_hours,start_date,end_date,days,session_duration,session_time,fees,trainer_fees) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$name,$type,$desc,$trainer,$hours,$start,$end,$days,$sd,$st,$fees,$tf]);
            
            if($ok){
                set_flash('تم إضافة الدورة/الدبلوم بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء إضافة الدورة.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة للدورة.');
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_course'])){
        $cid = (int)($_POST['edit_course_id'] ?? 0);
        $name = trim($_POST['edit_course_name'] ?? '');
        $type = $_POST['edit_course_type'] ?? '';
        $desc = trim($_POST['edit_course_desc'] ?? '');
        $trainer = trim($_POST['edit_trainer_name'] ?? '');
        $hours = (int)($_POST['edit_total_hours'] ?? 0);
        $start = $_POST['edit_start_date'] ?? '';
        $end = $_POST['edit_end_date'] ?? '';
        $days = trim($_POST['edit_days'] ?? '');
        $sd = trim($_POST['edit_session_duration'] ?? '');
        $st = $_POST['edit_session_time'] ?? '';
        $fees = (float)($_POST['edit_fees'] ?? 0);
        $tf = (float)($_POST['edit_trainer_fees'] ?? 0);
        
        if($cid && $name && $trainer && $hours>0 && $start && $end && $days && $sd && $st){
            $ok = safe_exec($pdo,"UPDATE courses SET name=?, type=?, description=?, trainer_name=?, total_hours=?, start_date=?, end_date=?, days=?, session_duration=?, session_time=?, fees=?, trainer_fees=? WHERE id=?",
                [$name,$type,$desc,$trainer,$hours,$start,$end,$days,$sd,$st,$fees,$tf,$cid]);
            
            if($ok){
                set_flash('تم تعديل الدورة/الدبلوم بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء تعديل الدورة.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة.');
        }
    }

    if (isset($_GET['delete_course'])){
        $cid = (int)$_GET['delete_course'];
        if($cid>0){
            safe_exec($pdo,"DELETE FROM courses WHERE id=?",[$cid]);
            set_flash('تم حذف الدورة/الدبلوم.');
            header("Location: accountant_dashboard.php#courses");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_student'])){
        $full_name = trim($_POST['student_name'] ?? '');
        $national_id = trim($_POST['student_nid'] ?? '');
        $nationality = trim($_POST['student_nationality'] ?? '');
        $specialization = trim($_POST['student_spec'] ?? '');
        $phone = trim($_POST['student_phone'] ?? '');
        $email = trim($_POST['student_email'] ?? '');
        $address = trim($_POST['student_address'] ?? '');
        
        if($full_name && $national_id && $nationality && $phone){
            $ok = safe_exec($pdo,"INSERT INTO students (full_name, national_id, nationality, specialization, phone, email, address) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$full_name, $national_id, $nationality, $specialization, $phone, $email, $address]);
            
            if($ok){
                set_flash('تم إضافة الطالب بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء إضافة الطالب.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة للطالب.');
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_student'])){
        $sid = (int)($_POST['edit_student_id'] ?? 0);
        $full_name = trim($_POST['edit_student_name'] ?? '');
        $national_id = trim($_POST['edit_student_nid'] ?? '');
        $nationality = trim($_POST['edit_student_nationality'] ?? '');
        $specialization = trim($_POST['edit_student_spec'] ?? '');
        $phone = trim($_POST['edit_student_phone'] ?? '');
        $email = trim($_POST['edit_student_email'] ?? '');
        $address = trim($_POST['edit_student_address'] ?? '');
        
        if($sid && $full_name && $national_id && $nationality && $phone){
            $ok = safe_exec($pdo,"UPDATE students SET full_name=?, national_id=?, nationality=?, specialization=?, phone=?, email=?, address=? WHERE id=?",
                [$full_name, $national_id, $nationality, $specialization, $phone, $email, $address, $sid]);
            
            if($ok){
                set_flash('تم تعديل بيانات الطالب بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء تعديل بيانات الطالب.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة.');
        }
    }

    if (isset($_GET['delete_student'])){
        $sid = (int)$_GET['delete_student'];
        if($sid>0){
            safe_exec($pdo,"DELETE FROM students WHERE id=?",[$sid]);
            set_flash('تم حذف الطالب.');
            header("Location: accountant_dashboard.php#students");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_invoice'])){
        $student_id = (int)($_POST['inv_student_id'] ?? 0);
        $course_id = (int)($_POST['inv_course_id'] ?? 0);
        $amount = (float)($_POST['inv_amount'] ?? 0);
        $due_date = $_POST['inv_due_date'] ?? '';
        
        if($student_id && $course_id && $amount && $due_date){
            $invoice_number = 'INV-' . time() . '-' . mt_rand(1000, 9999);
            
            $ok = safe_exec($pdo,"INSERT INTO invoices (student_id, course_id, invoice_number, total_amount, paid_amount, remaining_amount, due_date, status) VALUES (?, ?, ?, ?, 0, ?, ?, 'Unpaid')",
                [$student_id, $course_id, $invoice_number, $amount, $amount, $due_date]);
            
            if($ok){
                set_flash('تم إصدار الفاتورة بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء إصدار الفاتورة.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة للفاتورة.');
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_payment'])){
        $student_id = (int)($_POST['pay_student_id'] ?? 0);
        $course_id = (int)($_POST['pay_course_id'] ?? 0);
        $amount = (float)($_POST['pay_amount'] ?? 0);
        $method = $_POST['pay_method'] ?? '';
        $notes = trim($_POST['pay_notes'] ?? '');
        
        if($student_id && $course_id && $amount && $method){
            $ok = safe_exec($pdo,"INSERT INTO payments (student_id, course_id, amount, payment_date, payment_method, notes) VALUES (?, ?, ?, NOW(), ?, ?)",
                [$student_id, $course_id, $amount, $method, $notes]);
            
            if($ok){
                $stmt = $pdo->prepare("SELECT id, paid_amount, total_amount FROM invoices WHERE student_id=? AND course_id=? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$student_id, $course_id]);
                $invoice = $stmt->fetch();
                
                if($invoice){
                    $new_paid = $invoice['paid_amount'] + $amount;
                    $remaining = $invoice['total_amount'] - $new_paid;
                    $status = ($remaining <= 0) ? 'Paid' : (($new_paid > 0) ? 'Partial' : 'Unpaid');
                    
                    safe_exec($pdo,"UPDATE invoices SET paid_amount=?, remaining_amount=?, status=? WHERE id=?",
                        [$new_paid, $remaining, $status, $invoice['id']]);
                }
                
                set_flash('تم تسجيل الدفعة بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء تسجيل الدفعة.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة للدفعة.');
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_trainer_payment'])){
        $course_id = (int)($_POST['trainer_course_id'] ?? 0);
        $amount = (float)($_POST['trainer_pay_amount'] ?? 0);
        $method = $_POST['trainer_pay_method'] ?? '';
        $notes = trim($_POST['trainer_pay_notes'] ?? '');
        
        if($course_id && $amount && $method){
            $ok = safe_exec($pdo,"INSERT INTO trainer_payments (course_id, amount, payment_date, payment_method, notes) VALUES (?, ?, NOW(), ?, ?)",
                [$course_id, $amount, $method, $notes]);
            
            if($ok){
                $stmt = $pdo->prepare("SELECT trainer_paid FROM courses WHERE id=?");
                $stmt->execute([$course_id]);
                $current_paid = $stmt->fetchColumn();
                
                $new_paid = $current_paid + $amount;
                safe_exec($pdo,"UPDATE courses SET trainer_paid=? WHERE id=?", [$new_paid, $course_id]);
                
                set_flash('تم تسجيل دفعة المدرب بنجاح!');
            } else {
                set_flash('حدث خطأ أثناء تسجيل دفعة المدرب.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة لدفعة المدرب.');
        }
    }

    // Handle pending enrollment processing
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['process_enrollment'])){
        $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
        $action = $_POST['action'] ?? ''; // 'contacted' or 'completed'
        $notes = trim($_POST['notes'] ?? '');
        
        if ($enrollment_id > 0 && in_array($action, ['contacted', 'completed'])) {
            try {
                $pdo->beginTransaction();
                
                // Get pending enrollment
                $stmt = $pdo->prepare("SELECT * FROM pending_enrollments WHERE id = ? AND status IN ('Pending', 'Contacted')");
                $stmt->execute([$enrollment_id]);
                $enrollment = $stmt->fetch();
                
                if ($enrollment) {
                    if ($action === 'contacted') {
                        // Mark as contacted
                        $stmt = $pdo->prepare("UPDATE pending_enrollments SET status = 'Contacted', notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                        $user_id = $_SESSION['user_id'] ?? null;
                        $stmt->execute([$notes, $user_id, $enrollment_id]);
                        
                        set_flash('تم تحديث حالة الطلب: تم التواصل مع الطالب.');
                    } else if ($action === 'completed') {
                        // Complete enrollment: Create student, enrollment, and invoice
                        // Check if student already exists
                        $stmt = $pdo->prepare("SELECT id FROM students WHERE national_id = ? OR email = ?");
                        $stmt->execute([$enrollment['national_id'], $enrollment['email']]);
                        $existing_student = $stmt->fetch();
                        
                        if ($existing_student) {
                            $student_id = $existing_student['id'];
                            // Update student info if needed
                            $stmt = $pdo->prepare("UPDATE students SET full_name = ?, phone = ?, email = ? WHERE id = ?");
                            $stmt->execute([$enrollment['full_name'], $enrollment['phone'], $enrollment['email'], $student_id]);
                        } else {
                            // Create new student
                            $stmt = $pdo->prepare("INSERT INTO students (full_name, national_id, phone, email, created_at) VALUES (?, ?, ?, ?, NOW())");
                            $stmt->execute([$enrollment['full_name'], $enrollment['national_id'], $enrollment['phone'], $enrollment['email']]);
                            $student_id = $pdo->lastInsertId();
                        }
                        
                        // Check if enrollment already exists
                        $stmt = $pdo->prepare("SELECT id FROM student_courses WHERE student_id = ? AND course_id = ?");
                        $stmt->execute([$student_id, $enrollment['course_id']]);
                        $existing_enrollment = $stmt->fetch();
                        
                        if (!$existing_enrollment) {
                            // Create enrollment
                            $stmt = $pdo->prepare("INSERT INTO student_courses (student_id, course_id, enrollment_date) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$student_id, $enrollment['course_id']]);
                        }
                        
                        // Check if invoice already exists
                        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE student_id = ? AND course_id = ?");
                        $stmt->execute([$student_id, $enrollment['course_id']]);
                        $existing_invoice = $stmt->fetch();
                        
                        if (!$existing_invoice) {
                            // Create invoice
                            $invoice_number = 'INV-' . time() . '-' . mt_rand(1000, 9999);
                            $fees = $enrollment['course_fees'] ?? 0;
                            $stmt = $pdo->prepare("INSERT INTO invoices (student_id, course_id, invoice_number, total_amount, paid_amount, remaining_amount, due_date, status) VALUES (?, ?, ?, ?, 0, ?, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid')");
                            $stmt->execute([$student_id, $enrollment['course_id'], $invoice_number, $fees, $fees]);
                        } else {
                            $stmt = $pdo->prepare("SELECT invoice_number FROM invoices WHERE id = ?");
                            $stmt->execute([$existing_invoice['id']]);
                            $invoice_data = $stmt->fetch();
                            $invoice_number = $invoice_data['invoice_number'] ?? 'موجود مسبقاً';
                        }
                        
                        // Update pending enrollment status to Approved
                        $stmt = $pdo->prepare("UPDATE pending_enrollments SET status = 'Approved', notes = ?, processed_at = NOW(), processed_by = ? WHERE id = ?");
                        $user_id = $_SESSION['user_id'] ?? null;
                        $final_notes = $notes ? $notes . " | تم إنشاء الفاتورة رقم: $invoice_number" : "تم إنشاء الفاتورة رقم: $invoice_number";
                        $stmt->execute([$final_notes, $user_id, $enrollment_id]);
                        
                        set_flash("تم إنهاء عملية التسجيل والاجراءات المالية بنجاح! رقم الفاتورة: $invoice_number");
                    }
                    
                    $pdo->commit();
                } else {
                    set_flash('طلب التسجيل غير موجود أو تم معالجته مسبقاً.');
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                set_flash('حدث خطأ أثناء معالجة طلب التسجيل: ' . $e->getMessage());
            }
        }
    }

    if (isset($_GET['export_excel'])){
        if (!$composer_loaded) {
            die('Excel export requires Composer dependencies. Please install: composer install');
        }
        $type = $_GET['export_excel'];
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set default font to support Arabic
        $spreadsheet->getDefaultStyle()->getFont()->setName('Arial Unicode MS');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(11);
        
        switch($type){
            case 'courses':
                $data = safe_query_all($pdo, "SELECT * FROM courses ORDER BY id DESC");
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'اسم الدورة');
                $sheet->setCellValue('C1', 'النوع');
                $sheet->setCellValue('D1', 'المدرب');
                $sheet->setCellValue('E1', 'عدد الساعات');
                $sheet->setCellValue('F1', 'تاريخ البداية');
                $sheet->setCellValue('G1', 'تاريخ النهاية');
                $sheet->setCellValue('H1', 'الرسوم');
                
                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                ];
                $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
                
                $row = 2;
                foreach($data as $item){
                    $sheet->setCellValue('A'.$row, $item['id']);
                    $sheet->setCellValue('B'.$row, $item['name']);
                    $sheet->setCellValue('C'.$row, $item['type']);
                    $sheet->setCellValue('D'.$row, $item['trainer_name']);
                    $sheet->setCellValue('E'.$row, $item['total_hours']);
                    $sheet->setCellValue('F'.$row, $item['start_date']);
                    $sheet->setCellValue('G'.$row, $item['end_date']);
                    $sheet->setCellValue('H'.$row, $item['fees']);
                    // Set RTL alignment for Arabic text columns
                    $sheet->getStyle('B'.$row.':D'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $row++;
                }
                // Auto-size columns
                foreach(range('A','H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                break;
                
            case 'students':
                $data = safe_query_all($pdo, "SELECT * FROM students ORDER BY id DESC");
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'الاسم');
                $sheet->setCellValue('C1', 'الرقم الوطني');
                $sheet->setCellValue('D1', 'الجنسية');
                $sheet->setCellValue('E1', 'التخصص');
                $sheet->setCellValue('F1', 'الهاتف');
                $sheet->setCellValue('G1', 'البريد الإلكتروني');
                
                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                ];
                $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
                
                $row = 2;
                foreach($data as $item){
                    $sheet->setCellValue('A'.$row, $item['id']);
                    $sheet->setCellValue('B'.$row, $item['full_name'] ?? '');
                    $sheet->setCellValue('C'.$row, $item['national_id'] ?? '');
                    $sheet->setCellValue('D'.$row, $item['nationality'] ?? '');
                    $sheet->setCellValue('E'.$row, $item['specialization'] ?? '');
                    $sheet->setCellValue('F'.$row, $item['phone'] ?? '');
                    $sheet->setCellValue('G'.$row, $item['email'] ?? '');
                    // Set RTL alignment for Arabic text columns
                    $sheet->getStyle('B'.$row.':G'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $row++;
                }
                // Auto-size columns
                foreach(range('A','G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                break;
                
            case 'invoices':
                $data = safe_query_all($pdo, "SELECT i.*, s.full_name, c.name as course_name FROM invoices i LEFT JOIN students s ON i.student_id = s.id LEFT JOIN courses c ON i.course_id = c.id ORDER BY i.id DESC");
                $sheet->setCellValue('A1', 'رقم الفاتورة');
                $sheet->setCellValue('B1', 'الطالب');
                $sheet->setCellValue('C1', 'الدورة');
                $sheet->setCellValue('D1', 'المبلغ');
                $sheet->setCellValue('E1', 'المدفوع');
                $sheet->setCellValue('F1', 'المتبقي');
                $sheet->setCellValue('G1', 'تاريخ الاستحقاق');
                $sheet->setCellValue('H1', 'الحالة');
                
                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                ];
                $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
                
                $row = 2;
                foreach($data as $item){
                    $sheet->setCellValue('A'.$row, $item['invoice_number'] ?? '');
                    $sheet->setCellValue('B'.$row, $item['full_name'] ?? '');
                    $sheet->setCellValue('C'.$row, $item['course_name'] ?? '');
                    $sheet->setCellValue('D'.$row, $item['total_amount'] ?? 0);
                    $sheet->setCellValue('E'.$row, $item['paid_amount'] ?? 0);
                    $sheet->setCellValue('F'.$row, $item['remaining_amount'] ?? 0);
                    $sheet->setCellValue('G'.$row, $item['due_date'] ?? '');
                    $sheet->setCellValue('H'.$row, $item['status'] ?? '');
                    // Set RTL alignment for Arabic text columns
                    $sheet->getStyle('A'.$row.':C'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('H'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $row++;
                }
                // Auto-size columns
                foreach(range('A','H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                break;
                
            case 'payments':
                $data = safe_query_all($pdo, "SELECT p.*, s.full_name, c.name as course_name FROM payments p LEFT JOIN students s ON p.student_id = s.id LEFT JOIN courses c ON p.course_id = c.id ORDER BY p.id DESC");
                $sheet->setCellValue('A1', 'ID');
                $sheet->setCellValue('B1', 'الطالب');
                $sheet->setCellValue('C1', 'الدورة');
                $sheet->setCellValue('D1', 'المبلغ');
                $sheet->setCellValue('E1', 'تاريخ الدفع');
                $sheet->setCellValue('F1', 'طريقة الدفع');
                $sheet->setCellValue('G1', 'الحالة');
                $sheet->setCellValue('H1', 'ملاحظات');
                
                // Style header row
                $headerStyle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT]
                ];
                $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
                
                $row = 2;
                foreach($data as $item){
                    $sheet->setCellValue('A'.$row, $item['id'] ?? '');
                    $sheet->setCellValue('B'.$row, $item['full_name'] ?? '');
                    $sheet->setCellValue('C'.$row, $item['course_name'] ?? '');
                    $sheet->setCellValue('D'.$row, $item['amount'] ?? 0);
                    $sheet->setCellValue('E'.$row, $item['payment_date'] ?? '');
                    $sheet->setCellValue('F'.$row, $item['payment_method'] ?? '');
                    $sheet->setCellValue('G'.$row, $item['status'] ?? '');
                    $sheet->setCellValue('H'.$row, $item['notes'] ?? '');
                    // Set RTL alignment for Arabic text columns
                    $sheet->getStyle('B'.$row.':C'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle('F'.$row.':H'.$row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $row++;
                }
                // Auto-size columns
                foreach(range('A','H') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                break;
        }
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet; charset=utf-8');
        header('Content-Disposition: attachment;filename="'.$type.'_export.xlsx"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    if (isset($_GET['export_pdf'])){
        if (!$composer_loaded) {
            die('PDF export requires Composer dependencies. Please install: composer install');
        }
        $type = $_GET['export_pdf'];
        
        // Configure mPDF with proper Arabic font support
        $mpdfConfig = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => __DIR__ . '/tmp',
            'default_font' => 'dejavusans',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
            'margin_header' => 9,
            'margin_footer' => 9,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ];
        
        $mpdf = new \Mpdf\Mpdf($mpdfConfig);
        $mpdf->SetDirectionality('rtl');
        
        switch($type){
            case 'course_report':
                $course_id = (int)$_GET['course_id'];
                $course = safe_query_all($pdo, "SELECT * FROM courses WHERE id = ?", [$course_id]);
                $students = safe_query_all($pdo, "SELECT s.* FROM student_courses sc JOIN students s ON sc.student_id = s.id WHERE sc.course_id = ?", [$course_id]);
                
                if(!empty($course)){
                    $course = $course[0];
                    $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; direction: rtl; text-align: right; }
        h1 { text-align: center; color: #0f172a; margin-bottom: 20px; }
        h2 { color: #1e293b; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #334155; padding: 8px; text-align: right; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; }
        p { margin: 8px 0; }
        strong { color: #1e293b; }
    </style>
</head>
<body>';
                    $html .= "<h1>تقرير الدورة: " . htmlspecialchars($course['name'], ENT_QUOTES, 'UTF-8') . "</h1>";
                    $html .= "<p><strong>المدرب:</strong> " . htmlspecialchars($course['trainer_name'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>عدد الساعات:</strong> " . htmlspecialchars($course['total_hours'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>الفترة:</strong> من " . htmlspecialchars($course['start_date'], ENT_QUOTES, 'UTF-8') . " إلى " . htmlspecialchars($course['end_date'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>الرسوم:</strong> " . htmlspecialchars($course['fees'], ENT_QUOTES, 'UTF-8') . " د.أ</p>";
                    
                    if(!empty($students)){
                        $html .= "<h2>الطلاب المسجلون</h2>";
                        $html .= "<table>";
                        $html .= "<tr><th>الاسم</th><th>الهاتف</th><th>البريد الإلكتروني</th></tr>";
                        
                        foreach($students as $student){
                            $html .= "<tr>";
                            $html .= "<td>" . htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($student['phone'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($student['email'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "</tr>";
                        }
                        
                        $html .= "</table>";
                    }
                    
                    $html .= '</body></html>';
                    
                    $mpdf->WriteHTML($html);
                    $mpdf->Output("course_report_{$course_id}.pdf", "D");
                    exit;
                }
                break;
                
            case 'student_report':
                $student_id = (int)($_GET['student_id'] ?? 0);
                if ($student_id <= 0) {
                    die('Invalid student ID');
                }
                
                try {
                    $student = safe_query_all($pdo, "SELECT * FROM students WHERE id = ?", [$student_id]);
                    
                    if(empty($student)){
                        die('Student not found');
                    }
                    
                    $student = $student[0];
                    
                    // Get student courses
                    $student_courses = safe_query_all($pdo, 
                        "SELECT c.*, sc.enrollment_date 
                         FROM student_courses sc 
                         JOIN courses c ON sc.course_id = c.id 
                         WHERE sc.student_id = ? 
                         ORDER BY sc.enrollment_date DESC", 
                        [$student_id]);
                    
                    // Get student invoices
                    $invoices = safe_query_all($pdo, 
                        "SELECT * FROM invoices WHERE student_id = ? ORDER BY created_at DESC", 
                        [$student_id]);
                    
                    // Get student payments
                    $payments = safe_query_all($pdo, 
                        "SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC", 
                        [$student_id]);
                    
                    $html = '<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; direction: rtl; text-align: right; }
        h1 { text-align: center; color: #0f172a; margin-bottom: 20px; }
        h2 { color: #1e293b; margin-top: 20px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #334155; padding: 8px; text-align: right; }
        th { background-color: #0f172a; color: #ffffff; font-weight: bold; }
        p { margin: 8px 0; }
        strong { color: #1e293b; }
        .section { margin-bottom: 25px; }
    </style>
</head>
<body>';
                    $html .= "<h1>تقرير الطالب: " . htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') . "</h1>";
                    
                    $html .= '<div class="section">';
                    $html .= "<h2>المعلومات الشخصية</h2>";
                    $html .= "<p><strong>الاسم الكامل:</strong> " . htmlspecialchars($student['full_name'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>الرقم الوطني:</strong> " . htmlspecialchars($student['national_id'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>الجنسية:</strong> " . htmlspecialchars($student['nationality'] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>التخصص:</strong> " . htmlspecialchars($student['specialization'] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>الهاتف:</strong> " . htmlspecialchars($student['phone'], ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>البريد الإلكتروني:</strong> " . htmlspecialchars($student['email'] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= "<p><strong>العنوان:</strong> " . htmlspecialchars($student['address'] ?? '', ENT_QUOTES, 'UTF-8') . "</p>";
                    $html .= '</div>';
                    
                    if(!empty($student_courses)){
                        $html .= '<div class="section">';
                        $html .= "<h2>الدورات المسجل فيها</h2>";
                        $html .= "<table>";
                        $html .= "<tr><th>اسم الدورة</th><th>النوع</th><th>المدرب</th><th>عدد الساعات</th><th>الرسوم</th><th>تاريخ التسجيل</th></tr>";
                        
                        foreach($student_courses as $course){
                            $html .= "<tr>";
                            $html .= "<td>" . htmlspecialchars($course['name'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($course['type'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($course['trainer_name'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($course['total_hours'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($course['fees'], ENT_QUOTES, 'UTF-8') . " د.أ</td>";
                            $html .= "<td>" . htmlspecialchars($course['enrollment_date'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "</tr>";
                        }
                        
                        $html .= "</table>";
                        $html .= '</div>';
                    }
                    
                    if(!empty($invoices)){
                        $html .= '<div class="section">';
                        $html .= "<h2>الفواتير</h2>";
                        $html .= "<table>";
                        $html .= "<tr><th>رقم الفاتورة</th><th>الدورة</th><th>المبلغ الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>تاريخ الاستحقاق</th></tr>";
                        
                        foreach($invoices as $invoice){
                            $course_name = '';
                            $course_info = safe_query_all($pdo, "SELECT name FROM courses WHERE id = ?", [$invoice['course_id']]);
                            if(!empty($course_info)){
                                $course_name = $course_info[0]['name'];
                            }
                            
                            $html .= "<tr>";
                            $html .= "<td>" . htmlspecialchars($invoice['invoice_number'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($course_name, ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($invoice['total_amount'], ENT_QUOTES, 'UTF-8') . " د.أ</td>";
                            $html .= "<td>" . htmlspecialchars($invoice['paid_amount'], ENT_QUOTES, 'UTF-8') . " د.أ</td>";
                            $html .= "<td>" . htmlspecialchars($invoice['remaining_amount'], ENT_QUOTES, 'UTF-8') . " د.أ</td>";
                            $html .= "<td>" . htmlspecialchars($invoice['status'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($invoice['due_date'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "</tr>";
                        }
                        
                        $html .= "</table>";
                        $html .= '</div>';
                    }
                    
                    if(!empty($payments)){
                        $html .= '<div class="section">';
                        $html .= "<h2>المدفوعات</h2>";
                        $html .= "<table>";
                        $html .= "<tr><th>المبلغ</th><th>طريقة الدفع</th><th>تاريخ الدفع</th><th>الحالة</th><th>ملاحظات</th></tr>";
                        
                        foreach($payments as $payment){
                            $html .= "<tr>";
                            $html .= "<td>" . htmlspecialchars($payment['amount'], ENT_QUOTES, 'UTF-8') . " د.أ</td>";
                            $html .= "<td>" . htmlspecialchars($payment['payment_method'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($payment['payment_date'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($payment['status'], ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "<td>" . htmlspecialchars($payment['notes'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
                            $html .= "</tr>";
                        }
                        
                        $html .= "</table>";
                        $html .= '</div>';
                    }
                    
                    $html .= '</body></html>';
                    
                    $mpdf->WriteHTML($html);
                    $mpdf->Output("student_report_{$student_id}.pdf", "D");
                    exit;
                } catch (Exception $e) {
                    error_log("PDF Export Error: " . $e->getMessage());
                    die('Error generating PDF: ' . htmlspecialchars($e->getMessage()));
                }
                break;
        }
    }
}

if (is_manager()){
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_task'])){
        $title = trim($_POST['task_title'] ?? '');
        $desc = trim($_POST['task_desc'] ?? '');
        $empcode = trim($_POST['task_employee_code'] ?? '');
        $prio = $_POST['task_priority'] ?? 'Normal';
        $due = $_POST['task_due'] ?? null;
        $status = $_POST['task_status'] ?? 'Open';
        
        if($title && $desc && $empcode!=='' && in_array($prio,['Low','Normal','High','Urgent'],true) && in_array($status,['Open','In Progress','Done','Archived'],true)){
            $ok = safe_exec($pdo,"INSERT INTO tasks (title,description,employee_code,priority,due_date,status,created_by_user_id,created_at) VALUES (?,?,?,?,?,?,?,NOW())",
                [$title,$desc,$empcode,$prio,($due?:null),$status,u_id()]);
            
            if($ok){
                $task_id = (int)$pdo->lastInsertId();
                [$files,$errs] = save_uploaded_files('manager_files', true);
                foreach($files as $p){
                    safe_exec($pdo,"INSERT INTO task_files (task_id,file_path,uploaded_by_role,created_at) VALUES (?,?, 'manager', NOW())",[$task_id,$p]);
                }
                
                if(!empty($errs)) {
                    set_flash('تم إنشاء المهمة مع ملاحظات رفع: '.implode(' | ',$errs));
                } else {
                    set_flash('تم إنشاء المهمة.');
                }
            } else {
                set_flash('حدث خطأ أثناء إنشاء المهمة.');
            }
        } else {
            set_flash('يرجى تعبئة جميع البيانات المطلوبة للمهمة.');
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['edit_task'])){
        $tid = (int)($_POST['task_id'] ?? 0);
        $title = trim($_POST['task_title'] ?? '');
        $desc = trim($_POST['task_desc'] ?? '');
        $empcode = trim($_POST['task_employee_code'] ?? '');
        $prio = $_POST['task_priority'] ?? 'Normal';
        $due = $_POST['task_due'] ?? null;
        $status = $_POST['task_status'] ?? 'Open';
        
        if($tid>0 && $title && $desc && $empcode!=='' && in_array($prio,['Low','Normal','High','Urgent'],true) && in_array($status,['Open','In Progress','Done','Archived'],true)){
            $ok = safe_exec($pdo,"UPDATE tasks SET title=?,description=?,employee_code=?,priority=?,due_date=?,status=? WHERE id=? AND created_by_user_id=?",
                [$title,$desc,$empcode,$prio,($due?:null),$status,$tid,u_id()]);
            
            [$files,$errs] = save_uploaded_files('manager_files', true);
            foreach($files as $p){
                safe_exec($pdo,"INSERT INTO task_files (task_id,file_path,uploaded_by_role,created_at) VALUES (?,?, 'manager', NOW())",[$tid,$p]);
            }
            
            if($ok) {
                set_flash('تم تعديل المهمة.' . (!empty($errs)?' | ملاحظات رفع: '.implode(' | ',$errs):''));
            } else {
                set_flash('حدث خطأ أثناء تعديل المهمة.');
            }
        } else {
            set_flash('بيانات التعديل غير مكتملة.');
        }
    }

    if (isset($_GET['delete_task'])){
        $tid = (int)$_GET['delete_task'];
        if($tid>0){
            safe_exec($pdo,"DELETE FROM task_files WHERE task_id=?",[$tid]);
            safe_exec($pdo,"DELETE FROM task_submissions WHERE task_id=?",[$tid]);
            safe_exec($pdo,"DELETE FROM tasks WHERE id=? AND created_by_user_id=?",[$tid,u_id()]);
            set_flash('تم حذف المهمة.');
            header("Location: accountant_dashboard.php#tab-tasks");
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['decision_submission'])){
        $sid = (int)($_POST['submission_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        
        if($sid>0 && in_array($decision,['Approved','Rejected'],true)){
            safe_exec($pdo,"UPDATE task_submissions SET status=? WHERE id=?",[$decision,$sid]);
            set_flash('تم تحديث حالة التسليم.');
        }
    }
}

if (is_employee()){
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['set_emp_code'])){
        $code = trim($_POST['employee_code'] ?? '');
        if ($code===''){ 
            set_flash('الرجاء إدخال رقمك الوظيفي.'); 
        } else { 
            $_SESSION['emp_code_entered'] = $code; 
            set_flash('تم تعيين الرقم الوظيفي: '.h($code)); 
        }
    }
    
    if (isset($_GET['clear_emp_code'])){ 
        unset($_SESSION['emp_code_entered']); 
        set_flash('تم مسح الرقم الوظيفي المدخل.'); 
        header("Location: accountant_dashboard.php#tab-my-tasks"); 
        exit; 
    }

    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_task'])){
        $task_id = (int)($_POST['deliver_task_id'] ?? 0);
        $emp_code = trim($_POST['deliver_emp_code'] ?? '');
        $text = trim($_POST['deliver_text'] ?? '');
        $checked = isset($_POST['deliver_checked']) ? 1 : 0;
        $task = safe_query_all($pdo,"SELECT employee_code FROM tasks WHERE id=?",[$task_id]);
        $ok = false;
        
        if(!isset($task['__error__']) && !empty($task)){
            if (trim($task[0]['employee_code']) === $emp_code) {
                $ok = true;
            }
        }
        
        if (!$ok){ 
            set_flash('لا يمكنك تسليم هذه المهمة: الرقم الوظيفي لا يطابق المهمة.'); 
        } else {
            [$file_path,$err] = save_uploaded_files('deliver_file', false);
            if ($err){ 
                set_flash($err); 
            } else {
                safe_exec($pdo,"INSERT INTO task_submissions (task_id,employee_code,text_notes,file_path,checked,status,created_at) VALUES (?,?,?,?,?, 'Submitted', NOW())",
                    [$task_id,$emp_code,$text,$file_path,$checked]);
                set_flash('تم إرسال تسليمك.');
            }
        }
    }
}

$courses = $students = $trainer_payments = $invoices = $payments = [];
if (is_accountant() || is_manager()){
    $courses = safe_query_all($pdo,"SELECT * FROM courses ORDER BY id DESC LIMIT 400");
    $students = safe_query_all($pdo,"SELECT * FROM students ORDER BY id DESC LIMIT 800");
    $trainer_payments = safe_query_all($pdo,"SELECT tp.*, c.name AS course_name FROM trainer_payments tp LEFT JOIN courses c ON c.id=tp.course_id ORDER BY tp.id DESC LIMIT 400");
    $invoices = safe_query_all($pdo,"SELECT i.*, s.full_name, c.name AS course_name FROM invoices i LEFT JOIN students s ON s.id=i.student_id LEFT JOIN courses c ON c.id=i.course_id ORDER BY i.id DESC LIMIT 600");
    $payments = safe_query_all($pdo,"SELECT p.*, s.full_name, c.name AS course_name FROM payments p LEFT JOIN students s ON s.id=p.student_id LEFT JOIN courses c ON c.id=p.course_id ORDER BY p.id DESC LIMIT 600");
}

$tasks_manager = []; 
$taskAgg = []; 
$taskFiles = []; 
$submissions = [];

if (is_manager()){
    $tq = trim($_GET['tq'] ?? '');
    $tstatus = $_GET['tstatus'] ?? '';
    $tcode = trim($_GET['tcode'] ?? '');
    $tfrom = $_GET['tfrom'] ?? '';
    $tto = $_GET['tto'] ?? '';
    
    $where = "t.created_by_user_id=?";
    $par = [u_id()];
    
    if($tq !== ''){ 
        $where .= " AND (t.title LIKE ? OR t.description LIKE ?)"; 
        $par[] = "%$tq%"; 
        $par[] = "%$tq%"; 
    }
    
    if($tstatus !== '' && in_array($tstatus,['Open','In Progress','Done','Archived'],true)){ 
        $where .= " AND t.status=?"; 
        $par[] = $tstatus; 
    }
    
    if($tcode !== ''){ 
        $where .= " AND t.employee_code LIKE ?"; 
        $par[] = '%'.$tcode.'%'; 
    }
    
    if($tfrom){ 
        $where .= " AND (t.due_date IS NULL OR t.due_date>=?)"; 
        $par[] = $tfrom; 
    }
    
    if($tto){ 
        $where .= " AND (t.due_date IS NULL OR t.due_date<=?)"; 
        $par[] = $tto; 
    }

    $tasks_manager = safe_query_all($pdo,"SELECT t.* FROM tasks t WHERE $where ORDER BY t.id DESC LIMIT 800",$par);
    
    $agg = safe_query_all($pdo,"SELECT t.id, COUNT(ts.id) sub_count, MAX(ts.created_at) last_submit FROM tasks t LEFT JOIN task_submissions ts ON ts.task_id=t.id WHERE $where GROUP BY t.id",$par);
    if(!isset($agg['__error__'])) {
        foreach($agg as $a){ 
            $taskAgg[(int)$a['id']] = ['sub_count'=>(int)$a['sub_count'], 'last_submit'=>$a['last_submit']]; 
        }
    }
    
    $files = safe_query_all($pdo,"SELECT * FROM task_files WHERE task_id IN (SELECT id FROM tasks WHERE $where) ORDER BY id DESC",$par);
    if(!isset($files['__error__'])) {
        foreach($files as $f){ 
            $taskFiles[(int)$f['task_id']][] = $f; 
        }
    }

    $sf = "t.created_by_user_id=?";
    $sp = [u_id()];
    $sub_tid = trim($_GET['sub_tid'] ?? '');
    $sub_st = $_GET['sub_st'] ?? '';
    
    if($sub_tid !== '' && ctype_digit($sub_tid)){ 
        $sf .= " AND ts.task_id=?"; 
        $sp[] = (int)$sub_tid; 
    }
    
    if($sub_st !== '' && in_array($sub_st,['Submitted','Approved','Rejected'],true)){ 
        $sf .= " AND ts.status=?"; 
        $sp[] = $sub_st; 
    }
    
    $submissions = safe_query_all($pdo,"SELECT ts.*, t.title AS task_title FROM task_submissions ts JOIN tasks t ON t.id=ts.task_id WHERE $sf ORDER BY ts.id DESC LIMIT 800",$sp);
}

$emp_tasks = []; 
$emp_task_files = [];

if (is_employee()){
    $entered = trim($_SESSION['emp_code_entered'] ?? '');
    $key = trim($_GET['emp_code'] ?? '');
    $emp_code = $key !== '' ? $key : $entered;
    
    if ($emp_code !== ''){
        $emp_tasks = safe_query_all($pdo,"SELECT * FROM tasks WHERE employee_code=? ORDER BY id DESC LIMIT 600",[$emp_code]);
        
        if (!isset($emp_tasks['__error__']) && is_array($emp_tasks) && count($emp_tasks) > 0) {
            $ids = array_map(function($r) {
                return is_array($r) && isset($r['id']) ? (int)$r['id'] : 0;
            }, $emp_tasks);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            
            if ($ids){
                $in = implode(',', array_fill(0,count($ids),'?'));
                $emp_files = safe_query_all($pdo,"SELECT * FROM task_files WHERE task_id IN ($in) ORDER BY id DESC",$ids);
                
                if(!isset($emp_files['__error__']) && is_array($emp_files)) {
                    foreach($emp_files as $f){ 
                        if (is_array($f) && isset($f['task_id'])) {
                            $emp_task_files[(int)$f['task_id']][] = $f; 
                        }
                    }
                }
            }
        }
    }
}

$sum_total = (float)safe_query_val($pdo,"SELECT SUM(total_amount) FROM invoices",[],0);
$sum_paid = (float)safe_query_val($pdo,"SELECT SUM(paid_amount) FROM invoices",[],0);
$sum_out = max(0,$sum_total-$sum_paid);

$inv_by_status = []; 
try{
    $inv_by_status = $pdo->query("SELECT status, COUNT(*) c FROM invoices GROUP BY status")->fetchAll();
}catch(Throwable $e){}

$pay_monthly = []; 
try{
    $pay_monthly = array_reverse($pdo->query("SELECT DATE_FORMAT(payment_date,'%Y-%m') m, SUM(amount) s FROM payments GROUP BY m ORDER BY m DESC LIMIT 6")->fetchAll());
}catch(Throwable $e){}

$tasks_status_all = []; 
try{
    $tasks_status_all = $pdo->query("SELECT status, COUNT(*) c FROM tasks GROUP BY status")->fetchAll();
}catch(Throwable $e){}

$tasks_due_counts = ['over' => 0, 'soon' => 0, 'open' => 0];
try{
    $result = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date < CURDATE() AND status != 'Done' AND status != 'Archived'");
    if ($result) {
        $over = $result->fetchColumn();
        $tasks_due_counts['over'] = (int)($over ?: 0);
    }
    $result = $pdo->query("SELECT COUNT(*) FROM tasks WHERE due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'Done' AND status != 'Archived'");
    if ($result) {
        $soon = $result->fetchColumn();
        $tasks_due_counts['soon'] = (int)($soon ?: 0);
    }
    $result = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status IN ('Open', 'In Progress')");
    if ($result) {
        $open = $result->fetchColumn();
        $tasks_due_counts['open'] = (int)($open ?: 0);
    }
}catch(Throwable $e){
    $tasks_due_counts = ['over' => 0, 'soon' => 0, 'open' => 0];
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم | النظام المتكامل</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0b1220;
            --panel: #0f172a;
            --card: #0b1220;
            --muted: #9aa4b2;
            --ink: #e5e7eb;
            --brand: #3b82f6;
            --brand2: #22d3ee;
            --accent: #a78bfa;
            --ok: #10b981;
            --warn: #f59e0b;
            --danger: #ef4444;
            --stroke: #1f2937;
            --chip: #111827;
            --grad: linear-gradient(135deg, #0b1220 0%, #0f1b3a 40%, #1034a6 100%);
        }
        
        * {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            background: radial-gradient(1200px 500px at 10% -10%, rgba(59,130,246,.25), transparent), var(--bg);
            color: var(--ink);
            font-family: 'Tajawal', Arial, sans-serif;
        }
        
        a {
            color: #93c5fd;
            text-decoration: none;
        }
        
        header {
            padding: 24px 20px;
            background: radial-gradient(900px 260px at 90% -10%, rgba(34,211,238,.25), transparent),
                        radial-gradient(900px 260px at -10% 110%, rgba(167,139,250,.20), transparent),
                        var(--grad);
            border-bottom: 1px solid #1e293b;
        }
        
        header .title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        
        header h1 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 900;
            letter-spacing: .3px;
        }
        
        header .hello {
            font-weight: 900;
            letter-spacing: 1px;
            opacity: .9;
        }
        
        .container {
            max-width: 1400px;
            margin: 18px auto;
            padding: 0 14px;
        }
        
        .shell {
            background: linear-gradient(180deg, #0b1220, #0b1220 60%, #0f172a);
            border: 1px solid #1e293b;
            border-radius: 18px;
            box-shadow: 0 20px 40px rgba(0,0,0,.4);
            padding: 16px;
        }
        
        .flash {
            margin: 12px 0 16px;
            background: #052e1f;
            color: #86efac;
            border: 1px solid #166534;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }
        
        .tabbtn {
            background: linear-gradient(180deg, #0f172a, #0b1220);
            border: 1px solid #1e293b;
            color: #e5e7eb;
            padding: 10px 14px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 900;
            transition: 150ms;
        }
        
        .tabbtn:hover {
            transform: translateY(-1px);
            border-color: #334155;
        }
        
        .tabbtn.active {
            background: linear-gradient(180deg, #1034a6, #0f1b3a);
            color: #fff;
            border-color: #1034a6;
        }
        
        .tabpane {
            display: none;
        }
        
        .tabpane.active {
            display: block;
            animation: fade .25s;
        }
        
        @keyframes fade {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        
        @media (max-width: 1000px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
        
        .card {
            border: 1px solid #1e293b;
            border-radius: 16px;
            padding: 14px;
            background: linear-gradient(180deg, #0f172a, #0b1220);
        }
        
        .card h3 {
            margin: 0 0 10px 0;
            color: #fff;
            font-size: 1.15rem;
        }
        
        .kpi {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }
        
        @media (max-width: 1100px) {
            .kpi {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .kpi .card {
            background: linear-gradient(180deg, #0b1220, #0f172a);
        }
        
        .kv {
            font-size: 1.9em;
            font-weight: 900;
        }
        
        .kd {
            font-size: .9em;
            color: #9aa4b2;
        }
        
        .btn {
            background: linear-gradient(90deg, #2563eb, #22d3ee);
            color: #0b1220;
            border: none;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 900;
        }
        
        .btn:hover {
            filter: brightness(1.08);
        }
        
        .btn.outline {
            background: transparent;
            color: #93c5fd;
            border: 1px solid #2563eb;
        }
        
        .btn.secondary {
            background: #334155;
            color: #e5e7eb;
        }
        
        .btn.danger {
            background: #ef4444;
            color: #fff;
        }
        
        input[type=text], input[type=number], input[type=email], input[type=date], input[type=time], textarea, select {
            width: 100%;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #243244;
            background: #0b1220;
            color: #e5e7eb;
            font-family: inherit;
        }
        
        label {
            font-weight: 800;
            color: #e5e7eb;
            margin-bottom: 6px;
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            border-radius: 12px;
            overflow: hidden;
        }
        
        th, td {
            border: 1px solid #1e293b;
            padding: 10px;
            text-align: center;
        }
        
        th {
            background: #0f1b3a;
            color: #c7d2fe;
        }
        
        tr:nth-child(even) {
            background: #0b1220;
        }
        
        .small {
            font-size: .92em;
            color: #9aa4b2;
        }
        
        .actions a {
            color: #fda4af;
            text-decoration: none;
            font-weight: 800;
        }
        
        .split {
            display: grid;
            grid-template-columns: minmax(380px, 1fr) 2fr;
            gap: 14px;
        }
        
        @media (max-width: 1100px) {
            .split {
                grid-template-columns: 1fr;
            }
        }
        
        .tag {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .85em;
            font-weight: 900;
        }
        
        .tag.open {
            background: #0e7490;
            color: #ecfeff;
        }
        
        .tag.prog {
            background: #854d0e;
            color: #fff7ed;
        }
        
        .tag.done {
            background: #14532d;
            color: #dcfce7;
        }
        
        .tag.arch {
            background: #334155;
            color: #e2e8f0;
        }
        
        .hr {
            height: 1px;
            background: #1e293b;
            margin: 10px 0;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #111827;
            border: 1px solid #374151;
            font-weight: 900;
            color: #cbd5e1;
        }
        
        .filter-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 8px 0;
            background: #0f1b3a;
            border: 1px solid #1e293b;
            padding: 10px;
            border-radius: 12px;
        }
        
        .chart-card {
            padding: 18px;
        }
        
        .chart-wrap {
            position: relative;
            height: 300px;
        }
        
        .notice {
            margin: 6px 0;
            color: #e5e7eb;
            background: #0f1b3a;
            border: 1px solid #1e293b;
            padding: 8px 12px;
            border-radius: 10px;
        }
        
        .row-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
            align-items: center;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-section {
            background: #0f172a;
            padding: 15px;
            border-radius: 10px;
            border: 1px solid #1e293b;
        }
        
        .export-options {
            display: flex;
            gap: 10px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        
        .export-btn {
            background: #10b981;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .export-btn:hover {
            background: #059669;
        }
    </style>
</head>
<body>
    <header>
        <div class="title">
            <h1>لوحة النظام الداخلية</h1>
            <div class="hello"><?=h(hello_by_role())?></div>
            <div><a href="logout.php" style="color:#fde68a;font-weight:900;">تسجيل الخروج</a></div>
        </div>
    </header>

    <div class="container">
        <div class="shell">
            <?php show_flash(); ?>

            <nav class="tabs">
                <button class="tabbtn active" data-tab="tab-analytics">لوحة الرصد والتحليلات</button>
                <?php if (is_accountant()): ?>
                    <button class="tabbtn" data-tab="tab-pending-enrollments">طلبات التسجيل المعلقة</button>
                    <button class="tabbtn" data-tab="tab-courses">الدورات</button>
                    <button class="tabbtn" data-tab="tab-students">الطلاب</button>
                    <button class="tabbtn" data-tab="tab-invoices">الفواتير</button>
                    <button class="tabbtn" data-tab="tab-payments">مدفوعات الطلاب</button>
                    <button class="tabbtn" data-tab="tab-trainer-payments">مدفوعات المدربين</button>
                    <button class="tabbtn" data-tab="tab-tools">الاستيراد/التصدير</button>
                <?php elseif (is_manager()): ?>
                    <button class="tabbtn" data-tab="tab-tasks">المهام (مدير)</button>
                    <button class="tabbtn" data-tab="tab-submissions">تسليمات المهام</button>
                <?php else: ?>
                    <button class="tabbtn" data-tab="tab-my-tasks">مهامي برقم الموظف</button>
                <?php endif; ?>
            </nav>

            <!-- ========== Pending Enrollments ========== -->
            <?php if (is_accountant()): ?>
            <section id="tab-pending-enrollments" class="tabpane">
                <div class="card">
                    <h3>طلبات التسجيل المعلقة</h3>
                    <?php
                    $pending_enrollments = safe_query_all($pdo, "
                        SELECT pe.*, c.name as course_name_display 
                        FROM pending_enrollments pe 
                        LEFT JOIN courses c ON pe.course_id = c.id 
                        WHERE pe.status IN ('Pending', 'Contacted')
                        ORDER BY pe.created_at DESC
                    ");
                    ?>
                    <?php if (!empty($pending_enrollments)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الهاتف</th>
                                <th>الرقم الوطني</th>
                                <th>الدورة</th>
                                <th>الرسوم</th>
                                <th>تاريخ الطلب</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_enrollments as $enrollment): ?>
                            <tr>
                                <td><?= h($enrollment['full_name']) ?></td>
                                <td><?= h($enrollment['email']) ?></td>
                                <td><?= h($enrollment['phone']) ?></td>
                                <td><?= h($enrollment['national_id']) ?></td>
                                <td><?= h($enrollment['course_name_display'] ?: $enrollment['course_name']) ?></td>
                                <td><?= h(money_jod($enrollment['course_fees'] ?? 0)) ?></td>
                                <td><?= h(date('Y-m-d H:i', strtotime($enrollment['created_at']))) ?></td>
                                <td>
                                    <?php if ($enrollment['status'] == 'Pending'): ?>
                                        <span style="color: #f59e0b;">معلق</span>
                                    <?php elseif ($enrollment['status'] == 'Contacted'): ?>
                                        <span style="color: #3b82f6;">تم التواصل</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($enrollment['status'] == 'Pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من تحديث حالة الطلب إلى "تم التواصل"؟');">
                                            <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                            <input type="hidden" name="action" value="contacted">
                                            <textarea name="notes" placeholder="ملاحظات (اختياري)" style="width:200px;height:60px;margin-bottom:5px;"></textarea><br>
                                            <button type="submit" name="process_enrollment" class="btn" style="background:#3b82f6;">✓ تم التواصل</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($enrollment['status'] == 'Contacted'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من إنهاء عملية التسجيل والاجراءات المالية؟ سيتم إنشاء حساب الطالب والفاتورة تلقائياً.');">
                                            <input type="hidden" name="enrollment_id" value="<?= $enrollment['id'] ?>">
                                            <input type="hidden" name="action" value="completed">
                                            <textarea name="notes" placeholder="ملاحظات (اختياري)" style="width:200px;height:60px;margin-bottom:5px;"></textarea><br>
                                            <button type="submit" name="process_enrollment" class="btn" style="background:#10b981;">✓ إنهاء عملية التسجيل والاجراءات المالية</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="notice">لا توجد طلبات تسجيل معلقة</p>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- ========== Analytics ========== -->
            <section id="tab-analytics" class="tabpane active">
                <div class="kpi">
                    <div class="card"><h3>إجمالي الفواتير</h3><div class="kv"><?=h(money_jod($sum_total))?></div><div class="kd">المُصدَرة</div></div>
                    <div class="card"><h3>المحصّل</h3><div class="kv"><?=h(money_jod($sum_paid))?></div><div class="kd">مدفوع</div></div>
                    <div class="card"><h3>المتبقي</h3><div class="kv"><?=h(money_jod($sum_out))?></div><div class="kd">غير مسدد</div></div>
                    <div class="card"><h3>المهام المفتوحة</h3><div class="kv"><?=h($tasks_due_counts['open'] ?? 0)?></div><div class="kd">Open/In Progress</div></div>
                </div>

                <div class="grid">
                    <div class="card chart-card"><h3>الفواتير حسب الحالة</h3><div class="chart-wrap"><canvas id="chInvStatus"></canvas></div></div>
                    <div class="card chart-card"><h3>مدفوعات 6 أشهر (<?=$CURRENCY?>)</h3><div class="chart-wrap"><canvas id="chPayMonthly"></canvas></div></div>
                    <div class="card chart-card"><h3>المهام حسب الحالة</h3><div class="chart-wrap"><canvas id="chTasksStatus"></canvas></div></div>
                    <div class="card chart-card"><h3>المهام المتأخرة / القريبة</h3><div class="chart-wrap"><canvas id="chTasksDue"></canvas></div></div>
                </div>
            </section>

            <!-- ========== المحاسبة: الدورات ========== -->
            <?php if (is_accountant()): ?>
            <section id="tab-courses" class="tabpane">
                <div class="card">
                    <h3>إدارة الدورات والبرامج</h3>
                    
                    <div class="form-section">
                        <h4>إضافة دورة جديدة</h4>
                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <div>
                                <label>اسم الدورة/البرنامج</label>
                                <input type="text" name="course_name" required>
                            </div>
                            <div>
                                <label>نوع الدورة</label>
                                <select name="course_type" required>
                                    <option value="Short Course">دورة قصيرة</option>
                                    <option value="Diploma">دبلوم</option>
                                    <option value="Workshop">ورشة عمل</option>
                                </select>
                            </div>
                            <div>
                                <label>اسم المدرب</label>
                                <input type="text" name="trainer_name" required>
                            </div>
                            <div>
                                <label>عدد الساعات</label>
                                <input type="number" name="total_hours" min="1" required>
                            </div>
                            <div>
                                <label>تاريخ البدء</label>
                                <input type="date" name="start_date" required>
                            </div>
                            <div>
                                <label>تاريخ الانتهاء</label>
                                <input type="date" name="end_date" required>
                            </div>
                            <div>
                                <label>أيام الانعقاد</label>
                                <input type="text" name="days" placeholder="مثال: السبت، الاثنين، الأربعاء" required>
                            </div>
                            <div>
                                <label>مدة الحصة (ساعات)</label>
                                <input type="text" name="session_duration" required>
                            </div>
                            <div>
                                <label>وقت الحصة</label>
                                <input type="time" name="session_time" required>
                            </div>
                            <div>
                                <label>رسوم الدورة (د.أ)</label>
                                <input type="number" step="0.01" name="fees" required>
                            </div>
                            <div>
                                <label>أجر المدرب (د.أ)</label>
                                <input type="number" step="0.01" name="trainer_fees" required>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label>وصف الدورة</label>
                                <textarea name="course_desc" rows="3"></textarea>
                            </div>
                            <div>
                                <button class="btn" type="submit" name="add_course">إضافة الدورة</button>
                            </div>
                        </form>
                    </div>

                    <div class="filter-bar">
                        <input type="text" placeholder="بحث في الدورات..." id="courseSearch">
                        <button class="btn" onclick="filterCourses()">بحث</button>
                    </div>

                    <table id="coursesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم الدورة</th>
                                <th>النوع</th>
                                <th>المدرب</th>
                                <th>الساعات</th>
                                <th>الفترة</th>
                                <th>الرسوم</th>
                                <th>أجر المدرب</th>
                                <th>المدفوع</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($courses['__error__'])): ?>
                                <tr><td colspan="10" class="error"><?= h($courses['__error__']) ?></td></tr>
                            <?php elseif (is_array($courses) && count($courses) > 0): ?>
                            <?php foreach($courses as $course): ?>
                            <?php if (!is_array($course)) continue; ?>
                            <tr>
                                <td><?= isset($course['id']) ? $course['id'] : '' ?></td>
                                <td><?= h($course['name'] ?? '') ?></td>
                                <td><?= h($course['type'] ?? '') ?></td>
                                <td><?= h($course['trainer_name'] ?? '') ?></td>
                                <td><?= $course['total_hours'] ?? 0 ?></td>
                                <td><?= ($course['start_date'] ?? '') ?> إلى <?= ($course['end_date'] ?? '') ?></td>
                                <td><?= money_jod($course['fees'] ?? 0) ?></td>
                                <td><?= money_jod($course['trainer_fees'] ?? 0) ?></td>
                                <td><?= money_jod($course['trainer_paid'] ?? 0) ?></td>
                                <td class="row-actions">
                                    <a href="?export_pdf=course_report&course_id=<?= $course['id'] ?? 0 ?>" class="btn outline small">PDF</a>
                                    <button class="btn outline small" onclick="editCourse(<?= $course['id'] ?? 0 ?>)">تعديل</button>
                                    <a href="?delete_course=<?= $course['id'] ?? 0 ?>" onclick="return confirm('هل أنت متأكد من حذف هذه الدورة؟')" class="btn danger small">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="notice">لا توجد دورات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========== المحاسبة: الطلاب ========== -->
            <section id="tab-students" class="tabpane">
                <div class="card">
                    <h3>إدارة الطلاب</h3>
                    
                    <div class="form-section">
                        <h4>إضافة طالب جديد</h4>
                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <div>
                                <label>الاسم الكامل</label>
                                <input type="text" name="student_name" required>
                            </div>
                            <div>
                                <label>الرقم الوطني</label>
                                <input type="text" name="student_nid" required>
                            </div>
                            <div>
                                <label>الجنسية</label>
                                <input type="text" name="student_nationality" required>
                            </div>
                            <div>
                                <label>التخصص</label>
                                <input type="text" name="student_spec">
                            </div>
                            <div>
                                <label>رقم الهاتف</label>
                                <input type="text" name="student_phone" required>
                            </div>
                            <div>
                                <label>البريد الإلكتروني</label>
                                <input type="email" name="student_email">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label>العنوان</label>
                                <textarea name="student_address" rows="2"></textarea>
                            </div>
                            <div>
                                <button class="btn" type="submit" name="add_student">إضافة الطالب</button>
                            </div>
                        </form>
                    </div>

                    <div class="filter-bar">
                        <input type="text" placeholder="بحث في الطلاب..." id="studentSearch">
                        <button class="btn" onclick="filterStudents()">بحث</button>
                    </div>

                    <table id="studentsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الاسم</th>
                                <th>الرقم الوطني</th>
                                <th>الجنسية</th>
                                <th>التخصص</th>
                                <th>الهاتف</th>
                                <th>البريد</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($students['__error__'])): ?>
                                <tr><td colspan="8" class="error"><?= h($students['__error__']) ?></td></tr>
                            <?php elseif (is_array($students) && count($students) > 0): ?>
                            <?php foreach($students as $student): ?>
                            <?php if (!is_array($student)) continue; ?>
                            <tr>
                                <td><?= isset($student['id']) ? $student['id'] : '' ?></td>
                                <td><?= h($student['full_name'] ?? '') ?></td>
                                <td><?= h($student['national_id'] ?? '') ?></td>
                                <td><?= h($student['nationality'] ?? '') ?></td>
                                <td><?= h($student['specialization'] ?? '') ?></td>
                                <td><?= h($student['phone'] ?? '') ?></td>
                                <td><?= h($student['email'] ?? '') ?></td>
                                <td class="row-actions">
                                    <?php if ($composer_loaded): ?>
                                    <a href="?export_pdf=student_report&student_id=<?= $student['id'] ?? 0 ?>" class="btn outline small">PDF</a>
                                    <?php endif; ?>
                                    <button class="btn outline small" onclick="editStudent(<?= $student['id'] ?? 0 ?>)">تعديل</button>
                                    <a href="?delete_student=<?= $student['id'] ?? 0 ?>" onclick="return confirm('هل أنت متأكد من حذف هذا الطالب؟')" class="btn danger small">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="notice">لا توجد طلاب</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========== المحاسبة: الفواتير ========== -->
            <section id="tab-invoices" class="tabpane">
                <div class="card">
                    <h3>إدارة الفواتير</h3>
                    
                    <div class="form-section">
                        <h4>إصدار فاتورة جديدة</h4>
                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <div>
                                <label>الطالب</label>
                                <select name="inv_student_id" required>
                                    <option value="">اختر الطالب</option>
                                    <?php if (is_array($students) && !isset($students['__error__'])): ?>
                                    <?php foreach($students as $student): ?>
                                    <?php if (!is_array($student)) continue; ?>
                                    <option value="<?= $student['id'] ?? 0 ?>"><?= h($student['full_name'] ?? '') ?> (<?= h($student['national_id'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label>الدورة</label>
                                <select name="inv_course_id" required>
                                    <option value="">اختر الدورة</option>
                                    <?php if (is_array($courses) && !isset($courses['__error__'])): ?>
                                    <?php foreach($courses as $course): ?>
                                    <?php if (!is_array($course)) continue; ?>
                                    <option value="<?= $course['id'] ?? 0 ?>"><?= h($course['name'] ?? '') ?> (<?= h($course['trainer_name'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label>المبلغ (د.أ)</label>
                                <input type="number" step="0.01" name="inv_amount" required>
                            </div>
                            <div>
                                <label>تاريخ الاستحقاق</label>
                                <input type="date" name="inv_due_date" required>
                            </div>
                            <div>
                                <button class="btn" type="submit" name="add_invoice">إصدار الفاتورة</button>
                            </div>
                        </form>
                    </div>

                    <div class="export-options">
                        <a href="?export_excel=invoices" class="export-btn">تصدير إلى Excel</a>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>رقم الفاتورة</th>
                                <th>الطالب</th>
                                <th>الدورة</th>
                                <th>المبلغ</th>
                                <th>المدفوع</th>
                                <th>المتبقي</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($invoices['__error__'])): ?>
                                <tr><td colspan="9" class="error"><?= h($invoices['__error__']) ?></td></tr>
                            <?php elseif (is_array($invoices) && count($invoices) > 0): ?>
                            <?php foreach($invoices as $invoice): ?>
                            <?php if (!is_array($invoice)) continue; ?>
                            <tr>
                                <td><?= h($invoice['invoice_number'] ?? '') ?></td>
                                <td><?= h($invoice['full_name'] ?? '') ?></td>
                                <td><?= h($invoice['course_name'] ?? '') ?></td>
                                <td><?= money_jod($invoice['total_amount'] ?? 0) ?></td>
                                <td><?= money_jod($invoice['paid_amount'] ?? 0) ?></td>
                                <td><?= money_jod($invoice['remaining_amount'] ?? 0) ?></td>
                                <td><?= $invoice['due_date'] ?? '' ?></td>
                                <td>
                                    <span class="badge">
                                        <?= h($invoice['status'] ?? '') ?>
                                    </span>
                                </td>
                                <td class="row-actions">
                                    <a href="#" class="btn outline small">تعديل</a>
                                    <a href="?delete_invoice=<?= $invoice['id'] ?? 0 ?>" onclick="return confirm('هل أنت متأكد من حذف هذه الفاتورة؟')" class="btn danger small">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="notice">لا توجد فواتير</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========== المحاسبة: مدفوعات الطلاب ========== -->
            <section id="tab-payments" class="tabpane">
                <div class="card">
                    <h3>مدفوعات الطلاب</h3>
                    
                    <div class="form-section">
                        <h4>تسجيل دفعة جديدة</h4>
                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <div>
                                <label>الطالب</label>
                                <select name="pay_student_id" required>
                                    <option value="">اختر الطالب</option>
                                    <?php if (is_array($students) && !isset($students['__error__'])): ?>
                                    <?php foreach($students as $student): ?>
                                    <?php if (!is_array($student)) continue; ?>
                                    <option value="<?= $student['id'] ?? 0 ?>"><?= h($student['full_name'] ?? '') ?> (<?= h($student['national_id'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label>الدورة</label>
                                <select name="pay_course_id" required>
                                    <option value="">اختر الدورة</option>
                                    <?php if (is_array($courses) && !isset($courses['__error__'])): ?>
                                    <?php foreach($courses as $course): ?>
                                    <?php if (!is_array($course)) continue; ?>
                                    <option value="<?= $course['id'] ?? 0 ?>"><?= h($course['name'] ?? '') ?> (<?= h($course['trainer_name'] ?? '') ?>)</option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label>المبلغ (د.أ)</label>
                                <input type="number" step="0.01" name="pay_amount" required>
                            </div>
                            <div>
                                <label>طريقة الدفع</label>
                                <select name="pay_method" required>
                                    <option value="نقدي">نقدي</option>
                                    <option value="تحويل بنكي">تحويل بنكي</option>
                                    <option value="بطاقة ائتمان">بطاقة ائتمان</option>
                                </select>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label>ملاحظات</label>
                                <textarea name="pay_notes" rows="2"></textarea>
                            </div>
                            <div>
                                <button class="btn" type="submit" name="add_payment">تسجيل الدفعة</button>
                            </div>
                        </form>
                    </div>

                    <div class="export-options">
                        <a href="?export_excel=payments" class="export-btn">تصدير إلى Excel</a>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الطالب</th>
                                <th>الدورة</th>
                                <th>المبلغ</th>
                                <th>تاريخ الدفع</th>
                                <th>طريقة الدفع</th>
                                <th>ملاحظات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($payments['__error__'])): ?>
                                <tr><td colspan="8" class="error"><?= h($payments['__error__']) ?></td></tr>
                            <?php elseif (is_array($payments) && count($payments) > 0): ?>
                            <?php foreach($payments as $payment): ?>
                            <?php if (!is_array($payment)) continue; ?>
                            <tr>
                                <td><?= $payment['id'] ?? '' ?></td>
                                <td><?= h($payment['full_name'] ?? '') ?></td>
                                <td><?= h($payment['course_name'] ?? '') ?></td>
                                <td><?= money_jod($payment['amount'] ?? 0) ?></td>
                                <td><?= $payment['payment_date'] ?? '' ?></td>
                                <td><?= h($payment['payment_method'] ?? '') ?></td>
                                <td><?= h($payment['notes'] ?? '') ?></td>
                                <td class="row-actions">
                                    <a href="?delete_payment=<?= $payment['id'] ?? 0 ?>" onclick="return confirm('هل أنت متأكد من حذف هذه الدفعة؟')" class="btn danger small">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="notice">لا توجد مدفوعات</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========== المحاسبة: مدفوعات المدربين ========== -->
            <section id="tab-trainer-payments" class="tabpane">
                <div class="card">
                    <h3>مدفوعات المدربين</h3>
                    
                    <div class="form-section">
                        <h4>تسجيل دفعة للمدرب</h4>
                        <form method="post" class="form-grid">
                            <?php csrf_field(); ?>
                            <div>
                                <label>الدورة</label>
                                <select name="trainer_course_id" required>
                                    <option value="">اختر الدورة</option>
                                    <?php if (is_array($courses) && !isset($courses['__error__'])): ?>
                                    <?php foreach($courses as $course): ?>
                                    <?php if (!is_array($course)) continue; ?>
                                    <option value="<?= $course['id'] ?? 0 ?>"><?= h($course['name'] ?? '') ?> - <?= h($course['trainer_name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label>المبلغ (د.أ)</label>
                                <input type="number" step="0.01" name="trainer_pay_amount" required>
                            </div>
                            <div>
                                <label>طريقة الدفع</label>
                                <select name="trainer_pay_method" required>
                                    <option value="نقدي">نقدي</option>
                                    <option value="تحويل بنكي">تحويل بنكي</option>
                                </select>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label>ملاحظات</label>
                                <textarea name="trainer_pay_notes" rows="2"></textarea>
                            </div>
                            <div>
                                <button class="btn" type="submit" name="add_trainer_payment">تسجيل الدفعة</button>
                            </div>
                        </form>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الدورة</th>
                                <th>المدرب</th>
                                <th>المبلغ</th>
                                <th>تاريخ الدفع</th>
                                <th>طريقة الدفع</th>
                                <th>ملاحظات</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($trainer_payments['__error__'])): ?>
                                <tr><td colspan="6" class="error"><?= h($trainer_payments['__error__']) ?></td></tr>
                            <?php elseif (is_array($trainer_payments) && count($trainer_payments) > 0): ?>
                            <?php foreach($trainer_payments as $tp): ?>
                            <?php if (!is_array($tp)) continue; ?>
                            <tr>
                                <td><?= $tp['id'] ?? '' ?></td>
                                <td><?= h($tp['course_name'] ?? '') ?></td>
                                <td>
                                    <?php 
                                    $course_trainer = safe_query_val($pdo, "SELECT trainer_name FROM courses WHERE id = ?", [$tp['course_id'] ?? 0], '');
                                    echo h($course_trainer);
                                    ?>
                                </td>
                                <td><?= money_jod($tp['amount'] ?? 0) ?></td>
                                <td><?= $tp['payment_date'] ?? '' ?></td>
                                <td><?= h($tp['payment_method'] ?? '') ?></td>
                                <td><?= h($tp['notes'] ?? '') ?></td>
                                <td class="row-actions">
                                    <a href="?delete_trainer_payment=<?= $tp['id'] ?? 0 ?>" onclick="return confirm('هل أنت متأكد من حذف هذه الدفعة؟')" class="btn danger small">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="notice">لا توجد مدفوعات للمدربين</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ========== المحاسبة: أدوات التصدير ========== -->
            <section id="tab-tools" class="tabpane">
                <div class="card">
                    <h3>أدوات الاستيراد والتصدير</h3>
                    
                    <div class="form-section">
                        <h4>تصدير البيانات</h4>
                        <div class="export-options">
                            <a href="?export_excel=courses" class="export-btn">تصدير الدورات إلى Excel</a>
                            <a href="?export_excel=students" class="export-btn">تصدير الطلاب إلى Excel</a>
                            <a href="?export_excel=invoices" class="export-btn">تصدير الفواتير إلى Excel</a>
                        </div>
                    </div>

                    <div class="form-section">
                        <h4>تقارير PDF</h4>
                        <div class="form-grid">
                            <div>
                                <label>اختر دورة لتقرير PDF</label>
                                <select id="courseForPdf">
                                    <option value="">اختر الدورة</option>
                                    <?php if (is_array($courses) && !isset($courses['__error__'])): ?>
                                    <?php foreach($courses as $course): ?>
                                    <?php if (!is_array($course)) continue; ?>
                                    <option value="<?= $course['id'] ?? 0 ?>"><?= h($course['name'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <button class="btn" onclick="generateCourseReport()">إنشاء تقرير PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- ========== المدير: المهام ========== -->
            <?php if (is_manager()): ?>
            <section id="tab-tasks" class="tabpane">
                <div class="split">
                    <div class="card">
                        <h3>إنشاء/تعديل مهمة (بالرقم الوظيفي)</h3>
                        <form method="post" id="taskForm" enctype="multipart/form-data">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="task_id" id="task_id">
                            <div class="grid">
                                <div>
                                    <label>الرقم الوظيفي للموظف</label>
                                    <input type="text" name="task_employee_code" id="task_employee_code" placeholder="مثال: EMP-10023" required>
                                </div>
                                <div>
                                    <label>العنوان</label>
                                    <input type="text" name="task_title" id="task_title" required>
                                </div>
                                <div>
                                    <label>الأولوية</label>
                                    <select name="task_priority" id="task_priority">
                                        <option value="Low">منخفض</option>
                                        <option value="Normal" selected>عادي</option>
                                        <option value="High">مرتفع</option>
                                        <option value="Urgent">عاجل</option>
                                    </select>
                                </div>
                                <div>
                                    <label>تاريخ الاستحقاق</label>
                                    <input type="date" name="task_due" id="task_due">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label>الوصف / ملاحظات إدارية</label>
                                    <textarea name="task_desc" id="task_desc" rows="4" required></textarea>
                                </div>
                                <div style="grid-column:1/-1">
                                    <label>الملفات المرفقة (اختياري، متعددة)</label>
                                    <input type="file" name="manager_files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt,.zip">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label>الحالة</label>
                                    <select name="task_status" id="task_status">
                                        <option value="Open">مفتوحة</option>
                                        <option value="In Progress">قيد التنفيذ</option>
                                        <option value="Done">مكتملة</option>
                                        <option value="Archived">مؤرشفة</option>
                                    </select>
                                </div>
                            </div>
                            <div class="hr"></div>
                            <div class="row-actions">
                                <button class="btn" type="submit" name="add_task" id="btn_add">إضافة مهمة</button>
                                <button class="btn secondary" type="submit" name="edit_task" id="btn_edit" style="display:none">حفظ التعديلات</button>
                                <button class="btn outline" type="button" onclick="resetTaskForm()">تفريغ الحقول</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <h3>المهام التي أنشأتها</h3>
                        <form method="get" class="filter-bar">
                            <div><label>بحث</label><input type="text" name="tq" value="<?=h($_GET['tq']??'')?>" placeholder="عنوان/وصف"></div>
                            <div>
                                <label>الحالة</label>
                                <select name="tstatus">
                                    <option value="">الكل</option>
                                    <?php foreach(['Open','In Progress','Done','Archived'] as $st): ?>
                                    <option value="<?=$st?>" <?=(($_GET['tstatus']??'')===$st?'selected':'')?>><?=$st?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div><label>الرقم الوظيفي</label><input type="text" name="tcode" value="<?=h($_GET['tcode']??'')?>" placeholder="EMP-..."></div>
                            <div><label>من</label><input type="date" name="tfrom" value="<?=h($_GET['tfrom']??'')?>"></div>
                            <div><label>إلى</label><input type="date" name="tto" value="<?=h($_GET['tto']??'')?>"></div>
                            <div style="align-self:end"><button class="btn" type="submit">تطبيق</button></div>
                        </form>

                        <?php if (isset($tasks_manager['__error__'])): ?>
                            <div class="small badge">تعذّر قراءة المهام: <?=h($tasks_manager['__error__'])?></div>
                        <?php else: ?>
                        <table>
                            <tr>
                                <th>#</th>
                                <th>الرقم الوظيفي</th>
                                <th>العنوان</th>
                                <th>أولوية</th>
                                <th>استحقاق</th>
                                <th>حالة</th>
                                <th>مرفقات</th>
                                <th>تسليمات</th>
                                <th>آخر تسليم</th>
                                <th>إجراءات</th>
                            </tr>
                            <?php foreach($tasks_manager as $t):
                                $tid = (int)$t['id']; 
                                $s = $t['status'];
                                $cls = ($s==='Open'?'open':($s==='In Progress'?'prog':($s==='Done'?'done':'arch')));
                                $files = $taskFiles[$tid] ?? [];
                                $agg = $taskAgg[$tid] ?? ['sub_count'=>0, 'last_submit'=>'-'];
                            ?>
                            <tr>
                                <td><?=h($tid)?></td>
                                <td><span class="badge"><?=h($t['employee_code'])?></span></td>
                                <td style="text-align:right">
                                    <?=h($t['title'])?>
                                    <div class="small" style="color:#cbd5e1"><?=nl2br(h($t['description']))?></div>
                                </td>
                                <td><?=h($t['priority'])?></td>
                                <td><?=h($t['due_date'])?></td>
                                <td><span class="tag <?=$cls?>"><?=h($s)?></span></td>
                                <td>
                                    <?php if($files): foreach($files as $f): ?>
                                        <a class="btn outline small" href="<?=h($f['file_path'])?>" target="_blank">تحميل</a>
                                    <?php endforeach; else: ?>-<?php endif; ?>
                                </td>
                                <td><?=h($agg['sub_count'])?></td>
                                <td><?=h($agg['last_submit']?:'-')?></td>
                                <td class="row-actions">
                                    <button class="btn outline small" onclick='fillTaskForm(<?=json_encode([
                                        'id'=>$t['id'],
                                        'code'=>$t['employee_code'],
                                        'title'=>$t['title'],
                                        'desc'=>$t['description'],
                                        'prio'=>$t['priority'],
                                        'due'=>$t['due_date'],
                                        'status'=>$t['status']
                                    ], JSON_UNESCAPED_UNICODE)?>)'>تعديل</button>
                                    <a href="?delete_task=<?=h($t['id'])?>" class="small" onclick="return confirm('حذف المهمة وكل مرفقاتها وتسليماتها؟')">حذف</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section id="tab-submissions" class="tabpane">
                <div class="card">
                    <h3>تسليمات الموظفين</h3>
                    <form method="get" class="filter-bar">
                        <div><label>رقم المهمة</label><input type="text" name="sub_tid" value="<?=h($_GET['sub_tid']??'')?>"></div>
                        <div>
                            <label>الحالة</label>
                            <select name="sub_st">
                                <option value="">الكل</option>
                                <?php foreach(['Submitted','Approved','Rejected'] as $st): ?>
                                <option value="<?=$st?>" <?=(($_GET['sub_st']??'')===$st?'selected':'')?>><?=$st?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="align-self:end"><button class="btn" type="submit">تطبيق</button></div>
                    </form>
                    
                    <?php if (isset($submissions['__error__'])): ?>
                        <div class="small badge">تعذّر قراءة التسليمات: <?=h($submissions['__error__'])?></div>
                    <?php else: ?>
                    <table>
                        <tr>
                            <th>#</th>
                            <th>المهمة</th>
                            <th>رقم الموظف</th>
                            <th>تاريخ الإرسال</th>
                            <th>حالة</th>
                            <th>النص</th>
                            <th>ملف</th>
                            <th>تم التحقق</th>
                            <th>قرار</th>
                        </tr>
                        <?php foreach($submissions as $s): ?>
                        <tr>
                            <td><?=h($s['id'])?></td>
                            <td><?=h($s['task_id'])?> - <?=h($s['task_title']??'')?></td>
                            <td><span class="badge"><?=h($s['employee_code'])?></span></td>
                            <td><?=h($s['created_at'])?></td>
                            <td><span class="badge"><?=h($s['status'])?></span></td>
                            <td style="max-width:420px;text-align:right"><?=nl2br(h($s['text_notes']??''))?></td>
                            <td>
                                <?php if(!empty($s['file_path'])): ?>
                                    <a class="btn outline small" href="<?=h($s['file_path'])?>" target="_blank">تحميل</a>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td><?=!empty($s['checked'])?'✓':''?></td>
                            <td>
                                <form method="post" class="row-actions">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="submission_id" value="<?=h($s['id'])?>">
                                    <button class="btn" name="decision_submission" value="1" onclick="this.form.decision.value='Approved'">اعتماد</button>
                                    <button class="btn danger" name="decision_submission" value="1" onclick="this.form.decision.value='Rejected'">رفض</button>
                                    <input type="hidden" name="decision" value="">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- ========== الموظف: مهامي ========== -->
            <?php if (is_employee()): ?>
            <section id="tab-my-tasks" class="tabpane">
                <div class="grid">
                    <div class="card">
                        <h3>أدخل رقمك الوظيفي لرؤية مهامك</h3>
                        <form method="post" class="small">
                            <?php csrf_field(); ?>
                            <div style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
                                <div style="flex:1">
                                    <label>رقمي الوظيفي</label>
                                    <input type="text" name="employee_code" placeholder="مثال: EMP-10023" value="<?=h($_SESSION['emp_code_entered'] ?? '')?>" required>
                                </div>
                                <button class="btn" type="submit" name="set_emp_code">اعتماد الرقم</button>
                                <a class="btn outline" href="?clear_emp_code=1">مسح</a>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <h3>مهامي</h3>
                        <form method="get" class="filter-bar">
                            <div>
                                <label>أدخل الرقم الوظيفي</label>
                                <input type="text" name="emp_code" value="<?=h($_GET['emp_code'] ?? '')?>">
                            </div>
                            <div style="align-self:end">
                                <button class="btn" type="submit">عرض</button>
                            </div>
                        </form>

                        <?php if (isset($emp_tasks['__error__'])): ?>
                            <div class="small badge">تعذّر جلب مهامك: <?=h($emp_tasks['__error__'])?></div>
                        <?php elseif (empty($emp_tasks)): ?>
                            <div class="small notice">لا توجد مهام لهذا الرقم.</div>
                        <?php else: ?>
                        <table>
                            <tr>
                                <th>#</th>
                                <th>العنوان</th>
                                <th>الاستحقاق</th>
                                <th>الأولوية</th>
                                <th>الحالة</th>
                                <th>الوصف/ملاحظات</th>
                                <th>ملفات من المدير</th>
                            </tr>
                            <?php foreach($emp_tasks as $t): 
                                $tid = (int)$t['id']; 
                                $files = $emp_task_files[$tid] ?? []; 
                            ?>
                            <tr>
                                <td><?=h($tid)?></td>
                                <td style="text-align:right"><?=h($t['title'])?></td>
                                <td><?=h($t['due_date'])?></td>
                                <td><?=h($t['priority'])?></td>
                                <td><?=h($t['status'])?></td>
                                <td style="text-align:right;max-width:420px"><?=nl2br(h($t['description']))?></td>
                                <td>
                                    <?php if($files): foreach($files as $f): ?>
                                        <a class="btn outline small" href="<?=h($f['file_path'])?>" target="_blank">تحميل</a>
                                    <?php endforeach; else: ?>-<?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3>تسليم مهمة</h3>
                        <form method="post" enctype="multipart/form-data">
                            <?php csrf_field(); ?>
                            <div class="grid">
                                <div>
                                    <label>رقم المهمة</label>
                                    <input type="number" name="deliver_task_id" required>
                                </div>
                                <div>
                                    <label>رقمي الوظيفي</label>
                                    <input type="text" name="deliver_emp_code" value="<?=h($_SESSION['emp_code_entered'] ?? ($_GET['emp_code'] ?? ''))?>" required readonly style="background:#1e293b;color:#cbd5e1">
                                </div>
                                <div style="grid-column:1/-1">
                                    <label>نص/ملاحظات</label>
                                    <textarea name="deliver_text" rows="4" placeholder="اكتب تفاصيل التسليم"></textarea>
                                </div>
                                <div>
                                    <label>مرفق (اختياري)</label>
                                    <input type="file" name="deliver_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.txt,.zip">
                                </div>
                                <div>
                                    <label><input type="checkbox" name="deliver_checked"> تم الإنجاز</label>
                                </div>
                            </div>
                            <div class="hr"></div>
                            <button class="btn" type="submit" name="submit_task">إرسال التسليم</button>
                            <p class="small" style="margin-top:8px">الحد الأقصى 30MB</p>
                        </form>
                    </div>
                </div>
            </section>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const tabs = [...document.querySelectorAll('.tabbtn')];
        const panes = [...document.querySelectorAll('.tabpane')];
        
        tabs.forEach(b => {
            b.addEventListener('click', () => {
                tabs.forEach(x => x.classList.remove('active'));
                panes.forEach(p => p.classList.remove('active'));
                b.classList.add('active');
                const id = b.dataset.tab;
                const el = document.getElementById(id);
                if (el) el.classList.add('active');
                if (history.pushState) {
                    history.replaceState(null, null, '#' + id);
                }
            });
        });
        
        (function(){
            const h = location.hash.replace('#', '');
            if (h) {
                const b = document.querySelector('.tabbtn[data-tab="' + h + '"]');
                if (b) b.click();
            }
        })();
        
        const invByStatus = <?=json_encode(is_array($inv_by_status) ? array_map(function($r){ return is_array($r) ? ['label'=>$r['status']??'', 'value'=>(int)($r['c']??0)] : ['label'=>'', 'value'=>0]; }, $inv_by_status) : [], JSON_UNESCAPED_UNICODE)?>;
        const payMonthly = <?=json_encode(is_array($pay_monthly) ? array_map(function($r){ return is_array($r) ? ['label'=>$r['m']??'', 'value'=>(float)($r['s']??0)] : ['label'=>'', 'value'=>0]; }, $pay_monthly) : [], JSON_UNESCAPED_UNICODE)?>;
        const tasksByStatus = <?=json_encode(is_array($tasks_status_all) ? array_map(function($r){ return is_array($r) ? ['label'=>$r['status']??'', 'value'=>(int)($r['c']??0)] : ['label'=>'', 'value'=>0]; }, $tasks_status_all) : [], JSON_UNESCAPED_UNICODE)?>;
        const tasksDue = <?=json_encode([['label'=>'متأخرة','value'=>(int)($tasks_due_counts['over']??0)],['label'=>'قريبة (≤7 أيام)','value'=>(int)($tasks_due_counts['soon']??0)]], JSON_UNESCAPED_UNICODE)?>;
        
        function makeBar(id, labels, values){
            const ctx = document.getElementById(id);
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '',
                        data: values,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    animation: {
                        duration: 700
                    }
                }
            });
        }
        
        function makePie(id, labels, values){
            const ctx = document.getElementById(id);
            if (!ctx) return;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    animation: {
                        duration: 700
                    }
                }
            });
        }
        
        makePie('chInvStatus', invByStatus.map(x => x.label), invByStatus.map(x => x.value));
        makeBar('chPayMonthly', payMonthly.map(x => x.label), payMonthly.map(x => x.value));
        makePie('chTasksStatus', tasksByStatus.map(x => x.label), tasksByStatus.map(x => x.value));
        makeBar('chTasksDue', tasksDue.map(x => x.label), tasksDue.map(x => x.value));
        
        function fillTaskForm(d){
            const m = id => document.getElementById(id);
            m('task_id').value = d.id || '';
            m('task_employee_code').value = d.code || '';
            m('task_title').value = d.title || '';
            m('task_desc').value = d.desc || '';
            m('task_priority').value = d.prio || 'Normal';
            m('task_due').value = d.due || '';
            m('task_status').value = d.status || 'Open';
            
            document.getElementById('btn_add').style.display = 'none';
            document.getElementById('btn_edit').style.display = 'inline-block';
        }
        
        function resetTaskForm(){
            ['task_id', 'task_employee_code', 'task_title', 'task_desc', 'task_due'].forEach(i => {
                const e = document.getElementById(i);
                if (e) e.value = '';
            });
            
            const p = document.getElementById('task_priority');
            if (p) p.value = 'Normal';
            
            const s = document.getElementById('task_status');
            if (s) s.value = 'Open';
            
            document.getElementById('btn_add').style.display = 'inline-block';
            document.getElementById('btn_edit').style.display = 'none';
        }
        
        function filterCourses(){
            const search = document.getElementById('courseSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#coursesTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }
        
        function filterStudents(){
            const search = document.getElementById('studentSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(search) ? '' : 'none';
            });
        }
        
        function generateCourseReport(){
            const courseId = document.getElementById('courseForPdf').value;
            if (courseId) {
                window.open(`?export_pdf=course_report&course_id=${courseId}`, '_blank');
            } else {
                alert('يرجى اختيار دورة أولاً');
            }
        }
        
        function editCourse(id){
            alert('ميزة التعديل قيد التطوير. سيتم تفعيلها قريباً.');
        }
        
        function editStudent(id){
            alert('ميزة التعديل قيد التطوير. سيتم تفعيلها قريباً.');
        }
    </script>
</body>
</html>
