# Academy Platform - Technical Documentation

## Project Title
**Academy Platform - Educational Management System**

---

## Professional Introduction

The Academy Platform is a comprehensive web-based educational management system designed for training academies and educational institutions. The platform serves as a centralized solution for managing courses, student enrollments, financial operations, and internal task management workflows.

**Target Users:**
- **Administrators/Managers**: Full system oversight, course management, and task assignment
- **Accountants**: Financial management including invoicing, payment processing, and reporting
- **Employees**: Task management and submission workflows
- **Students (Public)**: Course browsing, enrollment, and AI-powered course recommendations

**Problem Solved:**
The platform addresses the need for an integrated system that combines course management, student enrollment workflows, financial tracking, and internal collaboration tools. It eliminates the need for multiple disconnected systems by providing a unified platform with role-based access control, automated financial calculations, and AI-powered student assistance.

---

## High-Level Architecture Overview

### Architecture Pattern
The system follows a **multi-tier architecture** with clear separation between presentation, business logic, and data layers:

- **Frontend Layer**: Static HTML/CSS/JavaScript (`index.html`) for public-facing pages, PHP-rendered pages for authenticated areas
- **Backend Layer**: PHP server-side processing (`accountant_dashboard.php`, `login.php`, etc.) and Python Flask API (`chatbot_api.py`)
- **Database Layer**: MySQL relational database (`sys_academy`) with 13 interconnected tables
- **Integration Layer**: OpenAI API integration for AI chatbot functionality

### Communication Flow
1. **Public Users** → `index.html` (static) → `checkout.php` (enrollment) → Database
2. **Authenticated Users** → `login.php` → Session Management → `accountant_dashboard.php` → Database
3. **Chatbot Requests** → `index.html` (JavaScript) → `chatbot_api.py` (Flask API) → OpenAI API → Response

### Technology Stack Distribution
- **PHP**: Core business logic, authentication, database operations, PDF/Excel generation
- **Python**: AI chatbot service (separate microservice)
- **MySQL**: Primary data storage
- **JavaScript**: Frontend interactivity, chatbot integration, dynamic UI updates

---

## Technologies Used

### Backend Technologies

#### PHP 8.2+
- **Purpose**: Primary server-side language for web application logic
- **Usage**: 
  - Authentication and session management (`login.php`, lines 2-50)
  - Database operations via PDO (`db_connection.php`, lines 8-12)
  - Business logic and CRUD operations (`accountant_dashboard.php`, throughout)
  - PDF generation (`download_syllabus.php`, lines 38-1080)
  - Excel export (`accountant_dashboard.php`, lines 514-690)
- **Why**: Native web server support, extensive library ecosystem, strong database integration

#### Python 3.11.7
- **Purpose**: AI chatbot microservice
- **Usage**: Flask REST API (`chatbot_api.py`, lines 1-745)
- **Why**: Strong AI/ML library support, easy integration with OpenAI API

#### MySQL 8.0+
- **Purpose**: Relational database for all persistent data
- **Usage**: All application data storage (13 tables)
- **Why**: ACID compliance, strong foreign key support, excellent PHP integration

### Frontend Technologies

#### HTML5
- **Purpose**: Structure and semantic markup
- **Usage**: Public homepage (`index.html`, lines 1-4064), authenticated dashboard (`accountant_dashboard.php`, HTML sections)
- **Why**: Modern web standards, accessibility support

#### CSS3
- **Purpose**: Styling and responsive design
- **Usage**: Inline styles in PHP files, embedded styles in `index.html` (lines 12-3300+)
- **Features**: CSS Grid, Flexbox, CSS Variables, animations, responsive breakpoints
- **Why**: Modern layout capabilities, no external dependencies

#### JavaScript (Vanilla ES6+)
- **Purpose**: Client-side interactivity and API communication
- **Usage**: 
  - Course filtering and rendering (`index.html`, lines 3702-3743)
  - Chatbot integration (`index.html`, lines 3764-3912)
  - Language switching (`index.html`, lines 3387-3409)
  - Dynamic UI updates
- **Why**: No framework overhead, direct DOM manipulation, full control

### Libraries and Frameworks

#### Composer Dependencies (PHP)

**mPDF 8.2** (`composer.json`, line 4)
- **Purpose**: PDF generation for course syllabi and financial reports
- **Usage**: 
  - Syllabus PDFs (`download_syllabus.php`, lines 814-1074)
  - Financial report PDFs (`accountant_dashboard.php`, lines 692-916)
- **Why**: Excellent UTF-8 support, Arabic/RTL text rendering, professional formatting

**PHPSpreadsheet 4.5** (`composer.json`, line 3)
- **Purpose**: Excel file generation for data exports
- **Usage**: Financial data exports (`accountant_dashboard.php`, lines 514-690)
- **Why**: Comprehensive Excel format support, styling capabilities, RTL text support

#### Python Dependencies (`requirements.txt`)

**Flask 3.0.0** (`requirements.txt`, line 1)
- **Purpose**: Web framework for chatbot API
- **Usage**: REST API endpoints (`chatbot_api.py`, lines 11-12, 534-722)
- **Why**: Lightweight, flexible, easy deployment

**Flask-CORS 4.0.0** (`requirements.txt`, line 2)
- **Purpose**: Cross-origin resource sharing for chatbot API
- **Usage**: Enables frontend to call chatbot API (`chatbot_api.py`, line 12)
- **Why**: Required for browser-based API calls from different origins

**OpenAI 1.3.0** (`requirements.txt`, line 3)
- **Purpose**: Integration with OpenAI GPT models
- **Usage**: AI-powered chatbot responses (`chatbot_api.py`, lines 3, 14, 684-704)
- **Why**: Industry-standard AI API, powerful language models

**python-dotenv 1.0.0** (`requirements.txt`, line 4)
- **Purpose**: Environment variable management
- **Usage**: Loading API keys securely (`chatbot_api.py`, lines 5, 9)
- **Why**: Secure credential management, separation of config from code

**Gunicorn 21.2.0** (`requirements.txt`, line 5)
- **Purpose**: Production WSGI server (optional, for deployment)
- **Usage**: Not used in development, available for production deployment
- **Why**: Production-grade Python application server

### External Services

#### OpenAI API
- **Purpose**: AI-powered course recommendations and chatbot responses
- **Integration**: `chatbot_api.py` (lines 684-704)
- **Model Used**: GPT-4o-mini
- **Why**: Advanced natural language understanding, multilingual support (English/Arabic)

### Development Tools

#### Composer
- **Purpose**: PHP dependency management
- **Usage**: Installing mPDF and PHPSpreadsheet libraries
- **Configuration**: `composer.json`

#### Git
- **Purpose**: Version control
- **Repository**: GitHub (`GhostReaperXX/Shaima`)
- **Why**: Standard version control, collaboration support

### Server Infrastructure

#### Apache (via XAMPP)
- **Purpose**: HTTP web server for PHP applications
- **Usage**: Serving PHP files and static assets
- **Why**: Standard PHP deployment, easy local development

