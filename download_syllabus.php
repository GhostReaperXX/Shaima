<?php
// Check if Composer dependencies are installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Missing Dependencies</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .error-box { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 8px; }
            h1 { color: #856404; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
            .steps { margin-top: 20px; }
            .steps ol { line-height: 2; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>⚠️ Missing PHP Dependencies</h1>
            <p>The syllabus download feature requires PHP dependencies to be installed via Composer.</p>
            <div class="steps">
                <h2>To fix this, please run:</h2>
                <ol>
                    <li>Open terminal/command prompt in the project directory</li>
                    <li>Run: <code>composer install</code></li>
                    <li>If Composer is not installed, download it from: <a href="https://getcomposer.org/download/" target="_blank">https://getcomposer.org/download/</a></li>
                    <li>After installation, refresh this page</li>
                </ol>
                <p><strong>Note:</strong> This is a one-time setup. Once dependencies are installed, the syllabus download will work.</p>
            </div>
        </div>
    </body>
    </html>
    ');
}

require_once 'vendor/autoload.php';
require_once 'db_connection.php';

use Mpdf\Mpdf;

// Get course ID
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Course data mapping (matching index.html courses)
$courses_data = [
    1 => [
        'title' => 'Advanced Cybersecurity',
        'instructor' => 'Dr. Mohammed Al-Ahmad',
        'hours' => 120,
        'price' => 450,
        'level' => 'Advanced',
        'category' => 'Cybersecurity'
    ],
    2 => [
        'title' => 'Cybersecurity Fundamentals for Beginners',
        'instructor' => 'Ms. Sarah Al-Khalid',
        'hours' => 60,
        'price' => 250,
        'level' => 'Beginner',
        'category' => 'Cybersecurity'
    ],
    3 => [
        'title' => 'Professional Software Engineering',
        'instructor' => 'Eng. Ahmed Ali',
        'hours' => 150,
        'price' => 500,
        'level' => 'Advanced',
        'category' => 'Software Engineering'
    ],
    4 => [
        'title' => 'Full-Stack Web Development',
        'instructor' => 'Eng. Khaled Al-Dosari',
        'hours' => 100,
        'price' => 400,
        'level' => 'Intermediate',
        'category' => 'Software Engineering'
    ],
    5 => [
        'title' => 'Computer Science Fundamentals',
        'instructor' => 'Dr. Fatima Al-Noor',
        'hours' => 80,
        'price' => 300,
        'level' => 'Beginner',
        'category' => 'Computer Science'
    ],
    6 => [
        'title' => 'Advanced Algorithms and Data Structures',
        'instructor' => 'Dr. Youssef Al-Maliki',
        'hours' => 120,
        'price' => 450,
        'level' => 'Advanced',
        'category' => 'Computer Science'
    ],
    7 => [
        'title' => 'Artificial Intelligence and Machine Learning',
        'instructor' => 'Dr. Ali Al-Hussein',
        'hours' => 140,
        'price' => 550,
        'level' => 'Advanced',
        'category' => 'Artificial Intelligence'
    ],
    8 => [
        'title' => 'Natural Language Processing',
        'instructor' => 'Dr. Nora Al-Saeed',
        'hours' => 110,
        'price' => 480,
        'level' => 'Intermediate',
        'category' => 'Artificial Intelligence'
    ],
    9 => [
        'title' => 'Advanced Financial Accounting',
        'instructor' => 'Dr. Mahmoud Al-Ali',
        'hours' => 90,
        'price' => 350,
        'level' => 'Advanced',
        'category' => 'Accounting'
    ],
    10 => [
        'title' => 'Accounting Fundamentals for Beginners',
        'instructor' => 'Ms. Lina Salama',
        'hours' => 50,
        'price' => 200,
        'level' => 'Beginner',
        'category' => 'Accounting'
    ],
    11 => [
        'title' => 'Managerial Accounting and Costing',
        'instructor' => 'Dr. Sami Al-Qadi',
        'hours' => 100,
        'price' => 380,
        'level' => 'Intermediate',
        'category' => 'Accounting'
    ],
    12 => [
        'title' => 'Strategic Business Management',
        'instructor' => 'Dr. Reem Al-Abdullah',
        'hours' => 110,
        'price' => 420,
        'level' => 'Advanced',
        'category' => 'Business Management'
    ],
    13 => [
        'title' => 'Entrepreneurship and Project Management',
        'instructor' => 'Eng. Khaled Al-Mutairi',
        'hours' => 95,
        'price' => 400,
        'level' => 'Intermediate',
        'category' => 'Business Management'
    ],
    14 => [
        'title' => 'Digital Marketing',
        'instructor' => 'Ms. Nora Al-Ahmad',
        'hours' => 85,
        'price' => 350,
        'level' => 'Intermediate',
        'category' => 'Business Management'
    ],
    15 => [
        'title' => 'Commercial Law and Companies',
        'instructor' => 'Dr. Fahd Al-Salem',
        'hours' => 120,
        'price' => 450,
        'level' => 'Advanced',
        'category' => 'Law'
    ],
    16 => [
        'title' => 'Civil Law and Contracts',
        'instructor' => 'Dr. Mona Al-Hassan',
        'hours' => 100,
        'price' => 380,
        'level' => 'Intermediate',
        'category' => 'Law'
    ]
];

// Get course data
$course = null;
if ($course_id > 0 && isset($courses_data[$course_id])) {
    $course = $courses_data[$course_id];
    $course['id'] = $course_id;
} else {
    // Try to get from database
    try {
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$course_id]);
        $db_course = $stmt->fetch();
        if ($db_course) {
            $course = [
                'id' => $db_course['id'],
                'title' => $db_course['name'],
                'instructor' => $db_course['trainer_name'],
                'hours' => $db_course['total_hours'],
                'price' => $db_course['fees'],
                'level' => 'Intermediate',
                'category' => 'General'
            ];
        }
    } catch (PDOException $e) {
        // Error handling
    }
}

