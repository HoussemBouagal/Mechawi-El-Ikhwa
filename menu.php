<?php
require_once 'config/database.php';

// جلب الإعدادات من قاعدة البيانات
try {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    $settings = $stmt->fetch();
    
    if(!$settings) {
        $settings = [
            'site_name' => 'مشاوي الإخوة',
            'whatsapp' => '213000000000',
            'facebook' => '#',
            'telegram' => '#',
            'instagram' => '#',
            'email' => 'info@mashawi.com',
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
        'facebook' => '#',
        'telegram' => '#',
        'instagram' => '#',
        'email' => 'info@mashawi.com',
        'address' => 'الجزائر العاصمة، الجزائر',
        'delivery_fee' => 500,
        'opening_time' => '09:00',
        'closing_time' => '23:00'
    ];
}

// ========== معالجة أرقام التواصل ==========

// معالجة رقم الواتساب
$whatsapp_raw = $settings['whatsapp'] ?? '213000000000';
$whatsapp_number = preg_replace('/[^0-9]/', '', $whatsapp_raw);
if(empty($whatsapp_number)) {
    $whatsapp_number = '213000000000';
}
if(substr($whatsapp_number, 0, 1) == '0' && strlen($whatsapp_number) > 9) {
    $whatsapp_number = ltrim($whatsapp_number, '0');
}
$whatsapp_link = "https://wa.me/" . $whatsapp_number;

// معالجة التلجرام
$telegram_username = '';
$telegram_link = '#';
if(!empty($settings['telegram']) && $settings['telegram'] != '#') {
    $telegram_value = $settings['telegram'];
    if(strpos($telegram_value, 't.me/') !== false) {
        $telegram_username = basename($telegram_value);
    } else {
        $telegram_username = ltrim($telegram_value, '@');
    }
    $telegram_link = "https://t.me/" . $telegram_username;
}

// معالجة فيسبوك
$facebook_link = !empty($settings['facebook']) && $settings['facebook'] != '#' ? $settings['facebook'] : '#';

// معالجة انستغرام
$instagram_link = !empty($settings['instagram']) && $settings['instagram'] != '#' ? $settings['instagram'] : '#';

// ========================================

// مسار الشعار
$logo_path = 'img/logo.png';
$logo_exists = file_exists($logo_path);

// جلب جميع الأطباق مع إمكانية البحث فقط
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// بناء استعلام SQL ديناميكي
$sql = "SELECT * FROM foods WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>قائمة الطعام | <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?></title>
    <meta name="description" content="قائمة الطعام في مطعم مشاوي الإخوة - أشهى المشاوي والأطباق الجزائرية">
    <link rel="icon" href="img/logo.ico" type="image/x-icon">

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
            background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
        }
        
        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --secondary: #ff9800;
            --secondary-dark: #c66900;
            --dark: #1a1a2e;
            --whatsapp: #25D366;
            --facebook: #1877F2;
            --telegram: #0088cc;
            --instagram: #E4405F;
            --email: #EA4335;
        }
        
        /* Navbar Styles */
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
        
        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 80px 0 60px;
            margin-bottom: 60px;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,152,0,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Search Box */
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .search-input {
            border-radius: 50px;
            padding: 15px 25px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            font-size: 1.1rem;
        }
        
        .search-btn {
            border-radius: 50px;
            padding: 15px 35px;
            background: var(--secondary);
            color: white;
            border: none;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: var(--secondary-dark);
            transform: scale(1.05);
        }
        
        /* Food Card */
        .food-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            height: 100%;
            position: relative;
        }
        
        .food-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .food-image {
            position: relative;
            overflow: hidden;
            height: 250px;
        }
        
        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .food-card:hover .food-image img {
            transform: scale(1.1);
        }
        
        .food-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 1;
        }
        
        .food-body {
            padding: 20px;
        }
        
        .food-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .food-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        
        .food-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 15px 0;
        }
        
        .food-price small {
            font-size: 0.9rem;
            font-weight: 400;
            color: #6c757d;
        }
        
        .btn-order {
            background: var(--dark);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-order:hover {
            background: var(--secondary);
            transform: translateY(-2px);
            color: white;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 20px;
        }
        
        .empty-state i {
            font-size: 5rem;
            color: var(--secondary);
            margin-bottom: 20px;
        }
        
        /* Results Count */
        .results-count {
            text-align: center;
            margin-bottom: 30px;
            color: #6c757d;
            font-weight: 500;
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
        
        /* Contact Icons Colors */
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
            width: 30px;
            font-size: 1.3rem;
            display: inline-block;
            text-align: center;
        }
        
        .whatsapp-icon { color: #25D366; }
        .facebook-icon { color: #1877F2; }
        .telegram-icon { color: #0088cc; }
        .instagram-icon { color: #E4405F; }
        .email-icon { color: #EA4335; }
        .location-icon { color: #ff4444; }
        
        .footer .contact-item a {
            transition: all 0.3s ease;
            margin-right: 8px;
        }
        
        .footer .contact-item:hover a {
            color: white !important;
        }
        
        .footer .contact-item:hover .contact-icon {
            transform: scale(1.2);
        }
        
        /* Navbar Toggler */
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        /* Footer Divider */
        .footer-divider {
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
            height: 1px;
            border: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 120px 0 40px;
            }
            
            .food-title {
                font-size: 1.1rem;
            }
            
            .food-price {
                font-size: 1.4rem;
            }
            
            .search-input {
                font-size: 0.9rem;
                padding: 12px 20px;
            }
            
            .search-btn {
                padding: 12px 25px;
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
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 50px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb-item a {
            color: var(--secondary);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
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
                <li class="nav-item"><a class="nav-link" href="offers.php">العروض الخاصة</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Header -->
<section class="page-header">
    <div class="container">
        <h1 class="text-center text-white display-4 fw-bold mb-3" data-aos="fade-down">
            <i class="fas fa-utensils text-warning me-2"></i>قائمة الطعام
        </h1>
        <p class="text-center text-white-50 lead mb-0" data-aos="fade-up">
            اكتشف أشهى الأطباق الجزائرية الأصيلة
        </p>
    </div>
</section>

<div class="container py-4">
    
    <!-- Search Box -->
    <div class="search-box mb-5" data-aos="fade-up">
        <form method="GET" action="" id="searchForm" class="d-flex gap-2">
            <input type="text" 
                   name="search" 
                   class="form-control search-input" 
                   placeholder="🔍 ابحث عن طبق... (مشاوي، كسكسي، طاجين...)" 
                   value="<?php echo htmlspecialchars($search); ?>"
                   autocomplete="off">
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> بحث
            </button>
            <?php if(!empty($search)): ?>
            <a href="menu.php" class="btn btn-secondary search-btn" style="background: #6c757d;">
                <i class="fas fa-times"></i> إلغاء
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Results Count -->
    <div class="results-count" data-aos="fade-up" data-aos-delay="100">
        <i class="fas fa-list-ul me-2"></i>
        عدد الأطباق: <strong><?php echo count($foods); ?></strong>
        <?php if(!empty($search)): ?>
        <span class="text-muted"> | نتائج البحث عن: "<?php echo htmlspecialchars($search); ?>"</span>
        <?php endif; ?>
    </div>
    
    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner-border text-warning" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
        </div>
    </div>
    
    <!-- Food Grid -->
    <div class="row g-4" id="foodGrid">
        <?php if(count($foods) > 0): ?>
            <?php foreach($foods as $index => $food): ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index % 6) * 50; ?>">
                <div class="food-card">
                    <div class="food-image">
                        <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                             alt="<?php echo htmlspecialchars($food['name']); ?>"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\' viewBox=\'0 0 400 300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\%\' y=\'50\%\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-family=\'Cairo\' font-size=\'20\'%3Eصورة غير متوفرة%3C/text%3E%3C/svg%3E'">
                        <span class="food-badge">
                            <i class="fas fa-fire"></i> جديد
                        </span>
                    </div>
                    <div class="food-body">
                        <h3 class="food-title"><?php echo htmlspecialchars($food['name']); ?></h3>
                        <p class="food-description">
                            <?php echo htmlspecialchars(mb_substr($food['description'], 0, 100)); ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="food-price">
                                <?php echo number_format($food['price'], 0); ?> <small>DA</small>
                            </div>
                            <div class="rating">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star-half-alt text-warning"></i>
                            </div>
                        </div>
                        <a href="food-details.php?id=<?php echo $food['id']; ?>" class="btn-order">
                            <i class="fas fa-eye me-2"></i> عرض التفاصيل
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state" data-aos="fade-up">
                    <i class="fas fa-search"></i>
                    <h3 class="mb-3">لا توجد نتائج</h3>
                    <p class="text-muted mb-4">عذراً، لم نجد أي أطباق تطابق بحثك: "<?php echo htmlspecialchars($search); ?>"</p>
                    <a href="menu.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-arrow-right"></i> عرض جميع الأطباق
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
</div>

<!-- Footer - تم إخفاء الأرقام والنصوص -->
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
            <!-- Contact Information - إخفاء الأرقام والعرض كنصوص -->
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">تواصل معنا</h5>
                <div class="text-white-50">
                    <!-- WhatsApp - إخفاء الرقم -->
                    <?php if($whatsapp_link && $whatsapp_link != '#'): ?>
                    <div class="mb-2 contact-item">
                        <i class="fab fa-whatsapp contact-icon whatsapp-icon"></i>
                        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="text-white-50 text-decoration-none">
                            واتساب
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Telegram - إخفاء اسم المستخدم -->
                    <?php if($telegram_link && $telegram_link != '#'): ?>
                    <div class="mb-2 contact-item">
                        <i class="fab fa-telegram contact-icon telegram-icon"></i>
                        <a href="<?php echo $telegram_link; ?>" target="_blank" class="text-white-50 text-decoration-none">
                            تلجرام
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Facebook - إخفاء اسم الصفحة -->
                    <?php if($facebook_link && $facebook_link != '#'): ?>
                    <div class="mb-2 contact-item">
                        <i class="fab fa-facebook contact-icon facebook-icon"></i>
                        <a href="<?php echo $facebook_link; ?>" target="_blank" class="text-white-50 text-decoration-none">
                            فيسبوك
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Instagram - إخفاء اسم المستخدم -->
                    <?php if($instagram_link && $instagram_link != '#'): ?>
                    <div class="mb-2 contact-item">
                        <i class="fab fa-instagram contact-icon instagram-icon"></i>
                        <a href="<?php echo $instagram_link; ?>" target="_blank" class="text-white-50 text-decoration-none">
                            انستغرام
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Email - إخفاء البريد -->
                    <?php if(!empty($settings['email'])): ?>
                    <div class="mb-2 contact-item">
                        <i class="fas fa-envelope contact-icon email-icon"></i>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>" class="text-white-50 text-decoration-none">
                            البريد الإلكتروني
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Address - يبقى العنوان ظاهراً -->
                    <?php if(!empty($settings['address'])): ?>
                    <div class="mb-2 contact-item">
                        <i class="fas fa-map-marker-alt contact-icon location-icon"></i>
                        <span class="me-2"><?php echo htmlspecialchars($settings['address']); ?></span>
                    </div>
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
    // Initialize AOS
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // Search Form Submit with Loading Effect
    const searchForm = document.getElementById('searchForm');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const foodGrid = document.getElementById('foodGrid');
    
    if(searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // Show loading spinner
            if(loadingSpinner) {
                loadingSpinner.style.display = 'block';
            }
            if(foodGrid) {
                foodGrid.style.opacity = '0.5';
            }
        });
    }
    
    // Lazy Loading Images
    const images = document.querySelectorAll('img[loading="lazy"]');
    if('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.src;
                    imageObserver.unobserve(img);
                }
            });
        });
        images.forEach(img => imageObserver.observe(img));
    }
    
    // Add smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if(href !== "#") {
                e.preventDefault();
                const target = document.querySelector(href);
                if(target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
    
    // Add hover effect for contact items
    const contactItems = document.querySelectorAll('.contact-item');
    contactItems.forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateX(-5px)';
        });
        item.addEventListener('mouseleave', function() {
            this.style.transform = 'translateX(0)';
        });
    });
</script>

</body>
</html>