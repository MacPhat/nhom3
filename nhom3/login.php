<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Vui long nhap day du thong tin!';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM taikhoan WHERE username = ? AND trangthai = 'hoat_dong'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['hoten'] = $user['hoten'];
            $_SESSION['quyen'] = $user['quyen'];

            if ($user['quyen'] === 'khachhang') {
                header('Location: trangchu.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Ten dang nhap hoac mat khau khong dung!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dang nhap - Quan Ly Khach San</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">&#9962;</div>
        <h2>GRAND HOTEL</h2>
        <p class="subtitle">He thong quan ly khach san - Nhom 3</p>

        <?php if ($error): ?>
            <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Ten dang nhap</label>
                <input type="text" name="username" class="form-control" placeholder="Nhap ten dang nhap" required autofocus>
            </div>
            <div class="form-group">
                <label>Mat khau</label>
                <input type="password" name="password" class="form-control" placeholder="Nhap mat khau" required>
            </div>
            <div class="form-check">
                <input type="checkbox" id="remember"> <label for="remember">Ghi nho dang nhap</label>
            </div>
            <button type="submit" class="btn btn-primary">Dang nhap</button>
        </form>

        <div class="auth-footer">
            Chua co tai khoan? <a href="dangky.php">Dang ky ngay</a>
        </div>

        <div style="text-align:center; margin-top:20px; padding-top:16px; border-top:1px solid #e2e8f0;">
            <p style="font-size:11px; color:#94a3b8; margin-bottom:6px;">Tai khoan mau:</p>
            <p style="font-size:11px; color:#64748b;">Admin: admin / 123456</p>
            <p style="font-size:11px; color:#64748b;">Nhan vien: nhanvien1 / 123456</p>
            <p style="font-size:11px; color:#64748b;">Khach hang: khachhang1 / 123456</p>
        </div>
    </div>
</div>
</body>
</html>
