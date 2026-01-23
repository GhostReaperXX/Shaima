from flask import Flask, request, jsonify
from flask_cors import CORS
import openai
import os
from dotenv import load_dotenv
import json
import re

load_dotenv()

app = Flask(__name__)
CORS(app)

openai.api_key = os.getenv('OPENAI_API_KEY', '')

courses_data = {
    "cybersecurity": [
        {"title": "Advanced Cybersecurity", "price": 450, "instructor": "Dr. Mohammed Al-Ahmad", "rating": 4.8, "hours": 120},
        {"title": "Cybersecurity Fundamentals for Beginners", "price": 250, "instructor": "Ms. Sarah Al-Khalid", "rating": 4.6, "hours": 60}
    ],
    "programming": [
        {"title": "Professional Software Engineering", "price": 500, "instructor": "Eng. Ahmed Ali", "rating": 4.9, "hours": 150},
        {"title": "Full-Stack Web Development", "price": 400, "instructor": "Eng. Khaled Al-Dosari", "rating": 4.7, "hours": 100}
    ],
    "ai": [
        {"title": "Artificial Intelligence and Machine Learning", "price": 550, "instructor": "Dr. Ali Al-Hussein", "rating": 4.9, "hours": 140},
        {"title": "Natural Language Processing", "price": 480, "instructor": "Dr. Nora Al-Saeed", "rating": 4.7, "hours": 110}
    ],
    "accounting": [
        {"title": "Advanced Financial Accounting", "price": 350, "instructor": "Dr. Mahmoud Al-Ali", "rating": 4.6, "hours": 90},
        {"title": "Accounting Fundamentals for Beginners", "price": 200, "instructor": "Ms. Lina Salama", "rating": 4.5, "hours": 50}
    ],
    "business": [
        {"title": "Strategic Business Management", "price": 420, "instructor": "Dr. Reem Al-Abdullah", "rating": 4.8, "hours": 110},
        {"title": "Entrepreneurship and Project Management", "price": 400, "instructor": "Eng. Khaled Al-Mutairi", "rating": 4.7, "hours": 95}
    ],
    "law": [
        {"title": "Commercial Law and Companies", "price": 450, "instructor": "Dr. Fahd Al-Salem", "rating": 4.7, "hours": 120},
        {"title": "Civil Law and Contracts", "price": 380, "instructor": "Dr. Mona Al-Hassan", "rating": 4.6, "hours": 100}
    ]
}

