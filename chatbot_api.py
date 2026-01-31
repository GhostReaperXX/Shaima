from flask import Flask, request, jsonify
from flask_cors import CORS
import openai
import os
from dotenv import load_dotenv
import json
import re
import time

load_dotenv()

# Debug log path
DEBUG_LOG_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), '.cursor', 'debug.log')

def write_debug_log(data):
    """Helper function to write debug logs safely"""
    try:
        os.makedirs(os.path.dirname(DEBUG_LOG_PATH), exist_ok=True)
        with open(DEBUG_LOG_PATH, 'a', encoding='utf-8') as f:
            f.write(json.dumps(data, ensure_ascii=False) + '\n')
    except Exception as e:
        # Print error to console for debugging
        print(f"Debug log error: {e}")
        pass

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
        # #region agent log
        write_debug_log({"id":"log_history_update","timestamp":time.time()*1000,"location":"chatbot_api.py:414","message":"Updating context from history","data":{"history_length":len(history),"processing_last":min(20,len(history))},"sessionId":"debug-session","runId":"run1","hypothesisId":"A"})
        # #endregion agent log
        
        # Look at more history (last 20 messages) to better preserve context
        for msg in reversed(history[-20:]):
            user_msg = msg.get("user", "").lower()
            bot_msg = msg.get("bot", "").lower()
            
            if not self.user_name:
                name_patterns = [
                    r"my name is (\w+)",
                    r"i'm (\w+)",
                    r"i am (\w+)",
                    r"call me (\w+)",
                    r"اسمي (\w+)",
                    r"أنا (\w+)",
                    r"أنا اسمي (\w+)",
                    r"^(\w{2,15})$"  # Single word (2-15 chars) might be a name if it's a standalone message
                ]
                for pattern in name_patterns:
                    match = re.search(pattern, user_msg)
                    if match:
                        potential_name = match.group(1)
                        # Filter out common words that aren't names
                        common_words = {"hi", "hello", "hey", "yes", "no", "ok", "okay", "thanks", "thank", "help", 
                                       "مرحبا", "نعم", "لا", "شكرا", "مساعدة", "مساعدة"}
                        if potential_name not in common_words:
                            self.user_name = potential_name
                            # #region agent log
                            write_debug_log({"id":"log_name_extracted","timestamp":time.time()*1000,"location":"chatbot_api.py:430","message":"Name extracted from history","data":{"name":self.user_name,"pattern":pattern,"user_msg":user_msg[:30]},"sessionId":"debug-session","runId":"run1","hypothesisId":"A"})
                            # #endregion agent log
                            break
                if self.user_name:
                    break
            
            if not self.selected_field:
                detected = detect_category(msg.get("user", ""))
                if detected:
                    self.selected_field = detected
            
            if not self.major:
                detected_major = detect_major_from_message(msg.get("user", ""))
                if detected_major:
                    self.major = detected_major
            
            # Update intent from bot messages to track conversation flow
            if "roadmap" in bot_msg or "خارطة" in bot_msg or "مسار" in bot_msg:
                self.current_intent = "roadmap"
            elif "course" in bot_msg or "دورة" in bot_msg:
                self.current_intent = "courses"
            elif any(word in bot_msg for word in ["salary", "راتب", "job market", "سوق العمل", "career", "مهنة", "advice", "نصيحة"]):
                self.current_intent = "career_info"
            elif "transition" in bot_msg or "تحول" in bot_msg:
                self.current_intent = "transition"
            
            # Also check user messages for intent
            if any(word in user_msg for word in ["career", "مهنة", "job", "وظيفة", "salary", "راتب", "advice", "نصيحة", "guidance", "إرشاد"]):
                self.current_intent = "career_info"
            elif any(word in user_msg for word in ["roadmap", "خارطة", "مسار", "path", "طريق"]):
                self.current_intent = "roadmap"
            elif any(word in user_msg for word in ["course", "دورة"]):
                self.current_intent = "courses"

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
    
    # #region agent log
    write_debug_log({"id":"log_intent_check","timestamp":time.time()*1000,"location":"chatbot_api.py:498","message":"Extracting intent","data":{"message_preview":message_lower[:50],"current_intent":context.current_intent},"sessionId":"debug-session","runId":"run1","hypothesisId":"B"})
    # #endregion agent log
    
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
    
    # Expanded career-related keywords
    career_keywords = [
        "salary", "راتب", "job market", "سوق العمل", "income", "دخل", 
        "career", "مهنة", "job", "وظيفة", "employment", "توظيف",
        "advice", "نصيحة", "guidance", "إرشاد", "help", "مساعدة",
        "future", "مستقبل", "opportunities", "فرص", "prospects", "آفاق",
        "what can i do", "ماذا يمكنني", "how to start", "كيف أبدأ",
        "career path", "مسار مهني", "job opportunities", "فرص عمل"
    ]
    if any(keyword in message_lower for keyword in career_keywords):
        return "career_info"
    
    if any(word in message_lower for word in ["transition", "تحول", "switch", "تغيير", "change career"]):
        return "transition"
    
    if any(word in message_lower for word in ["how", "كيف", "what", "ماذا", "explain", "اشرح"]):
        # Check if it's a career-related question
        if any(word in message_lower for word in ["career", "job", "salary", "مهنة", "وظيفة", "راتب"]):
            return "career_info"
        return "explanation"
    
    category = detect_category(message)
    if category:
        return "field_inquiry"
    
    # #region agent log
    write_debug_log({"id":"log_intent_result","timestamp":time.time()*1000,"location":"chatbot_api.py:532","message":"Intent extraction result","data":{"intent":"general","message":message_lower[:50]},"sessionId":"debug-session","runId":"run1","hypothesisId":"B"})
    # #endregion agent log
    
    return "general"