#### XAMPP
- **Purpose**: Local development environment
- **Components**: Apache, MySQL, PHP, phpMyAdmin
- **Why**: All-in-one solution for Windows development

---

## Main Features

### 1. Course Management
**Implementation**: `accountant_dashboard.php` (lines 216-281)
- **Add Courses**: POST handler with validation (lines 216-242)
- **Edit Courses**: Update existing courses (lines 244-271)
- **Delete Courses**: Soft delete with cascade handling (lines 273-281)
- **Database**: `courses` table (`db_connection.php`, lines 69-90)
- **Features**: Course types (Diploma/Short Course), trainer management, scheduling, fee management

### 2. Student Management
**Implementation**: `accountant_dashboard.php` (lines 283-350)
- **Add Students**: Form validation and database insertion (lines 283-310)
- **Edit Students**: Update student records (lines 312-340)
- **Delete Students**: Cascade deletion handling (lines 342-350)
- **Database**: `students` table (`db_connection.php`, lines 53-67)
- **Features**: National ID validation (unique), contact information, specialization tracking

### 3. Enrollment Management
**Implementation**: 
- **Public Enrollment**: `checkout.php` (lines 47-109)
- **Pending Enrollments**: `accountant_dashboard.php` (lines 1636-1750)
- **Database**: `pending_enrollments` table (`db_connection.php`, lines 227-246)
- **Workflow**: Public form → Pending status → Accountant review → Approval/Rejection
- **Features**: Status tracking (Pending/Contacted/Approved/Rejected), processor tracking

### 4. Financial Management

#### Invoice Management
**Implementation**: `accountant_dashboard.php` (lines 352-450)
- **Create Invoices**: Automatic calculation of amounts (lines 352-400)
- **Update Payments**: Track partial/full payments (lines 402-430)
- **Database**: `invoices` table (`db_connection.php`, lines 104-124)
- **Features**: Invoice numbering, status tracking (Paid/Partial/Unpaid), due date management

#### Payment Processing
**Implementation**: `accountant_dashboard.php` (lines 452-550)
- **Record Payments**: Link to students and courses (lines 452-500)
- **Payment Methods**: Multiple payment method tracking
- **Database**: `payments` table (`db_connection.php`, lines 126-143)
- **Features**: Payment status (Completed/Voided), date tracking, notes

#### Trainer Payments
**Implementation**: `accountant_dashboard.php` (lines 552-620)
- **Track Trainer Payments**: Link to courses
- **Database**: `trainer_payments` table (`db_connection.php`, lines 202-214)
- **Features**: Payment tracking, method recording, notes

### 5. Task Management System
**Implementation**: `accountant_dashboard.php` (lines 918-1200)
- **Create Tasks**: Manager-only task creation (lines 919-949)
- **Task Files**: File upload support (lines 162-211)
- **Task Submissions**: Employee task completion (lines 1000-1100)
- **Daily Tasks**: Progress tracking (lines 1100-1200)
- **Database**: 
  - `tasks` table (`db_connection.php`, lines 145-163)
  - `task_files` table (lines 165-174)
  - `task_submissions` table (lines 176-190)
  - `daily_tasks` table (lines 192-200)
- **Features**: Priority levels, status tracking, file attachments, employee code assignment

### 6. AI-Powered Chatbot
**Implementation**: `chatbot_api.py` (lines 1-745)
- **Frontend Integration**: `index.html` (lines 3764-3912)
- **API Endpoint**: `/api/chat` (POST) (`chatbot_api.py`, lines 534-722)
- **Features**:
  - Natural language processing (`chatbot_api.py`, lines 451-465)
  - Course recommendations (lines 488-491)
  - Career roadmaps (lines 493-496)
  - Bilingual support (English/Arabic) (lines 482-486)
  - Context-aware conversations (lines 405-450)
- **AI Model**: OpenAI GPT-4o-mini (lines 686-694)
- **Why**: Provides intelligent course guidance, reduces support workload

### 7. PDF Generation
**Implementation**: `download_syllabus.php` (lines 1-1080)
- **Syllabus PDFs**: Professional academic-style course syllabi
- **Library**: mPDF (`download_syllabus.php`, lines 38, 814-823)
- **Features**: 
  - Course-specific content generation (lines 46-810)
  - Professional formatting (lines 825-1070)
  - Automatic tmp directory creation (lines 813-815)
- **Usage**: Public course pages (`index.html`, syllabus download button)

### 8. Excel Export
**Implementation**: `accountant_dashboard.php` (lines 514-690)
- **Library**: PHPSpreadsheet (lines 519-688)
- **Export Types**: Students, Courses, Invoices, Payments
- **Features**: 
  - RTL text support for Arabic (lines 633, 673)
  - Professional styling (lines 615-620, 655-660)
  - Auto-sized columns (lines 638-640, 678-680)

### 9. Contact Form
**Implementation**: `contact.php` (lines 1-178)
- **Database**: `contacts` table (`db_connection.php`, lines 216-225)
- **Features**: Form validation, email/phone capture, message storage
- **Security**: Input sanitization (lines 9-13, 143-163)

### 10. Authentication & Authorization
**Implementation**: `login.php` (lines 1-243)
- **Session Management**: PHP sessions (`login.php`, lines 2, 27-31)
- **Password Security**: `password_hash()` and `password_verify()` (lines 24, `db_connection.php` line 31)
- **Role-Based Access**: Manager, Accountant, Employee roles
- **Session Regeneration**: Security on login (line 27)

### 11. Dependency Checker
**Implementation**: `check_dependencies.php` (new file)
- **Purpose**: Verify all required dependencies are installed
- **Checks**: PHP version, Composer dependencies, mPDF, database connection, tmp directory
- **Usage**: Post-installation verification tool

---

## Project Structure Walkthrough

### Root Directory

#### `index.html` (4,064 lines)
- **Purpose**: Public-facing homepage with course catalog and chatbot
- **Key Components**:
  - **Navigation**: Responsive navbar with language switcher (lines 92-200)
  - **Hero Section**: Landing area with call-to-action (lines 300-600)
  - **Course Grid**: Dynamic course rendering with filtering (lines 3101-3743)
  - **Chatbot UI**: Embedded chatbot interface (lines 3357-3381, 3764-3912)
  - **Course Data**: JavaScript course array (lines 3411-3700)
- **Styling**: Embedded CSS with CSS variables (lines 12-3300+)
- **JavaScript**: Vanilla JS for interactivity (lines 3383-4064)

#### `login.php` (243 lines)
- **Purpose**: User authentication portal
- **Key Components**:
  - **Session Start**: Line 2
  - **Form Processing**: POST handler (lines 10-50)
  - **Password Verification**: `password_verify()` (line 24)
  - **Session Creation**: User data stored in session (lines 28-31)
  - **Role-Based Redirect**: Dashboard redirect (lines 33-38)
- **Security**: Input sanitization (lines 11-12, 232, 236), error handling (lines 44-48)

