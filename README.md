# Academy Platform - Educational Management System

A comprehensive educational platform for managing courses, students, enrollments, invoices, payments, and providing AI-powered course recommendations.

## Prerequisites

Before installing and running this project, ensure you have the following software installed:

### Required Software

1. **XAMPP** (Latest Version)
   - Download from: https://www.apachefriends.org/
   - Includes: Apache, MySQL, PHP, phpMyAdmin
   - Install XAMPP in the default location (usually `C:\xampp`)

2. **Python 3.11.7**
   - Download from: https://www.python.org/downloads/
   - During installation, check "Add Python to PATH"
   - Verify installation: `python --version` should show 3.11.7

3. **Git**
   - Download from: https://git-scm.com/downloads
   - Use default installation settings
   - Verify installation: `git --version`

## Installation Steps

### Step 1: Clone or Extract Project

```bash
# If using Git
git clone <repository-url>
cd Academy-platform

# Or extract the project folder to your desired location
```

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Start **Apache** service
3. Start **MySQL** service
4. Ensure both services show green "Running" status

### Step 3: Database Setup

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Create a new database named `sys_academy`
3. Import the database:
   - Click on `sys_academy` database
   - Go to "Import" tab
   - Choose file: `sys_academy_backup.sql`
   - Click "Go"

**OR** use the automatic setup:

1. Navigate to: http://localhost/Academy-platform/setup.php
2. Follow the setup wizard to initialize the database

### Step 4: Configure Database Connection

Edit `db_connection.php` if needed (default settings usually work):
- Host: `localhost`
- Database: `sys_academy`
- Username: `root`
- Password: (empty or your MySQL password)

### Step 5: Install PHP Dependencies

Open terminal/command prompt in the project directory:

```bash
cd Academy-platform
composer install
```

If Composer is not installed:
1. Download from: https://getcomposer.org/download/
2. Install Composer globally
3. Run `composer install` again

### Step 6: Install Python Dependencies

1. Create a virtual environment (recommended):

```bash
python -m venv venv
```

2. Activate virtual environment:

**Windows:**
```bash
venv\Scripts\activate
```

**Linux/Mac:**
```bash
source venv/bin/activate
```

3. Install required packages:

```bash
pip install -r requirements.txt
```

### Step 7: Configure Environment Variables

1. Create a `.env` file in the project root:

```env
OPENAI_API_KEY=your_openai_api_key_here
```

2. Get your OpenAI API key from: https://platform.openai.com/api-keys
3. Add the key to the `.env` file

### Step 8: Start the Chatbot API Server

**Windows:**
```bash
start_chatbot.bat
```

**Linux/Mac:**
```bash
python chatbot_api.py
```

The chatbot will run on: http://localhost:5000

### Step 9: Access the Application

1. Open your web browser
2. Navigate to: http://localhost/Academy-platform/
3. Default login credentials:
   - Username: `manager` / Password: `manager`
   - Username: `accountant` / Password: `accountant`
   - Username: `employee` / Password: `employee`

## Project Structure

```
Academy-platform/
├── assets/                 # Images and media files
├── tmp/                   # Temporary files (PDF generation)
├── uploads/               # Uploaded files
├── vendor/                # PHP Composer dependencies
├── venv/                  # Python virtual environment
├── accountant_dashboard.php  # Main dashboard
├── chatbot_api.py         # AI Chatbot API
├── db_connection.php      # Database configuration
├── index.html             # Public homepage
├── login.php              # Login page
├── requirements.txt       # Python dependencies
├── composer.json          # PHP dependencies
└── README.md             # This file
```

## Features

### For Administrators/Managers
- Course management (create, edit, delete)
- Student management
- Task assignment and tracking
- Financial reports and analytics
- PDF and Excel exports

### For Accountants
- Invoice management
- Payment processing
- Financial reports
- Student financial records
- Trainer payment tracking

### For Employees
- Task management
- Task submission
- Daily task tracking

### For Students (Public)
- Course browsing
- Course enrollment
- AI-powered course recommendations
- Chatbot assistance

## Troubleshooting

### Database Connection Issues
- Ensure MySQL is running in XAMPP
- Check `db_connection.php` credentials
- Verify database `sys_academy` exists

### PHP Errors
- Check PHP version (should be 8.2+)
- Ensure all Composer dependencies are installed
- Check `php_errors.log` for detailed errors

### Chatbot Not Working
- Verify Python 3.11.7 is installed
- Ensure chatbot API is running on port 5000
- Check `.env` file has valid OpenAI API key
- Verify all Python dependencies are installed

### PDF Export Issues
- Ensure `tmp/` folder has write permissions
- Check that mPDF library is properly installed via Composer

### Excel Export Issues
- Verify PhpSpreadsheet is installed via Composer
- Check file permissions for exports

## Default Accounts

After initial setup, these accounts are created:

| Role | Username | Password |
|------|----------|----------|
| Manager | manager | manager |
| Accountant | accountant | accountant |
| Employee | employee | employee |

**Important:** Change default passwords after first login!

## Support

For issues or questions:
1. Check the `php_errors.log` file for PHP errors
2. Check console output for Python/chatbot errors
3. Verify all prerequisites are correctly installed
4. Ensure all services (Apache, MySQL) are running

## License

This project is proprietary software. All rights reserved.

## Version

Current Version: 1.0.0

Last Updated: January 2026
