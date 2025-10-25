<?php
session_start();
require 'config.php';

// --- Security Check: Admin and Officer Only ---
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['admin', 'officer'])) {
    header("Location: index.php");
    exit;
}
$user_type = $_SESSION['user_type'];

// --- Search Logic ---
$search_keyword = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause = "WHERE username LIKE '%$search_keyword%' OR fullname LIKE '%$search_keyword%' OR `group` LIKE '%$search_keyword%'";
}

// --- จุดที่แก้ไข: สร้างคำสั่ง SQL ตามสิทธิ์ของผู้ใช้ ---
// เริ่มต้นด้วยการดึงข้อมูล user และ officer ซึ่งทุกคนเห็นได้
$sql = "
    (SELECT id, username, fullname, `group`, 'user' as user_type FROM users $where_clause)
    UNION ALL
    (SELECT id, username, fullname, '' as `group`, 'officer' as user_type FROM officers WHERE username LIKE '%$search_keyword%' OR fullname LIKE '%$search_keyword%')
";

// ถ้าผู้ใช้ที่ login อยู่เป็น 'admin' เท่านั้น ถึงจะดึงข้อมูล admin มาเพิ่ม
if ($user_type === 'admin') {
    $sql .= "
        UNION ALL
        (SELECT id, username, admin_name as fullname, '' as `group`, 'admin' as user_type FROM admin WHERE username LIKE '%$search_keyword%' OR admin_name LIKE '%$search_keyword%')
    ";
}

// ปิดท้ายด้วยการเรียงลำดับข้อมูล
$sql .= " ORDER BY user_type, username";
// --- สิ้นสุดการแก้ไข ---

$result = $conn->query($sql);
$all_users = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>จัดการผู้ใช้งาน</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">
    
    <style>
        .header h1 {
            width: 100%;
            text-align: center;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff660022;
        }
        .page-header h2 {
            margin: 0;
            color: #ff6600;
        }
        .page-header .action-btn {
            margin: 0;
        }
        .search-form input {
            width: 50%; 
            padding: 9px;
            border-radius: 8px;
            border: 1.5px solid #ff6600;
        }
        tbody tr:hover {
            background-color: #fffaf6;
        }
        td .edit-btn, td .del-btn {
            margin: 0 4px;
        }
    </style>
</head>
<body>
    <div class="header"><h1>จัดการผู้ใช้งาน</h1></div>

    <div class="main-menu">
        <a href="index.php">หน้าหลัก</a>
        <a href="logout.php">ออกจากระบบ</a>
    </div>

    <div class="container info" style="max-width: 900px;">
        
        <div class="page-header">
            <h2>รายชื่อผู้ใช้ทั้งหมดในระบบ</h2>
            <a href="add_user.php" class="action-btn">เพิ่มผู้ใช้ใหม่</a>
        </div>

        <form method="get" class="search-form" style="text-align:center; margin-bottom: 25px;">
            <input type="text" name="search" placeholder="กดเพื่อค้นหา " value="<?= htmlspecialchars($search_keyword) ?>">
            <button type="submit">ค้นหา</button>
        </form>

        <?php if (isset($_SESSION['action_success'])): ?>
            <div class="success" style="text-align:center;"><?= $_SESSION['action_success'] ?></div>
            <?php unset($_SESSION['action_success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['action_error'])): ?>
            <div class="error" style="text-align:center;"><?= $_SESSION['action_error'] ?></div>
            <?php unset($_SESSION['action_error']); ?>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ชื่อผู้ใช้ (Username)</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>คณะ/กลุ่ม</th>
                    <th>ประเภท</th>
                    <th>การจัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($all_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                    <td><?= htmlspecialchars($user['group'] ?: '-') ?></td>
                    <td><?= htmlspecialchars(ucfirst($user['user_type'])) ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $user['id'] ?>&type=<?= $user['user_type'] ?>" class="edit-btn">แก้ไข</a>
                        <?php
                        $can_delete = false;
                        if (in_array($user_type, ['admin', 'officer'])) {
                            if (($user['user_type'] !== $user_type || $user['id'] != $_SESSION['user_id'])) {
                                if (!($user['user_type'] === 'admin' && $user_type === 'officer')) {
                                    $can_delete = true;
                                }
                            }
                        }
                        if ($can_delete):
                        ?>
                            <a href="delete_user.php?id=<?= $user['id'] ?>&type=<?= $user['user_type'] ?>" class="del-btn" onclick="return confirm('ยืนยันการลบผู้ใช้ <?= htmlspecialchars($user['username']) ?>?')">ลบ</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>