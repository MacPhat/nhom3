<?php
$page_title = 'Dat phong';
require_once 'config.php';
checkLogin();

$userId = $_SESSION['user_id'];
$message = '';
$success = '';

// Lay thong tin khach hang
$stmt = $pdo->prepare("SELECT * FROM khachhang WHERE taikhoan_id = ?");
$stmt->execute([$userId]);
$khachHang = $stmt->fetch();

if (!$khachHang) {
    $stmt = $pdo->prepare("SELECT * FROM taikhoan WHERE id = ?");
    $stmt->execute([$userId]);
    $tk = $stmt->fetch();
    $makh = generateCode('KH', 'khachhang');
    $pdo->prepare("INSERT INTO khachhang (makh, taikhoan_id, hoten, sdt, email) VALUES (?, ?, ?, ?, ?)")
        ->execute([$makh, $userId, $tk['hoten'], $tk['sdt'] ?? '', $tk['email'] ?? '']);
    $khachHang = $pdo->query("SELECT * FROM khachhang WHERE taikhoan_id = $userId")->fetch();
}

// Xu ly dat phong
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phong_id = (int)($_POST['phong_id'] ?? 0);
    $ngayden = $_POST['ngayden'] ?? '';
    $ngaydi = $_POST['ngaydi'] ?? '';
    $songuoi = (int)($_POST['songuoi'] ?? 1);
    $yeucau = trim($_POST['yeucau'] ?? '');

    if ($phong_id === 0 || $ngayden === '' || $ngaydi === '') {
        $message = 'Vui long chon phong va ngay!';
    } elseif (strtotime($ngayden) < strtotime(date('Y-m-d'))) {
        $message = 'Ngay den khong duoc la qua khu!';
    } elseif (strtotime($ngaydi) <= strtotime($ngayden)) {
        $message = 'Ngay di phai sau ngay den!';
    } else {
        try {
            $madatphong = generateCode('DP', 'datphong');
            $stmt = $pdo->prepare("INSERT INTO datphong (madatphong, khachhang_id, phong_id, ngayden, ngaydi, songuoi, yeucau, trangthai) VALUES (?, ?, ?, ?, ?, ?, ?, 'cho_xac_nhan')");
            $stmt->execute([$madatphong, $khachHang['id'], $phong_id, $ngayden, $ngaydi, $songuoi, $yeucau]);

            $pdo->prepare("UPDATE phong SET trangthai='da_dat' WHERE id=? AND trangthai='trong'")->execute([$phong_id]);

            addNotification($userId, 'Dat phong thanh cong', "Ban da dat phong thanh cong. Ma dat phong: $madatphong. Vui long cho xac nhan.", 'dat_phong');

            $success = "Dat phong thanh cong! Ma dat phong cua ban la: <strong>$madatphong</strong>. Vui long cho nhan vien xac nhan.";
        } catch (PDOException $e) {
            $message = 'Dat phong that bai! Vui long thu lai.';
        }
    }
}

// Lay tham so tim kiem
$ngayden = $_GET['ngayden'] ?? date('Y-m-d');
$ngaydi = $_GET['ngaydi'] ?? date('Y-m-d', strtotime('+1 day'));
$loaiphong_id = (int)($_GET['loaiphong_id'] ?? 0);

// Lay danh sach phong trong
$sql = "SELECT p.*, lp.tenloai, lp.giaban, lp.tienich, lp.sophong_max, lp.mota as loaimota
        FROM phong p LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id
        WHERE p.trangthai='trong'";
$params = [];
if ($loaiphong_id > 0) {
    $sql .= " AND p.loaiphong_id = ?";
    $params[] = $loaiphong_id;
}
$sql .= " ORDER BY lp.giaban ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsPhongTrong = $stmt->fetchAll();

$dsLoaiPhong = $pdo->query("SELECT * FROM loaiphong ORDER BY giaban ASC")->fetchAll();

// Lich su dat phong cua khach
$dsLichSu = $pdo->prepare("SELECT dp.*, p.tenphong, lp.tenloai, lp.giaban FROM datphong dp LEFT JOIN phong p ON dp.phong_id=p.id LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id WHERE dp.khachhang_id=? ORDER BY dp.created_at DESC LIMIT 10");
$dsLichSu->execute([$khachHang['id']]);
$lichSu = $dsLichSu->fetchAll();