@app.route('/api/chat', methods=['POST'])
def chat():
    try:
        # #region agent log
        write_debug_log({"id":"log_entry","timestamp":time.time()*1000,"location":"chatbot_api.py:536","message":"Chat endpoint called","data":{"message_length":len(request.json.get('message','')),"history_length":len(request.json.get('history',[]))},"sessionId":"debug-session","runId":"run1","hypothesisId":"A"})
        # #endregion agent log
        
        data = request.json
        message = data.get('message', '')
        conversation_history = data.get('history', [])
        user_lang = detect_language(message)
        
        if not message:
            return jsonify({"error": "Message is required"}), 400
        
        context = ConversationContext()
        context.update_from_history(conversation_history)
        
        # Also check current message for name if not found in history
        if not context.user_name:
            name_patterns = [
                r"my name is (\w+)",
                r"i'm (\w+)",
                r"i am (\w+)",
                r"call me (\w+)",
                r"اسمي (\w+)",
                r"أنا (\w+)",
                r"أنا اسمي (\w+)",
            ]
            message_lower = message.lower()
            for pattern in name_patterns:
                match = re.search(pattern, message_lower)
                if match:
                    potential_name = match.group(1)
                    common_words = {"hi", "hello", "hey", "yes", "no", "ok", "okay", "thanks", "thank", "help"}
                    if potential_name not in common_words and len(potential_name) >= 2:
                        context.user_name = potential_name
                        write_debug_log({"id":"log_name_current","timestamp":time.time()*1000,"location":"chatbot_api.py:630","message":"Name extracted from current message","data":{"name":context.user_name},"sessionId":"debug-session","runId":"run1","hypothesisId":"A"})
                        break
        
        # #region agent log
        write_debug_log({"id":"log_context","timestamp":time.time()*1000,"location":"chatbot_api.py:647","message":"Context after update_from_history","data":{"user_name":context.user_name,"selected_field":context.selected_field,"major":context.major,"current_intent":context.current_intent},"sessionId":"debug-session","runId":"run1","hypothesisId":"A"})
        # #endregion agent log
        
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
        
        # #region agent log
        write_debug_log({"id":"log_intent","timestamp":time.time()*1000,"location":"chatbot_api.py:578","message":"Intent extracted","data":{"intent":intent,"detected_category":detected_category,"message_preview":message[:50]},"sessionId":"debug-session","runId":"run1","hypothesisId":"B"})
        # #endregion agent log
        
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
        
        roadmap = get_roadmap(detected_category, user_lang)
        
        intent = extract_intent(message, context, conversation_history)
        
        # #region agent log
        write_debug_log({"id":"log_intent","timestamp":time.time()*1000,"location":"chatbot_api.py:671","message":"Intent extracted","data":{"intent":intent,"detected_category":detected_category,"message_preview":message[:50]},"sessionId":"debug-session","runId":"run1","hypothesisId":"B"})
        # #endregion agent log
        
        # Build personalized greeting if name is available
        name_greeting = ""
        if context.user_name and context.user_name != "Not provided":
            if user_lang == "ar":
                name_greeting = f"مرحباً {context.user_name}، "
            else:
                name_greeting = f"{context.user_name}, "
        
        # Determine if this is primarily a career question
        is_career_question = intent == "career_info" or any(word in message.lower() for word in ["career", "job", "salary", "مهنة", "وظيفة", "راتب", "advice", "نصيحة"])
        
        # Build the most explicit system prompt possible
        name_instruction = ""
        if context.user_name and context.user_name != "Not provided":
            if user_lang == "ar":
                name_instruction = f"يجب أن تبدأ ردك بـ '{context.user_name}،' في كل مرة. استخدم الاسم '{context.user_name}' بشكل طبيعي في ردك."
            else:
                name_instruction = f"You MUST start your response with '{context.user_name},' every single time. Use the name '{context.user_name}' naturally throughout your response."
        else:
            name_instruction = "If the user provides their name, immediately acknowledge it and use it in all future responses."
        
        career_instruction = ""
        if is_career_question:
            if user_lang == "ar":
                career_instruction = "هذا سؤال مهني - قدم إرشادات مهنية مفصلة أولاً (سوق العمل، الرواتب، المسارات المهنية، المهارات المطلوبة)، ثم اذكر الدورات إذا كانت ذات صلة. لا تعتمد على خرائط الطريق فقط."
            else:
                career_instruction = "THIS IS A CAREER QUESTION - Provide detailed career guidance FIRST (job market, salaries, career paths, skills needed), THEN mention courses if relevant. DO NOT default to roadmaps."
        else:
            career_instruction = "Answer the user's question directly and helpfully."
        
        system_prompt = f"""You are a senior academic advisor and career consultant embedded in an education platform. Your role is to GUIDE, not sell, not reset, not loop.

CRITICAL REQUIREMENTS (MUST FOLLOW):
1. USER'S NAME: {context.user_name if context.user_name else "NOT PROVIDED YET"}
   {name_instruction}
   
2. CAREER ADVICE PRIORITY: {career_instruction}

ABSOLUTE RULES:
1. RESPOND TO USER'S LAST MESSAGE ONLY - Never respond with marketing text, never reintroduce yourself, never list capabilities unless explicitly asked.
2. MANDATORY NAME USAGE - {"YOU MUST START YOUR RESPONSE WITH: '" + name_greeting + "' if name is available. Use the name naturally throughout your response." if context.user_name else "If the user provides their name, immediately acknowledge it and use it in all future responses."}
3. ACKNOWLEDGE USER INPUT - If user gives a name, confirm and use it. If user says "yes/okay", continue logically. If user selects a field, stay in that field forever unless changed.
4. CONTEXT IS PERMANENT - Store and reuse: name, selected field, current intent. Never ask again for something already provided.
5. NEVER RESET - Forbidden phrases: "Tell me which field interests you", "I can help you with...", "Ask me anything", any capability list.
6. NO DUPLICATION - If roadmap was shown, do NOT show it again. If courses were listed, do NOT relist unless asked. If user said YES, MOVE FORWARD, not sideways.
7. INTENT-DRIVEN LOGIC - Interpret intent from last message. "yes" → proceed to next logical step. Single keyword (e.g. "cybersecurity") → deep dive, not overview.
8. ANSWER FIRST, ASK LATER - Provide value BEFORE asking follow-up. Only ask ONE short clarifying question if absolutely necessary.
9. OUTPUT FORMAT - Short paragraphs, clear sections, no emojis unless user uses them, no hype language, no sales tone.
10. SELF-CHECK - Ask: "Does this directly answer the user's last message?" If NO → rewrite.
11. CAREER ADVICE IS PRIMARY - When users ask about careers, job market, salaries, career paths, or advice about their major, provide detailed, professional career guidance FIRST. Don't default to course roadmaps. Give real career insights: job market trends, salary ranges, career progression paths, required skills, industry outlook, and practical advice for entering/advancing in the field. THEN mention courses if relevant.
12. REMEMBER CONTEXT - Reference previous conversation topics naturally. If user mentioned their major earlier, remember it. If they asked about careers, continue that thread.

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
- {"MANDATORY: Start your response with the user's name: '" + context.user_name + "'" if context.user_name and context.user_name != "Not provided" else "If user provides their name, use it immediately"}
- If user said "yes/okay" and we were discussing {context.current_intent}, provide MORE DETAILED information about that topic
- If user mentioned a field ({detected_category}), provide deep dive, not overview
- If user mentioned their major ({context.major}), provide comprehensive guidance: career paths, job market, salary expectations, required skills, AND recommend relevant courses
- When users ask about careers, salaries, job market, or career advice: provide detailed professional guidance with real insights FIRST, then mention courses if relevant. Don't default to roadmaps.
- For career questions, include: job market outlook, typical salary ranges, career progression paths, required skills/qualifications, industry trends, and practical next steps
- Never repeat information already provided in conversation history
- Never ask "what field interests you" if field is already known
- Be decisive. If user is vague, assume they want the most practical next step
- Act like a senior academic advisor and career counselor, providing both academic and career guidance
- Remember the full conversation context - reference previous topics naturally when relevant
- If intent is "career_info", prioritize career guidance over course recommendations"""

        user_messages = [{"role": "system", "content": system_prompt}]
        
        for hist in conversation_history[-20:]:
            if hist.get("user"):
                user_messages.append({"role": "user", "content": hist.get("user")})
            if hist.get("bot"):
                user_messages.append({"role": "assistant", "content": hist.get("bot")})
        
        # Add explicit instruction to the user message if this is a career question
        enhanced_message = message
        if intent == "career_info" or any(word in message.lower() for word in ["career", "job", "salary", "مهنة", "وظيفة", "راتب", "advice", "نصيحة", "guidance", "إرشاد"]):
            if user_lang == "ar":
                enhanced_message = f"{message}\n\n[ملاحظة: يرجى تقديم نصائح مهنية مفصلة حول سوق العمل والرواتب والمسار المهني، وليس فقط خارطة طريق للدورات]"
            else:
                enhanced_message = f"{message}\n\n[Note: Please provide detailed career guidance about job market, salaries, and career paths, not just course roadmaps]"
        
        user_messages.append({"role": "user", "content": enhanced_message})
        
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
                
                # Debug: Print context info
                print(f"[DEBUG] User name in context: {context.user_name}")
                print(f"[DEBUG] Intent: {intent}")
                print(f"[DEBUG] Response preview: {bot_response[:100] if bot_response else 'Empty'}")
                
                # #region agent log
                write_debug_log({"id":"log_response","timestamp":time.time()*1000,"location":"chatbot_api.py:833","message":"OpenAI response received","data":{"response_length":len(bot_response) if bot_response else 0,"has_name":context.user_name in bot_response if context.user_name and bot_response else False,"user_name":context.user_name,"intent":intent},"sessionId":"debug-session","runId":"run1","hypothesisId":"C"})
                # #endregion agent log
                
                # Post-process: Ensure name is used if available
                if context.user_name and context.user_name != "Not provided":
                    # Check if name is already in response (case-insensitive)
                    name_in_response = context.user_name.lower() in bot_response.lower()
                    print(f"[DEBUG] Name '{context.user_name}' in response: {name_in_response}")
                    
                    if not name_in_response:
                        # Add name greeting at the beginning
                        if user_lang == "ar":
                            bot_response = f"{context.user_name}، {bot_response}"
                        else:
                            bot_response = f"{context.user_name}, {bot_response}"
                        print(f"[DEBUG] Added name to response: {bot_response[:100]}")
                        write_debug_log({"id":"log_name_added","timestamp":time.time()*1000,"location":"chatbot_api.py:848","message":"Name added to response","data":{"name":context.user_name},"sessionId":"debug-session","runId":"run1","hypothesisId":"C"})
                
                if not bot_response:
                    bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
                
            except Exception as e:
                print(f"OpenAI API Error: {str(e)}")
                bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
        else:
            bot_response = generate_contextual_fallback(message, detected_category, recommendations, roadmap, user_lang, context)
        
        # Post-process fallback responses too: Ensure name is used if available
        if context.user_name and context.user_name != "Not provided":
            if context.user_name.lower() not in bot_response.lower():
                if user_lang == "ar":
                    bot_response = f"{context.user_name}، {bot_response}"
                else:
                    bot_response = f"{context.user_name}, {bot_response}"
        
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
    
    # #region agent log
    write_debug_log({"id":"log_fallback","timestamp":time.time()*1000,"location":"chatbot_api.py:724","message":"Using fallback response","data":{"has_category":bool(category),"has_recommendations":bool(recommendations),"has_name":bool(context.user_name)},"sessionId":"debug-session","runId":"run1","hypothesisId":"E"})
    # #endregion agent log
    
    if context.user_name:
        greeting = f"Hello {context.user_name}" if lang == "en" else f"مرحباً {context.user_name}"
    else:
        greeting = "Hello" if lang == "en" else "مرحباً"
    
    # Check if this is a career-related question
    career_keywords = ["career", "job", "salary", "مهنة", "وظيفة", "راتب", "advice", "نصيحة"]
    is_career_question = any(keyword in message_lower for keyword in career_keywords)
    
    if is_career_question and category:
        # Provide career advice instead of just courses
        if lang == "ar":
            return f"{greeting}. بناءً على اهتمامك بمجال {category}، إليك بعض النصائح المهنية:\n\n" + \
                   f"• سوق العمل في {category} يتطلب مهارات تقنية قوية\n" + \
                   f"• الراتب المتوقع يتراوح بين $30,000 - $80,000 سنوياً حسب الخبرة\n" + \
                   f"• المسار المهني: مبتدئ → متوسط → خبير → قائد فريق\n" + \
                   f"• المهارات المطلوبة: معرفة تقنية عميقة، حل المشكلات، العمل الجماعي\n\n" + \
                   f"هل تريد معرفة المزيد عن الدورات التدريبية المتاحة في هذا المجال؟"
        else:
            return f"{greeting}. Based on your interest in {category}, here's some career guidance:\n\n" + \
                   f"• The job market in {category} requires strong technical skills\n" + \
                   f"• Expected salary ranges from $30,000 - $80,000 annually depending on experience\n" + \
                   f"• Career path: Entry-level → Mid-level → Senior → Team Lead\n" + \
                   f"• Required skills: Deep technical knowledge, problem-solving, teamwork\n\n" + \
                   f"Would you like to know more about available training courses in this field?"
    
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
