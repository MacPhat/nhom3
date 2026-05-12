<?php
$page_title = 'Quan ly dich vu';
require_once 'config.php';
checkLogin();

$message = '';

// Xoa dich vu
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Kiem tra dich vu da duoc su dung chua
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sudung_dichvu WHERE dichvu_id = ?");
        $stmt->execute([$id]);
        $usageCount = $stmt->fetchColumn();

        if ($usageCount > 0) {
            $message = '<div class="alert alert-danger">Khong the xoa! Dich vu nay da duoc su dung trong ' . $usageCount . ' lan su dung.</div>';
        } else {
            $stmt = $pdo->prepare("DELETE FROM dichvu WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = '<div class="alert alert-success">Xoa dich vu thanh cong!</div>';
            } else {
                $message = '<div class="alert alert-danger">Xoa that bai!</div>';
            }
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Khong the xoa! Dich vu nay dang duoc su dung trong he thong.</div>';
    }
}

// Them/Sua dich vu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $madv = trim($_POST['madv'] ?? '');
    $tendv = trim($_POST['tendv'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $gia = (float)($_POST['gia'] ?? 0);
    $donvitinh = trim($_POST['donvitinh'] ?? 'lan');
    $trangthai = $_POST['trangthai'] ?? 'hoat_dong';

    // Validate
    $errors = [];
    if ($madv === '') {
        $errors[] = 'Ma dich vu khong duoc de trong!';
    }
    if ($tendv === '') {
        $errors[] = 'Ten dich vu khong duoc de trong!';
    }
    if ($gia < 0) {
        $errors[] = 'Gia dich vu khong duoc am!';
    }

    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE dichvu SET madv=?, tendv=?, mota=?, gia=?, donvitinh=?, trangthai=? WHERE id=?");
                $stmt->execute([$madv, $tendv, $mota, $gia, $donvitinh, $trangthai, $id]);
                $message = '<div class="alert alert-success">Cap nhat dich vu thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO dichvu (madv, tendv, mota, gia, donvitinh, trangthai) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$madv, $tendv, $mota, $gia, $donvitinh, $trangthai]);
                $message = '<div class="alert alert-success">Them dich vu thanh cong!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma dich vu da ton tai!</div>';
            } else {
                $message = '<div class="alert alert-danger">Loi: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// Lay du lieu sua
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM dichvu WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tim kiem va loc
$search = trim($_GET['search'] ?? '');
$filter_trangthai = $_GET['filter_trangthai'] ?? '';

$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (d.madv LIKE ? OR d.tendv LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_trangthai !== '') {
    $where .= " AND d.trangthai = ?";
    $params[] = $filter_trangthai;
}

// Danh sach dich vu kem so lan su dung
$sql = "SELECT d.*,
        COALESCE(sd.so_lan_su_dung, 0) AS so_lan_su_dung,
        COALESCE(sd.tong_tien_sd, 0) AS tong_tien_sd
        FROM dichvu d
        LEFT JOIN (
            SELECT dichvu_id, COUNT(*) AS so_lan_su_dung, SUM(thanhtien) AS tong_tien_sd
            FROM sudung_dichvu
            GROUP BY dichvu_id
        ) sd ON d.id = sd.dichvu_id
        $where
        ORDER BY d.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsDichVu = $stmt->fetchAll();

// Thong ke
$tongDichVu = $pdo->query("SELECT COUNT(*) FROM dichvu")->fetchColumn();
$dichVuHoatDong = $pdo->query("SELECT COUNT(*) FROM dichvu WHERE trangthai='hoat_dong'")->fetchColumn();
$dichVuNgung = $pdo->query("SELECT COUNT(*) FROM dichvu WHERE trangthai='ngung'")->fetchColumn();
$tongDoanhThuDV = $pdo->query("SELECT COALESCE(SUM(thanhtien), 0) FROM sudung_dichvu")->fetchColumn();

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thong ke nhanh -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#10023;</div>
        <div class="stat-info">
            <h4><?php echo $tongDichVu; ?></h4>
            <p>Tong dich vu</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-info">
            <h4><?php echo $dichVuHoatDong; ?></h4>
            <p>Dang hoat dong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#10007;</div>
        <div class="stat-info">
            <h4><?php echo $dichVuNgung; ?></h4>
            <p>Da ngung</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9830;</div>
        <div class="stat-info">
            <h4><?php echo formatMoney($tongDoanhThuDV); ?></h4>
            <p>Tong doanh thu DV</p>
        </div>
    </div>
</div>

<!-- Form Them/Sua dich vu -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua thong tin dich vu' : 'Them dich vu moi'; ?></h3>
        <?php if ($editItem): ?>
            <a href="dichvu.php" class="btn btn-outline btn-sm">Huy sua</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma dich vu <span class="required">*</span></label>
                <input type="text" name="madv" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['madv']) : generateCode('DV', 'dichvu'); ?>" required>
                <div class="form-hint">Ma dich vu tu dong tao, co the thay doi</div>
            </div>
            <div class="form-group">
                <label>Ten dich vu <span class="required">*</span></label>
                <input type="text" name="tendv" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['tendv']) : ''; ?>" placeholder="Nhap ten dich vu" required>
            </div>
            <div class="form-group">
                <label>Gia (VND) <span class="required">*</span></label>
                <input type="number" name="gia" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['gia'] : 0; ?>" placeholder="Gia dich vu" required>
            </div>
            <div class="form-group">
                <label>Don vi tinh</label>
                <input type="text" name="donvitinh" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['donvitinh']) : 'lan'; ?>" placeholder="VD: lan, ngay, suat, nguoi...">
            </div>
            <div class="form-group">
                <label>Trang thai</label>
                <select name="trangthai" class="form-control">
                    <option value="hoat_dong" <?php echo (!$editItem || $editItem['trangthai'] === 'hoat_dong') ? 'selected' : ''; ?>>Hoat dong</option>
                    <option value="ngung" <?php echo ($editItem && $editItem['trangthai'] === 'ngung') ? 'selected' : ''; ?>>Ngung</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Mo ta</label>
            <textarea name="mota" class="form-control" rows="3" placeholder="Mo ta chi tiet ve dich vu..."><?php echo $editItem ? htmlspecialchars($editItem['mota'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="dichvu.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sach dich vu -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach dich vu (<?php echo $tongDichVu; ?>)</h3>
        <?php if ($search || $filter_trangthai): ?>
            <a href="dichvu.php" class="btn btn-outline btn-sm">Xoa bo loc</a>
        <?php endif; ?>
    </div>

    <!-- Tim kiem va loc -->
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem theo ma dich vu, ten dich vu..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:360px;">
            <div class="filter-group">
                <select name="filter_trangthai" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca trang thai --</option>
                    <option value="hoat_dong" <?php echo $filter_trangthai === 'hoat_dong' ? 'selected' : ''; ?>>Hoat dong</option>
                    <option value="ngung" <?php echo $filter_trangthai === 'ngung' ? 'selected' : ''; ?>>Ngung</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="dichvu.php<?php echo $filter_trangthai ? '?filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma DV</th>
                    <th>Ten dich vu</th>
                    <th>Mo ta</th>
                    <th>Gia</th>
                    <th>Don vi</th>
                    <th>So lan su dung</th>
                    <th>Doanh thu</th>
                    <th>Trang thai</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsDichVu)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-icon">&#10023;</div>
                                <h4>Khong co dich vu nao</h4>
                                <p>Khong tim thay dich vu nao voi tieu chi tim kiem hien tai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsDichVu as $dv): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($dv['madv']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($dv['tendv']); ?>
                        </td>
                        <td>
                            <?php
                            $motaRutGon = $dv['mota'] ?? '';
                            echo htmlspecialchars(mb_strlen($motaRutGon) > 60 ? mb_substr($motaRutGon, 0, 60) . '...' : $motaRutGon);
                            ?>
                        </td>
                        <td><strong class="text-primary"><?php echo formatMoney($dv['gia']); ?></strong></td>
                        <td><?php echo htmlspecialchars($dv['donvitinh']); ?></td>
                        <td class="text-center">
                            <?php if ($dv['so_lan_su_dung'] > 0): ?>
                                <span class="badge badge-info badge-dot"><?php echo $dv['so_lan_su_dung']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($dv['tong_tien_sd'] > 0): ?>
                                <?php echo formatMoney($dv['tong_tien_sd']); ?>
                            <?php else: ?>
                                <span class="text-muted">--</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo getStatusBadge($dv['trangthai'], 'dichvu'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="dichvu.php?edit=<?php echo $dv['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="dichvu.php?delete=<?php echo $dv['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa dich vu <?php echo htmlspecialchars(addslashes($dv['tendv'])); ?>?')">Xoa</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