#### `accountant_dashboard.php` (2,744 lines)
- **Purpose**: Main authenticated dashboard for all user roles
- **Key Components**:
  - **Session & Security**: Lines 1-16 (session check, security headers, CSRF)
  - **Helper Functions**: Lines 59-152 (CSRF, role checks, safe queries)
  - **File Upload Handler**: Lines 162-211 (validation, sanitization)
  - **Course Management**: Lines 216-281
  - **Student Management**: Lines 283-350
  - **Financial Operations**: Lines 352-620
  - **Task Management**: Lines 918-1200
  - **Export Functions**: Lines 514-916 (Excel/PDF)
  - **UI Rendering**: Lines 1200-2744 (HTML output with role-based views)

#### `db_connection.php` (388 lines)
- **Purpose**: Database connection and schema initialization
- **Key Components**:
  - **Connection Configuration**: Lines 2-12 (PDO setup with security options)
  - **Table Creation**: `initializeTables()` function (lines 41-253)
    - Users table (lines 43-51)
    - Students table (lines 53-67)
    - Courses table (lines 69-90)
    - Student-Courses junction (lines 92-102)
    - Invoices table (lines 104-124)
    - Payments table (lines 126-143)
    - Tasks tables (lines 145-200)
    - Trainer payments (lines 202-214)
    - Contacts (lines 216-225)
    - Pending enrollments (lines 227-246)
  - **Default Users**: `initializeDefaultUsers()` (lines 17-39)
  - **Connection Retry Logic**: Lines 276-385 (handles MySQL startup delays)

#### `chatbot_api.py` (745 lines)
- **Purpose**: Flask REST API for AI chatbot service
- **Key Components**:
  - **Flask App Setup**: Lines 11-14 (CORS enabled)
  - **Course Data**: Hardcoded course catalog (lines 16-41)
  - **Roadmaps**: Career path data (lines 43-356)
  - **Context Management**: `ConversationContext` class (lines 405-450)
  - **Language Detection**: `detect_language()` (lines 482-486)
  - **Intent Extraction**: `extract_intent()` (lines 498-532)
  - **Main API Endpoint**: `/api/chat` (lines 534-722)
  - **OpenAI Integration**: Lines 684-704
  - **Fallback Handler**: `generate_contextual_fallback()` (lines 724-741)

#### `download_syllabus.php` (1,080 lines)
- **Purpose**: Generate and download course syllabus PDFs
- **Key Components**:
  - **Dependency Check**: Lines 3-36 (verifies Composer dependencies)
  - **Course Data Mapping**: Lines 46-200 (course information)
  - **Syllabus Content Generator**: `generateSyllabusContent()` (lines 202-810)
  - **PDF Generation**: mPDF initialization (lines 814-823)
  - **HTML Template**: Professional syllabus template (lines 825-1070)
  - **Error Handling**: Lines 1076-1080

#### `checkout.php` (272 lines)
- **Purpose**: Course enrollment form processing
- **Key Components**:
  - **Form Processing**: POST handler (lines 47-109)
  - **Course Lookup**: Database or fallback data (lines 28-42)
  - **Enrollment Creation**: Insert into `pending_enrollments` (lines 97-98)
  - **Input Validation**: Required field checks (lines 48-52, 106-108)
- **Security**: Prepared statements (lines 60-61, 68, 97-98), input sanitization

#### `contact.php` (178 lines)
- **Purpose**: Public contact form
- **Key Components**:
  - **Form Processing**: POST handler (lines 8-27)
  - **Database Insert**: Contacts table (lines 17-18)
  - **Input Validation**: Required fields (line 15)
- **Security**: Prepared statements, `htmlspecialchars()` (lines 133, 137, 143-163)

#### `error_handler.php` (30 lines)
- **Purpose**: Centralized error handling and logging
- **Key Components**:
  - **Error Logging**: Custom error handler (lines 5-9)
  - **Exception Handler**: Uncaught exception logging (lines 11-21)
  - **Shutdown Handler**: Fatal error capture (lines 23-29)
- **Log File**: `php_errors.log` (created automatically)

#### `logout.php` (7 lines)
- **Purpose**: Session termination
- **Key Components**:
  - **Session Destruction**: Lines 3-4
  - **Redirect**: To login page (line 5)

#### `check_dependencies.php` (new file)
- **Purpose**: Post-installation dependency verification
- **Key Components**:
  - **PHP Version Check**: Verifies PHP 7.4+
  - **Composer Check**: Verifies `vendor/autoload.php` exists
  - **Library Checks**: mPDF, PHPSpreadsheet availability
  - **Database Check**: Connection verification
  - **Directory Checks**: tmp/ directory permissions

#### `setup.php` & `init_db.php`
- **Purpose**: Database initialization utilities
- **Usage**: One-time setup scripts for fresh installations

#### `reset_passwords.php` & `reset_passwords.bat`
- **Purpose**: Password reset utilities
- **Usage**: Administrative tools for password management

#### `test_db_connection.php`
- **Purpose**: Database connectivity testing
- **Usage**: Diagnostic tool for troubleshooting database issues

### Configuration Files

#### `composer.json` (7 lines)
- **Purpose**: PHP dependency declaration
- **Dependencies**: mPDF 8.2, PHPSpreadsheet 4.5

#### `requirements.txt` (11 lines)
- **Purpose**: Python dependency declaration
- **Dependencies**: Flask, Flask-CORS, OpenAI, python-dotenv, Gunicorn

#### `.gitignore` (68 lines)
- **Purpose**: Git exclusion rules
- **Excluded**: vendor/, venv/, tmp/, uploads/, logs, credentials, .env files
- **Why**: Prevents committing dependencies, sensitive data, and generated files

### Asset Directory

#### `assets/` (30+ image files)
- **Purpose**: Static media files
- **Contents**:
  - Course images (ACCOUNTING1.jpeg, AI1.jpeg, etc.)
  - Founder photos (founder_*.jpeg)
  - Review/testimonial images (review_*.jpeg)
  - Category images (business_management.jpeg, etc.)
- **Usage**: Referenced in `index.html` course data (lines 3425-3699)

### Runtime Directories (Not in Git)

#### `vendor/` (generated)
- **Purpose**: Composer-installed PHP libraries
- **Contents**: mPDF, PHPSpreadsheet, and dependencies
- **Installation**: `composer install`

#### `venv/` (generated)
- **Purpose**: Python virtual environment
- **Contents**: Python packages and interpreter
- **Installation**: `python -m venv venv`

#### `tmp/` (generated)
- **Purpose**: Temporary files for PDF generation
- **Created**: Automatically by mPDF or manually
- **Permissions**: Must be writable (0777)

#### `uploads/` (generated)
- **Purpose**: User-uploaded files (task attachments, submissions)
- **Created**: Automatically on first upload
- **Security**: File type validation, size limits (30MB)

---

## Database Section

### Database System
**MySQL 8.0+** (InnoDB engine)
- **Database Name**: `sys_academy`
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Connection**: PDO with prepared statements (`db_connection.php`, lines 8-12)

