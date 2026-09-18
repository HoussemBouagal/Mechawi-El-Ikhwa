<?php
require_once 'config/database.php';

// ==============================================
// جلب الإعدادات من قاعدة البيانات
// ==============================================
try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if(!$settings) {
        $settings = [
            'site_name' => 'مشاوي الإخوة',
            'whatsapp' => '213000000000',
            'facebook' => 'https://facebook.com/machaoui',
            'telegram' => 'https://t.me/machaoui',
            'instagram' => 'https://instagram.com/machaoui',
            'email' => 'info@machaoui.com',
            'address' => 'الجزائر العاصمة'
        ];
    }
} catch(PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
    $settings = [
        'site_name' => 'مشاوي الإخوة',
        'whatsapp' => '213000000000',
        'facebook' => '#',
        'telegram' => '#',
        'instagram' => '#',
        'email' => 'info@machaoui.com',
        'address' => 'الجزائر العاصمة'
    ];
}

// ==============================================
// معالجة أرقام التواصل - WhatsApp & Telegram
// ==============================================

// --- معالجة رقم الواتساب ---
$whatsapp_raw = $settings['whatsapp'] ?? '213000000000';
$whatsapp_number = preg_replace('/[^0-9]/', '', $whatsapp_raw);
if(empty($whatsapp_number)) {
    $whatsapp_number = '213000000000';
}
if(substr($whatsapp_number, 0, 1) == '0' && strlen($whatsapp_number) > 9) {
    $whatsapp_number = ltrim($whatsapp_number, '0');
}
$whatsapp_link = "https://wa.me/" . $whatsapp_number;

// --- معالجة التلجرام ---
$telegram_username = '';
$telegram_link = '#';
if(!empty($settings['telegram']) && $settings['telegram'] != '#') {
    $telegram_value = $settings['telegram'];
    if(strpos($telegram_value, 't.me/') !== false) {
        $telegram_username = basename($telegram_value);
    } else {
        $telegram_username = ltrim($telegram_value, '@');
    }
    if(!empty($telegram_username)) {
        $telegram_link = "https://t.me/" . $telegram_username;
    }
}

// --- معالجة فيسبوك ---
$facebook_link = (!empty($settings['facebook']) && $settings['facebook'] != '#') ? $settings['facebook'] : '#';

// --- معالجة انستغرام ---
$instagram_link = (!empty($settings['instagram']) && $settings['instagram'] != '#') ? $settings['instagram'] : '#';

// ==============================================
// جلب البيانات الأخرى
// ==============================================

