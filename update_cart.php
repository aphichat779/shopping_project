<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $productId = $data->productId;
    $action = $data->action;

    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือไม่
    if (!isset($_SESSION['user_login'])) {
        http_response_code(401); // ส่งรหัส HTTP 401 Unauthorized
        exit();
    }

    // ตรวจสอบว่าสินค้าอยู่ในตะกร้าของผู้ใช้หรือไม่
    $user_id = $_SESSION['user_login'];
    $query = "SELECT * FROM cart WHERE id = :cart_id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cart_id', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cartItem) {
        // ตรวจสอบว่าเป็นการลบสินค้าหรือเพิ่ม/ลดจำนวนสินค้า
        if ($action === 'delete') {
            $query = "DELETE FROM cart WHERE id = :cart_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cart_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } elseif ($action === 'increase') {
            $query = "UPDATE cart SET quantity = quantity + 1 WHERE id = :cart_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cart_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } elseif ($action === 'decrease' && $cartItem['quantity'] > 1) {
            $query = "UPDATE cart SET quantity = quantity - 1 WHERE id = :cart_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':cart_id', $productId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        } else {
            // กรณีที่ไม่ต้องการเปลี่ยนแปลงจำนวนสินค้า
            http_response_code(200);
            exit();
        }

        if ($stmt->execute()) {
            http_response_code(200);
        } else {
            http_response_code(500);
        }
    } else {
        http_response_code(404); // ส่งรหัส HTTP 404 Not Found ถ้าไม่พบสินค้าในตะกร้า
    }
} else {
    http_response_code(405);
}
?>