### Schema Overview

#### Table: `users` (Lines 43-51 in `db_connection.php`)
- **Purpose**: System user accounts (managers, accountants, employees)
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `username` (VARCHAR(50), UNIQUE, NOT NULL)
  - `password` (VARCHAR(255), NOT NULL) - Hashed with `password_hash()`
  - `full_name` (VARCHAR(100), NOT NULL)
  - `role` (ENUM: 'manager', 'accountant', 'employee', NOT NULL)
- **Relationships**: 
  - One-to-Many with `tasks` (via `created_by_user_id`)
  - One-to-Many with `pending_enrollments` (via `processed_by`)

#### Table: `students` (Lines 53-67)
- **Purpose**: Student records
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `full_name` (VARCHAR(150), NOT NULL)
  - `national_id` (VARCHAR(50), UNIQUE, NOT NULL) - Unique identifier
  - `nationality` (VARCHAR(80))
  - `specialization` (VARCHAR(120))
  - `phone` (VARCHAR(50), NOT NULL)
  - `email` (VARCHAR(150))
  - `address` (VARCHAR(255))
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `full_name`, `nationality`
- **Relationships**:
  - Many-to-Many with `courses` (via `student_courses`)
  - One-to-Many with `invoices`
  - One-to-Many with `payments`

#### Table: `courses` (Lines 69-90)
- **Purpose**: Course catalog
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `name` (VARCHAR(200), NOT NULL)
  - `type` (ENUM: 'Diploma', 'Short Course', DEFAULT 'Short Course')
  - `description` (TEXT)
  - `trainer_name` (VARCHAR(150), NOT NULL)
  - `total_hours` (INT, NOT NULL)
  - `start_date` (DATE, NOT NULL)
  - `end_date` (DATE, NOT NULL)
  - `days` (VARCHAR(120), NOT NULL)
  - `session_duration` (VARCHAR(60), NOT NULL)
  - `session_time` (TIME, NOT NULL)
  - `fees` (DECIMAL(12,2), DEFAULT 0.00)
  - `trainer_fees` (DECIMAL(12,2), DEFAULT 0.00)
  - `trainer_paid` (DECIMAL(12,2), DEFAULT 0.00)
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `trainer_name`, `type`, `start_date`, `end_date`
- **Relationships**:
  - Many-to-Many with `students` (via `student_courses`)
  - One-to-Many with `invoices`
  - One-to-Many with `payments`
  - One-to-Many with `trainer_payments`
  - One-to-Many with `pending_enrollments`

#### Table: `student_courses` (Lines 92-102)
- **Purpose**: Junction table for student-course enrollments
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `student_id` (INT, FK → students.id, NOT NULL)
  - `course_id` (INT, FK → courses.id, NOT NULL)
  - `enrollment_date` (DATE, DEFAULT CURDATE())
- **Constraints**: 
  - UNIQUE(`student_id`, `course_id`) - Prevents duplicate enrollments
  - FOREIGN KEY CASCADE DELETE
- **Relationships**: Many-to-Many bridge between `students` and `courses`

#### Table: `invoices` (Lines 104-124)
- **Purpose**: Financial invoices for student-course combinations
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `student_id` (INT, FK → students.id, NOT NULL)
  - `course_id` (INT, FK → courses.id, NOT NULL)
  - `invoice_number` (VARCHAR(64), UNIQUE, NOT NULL)
  - `total_amount` (DECIMAL(12,2), NOT NULL)
  - `paid_amount` (DECIMAL(12,2), DEFAULT 0.00)
  - `remaining_amount` (DECIMAL(12,2), DEFAULT 0.00)
  - `due_date` (DATE, NOT NULL)
  - `status` (ENUM: 'Paid', 'Partial', 'Unpaid', DEFAULT 'Unpaid')
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `student_id`, `course_id`, `status`, `due_date`, composite(`status`, `due_date`)
- **Relationships**: Links `students` and `courses` with financial data

#### Table: `payments` (Lines 126-143)
- **Purpose**: Payment records
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `student_id` (INT, FK → students.id, NOT NULL)
  - `course_id` (INT, FK → courses.id, NOT NULL)
  - `amount` (DECIMAL(12,2), NOT NULL)
  - `payment_date` (DATE, DEFAULT CURDATE())
  - `payment_method` (VARCHAR(60), NOT NULL)
  - `notes` (VARCHAR(255))
  - `status` (ENUM: 'Completed', 'Voided', DEFAULT 'Completed')
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `student_id`, `course_id`, `payment_date`, composite index on `payment_date`
- **Relationships**: Links payments to specific student-course combinations

#### Table: `tasks` (Lines 145-163)
- **Purpose**: Task assignments for employees
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `title` (VARCHAR(200), NOT NULL)
  - `description` (TEXT, NOT NULL)
  - `employee_code` (VARCHAR(80), NOT NULL)
  - `priority` (ENUM: 'Low', 'Normal', 'High', 'Urgent', DEFAULT 'Normal')
  - `due_date` (DATE)
  - `status` (ENUM: 'Open', 'In Progress', 'Done', 'Archived', DEFAULT 'Open')
  - `created_by_user_id` (INT, FK → users.id, NULL, ON DELETE SET NULL)
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `employee_code`, `status`, `priority`, `due_date`, `created_by_user_id`, composite(`employee_code`, `status`)
- **Relationships**: 
  - Many-to-One with `users` (creator)
  - One-to-Many with `task_files`
  - One-to-Many with `task_submissions`
  - One-to-Many with `daily_tasks`

#### Table: `task_files` (Lines 165-174)
- **Purpose**: File attachments for tasks
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `task_id` (INT, FK → tasks.id, NOT NULL, ON DELETE CASCADE)
  - `file_path` (VARCHAR(255), NOT NULL)
  - `uploaded_by_role` (ENUM: 'manager', 'accountant', 'employee', DEFAULT 'manager')
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Relationships**: Many-to-One with `tasks`

#### Table: `task_submissions` (Lines 176-190)
- **Purpose**: Employee task completion submissions
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `task_id` (INT, FK → tasks.id, NOT NULL, ON DELETE CASCADE)
  - `employee_code` (VARCHAR(80), NOT NULL)
  - `text_notes` (TEXT)
  - `file_path` (VARCHAR(255))
  - `checked` (TINYINT(1), DEFAULT 0)
  - `status` (ENUM: 'Submitted', 'Approved', 'Rejected', DEFAULT 'Submitted')
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `task_id`, `employee_code`, `status`
- **Relationships**: Many-to-One with `tasks`

#### Table: `daily_tasks` (Lines 192-200)
- **Purpose**: Daily progress notes for tasks
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `task_id` (INT, FK → tasks.id, NOT NULL, ON DELETE CASCADE)
  - `note` (VARCHAR(255), DEFAULT '')
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Relationships**: Many-to-One with `tasks`

