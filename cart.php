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

$cartQuery = "SELECT cart.id, products.name, products.price, products.image_path, cart.quantity 
              FROM cart 
              INNER JOIN products ON cart.product_id = products.id 
              WHERE cart.user_id = :user_id";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$cartStmt->execute();
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า</title>
    <link rel="stylesheet" href="css/cart_styles.css">
</head>

<body>
    <header>
        <h1>ร้านค้าออนไลน์</h1>
        <nav>
            <ul>
                <li><a href="user.php">หน้าแรก</a></li>
                <li><a href="logout.php">ออกจากระบบ</a></li>
                <li><span>ชื่อผู้ใช้: <?php echo $user['firstname'] . ' ' . $user['lastname']; ?></span></li>
            </ul>
        </nav>
    </header>

    <section class="cart">
        <h2>รถเข็น</h2>
        <table>
            <thead>
                <tr>
                    <th>สินค้า</th>
                    <th>ราคา</th>
                    <th>จำนวน</th>
                    <th>ลบ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <img src="<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>"
                                class="product-image">
                            <?php echo $item['name']; ?>
                        </td>
                        <td><?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>
                            <button class="remove-button" data-product-id="<?php echo $item['id']; ?>">ลบ</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="checkout-summary">
            <div class="summary-item">
                <span>ยอดรวม:</span>
                <span><?php echo number_format(calculateTotal($cartItems), 2); ?></span>
            </div>
            <button class="payment-button" onclick="window.location.href='payment.php';">ชำระเงิน</button>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const removeButtons = document.querySelectorAll('.remove-button');
            removeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    fetch('remove_product.php', {
                        method: 'POST',
                        body: JSON.stringify({ productId: productId }),
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        // หลังจากลบสินค้าแล้ว รีเฟรชหน้าเว็บ
                        location.reload();
                    })
                    .catch(error => {
                        console.error('เกิดข้อผิดพลาด:', error);
                    });
                });
            });
        });
    </script>

    <?php
    function calculateTotal($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    ?>
</body>
</html>
