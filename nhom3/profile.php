<?php
$page_title = 'Thong tin ca nhan';
require_once 'config.php';
checkLogin();

$userId = $_SESSION['user_id'];
$message = '';
$success = '';

// Lay thong tin tai khoan
$stmt = $pdo->prepare("SELECT * FROM taikhoan WHERE id = ?");
$stmt->execute([$userId]);
$tk = $stmt->fetch();

// Cap nhat thong tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_profile') {
        $hoten = trim($_POST['hoten'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $sdt = trim($_POST['sdt'] ?? '');

        if ($hoten === '') {
            $message = 'Ho ten khong duoc de trong!';
        } else {
            $stmt = $pdo->prepare("UPDATE taikhoan SET hoten=?, email=?, sdt=? WHERE id=?");
            $stmt->execute([$hoten, $email, $sdt, $userId]);
            $_SESSION['hoten'] = $hoten;
            $success = 'Cap nhat thong tin thanh cong!';
            $tk['hoten'] = $hoten;
            $tk['email'] = $email;
            $tk['sdt'] = $sdt;
        }
    }

    if ($action === 'change_password') {
        $old_pass = trim($_POST['old_password'] ?? '');
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if ($old_pass !== $tk['password']) {
            $message = 'Mat khau cu khong dung!';
        } elseif (strlen($new_pass) < 6) {
            $message = 'Mat khau moi phai co it nhat 6 ky tu!';
        } elseif ($new_pass !== $confirm_pass) {
            $message = 'Xac nhan mat khau khong khop!';
        } else {
            $stmt = $pdo->prepare("UPDATE taikhoan SET password=? WHERE id=?");
            $stmt->execute([$new_pass, $userId]);
            $success = 'Doi mat khau thanh cong!';
            $tk['password'] = $new_pass;
        }
    }
}

// Lay thong bao
$dsThongBao = getNotifications($userId, 10);
$unreadCount = getUnreadCount($userId);

// Danh dau da doc
if (isset($_GET['readall'])) {
    $pdo->prepare("UPDATE thongbao SET daxem=1 WHERE taikhoan_id=?")->execute([$userId]);
    header('Location: profile.php');
    exit;
}

// Lay thong tin khach hang hoac nhan vien
$khachHang = null;
$nhanVien = null;
if ($_SESSION['quyen'] === 'khachhang') {
    $stmt = $pdo->prepare("SELECT * FROM khachhang WHERE taikhoan_id = ?");
    $stmt->execute([$userId]);
    $khachHang = $stmt->fetch();
} elseif ($_SESSION['quyen'] === 'nhanvien' || $_SESSION['quyen'] === 'admin') {
    $stmt = $pdo->prepare("SELECT * FROM nhanvien WHERE taikhoan_id = ?");
    $stmt->execute([$userId]);
    $nhanVien = $stmt->fetch();
}

