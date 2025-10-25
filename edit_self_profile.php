<?php
session_start();
require 'config.php';

// --- Security Check: Must be logged in and must be a 'user' ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'user') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// 1. Fetch current data for the logged-in user
$stmt = $conn->prepare("SELECT fullname, lecturer_id, `group` FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();

// 2. Handle form submission to update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $conn->real_escape_string($_POST['fullname']);
    $lecturer_id = $conn->real_escape_string($_POST['lecturer_id']);
    $group = $conn->real_escape_string($_POST['group']);
    
    $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, lecturer_id = ?, `group` = ? WHERE id = ?");
    $update_stmt->bind_param("sssi", $fullname, $lecturer_id, $group, $user_id);

    if ($update_stmt->execute()) {
        $success = "บันทึกข้อมูลสำเร็จแล้ว!";
        // Update session so the name changes immediately on other pages
        $_SESSION['fullname'] = $fullname;
        // Re-fetch data to show updated values in the form
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
    } else {
        $error = "เกิดข้อผิดพลาดในการบันทึก: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>แก้ไขข้อมูลส่วนตัว</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">

    <style>
        .header h1 { width: 100%; text-align: center; }
        .form-container { max-width: 600px; margin: 40px auto; }
        .form-group { margin-bottom: 20px; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        input[type="text"] {
            width: 95%; 
            padding: 12px; 
            font-size: 1em; 
            border-radius: 8px; 
            border: 1.5px solid #ffaa80; 
            background-color: #fffaf6;
        }
        input:focus {
            outline: none;
            border-color: #ff6600;
            box-shadow: 0 0 5px #ff660044;
        }
        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #f0f0f0;
        }
        .form-actions .back-link {
            margin-left: 15px;
            background-color: #555;
        }
        .form-actions .back-link:hover {
            background-color: #222;
        }
    </style>
</head>
<body>
    <div class="header"><h1>แก้ไขข้อมูลส่วนตัว</h1></div>
    <div class="container info form-container">
        <h2 style="color: #ff6600; text-align: center;">ข้อมูลของคุณ</h2>
        
        <?php if ($success): ?><div class="success" style="text-align:center;"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error" style="text-align:center;"><?= $error ?></div><?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="fullname">ชื่อ-นามสกุล:</label>
                <input type="text" id="fullname" name="fullname" value="<?= htmlspecialchars($userData['fullname'] ?? '') ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="lecturer_id">รหัสประจำตัว:</label>
                    <input type="text" id="lecturer_id" name="lecturer_id" value="<?= htmlspecialchars($userData['lecturer_id'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="group">คณะ</label>
                    <input type="text" id="group" name="group" value="<?= htmlspecialchars($userData['group'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit">บันทึกการเปลี่ยนแปลง</button>
                <a href="index.php" class="back-link">กลับสู่หน้าหลัก</a>
            </div>
        </form>
    </div>
</body>
</html>