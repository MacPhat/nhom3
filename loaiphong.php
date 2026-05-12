<?php
$page_title = 'Quan ly loai phong';
require_once 'config.php';
checkLogin();

$message = '';

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Kiểm tra loại phòng có đang được sử dụng không
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM phong WHERE loaiphong_id = ?");
    $stmt->execute([$id]);
    $count = $stmt->fetchColumn();
    if ($count > 0) {
        $message = '<div class="alert alert-danger">Khong the xoa! Loai phong nay dang co ' . $count . ' phong lien ket.</div>';
    } else {
        $stmt = $pdo->prepare("DELETE FROM loaiphong WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message = '<div class="alert alert-success">Xoa loai phong thanh cong!</div>';
        } else {
            $message = '<div class="alert alert-danger">Xoa that bai!</div>';
        }
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $tenloai = trim($_POST['tenloai'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $giaban = (float)($_POST['giaban'] ?? 0);
    $sophong_max = (int)($_POST['sophong_max'] ?? 2);
    $tienich = trim($_POST['tienich'] ?? '');

    if ($tenloai === '') {
        $message = '<div class="alert alert-danger">Vui long nhap ten loai phong!</div>';
    } elseif ($giaban <= 0) {
        $message = '<div class="alert alert-danger">Gia ban phai lon hon 0!</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE loaiphong SET tenloai=?, mota=?, giaban=?, sophong_max=?, tienich=? WHERE id=?");
                $stmt->execute([$tenloai, $mota, $giaban, $sophong_max, $tienich, $id]);
                $message = '<div class="alert alert-success">Cap nhat loai phong thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO loaiphong (tenloai, mota, giaban, sophong_max, tienich) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$tenloai, $mota, $giaban, $sophong_max, $tienich]);
                $message = '<div class="alert alert-success">Them loai phong thanh cong!</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Loi: ' . $e->getMessage() . '</div>';
        }
    }
}

// Lấy dữ liệu sửa
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM loaiphong WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tìm kiếm
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE lp.tenloai LIKE ? OR lp.mota LIKE ? OR lp.tienich LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Lấy danh sách loại phòng với số phòng mỗi loại (subquery)
$sql = "SELECT lp.*,
        (SELECT COUNT(*) FROM phong WHERE loaiphong_id = lp.id) AS sophong_hientai,
        (SELECT COUNT(*) FROM phong WHERE loaiphong_id = lp.id AND trangthai = 'trong') AS sophong_trong,
        (SELECT COUNT(*) FROM phong WHERE loaiphong_id = lp.id AND trangthai = 'dang_thue') AS sophong_dangthue,
        (SELECT COUNT(*) FROM phong WHERE loaiphong_id = lp.id AND trangthai = 'bao_tri') AS sophong_baotri
        FROM loaiphong lp $where ORDER BY lp.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsLoaiPhong = $stmt->fetchAll();

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Form thêm/sửa loại phòng -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua loai phong' : 'Them loai phong moi'; ?></h3>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ten loai phong <span style="color:red">*</span></label>
                <input type="text" name="tenloai" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['tenloai']) : ''; ?>" required placeholder="Nhap ten loai phong...">
            </div>
            <div class="form-group">
                <label>Gia ban (VND) <span style="color:red">*</span></label>
                <input type="number" name="giaban" class="form-control" min="0" step="1000" value="<?php echo $editItem ? $editItem['giaban'] : ''; ?>" required placeholder="VD: 500000">
            </div>
            <div class="form-group">
                <label>So phong toi da</label>
                <input type="number" name="sophong_max" class="form-control" min="1" max="100" value="<?php echo $editItem ? $editItem['sophong_max'] : 2; ?>" placeholder="So phong toi da">
            </div>
            <div class="form-group">
                <label>Tien ich <span class="text-muted">(cach nhau bang dau phay)</span></label>
                <input type="text" name="tienich" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['tienich'] ?? '') : ''; ?>" placeholder="VD: WiFi, TV, Dieu hoa, Minibar">
            </div>
        </div>
        <div class="form-group">
            <label>Mo ta</label>
            <textarea name="mota" class="form-control" rows="3" placeholder="Mo ta chi tiet ve loai phong..."><?php echo $editItem ? htmlspecialchars($editItem['mota'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="loaiphong.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sách loại phòng -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach loai phong (<?php echo count($dsLoaiPhong); ?>)</h3>
    </div>
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem loai phong..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="loaiphong.php" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ten loai phong</th>
                    <th>Mo ta</th>
                    <th>Gia ban</th>
                    <th>So phong</th>
                    <th>Trang thai</th>
                    <th>Tien ich</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsLoaiPhong)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon">&#9733;</div>
                                <h4>Khong co loai phong nao</h4>
                                <p><?php echo $search ? 'Khong tim thay ket qua cho "' . htmlspecialchars($search) . '"' : 'Nhan "Them moi" de tao loai phong dau tien'; ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsLoaiPhong as $lp): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($lp['tenloai']); ?></strong></td>
                        <td>
                            <?php
                            $mota = $lp['mota'] ?? '';
                            echo htmlspecialchars(mb_strlen($mota) > 80 ? mb_substr($mota, 0, 80) . '...' : $mota);
                            ?>
                        </td>
                        <td><?php echo formatMoney($lp['giaban']); ?></td>
                        <td>
                            <?php echo $lp['sophong_hientai']; ?>/<?php echo $lp['sophong_max']; ?>
                        </td>
                        <td>
                            <?php
                            $trong = (int)$lp['sophong_trong'];
                            $dangthue = (int)$lp['sophong_dangthue'];
                            $baotri = (int)$lp['sophong_baotri'];
                            if ($trong > 0) {
                                echo getStatusBadge('trong', 'phong') . ' ';
                            }
                            if ($dangthue > 0) {
                                echo getStatusBadge('dang_thue', 'phong') . ' ';
                            }
                            if ($baotri > 0) {
                                echo getStatusBadge('bao_tri', 'phong');
                            }
                            if ($trong === 0 && $dangthue === 0 && $baotri === 0) {
                                echo '<span class="badge badge-secondary">Chua co phong</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $tienich = $lp['tienich'] ?? '';
                            if ($tienich !== '') {
                                $items = explode(',', $tienich);
                                $display = array_slice($items, 0, 3);
                                foreach ($display as $item) {
                                    echo '<span class="badge badge-info">' . htmlspecialchars(trim($item)) . '</span> ';
                                }
                                if (count($items) > 3) {
                                    echo '<span class="badge badge-secondary">+' . (count($items) - 3) . '</span>';
                                }
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="loaiphong.php?edit=<?php echo $lp['id']; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="loaiphong.php?delete=<?php echo $lp['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa loai phong <?php echo htmlspecialchars(addslashes($lp['tenloai'])); ?>?')">Xoa</a>
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
