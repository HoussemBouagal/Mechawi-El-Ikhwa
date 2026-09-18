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

// التحقق من وجود ID صالح
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: foods.php?error=no_id");
    exit();
}

$id = intval($_GET['id']);

// جلب بيانات الطعام
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

// جلب التصنيفات الموجودة للإقتراح
try {
    $existing_categories = $pdo->query("SELECT DISTINCT category FROM foods WHERE category IS NOT NULL AND category != '' LIMIT 10")->fetchAll();
} catch(PDOException $e) {
    $existing_categories = [];
}

// معالجة تحديث البيانات
if(isset($_POST['update'])){
    
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = trim($_POST['category'] ?? '');
    
    // التحقق من صحة البيانات
    if(empty($name)) {
        $error = "يرجى إدخال اسم الطبق";
    } elseif(empty($description)) {
        $error = "يرجى إدخال وصف الطبق";
    } elseif($price <= 0) {
        $error = "يرجى إدخال سعر صحيح";
    } else {
        
        $image_name = $food['image']; // الاحتفاظ بالصورة القديمة
        
        // معالجة رفع صورة جديدة إذا تم اختيارها
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $image_tmp = $image['tmp_name'];
            $image_size = $image['size'];
            
            // التحقق من نوع الصورة
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $image_extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            
            if(!in_array($image_extension, $allowed_extensions)) {
                $error = "الصورة غير مدعومة. الأنواع المسموحة: jpg, jpeg, png, gif, webp";
            } elseif($image_size > 5 * 1024 * 1024) {
                $error = "حجم الصورة كبير جداً. الحد الأقصى 5MB";
            } else {
                // إنشاء اسم فريد للصورة الجديدة
                $image_name = time() . '_' . uniqid() . '.' . $image_extension;
                $upload_path = "../uploads/foods/" . $image_name;
                
                // إنشاء المجلد إذا لم يكن موجوداً
                if(!is_dir("../uploads/foods")) {
                    mkdir("../uploads/foods", 0777, true);
                }
                
                // رفع الصورة الجديدة
                if(move_uploaded_file($image_tmp, $upload_path)) {
                    // حذف الصورة القديمة (إذا كانت موجودة وليست placeholder)
                    $old_image_path = "../uploads/foods/" . $food['image'];
                    if(!empty($food['image']) && file_exists($old_image_path) && $food['image'] != 'placeholder.jpg') {
                        unlink($old_image_path);
                    }
                } else {
                    $error = "حدث خطأ أثناء رفع الصورة";
                    $image_name = $food['image']; // استعادة الصورة القديمة
                }
            }
        }
        
        // إذا لم يحدث خطأ، نقوم بتحديث قاعدة البيانات
        if(empty($error)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE foods 
                    SET name = ?, description = ?, price = ?, image = ?, category = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                if($stmt->execute([$name, $description, $price, $image_name, $category, $id])) {
                    $success = "تم تحديث الطبق بنجاح!";
                    
                    // تحديث المتغيرات المحلية
                    $food['name'] = $name;
                    $food['description'] = $description;
                    $food['price'] = $price;
                    $food['image'] = $image_name;
                    $food['category'] = $category;
                    
                    // إعادة التوجيه بعد 2 ثانية
                    echo "<script>setTimeout(() => { window.location.href = 'foods.php?success=updated'; }, 2000);</script>";
                } else {
                    $error = "حدث خطأ أثناء تحديث الطبق";
                }
            } catch(PDOException $e) {
                error_log("Update food error: " . $e->getMessage());
                $error = "حدث خطأ في قاعدة البيانات";
            }
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
    <title>تعديل طبق | مشاوي الإخوة</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="icon" href="../img/logo.ico" type="image/x-icon">

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
        
        textarea.form-control-custom {
            resize: vertical;
            min-height: 120px;
        }
        
        /* Image Preview */
        .current-image {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .current-image img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .image-preview {
            border: 2px dashed #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            margin-top: 15px;
        }
        
        .image-preview:hover {
            border-color: var(--secondary);
            background: #fff;
        }
        
        .preview-img {
            max-width: 200px;
            max-height: 200px;
            margin: 15px auto;
            display: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .preview-img.show {
            display: block;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #999;
            margin-bottom: 10px;
        }
        
        /* Buttons */
        .btn-update {
            background: linear-gradient(135deg, var(--warning), #e0a800);
            color: var(--dark);
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,193,7,0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 35px;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: #5a6268;
            color: white;
        }
        
        .btn-update.loading {
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
    
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="welcome-text">
            <h2>مرحباً، <?php echo htmlspecialchars($admin_name); ?> 👋</h2>
            <p class="text-muted mb-0">قم بتعديل بيانات الطبق</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <div class="row justify-content-center">
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
                
                <!-- Edit Food Form -->
                <form method="POST" enctype="multipart/form-data" id="editFoodForm">
                    
                    <!-- Current Image -->
                    <div class="mb-3">
                        <label class="form-label">الصورة الحالية</label>
                        <div class="current-image">
                            <?php 
                            $image_path = "../uploads/foods/" . htmlspecialchars($food['image']);
                            if(!empty($food['image']) && file_exists($image_path)):
                            ?>
                            <img src="<?php echo $image_path; ?>" 
                                 alt="<?php echo htmlspecialchars($food['name']); ?>">
                            <?php else: ?>
                            <div class="bg-secondary d-flex align-items-center justify-content-center text-white" 
                                 style="width: 200px; height: 200px; margin: 0 auto; border-radius: 15px;">
                                <i class="fas fa-image fa-3x"></i>
                            </div>
                            <?php endif; ?>
                            <p class="text-muted small mt-2">الصورة الحالية للطبق</p>
                        </div>
                    </div>
                    
                    <!-- Image Upload -->
                    <div class="mb-3">
                        <label class="form-label">تغيير الصورة (اختياري)</label>
                        <div class="image-preview" id="imagePreviewBox">
                            <input type="file" 
                                   name="image" 
                                   id="imageInput" 
                                   accept="image/*"
                                   style="display: none;">
                            <i class="fas fa-cloud-upload-alt upload-icon"></i>
                            <p class="mb-0">انقر هنا لاختيار صورة جديدة</p>
                            <p class="text-muted small mb-0">jpg, jpeg, png, gif, webp (max 5MB)</p>
                            <img id="previewImg" class="preview-img" alt="معاينة الصورة الجديدة">
                        </div>
                    </div>
                    
                    <!-- Food Name -->
                    <div class="mb-3">
                        <label class="form-label required">اسم الطبق</label>
                        <input type="text" 
                               name="name" 
                               class="form-control form-control-custom" 
                               placeholder="مثال: شواية مشكل"
                               value="<?php echo htmlspecialchars($food['name']); ?>"
                               required>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label required">وصف الطبق</label>
                        <textarea name="description" 
                                  class="form-control form-control-custom" 
                                  placeholder="وصف تفصيلي للطبق..."
                                  required><?php echo htmlspecialchars($food['description']); ?></textarea>
                        <small class="text-muted">يمكنك إضافة المكونات وطريقة التحضير هنا</small>
                    </div>
                    
                    <!-- Price -->
                    <div class="mb-3">
                        <label class="form-label required">السعر (DA)</label>
                        <input type="number" 
                               name="price" 
                               class="form-control form-control-custom" 
                               placeholder="مثال: 1500"
                               step="0.01"
                               min="0"
                               value="<?php echo $food['price']; ?>"
                               required>
                    </div>
                    
                    <!-- Category with datalist -->
                    <div class="mb-3">
                        <label class="form-label">التصنيف (اختياري)</label>
                        <input type="text" 
                               name="category" 
                               list="categoriesList"
                               class="form-control form-control-custom" 
                               placeholder="مثال: مشاوي, مقبلات, مشروبات"
                               value="<?php echo htmlspecialchars($food['category'] ?? ''); ?>">
                        <datalist id="categoriesList">
                            <?php foreach($existing_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php endforeach; ?>
                            <option value="مشاوي">
                            <option value="مقبلات">
                            <option value="مشروبات">
                            <option value="حلويات">
                            <option value="سلطات">
                        </datalist>
                        <small class="text-muted">يمكنك إضافة تصنيف جديد أو اختيار من القائمة</small>
                    </div>
                    
                    <!-- Buttons -->
                    <div class="d-flex gap-3 mt-4">
                        <button type="submit" name="update" class="btn-update" id="submitBtn">
                            <i class="fas fa-save me-2"></i> تحديث البيانات
                        </button>
                        <a href="foods.php" class="btn-cancel">
                            <i class="fas fa-times me-2"></i> إلغاء
                        </a>
                    </div>
                    
                </form>
            </div>
            
            <!-- Tips Card -->
            <div class="alert alert-info border-0 rounded-4 shadow-sm">
                <i class="fas fa-lightbulb text-warning fa-2x float-start me-3"></i>
                <h5 class="fw-bold">نصائح لتعديل الطبق</h5>
                <ul class="mb-0">
                    <li>يمكنك تغيير الصورة باختيار صورة جديدة (اختياري)</li>
                    <li>تأكد من صحة السعر قبل التحديث</li>
                    <li>الوصف الجيد يساعد الزبائن على اتخاذ القرار</li>
                    <li>إضافة تصنيف يساعد في تنظيم القائمة</li>
                </ul>
            </div>
            
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
    
    // Image Preview
    const imageInput = document.getElementById('imageInput');
    const previewImg = document.getElementById('previewImg');
    const imagePreviewBox = document.getElementById('imagePreviewBox');
    const submitBtn = document.getElementById('submitBtn');
    
    if(imagePreviewBox && imageInput) {
        // Open file selector when clicking on preview box
        imagePreviewBox.addEventListener('click', function() {
            imageInput.click();
        });
        
        // Preview image when selected
        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if(file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if(!allowedTypes.includes(file.type)) {
                    alert('نوع الصورة غير مدعوم. الأنواع المسموحة: jpg, jpeg, png, gif, webp');
                    imageInput.value = '';
                    if(previewImg) previewImg.classList.remove('show');
                    return;
                }
                
                // Validate file size (5MB)
                if(file.size > 5 * 1024 * 1024) {
                    alert('حجم الصورة كبير جداً. الحد الأقصى 5MB');
                    imageInput.value = '';
                    if(previewImg) previewImg.classList.remove('show');
                    return;
                }
                
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    if(previewImg) {
                        previewImg.src = e.target.result;
                        previewImg.classList.add('show');
                    }
                    if(imagePreviewBox) imagePreviewBox.style.borderColor = '#28a745';
                }
                reader.readAsDataURL(file);
            } else {
                if(previewImg) previewImg.classList.remove('show');
                if(imagePreviewBox) imagePreviewBox.style.borderColor = '#e0e0e0';
            }
        });
        
        // Drag and drop for image
        imagePreviewBox.addEventListener('dragover', function(e) {
            e.preventDefault();
            imagePreviewBox.style.borderColor = '#ff9800';
            imagePreviewBox.style.background = '#fff3e0';
        });
        
        imagePreviewBox.addEventListener('dragleave', function(e) {
            e.preventDefault();
            imagePreviewBox.style.borderColor = '#e0e0e0';
            imagePreviewBox.style.background = '#f8f9fa';
        });
        
        imagePreviewBox.addEventListener('drop', function(e) {
            e.preventDefault();
            imagePreviewBox.style.borderColor = '#e0e0e0';
            imagePreviewBox.style.background = '#f8f9fa';
            
            const file = e.dataTransfer.files[0];
            if(file && file.type.startsWith('image/')) {
                imageInput.files = e.dataTransfer.files;
                
                // Trigger change event
                const event = new Event('change');
                imageInput.dispatchEvent(event);
            }
        });
    }
    
    // Form validation before submit
    const form = document.getElementById('editFoodForm');
    
    if(form && submitBtn) {
        form.addEventListener('submit', function(e) {
            const name = document.querySelector('input[name="name"]');
            const description = document.querySelector('textarea[name="description"]');
            const price = document.querySelector('input[name="price"]');
            
            if(name && !name.value.trim()) {
                e.preventDefault();
                alert('يرجى إدخال اسم الطبق');
                name.focus();
                return;
            }
            
            if(description && !description.value.trim()) {
                e.preventDefault();
                alert('يرجى إدخال وصف الطبق');
                description.focus();
                return;
            }
            
            if(price && (!price.value || price.value <= 0)) {
                e.preventDefault();
                alert('يرجى إدخال سعر صحيح');
                price.focus();
                return;
            }
            
            // Show loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> جاري التحديث...';
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