#### Table: `trainer_payments` (Lines 202-214)
- **Purpose**: Payment records for course trainers
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `course_id` (INT, FK → courses.id, NOT NULL, ON DELETE CASCADE)
  - `amount` (DECIMAL(12,2), NOT NULL)
  - `payment_date` (DATE, DEFAULT CURDATE())
  - `payment_method` (VARCHAR(60), NOT NULL)
  - `notes` (VARCHAR(255))
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Indexes**: `course_id`, `payment_date`
- **Relationships**: Many-to-One with `courses`

#### Table: `contacts` (Lines 216-225)
- **Purpose**: Public contact form submissions
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `name` (VARCHAR(150), NOT NULL)
  - `email` (VARCHAR(150), NOT NULL)
  - `phone` (VARCHAR(50))
  - `subject` (VARCHAR(200))
  - `message` (TEXT, NOT NULL)
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- **Relationships**: None (standalone table)

#### Table: `pending_enrollments` (Lines 227-246)
- **Purpose**: Enrollment requests awaiting processing
- **Columns**:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `full_name` (VARCHAR(150), NOT NULL)
  - `national_id` (VARCHAR(50), NOT NULL)
  - `phone` (VARCHAR(50), NOT NULL)
  - `email` (VARCHAR(150), NOT NULL)
  - `course_id` (INT, FK → courses.id, NOT NULL, ON DELETE CASCADE)
  - `course_name` (VARCHAR(200))
  - `course_fees` (DECIMAL(12,2))
  - `status` (ENUM: 'Pending', 'Contacted', 'Approved', 'Rejected', DEFAULT 'Pending')
  - `notes` (TEXT)
  - `created_at` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
  - `processed_at` (TIMESTAMP)
  - `processed_by` (INT, FK → users.id, NULL)
- **Indexes**: `course_id`, `status`, `created_at`
- **Relationships**: 
  - Many-to-One with `courses`
  - Many-to-One with `users` (processor)

### Data Access Layer

#### Query Execution Pattern
All database queries use **PDO prepared statements** to prevent SQL injection:

**Pattern Example** (`accountant_dashboard.php`, lines 123-131):
```php
function safe_query_all(PDO $pdo, $sql, $params=[]) {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
}
```

**Usage Locations**:
- Course queries: Lines 216-281
- Student queries: Lines 283-350
- Financial queries: Lines 352-620
- Task queries: Lines 918-1200

#### Connection Management
- **File**: `db_connection.php`
- **Connection**: Global `$pdo` variable (line 6)
- **Options**: 
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION` (line 9)
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC` (line 10)
  - `PDO::ATTR_EMULATE_PREPARES => false` (line 11) - **Security**: Prevents emulated prepares
- **Retry Logic**: Handles MySQL startup delays (lines 276-385)

---

## Security Section

### Authentication & Session Management

