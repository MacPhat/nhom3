<?php
$page_title = 'Quan ly khach hang';
require_once 'config.php';
checkLogin();

$message = '';

// Xử lý xóa khách hàng
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Kiểm tra xem khách hàng có đặt phòng không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM datphong WHERE khachhang_id = ?");
    $stmt->execute([$id]);
    $bookingCount = $stmt->fetchColumn();
    if ($bookingCount > 0) {
        $message = '<div class="alert alert-danger">Khong the xoa! Khach hang nay dang co ' . $bookingCount . ' dat phong trong he thong.</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM khachhang WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Xoa khach hang thanh cong!</div>';
        } else {
            $message = '<div class="alert alert-danger">Xoa that bai!</div>';
        }
    }
}

// Xử lý thêm/sửa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $makh = trim($_POST['makh'] ?? '');
    $hoten = trim($_POST['hoten'] ?? '');
    $cmnd = trim($_POST['cmnd'] ?? '');
    $sdt = trim($_POST['sdt'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $diachi = trim($_POST['diachi'] ?? '');
    $gioitinh = $_POST['gioitinh'] ?? 'Nam';
    $ngaysinh = $_POST['ngaysinh'] ?? '';
    $quoctich = trim($_POST['quoctich'] ?? '');
    $ghichu = trim($_POST['ghichu'] ?? '');

    // Validate
    $errors = [];
    if ($makh === '') {
        $errors[] = 'Ma khach hang khong duoc de trong!';
    }
    if ($hoten === '') {
        $errors[] = 'Ho ten khong duoc de trong!';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email khong hop le!';
    }
    if ($cmnd !== '') {
        // Kiểm tra trùng CMND
        $checkSql = "SELECT id FROM khachhang WHERE cmnd = ?";
        $checkParams = [$cmnd];
        if ($id > 0) {
            $checkSql .= " AND id != ?";
            $checkParams[] = $id;
        }
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute($checkParams);
        if ($stmt->fetch()) {
            $errors[] = 'So CMND/CCCD nay da ton tai!';
        }
    }

    if (!empty($errors)) {
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE khachhang SET makh=?, hoten=?, cmnd=?, sdt=?, email=?, diachi=?, gioitinh=?, ngaysinh=?, quoctich=?, ghichu=? WHERE id=?");
                $stmt->execute([$makh, $hoten, $cmnd, $sdt, $email, $diachi, $gioitinh, $ngaysinh ?: null, $quoctich, $ghichu, $id]);
                $message = '<div class="alert alert-success">Cap nhat khach hang thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO khachhang (makh, hoten, cmnd, sdt, email, diachi, gioitinh, ngaysinh, quoctich, ghichu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$makh, $hoten, $cmnd, $sdt, $email, $diachi, $gioitinh, $ngaysinh ?: null, $quoctich, $ghichu]);
                $message = '<div class="alert alert-success">Them khach hang thanh cong!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma khach hang da ton tai!</div>';
            } else {
                $message = '<div class="alert alert-danger">Loi: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
    }
}

// Lấy thông tin khách hàng để sửa
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM khachhang WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tìm kiếm
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE k.makh LIKE ? OR k.hoten LIKE ? OR k.sdt LIKE ? OR k.cmnd LIKE ? OR k.email LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like, $like, $like];
}

// Lấy danh sách khách hàng kèm số lượt đặt phòng (subquery)
$sql = "SELECT k.*,
            (SELECT COUNT(*) FROM datphong dp WHERE dp.khachhang_id = k.id) AS solandatphong
        FROM khachhang k
        $where
        ORDER BY k.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsKhach = $stmt->fetchAll();

// Thống kê tổng khách hàng
$totalKhach = $pdo->query("SELECT COUNT(*) FROM khachhang")->fetchColumn();

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thống kê nhanh -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-value"><?php echo $totalKhach; ?></div>
        <div class="stat-label">Tong khach hang</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM khachhang WHERE gioitinh = 'Nam'")->fetchColumn(); ?></div>
        <div class="stat-label">Khach nam</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM khachhang WHERE gioitinh = 'Nu'")->fetchColumn(); ?></div>
        <div class="stat-label">Khach nu</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $pdo->query("SELECT COUNT(*) FROM khachhang k INNER JOIN datphong dp ON dp.khachhang_id = k.id")->fetchColumn(); ?></div>
        <div class="stat-label">Co dat phong</div>
    </div>
</div>

