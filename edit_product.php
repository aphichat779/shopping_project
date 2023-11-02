<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["product_id"])) {
    $product_id = $_POST["product_id"];

    $query = "SELECT * FROM products WHERE id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: admin.php");
        exit();
    }
} else {
    header("Location: admin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_product"])) {
    $name = htmlspecialchars($_POST["name"]);
    $description = htmlspecialchars($_POST["description"]);
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);

    $image_path = $_POST["image_path"];

    if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_extensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($imageFileType, $allowed_extensions)) {
            if ($_FILES["image"]["size"] <= 2000000) {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $image_path = $target_file;
                } else {
                    echo "อัปโหลดรูปภาพล้มเหลว";
                }
            } else {
                echo "ขนาดไฟล์รูปภาพใหญ่เกินไป (ไม่เกิน 2 MB)";
            }
        } else {
            echo "รูปแบบไฟล์ไม่ถูกต้อง (รองรับเฉพาะ JPG, JPEG, PNG, GIF)";
        }
    }

    $update_query = "UPDATE products SET name = :name, description = :description, price = :price, stock_quantity = :stock, image_path = :image_path WHERE id = :product_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $update_stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $update_stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $update_stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
    $update_stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
    $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: อัปเดตสินค้าไม่สำเร็จ";
    }
} else {
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า</title>
    <link rel="stylesheet" href="css/admin_styles.css">
</head>

<body>
    <h1>แก้ไขสินค้า</h1>

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

    <div class="edit-product-form">
        <h2>แก้ไขสินค้า</h2>
        <form method="POST" action="edit_product.php" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
            <input type="hidden" name="image_path" value="<?php echo $product['image_path']; ?>">

            <label for="name">ชื่อสินค้า:</label>
            <input type="text" name="name" value="<?php echo $product['name']; ?>" required><br>

            <label for="description">คำอธิบาย:</label>
            <input type="text" name="description" value="<?php echo $product['description']; ?>"><br>

            <label for="price">ราคา:</label>
            <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required><br>

            <label for="stock">สต็อก:</label>
            <input type="number" name="stock" value="<?php echo $product['stock_quantity']; ?>" required><br>

            <label for="image">รูปภาพ (เฉพาะ: JPG, JPEG, PNG, GIF, ขนาดไม่เกิน 2 MB):</label>
            <input type="file" name="image" accept="image/jpeg, image/png, image/gif"><br>

            <input type="submit" name="update_product" value="บันทึกการแก้ไข">
        </form>
    </div>
</body>

</html>