require_once 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;">
    <div>
        <div class="card">
            <div class="card-header">
                <h3>Tim phong trong</h3>
            </div>
            <form method="GET" action="">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Ngay den</label>
                        <input type="date" name="ngayden" class="form-control" value="<?php echo htmlspecialchars($ngayden); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ngay di</label>
                        <input type="date" name="ngaydi" class="form-control" value="<?php echo htmlspecialchars($ngaydi); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Loai phong</label>
                    <select name="loaiphong_id" class="form-control">
                        <option value="">Tat ca</option>
                        <?php foreach ($dsLoaiPhong as $lp): ?>
                        <option value="<?php echo $lp['id']; ?>" <?php echo $loaiphong_id == $lp['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lp['tenloai'] . ' - ' . formatMoney($lp['giaban']) . '/dem'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Tim phong</button>
            </form>
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="card-header">
                <h3>Phong trong (<span id="roomCount"><?php echo count($dsPhongTrong); ?></span>)</h3>
            </div>
            <?php if (empty($dsPhongTrong)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#9962;</div>
                    <h4>Khong co phong trong</h4>
                    <p>Vui long thu ngay khac hoac loai phong khac</p>
                </div>
            <?php else: ?>
                <div style="display:grid; gap:12px;">
                    <?php foreach ($dsPhongTrong as $p): ?>
                    <div style="border:1px solid var(--border); border-radius:var(--radius); padding:16px; display:flex; justify-content:space-between; align-items:center; transition:var(--transition);" onmouseover="this.style.borderColor='var(--primary-light)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div>
                            <strong style="font-size:15px; color:var(--primary-dark);"><?php echo htmlspecialchars($p['tenphong']); ?></strong>
                            <span style="font-size:12px; color:var(--text-light); margin-left:8px;"><?php echo htmlspecialchars($p['tenloai']); ?></span>
                            <div style="margin-top:4px;">
                                <?php if ($p['tienich']): ?>
                                    <?php foreach (explode(',', $p['tienich']) as $i => $t): ?>
                                        <?php if ($i < 3): ?>
                                        <span class="room-amenity"><?php echo htmlspecialchars(trim($t)); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div style="margin-top:6px; font-size:20px; font-weight:800; color:var(--accent);">
                                <?php echo formatMoney($p['giaban']); ?> <small style="font-size:12px; font-weight:400; color:var(--text-light);">/dem</small>
                            </div>
                        </div>
                        <form method="POST" action="" style="display:flex; flex-direction:column; gap:8px; min-width:200px;">
                            <input type="hidden" name="phong_id" value="<?php echo $p['id']; ?>">
                            <input type="hidden" name="ngayden" value="<?php echo htmlspecialchars($ngayden); ?>">
                            <input type="hidden" name="ngaydi" value="<?php echo htmlspecialchars($ngaydi); ?>">
                            <div>
                                <label style="font-size:11px;">So nguoi</label>
                                <input type="number" name="songuoi" class="form-control" value="1" min="1" max="<?php echo $p['sophong_max']; ?>" style="padding:6px 10px; font-size:13px;">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Dat phong</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="card">
            <div class="card-header">
                <h3>Thong tin ca nhan</h3>
            </div>
            <div style="display:grid; gap:12px;">
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ma KH:</span>
                    <strong><?php echo htmlspecialchars($khachHang['makh']); ?></strong>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Ho ten:</span>
                    <strong><?php echo htmlspecialchars($khachHang['hoten']); ?></strong>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">SĐT:</span>
                    <span><?php echo htmlspecialchars($khachHang['sdt'] ?? 'Chua cap nhat'); ?></span>
                </div>
                <div class="d-flex justify-between">
                    <span style="color:var(--text-light);">Email:</span>
                    <span><?php echo htmlspecialchars($khachHang['email'] ?? 'Chua cap nhat'); ?></span>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top:24px;">
            <div class="card-header">
                <h3>Lich su dat phong</h3>
            </div>
            <?php if (empty($lichSu)): ?>
                <div class="empty-state">
                    <div class="empty-icon">&#9998;</div>
                    <h4>Chua co lich su</h4>
                    <p>Ban chua dat phong nao</p>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Ma DP</th>
                                <th>Phong</th>
                                <th>Ngay den</th>
                                <th>Ngay di</th>
                                <th>Trang thai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lichSu as $ls): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($ls['madatphong']); ?></strong></td>
                                <td><?php echo htmlspecialchars($ls['tenphong']); ?></td>
                                <td><?php echo formatDate($ls['ngayden']); ?></td>
                                <td><?php echo formatDate($ls['ngaydi']); ?></td>
                                <td><?php echo getStatusBadge($ls['trangthai'], 'datphong'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