if (!$course) {
    header("Location: index.html");
    exit;
}

// Function to generate syllabus content based on course
function generateSyllabusContent($course) {
    $category = $course['category'];
    $title = $course['title'];
    $instructor = $course['instructor'];
    $hours = $course['hours'];
    $level = $course['level'];
    
    // Base syllabus structure
    $syllabus = [
        'description' => '',
        'objectives' => [],
        'learning_outcomes' => [],
        'modules' => [],
        'assessment' => [],
        'materials' => [],
        'prerequisites' => []
    ];
    
    // Generate content based on category
    switch ($category) {
        case 'Cybersecurity':
            if ($level === 'Advanced') {
                $syllabus = [
                    'description' => 'This advanced course provides comprehensive training in cybersecurity principles, threat analysis, network security, and incident response. Students will gain hands-on experience with industry-standard security tools and methodologies.',
                    'objectives' => [
                        'Master advanced cybersecurity concepts and frameworks',
                        'Develop expertise in threat detection and analysis',
                        'Implement comprehensive security architectures',
                        'Execute effective incident response strategies',
                        'Understand compliance and regulatory requirements'
                    ],
                    'learning_outcomes' => [
                        'Design and implement enterprise-level security solutions',
                        'Conduct comprehensive security assessments and audits',
                        'Respond to and mitigate security incidents effectively',
                        'Apply security frameworks (NIST, ISO 27001) in practice',
                        'Evaluate and select appropriate security technologies'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Advanced Threat Landscape', 'topics' => ['Advanced persistent threats (APTs)', 'Zero-day vulnerabilities', 'Social engineering techniques', 'Threat intelligence and analysis']],
                        ['title' => 'Module 2: Network Security Architecture', 'topics' => ['Firewall configuration and management', 'Intrusion detection and prevention systems', 'Network segmentation strategies', 'VPN and secure communications']],
                        ['title' => 'Module 3: Vulnerability Assessment', 'topics' => ['Penetration testing methodologies', 'Vulnerability scanning tools', 'Risk assessment frameworks', 'Remediation strategies']],
                        ['title' => 'Module 4: Incident Response', 'topics' => ['Incident response lifecycle', 'Forensic analysis techniques', 'Business continuity planning', 'Disaster recovery procedures']],
                        ['title' => 'Module 5: Security Compliance', 'topics' => ['Regulatory frameworks (GDPR, HIPAA)', 'Compliance auditing', 'Security policies and procedures', 'Governance and risk management']]
                    ],
                    'assessment' => [
                        ['type' => 'Practical Labs', 'weight' => '30%', 'description' => 'Hands-on security exercises and simulations'],
                        ['type' => 'Midterm Examination', 'weight' => '25%', 'description' => 'Written examination covering modules 1-3'],
                        ['type' => 'Final Project', 'weight' => '30%', 'description' => 'Comprehensive security assessment project'],
                        ['type' => 'Participation & Quizzes', 'weight' => '15%', 'description' => 'Class participation and weekly quizzes']
                    ],
                    'materials' => [
                        'Required: "Cybersecurity Essentials" by Charles J. Brooks',
                        'Required: "Network Security Essentials" by William Stallings',
                        'Recommended: NIST Cybersecurity Framework documentation',
                        'Lab environment access provided'
                    ],
                    'prerequisites' => ['Cybersecurity Fundamentals course or equivalent', 'Basic networking knowledge', 'Understanding of operating systems']
                ];
            } else {
                $syllabus = [
                    'description' => 'This foundational course introduces students to the fundamental concepts of cybersecurity, including basic security principles, common threats, and essential protection strategies.',
                    'objectives' => [
                        'Understand core cybersecurity concepts and terminology',
                        'Identify common security threats and vulnerabilities',
                        'Learn basic security best practices',
                        'Introduction to security tools and technologies'
                    ],
                    'learning_outcomes' => [
                        'Recognize common cybersecurity threats',
                        'Apply basic security measures',
                        'Understand security fundamentals',
                        'Use basic security tools effectively'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Introduction to Cybersecurity', 'topics' => ['What is cybersecurity?', 'The threat landscape', 'Security principles (CIA triad)', 'Types of attacks']],
                        ['title' => 'Module 2: Network Security Basics', 'topics' => ['Network fundamentals', 'Firewalls and routers', 'Wireless security', 'Network protocols']],
                        ['title' => 'Module 3: System Security', 'topics' => ['Operating system security', 'User authentication', 'Access control', 'Malware protection']],
                        ['title' => 'Module 4: Data Protection', 'topics' => ['Encryption basics', 'Data backup strategies', 'Privacy principles', 'Secure data handling']]
                    ],
                    'assessment' => [
                        ['type' => 'Assignments', 'weight' => '40%', 'description' => 'Practical exercises and case studies'],
                        ['type' => 'Midterm Examination', 'weight' => '30%', 'description' => 'Written examination'],
                        ['type' => 'Final Examination', 'weight' => '30%', 'description' => 'Comprehensive final exam']
                    ],
                    'materials' => [
                        'Required: "Cybersecurity Fundamentals" textbook',
                        'Online resources and documentation',
                        'Lab exercises provided'
                    ],
                    'prerequisites' => ['Basic computer literacy', 'No prior cybersecurity experience required']
                ];
            }
            break;
            
        case 'Software Engineering':
            if (strpos($title, 'Full-Stack') !== false) {
                $syllabus = [
                    'description' => 'This comprehensive course covers full-stack web development, teaching students to build complete web applications from frontend to backend, including database integration and deployment.',
                    'objectives' => [
                        'Master frontend technologies (HTML, CSS, JavaScript)',
                        'Develop backend applications using modern frameworks',
                        'Integrate databases and APIs',
                        'Deploy and maintain web applications'
                    ],
                    'learning_outcomes' => [
                        'Build responsive web interfaces',
                        'Create RESTful APIs and server-side applications',
                        'Design and implement database schemas',
                        'Deploy applications to production environments'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Frontend Development', 'topics' => ['HTML5 and semantic markup', 'CSS3 and responsive design', 'JavaScript ES6+', 'Frontend frameworks (React/Vue)']],
                        ['title' => 'Module 2: Backend Development', 'topics' => ['Server-side programming', 'Node.js and Express', 'API design and development', 'Authentication and authorization']],
                        ['title' => 'Module 3: Database Integration', 'topics' => ['SQL and NoSQL databases', 'Database design', 'ORM frameworks', 'Data modeling']],
                        ['title' => 'Module 4: Deployment and DevOps', 'topics' => ['Version control (Git)', 'CI/CD pipelines', 'Cloud deployment', 'Performance optimization']]
                    ],
                    'assessment' => [
                        ['type' => 'Projects', 'weight' => '50%', 'description' => 'Three full-stack application projects'],
                        ['type' => 'Assignments', 'weight' => '25%', 'description' => 'Weekly coding assignments'],
                        ['type' => 'Final Examination', 'weight' => '25%', 'description' => 'Practical coding examination']
                    ],
                    'materials' => [
                        'Required: "Full-Stack Web Development" by David Flanagan',
                        'Development environment setup guide',
                        'Online documentation and resources'
                    ],
                    'prerequisites' => ['Basic programming knowledge', 'Understanding of web technologies']
                ];
            } else {
                $syllabus = [
                    'description' => 'This professional course covers software engineering methodologies, requirements analysis, system design, project management, and the complete software development lifecycle.',
                    'objectives' => [
                        'Understand software development methodologies (Agile, Waterfall)',
                        'Master requirements engineering and analysis',
                        'Learn system architecture and design patterns',
                        'Apply project management principles',
                        'Understand quality assurance and testing'
                    ],
                    'learning_outcomes' => [
                        'Design software systems using UML and design patterns',
                        'Manage software projects effectively',
                        'Apply software engineering best practices',
                        'Conduct requirements analysis and documentation',
                        'Implement quality assurance processes'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Software Development Lifecycle', 'topics' => ['SDLC models', 'Agile methodologies', 'Scrum and Kanban', 'Project planning']],
                        ['title' => 'Module 2: Requirements Engineering', 'topics' => ['Requirements gathering', 'Use cases and user stories', 'Requirements documentation', 'Stakeholder management']],
                        ['title' => 'Module 3: System Design', 'topics' => ['Architectural patterns', 'Design patterns (GoF)', 'UML modeling', 'Database design']],
                        ['title' => 'Module 4: Quality Assurance', 'topics' => ['Testing strategies', 'Unit and integration testing', 'Test-driven development', 'Code quality metrics']],
                        ['title' => 'Module 5: Project Management', 'topics' => ['Team management', 'Risk management', 'Version control', 'Documentation standards']]
                    ],
                    'assessment' => [
                        ['type' => 'Group Project', 'weight' => '40%', 'description' => 'Complete software development project'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Design and documentation assignments'],
                        ['type' => 'Examinations', 'weight' => '30%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "Software Engineering" by Ian Sommerville',
                        'Required: "Design Patterns" by Gang of Four',
                        'UML modeling tools',
                        'Project management software'
                    ],
                    'prerequisites' => ['Programming experience', 'Basic understanding of databases', 'Object-oriented programming knowledge']
                ];
            }
            break;
            
        case 'Artificial Intelligence':
            if (strpos($title, 'Natural Language') !== false) {
                $syllabus = [
                    'description' => 'This course explores natural language processing techniques, covering text analysis, language models, sentiment analysis, and practical applications in chatbots and language understanding systems.',
                    'objectives' => [
                        'Understand NLP fundamentals and applications',
                        'Master text preprocessing and feature extraction',
                        'Learn language models and transformers',
                        'Build practical NLP applications'
                    ],
                    'learning_outcomes' => [
                        'Preprocess and analyze text data',
                        'Implement NLP models using modern frameworks',
                        'Build chatbots and language understanding systems',
                        'Apply NLP techniques to real-world problems'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: NLP Fundamentals', 'topics' => ['Text preprocessing', 'Tokenization and stemming', 'Part-of-speech tagging', 'Named entity recognition']],
                        ['title' => 'Module 2: Language Models', 'topics' => ['N-gram models', 'Neural language models', 'Transformer architecture', 'BERT and GPT models']],
                        ['title' => 'Module 3: Text Classification', 'topics' => ['Sentiment analysis', 'Topic modeling', 'Text classification algorithms', 'Feature extraction']],
                        ['title' => 'Module 4: Advanced Applications', 'topics' => ['Chatbot development', 'Machine translation', 'Question answering systems', 'Text generation']]
                    ],
                    'assessment' => [
                        ['type' => 'Projects', 'weight' => '45%', 'description' => 'NLP application projects'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Weekly programming assignments'],
                        ['type' => 'Final Examination', 'weight' => '25%', 'description' => 'Written and practical examination']
                    ],
                    'materials' => [
                        'Required: "Natural Language Processing with Python" by Bird et al.',
                        'Python NLP libraries (NLTK, spaCy, Transformers)',
                        'Access to GPU computing resources'
                    ],
                    'prerequisites' => ['Python programming', 'Machine learning fundamentals', 'Statistics and probability']
                ];
            } else {
                $syllabus = [
                    'description' => 'This comprehensive course covers artificial intelligence and machine learning, including supervised and unsupervised learning, deep learning, neural networks, and practical applications.',
                    'objectives' => [
                        'Master machine learning algorithms and techniques',
                        'Understand deep learning architectures',
                        'Apply AI/ML to real-world problems',
                        'Evaluate and optimize ML models'
                    ],
                    'learning_outcomes' => [
                        'Implement various ML algorithms',
                        'Design and train neural networks',
                        'Apply deep learning frameworks (TensorFlow, PyTorch)',
                        'Evaluate model performance and optimize',
                        'Deploy ML models to production'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Machine Learning Fundamentals', 'topics' => ['Supervised learning', 'Unsupervised learning', 'Model evaluation', 'Feature engineering']],
                        ['title' => 'Module 2: Deep Learning', 'topics' => ['Neural networks', 'Backpropagation', 'Convolutional neural networks', 'Recurrent neural networks']],
                        ['title' => 'Module 3: Advanced ML Techniques', 'topics' => ['Ensemble methods', 'Reinforcement learning', 'Transfer learning', 'Model optimization']],
                        ['title' => 'Module 4: Practical Applications', 'topics' => ['Computer vision', 'Natural language processing', 'Recommender systems', 'Time series analysis']]
                    ],
                    'assessment' => [
                        ['type' => 'Projects', 'weight' => '50%', 'description' => 'ML model development projects'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Weekly programming assignments'],
                        ['type' => 'Final Examination', 'weight' => '20%', 'description' => 'Comprehensive examination']
                    ],
                    'materials' => [
                        'Required: "Hands-On Machine Learning" by Aurélien Géron',
                        'Required: "Deep Learning" by Ian Goodfellow',
                        'Python ML libraries (scikit-learn, TensorFlow, PyTorch)',
                        'GPU computing resources'
                    ],
                    'prerequisites' => ['Python programming', 'Linear algebra', 'Calculus', 'Statistics and probability']
                ];
            }
            break;
            
        case 'Accounting':
            if ($level === 'Advanced') {
                $syllabus = [
                    'description' => 'This advanced course covers complex financial accounting topics, including international accounting standards, financial statement analysis, and advanced reporting requirements.',
                    'objectives' => [
                        'Master advanced accounting principles and standards',
                        'Prepare complex financial statements',
                        'Analyze financial performance',
                        'Understand international accounting standards (IFRS)'
                    ],
                    'learning_outcomes' => [
                        'Prepare comprehensive financial statements',
                        'Apply IFRS and GAAP standards',
                        'Conduct financial analysis and interpretation',
                        'Understand advanced accounting topics'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Advanced Financial Reporting', 'topics' => ['IFRS standards', 'Complex transactions', 'Consolidated statements', 'Segment reporting']],
                        ['title' => 'Module 2: Financial Statement Analysis', 'topics' => ['Ratio analysis', 'Cash flow analysis', 'Profitability analysis', 'Financial forecasting']],
                        ['title' => 'Module 3: Advanced Topics', 'topics' => ['Leases and revenue recognition', 'Financial instruments', 'Pensions and benefits', 'Tax accounting']],
                        ['title' => 'Module 4: Auditing and Assurance', 'topics' => ['Audit planning', 'Internal controls', 'Audit evidence', 'Audit reports']]
                    ],
                    'assessment' => [
                        ['type' => 'Case Studies', 'weight' => '35%', 'description' => 'Financial analysis case studies'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Financial statement preparation'],
                        ['type' => 'Examinations', 'weight' => '35%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "Advanced Financial Accounting" textbook',
                        'IFRS standards documentation',
                        'Financial statement examples',
                        'Accounting software access'
                    ],
                    'prerequisites' => ['Intermediate Financial Accounting', 'Understanding of basic accounting principles']
                ];
            } else {
                $syllabus = [
                    'description' => 'This foundational course introduces students to accounting principles, financial statements, bookkeeping, and basic accounting practices.',
                    'objectives' => [
                        'Understand fundamental accounting principles',
                        'Learn double-entry bookkeeping',
                        'Prepare basic financial statements',
                        'Understand accounting cycle'
                    ],
                    'learning_outcomes' => [
                        'Record business transactions',
                        'Prepare trial balance and financial statements',
                        'Understand accounting principles',
                        'Apply basic accounting concepts'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Introduction to Accounting', 'topics' => ['Accounting principles', 'Accounting equation', 'Types of accounts', 'Double-entry system']],
                        ['title' => 'Module 2: Recording Transactions', 'topics' => ['Journal entries', 'Ledger accounts', 'Trial balance', 'Adjusting entries']],
                        ['title' => 'Module 3: Financial Statements', 'topics' => ['Income statement', 'Balance sheet', 'Statement of cash flows', 'Statement of equity']],
                        ['title' => 'Module 4: Accounting Cycle', 'topics' => ['Complete accounting cycle', 'Closing entries', 'Post-closing trial balance', 'Financial statement analysis basics']]
                    ],
                    'assessment' => [
                        ['type' => 'Assignments', 'weight' => '40%', 'description' => 'Accounting practice problems'],
                        ['type' => 'Quizzes', 'weight' => '20%', 'description' => 'Weekly quizzes'],
                        ['type' => 'Final Examination', 'weight' => '40%', 'description' => 'Comprehensive final exam']
                    ],
                    'materials' => [
                        'Required: "Accounting Principles" textbook',
                        'Accounting practice workbook',
                        'Online resources'
                    ],
                    'prerequisites' => ['Basic mathematics', 'No prior accounting knowledge required']
                ];
            }
            break;
            
        case 'Business Management':
            if (strpos($title, 'Strategic') !== false) {
                $syllabus = [
                    'description' => 'This advanced course covers strategic business management, including strategic planning, organizational leadership, competitive analysis, and strategic decision-making.',
                    'objectives' => [
                        'Understand strategic management frameworks',
                        'Develop strategic planning skills',
                        'Analyze competitive environments',
                        'Make strategic business decisions'
                    ],
                    'learning_outcomes' => [
                        'Formulate business strategies',
                        'Conduct competitive analysis',
                        'Implement strategic plans',
                        'Lead organizational change',
                        'Evaluate strategic performance'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Strategic Analysis', 'topics' => ['SWOT analysis', 'PESTEL analysis', 'Competitive forces', 'Internal analysis']],
                        ['title' => 'Module 2: Strategy Formulation', 'topics' => ['Business-level strategies', 'Corporate strategies', 'International strategies', 'Strategic options']],
                        ['title' => 'Module 3: Strategy Implementation', 'topics' => ['Organizational structure', 'Change management', 'Strategic control', 'Performance measurement']],
                        ['title' => 'Module 4: Leadership and Governance', 'topics' => ['Strategic leadership', 'Corporate governance', 'Ethics and social responsibility', 'Crisis management']]
                    ],
                    'assessment' => [
                        ['type' => 'Strategic Analysis Project', 'weight' => '40%', 'description' => 'Comprehensive strategic analysis of a company'],
                        ['type' => 'Case Studies', 'weight' => '30%', 'description' => 'Strategic management case studies'],
                        ['type' => 'Examinations', 'weight' => '30%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "Strategic Management" by Fred R. David',
                        'Case study materials',
                        'Business analysis tools'
                    ],
                    'prerequisites' => ['Business fundamentals', 'Management principles', 'Economics basics']
                ];
            } else if (strpos($title, 'Digital Marketing') !== false) {
                $syllabus = [
                    'description' => 'This course covers digital marketing strategies, including SEO, social media marketing, content marketing, email marketing, and digital advertising.',
                    'objectives' => [
                        'Master digital marketing channels and tools',
                        'Develop effective digital marketing campaigns',
                        'Understand analytics and measurement',
                        'Apply digital marketing strategies'
                    ],
                    'learning_outcomes' => [
                        'Create digital marketing campaigns',
                        'Optimize websites for search engines',
                        'Manage social media marketing',
                        'Analyze marketing performance',
                        'Use digital marketing tools effectively'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Digital Marketing Fundamentals', 'topics' => ['Digital marketing landscape', 'Customer journey', 'Marketing funnels', 'Digital strategy']],
                        ['title' => 'Module 2: Search Engine Optimization', 'topics' => ['SEO fundamentals', 'Keyword research', 'On-page and off-page SEO', 'Technical SEO']],
                        ['title' => 'Module 3: Social Media Marketing', 'topics' => ['Social media platforms', 'Content creation', 'Community management', 'Social media advertising']],
                        ['title' => 'Module 4: Digital Advertising', 'topics' => ['Google Ads', 'Facebook Ads', 'Display advertising', 'Remarketing strategies']],
                        ['title' => 'Module 5: Analytics and Measurement', 'topics' => ['Google Analytics', 'Marketing metrics', 'ROI measurement', 'Performance optimization']]
                    ],
                    'assessment' => [
                        ['type' => 'Digital Marketing Campaign', 'weight' => '45%', 'description' => 'Complete digital marketing campaign project'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Weekly marketing assignments'],
                        ['type' => 'Final Examination', 'weight' => '25%', 'description' => 'Comprehensive examination']
                    ],
                    'materials' => [
                        'Required: "Digital Marketing" by Dave Chaffey',
                        'Access to marketing tools (Google Analytics, etc.)',
                        'Case studies and examples'
                    ],
                    'prerequisites' => ['Basic marketing knowledge', 'Computer literacy']
                ];
            } else {
                $syllabus = [
                    'description' => 'This course covers entrepreneurship fundamentals, business planning, project management, and the process of starting and managing a new business venture.',
                    'objectives' => [
                        'Understand entrepreneurship and business creation',
                        'Develop business plans',
                        'Learn project management principles',
                        'Apply entrepreneurial skills'
                    ],
                    'learning_outcomes' => [
                        'Create comprehensive business plans',
                        'Manage business projects effectively',
                        'Understand startup financing',
                        'Apply entrepreneurial thinking',
                        'Launch and manage new ventures'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Entrepreneurship Fundamentals', 'topics' => ['Entrepreneurial mindset', 'Opportunity identification', 'Business models', 'Value proposition']],
                        ['title' => 'Module 2: Business Planning', 'topics' => ['Business plan development', 'Market analysis', 'Financial planning', 'Risk assessment']],
                        ['title' => 'Module 3: Project Management', 'topics' => ['Project planning', 'Resource management', 'Timeline and scheduling', 'Project execution']],
                        ['title' => 'Module 4: Startup Management', 'topics' => ['Funding and financing', 'Team building', 'Marketing for startups', 'Growth strategies']]
                    ],
                    'assessment' => [
                        ['type' => 'Business Plan Project', 'weight' => '45%', 'description' => 'Complete business plan development'],
                        ['type' => 'Project Management Assignment', 'weight' => '25%', 'description' => 'Project planning and execution'],
                        ['type' => 'Examinations', 'weight' => '30%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "The Lean Startup" by Eric Ries',
                        'Business plan templates',
                        'Project management tools'
                    ],
                    'prerequisites' => ['Business fundamentals', 'Basic management knowledge']
                ];
            }
            break;
            
        case 'Law':
            if (strpos($title, 'Commercial') !== false) {
                $syllabus = [
                    'description' => 'This specialized course covers commercial law, including company law, commercial contracts, intellectual property, and business regulations.',
                    'objectives' => [
                        'Understand commercial law principles',
                        'Master company law and corporate structures',
                        'Learn commercial contract law',
                        'Understand intellectual property rights'
                    ],
                    'learning_outcomes' => [
                        'Analyze commercial legal issues',
                        'Draft commercial contracts',
                        'Understand corporate legal structures',
                        'Apply commercial law principles',
                        'Protect intellectual property rights'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Company Law', 'topics' => ['Types of companies', 'Corporate governance', 'Shareholders rights', 'Directors duties']],
                        ['title' => 'Module 2: Commercial Contracts', 'topics' => ['Contract formation', 'Terms and conditions', 'Breach of contract', 'Remedies']],
                        ['title' => 'Module 3: Intellectual Property', 'topics' => ['Copyright law', 'Trademark law', 'Patent law', 'Trade secrets']],
                        ['title' => 'Module 4: Business Regulations', 'topics' => ['Competition law', 'Consumer protection', 'Employment law basics', 'Commercial disputes']]
                    ],
                    'assessment' => [
                        ['type' => 'Legal Analysis Papers', 'weight' => '40%', 'description' => 'Case analysis and legal research papers'],
                        ['type' => 'Contract Drafting', 'weight' => '25%', 'description' => 'Commercial contract drafting exercises'],
                        ['type' => 'Examinations', 'weight' => '35%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "Commercial Law" textbook',
                        'Legal case studies',
                        'Statute books and regulations',
                        'Legal research databases'
                    ],
                    'prerequisites' => ['Introduction to Law', 'Legal reasoning skills']
                ];
            } else {
                $syllabus = [
                    'description' => 'This course provides a comprehensive introduction to civil law, covering contracts, obligations, property law, and civil rights.',
                    'objectives' => [
                        'Understand civil law principles',
                        'Learn contract law fundamentals',
                        'Understand property rights',
                        'Apply civil law concepts'
                    ],
                    'learning_outcomes' => [
                        'Analyze civil law cases',
                        'Understand contract formation and enforcement',
                        'Apply civil law principles',
                        'Draft basic legal documents'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Introduction to Civil Law', 'topics' => ['Civil law system', 'Legal sources', 'Legal persons', 'Legal capacity']],
                        ['title' => 'Module 2: Contract Law', 'topics' => ['Contract formation', 'Offer and acceptance', 'Consideration', 'Contract validity']],
                        ['title' => 'Module 3: Obligations', 'topics' => ['Types of obligations', 'Performance and breach', 'Damages and remedies', 'Discharge of obligations']],
                        ['title' => 'Module 4: Property Law', 'topics' => ['Property rights', 'Ownership and possession', 'Real property', 'Personal property']]
                    ],
                    'assessment' => [
                        ['type' => 'Case Studies', 'weight' => '35%', 'description' => 'Civil law case analysis'],
                        ['type' => 'Assignments', 'weight' => '30%', 'description' => 'Legal problem-solving exercises'],
                        ['type' => 'Examinations', 'weight' => '35%', 'description' => 'Midterm and final examinations']
                    ],
                    'materials' => [
                        'Required: "Civil Law" textbook',
                        'Legal case studies',
                        'Statute books',
                        'Legal research resources'
                    ],
                    'prerequisites' => ['Introduction to Law', 'Legal reasoning']
                ];
            }
            break;
            
        case 'Computer Science':
            if ($level === 'Advanced') {
                $syllabus = [
                    'description' => 'This advanced course covers complex algorithms and data structures, including advanced algorithms, optimization techniques, and algorithmic problem-solving strategies.',
                    'objectives' => [
                        'Master advanced algorithms and data structures',
                        'Solve complex algorithmic problems',
                        'Understand algorithm analysis and optimization',
                        'Apply advanced problem-solving techniques'
                    ],
                    'learning_outcomes' => [
                        'Design efficient algorithms',
                        'Analyze algorithm complexity',
                        'Implement advanced data structures',
                        'Solve complex programming problems',
                        'Optimize algorithmic solutions'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Advanced Data Structures', 'topics' => ['Advanced trees (AVL, B-trees)', 'Graphs and graph algorithms', 'Hash tables and hashing', 'Advanced sorting algorithms']],
                        ['title' => 'Module 2: Algorithm Design Techniques', 'topics' => ['Divide and conquer', 'Dynamic programming', 'Greedy algorithms', 'Backtracking']],
                        ['title' => 'Module 3: Advanced Algorithms', 'topics' => ['Graph algorithms (shortest paths, MST)', 'String algorithms', 'Network flow algorithms', 'Computational geometry']],
                        ['title' => 'Module 4: Algorithm Analysis', 'topics' => ['Complexity analysis', 'Amortized analysis', 'Probabilistic analysis', 'Algorithm optimization']]
                    ],
                    'assessment' => [
                        ['type' => 'Programming Projects', 'weight' => '45%', 'description' => 'Algorithm implementation projects'],
                        ['type' => 'Problem Sets', 'weight' => '30%', 'description' => 'Weekly algorithmic problem sets'],
                        ['type' => 'Final Examination', 'weight' => '25%', 'description' => 'Comprehensive examination']
                    ],
                    'materials' => [
                        'Required: "Introduction to Algorithms" by Cormen et al.',
                        'Programming environment',
                        'Online judge platforms'
                    ],
                    'prerequisites' => ['Data Structures and Algorithms', 'Strong programming skills', 'Discrete mathematics']
                ];
            } else {
                $syllabus = [
                    'description' => 'This foundational course introduces computer science fundamentals, including algorithms, data structures, databases, and operating systems.',
                    'objectives' => [
                        'Understand computer science fundamentals',
                        'Learn basic algorithms and data structures',
                        'Understand database concepts',
                        'Introduction to operating systems'
                    ],
                    'learning_outcomes' => [
                        'Implement basic algorithms',
                        'Use fundamental data structures',
                        'Understand database operations',
                        'Comprehend operating system concepts'
                    ],
                    'modules' => [
                        ['title' => 'Module 1: Algorithms and Problem Solving', 'topics' => ['Algorithm design', 'Pseudocode', 'Algorithm analysis', 'Problem-solving strategies']],
                        ['title' => 'Module 2: Data Structures', 'topics' => ['Arrays and lists', 'Stacks and queues', 'Trees and binary trees', 'Hash tables']],
                        ['title' => 'Module 3: Databases', 'topics' => ['Database fundamentals', 'SQL basics', 'Database design', 'Normalization']],
                        ['title' => 'Module 4: Operating Systems', 'topics' => ['OS fundamentals', 'Process management', 'Memory management', 'File systems']]
                    ],
                    'assessment' => [
                        ['type' => 'Programming Assignments', 'weight' => '40%', 'description' => 'Algorithm and data structure implementations'],
                        ['type' => 'Quizzes', 'weight' => '20%', 'description' => 'Weekly quizzes'],
                        ['type' => 'Final Examination', 'weight' => '40%', 'description' => 'Comprehensive final exam']
                    ],
                    'materials' => [
                        'Required: "Computer Science: An Overview" textbook',
                        'Programming environment',
                        'Online resources'
                    ],
                    'prerequisites' => ['Basic programming knowledge', 'Mathematics fundamentals']
                ];
            }
            break;
            
        default:
            $syllabus = [
                'description' => 'This course provides comprehensive training in ' . strtolower($category) . ', covering fundamental concepts, practical applications, and industry best practices.',
                'objectives' => [
                    'Understand core concepts and principles',
                    'Apply knowledge to practical situations',
                    'Develop professional skills',
                    'Achieve course learning outcomes'
                ],
                'learning_outcomes' => [
                    'Demonstrate understanding of key concepts',
                    'Apply knowledge effectively',
                    'Develop critical thinking skills',
                    'Complete course requirements successfully'
                ],
                'modules' => [
                    ['title' => 'Module 1: Fundamentals', 'topics' => ['Introduction', 'Basic concepts', 'Core principles']],
                    ['title' => 'Module 2: Intermediate Topics', 'topics' => ['Advanced concepts', 'Practical applications']],
                    ['title' => 'Module 3: Advanced Applications', 'topics' => ['Real-world applications', 'Best practices']]
                ],
                'assessment' => [
                    ['type' => 'Assignments', 'weight' => '40%', 'description' => 'Course assignments'],
                    ['type' => 'Examinations', 'weight' => '60%', 'description' => 'Midterm and final examinations']
                ],
                'materials' => [
                    'Required course textbook',
                    'Additional resources as provided'
                ],
                'prerequisites' => ['Basic knowledge in related field']
            ];
    }
    
    return $syllabus;
}

// Generate syllabus content
$syllabusContent = generateSyllabusContent($course);

// Create PDF
try {
    // Ensure tmp directory exists for mPDF
    $tmpDir = __DIR__ . '/tmp';
    if (!is_dir($tmpDir)) {
        @mkdir($tmpDir, 0777, true);
    }
    
    $mpdf = new Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 20,
        'margin_right' => 20,
        'margin_top' => 25,
        'margin_bottom' => 25,
        'margin_header' => 10,
        'margin_footer' => 10,
        'tempDir' => $tmpDir
    ]);
    
    // Generate HTML content
    $html = '
    <style>
        @page {
            margin: 25mm 20mm;
        }
        body {
            font-family: "Times New Roman", serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #667eea;
            font-size: 24pt;
            font-weight: bold;
            margin: 0 0 10px 0;
            text-align: center;
        }
        .header .course-info {
            text-align: center;
            color: #666;
            font-size: 10pt;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            color: #667eea;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 12px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 5px;
        }
        .section-content {
            margin-left: 0;
            text-align: justify;
        }
        .objectives-list, .outcomes-list, .modules-list, .assessment-list, .materials-list, .prerequisites-list {
            margin: 10px 0;
            padding-left: 25px;
        }
        .objectives-list li, .outcomes-list li, .modules-list li, .assessment-list li, .materials-list li, .prerequisites-list li {
            margin-bottom: 8px;
            line-height: 1.7;
        }
        .module {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #667eea;
        }
        .module-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            font-size: 12pt;
        }
        .module-topics {
            margin-left: 20px;
            margin-top: 5px;
        }
        .module-topics li {
            margin-bottom: 5px;
        }
        .assessment-item {
            margin-bottom: 10px;
            padding: 8px;
            background-color: #f5f5f5;
        }
        .assessment-type {
            font-weight: bold;
            color: #667eea;
        }
        .assessment-weight {
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th {
            background-color: #667eea;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e0e0e0;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
    </style>
    
    <div class="header">
        <h1>COURSE SYLLABUS</h1>
        <div class="course-info">
            <strong>' . htmlspecialchars($course['title']) . '</strong><br>
            Course Code: CS-' . str_pad($course['id'], 3, '0', STR_PAD_LEFT) . ' | 
            Credit Hours: ' . $course['hours'] . ' | 
            Level: ' . $course['level'] . '<br>
            Instructor: ' . htmlspecialchars($course['instructor']) . ' | 
            Department: ' . htmlspecialchars($course['category']) . '
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">1. COURSE DESCRIPTION</div>
        <div class="section-content">
            ' . htmlspecialchars($syllabusContent['description']) . '
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">2. COURSE OBJECTIVES</div>
        <div class="section-content">
            <ol class="objectives-list">';
    foreach ($syllabusContent['objectives'] as $objective) {
        $html .= '<li>' . htmlspecialchars($objective) . '</li>';
    }
    $html .= '
            </ol>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">3. LEARNING OUTCOMES</div>
        <div class="section-content">
            <ol class="outcomes-list">';
    foreach ($syllabusContent['learning_outcomes'] as $outcome) {
        $html .= '<li>' . htmlspecialchars($outcome) . '</li>';
    }
    $html .= '
            </ol>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">4. COURSE MODULES</div>
        <div class="section-content">';
    foreach ($syllabusContent['modules'] as $module) {
        $html .= '
            <div class="module">
                <div class="module-title">' . htmlspecialchars($module['title']) . '</div>
                <ul class="module-topics">';
        foreach ($module['topics'] as $topic) {
            $html .= '<li>' . htmlspecialchars($topic) . '</li>';
        }
        $html .= '
                </ul>
            </div>';
    }
    $html .= '
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">5. ASSESSMENT AND GRADING</div>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Assessment Type</th>
                        <th>Weight</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($syllabusContent['assessment'] as $assessment) {
        $html .= '
                    <tr>
                        <td><strong>' . htmlspecialchars($assessment['type']) . '</strong></td>
                        <td>' . htmlspecialchars($assessment['weight']) . '</td>
                        <td>' . htmlspecialchars($assessment['description']) . '</td>
                    </tr>';
    }
    $html .= '
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">6. REQUIRED MATERIALS</div>
        <div class="section-content">
            <ul class="materials-list">';
    foreach ($syllabusContent['materials'] as $material) {
        $html .= '<li>' . htmlspecialchars($material) . '</li>';
    }
    $html .= '
            </ul>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">7. PREREQUISITES</div>
        <div class="section-content">
            <ul class="prerequisites-list">';
    foreach ($syllabusContent['prerequisites'] as $prereq) {
        $html .= '<li>' . htmlspecialchars($prereq) . '</li>';
    }
    $html .= '
            </ul>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">8. COURSE POLICIES</div>
        <div class="section-content">
            <ul>
                <li><strong>Attendance:</strong> Regular attendance is expected. Students are responsible for all material covered in class.</li>
                <li><strong>Assignments:</strong> All assignments must be submitted by the specified deadline. Late submissions may be penalized.</li>
                <li><strong>Academic Integrity:</strong> All work must be original. Plagiarism and academic dishonesty will result in course failure.</li>
                <li><strong>Communication:</strong> Students are encouraged to communicate with the instructor during office hours or via email.</li>
                <li><strong>Grading Scale:</strong> A (90-100%), B (80-89%), C (70-79%), D (60-69%), F (Below 60%)</li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>This syllabus is subject to change at the instructor\'s discretion. Students will be notified of any changes.</p>
        <p>Generated on ' . date('F j, Y') . ' | Academy Platform</p>
    </div>
    ';
    
    $mpdf->WriteHTML($html);
    
    // Output PDF
    $filename = 'Syllabus_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $course['title']) . '.pdf';
    $mpdf->Output($filename, 'D'); // 'D' for download
    
} catch (Exception $e) {
    // Better error handling
    $errorMessage = htmlspecialchars($e->getMessage());
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>PDF Generation Error</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .error-box { background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 8px; }
            h1 { color: #721c24; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h1>❌ Error Generating PDF</h1>
            <p><strong>Error:</strong> ' . $errorMessage . '</p>
            <p>If this error persists, please:</p>
            <ol>
                <li>Verify that <code>composer install</code> has been run</li>
                <li>Check that the <code>tmp/</code> directory is writable</li>
                <li>Verify PHP extensions: gd, mbstring are installed</li>
                <li>Check <a href="check_dependencies.php">check_dependencies.php</a> for more details</li>
            </ol>
        </div>
    </body>
    </html>
    ');
}
?>
