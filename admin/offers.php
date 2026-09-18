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

// معالجة البحث والتصفية
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'id_desc';

// بناء استعلام SQL
$sql = "
    SELECT offers.*, foods.name as food_name, foods.price as original_price, foods.image
    FROM offers
    JOIN foods ON offers.food_id = foods.id
    WHERE 1=1
";
$params = [];

// فلترة البحث
if(!empty($search)) {
    $sql .= " AND foods.name LIKE :search";
    $params[':search'] = "%$search%";
}

// فلترة حسب الحالة
if($status == 'active') {
    $sql .= " AND offers.end_date >= CURDATE() AND offers.start_date <= CURDATE()";
} elseif($status == 'expired') {
    $sql .= " AND offers.end_date < CURDATE()";
} elseif($status == 'upcoming') {
    $sql .= " AND offers.start_date > CURDATE()";
}

// ترتيب النتائج - التحقق من صحة المدخلات
$allowed_sorts = ['id_desc', 'discount_desc', 'discount_asc', 'date_asc', 'date_desc'];
if(in_array($sort, $allowed_sorts)) {
    switch($sort) {
        case 'discount_desc':
            $sql .= " ORDER BY offers.discount DESC";
            break;
        case 'discount_asc':
            $sql .= " ORDER BY offers.discount ASC";
            break;
        case 'date_asc':
            $sql .= " ORDER BY offers.end_date ASC";
            break;
        case 'date_desc':
            $sql .= " ORDER BY offers.end_date DESC";
            break;
        default:
            $sql .= " ORDER BY offers.id DESC";
    }
} else {
    $sql .= " ORDER BY offers.id DESC";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $offers = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Offers query error: " . $e->getMessage());
    $offers = [];
}

// إحصائيات سريعة
$total_offers = count($offers);
$active_offers = 0;
$expired_offers = 0;
$upcoming_offers = 0;
$today = date('Y-m-d');

