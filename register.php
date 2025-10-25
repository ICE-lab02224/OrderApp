<?php
session_start();
require 'config.php';
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าและป้องกัน SQL Injection
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);
    $confirm_password = $conn->real_escape_string($_POST['confirm_password']);
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $lecturer_id = $conn->real_escape_string($_POST['lecturer_id']);
    $group = $conn->real_escape_string($_POST['group']);

    // --- การตรวจสอบข้อมูล (Validation) ---
    if ($password !== $confirm_password) {
        $error = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
    } else {
        // ตรวจสอบว่ามี username นี้ในระบบแล้วหรือยัง
        $check_user_q = $conn->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_user_q->num_rows > 0) {
            $error = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว กรุณาใช้ชื่ออื่น";
        } else {
            // --- การเข้ารหัสรหัสผ่านที่ปลอดภัย (ใช้ password_hash แทน MD5) ---
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // --- เตรียมคำสั่ง SQL เพื่อบันทึกข้อมูล ---
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, lecturer_id, `group`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $lecturer_id, $group);

            if ($stmt->execute()) {
                // หากบันทึกสำเร็จ ให้ส่งข้อความไปแสดงที่หน้า login
                $_SESSION['register_success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                header("Location: login.php");
                exit;
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>สมัครสมาชิก - PKRU</title>
    <link rel="stylesheet" href="assets-style.css">
    <style>
        body { background: linear-gradient(135deg, #fff 0%, #fffbe7 60%, #ff6600 100%); min-height: 100vh; }
        .login-container { max-width: 400px; margin: 50px auto; background: #fff; border-radius: 22px; box-shadow: 0 6px 32px #ff660044; padding: 42px 32px; text-align: center; }
        .login-container h2 { color: #ff6600; margin-bottom: 22px; font-size: 1.7em; }
        .login-container input { width: 88%; padding: 12px; margin: 8px 0; font-size: 1.05em; border-radius: 10px; border: 1.5px solid #ff6600; background: #fffaf6; }
        .login-container button { background: linear-gradient(90deg, #ff6600 80%, #222 100%); color: #fff; font-size: 1.08em; font-weight: bold; border: none; border-radius: 10px; padding: 13px 38px; margin-top: 15px; cursor: pointer; }
        .login-container button:hover { background: linear-gradient(90deg, #222 80%, #ff6600 100%); color: #ff6600; }
        .error { color: #d32f2f; margin-top: 15px; font-weight: 600; }
        .success { color: #28a745; margin-top: 15px; font-weight: 600; }
        .login-links { margin-top: 20px; }
        .login-links a { color: #ff6600; text-decoration: none; margin: 0 10px; font-weight: 600; }
        .login-links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>สมัครสมาชิกสำหรับผู้ใช้</h2>
        <form method="post">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้ (Username)" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required>
            <input type="text" name="fullname" placeholder="ชื่อ-นามสกุล" required>
            <input type="text" name="lecturer_id" placeholder="รหัสประจำตัว" required>
            <input type="text" name="group" placeholder="คณะ" required>
            <button type="submit">สมัครสมาชิก</button>
        </form>
        <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <div class="login-links">
            <a href="login.php">กลับไปหน้าเข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>