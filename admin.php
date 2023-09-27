<?php
session_start();
require_once 'config/db.php';

// เช็คว่าผู้ใช้เข้าสู่ระบบหรือไม่
if (!isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit(); // ออกจากสคริปต์หลังจาก redirect
}

$admin_id = $_SESSION['admin_login'];
$stmt = $conn->query("SELECT * FROM users WHERE id = $admin_id");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงรายการสินค้าทั้งหมดจากฐานข้อมูล
$query = "SELECT * FROM products";
$result = $conn->query($query);

$products = [];
if ($result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
}

// เมื่อ Admin ส่งแบบฟอร์มเพิ่มสินค้า
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_products"])) {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];

    // อัปโหลดรูปภาพ
    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/"; // โฟลเดอร์เก็บรูปภาพ
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        } else {
            echo "อัปโหลดรูปภาพล้มเหลว";
        }
    }

    // ใช้ prepared statement เพื่อป้องกัน SQL injection
    $insert_query = "INSERT INTO products (name, description, price, image_path) VALUES (:name, :description, :price, :image_path)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);

    if ($stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: เพิ่มสินค้าไม่สำเร็จ";
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าออนไลน์</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>ร้านค้าออนไลน์</h1>

    <h2>ผู้ใช้</h2>
    <p>ชื่อผู้ใช้: <?php echo $user['firstname'] . ' ' . $user['lastname']; ?></p>

    <h2>สินค้าทั้งหมด</h2>
    <ul>
        <?php foreach ($products as $product): ?>
            <li>
            <?php if (!empty($product["image_path"])): ?>
                    <img src="<?php echo $product["image_path"]; ?>" alt="<?php echo $product["name"]; ?>" width="200">
                <h3><?php echo $product["name"]; ?></h3>
                <p><?php echo $product["description"]; ?></p>
                <p>ราคา: <?php echo $product["price"]; ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>เพิ่มสินค้าใหม่</h2>
    <form method="POST" action="admin.php" enctype="multipart/form-data">
        <label for="image">รูปภาพ:</label>
        <input type="file" name="image" accept="image/*" onchange="previewImage(this);"><br><br>
        <img id="imagePreview" src="#" alt="รูปภาพ" style="max-width: 200px; display: none;">

        <label for="name">ชื่อสินค้า:</label>
        <input type="text" name="name" required><br>

        <label for="description">คำอธิบาย:</label>
        <input type="text" name="description"><br>

        <label for="price">ราคา:</label>
        <input type="number" name="price" step="0.01" required><br>

        <input type="submit" name="add_products" value="เพิ่มสินค้า">
    </form>

    <div class="container">
        <?php 
            if (isset($_SESSION['admin_login'])) {
                $admin_id = $_SESSION['admin_login'];
                $stmt = $conn->query("SELECT * FROM users WHERE id = $admin_id");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        ?>
        <h3 class="mt-4">Welcome Admin, <?php echo $row['firstname'] . ' ' . $row['lastname'] ?></h3>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <script>
        function previewImage(input) {
            var imagePreview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }

                reader.readAsDataURL(input.files[0]);
            } else {
                imagePreview.style.display = 'none';
            }
        }
    </script>


</body>
</html>
