<?php
session_start();
require 'config.php';

// --- Security Check: Admin and Officer Only ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'officer']) || !isset($_GET['id']) || !isset($_GET['type'])) {
    header("Location: index.php");
    exit;
}

$id_to_edit = intval($_GET['id']);
$type_to_edit = $_GET['type'];
$error = "";
$success = "";

// --- Fetch current user data ---
$table_name = '';
$fullname_field = 'fullname';
$userData = null;
switch ($type_to_edit) {
    case 'user': $table_name = 'users'; break;
    case 'officer': $table_name = 'officers'; break;
    case 'admin': $table_name = 'admin'; $fullname_field = 'admin_name'; break;
    default: header("Location: manage_users.php"); exit;
}
$stmt = $conn->prepare("SELECT * FROM $table_name WHERE id = ?");
$stmt->bind_param("i", $id_to_edit);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

if (!$userData) {
    echo "User not found.";
    exit;
}

// --- Handle POST request for update ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $password = $_POST['password'];

    if ($type_to_edit === 'user') {
        $lecturer_id = $conn->real_escape_string($_POST['lecturer_id']);
        $group = $conn->real_escape_string($_POST['group']);
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET fullname=?, password=?, lecturer_id=?, `group`=? WHERE id=?");
            $update_stmt->bind_param("ssssi", $fullname, $hashed_password, $lecturer_id, $group, $id_to_edit);
        } else {
            $update_stmt = $conn->prepare("UPDATE users SET fullname=?, lecturer_id=?, `group`=? WHERE id=?");
            $update_stmt->bind_param("sssi", $fullname, $lecturer_id, $group, $id_to_edit);
        }
    } else { // Officer or Admin
        $update_sql = "UPDATE $table_name SET $fullname_field = ?";
        $bind_types = "s";
        $bind_params = [$fullname];
        if (!empty($password)) {
            $update_sql .= ", password = ?";
            $bind_params[] = ($type_to_edit === 'admin' || $type_to_edit === 'officer') ? md5($password) : password_hash($password, PASSWORD_DEFAULT);
            $bind_types .= "s";
        }
        $update_sql .= " WHERE id = ?";
        $bind_params[] = $id_to_edit;
        $bind_types .= "i";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($bind_types, ...$bind_params);
    }

    if ($update_stmt->execute()) {
        $_SESSION['action_success'] = "อัปเดตข้อมูล '" . htmlspecialchars($userData['username']) . "' สำเร็จแล้ว";
        header("Location: manage_users.php");
        exit;
    } else {
        $error = "เกิดข้อผิดพลาดในการอัปเดต: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>แก้ไขผู้ใช้</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">
    <style>
        .header h1 { width: 100%; text-align: center; }
        .form-container { max-width: 500px; margin: 40px auto; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="password"] {
            width: 95%; padding: 12px; font-size: 1em; border-radius: 8px; 
            border: 1.5px solid #ffaa80; background-color: #fffaf6;
        }
        input:focus { outline: none; border-color: #ff6600; box-shadow: 0 0 5px #ff660044; }
        input[readonly] { background-color: #f0f0f0; cursor: not-allowed; }
        .form-actions { text-align: center; margin-top: 30px; }
        /* --- จุดที่แก้ไข 1: ลบ CSS ของ .password-note ที่ไม่ได้ใช้แล้ว --- */
    </style>
</head>
<body>
    <div class="header"><h1>แก้ไขผู้ใช้: <?= htmlspecialchars($userData['username']) ?></h1></div>
    <div class="container info form-container">
        <?php if ($error): ?><div class="error" style="text-align:center;"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label for="username">ชื่อผู้ใช้ (Username):</label>
                <input type="text" id="username" name="username" value="<?= htmlspecialchars($userData['username']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label for="password">รหัสผ่านใหม่:</label>
                <input type="password" id="password" name="password" placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน">
            </div>

            <div class="form-group">
                <label for="fullname">ชื่อ-นามสกุล:</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($userData[$fullname_field]) ?>" required>
            </div>
            <?php if ($type_to_edit === 'user'): ?>
                <div class="form-group">
                    <label for="lecturer_id">รหัสประจำตัว:</label>
                    <input type="text" id="lecturer_id" name="lecturer_id" value="<?= htmlspecialchars($userData['lecturer_id']) ?>">
                </div>
                <div class="form-group">
                    <label for="group">คณะ/กลุ่ม:</label>
                    <input type="text" id="group" name="group" value="<?= htmlspecialchars($userData['group']) ?>">
                </div>
            <?php endif; ?>
            <div class="form-actions">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
                <a href="manage_users.php" class="back-link">ยกเลิก</a>
            </div>
        </form>
    </div>
</body>
</html>