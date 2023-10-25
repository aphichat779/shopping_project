<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_login'];
$stmt = $conn->query("SELECT * FROM users WHERE id = $admin_id");
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_products"])) {
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];

    // อัปโหลดรูปภาพ
    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/"; 
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_stock"])) {
    $product_id = $_POST["product_id"];
    $additional_stock = $_POST["stock"];

    if (!empty($product_id) && !empty($additional_stock)) {
        $query = "SELECT * FROM products WHERE id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // ปรับปรุงสต็อกสินค้า
            $new_stock_quantity = $product["stock_quantity"] + $additional_stock;
            $update_query = "UPDATE products SET stock_quantity = :new_stock WHERE id = :product_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':new_stock', $new_stock_quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

            if ($update_stmt->execute()) {
                header("Location: admin.php");
                exit();
            } else {
                echo "Error: อัปเดตสต็อกไม่สำเร็จ";
            }
        } else {
            echo "Error: ไม่พบสินค้าที่ต้องการอัปเดตสต็อก";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_product"])) {
    $product_id = $_POST["product_id"];
    $edit_name = $_POST["edit_name"];
    $edit_description = $_POST["edit_description"];
    $edit_price = $_POST["edit_price"];
    $edit_stock = $_POST["edit_stock"];

    // อัปโหลดรูปภาพ
    $edit_image_path = $product["image_path"]; // ให้เริ่มต้นค่าเป็นรูปเดิม
    if (isset($_FILES["edit_image"]) && $_FILES["edit_image"]["error"] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $edit_image_name = basename($_FILES["edit_image"]["name"]);
        $edit_target_file = $target_dir . $edit_image_name;

        if (move_uploaded_file($_FILES["edit_image"]["tmp_name"], $edit_target_file)) {
            $edit_image_path = $edit_target_file;
        } else {
            echo "อัปโหลดรูปภาพล้มเหลว";
        }
    }

    // ตรวจสอบราคาที่ถูกส่งมาจากฟอร์ม
    $edit_price = $_POST["edit_price"];
    if (!is_numeric($edit_price)) {
        echo "Error: ราคาต้องเป็นตัวเลข";
        exit();
    }

    // ใช้ prepared statement เพื่อป้องกัน SQL injection
    $update_query = "UPDATE products SET name = :edit_name, description = :edit_description, price = :edit_price, image_path = :edit_image_path, stock_quantity = :edit_stock WHERE id = :product_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':edit_name', $edit_name, PDO::PARAM_STR);
    $update_stmt->bindParam(':edit_description', $edit_description, PDO::PARAM_STR);
    $update_stmt->bindParam(':edit_price', $edit_price, PDO::PARAM_STR);
    $update_stmt->bindParam(':edit_image_path', $edit_image_path, PDO::PARAM_STR);
    $update_stmt->bindParam(':edit_stock', $edit_stock, PDO::PARAM_INT);
    $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: แก้ไขสินค้าไม่สำเร็จ";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านค้าออนไลน์</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>

<body>
    <h1>ร้านค้าออนไลน์</h1>

    <div class="container">
        <?php 
            if (isset($_SESSION['admin_login'])) {
                $admin_id = $_SESSION['admin_login'];
                $stmt = $conn->query("SELECT * FROM users WHERE id = $admin_id");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <h3 class="mt-4">Welcome Admin, <?php echo $row['firstname'] . ' ' . $row['lastname'] ?></h3>
        <a href="logout.php" class="btn btn-danger">Logout</a>
        <?php } ?>
    </div>

    <div class="add-product-form">            
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
    </div>

    <section class="product-list">
        <h2>สินค้าทั้งหมด</h2>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product">
                <form method="POST" action="admin.php" enctype="multipart/form-data">
                    <img class="product-image" src="<?php echo $product["image_path"]; ?>" alt="<?php echo $product["name"]; ?>">
                        <label for="edit_image">รูปภาพ:</label>
                        <input type="file" name="edit_image" accept="image/*"><br>
                    <h3 class="product-name"><?php echo $product["name"]; ?></h3>
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <label for="edit_name">ชื่อสินค้า:</label>
                        <input type="text" name="edit_name" value="<?php echo $product['name']; ?>" required><br>
                    <p class="product-description"><?php echo $product["description"]; ?></p>
                        <label for="edit_description">คำอธิบาย:</label>
                        <input type="text" name="edit_description" value="<?php echo $product['description']; ?>"><br>
                    <p class="product-price">ราคา: <?php echo number_format($product["price"], 2, '.', ','); ?> บาท</p>
                        <label for="edit_price">ราคา:</label>
                        <input type="number" name="edit_price" step="0.01" value="<?php echo $product['price']; ?>" required><br>
                    <p class="product-stock">สต็อก: <?php echo $product["stock_quantity"]; ?></p>
                        <label for="edit_stock">สต็อก:</label>
                        <input type="number" name="edit_stock" value="<?php echo $product['stock_quantity']; ?>" required><br>
                        <button type="submit" name="edit_product">บันทึกการแก้ไข</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

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
