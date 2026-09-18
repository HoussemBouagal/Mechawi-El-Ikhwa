<?php
require_once 'config/database.php';

// التحقق من وجود ID الطلب
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: menu.php');
    exit();
}

$id = intval($_GET['id']); // تأمين المدخلات

// جلب بيانات الطعام
$stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
$stmt->execute([$id]);
$food = $stmt->fetch();

// التحقق من وجود الطعام
if(!$food) {
    header('Location: menu.php');
    exit();
}

// جلب إعدادات الموقع
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch();
$whatsapp = $settings['whatsapp'] ?? '213000000000';

// متغيرات الطلب
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$notes = isset($_GET['notes']) ? trim($_GET['notes']) : '';

// حساب السعر الإجمالي
$total_price = $food['price'] * $quantity;

// رسالة واتساب
$message = "🍽️ *طلب جديد من مشاوي الإخوة* 🍽️\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n";
$message .= "*الطلب رقم:* #" . rand(1000, 9999) . "\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
$message .= "*🍕 اسم الطبق:* " . $food['name'] . "\n";
$message .= "*💰 السعر:* " . number_format($food['price'], 0) . " DA\n";
$message .= "*🔢 الكمية:* " . $quantity . "\n";
$message .= "*💵 الإجمالي:* " . number_format($total_price, 0) . " DA\n\n";

if(!empty($notes)) {
    $message .= "━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "*📝 ملاحظات إضافية:* \n" . $notes . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━\n\n";
}

$message .= "*👤 معلومات العميل:* \n";
$message .= "سيتم إرسالها عبر واتساب\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n";
$message .= "✨ *شكراً لثقتكم بمشاوي الإخوة* ✨";