roadmaps = {
    "cybersecurity": {
        "en": """Cybersecurity Career Roadmap:

Phase 1: Foundations (Months 1-3)
- Learn networking basics (TCP/IP, OSI model)
- Understand operating systems (Linux, Windows)
- Study cybersecurity fundamentals
- Complete: Cybersecurity Fundamentals course

Phase 2: Core Skills (Months 4-6)
- Network security and firewalls
- Vulnerability assessment
- Incident response basics
- Complete: Advanced Cybersecurity course

Phase 3: Specialization (Months 7-12)
- Ethical hacking and penetration testing
- Security architecture
- Compliance and risk management
- Get certified: CEH, CISSP

Phase 4: Advanced (Year 2+)
- Security operations center (SOC)
- Threat intelligence
- Security leadership
- Career: Security Analyst → Security Engineer → Security Architect""",
        "ar": """خارطة طريق الأمن السيبراني:

المرحلة 1: الأساسيات (أشهر 1-3)
- تعلم أساسيات الشبكات (TCP/IP، نموذج OSI)
- فهم أنظمة التشغيل (Linux، Windows)
- دراسة أساسيات الأمن السيبراني
- إكمال: دورة أساسيات الأمن السيبراني

المرحلة 2: المهارات الأساسية (أشهر 4-6)
- أمان الشبكات والجدران النارية
- تقييم الثغرات
- أساسيات الاستجابة للحوادث
- إكمال: دورة الأمن السيبراني المتقدم

المرحلة 3: التخصص (أشهر 7-12)
- الاختراق الأخلاقي واختبار الاختراق
- هندسة الأمن
- الامتثال وإدارة المخاطر
- الحصول على شهادة: CEH، CISSP

المرحلة 4: المتقدم (السنة 2+)
- مركز عمليات الأمن (SOC)
- استخبارات التهديدات
- قيادة الأمن
- المسار المهني: محلل أمن → مهندس أمن → مهندس معماري للأمن"""
    },
    "programming": {
        "en": """Software Engineering Career Roadmap:

Phase 1: Foundations (Months 1-3)
- Learn a programming language (Python/JavaScript)
- Understand data structures and algorithms
- Version control (Git)
- Complete: Computer Science Fundamentals course

Phase 2: Core Development (Months 4-6)
- Web development (HTML, CSS, JavaScript)
- Backend development (Node.js/Python)
- Databases (SQL, NoSQL)
- Complete: Full-Stack Web Development course

Phase 3: Advanced Skills (Months 7-12)
- Software architecture and design patterns
- DevOps and CI/CD
- Testing and quality assurance
- Complete: Professional Software Engineering course

Phase 4: Specialization (Year 2+)
- Choose specialization (Mobile, Cloud, AI)
- System design and scalability
- Leadership and team management
- Career: Junior Developer → Senior Developer → Tech Lead → Engineering Manager""",
        "ar": """خارطة طريق هندسة البرمجيات:

المرحلة 1: الأساسيات (أشهر 1-3)
- تعلم لغة برمجة (Python/JavaScript)
- فهم هياكل البيانات والخوارزميات
- التحكم بالإصدارات (Git)
- إكمال: دورة أساسيات علوم الحاسوب

المرحلة 2: التطوير الأساسي (أشهر 4-6)
- تطوير الويب (HTML، CSS، JavaScript)
- تطوير الخلفية (Node.js/Python)
- قواعد البيانات (SQL، NoSQL)
- إكمال: دورة تطوير تطبيقات الويب الكاملة

المرحلة 3: المهارات المتقدمة (أشهر 7-12)
- هندسة البرمجيات وأنماط التصميم
- DevOps و CI/CD
- الاختبار وضمان الجودة
- إكمال: دورة هندسة البرمجيات الاحترافية

المرحلة 4: التخصص (السنة 2+)
- اختر التخصص (موبايل، سحابة، ذكاء اصطناعي)
- تصميم الأنظمة والقابلية للتوسع
- القيادة وإدارة الفريق
- المسار المهني: مطور مبتدئ → مطور أول → قائد تقني → مدير هندسة"""
    },
    "ai": {
        "en": """AI/ML Career Roadmap:

Phase 1: Foundations (Months 1-3)
- Python programming
- Mathematics (Linear Algebra, Calculus, Statistics)
- Data analysis basics
- Complete: Computer Science Fundamentals

Phase 2: Machine Learning (Months 4-6)
- Supervised and unsupervised learning
- Deep learning basics (Neural Networks)
- ML frameworks (TensorFlow, PyTorch)
- Complete: AI and Machine Learning course

Phase 3: Advanced ML (Months 7-12)
- Natural Language Processing
- Computer Vision
- Reinforcement Learning
- Complete: Natural Language Processing course

Phase 4: Specialization (Year 2+)
- Research and publications
- ML engineering and MLOps
- AI ethics and governance
- Career: ML Engineer → Data Scientist → AI Researcher → AI Architect""",
        "ar": """خارطة طريق الذكاء الاصطناعي:

المرحلة 1: الأساسيات (أشهر 1-3)
- برمجة Python
- الرياضيات (الجبر الخطي، التفاضل، الإحصاء)
- أساسيات تحليل البيانات
- إكمال: أساسيات علوم الحاسوب

المرحلة 2: التعلم الآلي (أشهر 4-6)
- التعلم الخاضع للإشراف وغير الخاضع للإشراف
- أساسيات التعلم العميق (الشبكات العصبية)
- أطر التعلم الآلي (TensorFlow، PyTorch)
- إكمال: دورة الذكاء الاصطناعي والتعلم الآلي

المرحلة 3: التعلم الآلي المتقدم (أشهر 7-12)
- معالجة اللغة الطبيعية
- الرؤية الحاسوبية
- التعلم المعزز
- إكمال: دورة معالجة اللغة الطبيعية

المرحلة 4: التخصص (السنة 2+)
- البحث والمنشورات
- هندسة ML و MLOps
- أخلاقيات الذكاء الاصطناعي والحوكمة
- المسار المهني: مهندس ML → عالم بيانات → باحث AI → مهندس معماري AI"""
    },
    "accounting": {
        "en": """Accounting Career Roadmap:

Phase 1: Fundamentals (Months 1-3)
- Basic accounting principles
- Financial statements
- Bookkeeping
- Complete: Accounting Fundamentals course

Phase 2: Intermediate (Months 4-6)
- Financial accounting standards
- Cost accounting
- Tax basics
- Complete: Advanced Financial Accounting course

Phase 3: Advanced (Months 7-12)
- Managerial accounting
- Financial analysis
- Auditing basics
- Complete: Managerial Accounting course

Phase 4: Professional (Year 2+)
- CPA/CMA certification
- Financial planning
- Strategic financial management
- Career: Junior Accountant → Senior Accountant → Financial Manager → CFO""",
        "ar": """خارطة طريق المحاسبة:

المرحلة 1: الأساسيات (أشهر 1-3)
- مبادئ المحاسبة الأساسية
- القوائم المالية
- مسك الدفاتر
- إكمال: دورة أساسيات المحاسبة

المرحلة 2: المتوسط (أشهر 4-6)
- معايير المحاسبة المالية
- محاسبة التكاليف
- أساسيات الضرائب
- إكمال: دورة المحاسبة المالية المتقدمة

المرحلة 3: المتقدم (أشهر 7-12)
- المحاسبة الإدارية
- التحليل المالي
- أساسيات التدقيق
- إكمال: دورة المحاسبة الإدارية

المرحلة 4: المهني (السنة 2+)
- شهادة CPA/CMA
- التخطيط المالي
- الإدارة المالية الاستراتيجية
- المسار المهني: محاسب مبتدئ → محاسب أول → مدير مالي → CFO"""
    },
    "business": {
        "en": """Business Management Career Roadmap:

Phase 1: Foundations (Months 1-3)
- Business fundamentals
- Management principles
- Marketing basics
- Complete: Strategic Business Management course

Phase 2: Core Skills (Months 4-6)
- Strategic planning
- Human resources
- Operations management
- Complete: Entrepreneurship course

Phase 3: Advanced (Months 7-12)
- Digital marketing
- Business analytics
- Leadership skills
- Complete: Digital Marketing course

Phase 4: Executive (Year 2+)
- MBA (optional)
- Executive leadership
- Business strategy
- Career: Business Analyst → Manager → Director → VP → CEO""",
        "ar": """خارطة طريق إدارة الأعمال:

المرحلة 1: الأساسيات (أشهر 1-3)
- أساسيات الأعمال
- مبادئ الإدارة
- أساسيات التسويق
- إكمال: دورة إدارة الأعمال الاستراتيجية

المرحلة 2: المهارات الأساسية (أشهر 4-6)
- التخطيط الاستراتيجي
- الموارد البشرية
- إدارة العمليات
- إكمال: دورة ريادة الأعمال

المرحلة 3: المتقدم (أشهر 7-12)
- التسويق الرقمي
- تحليلات الأعمال
- مهارات القيادة
- إكمال: دورة التسويق الرقمي

المرحلة 4: التنفيذي (السنة 2+)
- MBA (اختياري)
- القيادة التنفيذية
- استراتيجية الأعمال
- المسار المهني: محلل أعمال → مدير → مدير عام → نائب رئيس → CEO"""
    },
    "law": {
        "en": """Law Career Roadmap:

Phase 1: Foundations (Months 1-3)
- Legal system basics
- Contract law
- Legal research
- Complete: Civil Law and Contracts course

Phase 2: Specialization (Months 4-6)
- Commercial law
- Corporate law
- Intellectual property
- Complete: Commercial Law course

Phase 3: Practice (Months 7-12)
- Legal writing
- Case analysis
- Client relations
- Internship/Clinic

Phase 4: Professional (Year 2+)
- Bar exam preparation
- Specialization certification
- Practice areas
- Career: Law Clerk → Associate → Partner → Senior Partner""",
        "ar": """خارطة طريق القانون:

المرحلة 1: الأساسيات (أشهر 1-3)
- أساسيات النظام القانوني
- قانون العقود
- البحث القانوني
- إكمال: دورة القانون المدني والعقود

المرحلة 2: التخصص (أشهر 4-6)
- القانون التجاري
- قانون الشركات
- الملكية الفكرية
- إكمال: دورة القانون التجاري

المرحلة 3: الممارسة (أشهر 7-12)
- الكتابة القانونية
- تحليل القضايا
- علاقات العملاء
- التدريب/العيادة

المرحلة 4: المهني (السنة 2+)
- التحضير لامتحان المحاماة
- شهادة التخصص
- مجالات الممارسة
- المسار المهني: كاتب قانون → محامي → شريك → شريك أول"""
    }
}

