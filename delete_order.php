<?php
session_start();
require 'config.php';
// ให้ admin และ officer เข้าได้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'officer'])) {
    header("Location: login.php");
    exit;
}
$order_id = intval($_GET['id']);

// ดึงชื่อไฟล์ก่อนลบ (ถ้ามี)
$result = $conn->query("SELECT order_download FROM orders WHERE id=$order_id");
$order = $result->fetch_assoc();
if ($order && $order['order_download']) {
    $file_path = "uploads/" . $order['order_download'];
    if (file_exists($file_path)) {
        unlink($file_path); // ลบไฟล์
    }
}

// ลบข้อมูลในฐานข้อมูล
$conn->query("DELETE FROM orders WHERE id=$order_id");

header("Location: orders.php");
exit;
?>