<!-- Form thêm/sửa khách hàng -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua thong tin khach hang' : 'Them khach hang moi'; ?></h3>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma khach hang <span style="color:red">*</span></label>
                <input type="text" name="makh" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['makh']) : generateCode('KH', 'khachhang'); ?>" required>
            </div>
            <div class="form-group">
                <label>Ho ten <span style="color:red">*</span></label>
                <input type="text" name="hoten" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['hoten']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>CMND/CCCD</label>
                <input type="text" name="cmnd" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['cmnd'] ?? '') : ''; ?>" placeholder="So chung minh nhan dan">
            </div>
            <div class="form-group">
                <label>So dien thoai</label>
                <input type="text" name="sdt" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['sdt'] ?? '') : ''; ?>" placeholder="So dien thoai">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['email'] ?? '') : ''; ?>" placeholder="Dia chi email">
            </div>
            <div class="form-group">
                <label>Gioi tinh</label>
                <select name="gioitinh" class="form-control">
                    <option value="Nam" <?php echo ($editItem && $editItem['gioitinh'] === 'Nam') ? 'selected' : ''; ?>>Nam</option>
                    <option value="Nu" <?php echo ($editItem && $editItem['gioitinh'] === 'Nu') ? 'selected' : ''; ?>>Nu</option>
                    <option value="Khac" <?php echo ($editItem && $editItem['gioitinh'] === 'Khac') ? 'selected' : ''; ?>>Khac</option>
                </select>
            </div>
            <div class="form-group">
                <label>Ngay sinh</label>
                <input type="date" name="ngaysinh" class="form-control" value="<?php echo $editItem ? formatDateInput($editItem['ngaysinh'] ?? '') : ''; ?>">
            </div>
            <div class="form-group">
                <label>Quoc tich</label>
                <input type="text" name="quoctich" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['quoctich'] ?? '') : 'Viet Nam'; ?>" placeholder="Quoc tich">
            </div>
        </div>
        <div class="form-group">
            <label>Dia chi</label>
            <textarea name="diachi" class="form-control" rows="2" placeholder="Dia chi liend he"><?php echo $editItem ? htmlspecialchars($editItem['diachi'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-group">
            <label>Ghi chu</label>
            <textarea name="ghichu" class="form-control" rows="2" placeholder="Ghi chu them (neu co)"><?php echo $editItem ? htmlspecialchars($editItem['ghichu'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="khachhang.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sách khách hàng -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach khach hang (<?php echo $totalKhach; ?>)</h3>
    </div>
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem theo ma, ho ten, SĐT, CMND, email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="khachhang.php" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma KH</th>
                    <th>Ho ten</th>
                    <th>Gioi tinh</th>
                    <th>Ngay sinh</th>
                    <th>CMND</th>
                    <th>SDT</th>
                    <th>Email</th>
                    <th>Quoc tich</th>
                    <th>Dat phong</th>
                    <th>Ngay tao</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsKhach)): ?>
                    <tr><td colspan="12" style="text-align:center;">Khong co khach hang nao<?php echo $search ? ' phoi voi tu khoa "' . htmlspecialchars($search) . '"' : ''; ?></td></tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsKhach as $kh): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($kh['makh']); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($kh['hoten']); ?>
                            <?php if (!empty($kh['ghichu'])): ?>
                                <br><small style="color:#888;" title="<?php echo htmlspecialchars($kh['ghichu']); ?>">(<?php echo htmlspecialchars(mb_substr($kh['ghichu'], 0, 30)); ?>...)</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            if ($kh['gioitinh'] === 'Nu') echo '<span class="badge badge-info">Nu</span>';
                            elseif ($kh['gioitinh'] === 'Khac') echo '<span class="badge badge-secondary">Khac</span>';
                            else echo '<span class="badge badge-primary">Nam</span>';
                            ?>
                        </td>
                        <td><?php echo formatDate($kh['ngaysinh']); ?></td>
                        <td><?php echo htmlspecialchars($kh['cmnd'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($kh['sdt'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($kh['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($kh['quoctich'] ?? 'Viet Nam'); ?></td>
                        <td style="text-align:center;">
                            <?php
                            $soLan = (int)$kh['solandatphong'];
                            if ($soLan > 0) {
                                echo '<span class="badge badge-success">' . $soLan . '</span>';
                            } else {
                                echo '<span class="badge badge-secondary">0</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo formatDate($kh['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="khachhang.php?edit=<?php echo $kh['id']; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="khachhang.php?delete=<?php echo $kh['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Ban co chac muon xoa khach hang <?php echo htmlspecialchars(addslashes($kh['hoten'])); ?>?')">Xoa</a>
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
