<?php
// إعدادات الأمان للجلسة - توضع BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// الآن يمكن بدء الجلسة
session_start();

require_once 'config/database.php';

// متغيرات الخطأ والنجاح
$error = "";
$success = "";

// مسار الشعار
$logo_path = 'img/logo.png';
$logo_exists = file_exists($logo_path);

// التحقق من وجود مجلد admin وملف dashboard.php
$dashboard_exists = file_exists('admin/dashboard.php');

// حل مشكلة إعادة التوجيه اللانهائية
if(isset($_SESSION['admin'])) {
    // التحقق من أننا لسنا في صفحة login أصلاً
    $current_page = basename($_SERVER['PHP_SELF']);
    
    if($current_page == 'login.php') {
        if($dashboard_exists) {
            header("Location: admin/dashboard.php");
            exit();
        } else {
            // إذا كان الملف غير موجود، قم بتدمير الجلسة
            session_destroy();
            $error = "تم تسجيل الخروج تلقائياً. الرجاء المحاولة مرة أخرى.";
        }
    }
}

// معالجة تسجيل الدخول
if(isset($_POST['login'])){
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // التحقق من عدم ترك الحقول فارغة
    if(empty($username) || empty($password)) {
        $error = "يرجى إدخال اسم المستخدم وكلمة المرور";
    } else {
        try {
            // البحث عن المستخدم
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            // التحقق من كلمة المرور
            if($user && password_verify($password, $user['password'])){
                // إعادة تجديد معرف الجلسة لمنع هجمات تثبيت الجلسة
                session_regenerate_id(true);
                
                // تخزين معلومات المستخدم في الجلسة
                $_SESSION['admin'] = $user['id'];
                $_SESSION['admin_name'] = $user['username'];
                $_SESSION['admin_role'] = $user['role'] ?? 'admin';
                $_SESSION['last_activity'] = time();
                
                // تحديث آخر تسجيل دخول في قاعدة البيانات (إذا كان العمود موجود)
                try {
                    $stmt = $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                } catch(PDOException $e) {
                    // تجاهل الخطأ إذا كان العمود غير موجود
                }
                
                // تسجيل نجاح الدخول
                error_log("Successful login for user: " . $username);
                
                // التوجيه إلى لوحة التحكم
                header("Location: admin/dashboard.php");
                exit();
            } else {
                $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
                error_log("Failed login attempt for user: " . $username);
            }
        } catch(PDOException $e) {
            $error = "حدث خطأ في قاعدة البيانات. الرجاء المحاولة مرة أخرى.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}

// إنشاء token CSRF جديد (اختياري)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>تسجيل الدخول | لوحة تحكم مشاوي الإخوة</title>
    <meta name="description" content="تسجيل الدخول إلى لوحة تحكم مطعم مشاوي الإخوة">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Background Animation */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            top: -150px;
            right: -150px;
            border-radius: 50%;
            animation: float 20s infinite;
        }
        
        body::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            bottom: -200px;
            left: -200px;
            border-radius: 50%;
            animation: float 15s infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(50px, 50px); }
        }
        
        /* Particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .particle {
            position: absolute;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: particleFloat 10s infinite;
        }
        
        @keyframes particleFloat {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-100px) rotate(180deg); }
        }
        
        /* Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
        }
        
        /* Login Header */
        .login-header {
            background: linear-gradient(135deg, #c62828, #ff9800);
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '🍽️';
            position: absolute;
            font-size: 120px;
            opacity: 0.1;
            bottom: -30px;
            left: -30px;
            transform: rotate(-15deg);
        }
        
        .login-header::after {
            content: '👨‍🍳';
            position: absolute;
            font-size: 100px;
            opacity: 0.1;
            top: -30px;
            right: -30px;
            transform: rotate(15deg);
        }
        
        /* Logo Styles */
        .logo-container {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            overflow: hidden;
            animation: pulse 2s infinite;
            backdrop-filter: blur(5px);
        }
        
        .logo-img {
            width: 80%;
            height: 80%;
            object-fit: contain;
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.2));
        }
        
        /* Fallback icon if logo doesn't exist */
        .logo-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Login Body */
        .login-body {
            padding: 40px;
        }
        
        /* Form Inputs */
        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #c62828;
            z-index: 10;
        }
        
        .form-control-custom {
            width: 100%;
            padding: 15px 50px 15px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
        }
        
        .form-control-custom:focus {
            outline: none;
            border-color: #ff9800;
            box-shadow: 0 0 0 4px rgba(255,152,0,0.1);
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .password-toggle:hover {
            color: #c62828;
        }
        
        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, #c62828, #ff9800);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 15px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(198,40,40,0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        /* Alert Messages */
        .alert-custom {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border: none;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger-custom {
            background: linear-gradient(135deg, #fee, #fdd);
            color: #c62828;
            border-right: 4px solid #c62828;
        }
        
        .alert-success-custom {
            background: linear-gradient(135deg, #efe, #dfd);
            color: #2e7d32;
            border-right: 4px solid #2e7d32;
        }
        
        /* Footer Links */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .login-footer a {
            color: #c62828;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #ff9800;
        }
        
        /* Security Badge */
        .security-badge {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #999;
        }
        
        /* Loading State */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .login-header {
                padding: 30px;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .logo-container {
                width: 70px;
                height: 70px;
            }
            
            .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="particles" id="particles"></div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <div class="login-card" data-aos="fade-up" data-aos-duration="800">
                <div class="login-header">
                    <!-- Logo instead of fork and knife -->
                    <div class="logo-container">
                        <?php if($logo_exists): ?>
                            <img src="<?php echo $logo_path; ?>" alt="Logo" class="logo-img">
                        <?php else: ?>
                            <div class="logo-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-white fw-bold mb-2">مشاوي الإخوة</h1>
                    <p class="text-white-50 mb-0">لوحة تحكم المطعم</p>
                </div>
                
                <div class="login-body">
                    
                    <!-- Error Message -->
                    <?php if($error): ?>
                    <div class="alert-custom alert-danger-custom" data-aos="fade-in">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Success Message -->
                    <?php if($success): ?>
                    <div class="alert-custom alert-success-custom" data-aos="fade-in">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Login Form -->
                    <form method="POST" action="" id="loginForm">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <!-- Username Field -->
                        <div class="input-group-custom">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   name="username" 
                                   class="form-control-custom" 
                                   placeholder="اسم المستخدم"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required
                                   autocomplete="username">
                        </div>
                        
                        <!-- Password Field -->
                        <div class="input-group-custom">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control-custom" 
                                   placeholder="كلمة المرور"
                                   required
                                   autocomplete="current-password">
                            <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        </div>
                        
                        <!-- Remember Me -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                                <label class="form-check-label" for="rememberMe">
                                    <i class="fas fa-check-circle text-muted"></i> تذكرني
                                </label>
                            </div>
                        </div>
                        
                        <!-- Login Button -->
                        <button type="submit" name="login" class="btn-login" id="loginBtn">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            دخول
                        </button>
                    </form>
                    
                    <!-- Footer Links -->
                    <div class="login-footer">
                        <a href="index.php">
                            <i class="fas fa-arrow-right"></i> العودة للموقع
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    
    if(togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
    
    // Form Submit Loading State
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    
    if(loginForm) {
        loginForm.addEventListener('submit', function() {
            loginBtn.classList.add('loading');
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحقق...';
        });
    }
    
    // Remember Me functionality
    const rememberCheckbox = document.getElementById('rememberMe');
    const usernameInput = document.querySelector('input[name="username"]');
    
    // Load saved username
    if(localStorage.getItem('rememberedUsername')) {
        usernameInput.value = localStorage.getItem('rememberedUsername');
        if(rememberCheckbox) rememberCheckbox.checked = true;
    }
    
    // Save username if remember me is checked
    if(rememberCheckbox) {
        rememberCheckbox.addEventListener('change', function() {
            if(this.checked) {
                localStorage.setItem('rememberedUsername', usernameInput.value);
            } else {
                localStorage.removeItem('rememberedUsername');
            }
        });
    }
    
    // Generate Particles
    function createParticles() {
        const particlesContainer = document.getElementById('particles');
        const particleCount = 50;
        
        for(let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            const size = Math.random() * 5 + 2;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = Math.random() * 10 + 5 + 's';
            
            particlesContainer.appendChild(particle);
        }
    }
    
    createParticles();
</script>

</body>
</html>