foreach($offers as $offer) {
    if($offer['end_date'] >= $today && $offer['start_date'] <= $today) {
        $active_offers++;
    } elseif($offer['end_date'] < $today) {
        $expired_offers++;
    } elseif($offer['start_date'] > $today) {
        $upcoming_offers++;
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
    <title>إدارة العروض | مشاوي الإخوة</title>

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
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
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
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            height: 100%;
            cursor: pointer;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.active { border-bottom: 3px solid var(--success); }
        .stat-card.expired { border-bottom: 3px solid var(--danger); }
        .stat-card.upcoming { border-bottom: 3px solid var(--info); }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.5rem;
        }
        
        .stat-icon.primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, #11998e, #38ef7d); color: white; }
        .stat-icon.danger { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, #4facfe, #00f2fe); color: white; }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
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
        
        .table-custom {
            width: 100%;
            min-width: 800px;
        }
        
        .table-custom th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 700;
            color: var(--dark);
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table-custom td {
            padding: 15px;
            vertical-align: middle;
        }
        
        .food-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        /* Discount Badge */
        .discount-badge {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 15px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            text-align: center;
        }
        
        .discount-badge.high { background: linear-gradient(135deg, #dc3545, #ff9800); }
        .discount-badge.medium { background: linear-gradient(135deg, #ff9800, #ffc107); }
        .discount-badge.low { background: linear-gradient(135deg, #28a745, #20c997); }
        
        /* Status Badge */
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active { background: #d4edda; color: #155724; }
        .status-expired { background: #f8d7da; color: #721c24; }
        .status-upcoming { background: #d1ecf1; color: #0c5460; }
        
        /* Price Section */
        .price-compare {
            display: flex;
            flex-direction: column;
        }
        
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.85rem;
        }
        
        .new-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Action Buttons */
        .btn-action {
            padding: 6px 12px;
            margin: 2px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
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
            .stat-number {
                font-size: 1.3rem;
            }
        }
        
        @media print {
            .sidebar, .menu-toggle, .btn-action, .search-section, .top-navbar .admin-avatar, .stat-card, .table-container .d-flex .btn {
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
            <p class="text-muted mb-0">إدارة العروض والخصومات على الأطباق</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card <?php echo $status == 'all' ? 'active' : ''; ?>" onclick="window.location.href='?status=all'">
                <div class="stat-icon primary">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-number"><?php echo $total_offers; ?></div>
                <div class="stat-label">جميع العروض</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card <?php echo $status == 'active' ? 'active' : ''; ?>" onclick="window.location.href='?status=active'">
                <div class="stat-icon success">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stat-number"><?php echo $active_offers; ?></div>
                <div class="stat-label">عروض نشطة</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card <?php echo $status == 'expired' ? 'expired' : ''; ?>" onclick="window.location.href='?status=expired'">
                <div class="stat-icon danger">
                    <i class="fas fa-hourglass-end"></i>
                </div>
                <div class="stat-number"><?php echo $expired_offers; ?></div>
                <div class="stat-label">عروض منتهية</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card <?php echo $status == 'upcoming' ? 'upcoming' : ''; ?>" onclick="window.location.href='?status=upcoming'">
                <div class="stat-icon info">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number"><?php echo $upcoming_offers; ?></div>
                <div class="stat-label">عروض قادمة</div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="search-section">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" 
                           name="search" 
                           class="form-control border-start-0" 
                           placeholder="بحث باسم الطبق..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="id_desc" <?php echo $sort == 'id_desc' ? 'selected' : ''; ?>>الأحدث أولاً</option>
                    <option value="discount_desc" <?php echo $sort == 'discount_desc' ? 'selected' : ''; ?>>أعلى خصم أولاً</option>
                    <option value="discount_asc" <?php echo $sort == 'discount_asc' ? 'selected' : ''; ?>>أقل خصم أولاً</option>
                    <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>الأقرب انتهاءً</option>
                    <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>الأبعد انتهاءً</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> تصفية
                </button>
            </div>
            <?php if(!empty($search) || $status != 'all'): ?>
            <div class="col-12">
                <a href="offers.php" class="btn btn-sm btn-secondary">
                    <i class="fas fa-times"></i> إلغاء التصفية
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Offers Table -->
    <div class="table-container">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="mb-0">
                <i class="fas fa-list"></i> قائمة العروض
                <span class="badge bg-secondary ms-2"><?php echo $total_offers; ?></span>
            </h5>
            <div>
                <button onclick="window.print()" class="btn btn-sm btn-secondary">
                    <i class="fas fa-print"></i> طباعة
                </button>
                <a href="add-offer.php" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> إضافة عرض
                </a>
                <a href="offers.php" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-sync-alt"></i> تحديث
                </a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table-custom table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>الطبق</th>
                        <th>الخصم</th>
                        <th>السعر</th>
                        <th>مدة العرض</th>
                        <th>الحالة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($offers) > 0): ?>
                        <?php foreach($offers as $offer): 
                            $is_active = ($offer['end_date'] >= $today && $offer['start_date'] <= $today);
                            $is_expired = ($offer['end_date'] < $today);
                            $is_upcoming = ($offer['start_date'] > $today);
                            
                            $new_price = $offer['original_price'] - ($offer['original_price'] * $offer['discount'] / 100);
                            
                            $discount_class = 'low';
                            if($offer['discount'] >= 40) $discount_class = 'high';
                            elseif($offer['discount'] >= 20) $discount_class = 'medium';
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offer['id']); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php 
                                    $image_path = '../uploads/foods/' . htmlspecialchars($offer['image']);
                                    if(!empty($offer['image']) && file_exists($image_path)):
                                    ?>
                                    <img src="<?php echo $image_path; ?>" 
                                         alt="<?php echo htmlspecialchars($offer['food_name']); ?>"
                                         class="food-img">
                                    <?php else: ?>
                                    <div class="food-img bg-secondary d-flex align-items-center justify-content-center text-white">
                                        <i class="fas fa-image"></i>
                                    </div>
                                    <?php endif; ?>
                                    <span class="fw-bold"><?php echo htmlspecialchars($offer['food_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="discount-badge <?php echo $discount_class; ?>">
                                    <i class="fas fa-percent"></i> <?php echo $offer['discount']; ?>%
                                </div>
                            </td>
                            <td>
                                <div class="price-compare">
                                    <span class="old-price"><?php echo number_format($offer['original_price'], 0); ?> DA</span>
                                    <span class="new-price"><?php echo number_format($new_price, 0); ?> DA</span>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <i class="fas fa-calendar-alt text-muted"></i>
                                    <?php echo date('Y-m-d', strtotime($offer['start_date'])); ?><br>
                                    <i class="fas fa-calendar-check text-muted"></i>
                                    <?php echo date('Y-m-d', strtotime($offer['end_date'])); ?>
                                </div>
                            </td>
                            <td>
                                <?php if($is_active): ?>
                                <span class="status-badge status-active">
                                    <i class="fas fa-check-circle"></i> نشط
                                </span>
                                <?php elseif($is_expired): ?>
                                <span class="status-badge status-expired">
                                    <i class="fas fa-times-circle"></i> منتهي
                                </span>
                                <?php else: ?>
                                <span class="status-badge status-upcoming">
                                    <i class="fas fa-clock"></i> قادم
                                </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit-offer.php?id=<?php echo $offer['id']; ?>" 
                                   class="btn btn-warning btn-action"
                                   title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="javascript:void(0)" 
                                   onclick="if(confirm('هل أنت متأكد من حذف هذا العرض؟')) window.location.href='delete-offer.php?id=<?php echo $offer['id']; ?>'"
                                   class="btn btn-danger btn-action"
                                   title="حذف">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-tags"></i>
                                    <h5>لا توجد عروض</h5>
                                    <p class="text-muted">لم يتم العثور على عروض مطابقة لبحثك</p>
                                    <a href="add-offer.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> إضافة عرض جديد
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<!-- Scripts -->
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
    
    // Tooltips initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>

</body>
</html>