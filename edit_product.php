<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['admin_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_product"])) {
    $product_id = $_POST["product_id"];
    $name = $_POST["name"];
    $description = $_POST["description"];
    $price = $_POST["price"];
    $stock = $_POST["stock"];

    $image_path = $_POST["image_path"]; // ใช้รูปภาพเดิมเริ่มต้น

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

    // ตรวจสอบความถูกต้องของข้อมูล (ชื่อ, ราคา, สต็อก, รูปภาพ, ฯลฯ)

    // ปรับปรุงข้อมูลสินค้าในฐานข้อมูล
    $update_query = "UPDATE products SET name = :name, description = :description, price = :price, stock_quantity = :stock, image_path = :image_path WHERE id = :product_id";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $update_stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $update_stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $update_stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
    $update_stmt->bindParam(':image_path', $image_path, PDO::PARAM_STR);
    $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);

    if ($update_stmt->execute()) {
        // อัปเดตสินค้าสำเร็จ
        header("Location: admin.php");
        exit();
    } else {
        echo "Error: อัปเดตสินค้าไม่สำเร็จ";
    }
} else {
    // Redirect to admin.php if product ID is not provided
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

            <label for="image">รูปภาพ:</label>
            <input type="file" name="image" accept="image/*"><br>

            <input type="submit" name="update_product" value="บันทึกการแก้ไข">
        </form>
    </div>
</body>

</html>