// رابط واتساب
$whatsapp_clean = ltrim($whatsapp, '+');
$url = "https://wa.me/" . $whatsapp_clean . "?text=" . urlencode($message);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>تأكيد الطلب | مشاوي الإخوة</title>
    
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
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        :root {
            --primary: #c62828;
            --primary-dark: #8e0000;
            --secondary: #ff9800;
            --secondary-dark: #c66900;
            --success: #25D366;
            --dark: #1a1a2e;
        }
        
        .order-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            margin: 20px;
        }
        
        .order-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .order-header::before {
            content: '🍽️';
            position: absolute;
            font-size: 150px;
            opacity: 0.1;
            bottom: -30px;
            left: -30px;
            transform: rotate(-15deg);
        }
        
        .order-header::after {
            content: '✨';
            position: absolute;
            font-size: 120px;
            opacity: 0.1;
            top: -30px;
            right: -30px;
            transform: rotate(15deg);
        }
        
        .order-body {
            padding: 40px;
        }
        
        /* Food Item */
        .food-item {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 20px;
            margin-bottom: 30px;
        }
        
        .food-image {
            width: 100px;
            height: 100px;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .food-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .food-info h3 {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 5px;
        }
        
        .food-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.2rem;
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
        
        /* Total Section */
        .total-section {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 20px;
            border-radius: 20px;
            color: white;
            margin: 20px 0;
        }
        
        .total-amount {
            font-size: 2rem;
            font-weight: 800;
            color: var(--secondary);
        }
        
        /* Notes */
        .notes-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            font-family: 'Cairo', sans-serif;
            resize: vertical;
            margin: 20px 0;
        }
        
        .notes-input:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        /* Buttons */
        .btn-order-whatsapp {
            background: var(--success);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        
        .btn-order-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
            color: white;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 50px;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        
        /* Alert */
        .alert-custom {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .order-body {
                padding: 25px;
            }
            
            .food-item {
                flex-direction: column;
                text-align: center;
            }
            
            .food-image {
                width: 80px;
                height: 80px;
                margin: 0 auto;
            }
            
            .total-amount {
                font-size: 1.5rem;
            }
        }
        
        /* Animation */
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
        
        .order-card {
            animation: fadeInUp 0.6s ease;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            
            <div class="order-card" data-aos="fade-up">
                <div class="order-header">
                    <i class="fas fa-shopping-cart fa-3x text-white mb-3"></i>
                    <h1 class="text-white display-6 fw-bold">تأكيد الطلب</h1>
                    <p class="text-white-50 mb-0">راجع بيانات طلبك قبل الإرسال</p>
                </div>
                
                <div class="order-body">
                    <!-- Alert Info -->
                    <div class="alert-custom">
                        <i class="fas fa-info-circle text-warning me-2"></i>
                        سيتم توجيهك إلى واتساب لإكمال الطلب
                    </div>
                    
                    <!-- Food Details -->
                    <div class="food-item">
                        <div class="food-image">
                            <img src="uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($food['name']); ?>"
                                 onerror="this.src='assets/images/placeholder-food.jpg'">
                        </div>
                        <div class="food-info flex-grow-1">
                            <h3><?php echo htmlspecialchars($food['name']); ?></h3>
                            <p class="text-muted small"><?php echo htmlspecialchars(mb_substr($food['description'], 0, 60)); ?>...</p>
                            <div class="food-price">
                                <i class="fas fa-tag"></i> <?php echo number_format($food['price'], 0); ?> DA
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Form -->
                    <form method="GET" action="" id="orderForm">
                        <input type="hidden" name="id" value="<?php echo $food['id']; ?>">
                        
                        <!-- Quantity Selector -->
                        <label class="fw-bold mb-2">
                            <i class="fas fa-hashtag text-secondary"></i> الكمية:
                        </label>
                        <div class="quantity-selector">
                            <button type="button" class="quantity-btn" id="decreaseBtn">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" 
                                   name="quantity" 
                                   id="quantity" 
                                   class="quantity-input" 
                                   value="<?php echo $quantity; ?>" 
                                   min="1" 
                                   max="99"
                                   readonly>
                            <button type="button" class="quantity-btn" id="increaseBtn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        
                        <!-- Total Price -->
                        <div class="total-section text-center">
                            <i class="fas fa-receipt fa-2x mb-2"></i>
                            <p class="mb-1">إجمالي الطلب</p>
                            <div class="total-amount" id="totalAmount">
                                <?php echo number_format($total_price, 0); ?> DA
                            </div>
                        </div>
                        
                        <!-- Additional Notes -->
                        <label class="fw-bold mb-2">
                            <i class="fas fa-pen text-secondary"></i> ملاحظات إضافية (اختياري):
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  class="notes-input" 
                                  rows="3" 
                                  placeholder="مثال: بدون بصل، إضافة صلصة، تأخير التوصيل..."><?php echo htmlspecialchars($notes); ?></textarea>
                        
                        <!-- Action Buttons -->
                        <button type="submit" class="btn-order-whatsapp" id="orderBtn">
                            <i class="fab fa-whatsapp fa-lg me-2"></i>
                            تأكيد الطلب عبر واتساب
                        </button>
                        
                        <a href="food-details.php?id=<?php echo $food['id']; ?>" class="btn-back text-center d-block text-decoration-none">
                            <i class="fas fa-arrow-right me-2"></i>
                            العودة للتفاصيل
                        </a>
                    </form>
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
    
    // Get elements
    const quantityInput = document.getElementById('quantity');
    const decreaseBtn = document.getElementById('decreaseBtn');
    const increaseBtn = document.getElementById('increaseBtn');
    const totalAmount = document.getElementById('totalAmount');
    const orderBtn = document.getElementById('orderBtn');
    const notesInput = document.getElementById('notes');
    const orderForm = document.getElementById('orderForm');
    
    const foodPrice = <?php echo $food['price']; ?>;
    
    // Update total price
    function updateTotal() {
        let quantity = parseInt(quantityInput.value);
        const total = foodPrice * quantity;
        totalAmount.innerHTML = total.toLocaleString() + ' DA';
    }
    
    // Decrease quantity
    decreaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value);
        if(currentValue > 1) {
            quantityInput.value = currentValue - 1;
            updateTotal();
        }
    });
    
    // Increase quantity
    increaseBtn.addEventListener('click', function() {
        let currentValue = parseInt(quantityInput.value);
        if(currentValue < 99) {
            quantityInput.value = currentValue + 1;
            updateTotal();
        }
    });
    
    // Update total on manual input
    quantityInput.addEventListener('change', function() {
        let value = parseInt(this.value);
        if(isNaN(value) || value < 1) {
            this.value = 1;
        }
        if(value > 99) {
            this.value = 99;
        }
        updateTotal();
    });
    
    // Handle order submission
    orderForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get values
        const quantity = quantityInput.value;
        const notes = notesInput.value;
        
        // Update button loading state
        orderBtn.disabled = true;
        orderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحويل إلى واتساب...';
        
        // Build WhatsApp URL
        let message = "🍽️ *طلب جديد من مشاوي الإخوة* 🍽️%0A";
        message += "━━━━━━━━━━━━━━━━━━━━%0A";
        message += "*الطلب رقم:* #" + Math.floor(Math.random() * 9000 + 1000) + "%0A";
        message += "━━━━━━━━━━━━━━━━━━━━%0A%0A";
        message += "*🍕 اسم الطبق:* <?php echo addslashes($food['name']); ?>%0A";
        message += "*💰 السعر:* <?php echo $food['price']; ?> DA%0A";
        message += "*🔢 الكمية:* " + quantity + "%0A";
        message += "*💵 الإجمالي:* " + (foodPrice * quantity).toLocaleString() + " DA%0A%0A";
        
        if(notes) {
            message += "━━━━━━━━━━━━━━━━━━━━%0A";
            message += "*📝 ملاحظات إضافية:* %0A" + encodeURIComponent(notes) + "%0A";
            message += "━━━━━━━━━━━━━━━━━━━━%0A%0A";
        }
        
        message += "*👤 معلومات العميل:* %0A";
        message += "سيتم إرسالها عبر واتساب%0A%0A";
        message += "━━━━━━━━━━━━━━━━━━━━%0A";
        message += "✨ *شكراً لثقتكم بمشاوي الإخوة* ✨";
        
        const whatsappNumber = "<?php echo ltrim($whatsapp, '+'); ?>";
        const whatsappUrl = "https://wa.me/" + whatsappNumber + "?text=" + message;
        
        // Redirect to WhatsApp
        window.location.href = whatsappUrl;
        
        // Reset button after 2 seconds (in case of popup blocker)
        setTimeout(function() {
            orderBtn.disabled = false;
            orderBtn.innerHTML = '<i class="fab fa-whatsapp fa-lg me-2"></i> تأكيد الطلب عبر واتساب';
        }, 2000);
    });
    
    // Lazy loading images
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
</script>

</body>
</html>