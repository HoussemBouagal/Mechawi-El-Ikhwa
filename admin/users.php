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

// التحقق من أن المستخدم الحالي هو مسؤول (admin) فقط يمكنه الوصول
$current_admin_id = $_SESSION['admin'];
$current_admin_role = $_SESSION['admin_role'] ?? 'editor';

if($current_admin_role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// إضافة مستخدم جديد
if(isset($_POST['add'])){
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'] ?? 'editor';
    
    // التحقق من صحة البيانات
    if(empty($username)) {
        $error = "يرجى إدخال اسم المستخدم";
    } elseif(empty($password)) {
        $error = "يرجى إدخال كلمة المرور";
    } elseif($password !== $confirm_password) {
        $error = "كلمة المرور غير متطابقة";
    } elseif(strlen($password) < 6) {
        $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    } else {
        try {
            // التحقق من عدم وجود نفس اسم المستخدم
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            
            if($stmt->fetch()) {
                $error = "اسم المستخدم موجود بالفعل";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO admins (username, password, role, created_at) 
                    VALUES (?, ?, ?, NOW())
                ");
                
                if($stmt->execute([$username, $hashed_password, $role])) {
                    $success = "تم إضافة المستخدم بنجاح!";
                } else {
                    $error = "حدث خطأ أثناء إضافة المستخدم";
                }
            }
        } catch(PDOException $e) {
            error_log("Add user error: " . $e->getMessage());
            $error = "حدث خطأ في قاعدة البيانات";
        }
    }
}

// تحديث كلمة المرور
if(isset($_POST['update_password'])){
    $user_id = intval($_POST['user_id']);
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    
    if(empty($new_password)) {
        $error = "يرجى إدخال كلمة المرور الجديدة";
    } elseif($new_password !== $confirm_new_password) {
        $error = "كلمة المرور غير متطابقة";
    } elseif(strlen($new_password) < 6) {
        $error = "كلمة المرور يجب أن تكون 6 أحرف على الأقل";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            
            if($stmt->execute([$hashed_password, $user_id])) {
                $success = "تم تحديث كلمة المرور بنجاح!";
            } else {
                $error = "حدث خطأ أثناء تحديث كلمة المرور";
            }
        } catch(PDOException $e) {
            error_log("Update password error: " . $e->getMessage());
            $error = "حدث خطأ في قاعدة البيانات";
        }
    }
}

// حذف مستخدم (مع منع حذف المسؤول الرئيسي)
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    
    if($id == 1) {
        $error = "لا يمكن حذف المسؤول الرئيسي";
    } elseif($id == $_SESSION['admin']) {
        $error = "لا يمكن حذف حسابك الحالي";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
            
            if($stmt->execute([$id])) {
                $success = "تم حذف المستخدم بنجاح!";
                header("Location: users.php?success=deleted");
                exit();
            } else {
                $error = "حدث خطأ أثناء حذف المستخدم";
            }
        } catch(PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            $error = "حدث خطأ في قاعدة البيانات";
        }
    }
}

// جلب المستخدمين
try {
    $users = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
} catch(PDOException $e) {
    error_log("Fetch users error: " . $e->getMessage());
    $users = [];
}

// إحصائيات
$total_users = count($users);
$admin_count = 0;
$editor_count = 0;

foreach($users as $user) {
    if(($user['role'] ?? 'editor') == 'admin') $admin_count++;
    else $editor_count++;
}

