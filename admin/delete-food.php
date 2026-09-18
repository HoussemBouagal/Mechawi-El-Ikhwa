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

$error = '';
$warning = '';

// التحقق من وجود ID صالح
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: foods.php?error=no_id");
    exit();
}

$id = intval($_GET['id']); // تأمين المدخلات

// جلب بيانات الطعام قبل الحذف (للحصول على اسم الصورة)
try {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch();
    
    // التحقق من وجود الطعام
    if(!$food) {
        header("Location: foods.php?error=not_found");
        exit();
    }
} catch(PDOException $e) {
    error_log("Fetch food error: " . $e->getMessage());
    header("Location: foods.php?error=not_found");
    exit();
}

// التحقق من وجود طلبات مرتبطة بهذا الطعام
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE food_id = ?");
    $stmt->execute([$id]);
    $order_count = $stmt->fetchColumn();
} catch(PDOException $e) {
    error_log("Check orders error: " . $e->getMessage());
    $order_count = 0;
}

// معالجة الحذف المؤكد
$confirmed = isset($_GET['confirmed']) && $_GET['confirmed'] == 'yes';
$force_delete = isset($_GET['force']) && $_GET['force'] == 'yes';

if($confirmed && !$force_delete) {
    // حذف عادي (بدون طلبات مرتبطة)
    try {
        // حذف الصورة من السيرفر
        $image_path = "../uploads/foods/" . $food['image'];
        if(!empty($food['image']) && file_exists($image_path)) {
            unlink($image_path); // حذف الصورة
        }
        
        // حذف الطعام من قاعدة البيانات
        $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
        
        if($stmt->execute([$id])) {
            header("Location: foods.php?success=deleted");
            exit();
        } else {
            $error = "حدث خطأ أثناء حذف الطبق";
        }
    } catch(PDOException $e) {
        error_log("Delete food error: " . $e->getMessage());
        $error = "حدث خطأ في قاعدة البيانات";
    }
} elseif($force_delete) {
    // حذف قسري (مع حذف الطلبات المرتبطة)
    try {
        // حذف الصورة من السيرفر
        $image_path = "../uploads/foods/" . $food['image'];
        if(!empty($food['image']) && file_exists($image_path)) {
            unlink($image_path);
        }
        
        // بدء المعاملة
        $pdo->beginTransaction();
        
        // حذف العناصر المرتبطة في order_items
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE food_id = ?");
        $stmt->execute([$id]);
        
        // حذف الطعام من قاعدة البيانات
        $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
        $stmt->execute([$id]);
        
        // تأكيد المعاملة
        $pdo->commit();
        
        header("Location: foods.php?success=deleted");
        exit();
    } catch(PDOException $e) {
        $pdo->rollBack();
        error_log("Force delete food error: " . $e->getMessage());
        $error = "حدث خطأ أثناء حذف الطبق والطلبات المرتبطة";
    }
}

