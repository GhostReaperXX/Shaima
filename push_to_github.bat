@echo off
echo ========================================
echo رفع المشروع على GitHub
echo ========================================
echo.
echo سيتم طلب اسم المستخدم وكلمة المرور منك
echo.
echo Username: ShaimaMaghaireh
echo Password: استخدم Personal Access Token (وليس كلمة المرور)
echo.
echo للحصول على Token:
echo 1. اذهب إلى: https://github.com/settings/tokens
echo 2. اضغط Generate new token ^(classic^)
echo 3. اختر الصلاحية: repo
echo 4. انسخ الـ Token واستخدمه كـ Password
echo.
pause
git push -u origin main
if %errorlevel% equ 0 (
    echo.
    echo ========================================
    echo تم رفع المشروع بنجاح!
    echo ========================================
) else (
    echo.
    echo ========================================
    echo فشل الرفع. تحقق من الـ credentials
    echo ========================================
)
pause