major_to_courses = {
    "أمن سيبراني": "cybersecurity",
    "cybersecurity": "cybersecurity",
    "أمن معلومات": "cybersecurity",
    "information security": "cybersecurity",
    "network security": "cybersecurity",
    "أمن الشبكات": "cybersecurity",
    "برمجة": "programming",
    "programming": "programming",
    "هندسة برمجيات": "programming",
    "software engineering": "programming",
    "تطوير": "programming",
    "development": "programming",
    "علوم حاسوب": "programming",
    "computer science": "programming",
    "تطوير ويب": "programming",
    "web development": "programming",
    "ذكاء اصطناعي": "ai",
    "artificial intelligence": "ai",
    "ai": "ai",
    "تعلم آلي": "ai",
    "machine learning": "ai",
    "علم بيانات": "ai",
    "data science": "ai",
    "محاسبة": "accounting",
    "accounting": "accounting",
    "محاسبة مالية": "accounting",
    "financial accounting": "accounting",
    "محاسبة إدارية": "accounting",
    "managerial accounting": "accounting",
    "إدارة أعمال": "business",
    "business": "business",
    "business management": "business",
    "ريادة أعمال": "business",
    "entrepreneurship": "business",
    "تسويق": "business",
    "marketing": "business",
    "تسويق رقمي": "business",
    "digital marketing": "business",
    "قانون": "law",
    "law": "law",
    "قانون تجاري": "law",
    "commercial law": "law",
    "قانون مدني": "law",
    "civil law": "law",
}