require_once 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
    <div>
        <div class="card">
            <div class="card-header">
                <h3>Thong tin tai khoan</h3>
            </div>
            <div style="text-align:center; margin-bottom:24px;">
                <div class="user-avatar" style="width:80px; height:80px; font-size:32px; margin:0 auto 12px;">
                    <?php echo mb_substr($tk['hoten'], 0, 1, 'UTF-8'); ?>
                </div>
                <h3 style="font-size:20px; font-weight:700;"><?php echo htmlspecialchars($tk['hoten']); ?></h3>
                <p style="color:var(--text-light); font-size:13px;">@<?php echo htmlspecialchars($tk['username']); ?></p>
                <p style="margin-top:4px;"><?php echo getStatusBadge($tk['trangthai'], 'taikhoan'); ?></p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label>Ho va ten</label>
                    <input type="text" name="hoten" class="form-control" value="<?php echo htmlspecialchars($tk['hoten']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($tk['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>So dien thoai</label>
                    <input type="text" name="sdt" class="form-control" value="<?php echo htmlspecialchars($tk['sdt'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Quyen</label>
                    <input type="text" class="form-control" value="<?php echo $tk['quyen'] === 'admin' ? 'Quan tri vien' : ($tk['quyen'] === 'nhanvien' ? 'Nhan vien' : 'Khach hang'); ?>" disabled>
                </div>
                <div class="form-group">
                    <label>Ngay tao</label>
                    <input type="text" class="form-control" value="<?php echo formatDate($tk['created_at']); ?>" disabled>
                </div>
                <button type="submit" class="btn btn-primary">Cap nhat thong tin</button>
            </form>
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="card-header">
                <h3>Doi mat khau</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Mat khau cu</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Mat khau moi</label>
                    <input type="password" name="new_password" class="form-control" placeholder="It nhat 6 ky tu" required minlength="6">
                </div>
                <div class="form-group">
                    <label>Xac nhan mat khau moi</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-warning">Doi mat khau</button>
            </form>
        </div>
    </div>

    <div>
        <?php if ($khachHang): ?>
        <div class="card">
            <div class="card-header">
                <h3>Thong tin khach hang</h3>
            </div>
            <div style="display:grid; gap:10px;">
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ma KH:</span>
                    <strong><?php echo htmlspecialchars($khachHang['makh']); ?></strong>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">CMND:</span>
                    <span><?php echo htmlspecialchars($khachHang['cmnd'] ?? 'Chua co'); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Gioi tinh:</span>
                    <span><?php echo $khachHang['gioitinh'] === 'Nu' ? 'Nu' : 'Nam'; ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ngay sinh:</span>
                    <span><?php echo formatDate($khachHang['ngaysinh']); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Quoc tich:</span>
                    <span><?php echo htmlspecialchars($khachHang['quoctich'] ?? 'Viet Nam'); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Dia chi:</span>
                    <span><?php echo htmlspecialchars($khachHang['diachi'] ?? 'Chua co'); ?></span>
                </div>
            </div>
            <div style="margin-top:16px;">
                <a href="datphong_khach.php" class="btn btn-primary btn-sm">Dat phong</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($nhanVien): ?>
        <div class="card">
            <div class="card-header">
                <h3>Thong tin nhan vien</h3>
            </div>
            <div style="display:grid; gap:10px;">
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ma NV:</span>
                    <strong><?php echo htmlspecialchars($nhanVien['manv']); ?></strong>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Chuc vu:</span>
                    <span><?php echo htmlspecialchars($nhanVien['chucvu'] ?? ''); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Phong ban:</span>
                    <span><?php echo htmlspecialchars($nhanVien['phongban'] ?? ''); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ngay vao lam:</span>
                    <span><?php echo formatDate($nhanVien['ngvaolam']); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Trang thai:</span>
                    <?php echo getStatusBadge($nhanVien['trangthai'], 'nhanvien'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card" style="margin-top:24px;">
            <div class="card-header">
                <h3>Thong bao <?php if ($unreadCount > 0): ?><span class="badge badge-danger"><?php echo $unreadCount; ?></span><?php endif; ?></h3>
                <?php if ($unreadCount > 0): ?>
                    <a href="profile.php?readall=1" class="btn btn-outline btn-sm">Danh dau tat ca da doc</a>
                <?php endif; ?>
            </div>
            <?php if (empty($dsThongBao)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#128276;</div>
                    <p>Khong co thong bao nao</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($dsThongBao as $tb): ?>
                    <div class="timeline-item" style="<?php echo $tb['daxem'] ? 'opacity:0.6;' : ''; ?>">
                        <div class="time"><?php echo date('d/m/Y H:i', strtotime($tb['created_at'])); ?>
                            <?php
                            $loaiMap = ['he_thong' => 'badge-info', 'dat_phong' => 'badge-warning', 'thanhtoan' => 'badge-success', 'khuyen_mai' => 'badge-primary'];
                            ?>
                            <span class="badge <?php echo $loaiMap[$tb['loai']] ?? 'badge-secondary'; ?>" style="margin-left:6px;"><?php echo $tb['loai']; ?></span>
                        </div>
                        <div class="desc"><strong><?php echo htmlspecialchars($tb['tieude']); ?></strong><br><?php echo htmlspecialchars($tb['noidung'] ?? ''); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