#### Password Hashing
**Implementation**: `db_connection.php` (line 31), `login.php` (line 24)
- **Method**: PHP `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- **Verification**: `password_verify()` in login process
- **Protection**: Prevents password disclosure even if database is compromised
- **Location**: 
  - Hashing: `db_connection.php:31`
  - Verification: `login.php:24`

#### Session Security
**Implementation**: `login.php` (lines 2, 27-31)
- **Session Regeneration**: `session_regenerate_id(true)` on login (line 27)
- **Purpose**: Prevents session fixation attacks
- **Session Data**: User ID, username, role, full name stored (lines 28-31)
- **Session Validation**: Checked in `accountant_dashboard.php` (lines 3-6)

#### Role-Based Access Control (RBAC)
**Implementation**: `accountant_dashboard.php` (lines 8-12, 81-91)
- **Roles**: Manager, Accountant, Employee
- **Access Control**: 
  - Session role check (lines 3-6)
  - Role validation (lines 9-12)
  - Function-based checks: `is_manager()`, `is_accountant()`, `is_employee()` (lines 81-91)
- **403 Response**: Invalid roles receive HTTP 403 (line 11)
- **Protection**: Prevents unauthorized access to role-specific features

### Input Validation & Sanitization

#### SQL Injection Prevention
**Implementation**: Throughout codebase using PDO prepared statements
- **Method**: Parameterized queries with `prepare()` and `execute()`
- **Examples**:
  - Login: `login.php:18-20` - `$stmt->prepare("SELECT ... WHERE username = ?")`
  - Enrollment: `checkout.php:60-61, 97-98` - Prepared statements for all inserts
  - Dashboard: `accountant_dashboard.php:123-131` - Safe query wrapper functions
- **PDO Configuration**: `PDO::ATTR_EMULATE_PREPARES => false` (`db_connection.php:11`)
- **Protection**: Prevents SQL injection by separating SQL structure from data

#### XSS (Cross-Site Scripting) Prevention
**Implementation**: `htmlspecialchars()` throughout
- **Method**: Output encoding with `ENT_QUOTES` flag
- **Examples**:
  - Login form: `login.php:228, 232, 236` - All user input escaped
  - Contact form: `contact.php:133, 137, 143-163` - Form values escaped
  - Dashboard: Helper function `h()` (`accountant_dashboard.php:77-79`)
- **Protection**: Prevents malicious scripts from executing in browser

#### CSRF (Cross-Site Request Forgery) Protection
**Implementation**: `accountant_dashboard.php` (lines 59-75)
- **Token Generation**: `bin2hex(random_bytes(16))` (line 60)
- **Token Storage**: Session variable `$_SESSION['csrf']` (line 60)
- **Token Validation**: `check_csrf()` function (lines 67-75)
  - Compares POST token with session token using `hash_equals()` (line 70)
  - Returns 400 error on failure (lines 71-72)
- **Token Injection**: `csrf_field()` helper (lines 63-65)
- **Usage**: All POST forms include CSRF token (lines 1736, 1854, 1946, 2044)
- **Protection**: Prevents unauthorized actions from external sites

### File Upload Security

#### File Type Validation
**Implementation**: `accountant_dashboard.php` (lines 159, 178-182, 199-200)
- **Allowed Extensions**: `['pdf','doc','docx','xls','xlsx','png','jpg','jpeg','txt','zip']` (line 159)
- **Validation**: Extension check using `pathinfo()` (lines 178, 199)
- **Whitelist Approach**: Only allowed extensions accepted
- **Protection**: Prevents executable file uploads (.php, .exe, etc.)

#### File Size Limits
**Implementation**: `accountant_dashboard.php` (line 160, 173-176, 197)
- **Maximum Size**: 30MB (`$MAX_UPLOAD = 30*1024*1024`)
- **Validation**: `filesize()` check before processing (lines 173, 197)
- **Protection**: Prevents DoS attacks via large file uploads

#### Filename Sanitization
**Implementation**: `accountant_dashboard.php` (lines 184, 202)
- **Method**: `preg_replace('/[^A-Za-z0-9\.\-_]/','_', basename($filename))`
- **Purpose**: Removes dangerous characters, prevents directory traversal
- **Unique Naming**: Timestamp + random number prefix (lines 185, 203)
- **Protection**: Prevents path traversal attacks (`../` injection)

#### Upload Directory Security
**Implementation**: `accountant_dashboard.php` (lines 154-157)
- **Directory Creation**: Automatic creation with safe permissions (0775)
- **Location**: `uploads/` directory (excluded from web root in some configurations)
- **Note**: Consider moving outside web root or adding `.htaccess` deny rules

### HTTP Security Headers

**Implementation**: `accountant_dashboard.php` (lines 14-16)
- **X-Frame-Options**: `SAMEORIGIN` - Prevents clickjacking
- **X-Content-Type-Options**: `nosniff` - Prevents MIME type sniffing
- **Referrer-Policy**: `no-referrer-when-downgrade` - Controls referrer information
- **Protection**: Mitigates various client-side attacks

### Error Handling & Information Disclosure

#### Error Logging
**Implementation**: `error_handler.php` (lines 1-30)
- **Method**: Custom error handlers log to `php_errors.log`
- **Display**: Errors not shown to users (except in development mode)
- **Protection**: Prevents information disclosure to attackers

#### Exception Handling
**Implementation**: Throughout codebase
- **Login Errors**: Generic messages, no database details (`login.php:22-25`)
- **Database Errors**: Logged, generic user messages (`checkout.php:103-104`)
- **Protection**: Prevents revealing system internals

### Database Security

#### Connection Security
**Implementation**: `db_connection.php` (lines 8-12)
- **Prepared Statements**: Always used (prevents SQL injection)
- **Error Mode**: Exception mode for proper error handling
- **Emulated Prepares**: Disabled (`PDO::ATTR_EMULATE_PREPARES => false`)
- **Character Set**: UTF-8 specified in DSN (prevents encoding attacks)

#### Credential Management
**Current State**: Hardcoded in `db_connection.php` (lines 2-5)
- **Risk**: Database credentials visible in source code
- **Recommendation**: Move to environment variables (`.env` file)
- **Note**: `.env` is in `.gitignore` (line 2), but credentials are currently hardcoded

### API Security (Chatbot)

#### CORS Configuration
**Implementation**: `chatbot_api.py` (line 12)
- **Method**: `CORS(app)` - Allows all origins
- **Risk**: Currently permissive (allows any origin)
- **Recommendation**: Restrict to specific frontend domain in production

#### API Key Management
**Implementation**: `chatbot_api.py` (lines 5, 9, 14)
- **Method**: Environment variable via `python-dotenv`
- **Storage**: `.env` file (excluded from Git)
- **Protection**: API keys not in source code

#### Input Validation
**Implementation**: `chatbot_api.py` (lines 537-543)
- **Message Validation**: Checks for empty messages (line 542)
- **Error Responses**: Proper HTTP status codes (400 for bad requests)
- **Protection**: Prevents malformed API requests

### Security Risks & Gaps

#### 1. Hardcoded Database Credentials
**Location**: `db_connection.php` (lines 2-5)
- **Risk**: Credentials visible in source code
- **Impact**: Medium - If repository is compromised, database access exposed
- **Recommendation**: 
  - Move to `.env` file
  - Use environment variables: `getenv('DB_HOST')`, etc.
  - Ensure `.env` is in `.gitignore` (already done)

#### 2. Permissive CORS
**Location**: `chatbot_api.py` (line 12)
- **Risk**: Any website can call the chatbot API
- **Impact**: Low-Medium - Potential for abuse, API quota exhaustion
- **Recommendation**: 
  ```python
  CORS(app, origins=["http://localhost", "https://yourdomain.com"])
  ```

#### 3. No Rate Limiting
**Location**: `chatbot_api.py`, `login.php`
- **Risk**: Brute force attacks on login, API abuse
- **Impact**: Medium - Account compromise, API cost escalation
- **Recommendation**: 
  - Implement rate limiting (e.g., 5 attempts per IP per 15 minutes)
  - Use Redis or file-based tracking
  - Lock accounts after multiple failed attempts

#### 4. Session Timeout Not Enforced
**Location**: `login.php`, `accountant_dashboard.php`
- **Risk**: Sessions remain valid indefinitely
- **Impact**: Low-Medium - If session hijacked, long-term access
- **Recommendation**: 
  - Set `session.gc_maxlifetime` in PHP.ini
  - Implement session timeout check in dashboard
  - Regenerate session ID periodically

#### 5. File Upload Location
**Location**: `accountant_dashboard.php` (line 154)
- **Risk**: Uploads directory may be web-accessible
- **Impact**: Medium - Direct file access if not properly configured
- **Recommendation**: 
  - Move `uploads/` outside web root, OR
  - Add `.htaccess` with `Deny from all` in uploads directory
  - Serve files through PHP script with authentication

#### 6. No Input Length Validation
**Location**: Various forms (`checkout.php`, `contact.php`)
- **Risk**: Database field overflow, potential DoS
- **Impact**: Low - Database constraints provide some protection
- **Recommendation**: 
  - Add `maxlength` attributes to HTML inputs
  - Server-side length validation before database insert

#### 7. Missing HTTPS Enforcement
**Location**: All PHP files
- **Risk**: Credentials transmitted in plain text
- **Impact**: High in production - Credential interception
- **Recommendation**: 
  - Force HTTPS in production
  - Add `header('Strict-Transport-Security: max-age=31536000')`
  - Use SSL/TLS certificates

#### 8. No Account Lockout
**Location**: `login.php`
- **Risk**: Brute force password attacks
- **Impact**: Medium - Account compromise
- **Recommendation**: 
  - Track failed login attempts per username
  - Lock account after 5 failed attempts for 30 minutes
  - Store attempts in database or cache

### Security Best Practices Implemented

✅ **Password Hashing**: Bcrypt via `password_hash()`
✅ **Prepared Statements**: All database queries use PDO prepared statements
✅ **Input Sanitization**: `htmlspecialchars()` on all output
✅ **CSRF Protection**: Token-based protection on all forms
✅ **Session Regeneration**: On login to prevent fixation
✅ **File Upload Validation**: Type and size checks
✅ **Error Handling**: Errors logged, not displayed to users
✅ **Security Headers**: X-Frame-Options, X-Content-Type-Options
✅ **Role-Based Access**: Function-level role checks

### Defensive Examples

#### SQL Injection Protection
**Threat**: Attacker injects `' OR '1'='1` in login form
**Mitigation**: 
- Prepared statement in `login.php:18-20` separates SQL from data
- Query: `SELECT ... WHERE username = ?` with parameter `[$username]`
- Result: Injection attempt treated as literal string, no SQL execution

#### XSS Protection
**Threat**: Attacker submits `<script>alert('XSS')</script>` in contact form
**Mitigation**: 
- `htmlspecialchars()` in `contact.php:143-163` encodes special characters
- Output: `&lt;script&gt;alert('XSS')&lt;/script&gt;`
- Result: Script rendered as text, not executed

#### CSRF Protection
**Threat**: Attacker creates form on external site submitting to dashboard
**Mitigation**: 
- CSRF token required in `accountant_dashboard.php:67-75`
- Token validated using `hash_equals()` (timing-safe comparison)
- Result: External form submissions fail token validation

#### Broken Authentication Protection
**Threat**: Session hijacking or weak passwords
**Mitigation**: 
- Session regeneration on login (`login.php:27`)
- Strong password hashing (`db_connection.php:31`)
- Role validation on every request (`accountant_dashboard.php:3-6`)
- Result: Compromised sessions limited, passwords protected

#### File Upload Protection
**Threat**: Attacker uploads `.php` file to execute code
**Mitigation**: 
- Whitelist validation (`accountant_dashboard.php:159, 178-182`)
- Filename sanitization (line 184)
- Size limits (line 160)
- Result: Only safe file types accepted, dangerous files rejected

---

## How to Run

### Prerequisites

1. **XAMPP** (or equivalent: Apache + MySQL + PHP)
   - Download: https://www.apachefriends.org/
   - Includes: Apache web server, MySQL database, PHP interpreter

2. **Python 3.11.7**
   - Download: https://www.python.org/downloads/
   - Ensure "Add Python to PATH" is checked during installation

3. **Composer** (PHP dependency manager)
   - Download: https://getcomposer.org/download/
   - Required for installing mPDF and PHPSpreadsheet

4. **Git** (optional, for cloning)
   - Download: https://git-scm.com/downloads

### Environment Variables

Create a `.env` file in the project root:

```env
# OpenAI API Key (for chatbot)
OPENAI_API_KEY=your_openai_api_key_here