// اسم المسؤول
$admin_name = $_SESSION['admin_name'] ?? 'مسؤول';
$has_orders = ($order_count > 0);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حذف طبق | مشاوي الإخوة</title>
    
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
            --danger: #dc3545;
            --warning: #ffc107;
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
            display: flex;
            align-items: center;
            justify-content: center;
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
        
        /* Confirmation Card */
        .confirmation-card {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .warning-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #fee, #fdd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .warning-icon i {
            font-size: 3.5rem;
            color: var(--danger);
        }
        
        .food-preview {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin: 25px 0;
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .food-preview-img {
            width: 100px;
            height: 100px;
            border-radius: 15px;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .food-preview-info h4 {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .food-preview-info .price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .alert-orders {
            background: #fff3cd;
            border-right: 4px solid var(--warning);
            padding: 15px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        /* Buttons */
        .btn-danger-custom {
            background: linear-gradient(135deg, var(--danger), #c82333);
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
            color: white;
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
            color: white;
        }
        
        .btn-warning-custom {
            background: var(--warning);
            color: var(--dark);
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-warning-custom:hover {
            background: #e0a800;
            color: var(--dark);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 25px 0;
        }
        
        /* Alert */
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
        
        /* Page Content Wrapper */
        .page-wrapper {
            width: 100%;
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
            .confirmation-card {
                padding: 30px 25px;
            }
            .food-preview {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media print {
            .sidebar, .menu-toggle, .top-navbar {
                display: none !important;
            }
            .main-content {
                margin-right: 0;
                padding: 0;
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
        <li><a href="foods.php" class="active"><i class="fas fa-utensils"></i> الأطباق</a></li>
        <li><a href="offers.php"><i class="fas fa-tags"></i> العروض</a></li>
        <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> الطلبات</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> المستخدمين</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
        <li><a href="../logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="page-wrapper">
        
        <!-- Top Navbar -->
        <div class="top-navbar">
            <div class="welcome-text">
                <h2>مرحباً، <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
                <p class="text-muted mb-0">تأكيد حذف الطبق</p>
            </div>
            <div class="admin-avatar">
                <i class="fas fa-user"></i>
            </div>
        </div>
        
        <div class="confirmation-card">
            
            <!-- Error Alert -->
            <?php if($error): ?>
            <div class="alert-custom alert-danger-custom">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <div class="mt-2">
                    <a href="foods.php" class="btn btn-sm btn-secondary">العودة إلى الأطباق</a>
                </div>
            </div>
            <?php else: ?>
            
            <div class="warning-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h2 class="text-center fw-bold mb-3">تأكيد حذف الطبق</h2>
            
            <?php if($has_orders): ?>
                <!-- حالة وجود طلبات مرتبطة -->
                <div class="alert-orders">
                    <i class="fas fa-shopping-cart text-warning me-2"></i>
                    <strong>تحذير!</strong> هذا الطبق مرتبط بـ <?php echo $order_count; ?> طلب/طلبات.
                    <br>
                    <small>حذف هذا الطبق سيؤثر على تقارير الطلبات السابقة.</small>
                </div>
            <?php endif; ?>
            
            <!-- معلومات الطعام -->
            <div class="food-preview">
                <?php 
                $image_path = "../uploads/foods/" . htmlspecialchars($food['image']);
                if(!empty($food['image']) && file_exists($image_path)):
                ?>
                <img src="<?php echo $image_path; ?>" 
                     alt="<?php echo htmlspecialchars($food['name']); ?>"
                     class="food-preview-img">
                <?php else: ?>
                <div class="food-preview-img bg-secondary d-flex align-items-center justify-content-center text-white">
                    <i class="fas fa-image fa-2x"></i>
                </div>
                <?php endif; ?>
                <div class="food-preview-info">
                    <h4><?php echo htmlspecialchars($food['name']); ?></h4>
                    <p class="text-muted small"><?php echo htmlspecialchars(mb_substr($food['description'] ?? '', 0, 80)); ?>...</p>
                    <div class="price"><?php echo number_format($food['price'], 0); ?> DA</div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <p class="text-center text-muted mb-4">
                هل أنت متأكد من حذف هذا الطبق نهائياً؟
                <?php if($has_orders): ?>
                <br>
                <strong class="text-danger">ملاحظة: هذا الطبق مرتبط بطلبات سابقة.</strong>
                <?php endif; ?>
            </p>
            
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <?php if($has_orders): ?>
                    <!-- خيارات الحذف مع الطلبات المرتبطة -->
                    <a href="?id=<?php echo $id; ?>&force=yes" class="btn-danger-custom" 
                       onclick="return confirm('تحذير: هذا الطبق مرتبط بطلبات سابقة. هل أنت متأكد من الحذف؟\n\nسيتم حذف جميع الطلبات المرتبطة بهذا الطبق.')">
                        <i class="fas fa-trash-alt me-2"></i>
                        حذف نهائي (مع الطلبات)
                    </a>
                    <a href="foods.php" class="btn-secondary-custom">
                        <i class="fas fa-times me-2"></i>
                        إلغاء
                    </a>
                <?php else: ?>
                    <!-- حذف عادي -->
                    <a href="?id=<?php echo $id; ?>&confirmed=yes" class="btn-danger-custom" id="confirmDelete">
                        <i class="fas fa-trash-alt me-2"></i>
                        نعم، احذف الطبق
                    </a>
                    <a href="foods.php" class="btn-secondary-custom">
                        <i class="fas fa-arrow-right me-2"></i>
                        لا، إلغاء
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- نصيحة -->
            <div class="mt-4 text-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    ملاحظة: حذف الطبق سيؤدي أيضاً إلى حذف صورته من السيرفر.
                </small>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
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
    
    // تأكيد الحذف مع تحسين تجربة المستخدم
    const confirmBtn = document.getElementById('confirmDelete');
    
    if(confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            const confirmed = confirm('⚠️ تحذير: هل أنت متأكد من حذف هذا الطبق نهائياً؟\n\nلا يمكنك التراجع عن هذا الإجراء.');
            if(!confirmed) {
                e.preventDefault();
            }
        });
    }
    
    // إضافة تأثير بصري عند تحميل الصفحة
    const card = document.querySelector('.confirmation-card');
    if(card) {
        card.style.opacity = '0';
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
        }, 100);
    }
</script>

</body>
</html>