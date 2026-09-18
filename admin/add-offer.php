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
$success = '';

// جلب الأطعمة لعرضها في القائمة
try {
    $foods = $pdo->query("SELECT * FROM foods ORDER BY name ASC")->fetchAll();
} catch(PDOException $e) {
    error_log("Fetch foods error: " . $e->getMessage());
    $foods = [];
}

// معالجة إضافة عرض جديد
if(isset($_POST['add'])){

    $food_id = intval($_POST['food_id']);
    $discount = floatval($_POST['discount']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $description = trim($_POST['description'] ?? '');
    
    // التحقق من صحة البيانات
    if($food_id == 0) {
        $error = "يرجى اختيار طبق";
    } elseif($discount <= 0 || $discount > 100) {
        $error = "نسبة الخصم يجب أن تكون بين 1 و 100";
    } elseif(empty($start_date)) {
        $error = "يرجى تحديد تاريخ بدء العرض";
    } elseif(empty($end_date)) {
        $error = "يرجى تحديد تاريخ انتهاء العرض";
    } elseif(strtotime($start_date) > strtotime($end_date)) {
        $error = "تاريخ البداية يجب أن يكون قبل تاريخ النهاية";
    } elseif(strtotime($end_date) < strtotime(date('Y-m-d'))) {
        $error = "تاريخ النهاية يجب أن يكون في المستقبل";
    } else {
        
        try {
            // جلب معلومات الطعام للتأكد من وجوده
            $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
            $stmt->execute([$food_id]);
            $food = $stmt->fetch();
            
            if(!$food) {
                $error = "الطبق المحدد غير موجود";
            } else {
                
                // التحقق من عدم وجود عرض نشط لنفس الطعام
                $stmt = $pdo->prepare("
                    SELECT * FROM offers 
                    WHERE food_id = ? 
                    AND (
                        (start_date BETWEEN ? AND ?) OR 
                        (end_date BETWEEN ? AND ?) OR
                        (start_date <= ? AND end_date >= ?)
                    )
                ");
                $stmt->execute([$food_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date]);
                
                if($stmt->rowCount() > 0) {
                    $error = "يوجد عرض نشط بالفعل لهذا الطبق في هذه الفترة";
                } else {
                    
                    // إدراج العرض الجديد
                    $stmt = $pdo->prepare("
                        INSERT INTO offers (food_id, discount, start_date, end_date, description, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if($stmt->execute([$food_id, $discount, $start_date, $end_date, $description])) {
                        $success = "تم إضافة العرض بنجاح!";
                        
                        // تفريغ المتغيرات
                        $food_id = $discount = $start_date = $end_date = $description = '';
                        
                        // إعادة التوجيه بعد 2 ثانية
                        echo "<script>setTimeout(() => { window.location.href = 'offers.php?success=added'; }, 2000);</script>";
                    } else {
                        $error = "حدث خطأ أثناء إضافة العرض";
                    }
                }
            }
        } catch(PDOException $e) {
            error_log("Add offer error: " . $e->getMessage());
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
    <title>إضافة عرض جديد | مشاوي الإخوة</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
          <link rel="icon" href="../img/logo.ico" type="image/x-icon">

    <!-- Flatpickr for date picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    
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
            --danger: #dc3545;
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
        
        /* Form Card */
        .form-card {
            background: white;
            border-radius: 25px;
            padding: 35px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
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
        
        /* Preview Card */
        .preview-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }
        
        .preview-price {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .old-price {
            font-size: 1.2rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 10px;
        }
        
        .discount-badge {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            display: inline-block;
            margin-top: 10px;
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
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
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
        
        /* Food Info */
        .food-info {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-top: 10px;
            display: none;
        }
        
        .food-info.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Required field */
        .required:after {
            content: " *";
            color: var(--primary);
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
            .form-card {
                padding: 25px;
            }
        }
        
        @media print {
            .sidebar, .menu-toggle, .top-navbar, .btn-save, .btn-cancel, .preview-card {
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
        <li><a href="foods.php"><i class="fas fa-utensils"></i> الأطباق</a></li>
        <li><a href="offers.php" class="active"><i class="fas fa-tags"></i> العروض</a></li>
        <li><a href="users.php"><i class="fas fa-users"></i> المستخدمين</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i> الإعدادات</a></li>
        <li><a href="../logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="welcome-text">
            <h2>مرحباً، <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
            <p class="text-muted mb-0">أضف عروضاً وخصومات لجذب المزيد من الزبائن</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            
            <div class="form-card">
                
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
                
                <!-- Add Offer Form -->
                <form method="POST" id="offerForm">
                    
                    <!-- Food Selection -->
                    <div class="mb-3">
                        <label class="form-label required">اختر الطبق</label>
                        <select name="food_id" id="food_id" class="form-select form-select-custom" required>
                            <option value="">-- اختر طبقاً --</option>
                            <?php foreach($foods as $food): ?>
                            <option value="<?php echo $food['id']; ?>" 
                                    data-price="<?php echo $food['price']; ?>"
                                    data-name="<?php echo htmlspecialchars($food['name']); ?>"
                                    <?php echo (isset($_POST['food_id']) && $_POST['food_id'] == $food['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($food['name']); ?> - <?php echo number_format($food['price'], 0); ?> DA
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Food Info (Dynamic) -->
                    <div id="foodInfo" class="food-info">
                        <div class="row">
                            <div class="col-6">
                                <strong>السعر الأصلي:</strong>
                                <span id="originalPrice">0</span> DA
                            </div>
                            <div class="col-6">
                                <strong>السعر بعد الخصم:</strong>
                                <span id="discountedPrice">0</span> DA
                            </div>
                        </div>
                    </div>
                    
                    <!-- Discount Percentage -->
                    <div class="mb-3">
                        <label class="form-label required">نسبة الخصم (%)</label>
                        <input type="number" 
                               name="discount" 
                               id="discount"
                               class="form-control form-control-custom" 
                               placeholder="مثال: 20"
                               min="1" 
                               max="100"
                               step="1"
                               value="<?php echo isset($_POST['discount']) ? htmlspecialchars($_POST['discount']) : ''; ?>"
                               required>
                        <small class="text-muted">أدخل نسبة الخصم من 1% إلى 100%</small>
                    </div>
                    
                    <!-- Start Date -->
                    <div class="mb-3">
                        <label class="form-label required">تاريخ بدء العرض</label>
                        <input type="text" 
                               name="start_date" 
                               id="start_date"
                               class="form-control form-control-custom datepicker" 
                               placeholder="اختر تاريخ البدء"
                               value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : date('Y-m-d'); ?>"
                               required>
                    </div>
                    
                    <!-- End Date -->
                    <div class="mb-3">
                        <label class="form-label required">تاريخ انتهاء العرض</label>
                        <input type="text" 
                               name="end_date" 
                               id="end_date"
                               class="form-control form-control-custom datepicker" 
                               placeholder="اختر تاريخ الانتهاء"
                               value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : date('Y-m-d', strtotime('+30 days')); ?>"
                               required>
                    </div>
                    
                    <!-- Description (Optional) -->
                    <div class="mb-3">
                        <label class="form-label">وصف العرض (اختياري)</label>
                        <textarea name="description" 
                                  class="form-control form-control-custom" 
                                  rows="3"
                                  placeholder="مثال: خصم خاص بمناسبة شهر رمضان..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="add" class="btn-save" id="submitBtn">
                            <i class="fas fa-save me-2"></i> حفظ العرض
                        </button>
                        <button type="reset" class="btn-cancel" id="resetBtn">
                            <i class="fas fa-undo me-2"></i> إعادة تعيين
                        </button>
                        <a href="offers.php" class="btn-cancel text-decoration-none">
                            <i class="fas fa-times me-2"></i> إلغاء
                        </a>
                    </div>
                    
                </form>
            </div>
            
        </div>
        
        <div class="col-lg-4">
            
            <!-- Preview Card -->
            <div class="form-card" id="previewCard">
                <h5 class="mb-3">
                    <i class="fas fa-eye"></i> معاينة العرض
                </h5>
                
                <div class="preview-card">
                    <div id="previewFoodName" class="fw-bold mb-2">---</div>
                    <div>
                        <span class="old-price" id="previewOldPrice">0 DA</span>
                        <span class="preview-price" id="previewNewPrice">0 DA</span>
                    </div>
                    <div class="discount-badge" id="previewDiscount">
                        خصم 0%
                    </div>
                    <div class="mt-3 text-muted small" id="previewDates">
                        اختر التواريخ
                    </div>
                </div>
            </div>
            
            <!-- Tips Card -->
            <div class="alert alert-info border-0 rounded-4 shadow-sm mt-4">
                <i class="fas fa-lightbulb text-warning fa-2x float-start me-3"></i>
                <h5 class="fw-bold">نصائح للعروض الناجحة</h5>
                <ul class="mb-0">
                    <li>خصم 10-20% للأطباق الأكثر مبيعاً</li>
                    <li>خصم 30-50% للأطباق الموسمية</li>
                    <li>اجعل العروض محدودة الوقت لخلق الإلحاح</li>
                    <li>استخدم أوصاف جذابة للعرض</li>
                </ul>
            </div>
            
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>

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
    
    // Initialize date pickers
    flatpickr(".datepicker", {
        locale: "ar",
        dateFormat: "Y-m-d",
        minDate: "today",
        altInput: true,
        altFormat: "F j, Y",
        allowInput: false
    });
    
    // Get elements
    const foodSelect = document.getElementById('food_id');
    const discountInput = document.getElementById('discount');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const foodInfo = document.getElementById('foodInfo');
    const originalPriceSpan = document.getElementById('originalPrice');
    const discountedPriceSpan = document.getElementById('discountedPrice');
    
    // Preview elements
    const previewFoodName = document.getElementById('previewFoodName');
    const previewOldPrice = document.getElementById('previewOldPrice');
    const previewNewPrice = document.getElementById('previewNewPrice');
    const previewDiscount = document.getElementById('previewDiscount');
    const previewDates = document.getElementById('previewDates');
    
    // Store selected food data
    let selectedFood = {
        id: 0,
        name: '',
        price: 0
    };
    
    // Update food info when selection changes
    if(foodSelect) {
        foodSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if(this.value) {
                selectedFood.id = this.value;
                selectedFood.name = selectedOption.dataset.name;
                selectedFood.price = parseFloat(selectedOption.dataset.price);
                
                // Show food info
                if(foodInfo) foodInfo.classList.add('show');
                if(originalPriceSpan) originalPriceSpan.textContent = selectedFood.price.toLocaleString();
                
                // Update preview
                if(previewFoodName) previewFoodName.textContent = selectedFood.name;
                if(previewOldPrice) previewOldPrice.textContent = selectedFood.price.toLocaleString() + ' DA';
                
                // Calculate discount if any
                if(discountInput && discountInput.value) {
                    updateDiscountPreview();
                } else if(previewNewPrice) {
                    previewNewPrice.textContent = selectedFood.price.toLocaleString() + ' DA';
                }
            } else {
                if(foodInfo) foodInfo.classList.remove('show');
                if(previewFoodName) previewFoodName.textContent = '---';
                if(previewOldPrice) previewOldPrice.textContent = '0 DA';
                if(previewNewPrice) previewNewPrice.textContent = '0 DA';
                if(previewDiscount) previewDiscount.textContent = 'خصم 0%';
                selectedFood = { id: 0, name: '', price: 0 };
            }
            
            updateDiscountedPrice();
        });
    }
    
    // Update discount preview
    function updateDiscountPreview() {
        if(selectedFood.price > 0 && discountInput && discountInput.value) {
            let discount = parseFloat(discountInput.value);
            let newPrice = selectedFood.price - (selectedFood.price * discount / 100);
            
            if(previewNewPrice) previewNewPrice.textContent = Math.round(newPrice).toLocaleString() + ' DA';
            if(previewDiscount) {
                previewDiscount.textContent = `خصم ${discount}%`;
                previewDiscount.style.background = discount >= 30 ? 'linear-gradient(135deg, #dc3545, #ff9800)' : 'linear-gradient(135deg, #c62828, #ff9800)';
            }
        }
    }
    
    // Update discounted price in info box
    function updateDiscountedPrice() {
        if(selectedFood.price > 0 && discountInput && discountInput.value) {
            let discount = parseFloat(discountInput.value);
            let newPrice = selectedFood.price - (selectedFood.price * discount / 100);
            if(discountedPriceSpan) discountedPriceSpan.textContent = Math.round(newPrice).toLocaleString();
        } else if(selectedFood.price > 0 && discountedPriceSpan) {
            discountedPriceSpan.textContent = selectedFood.price.toLocaleString();
        } else if(discountedPriceSpan) {
            discountedPriceSpan.textContent = '0';
        }
    }
    
    // Update on discount change
    if(discountInput) {
        discountInput.addEventListener('input', function() {
            let value = parseFloat(this.value);
            
            if(value < 0) this.value = 0;
            if(value > 100) this.value = 100;
            
            updateDiscountPreview();
            updateDiscountedPrice();
        });
    }
    
    // Update dates preview
    function updateDatesPreview() {
        if(startDateInput && startDateInput.value && endDateInput && endDateInput.value && previewDates) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            previewDates.innerHTML = `
                <i class="fas fa-calendar-alt"></i> 
                من ${startDate.toLocaleDateString('ar-EG', options)} 
                إلى ${endDate.toLocaleDateString('ar-EG', options)}
            `;
        } else if(startDateInput && startDateInput.value && previewDates) {
            previewDates.innerHTML = `<i class="fas fa-calendar-alt"></i> يبدأ في ${startDateInput.value}`;
        } else if(endDateInput && endDateInput.value && previewDates) {
            previewDates.innerHTML = `<i class="fas fa-calendar-alt"></i> ينتهي في ${endDateInput.value}`;
        } else if(previewDates) {
            previewDates.innerHTML = 'اختر التواريخ';
        }
    }
    
    if(startDateInput) startDateInput.addEventListener('change', updateDatesPreview);
    if(endDateInput) endDateInput.addEventListener('change', updateDatesPreview);
    
    // Reset form
    const resetBtn = document.getElementById('resetBtn');
    if(resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            setTimeout(() => {
                if(foodSelect) foodSelect.dispatchEvent(new Event('change'));
                updateDatesPreview();
            }, 100);
        });
    }
    
    // Form validation before submit
    const form = document.getElementById('offerForm');
    const submitBtn = document.getElementById('submitBtn');
    
    if(form && submitBtn) {
        form.addEventListener('submit', function(e) {
            if(!foodSelect || !foodSelect.value) {
                e.preventDefault();
                alert('يرجى اختيار طبق');
                return;
            }
            
            if(!discountInput || !discountInput.value || discountInput.value <= 0) {
                e.preventDefault();
                alert('يرجى إدخال نسبة خصم صحيحة');
                return;
            }
            
            if(!startDateInput || !startDateInput.value) {
                e.preventDefault();
                alert('يرجى تحديد تاريخ بدء العرض');
                return;
            }
            
            if(!endDateInput || !endDateInput.value) {
                e.preventDefault();
                alert('يرجى تحديد تاريخ انتهاء العرض');
                return;
            }
            
            // Check date range
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            
            if(startDate > endDate) {
                e.preventDefault();
                alert('تاريخ البداية يجب أن يكون قبل تاريخ النهاية');
                return;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري الحفظ...';
        });
    }
    
    // Trigger initial updates
    if(foodSelect && foodSelect.value) {
        foodSelect.dispatchEvent(new Event('change'));
    }
    updateDatesPreview();
</script>

</body>
</html>