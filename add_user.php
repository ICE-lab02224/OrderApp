<?php
session_start();
require 'config.php';

// --- Security Check: Admin and Officer Only ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'officer'])) {
    header("Location: index.php");
    exit;
}
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type_to_add = $_POST['user_type'];
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $fullname = $conn->real_escape_string($_POST['fullname']);

    // --- Validation: Check for duplicate username in all tables ---
    $q_user = $conn->query("SELECT id FROM users WHERE username = '$username'");
    $q_officer = $conn->query("SELECT id FROM officers WHERE username = '$username'");
    $q_admin = $conn->query("SELECT id FROM admin WHERE username = '$username'");
    if ($q_user->num_rows > 0 || $q_officer->num_rows > 0 || $q_admin->num_rows > 0) {
        $error = "ชื่อผู้ใช้ (Username) นี้ถูกใช้งานแล้ว";
    } else {
        // --- Secure Password Hashing ---
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // --- Insert into the correct table based on selected type ---
        $sql = "";
        if ($user_type_to_add === 'user') {
            $lecturer_id = $conn->real_escape_string($_POST['lecturer_id']);
            $group = $conn->real_escape_string($_POST['group']);
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, lecturer_id, `group`) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $username, $hashed_password, $fullname, $lecturer_id, $group);
        } elseif ($user_type_to_add === 'officer') {
            // Use MD5 hash for officer to match login logic if it's still MD5
            $md5_password = md5($password);
            $stmt = $conn->prepare("INSERT INTO officers (username, password, fullname) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $md5_password, $fullname);
        } elseif ($user_type_to_add === 'admin') {
            // Admin password uses MD5 for now, as per login.php logic
            $md5_password = md5($password);
            $stmt = $conn->prepare("INSERT INTO admin (username, password, admin_name) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $md5_password, $fullname);
        }

        if (isset($stmt) && $stmt->execute()) {
            $_SESSION['action_success'] = "เพิ่มผู้ใช้ '" . htmlspecialchars($username) . "' สำเร็จแล้ว";
            header("Location: manage_users.php");
            exit;
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>เพิ่มผู้ใช้ใหม่</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">
    <style>
        .header h1 { width: 100%; text-align: center; }
        .form-container { max-width: 500px; margin: 40px auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="password"], select {
            width: 95%; padding: 12px; font-size: 1em; border-radius: 8px; 
            border: 1.5px solid #ffaa80; background-color: #fffaf6;
        }
        input:focus, select:focus { outline: none; border-color: #ff6600; box-shadow: 0 0 5px #ff660044; }
        .form-actions { text-align: center; margin-top: 30px; }
        #user_specific_fields {
            border-left: 3px solid #ff6600;
            padding-left: 15px;
            margin-top: 20px;
        }
    </style>
    <script>
        function toggleUserFields() {
            const userType = document.getElementById('user_type').value;
            const userFields = document.getElementById('user_specific_fields');
            if (userType === 'user') {
                userFields.style.display = 'block';
                document.getElementById('lecturer_id').required = true;
                document.getElementById('group').required = true;
            } else {
                userFields.style.display = 'none';
                document.getElementById('lecturer_id').required = false;
                document.getElementById('group').required = false;
            }
        }
    </script>
</head>
<body onload="toggleUserFields()">
    <div class="header"><h1>เพิ่มผู้ใช้ใหม่</h1></div>
    <div class="container info form-container">
        <?php if ($error): ?><div class="error" style="text-align:center;"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="user_type">ประเภทผู้ใช้:</label>
                <select id="user_type" name="user_type" onchange="toggleUserFields()" required>
                    <option value="user">User</option>
                    <option value="officer">Officer</option>
                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้ (Username):</label>
                <input type="text" id="username" name="username" required placeholder="กรุณากรอก Username">
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน:</label>
                <input type="password" id="password" name="password" required placeholder="กรุณากรอกรหัสผ่าน">
            </div>
            <div class="form-group">
                <label for="fullname">ชื่อ-นามสกุล:</label>
                <input type="text" id="fullname" name="fullname" required placeholder="กรุณากรอกชื่อ-นามสกุล">
            </div>
            <div id="user_specific_fields">
                <div class="form-group">
                    <label for="lecturer_id">รหัสประจำตัว:</label>
                    <input type="text" id="lecturer_id" name="lecturer_id"required placeholder="กรุณากรอกรหัสประจำตัว">
                </div>
                <div class="form-group">
                    <label for="group">คณะ/กลุ่ม:</label>
                    <input type="text" id="group" name="group" required placeholder="กรุณากรอกคณะ">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit">บันทึก</button>
                <a href="manage_users.php" class="back-link">ยกเลิก</a>
            </div>
        </form>
    </div>
</body>
</html>