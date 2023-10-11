<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

$user_id = $_SESSION['user_login'];

$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$query = "SELECT * FROM products";
$result = $conn->query($query);

$products = [];
if ($result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
}

if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];

    // ตรวจสอบว่าสินค้ามีอยู่ในฐานข้อมูลหรือไม่
    $query = "SELECT * FROM products WHERE id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // สินค้ามีอยู่ในฐานข้อมูล, ดำเนินการเพิ่มสินค้าลงในตะกร้าได้
        // ดึง URL ของรูปภาพจากตาราง products
        $query = "SELECT image_path FROM products WHERE id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product_image = $stmt->fetchColumn();

        // เพิ่มข้อมูลลงในตาราง cart
        $insertQuery = "INSERT INTO cart (user_id, product_id, quantity, product_image) VALUES (:user_id, :product_id, 1, :product_image)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':product_image', $product_image, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        // ไม่พบสินค้า, แสดงข้อความผิดพลาดหรือทำตามที่คุณต้องการ
        echo "ไม่พบสินค้า";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าออนไลน์</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/user_styles.css">
</head>
<body>
    <header>
        <h1>ช็อปเลย</h1>
        <nav>
            <ul>
                <li><a href="user.php">หน้าแรก</a></li>
                <li><a href="cart.php"><i class="material-icons">shopping_cart</i></a></li>
                <li><span>ชื่อผู้ใช้: <?php echo $user['firstname'] . ' ' . $user['lastname']; ?></span></li>
                <li><a href="logout.php">ออกจากระบบ</a></li>
            </ul>
        </nav>
    </header>
    
    <section class="product-list">
    <h2>สินค้าทั้งหมด</h2>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product">
                <img class="product-image" src="<?php echo $product["image_path"]; ?>" alt="<?php echo $product["name"]; ?>">
                <h3 class="product-name"><?php echo $product["name"]; ?></h3>
                <p class="product-description"><?php echo $product["description"]; ?></p>
                <p class="product-price">ราคา: <?php echo number_format($product["price"], 2, '.', ','); ?> บาท</p>
                <p class="product-stock">สินค้าคงเหลือ: <?php echo $product["stock_quantity"]; ?></p>
                <form method="post">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="add-to-cart-button" name="add_to_cart">เพิ่มไปยังรถเข็น</button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
    </section>
    
</body>
</html>