// جلب آخر 6 أطباق
try {
    $foods = $pdo->query("
        SELECT * FROM foods 
        ORDER BY id DESC 
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Foods fetch error: " . $e->getMessage());
    $foods = [];
}

// مسار الشعار
$logo_path = 'img/logo.png';
$logo_exists = file_exists($logo_path);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?> | أفضل المشاوي والأطباق الجزائرية</title>
    <link rel="icon" href="img/logo.ico" type="image/x-icon">

    <meta name="description" content="مشاوي الإخوة - أشهى المشاوي والأطباق الجزائرية. جودة وطعم لا يُقاوم">
    <meta name="keywords" content="مشاوي, مطعم جزائري, أكل جزائري, مشاوي الإخوة">
    <meta name="author" content="<?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 RTL + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
    
    <style>
        /* ============================================== */
        /* CSS Variables & Reset */
        /* ============================================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            overflow-x: hidden;
        }
        
        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --secondary: #ff9800;
            --secondary-dark: #c66900;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --facebook: #1877f2;
            --instagram: #e4405f;
            --whatsapp: #25d366;
            --telegram: #0088cc;
        }
        
        /* ============================================== */
        /* Navbar Styles */
        /* ============================================== */
        .navbar-custom {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 10px 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 5px 0;
        }
        
        .navbar-logo {
            height: 50px;
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
        
        /* ============================================== */
        /* Hero Section */
        /* ============================================== */
        .hero-section {
            position: relative;
            min-height: 100vh;
            background: linear-gradient(135deg, rgba(0,0,0,0.7), rgba(0,0,0,0.5)), 
                        url('https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=1600') center/cover no-repeat;
            background-attachment: fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-top: 0;
            padding-top: 80px;
        }
        
        .hero-logo {
            margin-bottom: 30px;
        }
        
        .hero-logo-img {
            max-width: 180px;
            height: auto;
            filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));
            animation: fadeInDown 1s ease;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ============================================== */
        /* Food Cards */
        /* ============================================== */
        .food-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 20px;
            overflow: hidden;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .food-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        
        .food-card img {
            transition: transform 0.5s ease;
            height: 250px;
            object-fit: cover;
            width: 100%;
        }
        
        .food-card:hover img {
            transform: scale(1.08);
        }
        
        /* ============================================== */
        /* Button Styles */
        /* ============================================== */
        .btn-custom {
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            margin: 10px;
        }
        
        .btn-primary-custom {
            background: var(--secondary);
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: var(--secondary-dark);
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(255,152,0,0.3);
        }
        
        .btn-danger-custom {
            background: var(--primary);
            color: white;
        }
        
        .btn-danger-custom:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(198,40,40,0.3);
        }
        
        /* ============================================== */
        /* Section Title */
        /* ============================================== */
        .section-title {
            position: relative;
            display: inline-block;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 50px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            right: 50%;
            transform: translateX(50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }
        
        /* ============================================== */
        /* Price Tag */
        /* ============================================== */
        .price-tag {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            display: inline-block;
        }
        
        /* ============================================== */
        /* Footer Styles */
        /* ============================================== */
        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: relative;
        }
        
        .footer a {
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .footer a:not(.social-icon):hover {
            color: var(--secondary) !important;
            transform: translateX(-5px);
            display: inline-block;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .footer-logo-img {
            height: 45px;
            width: auto;
        }
        
        /* Social Media Icons */
        .social-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            margin: 0 5px;
            text-decoration: none;
            font-size: 1.2rem;
        }
        
        .social-icon.facebook { background: var(--facebook); color: white; }
        .social-icon.instagram { background: var(--instagram); color: white; }
        .social-icon.whatsapp { background: var(--whatsapp); color: white; }
        .social-icon.telegram { background: var(--telegram); color: white; }
        
        .social-icon:hover {
            transform: translateY(-5px);
            filter: brightness(0.9);
            color: white;
        }
        
        /* Contact Items */
        .contact-item {
            transition: all 0.3s ease;
            margin-bottom: 12px;
        }
        
        .contact-item:hover {
            transform: translateX(-5px);
        }
        
        .contact-item i {
            width: 30px;
            font-size: 1.2rem;
        }
        
        .contact-item .whatsapp-icon { color: var(--whatsapp); }
        .contact-item .facebook-icon { color: var(--facebook); }
        .contact-item .telegram-icon { color: var(--telegram); }
        .contact-item .instagram-icon { color: var(--instagram); }
        .contact-item .email-icon { color: #ea4335; }
        .contact-item .location-icon { color: #ff4444; }
        
        /* ============================================== */
        /* Scroll to Top Button */
        /* ============================================== */
        .scroll-top {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .scroll-top.show {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top:hover {
            background: var(--secondary);
            transform: translateY(-5px);
        }
        
        /* ============================================== */
        /* Loading Animation */
        /* ============================================== */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.5s ease;
        }
        
        .loading.hide {
            opacity: 0;
            visibility: hidden;
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* ============================================== */
        /* Responsive Design */
        /* ============================================== */
        @media (max-width: 768px) {
            .hero-section {
                min-height: 85vh;
                background-attachment: scroll;
                padding-top: 70px;
            }
            
            .hero-logo-img {
                max-width: 120px;
            }
            
            .hero-title {
                font-size: 2rem !important;
            }
            
            .hero-subtitle {
                font-size: 1rem !important;
            }
            
            .navbar-logo {
                height: 40px;
            }
            
            .navbar-brand-text {
                font-size: 1rem;
            }
            
            .food-card img {
                height: 200px;
            }
            
            .btn-custom {
                padding: 8px 25px;
                font-size: 0.9rem;
            }
        }
        
        @media (min-width: 768px) and (max-width: 1024px) {
            .hero-title {
                font-size: 3rem !important;
            }
            .hero-logo-img {
                max-width: 150px;
            }
        }
        
        @media (min-width: 1920px) {
            .hero-section {
                min-height: 90vh;
            }
            .hero-title {
                font-size: 4.5rem !important;
            }
            .hero-logo-img {
                max-width: 220px;
            }
        }
        
        .footer-divider {
            background: rgba(255,255,255,0.1);
        }
        
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>
<body>

<!-- ============================================== -->
<!-- Loading Screen -->
<!-- ============================================== -->
<div class="loading" id="loading">
    <div class="spinner"></div>
</div>

<!-- ============================================== -->
<!-- Navbar -->
<!-- ============================================== -->
<nav class="navbar navbar-expand-lg navbar-custom fixed-top">
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
                <li class="nav-item"><a class="nav-link" href="offers.php">العروض الخاصة</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- ============================================== -->
<!-- Hero Section -->
<!-- ============================================== -->
<section class="hero-section">
    <div class="container px-4" data-aos="fade-up" data-aos-duration="1000">
        <div class="hero-logo" data-aos="zoom-in" data-aos-duration="800">
            <?php if($logo_exists): ?>
            <img src="<?php echo $logo_path; ?>" alt="<?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?>" class="hero-logo-img">
            <?php endif; ?>
        </div>
        <h1 class="display-1 fw-bold text-white hero-title mb-4" style="text-shadow: 2px 2px 10px rgba(0,0,0,0.5);">
            🔥 <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?>
        </h1>
        <p class="lead text-white mb-5 hero-subtitle" style="font-size: 1.5rem; text-shadow: 1px 1px 5px rgba(0,0,0,0.5);">
            أفضل المشاوي والأطباق الجزائرية بجودة وطعم لا يُقاوم
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="menu.php" class="btn btn-primary-custom btn-custom btn-lg">
                <i class="fas fa-utensils me-2"></i>تصفح القائمة
            </a>
            <a href="offers.php" class="btn btn-danger-custom btn-custom btn-lg">
                <i class="fas fa-tag me-2"></i>العروض
            </a>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- Popular Dishes Section -->
<!-- ============================================== -->
<section class="py-5" style="margin-top: 0;">
    <div class="container py-4">
        <div class="text-center" data-aos="fade-up">
            <h2 class="section-title display-5 fw-bold">أشهر الأطباق</h2>
        </div>
        
        <div class="row g-4 mt-2">
            <?php if(count($foods) > 0): ?>
                <?php foreach($foods as $index => $food): ?>
                <div class="col-12 col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?php echo $index * 100; ?>">
                    <div class="food-card">
                        <div class="position-relative overflow-hidden">
                            <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($food['name']); ?>"
                                 loading="lazy"
                                 onerror="this.src='assets/images/placeholder.jpg'">
                        </div>
                        <div class="card-body p-4">
                            <h3 class="h5 fw-bold mb-3" style="color: var(--dark);">
                                <?php echo htmlspecialchars($food['name']); ?>
                            </h3>
                            <p class="text-muted mb-3" style="line-height: 1.6;">
                                <?php echo htmlspecialchars(substr($food['description'], 0, 80)); ?>...
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="price-tag">
                                    <?php echo number_format($food['price'], 0); ?> DA
                                </span>
                                <div>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star text-warning"></i>
                                    <i class="fas fa-star-half-alt text-warning"></i>
                                </div>
                            </div>
                            <a href="food-details.php?id=<?php echo $food['id']; ?>" 
                               class="btn btn-dark w-100 rounded-pill py-2 fw-bold"
                               style="transition: all 0.3s ease;">
                                <i class="fas fa-eye me-2"></i>عرض التفاصيل
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                    <p class="lead text-muted">لا توجد أطباق حالياً</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- Stats Section -->
<!-- ============================================== -->
<section class="py-5 bg-white" data-aos="fade-up">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <i class="fas fa-utensils fa-3x text-danger mb-3"></i>
                    <p class="text-muted">أطباق متنوعة</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <i class="fas fa-users fa-3x text-warning mb-3"></i>
                    <p class="text-muted">زبائن سعداء</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <i class="fas fa-truck fa-3x text-success mb-3"></i>
                    <p class="text-muted">توصيل سريع</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <i class="fas fa-star fa-3x text-info mb-3"></i>
                    <p class="text-muted">تقييم عالي</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- Footer -->
<!-- ============================================== -->
<footer class="footer pt-5 pb-4 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- About Section -->
            <div class="col-12 col-md-4" data-aos="fade-up">
                <div class="footer-logo">
                    <?php if($logo_exists): ?>
                    <img src="<?php echo $logo_path; ?>" alt="Logo" class="footer-logo-img">
                    <?php endif; ?>
                    <h4 class="fw-bold mb-0">
                        <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?>
                    </h4>
                </div>
                <p class="text-white-50 mt-2" style="line-height: 1.8;">
                    أفضل المشاوي والأطباق الجزائرية بجودة عالية وطعم لا يُقاوم. 
                    نقدم لكم أشهى المأكولات الجزائرية الأصيلة.
                </p>
            </div>
            
            <!-- Quick Links -->
            <div class="col-6 col-md-4" data-aos="fade-up" data-aos-delay="100">
                <h5 class="fw-bold mb-3">روابط سريعة</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none"><i class="fas fa-chevron-left me-2"></i>الرئيسية</a></li>
                    <li class="mb-2"><a href="menu.php" class="text-white-50 text-decoration-none"><i class="fas fa-chevron-left me-2"></i>القائمة</a></li>
                    <li class="mb-2"><a href="offers.php" class="text-white-50 text-decoration-none"><i class="fas fa-chevron-left me-2"></i>العروض</a></li>
                </ul>
            </div>
            
            <!-- Contact Information -->
            <div class="col-6 col-md-4" data-aos="fade-up" data-aos-delay="200">
                <h5 class="fw-bold mb-3">تواصل معنا</h5>
                <div class="text-white-50">
                    <!-- WhatsApp -->
                    <?php if($whatsapp_link && $whatsapp_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-whatsapp whatsapp-icon me-2"></i>
                        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="text-white-50 text-decoration-none">
                            واتساب
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Telegram -->
                    <?php if($telegram_link && $telegram_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-telegram telegram-icon me-2"></i>
                        <a href="<?php echo htmlspecialchars($telegram_link); ?>" target="_blank" class="text-white-50 text-decoration-none">
                            تلجرام
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Facebook -->
                    <?php if($facebook_link && $facebook_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-facebook facebook-icon me-2"></i>
                        <a href="<?php echo htmlspecialchars($facebook_link); ?>" target="_blank" class="text-white-50 text-decoration-none">
                            فيسبوك
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Instagram -->
                    <?php if($instagram_link && $instagram_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-instagram instagram-icon me-2"></i>
                        <a href="<?php echo htmlspecialchars($instagram_link); ?>" target="_blank" class="text-white-50 text-decoration-none">
                            انستغرام
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Email -->
                    <?php if(!empty($settings['email'])): ?>
                    <p class="mb-2 contact-item">
                        <i class="fas fa-envelope email-icon me-2"></i>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>" class="text-white-50 text-decoration-none">
                            البريد الإلكتروني
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Address -->
                    <?php if(!empty($settings['address'])): ?>
                    <p class="mb-2 contact-item">
                        <i class="fas fa-map-marker-alt location-icon me-2"></i>
                        <span class="text-white-50"><?php echo htmlspecialchars($settings['address']); ?></span>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <hr class="my-4 footer-divider">
        
        <div class="text-center pb-3">
            <p class="text-white-50 mb-0">
                © <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?> - جميع الحقوق محفوظة
            </p>
        </div>
    </div>
</footer>

<!-- ============================================== -->
<!-- Scroll to Top Button -->
<!-- ============================================== -->
<div class="scroll-top" id="scrollTop">
    <i class="fas fa-arrow-up"></i>
</div>

<!-- ============================================== -->
<!-- Scripts -->
<!-- ============================================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 100
    });
    
    // Loading Screen
    window.addEventListener('load', function() {
        const loading = document.getElementById('loading');
        setTimeout(function() {
            loading.classList.add('hide');
        }, 500);
    });
    
    // Scroll to Top Button
    const scrollTop = document.getElementById('scrollTop');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollTop.classList.add('show');
        } else {
            scrollTop.classList.remove('show');
        }
    });
    
    scrollTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Lazy Loading Images
    const images = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.src;
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(function(img) {
            imageObserver.observe(img);
        });
    }
    
    // Add smooth hover effects for food cards
    const cards = document.querySelectorAll('.food-card');
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