<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

$admin_id = $_SESSION['admin_login'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :admin_id");
$stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
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

    if (!empty($product_id) && is_numeric($product_id) && !empty($additional_stock) && is_numeric($additional_stock)) {
        $query = "SELECT * FROM products WHERE id = :product_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
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
    } else {
        echo "ข้อมูลไม่ถูกต้อง";
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
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);
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
                    <img class="product-image" src="<?php echo $product["image_path"]; ?>" alt="<?php echo $product["name"]; ?>">
                    <h3 class="product-name"><?php echo $product["name"]; ?></h3>
                    <p class="product-description"><?php echo $product["description"]; ?></p>
                    <p class="product-price">ราคา: <?php echo number_format($product["price"], 2, '.', ','); ?> บาท</p>
                    <p class="product-stock">สต็อก: <?php echo $product["stock_quantity"]; ?></p>
                    <form method="POST" action="edit_product.php">
                        <input type="hidden" name="ID" value="<?php echo $product['id']; ?>">
                        <button type="submit" name="edit_product">แก้ไขสินค้า</button>
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
