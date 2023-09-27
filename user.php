<?php
session_start();
require_once 'config/db.php';

// เช็คว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit(); // ออกจากสคริปต์หลังจาก redirect
}

// ดึงข้อมูลสินค้าจากฐานข้อมูล
$query = "SELECT * FROM products";
$result = $conn->query($query);

$products = [];
if ($result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
}

$user_id = $_SESSION['user_login'];
$stmt = $conn->query("SELECT * FROM users WHERE id = $user_id");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าออนไลน์</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- เชื่อมต่อไฟล์ CSS สำหรับการปรับแต่งหน้าเว็บ -->
</head>
<body>
    <header>
        <h1>ร้านค้าออนไลน์</h1>
        <nav>
            <ul>
                <li><a href="#">หน้าแรก</a></li>
                <li><a href="#">สินค้า</a></li>
                <li><a href="#">ตะกร้า</a></li>
                <li><a href="logout.php">ออกจากระบบ</a></li>
            </ul>
        </nav>
    </header>
    
    <section class="user-info">
        <h2>ผู้ใช้</h2>
        <p>ชื่อผู้ใช้: <?php echo $user['firstname'] . ' ' . $user['lastname']; ?></p>
    </section>
    
    <section class="product-list">
        <h2>สินค้าทั้งหมด</h2>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <img src="<?php echo $product["image_path"]; ?>" alt="<?php echo $product["name"]; ?>">
                    <h3><?php echo $product["name"]; ?></h3>
                    <p><?php echo $product["description"]; ?></p>
                    <p>ราคา: <?php echo $product["price"]; ?></p>
                    <button>เพิ่มลงในตะกร้า</button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
</body>
</html>