# Database Configuration (optional - currently hardcoded)
# DB_HOST=localhost
# DB_NAME=sys_academy
# DB_USER=root
# DB_PASS=your_password
```

**Note**: Database credentials are currently hardcoded in `db_connection.php` (lines 2-5). For production, move these to `.env` and update `db_connection.php` to read from environment variables.

### Installation Steps

#### 1. Clone/Download Repository
```bash
git clone https://github.com/GhostReaperXX/Shaima.git
cd Academy-platform
```

#### 2. Start XAMPP Services
- Open XAMPP Control Panel
- Start **Apache** service
- Start **MySQL** service
- Verify both show "Running" status

#### 3. Database Setup

**Option A: Automatic Setup**
1. Navigate to: `http://localhost/Academy-platform/setup.php`
2. Follow the setup wizard

**Option B: Manual Setup**
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create database: `sys_academy`
3. Import: `sys_academy_backup.sql` (if available)
4. OR: Database will auto-create on first page load via `db_connection.php`

#### 4. Install PHP Dependencies
```bash
composer install
```

This installs:
- mPDF 8.2 (PDF generation)
- PHPSpreadsheet 4.5 (Excel export)

#### 5. Install Python Dependencies
```bash
# Create virtual environment
python -m venv venv

# Activate virtual environment
# Windows:
venv\Scripts\activate
# Linux/Mac:
source venv/bin/activate

# Install packages
pip install -r requirements.txt
```

#### 6. Configure OpenAI API Key
1. Get API key from: https://platform.openai.com/api-keys
2. Add to `.env` file: `OPENAI_API_KEY=sk-...`

#### 7. Start Chatbot API Server

**Windows:**
```bash
start_chatbot.bat
```

**Linux/Mac:**
```bash
python chatbot_api.py
```

Server runs on: `http://localhost:5000`

#### 8. Verify Dependencies (Optional)
Navigate to: `http://localhost/Academy-platform/check_dependencies.php`

This verifies:
- PHP version
- Composer dependencies
- mPDF library
- Database connection
- Directory permissions

#### 9. Access Application

**Public Homepage:**
```
http://localhost/Academy-platform/
```

**Login Portal:**
```
http://localhost/Academy-platform/login.php
```

**Default Credentials:**
- Manager: `manager` / `manager`
- Accountant: `accountant` / `accountant`
- Employee: `employee` / `employee`

**⚠️ Important**: Change default passwords after first login!

### Setup Verification Checklist

- [ ] Apache is running
- [ ] MySQL is running
- [ ] Database `sys_academy` exists
- [ ] `composer install` completed successfully
- [ ] `vendor/` directory exists
- [ ] Python virtual environment created
- [ ] `pip install -r requirements.txt` completed
- [ ] `.env` file created with OpenAI API key
- [ ] Chatbot API server running on port 5000
- [ ] Can access `index.html` in browser
- [ ] Can login with default credentials
- [ ] Syllabus download works (tests mPDF)
- [ ] Excel export works (tests PHPSpreadsheet)

---

## Deployment

### Current Deployment Model
**Development Environment**: XAMPP on Windows
**Production Status**: Not explicitly configured

### Recommended Production Deployment

#### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: 8.2+ with extensions: `gd`, `mbstring`, `pdo_mysql`
- **MySQL**: 8.0+
- **Python**: 3.11+ (for chatbot service)
- **SSL Certificate**: Required for HTTPS

#### Deployment Steps

1. **Server Setup**
   ```bash
   # Install PHP and extensions
   sudo apt-get install php8.2 php8.2-mysql php8.2-mbstring php8.2-gd
   
   # Install MySQL
   sudo apt-get install mysql-server
   
   # Install Python and pip
   sudo apt-get install python3.11 python3-pip
   ```

2. **Upload Files**
   - Upload project files to web root (e.g., `/var/www/academy-platform/`)
   - Ensure proper file permissions:
     ```bash
     chmod 755 /var/www/academy-platform
     chmod 644 /var/www/academy-platform/*.php
     ```