class ConversationContext:
    def __init__(self):
        self.user_name = None
        self.selected_field = None
        self.current_intent = None
        self.major = None
        self.previous_responses = []
        self.last_topic = None
    
    def update_from_history(self, history):
        for msg in reversed(history[-10:]):
            user_msg = msg.get("user", "").lower()
            bot_msg = msg.get("bot", "").lower()
            
            if not self.user_name:
                name_patterns = [
                    r"my name is (\w+)",
                    r"i'm (\w+)",
                    r"اسمي (\w+)",
                    r"أنا (\w+)"
                ]
                for pattern in name_patterns:
                    match = re.search(pattern, user_msg)
                    if match:
                        self.user_name = match.group(1)
                        break
            
            if not self.selected_field:
                detected = detect_category(msg.get("user", ""))
                if detected:
                    self.selected_field = detected
            
            if not self.major:
                detected_major = detect_major_from_message(msg.get("user", ""))
                if detected_major:
                    self.major = detected_major
            
            if "roadmap" in bot_msg or "خارطة" in bot_msg:
                self.current_intent = "roadmap"
            elif "course" in bot_msg or "دورة" in bot_msg:
                self.current_intent = "courses"
            elif "salary" in bot_msg or "راتب" in bot_msg or "job market" in bot_msg:
                self.current_intent = "career_info"
            elif "transition" in bot_msg or "تحول" in bot_msg:
                self.current_intent = "transition"

