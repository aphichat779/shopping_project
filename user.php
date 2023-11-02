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
    // ดึงข้อมูลจำนวนสินค้าในตะกร้า
$cartQuery = "SELECT COUNT(*) FROM cart WHERE user_id = :user_id";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$cartStmt->execute();
$cartCount = $cartStmt->fetchColumn();

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
        <h1>SHOPPING</h1>
        <div class="search-bar">
        <form method="get" action="search.php"> 
            <input type="text" id="search-input" name="query" placeholder="ค้นหาสินค้า...">
            <button type="submit" id="search-button"><i class="material-icons">search</i></button>
        </form>
        </div>
        <nav>
            <ul>
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
