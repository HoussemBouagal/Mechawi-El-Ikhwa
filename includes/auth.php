<?php
/**
 * ملف المصادقة والتحقق من الصلاحيات
 * includes/auth.php
 */

// بدء الجلسة إذا لم تكن قد بدأت
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// الحصول على اسم الصفحة الحالية
$current_page = basename($_SERVER['PHP_SELF']);

// التحقق من تسجيل الدخول - استثناء صفحات معينة
$public_pages = ['login.php', 'logout.php', 'hash.php', 'test_session.php'];

if(!isset($_SESSION['admin_id'])) {
    // إذا لم يكن المستخدم مسجل دخول وكان في صفحة محمية (ليست عامة)
    if(!in_array($current_page, $public_pages)) {
        header("Location: ../login.php");
        exit();
    }
}

// تحديث وقت آخر نشاط (إذا كان المستخدم مسجل دخول)
if(isset($_SESSION['admin_id'])) {
    $_SESSION['last_activity'] = time();
}

// دوال مساعدة للتحقق من الصلاحيات
function isAdmin() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'admin';
}

function isEditor() {
    return isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 'editor';
}

function requireAdmin() {
    if(!isAdmin()) {
        header("Location: ../dashboard.php");
        exit();
    }
}

// دالة للتحقق من صلاحية الوصول إلى صفحة معينة
function checkPageAccess($allowed_roles = ['admin']) {
    if(!isset($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
    
    $user_role = $_SESSION['admin_role'] ?? 'editor';
    if(!in_array($user_role, $allowed_roles)) {
        header("Location: ../dashboard.php");
        exit();
    }
}

// دالة لتسجيل الخروج الآمن
function logout() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>