def detect_category(message):
    message_lower = message.lower()
    categories = {
        "cybersecurity": ["cybersecurity", "security", "cyber", "hacking", "protection", "penetration", "ethical hacker", "network security", "information security", "data protection", "vulnerability", "firewall", "malware", "infosec", "أمن", "سيبراني", "اختراق", "حماية", "أمن المعلومات", "جدار ناري", "أمن الشبكات"],
        "programming": ["programming", "coding", "software", "development", "web", "developer", "backend", "frontend", "full stack", "fullstack", "app development", "application", "code", "programmer", "software engineer", "web dev", "برمجة", "تطوير", "مطور", "تطبيقات", "مواقع", "كود", "هندسة برمجيات"],
        "ai": ["ai", "artificial intelligence", "machine learning", "ml", "neural", "deep learning", "nlp", "natural language", "computer vision", "data science", "neural network", "algorithm", "model", "training", "prediction", "data scientist", "ml engineer", "ذكاء", "اصطناعي", "تعلم آلي", "تعلم عميق", "بيانات", "نموذج", "علم بيانات"],
        "accounting": ["accounting", "finance", "financial", "cpa", "cma", "audit", "bookkeeping", "tax", "budget", "accountant", "financial statement", "balance sheet", "forensic accounting", "محاسبة", "مالي", "تدقيق", "محاسب", "قوائم مالية", "ضرائب", "محاسبة مالية"],
        "business": ["business", "management", "entrepreneurship", "marketing", "mba", "strategy", "leadership", "project management", "operations", "hr", "human resources", "sales", "startup", "business analyst", "consulting", "أعمال", "تجارة", "تسويق", "إدارة", "ريادة", "مشروع", "قيادة", "تحليل أعمال"],
        "law": ["law", "legal", "lawyer", "attorney", "litigation", "contract", "court", "judge", "legal system", "jurisprudence", "legislation", "paralegal", "قانون", "قضائي", "محامي", "عقد", "محكمة", "قاضي", "نظام قانوني", "قانوني"],
    }
    
    for category, keywords in categories.items():
        if any(keyword in message_lower for keyword in keywords):
            return category
    return None

def detect_major_from_message(message):
    message_lower = message.lower()
    for major_key, category in major_to_courses.items():
        if major_key.lower() in message_lower:
            return category
    return None

def get_courses_by_major(major):
    if major and major in major_to_courses:
        category = major_to_courses[major]
        return get_course_recommendations(category)
    elif major and major in courses_data:
        return get_course_recommendations(major)
    return []

def detect_language(message):
    arabic_pattern = re.compile(r'[\u0600-\u06FF]')
    if arabic_pattern.search(message):
        return "ar"
    return "en"

def get_course_recommendations(category):
    if category and category in courses_data:
        return courses_data[category]
    return []

def get_roadmap(category, lang="en"):
    if category and category in roadmaps:
        return roadmaps[category].get(lang, roadmaps[category]["en"])
    return None

def extract_intent(message, context, history):
    message_lower = message.lower().strip()
    
    affirmative = ["yes", "yeah", "yep", "sure", "ok", "okay", "alright", "نعم", "أجل", "حسنا", "تمام", "موافق"]
    if any(word in message_lower for word in affirmative) and len(message_lower) < 15:
        if context.current_intent == "roadmap":
            return "roadmap_details"
        elif context.current_intent == "courses":
            return "course_details"
        elif context.current_intent == "career_info":
            return "career_deep_dive"
        elif context.last_topic:
            return "continue_topic"
        return "proceed"
    
    if any(word in message_lower for word in ["roadmap", "path", "plan", "مسار", "خارطة", "طريق", "خطة"]):
        return "roadmap"
    
    if any(word in message_lower for word in ["course", "دورة", "recommend", "أنصح"]):
        return "courses"
    
    if any(word in message_lower for word in ["salary", "راتب", "job market", "سوق العمل", "income", "دخل"]):
        return "career_info"
    
    if any(word in message_lower for word in ["transition", "تحول", "switch", "تغيير", "change career"]):
        return "transition"
    
    if any(word in message_lower for word in ["how", "كيف", "what", "ماذا", "explain", "اشرح"]):
        return "explanation"
    
    category = detect_category(message)
    if category:
        return "field_inquiry"
    
    return "general"

