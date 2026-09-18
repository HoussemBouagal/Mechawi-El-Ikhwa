<?php
// إعدادات الأمان للجلسة - توضع BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once '../config/database.php';

// التحقق من صلاحية المسؤول مع منع الحلقات اللانهائية
if(!isset($_SESSION['admin'])) {
    // منع التوجيه المتكرر
    if(!isset($_SESSION['redirect_attempt'])) {
        $_SESSION['redirect_attempt'] = 1;
        header("Location: ../login.php");
        exit();
    } else {
        // في حالة التوجيه المتكرر، قم بتدمير الجلسة
        session_destroy();
        die("خطأ في الجلسة. الرجاء <a href='../login.php'>تسجيل الدخول</a> مرة أخرى.");
    }
}

// إعادة تعيين محاولة التوجيه بعد نجاح التحقق
unset($_SESSION['redirect_attempt']);

// التحقق من انتهاء الجلسة (30 دقيقة)
$timeout = 1800; // 30 دقيقة
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// جلب الإحصائيات مع معالجة الأخطاء
try {
    $total_foods = $pdo->query("SELECT COUNT(*) FROM foods")->fetchColumn();
    $total_offers = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
    // تم حذف سطر total_orders
} catch(PDOException $e) {
    $total_foods = $total_offers = 0; // تم تعديل هذا السطر
    error_log("Dashboard stats error: " . $e->getMessage());
}

// تم حذف كود جلب آخر الطلبات بالكامل

// جلب آخر 5 أطباق مضافة
try {
    $recent_foods = $pdo->query("
        SELECT * FROM foods 
        ORDER BY id DESC 
        LIMIT 5
    ")->fetchAll();
} catch(PDOException $e) {
    $recent_foods = [];
    error_log("Recent foods error: " . $e->getMessage());
}

// اسم المسؤول
$admin_name = $_SESSION['admin_name'] ?? 'مسؤول';
$admin_role = $_SESSION['admin_role'] ?? 'admin';

// رسالة ترحيب من تسجيل الدخول
$login_success_message = $_SESSION['login_success'] ?? '';
unset($_SESSION['login_success']);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم | مشاوي الإخوة</title>
    
    <link rel="icon" href="../img/logo.ico" type="image/x-icon">

    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        
        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --secondary: #ff9800;
            --dark: #1a1a2e;
            --sidebar-width: 280px;
        }
        
        /* Alert Success for Login */
        .alert-login-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            color: #155724;
            border-right: 4px solid #28a745;
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            animation: slideInRight 0.5s ease;
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
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
        
        .welcome-text p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
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
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 15px;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, #c62828, #ff9800); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            margin: 10px 0 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Tables */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-top: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .table-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .table-custom {
            width: 100%;
        }
        
        .table-custom th {
            background: #f8f9fa;
            padding: 12px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .table-custom td {
            padding: 12px;
            vertical-align: middle;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                right: -280px;
            }
            .main-content {
                margin-right: 0;
            }
            .sidebar.open {
                right: 0;
            }
            .menu-toggle {
                display: block;
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
            .stat-number {
                font-size: 1.5rem;
            }
        }
        
        @media (min-width: 769px) {
            .menu-toggle {
                display: none;
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
        <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> الرئيسية</a></li>
        <li><a href="foods.php"><i class="fas fa-utensils"></i> الأطباق</a></li>
        <li><a href="offers.php"><i class="fas fa-tags"></i> العروض</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> المستخدمين</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
        <li><a href="../logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- رسالة الترحيب بعد تسجيل الدخول -->
    <?php if($login_success_message): ?>
    <div class="alert-login-success" id="loginSuccessAlert">
        <div class="d-flex align-items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-smile-wink fa-2x"></i>
            </div>
            <div class="flex-grow-1 me-3">
                <strong class="d-block"><?php echo htmlspecialchars($login_success_message); ?></strong>
                <small>مرحباً بعودتك إلى لوحة التحكم</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" onclick="this.parentElement.parentElement.style.display='none'"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="welcome-text">
            <h2>مرحباً، <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
            <p>مرحباً بعودتك! إليك ملخص أداء مطعمك اليوم</p>
        </div>
        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                <small class="text-muted d-block">
                    <?php echo $admin_role == 'admin' ? 'مدير المطعم' : 'موظف'; ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards (2 Cards Only: Foods & Offers) -->
    <div class="row g-4">
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="0">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_foods); ?></div>
                <div class="stat-label">إجمالي الأطباق</div>
            </div>
        </div>
        
        <div class="col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_offers); ?></div>
                <div class="stat-label">العروض الحالية</div>
            </div>
        </div>
    </div>
    
    <!-- Recent Foods -->
    <div class="table-container" data-aos="fade-up" data-aos-delay="400">
        <div class="table-title">
            <i class="fas fa-plus-circle"></i> آخر الأطباق المضافة
        </div>
        
        <div class="table-responsive">
            <?php if(count($recent_foods) > 0): ?>
            <table class="table-custom table">
                <thead>
                    <tr>
                        <th>الصورة</th>
                        <th>اسم الطبق</th>
                        <th>السعر</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_foods as $food): ?>
                    <tr>
                        <td>
                            <?php if(!empty($food['image']) && file_exists("../uploads/foods/" . $food['image'])): ?>
                            <img src="../uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                                 width="50" height="50" style="object-fit:cover; border-radius:10px;">
                            <?php else: ?>
                            <div style="width:50px; height:50px; background:#f0f0f0; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                                <i class="fas fa-image text-muted"></i>
                            </div>
                            <?php endif; ?>
                         </td>
                        <td><?php echo htmlspecialchars($food['name']); ?></td>
                        <td><?php echo number_format($food['price'], 0); ?> DA</td>
                        <td><?php echo date('Y-m-d', strtotime($food['created_at'] ?? 'now')); ?></td>
                        <td>
                            <a href="edit-food.php?id=<?php echo $food['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                         </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-utensils"></i>
                <p>لا توجد أطباق مضافة بعد</p>
                <a href="add-food.php" class="btn btn-primary mt-3">إضافة طبق جديد</a>
            </div>
            <?php endif; ?>
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
    
    // Auto-hide login success message after 5 seconds
    setTimeout(function() {
        const alertBox = document.getElementById('loginSuccessAlert');
        if(alertBox) {
            alertBox.style.transition = 'opacity 0.5s';
            alertBox.style.opacity = '0';
            setTimeout(() => {
                if(alertBox) alertBox.style.display = 'none';
            }, 500);
        }
    }, 5000);
    
    // Add smooth hover effects
    const cards = document.querySelectorAll('.stat-card'); // تم تعديل هذا السطر ليشمل البطاقات الجديدة فقط
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-15px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
</script>

</body>
</html>