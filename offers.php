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

// جلب العروض مع معلومات الأطباق
$sql = "
SELECT offers.*, foods.name as food_name, foods.image, foods.price as original_price
FROM offers
JOIN foods ON offers.food_id = foods.id
WHERE offers.end_date >= CURDATE() OR offers.end_date IS NULL
ORDER BY offers.discount DESC
";

$offers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// حساب السعر بعد الخصم
function getDiscountedPrice($originalPrice, $discount) {
    return $originalPrice - ($originalPrice * $discount / 100);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>العروض الخاصة | <?php echo htmlspecialchars($settings['site_name'] ?? 'مشاوي الإخوة'); ?></title>
    <link rel="icon" href="img/logo.ico" type="image/x-icon">
    <meta name="description" content="أفضل العروض والخصومات على المشاوي والأطباق الجزائرية في مطعم مشاوي الإخوة">
    
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
            background: linear-gradient(135deg, #c62828 0%, #ff9800 100%);
            padding: 80px 0 60px;
            margin-bottom: 60px;
            position: relative;
            overflow: hidden;
        }
        
        .page-header::before {
            content: '🔥';
            position: absolute;
            font-size: 300px;
            opacity: 0.1;
            bottom: -50px;
            left: -50px;
            transform: rotate(-15deg);
        }
        
        .page-header::after {
            content: '🎁';
            position: absolute;
            font-size: 250px;
            opacity: 0.1;
            top: -50px;
            right: -50px;
            transform: rotate(15deg);
        }
        
        /* Offer Card */
        .offer-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            height: 100%;
            position: relative;
        }
        
        .offer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(198,40,40,0.2);
        }
        
        /* Discount Badge */
        .discount-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #c62828, #ff9800);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.2rem;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .discount-badge i {
            margin-left: 5px;
        }
        
        /* Offer Image */
        .offer-image {
            position: relative;
            overflow: hidden;
            height: 250px;
        }
        
        .offer-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .offer-card:hover .offer-image img {
            transform: scale(1.1);
        }
        
        .offer-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
        }
        
        .offer-card:hover .offer-overlay {
            opacity: 1;
        }
        
        /* Offer Body */
        .offer-body {
            padding: 25px;
        }
        
        .offer-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        /* Price Section */
        .price-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 15px;
            margin: 15px 0;
        }
        
        .old-price {
            font-size: 1.1rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 10px;
        }
        
        .new-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .discount-info {
            font-size: 0.9rem;
            color: #28a745;
            font-weight: 600;
        }
        
        /* Countdown Timer */
        .countdown {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 15px;
            border-radius: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .countdown-title {
            color: white;
            font-size: 0.85rem;
            margin-bottom: 10px;
        }
        
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .countdown-item {
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 10px;
            text-align: center;
        }
        
        .countdown-number {
            color: var(--secondary);
            font-size: 1.3rem;
            font-weight: 800;
            display: block;
        }
        
        .countdown-label {
            color: white;
            font-size: 0.7rem;
        }
        
        /* Button */
        .btn-offer {
            background: linear-gradient(135deg, #c62828, #ff9800);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-offer:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(198,40,40,0.4);
            color: white;
        }
        
        /* Limited Stock */
        .limited-stock {
            background: #fff3cd;
            padding: 8px;
            border-radius: 10px;
            margin-top: 10px;
            font-size: 0.85rem;
            color: #856404;
            text-align: center;
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
        
        /* Breadcrumb */
        .breadcrumb-item a {
            text-decoration: none;
        }
        
        /* Newsletter */
        .newsletter-box {
            background: linear-gradient(135deg, #fff5eb, #fff);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                padding: 120px 0 40px;
            }
            
            .offer-title {
                font-size: 1.2rem;
            }
            
            .new-price {
                font-size: 1.5rem;
            }
            
            .discount-badge {
                padding: 5px 15px;
                font-size: 1rem;
            }
            
            .navbar-logo {
                height: 35px;
            }
            
            .navbar-brand-text {
                font-size: 1rem;
            }
        }
        
        /* Animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .offer-card:hover .discount-badge {
            animation: shake 0.5s ease;
        }
        
        /* Footer Divider */
        .footer-divider {
            background: linear-gradient(90deg, transparent, var(--secondary), transparent);
            height: 1px;
            border: none;
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
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active text-white-50" aria-current="page">العروض الخاصة</li>
            </ol>
        </nav>
        <p class="text-center text-white-50 lead mb-0" data-aos="fade-up">
            خصومات تصل إلى 50% على أشهى الأطباق
        </p>
    </div>
</section>

<div class="container py-4">
    
    <!-- Offers Grid -->
    <div class="row g-4">
        <?php if(count($offers) > 0): ?>
            <?php foreach($offers as $index => $offer): 
                $discountedPrice = getDiscountedPrice($offer['original_price'], $offer['discount']);
                $endDate = strtotime($offer['end_date']);
                $now = time();
                $daysLeft = ceil(($endDate - $now) / (60 * 60 * 24));
            ?>
            <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo ($index % 6) * 100; ?>">
                <div class="offer-card">
                    <!-- Discount Badge -->
                    <div class="discount-badge">
                        <i class="fas fa-percent"></i>
                        <?php echo $offer['discount']; ?>% OFF
                    </div>
                    
                    <!-- Offer Image -->
                    <div class="offer-image">
                        <img src="uploads/foods/<?php echo htmlspecialchars($offer['image']); ?>" 
                             alt="<?php echo htmlspecialchars($offer['food_name']); ?>"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\' viewBox=\'0 0 400 300\'%3E%3Crect width=\'400\' height=\'300\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50\%\' y=\'50\%\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\' font-family=\'Cairo\' font-size=\'20\'%3Eصورة غير متوفرة%3C/text%3E%3C/svg%3E'">
                        <div class="offer-overlay">
                            <i class="fas fa-gift fa-3x text-white"></i>
                        </div>
                    </div>
                    
                    <!-- Offer Body -->
                    <div class="offer-body">
                        <h3 class="offer-title"><?php echo htmlspecialchars($offer['food_name']); ?></h3>
                        
                        <?php if(!empty($offer['description'])): ?>
                        <p class="text-muted small">
                            <?php echo htmlspecialchars(mb_substr($offer['description'], 0, 80)); ?>...
                        </p>
                        <?php endif; ?>
                        
                        <!-- Price Section -->
                        <div class="price-section text-center">
                            <span class="old-price"><?php echo number_format($offer['original_price'], 0); ?> DA</span>
                            <span class="new-price"><?php echo number_format($discountedPrice, 0); ?> DA</span>
                            <div class="discount-info">
                                <i class="fas fa-save"></i> وفر <?php echo number_format($offer['original_price'] - $discountedPrice, 0); ?> DA
                            </div>
                        </div>
                        
                        <!-- Countdown Timer -->
                        <?php if($offer['end_date'] && $endDate > $now): ?>
                        <div class="countdown">
                            <div class="countdown-title">
                                <i class="fas fa-clock"></i> ينتهي العرض بعد:
                            </div>
                            <div class="countdown-timer" id="countdown-<?php echo $offer['id']; ?>">
                                <div class="countdown-item">
                                    <span class="countdown-number days"><?php echo $daysLeft; ?></span>
                                    <span class="countdown-label">يوم</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number hours">00</span>
                                    <span class="countdown-label">ساعة</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number minutes">00</span>
                                    <span class="countdown-label">دقيقة</span>
                                </div>
                                <div class="countdown-item">
                                    <span class="countdown-number seconds">00</span>
                                    <span class="countdown-label">ثانية</span>
                                </div>
                            </div>
                        </div>
                        <?php elseif($offer['end_date'] && $endDate <= $now): ?>
                        <div class="limited-stock">
                            <i class="fas fa-hourglass-end"></i> العرض منتهي
                        </div>
                        <?php else: ?>
                        <div class="limited-stock">
                            <i class="fas fa-infinity"></i> عرض مستمر
                        </div>
                        <?php endif; ?>
                        
                        <!-- Order Button -->
                        <a href="food-details.php?id=<?php echo $offer['food_id']; ?>" class="btn-offer mt-3 d-block text-center text-decoration-none">
                            <i class="fas fa-shopping-cart me-2"></i> اطلب الآن
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state" data-aos="fade-up">
                    <i class="fas fa-tags"></i>
                    <h3 class="mb-3">لا توجد عروض حالياً</h3>
                    <p class="text-muted mb-4">ترقبوا عروضنا القادمة قريباً!</p>
                    <a href="menu.php" class="btn btn-warning btn-lg">
                        <i class="fas fa-utensils"></i> تصفح القائمة
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Newsletter Section -->
    <?php if(count($offers) > 0): ?>
    <div class="row mt-5" data-aos="fade-up">
        <div class="col-12">
            <div class="newsletter-box p-4 p-md-5 text-center shadow">
                <i class="fas fa-bell fa-3x text-warning mb-3"></i>
                <h3 class="mb-3">لا تفوت العروض القادمة!</h3>
                <p class="text-muted mb-4">اشترك في نشرتنا البريدية لتصلك أحدث العروض والخصومات</p>
                <form class="row g-3 justify-content-center" onsubmit="event.preventDefault(); alert('شكراً للاشتراك! سنرسل لك أحدث العروض على بريدك الإلكتروني.');">
                    <div class="col-md-6">
                        <input type="email" class="form-control form-control-lg" placeholder="بريدك الإلكتروني" required>
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-warning btn-lg px-5">
                            <i class="fas fa-envelope"></i> اشتراك
                        </button>
                    </div>
                </form>
            </div>
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
            <!-- Contact Information - تم إخفاء الأرقام والنصوص -->
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold mb-3">تواصل معنا</h5>
                <div class="text-white-50">
                    <!-- WhatsApp - إخفاء الرقم -->
                    <?php if($whatsapp_link && $whatsapp_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-whatsapp contact-icon whatsapp-icon"></i>
                        <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="text-white-50 text-decoration-none ms-2">
                            واتساب
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Telegram - إخفاء اسم المستخدم -->
                    <?php if($telegram_link && $telegram_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-telegram contact-icon telegram-icon"></i>
                        <a href="<?php echo $telegram_link; ?>" target="_blank" class="text-white-50 text-decoration-none ms-2">
                            تلجرام
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Facebook - إخفاء اسم الصفحة -->
                    <?php if($facebook_link && $facebook_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-facebook contact-icon facebook-icon"></i>
                        <a href="<?php echo $facebook_link; ?>" target="_blank" class="text-white-50 text-decoration-none ms-2">
                            فيسبوك
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Instagram - إخفاء اسم المستخدم -->
                    <?php if($instagram_link && $instagram_link != '#'): ?>
                    <p class="mb-2 contact-item">
                        <i class="fab fa-instagram contact-icon instagram-icon"></i>
                        <a href="<?php echo $instagram_link; ?>" target="_blank" class="text-white-50 text-decoration-none ms-2">
                            انستغرام
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Email - إخفاء البريد -->
                    <?php if(!empty($settings['email'])): ?>
                    <p class="mb-2 contact-item">
                        <i class="fas fa-envelope contact-icon email-icon"></i>
                        <a href="mailto:<?php echo htmlspecialchars($settings['email']); ?>" class="text-white-50 text-decoration-none ms-2">
                            البريد الإلكتروني
                        </a>
                    </p>
                    <?php endif; ?>
                    
                    <!-- Address - يبقى العنوان ظاهراً -->
                    <?php if(!empty($settings['address'])): ?>
                    <p class="mb-2 contact-item">
                        <i class="fas fa-map-marker-alt contact-icon location-icon"></i>
                        <span class="ms-2"><?php echo htmlspecialchars($settings['address']); ?></span>
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>

<script>
    // Initialize AOS with error handling
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
    }
    
    // Countdown Timers
    <?php foreach($offers as $offer): 
        if($offer['end_date'] && strtotime($offer['end_date']) > time()):
    ?>
    function updateCountdown<?php echo $offer['id']; ?>() {
        const endDate = new Date("<?php echo $offer['end_date']; ?>").getTime();
        const now = new Date().getTime();
        const distance = endDate - now;
        
        if(distance < 0) {
            const timerDiv = document.getElementById('countdown-<?php echo $offer['id']; ?>');
            if(timerDiv) {
                timerDiv.innerHTML = '<div class="text-white">العرض انتهى</div>';
            }
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        const timerDiv = document.getElementById('countdown-<?php echo $offer['id']; ?>');
        if(timerDiv) {
            timerDiv.innerHTML = `
                <div class="countdown-item">
                    <span class="countdown-number days">${days}</span>
                    <span class="countdown-label">يوم</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number hours">${hours}</span>
                    <span class="countdown-label">ساعة</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number minutes">${minutes}</span>
                    <span class="countdown-label">دقيقة</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number seconds">${seconds}</span>
                    <span class="countdown-label">ثانية</span>
                </div>
            `;
        }
    }
    
    updateCountdown<?php echo $offer['id']; ?>();
    setInterval(updateCountdown<?php echo $offer['id']; ?>, 1000);
    <?php 
        endif;
    endforeach; 
    ?>
    
    // Lazy Loading Images
    if('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if(entry.isIntersecting) {
                    const img = entry.target;
                    if(img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    imageObserver.unobserve(img);
                }
            });
        });
        document.querySelectorAll('img[loading="lazy"]').forEach(img => imageObserver.observe(img));
    }
    
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