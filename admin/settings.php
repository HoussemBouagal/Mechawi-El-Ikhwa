<?php
// إعدادات الأمان للجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once '../config/database.php';

// التحقق من صلاحية المسؤول مباشرة
if(!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

// التحقق من أن المستخدم الحالي هو مسؤول (admin) فقط يمكنه الوصول
$current_admin_role = $_SESSION['admin_role'] ?? 'editor';

if($current_admin_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// جلب الإعدادات الحالية
try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    // إذا لم توجد إعدادات، نقوم بإنشاء سجل افتراضي
    if(!$settings) {
        $pdo->exec("
            INSERT INTO settings (id, site_name, whatsapp, facebook, telegram, email, address, delivery_fee, created_at, updated_at) 
            VALUES (1, 'مشاوي الإخوة', '213000000000', 'https://facebook.com/machaoui', '213000000000', 'info@machaoui.com', 'الجزائر العاصمة', 500, NOW(), NOW())
        ");
        $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
        $stmt->execute();
        $settings = $stmt->fetch();
    }
} catch(PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $error = "حدث خطأ في قاعدة البيانات";
}

// معالجة حفظ الإعدادات
if(isset($_POST['save'])){
    
    $site_name = trim($_POST['site_name']);
    $whatsapp = trim($_POST['whatsapp']);
    $facebook = trim($_POST['facebook']);
    $telegram = trim($_POST['telegram']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $delivery_fee = floatval($_POST['delivery_fee']);
    
    // التحقق من صحة البيانات
    if(empty($site_name)) {
        $error = "يرجى إدخال اسم الموقع";
    } elseif(empty($whatsapp)) {
        $error = "يرجى إدخال رقم واتساب";
    } elseif(empty($telegram)) {
        $error = "يرجى إدخال رقم تيليجرام";
    } elseif(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "البريد الإلكتروني غير صحيح";
    } elseif($delivery_fee < 0) {
        $error = "رسوم التوصيل يجب أن تكون رقم موجب";
    } else {
        try {
            // تنظيف رقم الواتساب (إزالة الأحرف غير الرقمية مع الاحتفاظ بعلامة +)
            $whatsapp = preg_replace('/[^0-9+]/', '', $whatsapp);
            // تنظيف رقم التيليجرام (إزالة الأحرف غير الرقمية مع الاحتفاظ بعلامة +)
            $telegram = preg_replace('/[^0-9+]/', '', $telegram);
            
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET site_name = ?, 
                    whatsapp = ?, 
                    facebook = ?, 
                    telegram = ?,
                    email = ?,
                    address = ?,
                    delivery_fee = ?,
                    updated_at = NOW()
                WHERE id = 1
            ");
            
            if($stmt->execute([
                $site_name, $whatsapp, $facebook, $telegram,
                $email, $address, $delivery_fee
            ])) {
                $success = "تم حفظ الإعدادات بنجاح!";
                
                // تحديث المتغيرات
                $settings['site_name'] = $site_name;
                $settings['whatsapp'] = $whatsapp;
                $settings['facebook'] = $facebook;
                $settings['telegram'] = $telegram;
                $settings['email'] = $email;
                $settings['address'] = $address;
                $settings['delivery_fee'] = $delivery_fee;
            } else {
                $error = "حدث خطأ أثناء حفظ الإعدادات";
            }
        } catch(PDOException $e) {
            error_log("Settings save error: " . $e->getMessage());
            $error = "حدث خطأ في قاعدة البيانات";
        }
    }
}

// اسم المسؤول
$admin_name = $_SESSION['admin_name'] ?? 'مسؤول';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات | مشاوي الإخوة</title>

    <link rel="icon" href="../img/adjust.ico" type="image/x-icon">

    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #f0f2f5;
        }
        
        :root {
            --primary: #c62828;
            --secondary: #ff9800;
            --success: #28a745;
            --dark: #1a1a2e;
            --sidebar-width: 280px;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 25px 0;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: -5px 0 20px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            text-align: center;
            padding: 0 20px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-header h3 {
            font-weight: 800;
            margin: 0;
        }
        
        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin: 5px 15px;
        }
        
        .sidebar-menu a {
            color: rgba(255,255,255,0.8);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-menu a i {
            width: 24px;
            font-size: 1.2rem;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(-5px);
        }
        
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #c62828, #ff9800);
            color: white;
            box-shadow: 0 5px 15px rgba(198,40,40,0.3);
        }
        
        /* Main Content */
        .main-content {
            margin-right: var(--sidebar-width);
            padding: 25px;
            min-height: 100vh;
        }
        
        /* Top Navbar */
        .top-navbar {
            background: white;
            border-radius: 15px;
            padding: 15px 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .welcome-text h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #c62828, #ff9800);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }
        
        /* Settings Card */
        .settings-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title i {
            color: var(--primary);
            margin-left: 10px;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark);
        }
        
        .form-control-custom, .form-select-custom {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-family: 'Cairo', sans-serif;
        }
        
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.2rem rgba(255,152,0,0.25);
            outline: none;
        }
        
        /* Preview Links */
        .preview-link {
            display: inline-block;
            margin-top: 5px;
            font-size: 0.8rem;
            color: var(--primary);
            text-decoration: none;
        }
        
        .preview-link:hover {
            color: var(--secondary);
        }
        
        /* Buttons */
        .btn-save {
            background: linear-gradient(135deg, var(--success), #218838);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        }
        
        .btn-save.loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        /* Alerts */
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
            color: var(--primary);
            border-right: 4px solid var(--primary);
        }
        
        .alert-success-custom {
            background: linear-gradient(135deg, #efe, #dfd);
            color: var(--success);
            border-right: 4px solid var(--success);
        }
        
        .info-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 5px;
        }
        
        /* Mobile Menu */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
            }
            .main-content {
                margin-right: 0;
                padding: 70px 15px 15px;
            }
            .sidebar.open {
                right: 0;
            }
            .menu-toggle {
                display: block;
            }
            .settings-card {
                padding: 20px;
            }
        }
        
        @media print {
            .sidebar, .menu-toggle, .top-navbar, .btn-save, .preview-link {
                display: none !important;
            }
            .main-content {
                margin-right: 0;
                padding: 0;
            }
            .settings-card {
                box-shadow: none;
                padding: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Menu Toggle -->
<button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>🔥 مشاوي الإخوة</h3>
        <p>لوحة تحكم المطعم</p>
    </div>
    
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> الرئيسية</a></li>
        <li><a href="foods.php"><i class="fas fa-utensils"></i> الأطباق</a></li>
        <li><a href="offers.php"><i class="fas fa-tags"></i> العروض</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> المستخدمين</a></li>
        <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> الإعدادات</a></li>
        <li><a href="../logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="welcome-text">
            <h2>مرحباً، <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
            <p class="text-muted mb-0">إعدادات وتخصيصات المطعم</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <!-- Error Alert -->
    <?php if($error): ?>
    <div class="alert-custom alert-danger-custom">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Success Alert -->
    <?php if($success): ?>
    <div class="alert-custom alert-success-custom">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="settingsForm">
        
        <!-- Basic Information -->
        <div class="settings-card">
            <h5 class="card-title">
                <i class="fas fa-info-circle"></i> المعلومات الأساسية
            </h5>
            
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">اسم الموقع / المطعم</label>
                    <input type="text" 
                           name="site_name" 
                           class="form-control form-control-custom" 
                           value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"
                           required>
                </div>
                
                <div class="col-12">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" 
                           name="email" 
                           class="form-control form-control-custom" 
                           value="<?php echo htmlspecialchars($settings['email'] ?? ''); ?>"
                           placeholder="info@example.com">
                    <div class="info-text">
                        <i class="fas fa-envelope"></i> سيتم استخدام هذا البريد للتواصل مع العملاء
                    </div>
                </div>
                
                <div class="col-12">
                    <label class="form-label">عنوان المطعم</label>
                    <textarea name="address" 
                              class="form-control form-control-custom" 
                              rows="2"
                              placeholder="العنوان الكامل للمطعم"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="settings-card">
            <h5 class="card-title">
                <i class="fas fa-phone-alt"></i> معلومات التواصل
            </h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">رقم واتساب</label>
                    <input type="tel" 
                           name="whatsapp" 
                           class="form-control form-control-custom" 
                           value="<?php echo htmlspecialchars($settings['whatsapp'] ?? ''); ?>"
                           placeholder="213000000000"
                           required>
                    <div class="info-text">
                        <i class="fab fa-whatsapp text-success"></i>
                        <?php if(!empty($settings['whatsapp'])): ?>
                        <a href="https://wa.me/<?php echo ltrim($settings['whatsapp'], '+'); ?>" 
                           target="_blank" 
                           class="preview-link">
                            معاينة الرابط
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">فيسبوك</label>
                    <input type="url" 
                           name="facebook" 
                           class="form-control form-control-custom" 
                           value="<?php echo htmlspecialchars($settings['facebook'] ?? ''); ?>"
                           placeholder="https://facebook.com/username">
                    <?php if(!empty($settings['facebook'])): ?>
                    <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" 
                       target="_blank" 
                       class="preview-link">
                        <i class="fab fa-facebook"></i> زيارة الصفحة
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">رقم تيليجرام</label>
                    <input type="tel" 
                           name="telegram" 
                           class="form-control form-control-custom" 
                           value="<?php echo htmlspecialchars($settings['telegram'] ?? ''); ?>"
                           placeholder="213000000000"
                           required>
                    <div class="info-text">
                        <i class="fab fa-telegram text-primary"></i>
                        <?php if(!empty($settings['telegram'])): ?>
                        <a href="https://t.me/<?php echo ltrim($settings['telegram'], '+'); ?>" 
                           target="_blank" 
                           class="preview-link">
                            <i class="fab fa-telegram"></i> معاينة الرابط
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Business Settings -->
        <div class="settings-card">
            <h5 class="card-title">
                <i class="fas fa-chart-line"></i> الإعدادات التجارية
            </h5>
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">رسوم التوصيل (DA)</label>
                    <input type="number" 
                           name="delivery_fee" 
                           class="form-control form-control-custom" 
                           value="<?php echo $settings['delivery_fee'] ?? 0; ?>"
                           step="0.01"
                           min="0">
                    <div class="info-text">
                        <i class="fas fa-truck"></i> رسوم التوصيل المضافة لكل طلب
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="text-center mt-4">
            <button type="submit" name="save" class="btn-save" id="saveBtn">
                <i class="fas fa-save me-2"></i> حفظ جميع الإعدادات
            </button>
        </div>
        
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if(menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if(window.innerWidth <= 768) {
            if(sidebar && menuToggle) {
                if(!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        }
    });
    
    // Form submit loading state
    const form = document.getElementById('settingsForm');
    const saveBtn = document.getElementById('saveBtn');
    
    if(form && saveBtn) {
        form.addEventListener('submit', function(e) {
            // Validate WhatsApp number
            const whatsapp = document.querySelector('input[name="whatsapp"]');
            if(whatsapp) {
                const whatsappValue = whatsapp.value.trim();
                if(whatsappValue && !/^[0-9+]+$/.test(whatsappValue)) {
                    e.preventDefault();
                    alert('رقم الواتساب يجب أن يحتوي على أرقام فقط (يمكن استخدام +)');
                    return;
                }
            }
            
            // Validate Telegram number
            const telegram = document.querySelector('input[name="telegram"]');
            if(telegram) {
                const telegramValue = telegram.value.trim();
                if(telegramValue && !/^[0-9+]+$/.test(telegramValue)) {
                    e.preventDefault();
                    alert('رقم التيليجرام يجب أن يحتوي على أرقام فقط (يمكن استخدام +)');
                    return;
                }
            }
            
            // Show loading state
            saveBtn.classList.add('loading');
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري الحفظ...';
        });
    }
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-custom');
        alerts.forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
</script>

</body>
</html>