<?php
require_once 'config/database.php';

// جلب الإعدادات من قاعدة البيانات (بما فيها سعر التوصيل)
try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if(!$settings) {
        $settings = [
            'site_name' => 'مشاوي الإخوة',
            'whatsapp' => '213000000000',
            'telegram' => '', // Added telegram field
            'facebook' => '#',
            'instagram' => '#',
            'email' => 'info@mashawi.dz',
            'address' => 'الجزائر العاصمة، الجزائر',
            'delivery_fee' => 500,
            'opening_time' => '09:00',
            'closing_time' => '23:00'
        ];
    }
} catch(PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $settings = [
        'site_name' => 'مشاوي الإخوة',
        'whatsapp' => '213000000000',
        'telegram' => '', // Added telegram field
        'facebook' => '#',
        'instagram' => '#',
        'email' => 'info@mashawi.dz',
        'address' => 'الجزائر العاصمة، الجزائر',
        'delivery_fee' => 500,
        'opening_time' => '09:00',
        'closing_time' => '23:00'
    ];
}

// ========== معالجة رقم الواتساب ==========
// استخراج رقم الواتساب من الإعدادات وإزالة أي أحرف غير رقمية
$whatsapp_raw = $settings['whatsapp'] ?? '213000000000';
$whatsapp_number = preg_replace('/[^0-9]/', '', $whatsapp_raw); // استخراج الأرقام فقط

// إذا كان الرقم فارغاً، استخدم الرقم الافتراضي
if(empty($whatsapp_number)) {
    $whatsapp_number = '213000000000';
}

// إزالة الصفر البادئ إذا كان الرقم يبدأ بـ 0 (مثل 0555123456 -> 555123456)
// مع ترك رمز البلد
if(substr($whatsapp_number, 0, 1) == '0' && strlen($whatsapp_number) > 9) {
    $whatsapp_number = ltrim($whatsapp_number, '0');
}

// إنشاء رابط الواتساب الصحيح
$whatsapp_link = "https://wa.me/" . $whatsapp_number;

// ========== معالجة رقم التليجرام ==========
$telegram_raw = $settings['telegram'] ?? '';
$telegram_number = preg_replace('/[^0-9]/', '', $telegram_raw); // استخراج الأرقام فقط

// إذا كان الرقم فارغاً، استخدم رابط فارغ أو لا تعرضه
if(empty($telegram_number)) {
    $telegram_number = ''; // أو يمكنك تعيين قيمة افتراضية
    $telegram_link = '#';
} else {
    // إزالة الصفر البادئ إذا كان الرقم يبدأ بـ 0
    if(substr($telegram_number, 0, 1) == '0' && strlen($telegram_number) > 9) {
        $telegram_number = ltrim($telegram_number, '0');
    }
    $telegram_link = "https://t.me/" . $telegram_number;
}
// ========================================

// مسار الشعار
$logo_path = 'img/logo.png';
$logo_exists = file_exists($logo_path);

// التحقق من وجود ID صالح
if(!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: menu.php");
    exit();
}

$id = intval($_GET['id']); // تأمين المدخلات

// جلب بيانات الطعام
$stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
$stmt->execute([$id]);
$food = $stmt->fetch();

// التحقق من وجود الطعام
if(!$food) {
    header("Location: menu.php");
    exit();
}

// التحقق من وجود الصورة
$image_path = 'uploads/foods/' . $food['image'];
if(!file_exists($image_path) || empty($food['image'])) {
    $food['image'] = 'placeholder-food.jpg';
}

