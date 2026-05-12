<?php
$page_title = 'Quan ly nhan vien';
require_once 'config.php';
checkLogin();

$message = '';

// Xoa nhan vien
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM nhanvien WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Xoa nhan vien thanh cong!</div>';
        } else {
            $message = '<div class="alert alert-danger">Xoa that bai!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Khong the xoa! Nhan vien nay dang duoc su dung trong he thong.</div>';
    }
}

// Them/Sua nhan vien
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $manv = trim($_POST['manv'] ?? '');
    $hoten = trim($_POST['hoten'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $chucvu = trim($_POST['chucvu'] ?? '');
    $phongban = trim($_POST['phongban'] ?? '');
    $luong = (float)($_POST['luong'] ?? 0);
    $ngaysinh = $_POST['ngaysinh'] ?? '';
    $gioitinh = $_POST['gioitinh'] ?? 'Nam';
    $ngvaolam = $_POST['ngvaolam'] ?? '';
    $trangthai = $_POST['trangthai'] ?? 'hoat_dong';

    // Validate
    $errors = [];
    if ($manv === '') {
        $errors[] = 'Ma nhan vien khong duoc de trong!';
    }
    if ($hoten === '') {
        $errors[] = 'Ho ten khong duoc de trong!';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email khong hop le!';
    }

    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE nhanvien SET manv=?, hoten=?, sdt=?, email=?, diachi=?, chucvu=?, phongban=?, luong=?, ngaysinh=?, gioitinh=?, ngvaolam=?, trangthai=? WHERE id=?");
                $stmt->execute([$manv, $hoten, $sdt, $email, $diachi, $chucvu, $phongban, $luong, $ngaysinh ?: null, $gioitinh, $ngvaolam ?: null, $trangthai, $id]);
                $message = '<div class="alert alert-success">Cap nhat nhan vien thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO nhanvien (manv, hoten, sdt, email, diachi, chucvu, phongban, luong, ngaysinh, gioitinh, ngvaolam, trangthai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$manv, $hoten, $sdt, $email, $diachi, $chucvu, $phongban, $luong, $ngaysinh ?: null, $gioitinh, $ngvaolam ?: null, $trangthai]);
                $message = '<div class="alert alert-success">Them nhan vien thanh cong!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma nhan vien da ton tai!</div>';
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
    $stmt = $pdo->prepare("SELECT * FROM nhanvien WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tim kiem va loc
$search = trim($_GET['search'] ?? '');
$filter_trangthai = $_GET['filter_trangthai'] ?? '';

$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (manv LIKE ? OR hoten LIKE ? OR chucvu LIKE ? OR phongban LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_trangthai !== '') {
    $where .= " AND trangthai = ?";
    $params[] = $filter_trangthai;
}

// Danh sach nhan vien
$sql = "SELECT * FROM nhanvien $where ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsNhanVien = $stmt->fetchAll();

// Thong ke
$tongNhanVien = $pdo->query("SELECT COUNT(*) FROM nhanvien")->fetchColumn();
$nhanVienHoatDong = $pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='hoat_dong'")->fetchColumn();
$nhanVienNghiViec = $pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='nghi_viec'")->fetchColumn();
$soPhongBan = $pdo->query("SELECT COUNT(DISTINCT phongban) FROM nhanvien WHERE phongban IS NOT NULL AND phongban != ''")->fetchColumn();

// Lay danh sach phong ban cho dropdown
$dsPhongBan = $pdo->query("SELECT DISTINCT phongban FROM nhanvien WHERE phongban IS NOT NULL AND phongban != '' ORDER BY phongban ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thong ke nhanh -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#9881;</div>
        <div class="stat-info">
            <h4><?php echo $tongNhanVien; ?></h4>
            <p>Tong nhan vien</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-info">
            <h4><?php echo $nhanVienHoatDong; ?></h4>
            <p>Dang hoat dong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#10007;</div>
        <div class="stat-info">
            <h4><?php echo $nhanVienNghiViec; ?></h4>
            <p>Nghi viec</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#9733;</div>
        <div class="stat-info">
            <h4><?php echo $soPhongBan; ?></h4>
            <p>Phong ban</p>
        </div>
    </div>
</div>

<!-- Form Them/Sua nhan vien -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua thong tin nhan vien' : 'Them nhan vien moi'; ?></h3>
        <?php if ($editItem): ?>
            <a href="nhanvien.php" class="btn btn-outline btn-sm">Huy sua</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma nhan vien <span style="color:red">*</span></label>
                <input type="text" name="manv" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['manv']) : generateCode('NV', 'nhanvien'); ?>" required>
            </div>
            <div class="form-group">
                <label>Ho ten <span style="color:red">*</span></label>
                <input type="text" name="hoten" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['hoten']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>So dien thoai</label>
                <input type="text" name="sdt" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['sdt'] ?? '') : ''; ?>" placeholder="So dien thoai lien he">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['email'] ?? '') : ''; ?>" placeholder="Dia chi email">
            </div>
            <div class="form-group">
                <label>Chuc vu</label>
                <input type="text" name="chucvu" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['chucvu'] ?? '') : ''; ?>" placeholder="VD: Le tan, Quan ly, Bep...">
            </div>
            <div class="form-group">
                <label>Phong ban</label>
                <input type="text" name="phongban" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['phongban'] ?? '') : ''; ?>" placeholder="VD: Tiep tan, Bu phong, Ke toan..." list="phongban_list">
                <datalist id="phongban_list">
                    <?php foreach ($dsPhongBan as $pb): ?>
                    <option value="<?php echo htmlspecialchars($pb); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="form-group">
                <label>Luong (VND)</label>
                <input type="number" name="luong" class="form-control" min="0" step="100000" value="<?php echo $editItem ? $editItem['luong'] : 0; ?>" placeholder="Muc luong">
            </div>
            <div class="form-group">
                <label>Gioi tinh</label>
                <select name="gioitinh" class="form-control">
                    <option value="Nam" <?php echo (!$editItem || $editItem['gioitinh'] === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                    <option value="Nu" <?php echo ($editItem && $editItem['gioitinh'] === 'Nu') ? 'selected' : ''; ?>>Nu</option>
                    <option value="Khac" <?php echo ($editItem && $editItem['gioitinh'] === 'Khac') ? 'selected' : ''; ?>>Khac</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ngay sinh</label>
                <input type="date" name="ngaysinh" class="form-control" value="<?php echo $editItem ? formatDateInput($editItem['ngaysinh'] ?? '') : ''; ?>">
            </div>
            <div class="form-group">
                <label>Ngay vao lam</label>
                <input type="date" name="ngvaolam" class="form-control" value="<?php echo $editItem ? formatDateInput($editItem['ngvaolam'] ?? '') : ''; ?>">
            </div>
            <div class="form-group">
                <label>Trang thai</label>
                <select name="trangthai" class="form-control">
                    <option value="hoat_dong" <?php echo (!$editItem || $editItem['trangthai'] === 'hoat_dong') ? 'selected' : ''; ?>>Hoat dong</option>
                    <option value="nghi_viec" <?php echo ($editItem && $editItem['trangthai'] === 'nghi_viec') ? 'selected' : ''; ?>>Nghi viec</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Dia chi</label>
            <textarea name="diachi" class="form-control" rows="2" placeholder="Dia chi lien he"><?php echo $editItem ? htmlspecialchars($editItem['diachi'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="nhanvien.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sach nhan vien -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach nhan vien (<?php echo $tongNhanVien; ?>)</h3>
        <?php if ($search || $filter_trangthai): ?>
            <a href="nhanvien.php" class="btn btn-outline btn-sm">Xoa bo loc</a>
        <?php endif; ?>
    </div>

    <!-- Tim kiem va loc -->
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem theo ma, ho ten, chuc vu, phong ban..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:360px;">
            <div class="filter-group">
                <select name="filter_trangthai" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca trang thai --</option>
                    <option value="hoat_dong" <?php echo $filter_trangthai === 'hoat_dong' ? 'selected' : ''; ?>>Hoat dong</option>
                    <option value="nghi_viec" <?php echo $filter_trangthai === 'nghi_viec' ? 'selected' : ''; ?>>Nghi viec</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="nhanvien.php<?php echo $filter_trangthai ? '?filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma NV</th>
                    <th>Ho ten</th>
                    <th>Gioi tinh</th>
                    <th>Ngay sinh</th>
                    <th>Chuc vu</th>
                    <th>Phong ban</th>
                    <th>SDT</th>
                    <th>Luong</th>
                    <th>Ngay vao lam</th>
                    <th>Trang thai</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsNhanVien)): ?>
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <div class="empty-icon">&#9881;</div>
                                <h4>Khong co nhan vien nao</h4>
                                <p>Khong tim thay nhan vien nao voi tieu chi tim kiem hien tai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsNhanVien as $nv): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($nv['manv']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($nv['hoten']); ?>
                            <?php if (!empty($nv['email'])): ?>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($nv['email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($nv['gioitinh'] === 'Nu') echo '<span class="badge badge-info">Nu</span>';
                            elseif ($nv['gioitinh'] === 'Khac') echo '<span class="badge badge-secondary">Khac</span>';
                            else echo '<span class="badge badge-primary">Nam</span>';
                            ?>
                        </td>
                        <td><?php echo formatDate($nv['ngaysinh']); ?></td>
                        <td><?php echo htmlspecialchars($nv['chucvu'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($nv['phongban'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($nv['sdt'] ?? ''); ?></td>
                        <td><?php echo formatMoney($nv['luong']); ?></td>
                        <td><?php echo formatDate($nv['ngvaolam']); ?></td>
                        <td><?php echo getStatusBadge($nv['trangthai'], 'nhanvien'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="nhanvien.php?edit=<?php echo $nv['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="nhanvien.php?delete=<?php echo $nv['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa nhan vien <?php echo htmlspecialchars(addslashes($nv['hoten'])); ?>?')">Xoa</a>
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