// اسم المسؤول
$admin_name = $_SESSION['admin_name'] ?? 'مسؤول';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين | مشاوي الإخوة</title>

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
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
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
        .stat-icon.warning { background: linear-gradient(135deg, #f093fb, #f5576c); color: white; }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--dark);
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        /* Cards */
        .form-card, .table-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .card-title i {
            color: var(--primary);
            margin-left: 10px;
        }
        
        /* Form Controls */
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
        
        /* Role Badge */
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-admin { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; }
        .role-editor { background: #e9ecef; color: var(--dark); }
        
        /* Table */
        .table-custom {
            width: 100%;
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
        
        /* Buttons */
        .btn-action {
            padding: 6px 12px;
            margin: 2px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
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
        
        /* Modal */
        .modal-content {
            border-radius: 20px;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 20px 20px 0 0;
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
            .sidebar, .menu-toggle, .btn-action, .top-navbar .admin-avatar, .stat-card, .form-card, .modal {
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
        <li><a href="offers.php"><i class="fas fa-tags"></i> العروض</a></li>
        <li><a href="users.php" class="active"><i class="fas fa-users"></i> المستخدمين</a></li>
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
            <p class="text-muted mb-0">إدارة المسؤولين والموظفين</p>
        </div>
        <div class="admin-avatar">
            <i class="fas fa-user"></i>
        </div>
    </div>
    
    <!-- Error Alert -->
    <?php if($error): ?>
    <div class="alert-custom alert-danger-custom">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Success Alert -->
    <?php if($success || (isset($_GET['success']) && $_GET['success'] == 'deleted')): ?>
    <div class="alert-custom alert-success-custom">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success ?: 'تم حذف المستخدم بنجاح!'; ?>
    </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $total_users; ?></div>
                <div class="stat-label">إجمالي المستخدمين</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-crown"></i>
                </div>
                <div class="stat-number"><?php echo $admin_count; ?></div>
                <div class="stat-label">مسؤولين</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-user-edit"></i>
                </div>
                <div class="stat-number"><?php echo $editor_count; ?></div>
                <div class="stat-label">محررين</div>
            </div>
        </div>
    </div>
    
    <!-- Add User Form -->
    <div class="form-card">
        <h5 class="card-title">
            <i class="fas fa-user-plus"></i> إضافة مستخدم جديد
        </h5>
        
        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">اسم المستخدم</label>
                <input type="text" 
                       name="username" 
                       class="form-control form-control-custom" 
                       placeholder="أدخل اسم المستخدم"
                       required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">كلمة المرور</label>
                <input type="password" 
                       name="password" 
                       class="form-control form-control-custom" 
                       placeholder="********"
                       required>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">تأكيد كلمة المرور</label>
                <input type="password" 
                       name="confirm_password" 
                       class="form-control form-control-custom" 
                       placeholder="********"
                       required>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">الدور</label>
                <select name="role" class="form-select form-select-custom">
                    <option value="editor">محرر</option>
                    <option value="admin">مسؤول</option>
                </select>
            </div>
            
            <div class="col-12">
                <button name="add" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> إضافة مستخدم
                </button>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="table-card">
        <h5 class="card-title">
            <i class="fas fa-list"></i> قائمة المستخدمين
        </h5>
        
        <div class="table-responsive">
            <table class="table-custom table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>اسم المستخدم</th>
                        <th>الدور</th>
                        <th>تاريخ التسجيل</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($user['id']); ?>
                            <?php if($user['id'] == 1): ?>
                                <span class="badge bg-warning ms-1">رئيسي</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="fas fa-user-circle text-muted me-2"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if($user['id'] == $_SESSION['admin']): ?>
                                <span class="badge bg-info ms-1">أنت</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="role-badge role-<?php echo $user['role'] ?? 'editor'; ?>">
                                <?php echo ($user['role'] ?? 'editor') == 'admin' ? 'مسؤول' : 'محرر'; ?>
                            </span>
                        </td>
                        <td>
                            <i class="fas fa-calendar-alt text-muted"></i>
                            <?php echo date('Y-m-d', strtotime($user['created_at'] ?? 'now')); ?>
                        </td>
                        <td>
                            <!-- Edit Password Button -->
                            <button type="button" 
                                    class="btn btn-warning btn-action" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#passwordModal"
                                    data-user-id="<?php echo $user['id']; ?>"
                                    data-user-name="<?php echo htmlspecialchars($user['username']); ?>">
                                <i class="fas fa-key"></i>
                            </button>
                            
                            <!-- Delete Button -->
                            <?php if($user['id'] != 1 && $user['id'] != $_SESSION['admin']): ?>
                            <a href="users.php?delete=<?php echo $user['id']; ?>" 
                               class="btn btn-danger btn-action"
                               onclick="return confirm('هل أنت متأكد من حذف المستخدم &quot;<?php echo htmlspecialchars($user['username']); ?>&quot;؟')">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<!-- Update Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">
                    <i class="fas fa-key"></i> تغيير كلمة المرور
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="modal_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">المستخدم</label>
                        <div class="alert alert-info">
                            <strong><span id="modal_user_name"></span></strong>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور الجديدة</label>
                        <input type="password" 
                               name="new_password" 
                               class="form-control form-control-custom" 
                               placeholder="أدخل كلمة المرور الجديدة"
                               required>
                        <div class="info-text text-muted mt-1">
                            <small>يجب أن تكون 6 أحرف على الأقل</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" 
                               name="confirm_new_password" 
                               class="form-control form-control-custom" 
                               placeholder="أعد إدخال كلمة المرور"
                               required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> إلغاء
                    </button>
                    <button type="submit" name="update_password" class="btn btn-primary">
                        <i class="fas fa-save"></i> تحديث كلمة المرور
                    </button>
                </div>
            </form>
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
    
    // Handle password modal data
    const passwordModal = document.getElementById('passwordModal');
    if(passwordModal) {
        passwordModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            const userName = button.getAttribute('data-user-name');
            
            const modalUserId = document.getElementById('modal_user_id');
            const modalUserName = document.getElementById('modal_user_name');
            
            if(modalUserId) modalUserId.value = userId;
            if(modalUserName) modalUserName.textContent = userName;
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
    
    // Tooltips initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
</script>

</body>
</html>