@app.route('/api/chat', methods=['POST'])
def chat():
    try:
        data = request.json
        message = data.get('message', '')
        conversation_history = data.get('history', [])
        user_lang = detect_language(message)
        
        if not message:
            return jsonify({"error": "Message is required"}), 400
        
        context = ConversationContext()
        context.update_from_history(conversation_history)
        
        detected_category = detect_category(message)
        detected_major = detect_major_from_message(message)
        
        if not detected_category and conversation_history:
            for msg in reversed(conversation_history[-5:]):
                if msg.get("user"):
                    temp_category = detect_category(msg.get("user", ""))
                    if temp_category:
                        detected_category = temp_category
                        break
                    if not detected_major:
                        temp_major = detect_major_from_message(msg.get("user", ""))
                        if temp_major:
                            detected_major = temp_major
        
        if detected_major:
            recommendations = get_courses_by_major(detected_major)
            if not recommendations:
                recommendations = get_course_recommendations(detected_major)
            if not detected_category:
                detected_category = detected_major
            context.major = detected_major
        else:
            recommendations = get_course_recommendations(detected_category)
        
        if detected_category:
            context.selected_field = detected_category
        
        roadmap = get_roadmap(detected_category, user_lang)
        
        intent = extract_intent(message, context, conversation_history)
        
        enrollment_keywords = ["enrollment", "registration", "how to enroll", "how to register", "sign up", "تسجيل", "كيف أسجل", "كيف أتسجل", "التسجيل", "إجراءات التسجيل"]
        if any(keyword in message.lower() for keyword in enrollment_keywords):
            if user_lang == "ar":
                enrollment_response = """تعليمات التسجيل في الدورات:

الخطوة 1: استكشف الدورات المتاحة
تصفح صفحة الدورات الرئيسية واختر التخصص الذي يهمك.

الخطوة 2: اختر الدورة المناسبة
اضغط على زر "سجل الآن" أسفل بطاقة الدورة.

الخطوة 3: املأ نموذج التسجيل
- الاسم الكامل
- البريد الإلكتروني
- رقم الهاتف
- الرقم الوطني

الخطوة 4: تأكيد التسجيل
راجع المعلومات واضغط "تأكيد التسجيل".

الخطوة 5: ما يحدث بعد التسجيل
سيتم إرسال طلبك إلى فريق المحاسبة للمراجعة. سيتواصل معك المحاسب خلال 24-48 ساعة.

الخطوة 6: إكمال الإجراءات المالية
بعد استلام الدفعة، سيتم إنشاء حسابك وتفعيل الوصول إلى محتوى الدورة."""
            else:
                enrollment_response = """Course Enrollment Instructions:

Step 1: Explore Available Courses
Browse the main courses page and choose your field of interest.

Step 2: Select Your Course
Click the "Enroll Now" button below the course card.

Step 3: Fill Out the Enrollment Form
- Full Name
- Email Address
- Phone Number
- National ID

Step 4: Confirm Enrollment
Review the information and click "Confirm Enrollment".

Step 5: What Happens After Enrollment
Your request will be sent to the accounting team for review. An accountant will contact you within 24-48 hours.

Step 6: Complete Financial Procedures
After payment is received, your account will be created and course content access will be activated."""
            
            return jsonify({
                "response": enrollment_response,
                "recommendations": [],
                "category": None,
                "roadmap": None
            })
        
        system_prompt = f"""You are a senior academic advisor and career consultant embedded in an education platform. Your role is to GUIDE, not sell, not reset, not loop.

ABSOLUTE RULES:
1. RESPOND TO USER'S LAST MESSAGE ONLY - Never respond with marketing text, never reintroduce yourself, never list capabilities unless explicitly asked.
2. ACKNOWLEDGE USER INPUT - If user gives a name, confirm and use it. If user says "yes/okay", continue logically. If user selects a field, stay in that field forever unless changed.
3. CONTEXT IS PERMANENT - Store and reuse: name, selected field, current intent. Never ask again for something already provided.
4. NEVER RESET - Forbidden phrases: "Tell me which field interests you", "I can help you with...", "Ask me anything", any capability list.
5. NO DUPLICATION - If roadmap was shown, do NOT show it again. If courses were listed, do NOT relist unless asked. If user said YES, MOVE FORWARD, not sideways.
6. INTENT-DRIVEN LOGIC - Interpret intent from last message. "yes" → proceed to next logical step. Single keyword (e.g. "cybersecurity") → deep dive, not overview.
7. ANSWER FIRST, ASK LATER - Provide value BEFORE asking follow-up. Only ask ONE short clarifying question if absolutely necessary.
8. OUTPUT FORMAT - Short paragraphs, clear sections, no emojis unless user uses them, no hype language, no sales tone.
9. SELF-CHECK - Ask: "Does this directly answer the user's last message?" If NO → rewrite.

CONTEXT FROM CONVERSATION:
- User name: {context.user_name or "Not provided"}
- Selected field: {context.selected_field or "Not selected"}
- Current intent: {context.current_intent or "General inquiry"}
- User major: {context.major or "Not mentioned"}
- Detected category: {detected_category or "None"}

AVAILABLE COURSES:
{json.dumps(courses_data, indent=2, ensure_ascii=False)}

AVAILABLE ROADMAPS:
{json.dumps({k: v.get(user_lang, v.get("en", "")) for k, v in roadmaps.items()}, indent=2, ensure_ascii=False)}

CURRENT INTENT DETECTED: {intent}

RESPONSE GUIDELINES:
- Language: Always respond in {user_lang}
- If user said "yes/okay" and we were discussing {context.current_intent}, provide MORE DETAILED information about that topic
- If user mentioned a field ({detected_category}), provide deep dive, not overview
- If user mentioned their major ({context.major}), recommend relevant courses and explain why they're beneficial
- Never repeat information already provided in conversation history
- Never ask "what field interests you" if field is already known
- Be decisive. If user is vague, assume they want the most practical next step
- Act like a senior academic advisor, not a chatbot demo or landing page"""

        user_messages = [{"role": "system", "content": system_prompt}]
        
        for hist in conversation_history[-20:]:
            if hist.get("user"):
                user_messages.append({"role": "user", "content": hist.get("user")})
            if hist.get("bot"):
                user_messages.append({"role": "assistant", "content": hist.get("bot")})
        
        user_messages.append({"role": "user", "content": message})
        
        if openai.api_key:
            try:
                response = openai.ChatCompletion.create(
                    model="gpt-4o-mini",
                    messages=user_messages,
                    temperature=0.7,
                    max_tokens=2000,
                    top_p=0.95,
                    frequency_penalty=0.3,
                    presence_penalty=0.2
                )
                bot_response = response.choices[0].message.content.strip()
                
                if not bot_response:
                    bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
                
            except Exception as e:
                print(f"OpenAI API Error: {str(e)}")
                bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
        else:
            bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
        
        if not isinstance(bot_response, str):
            bot_response = str(bot_response) if bot_response else "I apologize, but I encountered an issue. Could you please rephrase your question?"
        
        return jsonify({
            "response": bot_response,
            "recommendations": recommendations[:3] if recommendations else [],
            "category": detected_category,
            "roadmap": roadmap if intent == "roadmap" and roadmap else None
        })
    
    except Exception as e:
        import traceback
        error_trace = traceback.format_exc()
        print(f"Chat API Error: {str(e)}")
        print(f"Traceback: {error_trace}")
        error_response = "I apologize, but I encountered an error processing your request. Please try rephrasing your question."
        return jsonify({"error": str(e), "response": error_response}), 500

def generate_contextual_fallback(message, category, recommendations, roadmap, lang, context):
    message_lower = message.lower().strip()
    
    if context.user_name:
        greeting = f"Hello {context.user_name}" if lang == "en" else f"مرحباً {context.user_name}"
    else:
        greeting = "Hello" if lang == "en" else "مرحباً"
    
    if category and recommendations:
        if lang == "ar":
            return f"{greeting}. بناءً على اهتمامك بـ {category}، أنصحك بالدورات التالية:\n\n" + "\n".join([f"• {r['title']} - ${r['price']} - {r['hours']} ساعة" for r in recommendations[:3]])
        else:
            return f"{greeting}. Based on your interest in {category}, I recommend these courses:\n\n" + "\n".join([f"• {r['title']} - ${r['price']} - {r['hours']} hours" for r in recommendations[:3]])
    
    if lang == "ar":
        return f"{greeting}. كيف يمكنني مساعدتك اليوم؟"
    else:
        return f"{greeting}. How can I help you today?"

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
