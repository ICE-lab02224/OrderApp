<?php
session_start();
require 'config.php';

// ตรวจสอบว่าล็อกอินหรือยัง ถ้ายังให้กลับไปหน้า login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$userData = null;
$new_orders_count = 0;

// ดึงข้อมูลผู้ใช้และนับจำนวนแจ้งเตือนตามประเภท
if ($user_type === 'admin') {
    $user_q = $conn->query("SELECT id, username, admin_name AS fullname FROM admin WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    $new_orders_q = $conn->query("SELECT COUNT(*) as count FROM orders WHERE is_read = 0");
    if ($new_orders_q) {
        $new_orders_count = $new_orders_q->fetch_assoc()['count'];
    }
} elseif ($user_type === 'officer') {
    $user_q = $conn->query("SELECT id, username, fullname FROM officers WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    $new_orders_q = $conn->query("SELECT COUNT(*) as count FROM orders WHERE is_read = 0");
    if ($new_orders_q) {
        $new_orders_count = $new_orders_q->fetch_assoc()['count'];
    }
} else { // user
    $user_q = $conn->query("SELECT id, username, fullname, lecturer_id, `group` FROM users WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    
    $new_orders_q = $conn->query("SELECT COUNT(*) as count FROM orders WHERE user_id = $user_id AND is_read = 0");
    if ($new_orders_q) {
        $new_orders_count = $new_orders_q->fetch_assoc()['count'];
    }
}

// ตั้งค่าตัวแปรสำหรับแสดงผล
$username = $userData['username'] ?? 'Unknown User';
$fullname = $userData['fullname'] ?? '';
$lecturer_id = $userData['lecturer_id'] ?? '';
$group = $userData['group'] ?? '';
// อัปเดต session fullname เผื่อมีการเปลี่ยนแปลง
if (!isset($_SESSION['fullname']) || $_SESSION['fullname'] != $fullname) {
    $_SESSION['fullname'] = $fullname;
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PKRU Command System</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">
    <style>
        .notification-bell { position: relative; }
        .badge { position: absolute; top: -5px; right: -10px; padding: 3px 7px; border-radius: 50%; background-color: red; color: white; font-size: 12px; font-weight: bold; }
        .content-box { background-color: #fff; border-radius: 15px; padding: 40px; max-width: 600px; margin: 30px auto; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="header">
        <div class="user-profile-box">
            <h1>
                <span class="user-profile-name"><?= htmlspecialchars($username) ?></span>
                <span style="font-size:0.7em;">
                    <?php
                      if ($user_type === 'admin') echo 'แอดมิน';
                      elseif ($user_type === 'officer') echo 'เจ้าหน้าที่';
                      else echo 'ผู้ใช้';
                    ?>
                </span>
            </h1>
        </div>
    </div>

    <div class="main-menu">
        <a href="add_order.php">เพิ่มคำสั่ง</a>
        
        <?php if ($user_type === 'admin' || $user_type === 'officer'): ?>
            <a href="manage_users.php">จัดการผู้ใช้งาน</a>
        <?php elseif ($user_type === 'user'): ?>
            <a href="edit_self_profile.php">แก้ไขข้อมูลส่วนตัว</a>
        <?php endif; ?>
        
        <a href="orders.php?action=mark_read" class="notification-bell">
            ระบบแจ้งเตือน
            <?php if ($new_orders_count > 0): ?>
                <span class="badge"><?= $new_orders_count ?></span>
            <?php endif; ?>
        </a>

        <a href="logout.php">ออกจากระบบ</a>
    </div>

    <div class="content-box">
        <h2 style="color: #ff6600;">ยินดีต้อนรับคุณ <?= htmlspecialchars($fullname) ?>!</h2> 
        <p style="font-size: 1.1em; margin-bottom: 10px;">
        สวัสดี 
                <?= 
                    ($user_type == 'admin') ? 'ผู้ดูแลระบบ' : 
                    (($user_type == 'officer') ? 'เจ้าหน้าที่' : 'ผู้ใช้งาน'); 
                ?> <br>
        <?php if ($user_type === 'user' && !empty($fullname)): ?>
            <strong>ชื่อ-นามสกุล:</strong> <?= htmlspecialchars($fullname) ?><br>
            <strong>รหัสประจำตัว:</strong> <?= htmlspecialchars($lecturer_id) ?><br>
            <strong>คณะ:</strong> <?= htmlspecialchars($group) ?>
        <?php endif; ?><br>
        
        <br>กรุณาเลือกเมนูที่ต้องการใช้งานด้านบน
        </p>
    </div>

</body>
</html>