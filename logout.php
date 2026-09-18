<?php
/**
 * تسجيل الخروج من لوحة التحكم
 * ملف: logout.php
 */

session_start();

// تسجيل وقت تسجيل الخروج في السجلات (للأمان)
if(isset($_SESSION['admin'])) {
    error_log("المسؤول ID: " . $_SESSION['admin'] . " قام بتسجيل الخروج في " . date('Y-m-d H:i:s'));
}

// تنظيف جميع متغيرات الجلسة
$_SESSION = array();

// حذف كوكي الجلسة إذا كان موجوداً
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// تدمير الجلسة بالكامل
session_destroy();

// حذف أي كوكيز أخرى متعلقة بتذكر المستخدم
if(isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// إعادة التوجيه إلى صفحة تسجيل الدخول مع رسالة نجاح
header("Location: login.php?logout=success");
exit();
?>