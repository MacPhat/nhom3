<?php
$page_title = 'Quan ly phong';
require_once 'config.php';
checkLogin();

$message = '';

// Xoa phong
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM phong WHERE id = ?");
        $stmt->execute([$id]);
        $message = '<div class="alert alert-success">Xoa phong thanh cong!</div>';
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Xoa phong that bai! Phong dang duoc su dung trong dat phong.</div>';
    }
}

// Them/Sua phong
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $maphong = trim($_POST['maphong'] ?? '');
    $tenphong = trim($_POST['tenphong'] ?? '');
    $loaiphong_id = (int)($_POST['loaiphong_id'] ?? 0);
    $tang = (int)($_POST['tang'] ?? 1);
    $trangthai = $_POST['trangthai'] ?? 'trong';
    $mota = trim($_POST['mota'] ?? '');

    if ($maphong === '' || $tenphong === '' || $loaiphong_id === 0) {
        $message = '<div class="alert alert-danger">Vui long dien day du thong tin bat buoc!</div>';
    } else {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE phong SET maphong=?, tenphong=?, loaiphong_id=?, tang=?, trangthai=?, mota=? WHERE id=?");
                $stmt->execute([$maphong, $tenphong, $loaiphong_id, $tang, $trangthai, $mota, $id]);
                $message = '<div class="alert alert-success">Cap nhat phong thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO phong (maphong, tenphong, loaiphong_id, tang, trangthai, mota) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$maphong, $tenphong, $loaiphong_id, $tang, $trangthai, $mota]);
                $message = '<div class="alert alert-success">Them phong thanh cong!</div>';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma phong da ton tai!</div>';
            } else {
                $message = '<div class="alert alert-danger">Loi: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// Lay du lieu sua
$editPhong = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM phong WHERE id = ?");
    $stmt->execute([$id]);
    $editPhong = $stmt->fetch();
}

// Tim kiem va loc
$search = trim($_GET['search'] ?? '');
$filter_loaiphong = $_GET['filter_loaiphong'] ?? '';
$filter_trangthai = $_GET['filter_trangthai'] ?? '';
$filter_tang = $_GET['filter_tang'] ?? '';

$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (p.maphong LIKE ? OR p.tenphong LIKE ? OR lp.tenloai LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_loaiphong !== '') {
    $where .= " AND p.loaiphong_id = ?";
    $params[] = (int)$filter_loaiphong;
}
if ($filter_trangthai !== '') {
    $where .= " AND p.trangthai = ?";
    $params[] = $filter_trangthai;
}
if ($filter_tang !== '') {
    $where .= " AND p.tang = ?";
    $params[] = (int)$filter_tang;
}

// Danh sach phong
$sql = "SELECT p.*, lp.tenloai, lp.giaban FROM phong p LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id $where ORDER BY p.tang ASC, p.maphong ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsPhong = $stmt->fetchAll();

// Danh sach loai phong cho dropdown
$dsLoaiPhong = $pdo->query("SELECT * FROM loaiphong ORDER BY giaban ASC")->fetchAll();

// Thong ke phong
$tongPhong = $pdo->query("SELECT COUNT(*) FROM phong")->fetchColumn();
$phongTrong = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='trong'")->fetchColumn();
$phongDangThue = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='dang_thue'")->fetchColumn();
$phongBaoTri = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='bao_tri'")->fetchColumn();
$phongDaDat = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='da_dat'")->fetchColumn();