3. **Install Dependencies**
   ```bash
   cd /var/www/academy-platform
   composer install --no-dev --optimize-autoloader
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

4. **Database Configuration**
   - Create production database
   - Update `db_connection.php` with production credentials (or use `.env`)
   - Run migrations/setup scripts

5. **Environment Configuration**
   - Create `.env` file with production values
   - Set `OPENAI_API_KEY`
   - Configure database credentials
   - Set `APP_ENV=production`

6. **Chatbot Service Deployment**
   ```bash
   # Using Gunicorn (production WSGI server)
   cd /var/www/academy-platform
   source venv/bin/activate
   gunicorn -w 4 -b 0.0.0.0:5000 chatbot_api:app
   
   # Or use systemd service (recommended)
   # Create /etc/systemd/system/chatbot.service
   ```

7. **Web Server Configuration**

   **Apache (.htaccess or virtual host)**:
   ```apache
   <VirtualHost *:80>
       ServerName academy-platform.com
       DocumentRoot /var/www/academy-platform
       
       <Directory /var/www/academy-platform>
           AllowOverride All
           Require all granted
       </Directory>
       
       # Redirect to HTTPS
       RewriteEngine On
       RewriteCond %{HTTPS} off
       RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
   </VirtualHost>
   
   <VirtualHost *:443>
       ServerName academy-platform.com
       DocumentRoot /var/www/academy-platform
       
       SSLEngine on
       SSLCertificateFile /path/to/certificate.crt
       SSLCertificateKeyFile /path/to/private.key
   </VirtualHost>
   ```

8. **Security Hardening**
   - Move `uploads/` outside web root or add `.htaccess` deny rules
   - Set proper file permissions (755 for directories, 644 for files)
   - Disable PHP error display in production (`php.ini`: `display_errors = Off`)
   - Enable PHP OPcache for performance
   - Configure firewall rules (allow 80, 443, block 5000 from external)

9. **Directory Permissions**
   ```bash
   # Create and secure directories
   mkdir -p tmp uploads
   chmod 775 tmp uploads
   chown www-data:www-data tmp uploads
   ```

### CI/CD (Not Currently Implemented)

**Recommendations for Future**:
- **GitHub Actions**: Automated testing on push
- **Deployment Pipeline**: 
  1. Run tests
  2. Build dependencies
  3. Deploy to staging
  4. Run smoke tests
  5. Deploy to production
- **Database Migrations**: Version-controlled schema changes

### Monitoring & Maintenance

**Recommended Tools**:
- **Error Logging**: Already implemented (`php_errors.log`)
- **Application Monitoring**: Consider Sentry or similar
- **Database Backups**: Automated daily backups
- **Uptime Monitoring**: External service (UptimeRobot, etc.)

---

## Summary

The Academy Platform is a comprehensive educational management system built with PHP and Python, featuring a MySQL database backend. The system provides role-based access control for managers, accountants, and employees, with a public-facing course catalog and AI-powered chatbot for student assistance.

**Key Strengths**:
- Clean separation of concerns (frontend/backend/database)
- Comprehensive security measures (CSRF, XSS prevention, SQL injection protection)
- Professional PDF and Excel export capabilities
- AI-powered student assistance via OpenAI integration
- Robust error handling and logging
- Bilingual support (English/Arabic)

**Architecture Highlights**:
- **Frontend**: Vanilla JavaScript with modern CSS, responsive design
- **Backend**: PHP for web application, Python Flask for AI service
- **Database**: Well-normalized MySQL schema with 13 interconnected tables
- **Security**: Multiple layers including prepared statements, CSRF tokens, password hashing

**Technology Stack**:
- PHP 8.2+ with PDO
- Python 3.11+ with Flask
- MySQL 8.0+
- mPDF for PDF generation
- PHPSpreadsheet for Excel exports
- OpenAI API for AI chatbot

---

## Future Improvements

### High Priority

1. **Environment Variable Configuration**
   - Move database credentials from `db_connection.php` to `.env`
   - Update connection code to read from environment variables
   - **Impact**: Security improvement, easier deployment

2. **Rate Limiting**
   - Implement rate limiting on login endpoint
   - Add rate limiting to chatbot API
   - **Implementation**: Redis or file-based tracking
   - **Impact**: Prevents brute force attacks

3. **Session Management Enhancement**
   - Implement session timeout (e.g., 30 minutes inactivity)
   - Add "Remember Me" functionality with secure tokens
   - **Impact**: Better security and user experience

4. **File Upload Security Hardening**
   - Move uploads directory outside web root
   - Add `.htaccess` deny rules as backup
   - Implement virus scanning (ClamAV integration)
   - **Impact**: Prevents direct file access attacks

5. **CORS Restriction**
   - Restrict chatbot API CORS to specific domains
   - **Implementation**: `chatbot_api.py` line 12
   - **Impact**: Prevents API abuse from external sites

### Medium Priority

6. **Email Notifications**
   - Send enrollment confirmation emails
   - Notify accountants of new enrollments
   - Payment receipt emails
   - **Implementation**: PHPMailer or similar
   - **Impact**: Better communication, reduced manual work

7. **Password Reset Functionality**
   - Forgot password flow with email verification
   - Secure token-based reset links
   - **Impact**: User self-service, reduced support load

8. **Audit Logging**
   - Log all financial transactions
   - Track user actions (who did what, when)
   - **Implementation**: New `audit_log` table
   - **Impact**: Compliance, security monitoring

9. **API Documentation**
   - Document chatbot API endpoints
   - Add OpenAPI/Swagger specification
   - **Impact**: Easier integration, developer experience

10. **Database Indexing Optimization**
    - Review query patterns
    - Add composite indexes for common queries
    - **Impact**: Improved performance on large datasets

### Low Priority

11. **Multi-language Support Expansion**
    - Add more languages beyond English/Arabic
    - Language selection persistence
    - **Impact**: Broader market reach

12. **Advanced Reporting**
    - Custom report builder
    - Scheduled report generation
    - **Impact**: Better business intelligence

13. **Mobile App**
    - React Native or Flutter app
    - Push notifications
    - **Impact**: Better user engagement

14. **Payment Gateway Integration**
    - Online payment processing (Stripe, PayPal)
    - Automatic invoice generation
    - **Impact**: Streamlined payment collection

15. **Course Content Management**
    - Video upload and streaming
    - Course materials library
    - **Impact**: Complete LMS functionality

---

## Glossary

**API (Application Programming Interface)**: A set of protocols and tools for building software applications. In this project, the chatbot API allows the frontend to communicate with the Python backend.

**BCrypt**: A password hashing algorithm used by PHP's `password_hash()` function. It's designed to be computationally expensive, making brute-force attacks difficult.

**CORS (Cross-Origin Resource Sharing)**: A security feature that allows web pages to make requests to a different domain than the one serving the web page. Configured in `chatbot_api.py`.

**CSRF (Cross-Site Request Forgery)**: An attack that forces authenticated users to execute unwanted actions. Prevented using CSRF tokens in this project.

**PDO (PHP Data Objects)**: A PHP extension providing a consistent interface for accessing databases. Used throughout this project for database operations.

**Prepared Statement**: A database feature that separates SQL code from data. Prevents SQL injection by treating user input as data, not executable code.

**RBAC (Role-Based Access Control)**: A security model where access is granted based on user roles (manager, accountant, employee) rather than individual permissions.

**Session**: A server-side mechanism to maintain user state across multiple HTTP requests. Used for authentication in this project.

**SQL Injection**: An attack where malicious SQL code is inserted into database queries. Prevented using prepared statements.

**XSS (Cross-Site Scripting)**: An attack where malicious scripts are injected into web pages. Prevented using `htmlspecialchars()` to encode output.

**WSGI (Web Server Gateway Interface)**: A specification for Python web applications. Gunicorn is a WSGI server used for deploying the Flask chatbot API.

---

## Questions / Unknowns

1. **Production Domain**: What is the intended production domain name?
2. **Email Configuration**: Are SMTP settings needed for email notifications?
3. **Backup Strategy**: What is the current database backup procedure?
4. **SSL Certificate**: Which certificate authority is preferred for HTTPS?
5. **Hosting Provider**: Is there a preferred hosting provider (AWS, DigitalOcean, etc.)?
6. **Monitoring**: Are there existing monitoring tools or preferences?
7. **User Capacity**: Expected number of concurrent users?
8. **Data Retention**: How long should financial records be retained?

---

**Document Version**: 1.0  
**Last Updated**: January 2026  
**Author**: Technical Documentation Team  
**Repository**: https://github.com/GhostReaperXX/Shaima
