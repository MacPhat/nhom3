<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $hoten = trim($_POST['hoten'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $loai = $_POST['loai'] ?? 'khachhang';

    if ($username === '' || $password === '' || $hoten === '') {
        $error = 'Vui long nhap day du thong tin bat buoc!';
    } elseif (strlen($username) < 4) {
        $error = 'Ten dang nhap phai co it nhat 4 ky tu!';
    } elseif (strlen($password) < 6) {
        $error = 'Mat khau phai co it nhat 6 ky tu!';
    } elseif ($password !== $confirm_password) {
        $error = 'Mat khau xac nhan khong khop!';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM taikhoan WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Ten dang nhap da ton tai!';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM taikhoan WHERE email = ? AND email != ''");
            $stmt->execute([$email]);
            if ($email && $stmt->fetch()) {
                $error = 'Email da duoc su dung!';
            } else {
                $pdo->beginTransaction();
                try {
                    $stmt = $pdo->prepare("INSERT INTO taikhoan (username, password, hoten, email, sdt, quyen) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $password, $hoten, $email, $sdt, $loai]);
                    $taikhoan_id = $pdo->lastInsertId();

                    if ($loai === 'khachhang') {
                        $makh = generateCode('KH', 'khachhang');
                        $stmt = $pdo->prepare("INSERT INTO khachhang (makh, taikhoan_id, hoten, sdt, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$makh, $taikhoan_id, $hoten, $sdt, $email]);
                    } elseif ($loai === 'nhanvien') {
                        $manv = generateCode('NV', 'nhanvien');
                        $stmt = $pdo->prepare("INSERT INTO nhanvien (manv, taikhoan_id, hoten, sdt, email) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$manv, $taikhoan_id, $hoten, $sdt, $email]);
                    }

                    addNotification($taikhoan_id, 'Chao mung!', 'Chao mung ban den voi he thong quan ly khach san Nhom 3!', 'he_thong');

                    $pdo->commit();
                    $success = 'Dang ky thanh cong! Ban co the dang nhap ngay.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Dang ky that bai! Vui long thu lai.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dang ky - Quan Ly Khach San</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">&#9962;</div>
        <h2>Dang Ky Tai Khoan</h2>
        <p class="subtitle">Tao tai khoan de su dung he thong quan ly khach san</p>

        <?php if ($error): ?>
            <div class="auth-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom:20px;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Loai tai khoan <span class="required">*</span></label>
                <select name="loai" class="form-control" id="loaiSelect">
                    <option value="khachhang">Khach hang</option>
                    <option value="nhanvien">Nhan vien</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ten dang nhap <span class="required">*</span></label>
                <input type="text" name="username" class="form-control" placeholder="It nhat 4 ky tu" required minlength="4">
            </div>
            <div class="form-group">
                <label>Ho va ten <span class="required">*</span></label>
                <input type="text" name="hoten" class="form-control" placeholder="Nhap ho va ten" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com">
            </div>
            <div class="form-group">
                <label>So dien thoai</label>
                <input type="text" name="sdt" class="form-control" placeholder="0901234567">
            </div>
            <div class="form-group">
                <label>Mat khau <span class="required">*</span></label>
                <input type="password" name="password" class="form-control" placeholder="It nhat 6 ky tu" required minlength="6">
            </div>
            <div class="form-group">
                <label>Xac nhan mat khau <span class="required">*</span></label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Nhap lai mat khau" required>
            </div>
            <button type="submit" class="btn btn-primary">Dang ky</button>
        </form>

        <div class="auth-footer">
            Da co tai khoan? <a href="login.php">Dang nhap ngay</a>
        </div>
    </div>
</div>
</body>
</html>
