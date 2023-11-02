<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

$user_id = $_SESSION['user_login'];

if(isset($_GET['category'])){
    $category = $_GET['category'];

    // ค้นหาสินค้าที่อยู่ในหมวดหมู่ที่ระบุ
    $query = "SELECT * FROM products WHERE category = :category";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // ถ้าไม่ได้รับค่าหมวดหมู่, แสดงทั้งหมดของสินค้า
    $query = "SELECT * FROM products";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// ตรวจสอบว่ามีการค้นหาหรือไม่
if (isset($_GET['query']) && !empty($_GET['query'])) {
    $search_query = '%' . $_GET['query'] . '%'; // เพิ่มเครื่องหมาย % เพื่อให้เป็นการค้นหาที่ครอบคลุม

    // สร้างคำสั่ง SQL เพื่อค้นหาสินค้าโดยใช้ชื่อสินค้า (name) ที่มีคำที่ตรงกับคำค้นหา
    $query = "SELECT * FROM products WHERE name LIKE :search_query OR description LIKE :search_query";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':search_query', $search_query, PDO::PARAM_STR);
    $stmt->execute();

    // ดึงข้อมูลผู้ใช้จากฐานข้อมูล
    $userQuery = "SELECT * FROM users WHERE id = :user_id";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    // ดึงข้อมูลจำนวนสินค้าในตะกร้า
    $cartQuery = "SELECT COUNT(*) FROM cart WHERE user_id = :user_id";
    $cartStmt = $conn->prepare($cartQuery);
    $cartStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $cartStmt->execute();
    $cartCount = $cartStmt->fetchColumn();

} else {
    // ถ้าไม่ได้ป้อนคำค้นหาในฟอร์ม ให้แสดงข้อความว่า "กรุณาป้อนคำค้นหา"
    $_SESSION['error'] = 'กรุณาป้อนคำค้นหา';
    header('location: search.php');
    exit();
}

// ดึงข้อมูลสินค้าที่ค้นหาและแสดงผลในส่วนของ HTML
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/user_styles.css">
</head>

<body>
    <header>
        <h1>SHOPPING</h1>
        <div class="search-bar">
            <form method="get" action="search.php">
                <input type="text" id="search-input" name="query" placeholder="ค้นหาสินค้า...">
                <button type="submit" id="search-button"><i class="material-icons">search</i></button>
            </form>
        </div>
        <nav>
            <ul>
                <li><a href="user.php">หน้าแรก</a></li>
                <li><a href="cart.php"><i class="material-icons">shopping_cart</i> <?php echo $cartCount; ?></a></li>
                <li><span>ชื่อผู้ใช้: <?php echo $user['firstname'] . ' ' . $user['lastname']; ?></span></li>
                <li><a href="logout.php">logout</a></li>
            </ul>
        </nav>
    </header>

    <section class="container">
        <section class="search-sidebar">
            <h4>ค้นหาตามหมวดหมู่และช่วงราคา</h4>
            <form method="get" action="user.php">
                <label for="category">เลือกหมวดหมู่:</label>
                <select id="category" name="category">
                    <option value="เสื้อผ้า">เสื้อผ้า</option>
                    <option value="อุปกรณ์อิเล็กทรอนิกส์">อุปกรณ์อิเล็กทรอนิกส์</option>
                    <option value="เครื่องสำอาง">เครื่องสำอาง</option>
                    <!-- เพิ่มตัวเลือกหมวดหมู่ต่าง ๆ ตามที่คุณต้องการ -->
                </select>
                <label for="min-price">ราคาต่ำสุด:</label>
                <input type="number" id="min-price" name="min_price" value="0">
                <label for="max-price">ราคาสูงสุด:</label>
                <input type="number" id="max-price" name="max_price" value="">
                <button type="submit"><i class="material-icons">search</i> ค้นหา</button>
            </form>
        </section>

    <section class="product-list">
        <h2>ผลลัพธ์จากการค้นหา:</h2>
        <?php
        if ($stmt->rowCount() > 0) {
            echo '<div class="product-grid">';
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<div class="product">';
                echo '<img class="product-image" src="' . $row['image_path'] . '" alt="' . $row['name'] . '">';
                echo '<h3 class="product-name">' . $row['name'] . '</h3>';
                echo '<p class="product-description">' . $row['description'] . '</p>';
                echo '<p class="product-price">ราคา: ' . number_format($row['price'], 2, '.', ',') . ' บาท</p>';
                echo '<p class="product-stock">สินค้าคงเหลือ: ' . $row['stock_quantity'] . '</p>';
                echo '<form method="post">';
                echo '<input type="hidden" name="product_id" value="' . $row['id'] . '">';
                echo '<button type="submit" class="add-to-cart-button" name="add_to_cart">เพิ่มไปยังรถเข็น</button>';
                echo '</form>';
                echo '</div>';
            }
            echo '</div>';
        } else {
            // ไม่มีผลลัพธ์ที่ตรงกับคำค้นหา
            echo "ไม่พบสินค้าที่ตรงกับคำค้นหา";
        }
        ?>
    </section>

</body>

</html>
