<?php
session_start();
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $productId = $data->productId;

    // ตรวจสอบว่าผู้ใช้เข้าสู่ระบบหรือยัง
    if (!isset($_SESSION['user_login'])) {
        http_response_code(401); // ส่งรหัส HTTP 401 Unauthorized
        exit();
    }

    // ตรวจสอบว่าสินค้าอยู่ในตะกร้าของผู้ใช้หรือไม่
    $user_id = $_SESSION['user_login'];
    $query = "DELETE FROM cart WHERE id = :cart_id AND user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':cart_id', $productId, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        http_response_code(200);
    } else {
        http_response_code(500); 
    }
} else {
    http_response_code(405); 
}
?>
