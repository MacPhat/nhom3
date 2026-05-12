<?php
session_start();

$host = 'localhost';
$dbname = 'quanly_khachsan';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Kết nối cơ sở dữ liệu thất bại: " . $e->getMessage());
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function checkAdmin() {
    checkLogin();
    if ($_SESSION['quyen'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . ' VNĐ';
}

function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatDateInput($date) {
    if (!$date) return '';
    return date('Y-m-d', strtotime($date));
}

function generateCode($prefix, $table) {
    global $pdo;
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM $table");
    $row = $stmt->fetch();
    $next = ($row['max_id'] ?? 0) + 1;
    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}

function getStatusBadge($status, $type = 'phong') {
    $map = [
        'phong' => [
            'trong' => ['badge-success', 'Trống'],
            'dang_thue' => ['badge-info', 'Đang thuê'],
            'bao_tri' => ['badge-danger', 'Bảo trì'],
            'da_dat' => ['badge-warning', 'Đã đặt'],
        ],
        'datphong' => [
            'cho_xac_nhan' => ['badge-warning', 'Chờ xác nhận'],
            'da_xac_nhan' => ['badge-info', 'Đã xác nhận'],
            'da_nhan_phong' => ['badge-primary', 'Đã nhận phòng'],
            'da_tra_phong' => ['badge-success', 'Đã trả phòng'],
            'da_huy' => ['badge-danger', 'Đã hủy'],
        ],
        'hoadon' => [
            'chua_thanh_toan' => ['badge-danger', 'Chưa thanh toán'],
            'da_thanh_toan' => ['badge-success', 'Đã thanh toán'],
            'da_huy' => ['badge-secondary', 'Đã hủy'],
        ],
        'nhanvien' => [
            'hoat_dong' => ['badge-success', 'Hoạt động'],
            'nghi_viec' => ['badge-danger', 'Nghỉ việc'],
        ],
        'dichvu' => [
            'hoat_dong' => ['badge-success', 'Hoạt động'],
            'ngung' => ['badge-danger', 'Ngưng'],
        ],
        'taikhoan' => [
            'hoat_dong' => ['badge-success', 'Hoạt động'],
            'khoa' => ['badge-danger', 'Khóa'],
        ],
    ];

    $item = $map[$type][$status] ?? ['badge-secondary', $status];
    return '<span class="badge ' . $item[0] . '">' . $item[1] . '</span>';
}

function getNotifications($user_id, $limit = 5) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM thongbao WHERE taikhoan_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

function getUnreadCount($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM thongbao WHERE taikhoan_id = ? AND daxem = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function addNotification($user_id, $tieude, $noidung, $loai = 'he_thong') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO thongbao (taikhoan_id, tieude, noidung, loai) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $tieude, $noidung, $loai]);
}
?>