// Lay danh sach tang
$dsTang = $pdo->query("SELECT DISTINCT tang FROM phong ORDER BY tang ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thong ke phong -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#9962;</div>
        <div class="stat-info">
            <h4><?php echo $tongPhong; ?></h4>
            <p>Tong so phong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10003;</div>
        <div class="stat-info">
            <h4><?php echo $phongTrong; ?></h4>
            <p>Phong trong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9201;</div>
        <div class="stat-info">
            <h4><?php echo $phongDangThue; ?></h4>
            <p>Dang thue</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#9998;</div>
        <div class="stat-info">
            <h4><?php echo $phongDaDat; ?></h4>
            <p>Da dat</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#9888;</div>
        <div class="stat-info">
            <h4><?php echo $phongBaoTri; ?></h4>
            <p>Bao tri</p>
        </div>
    </div>
</div>

<!-- Form Them/Sua phong -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editPhong ? 'Sua phong' : 'Them phong moi'; ?></h3>
        <?php if ($editPhong): ?>
            <a href="phong.php" class="btn btn-outline btn-sm">Huy sua</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="id" value="<?php echo $editPhong ? $editPhong['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma phong <span style="color:red">*</span></label>
                <input type="text" name="maphong" class="form-control" placeholder="VD: P101" value="<?php echo $editPhong ? htmlspecialchars($editPhong['maphong']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Ten phong <span style="color:red">*</span></label>
                <input type="text" name="tenphong" class="form-control" placeholder="VD: Phong 101" value="<?php echo $editPhong ? htmlspecialchars($editPhong['tenphong']) : ''; ?>" required>
            </div>
            <div class="form-group">
                <label>Loai phong <span style="color:red">*</span></label>
                <select name="loaiphong_id" class="form-control" required>
                    <option value="">-- Chon loai phong --</option>
                    <?php foreach ($dsLoaiPhong as $lp): ?>
                    <option value="<?php echo $lp['id']; ?>" <?php echo ($editPhong && $editPhong['loaiphong_id'] == $lp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lp['tenloai'] . ' - ' . formatMoney($lp['giaban'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tang</label>
                <input type="number" name="tang" class="form-control" min="1" max="50" value="<?php echo $editPhong ? $editPhong['tang'] : 1; ?>">
            </div>
            <div class="form-group">
                <label>Trang thai</label>
                <select name="trangthai" class="form-control">
                    <option value="trong" <?php echo (!$editPhong || $editPhong['trangthai']==='trong') ? 'selected' : ''; ?>>Trong</option>
                    <option value="dang_thue" <?php echo ($editPhong && $editPhong['trangthai']==='dang_thue') ? 'selected' : ''; ?>>Dang thue</option>
                    <option value="bao_tri" <?php echo ($editPhong && $editPhong['trangthai']==='bao_tri') ? 'selected' : ''; ?>>Bao tri</option>
                    <option value="da_dat" <?php echo ($editPhong && $editPhong['trangthai']==='da_dat') ? 'selected' : ''; ?>>Da dat</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>Mo ta</label>
            <textarea name="mota" class="form-control" rows="3" placeholder="Mo ta chi tiet ve phong..."><?php echo $editPhong ? htmlspecialchars($editPhong['mota'] ?? '') : ''; ?></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editPhong ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editPhong): ?>
                <a href="phong.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sach phong -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach phong (<?php echo count($dsPhong); ?>)</h3>
        <?php if ($search || $filter_loaiphong || $filter_trangthai || $filter_tang): ?>
            <a href="phong.php" class="btn btn-outline btn-sm">Xoa bo loc</a>
        <?php endif; ?>
    </div>

    <!-- Tim kiem va loc -->
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem ma phong, ten phong, loai phong..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:320px;">
            <div class="filter-group">
                <select name="filter_loaiphong" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca loai phong --</option>
                    <?php foreach ($dsLoaiPhong as $lp): ?>
                    <option value="<?php echo $lp['id']; ?>" <?php echo $filter_loaiphong == $lp['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($lp['tenloai']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="filter_trangthai" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca trang thai --</option>
                    <option value="trong" <?php echo $filter_trangthai === 'trong' ? 'selected' : ''; ?>>Trong</option>
                    <option value="dang_thue" <?php echo $filter_trangthai === 'dang_thue' ? 'selected' : ''; ?>>Dang thue</option>
                    <option value="bao_tri" <?php echo $filter_trangthai === 'bao_tri' ? 'selected' : ''; ?>>Bao tri</option>
                    <option value="da_dat" <?php echo $filter_trangthai === 'da_dat' ? 'selected' : ''; ?>>Da dat</option>
                </select>
                <select name="filter_tang" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca tang --</option>
                    <?php foreach ($dsTang as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $filter_tang == $t ? 'selected' : ''; ?>>Tang <?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="phong.php" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma phong</th>
                    <th>Ten phong</th>
                    <th>Loai phong</th>
                    <th>Gia</th>
                    <th>Tang</th>
                    <th>Trang thai</th>
                    <th>Ngay tao</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsPhong)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-icon">&#9962;</div>
                                <h4>Khong co phong nao</h4>
                                <p>Khong tim thay phong nao voi tieu chi tim kiem hien tai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsPhong as $p): ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($p['maphong']); ?></strong></td>
                        <td><?php echo htmlspecialchars($p['tenphong']); ?></td>
                        <td><?php echo htmlspecialchars($p['tenloai']); ?></td>
                        <td><?php echo formatMoney($p['giaban']); ?></td>
                        <td>Tang <?php echo $p['tang']; ?></td>
                        <td><?php echo getStatusBadge($p['trangthai'], 'phong'); ?></td>
                        <td><?php echo formatDate($p['created_at']); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="phong.php?edit=<?php echo $p['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_loaiphong ? '&filter_loaiphong=' . urlencode($filter_loaiphong) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?><?php echo $filter_tang ? '&filter_tang=' . urlencode($filter_tang) : ''; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="phong.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa phong <?php echo htmlspecialchars(addslashes($p['maphong'])); ?>?')">Xoa</a>
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
