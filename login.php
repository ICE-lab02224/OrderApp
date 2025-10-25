<?php
session_start();
include 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    // 1. Check admin table (ยังใช้ MD5 ถ้าคุณยังไม่ได้เปลี่ยน)
    $admin_sql = "SELECT * FROM admin WHERE username='$username' AND password=MD5('$password')";
    $admin_result = $conn->query($admin_sql);
    if ($admin_result->num_rows == 1) {
        $row = $admin_result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_type'] = 'admin';
        $_SESSION['admin_name'] = $row['admin_name'];
        $_SESSION['admin_code'] = $row['admin_code'];
        header('Location: index.php');
        exit();
    }

    // 2. Check officer table (ยังใช้ MD5 ถ้าคุณยังไม่ได้เปลี่ยน)
    $officer_sql = "SELECT * FROM officers WHERE username='$username' AND password=MD5('$password')";
    $officer_result = $conn->query($officer_sql);
    if ($officer_result->num_rows == 1) {
        $row = $officer_result->fetch_assoc();
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_type'] = 'officer';
        $_SESSION['fullname'] = $row['fullname'];
        header('Location: index.php');
        exit();
    }

    // --- จุดที่แก้ไข 3: อัปเดตการตรวจสอบรหัสผ่านของ users ให้ปลอดภัย ---
    // 3. Check users table (เปลี่ยนมาใช้ password_verify)
    $user_sql = "SELECT * FROM users WHERE username='$username'";
    $user_result = $conn->query($user_sql);
    if ($user_result->num_rows == 1) {
        $row = $user_result->fetch_assoc();
        // ตรวจสอบรหัสผ่านที่ถูก hash ด้วย password_verify
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_type'] = 'user';
            $_SESSION['fullname'] = $row['fullname'];
            $conn->query("INSERT INTO logins (user_id) VALUES ({$row['id']})");
            header('Location: index.php');
            exit();
        }
    }

    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>เข้าสู่ระบบ PKRU</title>
    <link rel="stylesheet" href="assets-style.css">
    <style>
        body { background: linear-gradient(135deg, #fff 0%, #fffbe7 60%, #ff6600 100%); min-height: 100vh; }
        .login-container { max-width: 400px; margin: 80px auto 0 auto; background: #fff; border-radius: 22px; box-shadow: 0 6px 32px #ff660044; padding: 42px 32px 32px 32px; text-align: center; }
        .login-container .logo { width: 90px; height: 90px; margin-bottom: 18px; border-radius: 50%; box-shadow: 0 2px 12px #ff660088; border: 7px solid #222; background: #fff; object-fit: cover; }
        .login-container h2 { color: #ff6600; margin-bottom: 22px; font-size: 1.7em; letter-spacing: 1px; }
        .login-container input[type="text"], .login-container input[type="password"] { width: 88%; padding: 13px; margin: 12px 0; font-size: 1.08em; border-radius: 10px; border: 1.5px solid #ff6600; background: #fffaf6; color: #222; transition: border .2s; }
        .login-container input:focus { border: 2px solid #222; outline: none; }
        .login-container button { background: linear-gradient(90deg, #ff6600 80%, #222 100%); color: #fff; font-size: 1.08em; font-weight: bold; border: none; border-radius: 10px; padding: 13px 38px; margin-top: 18px; cursor: pointer; box-shadow: 0 2px 8px #ff660044; transition: background .2s; }
        .login-container button:hover { background: linear-gradient(90deg, #222 80%, #ff6600 100%); color: #ff6600; }
        .error { color: #d32f2f; margin-top: 17px; font-weight: 600; font-size: 1.09em; }
        .login-title { font-size: 1.18em; color: #222; margin-bottom: 10px; }
        .login-links { margin-top: 25px; font-size: 0.95em; }
        .login-links a { color: #ff6600; text-decoration: none; margin: 0 15px; font-weight: 600; }
        .login-links a:hover { text-decoration: underline; }
        .success { color: #28a745; margin-top: 15px; font-weight: 600; padding: 10px; border: 1px solid #28a745; border-radius: 8px; background-color: #eaf6ec;}
        @media (max-width: 500px) { .login-container { padding: 8vw 4vw; } .login-container .logo { width: 68px; height: 68px;} }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="img/pkru logo.png" width="130" height="150" alt="รูปจากเว็บอื่น">
        <h2>เข้าสู่ระบบ</h2>
        <div class="login-title">ระบบรับคำสั่งมหาวิทยาลัยราชภัฏภูเก็ต</div>
        <form method="post">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit">เข้าสู่ระบบ</button>
        </form>
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php 
        if (isset($_SESSION['register_success'])) {
            echo '<div class="success">' . $_SESSION['register_success'] . '</div>';
            unset($_SESSION['register_success']);
        }
        ?>

        <div class="login-links">
            <a href="register.php">สมัครสมาชิก</a>
            <a href="contact.php">ติดต่อเรา</a>
        </div>
    </div>
</body>
</html>