<?php
session_start();
require 'config.php';

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

if ($user_type === 'admin' || $user_type === 'officer') {
    $user_q = $conn->query("SELECT username FROM admin WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    $username = $userData ? $userData['username'] : '';
    $where = '';
    $search = '';
    if (!empty($_GET['search_keyword'])) {
        $search = $conn->real_escape_string($_GET['search_keyword']);
        $where = " AND (
            o.order_number LIKE '%$search%' OR
            o.order_name LIKE '%$search%' OR
            o.order_type LIKE '%$search%' OR
            o.order_role LIKE '%$search%' OR
            u.fullname LIKE '%$search%' OR
            u.group LIKE '%$search%' OR /* --- จุดที่แก้ไข: เพิ่ม u.group ในการค้นหา --- */
            o.created_at LIKE '%$search%'
        )";
    }
    // --- จุดที่แก้ไข 1: เพิ่ม u.group ใน SELECT statement ---
    $sql = "SELECT o.*, u.fullname, u.group FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1 $where ORDER BY o.created_at DESC";
} elseif ($user_type === 'officer') {
    $user_q = $conn->query("SELECT username FROM officers WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    $username = $userData ? $userData['username'] : '';
    $where = '';
    $search = '';
    if (!empty($_GET['search_keyword'])) {
        $search = $conn->real_escape_string($_GET['search_keyword']);
        $where = " AND (
            o.order_number LIKE '%$search%' OR
            o.order_name LIKE '%$search%' OR
            o.order_role LIKE '%$search%' OR
            u.fullname LIKE '%$search%' OR
            u.group LIKE '%$search%' OR /* --- จุดที่แก้ไข: เพิ่ม u.group ในการค้นหา --- */
            o.order_type LIKE '%$search%' OR
            o.created_at LIKE '%$search%'
        )";
    }
    // --- จุดที่แก้ไข 2: เพิ่ม u.group ใน SELECT statement ---
    $sql = "SELECT o.*, u.fullname, u.group FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1 $where ORDER BY o.created_at DESC";
} else {
    // USER
    $user_q = $conn->query("SELECT username FROM users WHERE id = $user_id");
    $userData = $user_q->fetch_assoc();
    $username = $userData ? $userData['username'] : '';
    $where = '';
    $search = '';
    if (!empty($_GET['search_keyword'])) {
        $search = $conn->real_escape_string($_GET['search_keyword']);
        $where = " AND (
            o.order_number LIKE '%$search%' OR
            o.order_name LIKE '%$search%' OR
            o.order_role LIKE '%$search%' OR
            u.fullname LIKE '%$search%' OR
            u.group LIKE '%$search%' OR /* --- จุดที่แก้ไข: เพิ่ม u.group ในการค้นหา --- */
            o.order_type LIKE '%$search%' OR
            o.created_at LIKE '%$search%'
        )";
    }
    // --- จุดที่แก้ไข 3: เพิ่ม u.group ใน SELECT statement ---
    $sql = "SELECT o.*, u.fullname, u.group FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.user_id = $user_id $where ORDER BY o.created_at DESC";
}
$result = $conn->query($sql);

if (isset($_GET['action']) && $_GET['action'] == 'mark_read') {
    $conn->query("UPDATE orders SET is_read=1 WHERE user_id = $user_id AND is_read=0");
}

?>
<html>
<head>
    <title>ประวัติคำสั่ง</title>
    <link rel="stylesheet" href="assets-style.css?v=<?php echo time(); ?>">
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
        <a href="index.php">หน้าหลัก</a>
        <a href="logout.php">ออกจากระบบ</a>
    </div>
    <form method="get" class="search-order-form" style="margin-bottom:16px;text-align:center;">
        <input type="text" name="search_keyword" placeholder="กดเพื่อค้นหา" 
            style="width:40%;padding:9px;border-radius:8px;border:1.5px solid #ff6600;"
            value="<?= htmlspecialchars($_GET['search_keyword'] ?? '') ?>">
        <button type="submit" style="background:#ff6600;color:#fff;border:none;border-radius:8px;padding:9px 28px;font-weight:600;box-shadow:0 2px 8px #ff660044;">ค้นหา</button>
    </form>
    <div class="table-wrap">
        <table>
            <tr>
                <th>เลขที่คำสั่ง</th>
                <th>ชื่อผู้รับคำสั่ง</th>
                <th>คณะ</th>
                <th>ประเภทคำสั่ง</th>
                <th>รายละเอียดเพิ่มเติม</th>
                <th>ดาวน์โหลดไฟล์</th>
                <th>วันที่</th>
                <?php if ($user_type == 'admin' || $user_type == 'officer'): ?>
                <th>แก้ไข</th>
                <th>ลบ</th>
                <?php endif; ?>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td>
                      <?= htmlspecialchars($row['order_number'] ?? '-') ?>
                      <?php if (isset($row['is_read']) && $row['is_read'] == 0): ?>
                        <span style="color:#ff6600;font-size:1.25em;vertical-align:middle;" title="คำสั่งใหม่">🔔</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['fullname'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['group'] ?? '-') ?></td>
                    <td>
                        <span class="type-badge"><?= isset($row['order_type']) ? htmlspecialchars($row['order_type']) : 'ไม่ระบุ' ?></span>
                    </td>
                    <td class="order-detail-cell">
                        <div><strong>ชื่อคำสั่ง:</strong> <?= htmlspecialchars($row['order_name'] ?? '-') ?></div>
                        <div><strong>หน้าที่คำสั่ง:</strong> <?= htmlspecialchars($row['order_role'] ?? '-') ?></div>
                    </td>
                    <td>
                        <?php if (!empty($row['order_download'])): ?>
                            <a href="uploads/<?= htmlspecialchars($row['order_download']) ?>" download>ดาวน์โหลดไฟล์</a>
                        <?php else: ?>
                            <span style="color:#bbb;">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['created_at'] ?></td>
                    <?php if ($user_type == 'admin' || $user_type == 'officer'): ?>
                        <td><a href="edit_order.php?id=<?= $row['id'] ?>" class="edit-btn">แก้ไข</a></td>
                        <td>
                            <a href="delete_order.php?id=<?= $row['id'] ?>" class="del-btn" onclick="return confirm('ยืนยันการลบคำสั่งนี้?')">ลบ</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>