// جلب أطباق مقترحة
$stmt_suggested = $pdo->prepare("
    SELECT * FROM foods 
    WHERE id != ? 
    ORDER BY RAND() 
    LIMIT 3
");
$stmt_suggested->execute([$id]);
$suggested_foods = $stmt_suggested->fetchAll();

// زيادة عدد المشاهدات (مع التحقق من وجود العمود)
try {
    // التحقق مما إذا كان عمود views موجود
    $check_column = $pdo->query("SHOW COLUMNS FROM foods LIKE 'views'");
    if($check_column->rowCount() > 0) {
        $pdo->prepare("UPDATE foods SET views = views + 1 WHERE id = ?")->execute([$id]);
    }
} catch(PDOException $e) {
    // تجاهل الخطأ إذا لم يكن حقل views موجوداً
    error_log("Views update error: " . $e->getMessage());
}

// سعر التوصيل من قاعدة البيانات
$delivery_fee = $settings['delivery_fee'] ?? 500;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($food['name']); ?> | <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($food['description'], 0, 150)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($food['name']); ?>, مشاوي, مطعم جزائري">
    
    <!-- Open Graph for Social Media -->
    <meta property="og:title" content="<?php echo htmlspecialchars($food['name']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($food['description'], 0, 150)); ?>">
    <meta property="og:image" content="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>">
    <meta property="og:type" content="product">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <link rel="icon" href="img/logo.ico" type="image/x-icon">

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
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        
        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --secondary: #ff9800;
            --secondary-dark: #c66900;
            --dark: #1a1a2e;
            --success: #25D366;
            --whatsapp: #25D366;
            --facebook: #1877F2;
            --instagram: #E4405F;
            --telegram: #0088cc;
            --email: #EA4335;
        }
        
        /* Navbar with Logo */
        .navbar-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 0;
        }
        
        .navbar-logo {
            height: 45px;
            width: auto;
            transition: all 0.3s ease;
        }
        
        .navbar-brand-text {
            font-size: 1.3rem;
            font-weight: 800;
            color: white !important;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: all 0.3s ease;
            font-weight: 500;
            margin: 0 5px;
        }
        
        .nav-link:hover {
            color: var(--secondary) !important;
            transform: translateX(-3px);
        }
        
        /* Breadcrumb */
        .breadcrumb-custom {
            background: transparent;
            padding: 20px 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-custom a {
            color: var(--primary);
            text-decoration: none;
        }
        
        /* Food Image Section */
        .food-image-main {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        
        .food-image-main img {
            width: 100%;
            height: auto;
            transition: transform 0.5s ease;
        }
        
        .food-image-main:hover img {
            transform: scale(1.05);
        }
        
        .image-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            z-index: 1;
        }
        
        /* Food Info Section */
        .food-info-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        
        .food-title {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 15px;
        }
        
        .food-rating {
            margin-bottom: 20px;
        }
        
        .stars {
            color: #ffc107;
            margin-left: 10px;
        }
        
        .review-count {
            color: #6c757d;
        }
        
        .food-description {
            color: #6c757d;
            line-height: 1.8;
            margin-bottom: 20px;
            font-size: 1rem;
        }
        
        /* Price Section */
        .price-section {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .food-price {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .food-price small {
            font-size: 1rem;
            font-weight: 400;
            color: #6c757d;
        }
        
        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }
        
        .quantity-btn {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: none;
            background: var(--secondary);
            color: white;
            font-size: 1.3rem;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .quantity-btn:hover {
            background: var(--secondary-dark);
            transform: scale(1.05);
        }
        
        .quantity-input {
            width: 80px;
            height: 45px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        /* Order Button */
        .btn-order {
            background: var(--success);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
            color: white;
        }
        
        /* Info Items */
        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-icon {
            width: 45px;
            height: 45px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
        }
        
        /* Suggested Foods */
        .suggested-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
        }
        
        .suggested-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .suggested-img {
            height: 200px;
            overflow: hidden;
        }
        
        .suggested-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .suggested-card:hover .suggested-img img {
            transform: scale(1.1);
        }
        
        .suggested-body {
            padding: 15px;
        }
        
        .suggested-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .suggested-price {
            color: var(--primary);
            font-weight: 700;
        }
        
        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            margin-top: 50px;
            padding: 40px 0 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .footer a:hover {
            color: var(--secondary);
        }
        
        /* Contact Section Styles */
        .contact-item {
            transition: all 0.3s ease;
            padding: 8px 0;
            display: flex;
            align-items: center;
        }
        
        .contact-item:hover {
            transform: translateX(-5px);
        }
        
        .contact-icon {
            width: 32px;
            font-size: 1.2rem;
            text-align: center;
            margin-left: 10px;
        }
        
        .contact-icon.whatsapp { color: #25D366; }
        .contact-icon.facebook { color: #1877F2; }
        .contact-icon.instagram { color: #E4405F; }
        .contact-icon.telegram { color: #0088cc; }
        .contact-icon.email { color: #EA4335; }
        .contact-icon.phone { color: #34B7F1; }
        .contact-icon.location { color: #ff4444; }
        
        .contact-item:hover .contact-icon {
            transform: scale(1.2);
        }
        
        .contact-item:hover a {
            color: white !important;
        }
        
        /* Modal for image viewer */
        .image-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            cursor: pointer;
        }
        
        .image-modal-content {
            margin: auto;
            display: block;
            width: 90%;
            max-width: 1200px;
            max-height: 90vh;
            object-fit: contain;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
            z-index: 10000;
        }
        
        .close-modal:hover {
            color: #bbb;
        }
        
        /* Footer Divider */
        .footer-divider {
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
            height: 1px;
            border: none;
            margin: 20px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .food-title {
                font-size: 1.5rem;
            }
            
            .food-price {
                font-size: 1.8rem;
            }
            
            .food-info-card {
                margin-top: 30px;
            }
            
            .navbar-logo {
                height: 35px;
            }
            
            .navbar-brand-text {
                font-size: 1rem;
            }
            
            .contact-item {
                font-size: 0.9rem;
            }
            
            .contact-icon {
                width: 28px;
                font-size: 1rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .food-info-card {
            animation: fadeIn 0.6s ease;
        }
        
        /* Navbar Toggler */
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>

<!-- Navbar with Logo -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <?php if($logo_exists): ?>
            <img src="<?php echo $logo_path; ?>" alt="Logo" class="navbar-logo">
            <?php endif; ?>
            <span class="navbar-brand-text">
                <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?>
            </span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="menu.php">القائمة</a></li>
                <li class="nav-item"><a class="nav-link" href="offers.php">العروض</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="close-modal">&times;</span>
    <img class="image-modal-content" id="modalImage">
</div>

<div class="container py-4">
    
    <!-- Breadcrumb -->
    <nav class="breadcrumb-custom" data-aos="fade-down">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i> الرئيسية</a></li>
            <li class="breadcrumb-item"><a href="menu.php">القائمة</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($food['name']); ?></li>
        </ol>
    </nav>
    
    <!-- Food Details Section -->
    <div class="row g-4">
        <!-- Image Column -->
        <div class="col-lg-6" data-aos="fade-left">
            <div class="food-image-main" id="mainImage">
                <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                     alt="<?php echo htmlspecialchars($food['name']); ?>"
                     class="img-fluid"
                     id="foodImage"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\' viewBox=\'0 0 400 300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\%\' y=\'50\%\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-family=\'Cairo\' font-size=\'20\'%3Eصورة غير متوفرة%3C/text%3E%3C/svg%3E'">
            </div>
        </div>
        
        <!-- Info Column -->
        <div class="col-lg-6" data-aos="fade-right">
            <div class="food-info-card">
                <h1 class="food-title"><?php echo htmlspecialchars($food['name']); ?></h1>
                
                <!-- Rating -->
                <div class="food-rating">
                    <span class="stars">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </span>
                </div>
                
                <!-- Description -->
                <p class="food-description">
                    <?php echo nl2br(htmlspecialchars($food['description'])); ?>
                </p>
                
                <!-- Price Section -->
                <div class="price-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="food-price">
                            <?php echo number_format($food['price'], 0); ?> <small>DA</small>
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-truck"></i> التوصيل <?php echo number_format($delivery_fee, 0); ?> DA
                        </span>
                    </div>
                </div>
                
                <!-- Order Form with WhatsApp -->
                <div>
                    <!-- Quantity Selector -->
                    <div class="quantity-selector">
                        <span class="fw-bold"><i class="fas fa-hashtag"></i> الكمية:</span>
                        <button type="button" class="quantity-btn" id="decreaseBtn">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" 
                               id="quantity" 
                               class="quantity-input" 
                               value="1" 
                               min="1" 
                               max="99"
                               readonly>
                        <button type="button" class="quantity-btn" id="increaseBtn">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                                   
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div>
                            <strong>رسوم التوصيل:</strong> <?php echo number_format($delivery_fee, 0); ?> DA
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div>
                            <strong>المطبخ:</strong> جزائري، مشاوي
                        </div>
                    </div>
                    
                    <!-- Order Button (Only WhatsApp) -->
                    <button type="button" class="btn-order" id="orderBtn">
                        <i class="fab fa-whatsapp fa-lg me-2"></i>
                        اطلب الآن عبر واتساب
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Suggested Foods Section -->
    <?php if(count($suggested_foods) > 0): ?>
    <div class="mt-5 pt-4">
        <h2 class="text-center mb-4" data-aos="fade-up">
            <i class="fas fa-star text-warning"></i>
            أطباق قد تعجبك أيضاً
            <i class="fas fa-star text-warning"></i>
        </h2>
        
        <div class="row g-4">
            <?php foreach($suggested_foods as $index => $suggested): ?>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                <div class="suggested-card">
                    <div class="suggested-img">
                        <img src="uploads/foods/<?php echo htmlspecialchars($suggested['image']); ?>" 
                             alt="<?php echo htmlspecialchars($suggested['name']); ?>"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\' viewBox=\'0 0 400 300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\%\' y=\'50\%\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-family=\'Cairo\' font-size=\'20\'%3Eصورة غير متوفرة%3C/text%3E%3C/svg%3E'">
                    </div>
                    <div class="suggested-body">
                        <h5 class="suggested-title"><?php echo htmlspecialchars($suggested['name']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars(substr($suggested['description'], 0, 60)); ?>...</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="suggested-price"><?php echo number_format($suggested['price'], 0); ?> DA</span>
                            <a href="food-details.php?id=<?php echo $suggested['id']; ?>" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-eye"></i> عرض
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3"><?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?></h5>
                <p class="text-white-50">أفضل المشاوي والأطباق الجزائرية بجودة عالية وطعم لا يُقاوم.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">روابط سريعة</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white-50">الرئيسية</a></li>
                    <li><a href="menu.php" class="text-white-50">القائمة</a></li>
                    <li><a href="offers.php" class="text-white-50">العروض</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">تواصل معنا</h5>
                <div class="text-white-50">
                    <!-- WhatsApp - إخفاء الرقم -->
                    <div class="contact-item">
                        <i class="fab fa-whatsapp contact-icon whatsapp"></i>
                        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="text-white-50">
                            واتساب
                        </a>
                    </div>
                    
                    <!-- Telegram - إخفاء الرقم -->
                    <?php if(!empty($telegram_number)): ?>
                    <div class="contact-item">
                        <i class="fab fa-telegram contact-icon telegram"></i>
                        <a href="<?php echo $telegram_link; ?>" target="_blank" class="text-white-50">
                            تلجرام
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Facebook -->
                    <?php if(!empty($settings['facebook']) && $settings['facebook'] != '#'): ?>
                    <div class="contact-item">
                        <i class="fab fa-facebook contact-icon facebook"></i>
                        <a href="<?php echo htmlspecialchars($settings['facebook']); ?>" target="_blank" class="text-white-50">
                            فيسبوك
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email -->
                    <?php if(!empty($settings['email'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope contact-icon email"></i>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>" class="text-white-50">
                            البريد الإلكتروني
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Address -->
                    <?php if(!empty($settings['address'])): ?>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt contact-icon location"></i>
                        <span class="text-white-50"><?php echo htmlspecialchars($settings['address']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <hr class="footer-divider">
        
        <div class="text-center">
            <p class="text-white-50 mb-0">© <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?> - جميع الحقوق محفوظة</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
// Wait for DOM to fully load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS with error handling
    if (typeof AOS !== 'undefined' && AOS) {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // ==================== IMAGE MODAL ====================
    const mainImageDiv = document.getElementById('mainImage');
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeModal = document.querySelector('.close-modal');
    
    if (mainImageDiv && modal && modalImg) {
        mainImageDiv.addEventListener('click', function() {
            const img = document.getElementById('foodImage');
            if (img && img.src) {
                modal.style.display = 'block';
                modalImg.src = img.src;
                document.body.style.overflow = 'hidden';
            }
        });
        
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
        
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // ==================== QUANTITY SELECTOR ====================
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseBtn');
    const increaseBtn = document.getElementById('increaseBtn');
    const orderBtn = document.getElementById('orderBtn');
    
    // Food data from PHP
    const foodName = "<?php echo addslashes($food['name']); ?>";
    const foodPrice = <?php echo (float)$food['price']; ?>;
    const deliveryFee = <?php echo (float)$delivery_fee; ?>;
    const whatsappNumber = "<?php echo $whatsapp_number; ?>";
    
    // Decrease quantity
    if (decreaseBtn && quantityInput) {
        decreaseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            let currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });
    }
    
    // Increase quantity
    if (increaseBtn && quantityInput) {
        increaseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            let currentValue = parseInt(quantityInput.value);
            if (currentValue < 99) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
    
    // Order button handler - إرسال الطلب عبر واتساب
    if (orderBtn && quantityInput) {
        orderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const quantity = parseInt(quantityInput.value) || 1;
            const subtotal = foodPrice * quantity;
            const totalPrice = subtotal + deliveryFee;
            
            const message = `مرحباً، أريد طلب:\n\n` +
                `🍽️ الطبق: ${foodName}\n` +
                `📦 الكمية: ${quantity}\n` +
                `💰 سعر الطبق: ${foodPrice.toFixed(0)} DA × ${quantity} = ${subtotal.toFixed(0)} DA\n` +
                `🚚 رسوم التوصيل: ${deliveryFee.toFixed(0)} DA\n` +
                `💵 الإجمالي: ${totalPrice.toFixed(0)} DA\n\n` +
                `الرجاء تأكيد الطلب. شكراً!`;
            
            const encodedMessage = encodeURIComponent(message);
            const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
            window.open(whatsappUrl, '_blank');
        });
    }
    
    // ==================== SMOOTH SCROLLING ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== "#") {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // ==================== ADD HOVER EFFECT TO SUGGESTED CARDS ====================
    const suggestedCards = document.querySelectorAll('.suggested-card');
    suggestedCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // ==================== PREVENT FORM SUBMIT ON ENTER ====================
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
});
</script>

</body>
</html>