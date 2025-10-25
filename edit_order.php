<?php
session_start();
require 'config.php';
// ให้ admin และ officer เข้าได้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'officer'])) {
    header("Location: login.php");
    exit;
}
$id = intval($_GET['id']);
$error = '';

// ดึงข้อมูล order เดิม
$result = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $result->fetch_assoc();
if (!$order) {
    echo "ไม่พบข้อมูล"; exit;
}

// ดึง user ทั้งหมดสำหรับ dropdown
$user_options = [];
$users_res = $conn->query("SELECT id, username FROM users ORDER BY username ASC");
while ($u = $users_res->fetch_assoc()) {
    $user_options[] = $u;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $order_number = isset($_POST['order_number']) ? $conn->real_escape_string($_POST['order_number']) : '';
    $order_type = isset($_POST['order_type']) ? $conn->real_escape_string($_POST['order_type']) : '';
    $order_name = isset($_POST['order_name']) ? $conn->real_escape_string($_POST['order_name']) : '';
    $order_role = isset($_POST['order_role']) ? $conn->real_escape_string($_POST['order_role']) : '';
    $user_id_edit = isset($_POST['user_id']) ? intval($_POST['user_id']) : $order['user_id'];
    
    if (!is_numeric($order_number)) {
        $error = "เลขที่คำสั่งต้องเป็นตัวเลขเท่านั้น";
    }

    // อัปโหลดไฟล์ใหม่ ถ้ามี
    if (empty($error)) {
        if (isset($_FILES['order_download']) && $_FILES['order_download']['error'] == UPLOAD_ERR_OK && $_FILES['order_download']['size'] > 0) {
            $imgName = time() . '_' . basename($_FILES['order_download']['name']);
            $target = "uploads/" . $imgName;
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $ext = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                move_uploaded_file($_FILES['order_download']['tmp_name'], $target);
                $conn->query("UPDATE orders SET order_download='$imgName' WHERE id=$id");
            } else {
                $error = "อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif, pdf";
            }
        }
    }


    if (empty($error)) {
        // ปรับข้อมูลที่แก้ไข
        $conn->query("UPDATE orders SET 
            order_number='$order_number',
            user_id=$user_id_edit,
            order_type='$order_type',
            order_name='$order_name',
            order_role='$order_role'
            WHERE id=$id
        ");
        header("Location: orders.php");
        exit;
    }
    // ดึงข้อมูลใหม่มาแสดงหลังแก้ไข
    $result = $conn->query("SELECT * FROM orders WHERE id=$id");
    $order = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>แก้ไขคำสั่ง</title>
    <link rel="stylesheet" href="assets-style.css">
    <style>
        .order-img { max-width: 180px; max-height: 140px; border-radius: 12px; border:2px solid #ff6600; }
        .edit-form { max-width: 400px; margin: 0 auto; }
        label { font-weight:600; display:block; margin-top:12px;}
        input, select { width:100%; padding:9px; border-radius:7px; border:1.2px solid #ffa07a; margin-bottom:10px;}
        .error { color: #d32f2f; margin-top: 12px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="header">
        <h1>แก้ไขรูปภาพคำสั่ง</h1>
    </div>
    <div class="info">
        <form method="post" enctype="multipart/form-data" class="edit-form">
            
            <label>เปลี่ยนไฟล์ (ถ้าต้องการ)</label>
            <input type="file" name="order_download" accept="image/*,.pdf"><br>

            <label>เลขที่คำสั่ง:</label>
            <input type="text" name="order_number" value="<?= htmlspecialchars($order['order_number'] ?? '') ?>" required inputmode="numeric" oninput="this.value = this.value.replace(/[^0-9]/g, '');">

            <label>ชื่อผู้ใช้:</label>
            <select name="user_id" required>
                <option value="">-- เลือกผู้รับ --</option>
                <?php foreach ($user_options as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= ($order['user_id']==$u['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($u['username']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>ประเภทคำสั่ง:</label>
            <select name="order_type" required>
                <option value="">-- เลือกประเภท --</option>
                <option value="คำสั่งภายใน" <?= ($order['order_type']=='คำสั่งภายใน')?'selected':'' ?>>คำสั่งภายใน</option>
                <option value="คำสั่งภายนอก" <?= ($order['order_type']=='คำสั่งภายนอก')?'selected':'' ?>>คำสั่งภายนอก</option>
            </select>

            <label>ชื่อคำสั่ง:</label>
            <input type="text" name="order_name" value="<?= htmlspecialchars($order['order_name'] ?? '') ?>" required>

            <label>หน้าที่คำสั่ง:</label>
            <input type="text" name="order_role" value="<?= htmlspecialchars($order['order_role'] ?? '') ?>" required>

            <button type="submit">บันทึก</button>
        </form>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <a href="orders.php" class="back-link">กลับไป</a>
    </div>
</body>
</html>