<?php
$host = 'localhost';
$dbname = 'edms';
// $dbname = 'edmsbackup';
$username = 'root';
$password = 'admin';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Thiết lập chế độ lỗi để ném ra ngoại lệ
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Kết nối thất bại: " . $e->getMessage();
}
?>
