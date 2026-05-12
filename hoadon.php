<?php
$page_title = 'Quan ly hoa don';
require_once 'config.php';
checkLogin();

$message = '';

// Xoa hoa don
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM hoadon WHERE id = ?");
        if ($stmt->execute([$id])) {
            if (isset($_SESSION['user_id'])) {
                addNotification($_SESSION['user_id'], 'Xoa hoa don', 'Da xoa hoa don #' . $id . ' thanh cong.', 'he_thong');
            }
            $message = '<div class="alert alert-success">Xoa hoa don thanh cong!</div>';
        } else {
            $message = '<div class="alert alert-danger">Xoa that bai!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Xoa that bai! Hoa don dang duoc su dung.</div>';
    }
}

// Them/Sua hoa don
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $mahoadon = trim($_POST['mahoadon'] ?? '');
    $datphong_id = (int)($_POST['datphong_id'] ?? 0);
    $nhanvien_id = (int)($_POST['nhanvien_id'] ?? 0) ?: null;
    $tienphong = (float)($_POST['tienphong'] ?? 0);
    $tiendichvu = (float)($_POST['tiendichvu'] ?? 0);
    $phuthu = (float)($_POST['phuthu'] ?? 0);
    $giamgia = (float)($_POST['giamgia'] ?? 0);
    $tongtien = $tienphong + $tiendichvu + $phuthu - $giamgia;
    $thanhtoan = (float)($_POST['thanhtoan'] ?? 0);
    $trangthai = $_POST['trangthai'] ?? 'chua_thanh_toan';
    $pt_thanhtoan = $_POST['pt_thanhtoan'] ?? 'tien_mat';
    $ghichu = trim($_POST['ghichu'] ?? '');

    if ($mahoadon === '' || $datphong_id === 0) {
        $message = '<div class="alert alert-danger">Vui long nhap ma hoa don va chon dat phong!</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE hoadon SET mahoadon=?, datphong_id=?, nhanvien_id=?, tienphong=?, tiendichvu=?, tongtien=?, phuthu=?, giamgia=?, thanhtoan=?, trangthai=?, pt_thanhtoan=?, ghichu=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$mahoadon, $datphong_id, $nhanvien_id, $tienphong, $tiendichvu, $tongtien, $phuthu, $giamgia, $thanhtoan, $trangthai, $pt_thanhtoan, $ghichu, $id]);
                $message = '<div class="alert alert-success">Cap nhat hoa don thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO hoadon (mahoadon, datphong_id, nhanvien_id, tienphong, tiendichvu, tongtien, phuthu, giamgia, thanhtoan, trangthai, pt_thanhtoan, ghichu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$mahoadon, $datphong_id, $nhanvien_id, $tienphong, $tiendichvu, $tongtien, $phuthu, $giamgia, $thanhtoan, $trangthai, $pt_thanhtoan, $ghichu]);
                $message = '<div class="alert alert-success">Them hoa don thanh cong!</div>';
            }

            if (isset($_SESSION['user_id'])) {
                $thongBaoMsg = $id > 0 ? 'Cap nhat hoa don ' . $mahoadon : 'Them moi hoa don ' . $mahoadon;
                addNotification($_SESSION['user_id'], 'Hoa don', $thongBaoMsg, 'he_thong');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma hoa don da ton tai!</div>';
            } else {
                $message = '<div class="alert alert-danger">Loi: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Lay du lieu sua
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM hoadon WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tim kiem va loc
$search = trim($_GET['search'] ?? '');
$filter_trangthai = $_GET['filter_trangthai'] ?? '';
$filter_pt_thanhtoan = $_GET['filter_pt_thanhtoan'] ?? '';

$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (hd.mahoadon LIKE ? OR kh.hoten LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_trangthai !== '') {
    $where .= " AND hd.trangthai = ?";
    $params[] = $filter_trangthai;
}
if ($filter_pt_thanhtoan !== '') {
    $where .= " AND hd.pt_thanhtoan = ?";
    $params[] = $filter_pt_thanhtoan;
}

// Lay danh sach hoa don
$sql = "SELECT hd.*, dp.madatphong, kh.hoten as tenkh, p.tenphong, p.maphong, nv.hoten as tennv
        FROM hoadon hd
        LEFT JOIN datphong dp ON hd.datphong_id = dp.id
        LEFT JOIN khachhang kh ON dp.khachhang_id = kh.id
        LEFT JOIN phong p ON dp.phong_id = p.id
        LEFT JOIN nhanvien nv ON hd.nhanvien_id = nv.id
        $where ORDER BY hd.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsHoaDon = $stmt->fetchAll();

// Lay danh sach cho dropdown
$dsDatPhong = $pdo->query("SELECT dp.id, dp.madatphong, kh.hoten, p.tenphong, lp.giaban
    FROM datphong dp
    LEFT JOIN khachhang kh ON dp.khachhang_id = kh.id
    LEFT JOIN phong p ON dp.phong_id = p.id
    LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id
    ORDER BY dp.id DESC")->fetchAll();
$dsNhanVien = $pdo->query("SELECT id, manv, hoten FROM nhanvien ORDER BY hoten")->fetchAll();

// Thong ke hoa don
$tongHoaDon = $pdo->query("SELECT COUNT(*) FROM hoadon")->fetchColumn();
$daThanhToan = $pdo->query("SELECT COUNT(*) FROM hoadon WHERE trangthai='da_thanh_toan'")->fetchColumn();
$chuaThanhToan = $pdo->query("SELECT COUNT(*) FROM hoadon WHERE trangthai='chua_thanh_toan'")->fetchColumn();
$tongDoanhThu = $pdo->query("SELECT COALESCE(SUM(tongtien), 0) FROM hoadon WHERE trangthai='da_thanh_toan'")->fetchColumn();

// Tao ma hoa don tu dong
$maHDDefault = generateCode('HD', 'hoadon');

// Xu ly AJAX in hoa don
if (isset($_GET['action']) && $_GET['action'] === 'print') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT hd.*, dp.madatphong, kh.hoten as tenkh, kh.sodt as sodtkh, kh.cmnd, p.tenphong, p.maphong, lp.tenloai, nv.hoten as tennv
            FROM hoadon hd
            LEFT JOIN datphong dp ON hd.datphong_id = dp.id
            LEFT JOIN khachhang kh ON dp.khachhang_id = kh.id
            LEFT JOIN phong p ON dp.phong_id = p.id
            LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id
            LEFT JOIN nhanvien nv ON hd.nhanvien_id = nv.id
            WHERE hd.id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    if ($invoice) {
        $ptLabels = ['tien_mat' => 'Tien mat', 'chuyen_khoan' => 'Chuyen khoan', 'the' => 'The'];
        $statusLabels = ['chua_thanh_toan' => 'Chua thanh toan', 'da_thanh_toan' => 'Da thanh toan', 'da_huy' => 'Da huy'];
        ?>
        <div style="text-align:center; margin-bottom:24px;">
            <h2 style="margin-bottom:2px;">GRAND HOTEL</h2>
            <p style="color:#64748b; font-size:13px;">Hoa don thanh toan</p>
        </div>
        <table>
            <tr><th colspan="2" style="text-align:center; background:#0ea5e9;">THONG TIN HOA DON</th></tr>
            <tr><td style="font-weight:600;">Ma hoa don:</td><td><strong><?php echo htmlspecialchars($invoice['mahoadon']); ?></strong></td></tr>
            <tr><td style="font-weight:600;">Ngay lap:</td><td><?php echo formatDate($invoice['ngaylap']); ?></td></tr>
            <tr><td style="font-weight:600;">Ma dat phong:</td><td><?php echo htmlspecialchars($invoice['madatphong']); ?></td></tr>
            <tr><td style="font-weight:600;">Khach hang:</td><td><?php echo htmlspecialchars($invoice['tenkh']); ?></td></tr>
            <?php if (!empty($invoice['sodtkh'])): ?><tr><td style="font-weight:600;">So DT:</td><td><?php echo htmlspecialchars($invoice['sodtkh']); ?></td></tr><?php endif; ?>
            <tr><td style="font-weight:600;">Phong:</td><td><?php echo htmlspecialchars($invoice['tenphong'] . ' - ' . $invoice['tenloai']); ?></td></tr>
            <tr><td style="font-weight:600;">Nhan vien:</td><td><?php echo htmlspecialchars($invoice['tennv'] ?? ''); ?></td></tr>
        </table>
        <table>
            <thead>
                <tr>
                    <th>Khoan muc</th>
                    <th class="text-right">Thanh tien (VND)</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Tien phong</td><td class="text-right"><?php echo formatMoney($invoice['tienphong']); ?></td></tr>
                <tr><td>Tien dich vu</td><td class="text-right"><?php echo formatMoney($invoice['tiendichvu']); ?></td></tr>
                <?php if ($invoice['phuthu'] > 0): ?><tr><td>Phu thu</td><td class="text-right"><?php echo formatMoney($invoice['phuthu']); ?></td></tr><?php endif; ?>
                <?php if ($invoice['giamgia'] > 0): ?><tr><td>Giam gia</td><td class="text-right" style="color:#dc2626;">-<?php echo formatMoney($invoice['giamgia']); ?></td></tr><?php endif; ?>
                <tr style="font-weight:700; font-size:15px; background:#f0fdf4;"><td>TONG TIEN</td><td class="text-right" style="color:#047857;"><?php echo formatMoney($invoice['tongtien']); ?></td></tr>
                <?php if ($invoice['thanhtoan'] > 0): ?><tr style="font-weight:600; background:#eff6ff;"><td>Da thanh toan</td><td class="text-right" style="color:#0369a1;"><?php echo formatMoney($invoice['thanhtoan']); ?></td></tr><?php endif; ?>
                <tr><td>Trang thai</td><td><strong><?php echo $statusLabels[$invoice['trangthai']] ?? $invoice['trangthai']; ?></strong></td></tr>
                <tr><td>Phuong thuc</td><td><?php echo $ptLabels[$invoice['pt_thanhtoan']] ?? $invoice['pt_thanhtoan']; ?></td></tr>
            </tbody>
        </table>
        <?php if (!empty($invoice['ghichu'])): ?>
        <p style="margin-top:12px; font-size:13px;"><strong>Ghi chu:</strong> <?php echo htmlspecialchars($invoice['ghichu']); ?></p>
        <?php endif; ?>
        <div class="footer" style="margin-top:40px; text-align:center; color:#64748b; font-size:12px;">
            <p>Cam on quy khach da su dung dich vu tai Grand Hotel!</p>
            <p>&copy; <?php echo date('Y'); ?> Grand Hotel - Nhom 3</p>
        </div>
        <?php
    } else {
        echo '<p style="text-align:center; color:#dc2626;">Khong tim thay hoa don!</p>';
    }
    exit;
}

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thong ke hoa don -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#9830;</div>
        <div class="stat-info">
            <h4><?php echo $tongHoaDon; ?></h4>
            <p>Tong hoa don</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10004;</div>
        <div class="stat-info">
            <h4><?php echo $daThanhToan; ?></h4>
            <p>Da thanh toan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#10008;</div>
        <div class="stat-info">
            <h4><?php echo $chuaThanhToan; ?></h4>
            <p>Chua thanh toan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9733;</div>
        <div class="stat-info">
            <h4><?php echo formatMoney($tongDoanhThu); ?></h4>
            <p>Tong doanh thu</p>
        </div>
    </div>
</div>

<!-- Form Them/Sua hoa don -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua hoa don' : 'Them hoa don moi'; ?></h3>
        <?php if ($editItem): ?>
            <a href="hoadon.php" class="btn btn-outline btn-sm">Huy sua</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="" id="formHoaDon">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma hoa don <span class="required">*</span></label>
                <input type="text" name="mahoadon" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['mahoadon']) : htmlspecialchars($maHDDefault); ?>" required>
            </div>
            <div class="form-group">
                <label>Dat phong <span class="required">*</span></label>
                <select name="datphong_id" class="form-control" id="datphongSelect" required>
                    <option value="">-- Chon dat phong --</option>
                    <?php foreach ($dsDatPhong as $dp): ?>
                    <option value="<?php echo $dp['id']; ?>" data-giaban="<?php echo $dp['giaban'] ?? 0; ?>" <?php echo ($editItem && $editItem['datphong_id'] == $dp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dp['madatphong'] . ' - ' . $dp['hoten'] . ' (' . $dp['tenphong'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Nhan vien</label>
                <select name="nhanvien_id" class="form-control">
                    <option value="">-- Chon nhan vien --</option>
                    <?php foreach ($dsNhanVien as $nv): ?>
                    <option value="<?php echo $nv['id']; ?>" <?php echo ($editItem && $editItem['nhanvien_id'] == $nv['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($nv['hoten'] . ' (' . $nv['manv'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tien phong (VND)</label>
                <input type="number" name="tienphong" id="tienphong" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['tienphong'] : 0; ?>" oninput="tinhTongTien()">
            </div>
            <div class="form-group">
                <label>Tien dich vu (VND)</label>
                <input type="number" name="tiendichvu" id="tiendichvu" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['tiendichvu'] : 0; ?>" oninput="tinhTongTien()">
            </div>
            <div class="form-group">
                <label>Phu thu (VND)</label>
                <input type="number" name="phuthu" id="phuthu" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['phuthu'] : 0; ?>" oninput="tinhTongTien()">
            </div>
            <div class="form-group">
                <label>Giam gia (VND)</label>
                <input type="number" name="giamgia" id="giamgia" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['giamgia'] : 0; ?>" oninput="tinhTongTien()">
            </div>
            <div class="form-group">
                <label>Tong tien (VND)</label>
                <input type="number" name="tongtien" id="tongtien" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['tongtien'] : 0; ?>" readonly style="background:#f0fdf4;font-weight:700;color:#047857;">
                <div class="form-hint">Tu dong tinh: Tien phong + Tien dich vu + Phu thu - Giam gia</div>
            </div>
            <div class="form-group">
                <label>Thanh toan (VND)</label>
                <input type="number" name="thanhtoan" id="thanhtoan" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['thanhtoan'] : 0; ?>">
            </div>
            <div class="form-group">
                <label>Trang thai</label>
                <select name="trangthai" class="form-control" id="trangthaiSelect">
                    <option value="chua_thanh_toan" <?php echo ($editItem && $editItem['trangthai']==='chua_thanh_toan') ? 'selected' : ''; ?>>Chua thanh toan</option>
                    <option value="da_thanh_toan" <?php echo ($editItem && $editItem['trangthai']==='da_thanh_toan') ? 'selected' : ''; ?>>Da thanh toan</option>
                    <option value="da_huy" <?php echo ($editItem && $editItem['trangthai']==='da_huy') ? 'selected' : ''; ?>>Da huy</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phuong thuc thanh toan</label>
                <select name="pt_thanhtoan" class="form-control" id="ptThanhtoanSelect">
                    <option value="tien_mat" <?php echo ($editItem && $editItem['pt_thanhtoan']==='tien_mat') ? 'selected' : ''; ?>>Tien mat</option>
                    <option value="chuyen_khoan" <?php echo ($editItem && $editItem['pt_thanhtoan']==='chuyen_khoan') ? 'selected' : ''; ?>>Chuyen khoan</option>
                    <option value="the" <?php echo ($editItem && $editItem['pt_thanhtoan']==='the') ? 'selected' : ''; ?>>The</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Ghi chu</label>
            <textarea name="ghichu" class="form-control" rows="3" placeholder="Ghi chu ve hoa don..."><?php echo $editItem ? htmlspecialchars($editItem['ghichu'] ?? '') : ''; ?></textarea>
        </div>
        <!-- Hien thi chi tiet tinh tien -->
        <div id="tongTienDisplay" style="display:none; background:var(--success-lighter,#d1fae5); border:1px solid var(--success,#059669); border-radius:var(--radius-sm,6px); padding:15px; margin-bottom:18px;">
            <strong style="color:var(--success,#059669);">Tong tien:</strong>
            <span id="tongTienValue" style="font-size:1.2em; font-weight:700; color:var(--success,#059669); margin-left:10px;"></span>
            <span id="tongTienDetail" style="display:block; font-size:0.9em; color:#047857; margin-top:5px;"></span>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="hoadon.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sach hoa don -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach hoa don (<?php echo count($dsHoaDon); ?>)</h3>
        <div class="btn-group no-print">
            <button type="button" class="btn btn-info btn-sm" onclick="window.print()">&#128424; In</button>
            <?php if ($search || $filter_trangthai || $filter_pt_thanhtoan): ?>
                <a href="hoadon.php" class="btn btn-outline btn-sm">Xoa bo loc</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tim kiem va loc -->
    <div class="search-bar no-print">
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem ma hoa don, ten khach hang..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:320px;">
            <div class="filter-group">
                <select name="filter_trangthai" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca trang thai --</option>
                    <option value="chua_thanh_toan" <?php echo $filter_trangthai === 'chua_thanh_toan' ? 'selected' : ''; ?>>Chua thanh toan</option>
                    <option value="da_thanh_toan" <?php echo $filter_trangthai === 'da_thanh_toan' ? 'selected' : ''; ?>>Da thanh toan</option>
                    <option value="da_huy" <?php echo $filter_trangthai === 'da_huy' ? 'selected' : ''; ?>>Da huy</option>
                </select>
                <select name="filter_pt_thanhtoan" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca phuong thuc --</option>
                    <option value="tien_mat" <?php echo $filter_pt_thanhtoan === 'tien_mat' ? 'selected' : ''; ?>>Tien mat</option>
                    <option value="chuyen_khoan" <?php echo $filter_pt_thanhtoan === 'chuyen_khoan' ? 'selected' : ''; ?>>Chuyen khoan</option>
                    <option value="the" <?php echo $filter_pt_thanhtoan === 'the' ? 'selected' : ''; ?>>The</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="hoadon.php" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma hoa don</th>
                    <th>Khach hang</th>
                    <th>Phong</th>
                    <th>Nhan vien</th>
                    <th>Ngay lap</th>
                    <th>Tien phong</th>
                    <th>Tien dich vu</th>
                    <th>Tong tien</th>
                    <th>Trang thai</th>
                    <th>PT thanh toan</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsHoaDon)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-icon">&#9830;</div>
                                <h4>Khong co hoa don nao</h4>
                                <p>Khong tim thay hoa don nao voi tieu chi tim kiem hien tai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsHoaDon as $hd): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($hd['mahoadon']); ?></strong></td>
                        <td><?php echo htmlspecialchars($hd['tenkh'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($hd['tenphong'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($hd['tennv'] ?? ''); ?></td>
                        <td><?php echo formatDate($hd['ngaylap']); ?></td>
                        <td><?php echo formatMoney($hd['tienphong']); ?></td>
                        <td><?php echo formatMoney($hd['tiendichvu']); ?></td>
                        <td><strong><?php echo formatMoney($hd['tongtien']); ?></strong></td>
                        <td><?php echo getStatusBadge($hd['trangthai'], 'hoadon'); ?></td>
                        <td>
                            <?php
                            $ptLabels = [
                                'tien_mat' => ['badge-warning', 'Tien mat'],
                                'chuyen_khoan' => ['badge-info', 'Chuyen khoan'],
                                'the' => ['badge-primary', 'The'],
                            ];
                            $pt = $ptLabels[$hd['pt_thanhtoan']] ?? ['badge-secondary', $hd['pt_thanhtoan']];
                            ?>
                            <span class="badge <?php echo $pt[0]; ?>"><?php echo $pt[1]; ?></span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="hoadon.php?edit=<?php echo $hd['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?><?php echo $filter_pt_thanhtoan ? '&filter_pt_thanhtoan=' . urlencode($filter_pt_thanhtoan) : ''; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="hoadon.php?delete=<?php echo $hd['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?><?php echo $filter_pt_thanhtoan ? '&filter_pt_thanhtoan=' . urlencode($filter_pt_thanhtoan) : ''; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa hoa don <?php echo htmlspecialchars(addslashes($hd['mahoadon'])); ?>?')">Xoa</a>
                                <button type="button" class="btn btn-info btn-sm" onclick="printInvoice(<?php echo $hd['id']; ?>)">&#128424;</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Print Invoice Modal -->
<div id="printModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:200; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); width:90%; max-width:700px; max-height:90vh; overflow-y:auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px; border-bottom:1px solid #e2e8f0;">
            <h3 style="font-size:17px; font-weight:700; color:#082f49;">In hoa don</h3>
            <button type="button" onclick="closePrintModal()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <div id="printContent" style="padding:32px;">
        </div>
        <div style="padding:14px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="doPrint()" class="btn btn-primary">&#128424; In hoa don</button>
            <button type="button" onclick="closePrintModal()" class="btn btn-outline">Dong</button>
        </div>
    </div>
</div>

<script>
// Tu dong tinh tong tien
function tinhTongTien() {
    var tienphong = parseFloat(document.getElementById('tienphong').value) || 0;
    var tiendichvu = parseFloat(document.getElementById('tiendichvu').value) || 0;
    var phuthu = parseFloat(document.getElementById('phuthu').value) || 0;
    var giamgia = parseFloat(document.getElementById('giamgia').value) || 0;
    var tongtien = tienphong + tiendichvu + phuthu - giamgia;
    if (tongtien < 0) tongtien = 0;

    document.getElementById('tongtien').value = tongtien;

    var display = document.getElementById('tongTienDisplay');
    var valueEl = document.getElementById('tongTienValue');
    var detailEl = document.getElementById('tongTienDetail');

    var formatted = tongtien.toLocaleString('vi-VN') + ' VND';
    valueEl.textContent = formatted;
    detailEl.textContent = tienphong.toLocaleString('vi-VN') + ' + ' +
        tiendichvu.toLocaleString('vi-VN') + ' + ' +
        phuthu.toLocaleString('vi-VN') + ' - ' +
        giamgia.toLocaleString('vi-VN') + ' = ' + formatted;
    display.style.display = 'block';
}

// Tu dong dien tien phong khi chon dat phong
document.getElementById('datphongSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var giaban = parseFloat(selected.getAttribute('data-giaban')) || 0;
    if (giaban > 0 && !<?php echo $editItem ? 'true' : 'false'; ?>) {
        document.getElementById('tienphong').value = giaban;
        tinhTongTien();
    }
});

// Cap nhat thanh toan tu dong khi chuyen trang thai
document.getElementById('trangthaiSelect').addEventListener('change', function() {
    var trangthai = this.value;
    var tongtien = parseFloat(document.getElementById('tongtien').value) || 0;
    if (trangthai === 'da_thanh_toan') {
        document.getElementById('thanhtoan').value = tongtien;
    } else if (trangthai === 'da_huy') {
        document.getElementById('thanhtoan').value = 0;
    }
});

// Khoi chay tinh tong tien khi trang tai (sua)
window.addEventListener('DOMContentLoaded', function() {
    tinhTongTien();
});

// In hoa don
function printInvoice(id) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'hoadon.php?action=print&id=' + id, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            document.getElementById('printContent').innerHTML = xhr.responseText;
            document.getElementById('printModal').style.display = 'flex';
        }
    };
    xhr.send();
}

function closePrintModal() {
    document.getElementById('printModal').style.display = 'none';
}

function doPrint() {
    var content = document.getElementById('printContent').innerHTML;
    var win = window.open('', '_blank', 'width=800,height=600');
    win.document.write('<!DOCTYPE html><html><head><title>In hoa don</title>');
    win.document.write('<style>body{font-family:Segoe UI,sans-serif;padding:30px;color:#1e293b;}');
    win.document.write('table{width:100%;border-collapse:collapse;margin:16px 0;}');
    win.document.write('th,td{padding:10px 14px;border:1px solid #e2e8f0;text-align:left;font-size:13px;}');
    win.document.write('th{background:#0c4a6e;color:#fff;font-weight:600;}');
    win.document.write('h2{color:#0c4a6e;margin-bottom:4px;}');
    win.document.write('.text-right{text-align:right;}');
    win.document.write('.footer{margin-top:40px;text-align:center;color:#64748b;font-size:12px;}');
    win.document.write('</style></head><body>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}
</script>

<?php require_once 'footer.php'; ?>
