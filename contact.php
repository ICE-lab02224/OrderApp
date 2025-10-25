<?php
session_start();
require 'config.php';

// --- จุดที่แก้ไข 1: ลบโค้ดบังคับ login และการดึงข้อมูล username ที่ไม่จำเป็นออก ---

// --- จุดที่แก้ไข 2: เพิ่มโค้ดเพื่อให้ลิงก์ "กลับ" ทำงานได้ถูกต้อง ---
// ถ้า login อยู่ ให้ลิงก์กลับไปหน้า index.php
// ถ้ายังไม่ login ให้ลิงก์กลับไปหน้า login.php
$back_link_url = 'login.php';
$back_link_text = 'กลับสู่หน้าเข้าสู่ระบบ';

if (isset($_SESSION['user_id'])) {
    $back_link_url = 'index.php';
    $back_link_text = 'กลับสู่หน้าหลัก';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>ติดต่อเรา</title>
    <link rel="stylesheet" href="assets-style.css">
    <style>
        .header {
            display: flex; align-items: center; background: #ff6600; color: #fff;
            padding: 18px 32px 18px 24px; border-radius: 0 0 22px 22px; box-shadow: 0 4px 16px #2228;
            position: relative; min-height: 110px;
        }
        
        .user-profile-name {
            margin-left: 16px; font-size: 1.15em; color: #fff; font-weight: 700; text-shadow: 0 2px 12px #ff660066;
            letter-spacing: 1px;
        }
        .header h1 {
            font-size: 2.1em; font-weight: 700; margin: 0 auto; text-shadow: 1px 3px 10px #ff660088; letter-spacing: 2px;
        }
        .info {
            max-width: 640px; margin: 38px auto 0; padding: 32px 32px 24px 32px; background: #fff; border-radius: 18px; box-shadow: 0 2px 18px #2228; font-size: 1.15em; color: #222; text-align:center;
        }
        @media (max-width: 720px) {
            .header { flex-direction: column; text-align: center; padding: 16px 10px; min-height: unset;}
            .user-profile-box { position: static; margin-bottom: 10px;}
            .header h1 { margin: 0;}
        }
        a.back-link {
            display: inline-block;
            background: #ff6600;
            color: #fff;
            padding: 9px 28px;
            border-radius: 8px;
            text-decoration: none;
            margin: 26px auto;
            font-weight: 600;
            box-shadow: 0 2px 8px #2228;
            transition: background .2s;
        }
        a.back-link:hover {
            background: #222;
            color: #ff6600;
        }
    </style>
</head>
<body>
    <div class="header">
        
        <h1>ติดต่อเรา</h1>
    </div>
    <div class="info">
        <p>ติดต่อสอบถามเพิ่มเติมได้ที่อีเมล <a href="mailto:info@pkru.ac.th">info@pkru.ac.th</a> หรือโทร <b>000-000000</b></p>
        <p>ที่อยู่: มหาวิทยาลัยราชภัฏภูเก็ต<br>เลขที่ 21 ถนนเทพกระษัตรี ตำบลรัษฎา อำเภอเมือง จังหวัดภูเก็ต 83000</p>
        
        <a href="<?= $back_link_url ?>" class="back-link"><?= $back_link_text ?></a>
    </div>
</body>
</html>