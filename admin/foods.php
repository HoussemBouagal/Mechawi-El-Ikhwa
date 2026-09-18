<?php
// إعدادات الأمان للجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();

require_once '../config/database.php';

// التحقق من صلاحية المسؤول مباشرة (بدون use of auth.php)
if(!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit();
}

// معالجة البحث والتصفية
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_desc';

// بناء استعلام SQL
$sql = "SELECT * FROM foods WHERE 1=1";
$params = [];

if(!empty($search)) {
    $sql .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if(!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

// ترتيب النتائج
$allowed_sorts = ['name_asc', 'name_desc', 'price_asc', 'price_desc', 'id_asc', 'id_desc'];
if(in_array($sort, $allowed_sorts)) {
    switch($sort) {
        case 'name_asc': $sql .= " ORDER BY name ASC"; break;
        case 'name_desc': $sql .= " ORDER BY name DESC"; break;
        case 'price_asc': $sql .= " ORDER BY price ASC"; break;
        case 'price_desc': $sql .= " ORDER BY price DESC"; break;
        case 'id_asc': $sql .= " ORDER BY id ASC"; break;
        default: $sql .= " ORDER BY id DESC";
    }
} else {
    $sql .= " ORDER BY id DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $foods = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Foods query error: " . $e->getMessage());
    $foods = [];
}

// جلب الفئات للفلترة
try {
    $categories = $pdo->query("SELECT DISTINCT category FROM foods WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

// إحصائيات سريعة
$total_foods = count($foods);
$total_price = array_sum(array_column($foods, 'price'));
$avg_price = $total_foods > 0 ? $total_price / $total_foods : 0;

// اسم المسؤول
$admin_name = $_SESSION['admin_name'] ?? 'مسؤول';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأطباق | مشاوي الإخوة</title>
      <link rel="icon" href="../img/logo.ico" type="image/x-icon">

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
            color: #1a1a2e;
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
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
            text-align: center;
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
            margin: 0 auto 15px;
            font-size: 1.8rem;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, #c62828, #ff9800); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #1a1a2e;
            margin: 10px 0 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Search Section */
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }
        
        .table-custom th {
            background: #f8f9fa;
            padding: 12px;
            font-weight: 600;
        }
        
        .table-custom td {
            padding: 12px;
            vertical-align: middle;
        }
        
        .food-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .price-badge {
            background: linear-gradient(135deg, #c62828, #ff9800);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }
        
        .btn-action {
            padding: 6px 12px;
            margin: 2px;
            border-radius: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            background: #c62828;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 10px;
            cursor: pointer;
        }
        
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
            .stat-number {
                font-size: 1.3rem;
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
            <p class="text-muted mb-0">إدارة قائمة الطعام والأطباق</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_foods); ?></div>
                <div class="stat-label">إجمالي الأطباق</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo number_format($total_price, 0); ?> DA</div>
                <div class="stat-label">إجمالي القيمة</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-calculator"></i>
                </div>
                <div class="stat-number"><?php echo number_format($avg_price, 0); ?> DA</div>
                <div class="stat-label">متوسط السعر</div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="search-section">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="بحث باسم الطبق أو الوصف..."
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">كل التصنيفات</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                            <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="sort" class="form-select">
                    <option value="id_desc" <?php echo $sort == 'id_desc' ? 'selected' : ''; ?>>الأحدث أولاً</option>
                    <option value="id_asc" <?php echo $sort == 'id_asc' ? 'selected' : ''; ?>>الأقدم أولاً</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>الاسم (أ-ي)</option>
                    <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>الاسم (ي-أ)</option>
                    <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>السعر (من الأقل)</option>
                    <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>السعر (من الأعلى)</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> بحث
                </button>
            </div>
        </form>
    </div>
    
    <!-- Foods Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><i class="fas fa-list"></i> قائمة الأطباق</h5>
            <a href="add-food.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة طبق جديد
            </a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الصورة</th>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th>السعر</th>
                        <th>التصنيف</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($foods) > 0): ?>
                        <?php foreach($foods as $food): ?>
                        <tr>
                            <td><?php echo $food['id']; ?></td>
                            <td>
                                <?php if(!empty($food['image']) && file_exists("../uploads/foods/" . $food['image'])): ?>
                                <img src="../uploads/foods/<?php echo htmlspecialchars($food['image']); ?>" 
                                     class="food-img">
                                <?php else: ?>
                                <div class="food-img bg-secondary d-flex align-items-center justify-content-center text-white">
                                    <i class="fas fa-image"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?php echo htmlspecialchars($food['name']); ?></td>
                            <td><?php echo htmlspecialchars(mb_substr($food['description'] ?? '', 0, 50)) . '...'; ?></td>
                            <td><span class="price-badge"><?php echo number_format($food['price'], 0); ?> DA</span></td>
                            <td>
                                <?php if(!empty($food['category'])): ?>
                                <span class="badge bg-info"><?php echo htmlspecialchars($food['category']); ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit-food.php?id=<?php echo $food['id']; ?>" class="btn btn-sm btn-warning btn-action">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0)" 
                                   onclick="if(confirm('هل أنت متأكد من حذف هذا الطبق؟')) window.location.href='delete-food.php?id=<?php echo $food['id']; ?>'"
                                   class="btn btn-sm btn-danger btn-action">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-utensils"></i>
                                    <h5>لا توجد أطباق</h5>
                                    <a href="add-food.php" class="btn btn-primary mt-3">إضافة طبق جديد</a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<script>
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    
    if(menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside
    document.addEventListener('click', function(event) {
        if(window.innerWidth <= 768) {
            if(sidebar && menuToggle) {
                if(!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        }
    });
</script>

</body>
</html>