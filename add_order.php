<?php
session_start();
require 'config.php';

// ให้ทั้ง 3 type เข้าได้
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'user', 'officer'])) {
    header("Location: login.php");
    exit;
}
$error = "";
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// ดึง user สำหรับ admin/officer เท่านั้น
$user_options = [];
if ($user_type === 'admin' || $user_type === 'officer') {
    $users_res = $conn->query("SELECT id, fullname FROM users ORDER BY fullname ASC");
    while ($u = $users_res->fetch_assoc()) {
        $user_options[] = $u;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับผู้รับคำสั่งเป็น array (checkbox)
    $target_user_ids = isset($_POST['target_user_ids']) ? $_POST['target_user_ids'] : [];
    $order_type = isset($_POST['order_type']) ? $conn->real_escape_string($_POST['order_type']) : '';
    $order_number = isset($_POST['order_number']) ? $conn->real_escape_string($_POST['order_number']) : '';
    $order_name = isset($_POST['order_name']) ? $conn->real_escape_string($_POST['order_name']) : '';
    $order_role = isset($_POST['order_role']) ? $conn->real_escape_string($_POST['order_role']) : '';
    $imgName = '';

    // --- จุดที่แก้ไข 1: เพิ่มการตรวจสอบว่าเป็นตัวเลขหรือไม่ ---
    if (!is_numeric($order_number)) {
        $error = "เลขที่คำสั่งต้องเป็นตัวเลขเท่านั้น";
    }

    // อัปโหลดไฟล์ (ส่งไฟล์เดียวให้ทุกคน)
    if (empty($error)) { // ตรวจสอบว่ายังไม่มี error ก่อนอัปโหลด
        if (isset($_FILES['order_download']) && $_FILES['order_download']['error'] == UPLOAD_ERR_OK && $_FILES['order_download']['size'] > 0) {
            $imgName = time() . '_' . basename($_FILES['order_download']['name']);
            $target = "uploads/" . $imgName;
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            $ext = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                move_uploaded_file($_FILES['order_download']['tmp_name'], $target);
            } else {
                $error = "อนุญาตเฉพาะไฟล์ jpg, jpeg, png, gif, pdf";
            }
        } else {
            $error = "กรุณาอัปโหลดไฟล์";
        }
    }


    // Insert orders สำหรับผู้รับที่เลือกทั้งหมด
    if (empty($error) && $imgName !== '' && !empty($target_user_ids)) {
        foreach ($target_user_ids as $target_user_id) {
           $stmt = $conn->prepare("INSERT INTO orders (user_id, order_download, order_type, order_number, order_name, order_role, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
            $stmt->bind_param("isssss", 
                $target_user_id, $imgName, $order_type, 
                $order_number, $order_name, $order_role
            );
            $stmt->execute();
        }
        header("Location: orders.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>เพิ่มคำสั่งใหม่</title>
    <link rel="stylesheet" href="assets-style.css">
    <style>
        /* CSS styles remain the same */
        body {
            background: linear-gradient(135deg, #fff 0%, #fffbe7 60%, #ff6600 100%);
            min-height: 100vh;
        }
        .header {
            display: flex;
            align-items: center;
            background: #ff6600;
            color: #fff;
            padding: 18px 32px 18px 24px;
            border-radius: 0 0 22px 22px;
            margin-bottom: 16px;
            box-shadow: 0 4px 16px #2228;
        }
        .logo-left {
            width: 70px;
            height: 70px;
            object-fit: cover;
            margin-right: 28px;
            box-shadow: 0 2px 8px #fff7;
            background: #fff;
            border-radius: 50%;
            border: 5px solid #222;
        }
        .header h1 {
            font-size: 2em;
            font-weight: 700;
            margin: 0;
            text-shadow: 1px 3px 10px #ff660088;
            letter-spacing: 2px;
        }
        .container {
            max-width: 480px;
            margin: 42px auto 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 4px 24px #ff660044;
            padding: 44px 36px 36px 36px;
        }
        h2 {
            color: #ff6600;
            text-align: center;
            font-size: 2em;
            margin-bottom: 14px;
            letter-spacing: 1px;
        }
        label {
            font-weight: 600;
            color: #222;
            margin-bottom: 7px;
            display: block;
        }
        input[type="file"], input[type="text"], select, textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            font-size: 1.07em;
            border-radius: 10px;
            border: 1.5px solid #ff6600;
            background: #fffaf6;
        }
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        button {
            background: linear-gradient(90deg, #ff6600 80%, #222 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1.09em;
            border: none;
            border-radius: 10px;
            padding: 13px 38px;
            cursor: pointer;
            box-shadow: 0 2px 8px #ff660044;
            margin-top: 10px;
            transition: background .2s, color .2s;
        }
        button:hover {
            background: linear-gradient(90deg, #222 80%, #ff6600 100%);
            color: #ff6600;
        }
        .back-link {
            display: inline-block;
            margin-top: 18px;
            background: #222;
            color: #ff6600;
            padding: 8px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 12px #ff660044;
            transition: background .2s;
        }
        .back-link:hover {
            background: #ff6600;
            color: #fff;
        }
        .error {
            color: #d32f2f;
            margin-top: 12px;
            text-align: center;
            font-weight: 600;
        }
        .or-sep {
            text-align: center;
            margin: 12px 0 10px 0;
            color: #888;
        }
        .form-title {
            font-size: 1.09em;
            margin-bottom: 16px;
            color: #ff6600;
            text-align: center;
        }
        .search-user-form {
            max-width: 480px;
            margin: 0 auto 18px auto;
            background: #fffbe7;
            padding: 14px 20px 10px 20px;
            border-radius: 14px;
            box-shadow: 0 2px 12px #ff660055;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-user-form input {
            border: 1.5px solid #ff6600;
            border-radius: 8px;
            padding: 9px;
            font-size: 1em;
            flex:1;
        }
        .search-user-form button {
            background: #ff6600;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 28px;
            font-weight: 600;
            box-shadow: 0 2px 8px #ff660044;
            transition: background .2s;
            cursor: pointer;
        }
        .search-user-form button:hover {
            background: #fffbe7;
            color: #ff6600;
        }
        @media (max-width: 700px) {
            .container, .search-user-form { max-width: 97vw; padding: 2vw;}
            .header { padding: 18px 12px;}
        }
    </style>
    <script>
        // ฟังก์ชันค้นหาผู้รับในกล่อง
        function filterUserList() {
            var input = document.getElementById('userSearch').value.toLowerCase();
            var users = document.querySelectorAll('.user-list-box label');
            users.forEach(function(lbl){
                if (lbl.textContent.toLowerCase().indexOf(input) !== -1) {
                    lbl.style.display = '';
                } else {
                    lbl.style.display = 'none';
                }
            });
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>เพิ่มคำสั่งใหม่</h1>
    </div>
    <div class="container">
        <h2>เพิ่มคำสั่งใหม่</h2>
        <form method="post" enctype="multipart/form-data">
            <div class="form-title">กรอกข้อมูลสำหรับเพิ่มคำสั่ง</div>
            <?php if ($user_type === 'admin' || $user_type === 'officer'): ?>
            <label>เลือกผู้รับคำสั่ง (เลือกได้หลายคน):</label>
            <input type="text" id="userSearch" class="user-search-input" onkeyup="filterUserList()" placeholder="ค้นหาผู้รับ...">
            <div class="user-list-box">
                <?php foreach ($user_options as $u): ?>
                    <label>
                        <input type="checkbox" name="target_user_ids[]" value="<?= $u['id'] ?>">
                        <?= htmlspecialchars($u['fullname']) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <input type="hidden" name="target_user_ids[]" value="<?= $user_id ?>">
            <label>คุณจะส่งคำสั่งหา <b><?= htmlspecialchars($_SESSION['fullname'] ?? '') ?></b>  เท่านั้น</label>
            <?php endif; ?>

            <label>ประเภทคำสั่ง:</label>
            <select name="order_type" required>
                <option value=""> เลือกประเภท </option>
                <option value="คำสั่งภายใน">คำสั่งภายใน</option>
                <option value="คำสั่งภายนอก">คำสั่งภายนอก</option>
            </select>

            <label>เลขที่คำสั่ง:</label>
            <input type="text" name="order_number" required inputmode="numeric" placeholder=" โปรดกรอกเลขคำสั่ง    " oninput="this.value = this.value.replace(/[^0-9]/g, '');">


            <label>ชื่อคำสั่ง:</label>
            <input type="text" name="order_name" required placeholder=" กรุณาใส่ชื่อคำสั่ง ">

            <label>หน้าที่คำสั่ง:</label>
            <input type="text" name="order_role" required placeholder=" กรุณาใส่หน้าที่ของคำสั่ง ">

            <label>อัปโหลดไฟล์ PDF:</label>
            <input type="file" name="order_download" accept="image/*,.pdf" required>

            <button type="submit">บันทึก</button>
        </form>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <a href="orders.php" class="back-link">กลับไป</a>
    </div>
</body>
</html>