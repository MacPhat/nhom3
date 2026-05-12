<?php
$page_title = 'Quan ly dat phong';
require_once 'config.php';
checkLogin();

$message = '';

// Xoa dat phong
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Lay thong tin dat phong truoc khi xoa de cap nhat trang thai phong
        $stmt = $pdo->prepare("SELECT phong_id, trangthai FROM datphong WHERE id = ?");
        $stmt->execute([$id]);
        $delItem = $stmt->fetch();

        $stmt = $pdo->prepare("DELETE FROM datphong WHERE id = ?");
        if ($stmt->execute([$id])) {
            // Neu dat phong dang o trang thai da_nhan_phong hoac da_dat thi tra phong ve trong
            if ($delItem && ($delItem['trangthai'] === 'da_nhan_phong' || $delItem['trangthai'] === 'da_xac_nhan')) {
                // Kiem tra xem con dat phong nao khac cho phong nay khong
                $chk = $pdo->prepare("SELECT COUNT(*) FROM datphong WHERE phong_id = ? AND trangthai IN ('da_nhan_phong','da_xac_nhan','cho_xac_nhan') AND id != ?");
                $chk->execute([$delItem['phong_id'], $id]);
                if ($chk->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE phong SET trangthai='trong' WHERE id=?")->execute([$delItem['phong_id']]);
                }
            }
            // Gui thong bao cho nhan vien
            if (isset($_SESSION['user_id'])) {
                addNotification($_SESSION['user_id'], 'Xoa dat phong', 'Da xoa dat phong #' . $id . ' thanh cong.', 'he_thong');
            }
            $message = '<div class="alert alert-success">Xoa dat phong thanh cong!</div>';
        } else {
            $message = '<div class="alert alert-danger">Xoa that bai!</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Xoa that bai! Dat phong dang duoc su dung trong hoa don.</div>';
    }
}

// Them/Sua dat phong
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $madatphong = trim($_POST['madatphong'] ?? '');
    $khachhang_id = (int)($_POST['khachhang_id'] ?? 0);
    $phong_id = (int)($_POST['phong_id'] ?? 0);
    $nhanvien_id = (int)($_POST['nhanvien_id'] ?? 0) ?: null;
    $ngayden = $_POST['ngayden'] ?? '';
    $ngaydi = $_POST['ngaydi'] ?? '';
    $songuoi = (int)($_POST['songuoi'] ?? 1);
    $trangthai = $_POST['trangthai'] ?? 'cho_xac_nhan';
    $yeucau = trim($_POST['yeucau'] ?? '');
    $ghichu = trim($_POST['ghichu'] ?? '');

    if ($madatphong === '' || $khachhang_id === 0 || $phong_id === 0 || $ngayden === '' || $ngaydi === '') {
        $message = '<div class="alert alert-danger">Vui long dien day du thong tin bat buoc!</div>';
    } elseif (strtotime($ngaydi) <= strtotime($ngayden)) {
        $message = '<div class="alert alert-danger">Ngay di phai lon hon ngay den!</div>';
    } else {
        try {
            $pdo->beginTransaction();

            // Lay trang thai cu khi sua
            $oldTrangthai = null;
            $oldPhongId = null;
            if ($id > 0) {
                $stmtOld = $pdo->prepare("SELECT trangthai, phong_id FROM datphong WHERE id = ?");
                $stmtOld->execute([$id]);
                $oldRow = $stmtOld->fetch();
                if ($oldRow) {
                    $oldTrangthai = $oldRow['trangthai'];
                    $oldPhongId = $oldRow['phong_id'];
                }
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE datphong SET madatphong=?, khachhang_id=?, phong_id=?, nhanvien_id=?, ngayden=?, ngaydi=?, songuoi=?, trangthai=?, yeucau=?, ghichu=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$madatphong, $khachhang_id, $phong_id, $nhanvien_id, $ngayden, $ngaydi, $songuoi, $trangthai, $yeucau, $ghichu, $id]);
                $message = '<div class="alert alert-success">Cap nhat dat phong thanh cong!</div>';
            } else {
                $stmt = $pdo->prepare("INSERT INTO datphong (madatphong, khachhang_id, phong_id, nhanvien_id, ngayden, ngaydi, songuoi, trangthai, yeucau, ghichu) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$madatphong, $khachhang_id, $phong_id, $nhanvien_id, $ngayden, $ngaydi, $songuoi, $trangthai, $yeucau, $ghichu]);
                $message = '<div class="alert alert-success">Them dat phong thanh cong!</div>';
            }

            // Cap nhat trang thai phong tu dong theo trang thai dat phong
            if ($trangthai === 'da_nhan_phong') {
                $pdo->prepare("UPDATE phong SET trangthai='dang_thue' WHERE id=?")->execute([$phong_id]);
            } elseif ($trangthai === 'da_xac_nhan') {
                $pdo->prepare("UPDATE phong SET trangthai='da_dat' WHERE id=?")->execute([$phong_id]);
            } elseif ($trangthai === 'da_tra_phong' || $trangthai === 'da_huy') {
                $pdo->prepare("UPDATE phong SET trangthai='trong' WHERE id=?")->execute([$phong_id]);
            }

            // Neu doi phong khi sua, tra phong cu ve trang thai thich hop
            if ($id > 0 && $oldPhongId && $oldPhongId != $phong_id) {
                $chkOld = $pdo->prepare("SELECT COUNT(*) FROM datphong WHERE phong_id = ? AND trangthai IN ('da_nhan_phong','da_xac_nhan','cho_xac_nhan') AND id != ?");
                $chkOld->execute([$oldPhongId, $id]);
                if ($chkOld->fetchColumn() == 0) {
                    $pdo->prepare("UPDATE phong SET trangthai='trong' WHERE id=?")->execute([$oldPhongId]);
                }
            }

            // Gui thong bao
            if (isset($_SESSION['user_id'])) {
                $thongBaoMsg = $id > 0 ? 'Cap nhat dat phong ' . $madatphong : 'Them moi dat phong ' . $madatphong;
                addNotification($_SESSION['user_id'], 'Dat phong', $thongBaoMsg, 'he_thong');
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $message = '<div class="alert alert-danger">Loi: Ma dat phong da ton tai!</div>';
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
    $stmt = $pdo->prepare("SELECT * FROM datphong WHERE id = ?");
    $stmt->execute([$id]);
    $editItem = $stmt->fetch();
}

// Tim kiem va loc
$search = trim($_GET['search'] ?? '');
$filter_trangthai = $_GET['filter_trangthai'] ?? '';

$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= " AND (dp.madatphong LIKE ? OR kh.hoten LIKE ? OR p.tenphong LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_trangthai !== '') {
    $where .= " AND dp.trangthai = ?";
    $params[] = $filter_trangthai;
}

// Lay danh sach dat phong voi gia phong tu loaiphong
$sql = "SELECT dp.*, kh.hoten as tenkh, p.tenphong, p.maphong, nv.hoten as tennv, lp.giaban, lp.tenloai
        FROM datphong dp
        LEFT JOIN khachhang kh ON dp.khachhang_id = kh.id
        LEFT JOIN phong p ON dp.phong_id = p.id
        LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id
        LEFT JOIN nhanvien nv ON dp.nhanvien_id = nv.id
        $where ORDER BY dp.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dsDatPhong = $stmt->fetchAll();

// Lay danh sach cho dropdown
$dsKhachHang = $pdo->query("SELECT id, makh, hoten FROM khachhang ORDER BY hoten")->fetchAll();
$dsPhong = $pdo->query("SELECT p.id, p.maphong, p.tenphong, p.trangthai, lp.tenloai, lp.giaban FROM phong p LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id ORDER BY p.tenphong")->fetchAll();
$dsNhanVien = $pdo->query("SELECT id, manv, hoten FROM nhanvien ORDER BY hoten")->fetchAll();

// Thong ke dat phong
$tongDatPhong = $pdo->query("SELECT COUNT(*) FROM datphong")->fetchColumn();
$choXacNhan = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='cho_xac_nhan'")->fetchColumn();
$daXacNhan = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='da_xac_nhan'")->fetchColumn();
$daNhanPhong = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='da_nhan_phong'")->fetchColumn();
$daTraPhong = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='da_tra_phong'")->fetchColumn();
$daHuy = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='da_huy'")->fetchColumn();

// Tao ma dat phong tu dong
$maDPDefault = generateCode('DP', 'datphong');

require_once 'header.php';
?>

<?php echo $message; ?>

<!-- Thong ke dat phong -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">&#9998;</div>
        <div class="stat-info">
            <h4><?php echo $tongDatPhong; ?></h4>
            <p>Tong dat phong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9203;</div>
        <div class="stat-info">
            <h4><?php echo $choXacNhan; ?></h4>
            <p>Cho xac nhan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#10003;</div>
        <div class="stat-info">
            <h4><?php echo $daXacNhan; ?></h4>
            <p>Da xac nhan</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">&#9962;</div>
        <div class="stat-info">
            <h4><?php echo $daNhanPhong; ?></h4>
            <p>Da nhan phong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">&#10004;</div>
        <div class="stat-info">
            <h4><?php echo $daTraPhong; ?></h4>
            <p>Da tra phong</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#10008;</div>
        <div class="stat-info">
            <h4><?php echo $daHuy; ?></h4>
            <p>Da huy</p>
        </div>
    </div>
</div>

<!-- Form Them/Sua dat phong -->
<div class="card">
    <div class="card-header">
        <h3><?php echo $editItem ? 'Sua dat phong' : 'Them dat phong moi'; ?></h3>
        <?php if ($editItem): ?>
            <a href="datphong.php" class="btn btn-outline btn-sm">Huy sua</a>
        <?php endif; ?>
    </div>
    <form method="POST" action="" id="formDatPhong">
        <input type="hidden" name="id" value="<?php echo $editItem ? $editItem['id'] : 0; ?>">
        <div class="form-grid">
            <div class="form-group">
                <label>Ma dat phong <span style="color:red">*</span></label>
                <input type="text" name="madatphong" class="form-control" value="<?php echo $editItem ? htmlspecialchars($editItem['madatphong']) : htmlspecialchars($maDPDefault); ?>" required>
            </div>
            <div class="form-group">
                <label>Khach hang <span style="color:red">*</span></label>
                <select name="khachhang_id" class="form-control" required>
                    <option value="">-- Chon khach hang --</option>
                    <?php foreach ($dsKhachHang as $kh): ?>
                    <option value="<?php echo $kh['id']; ?>" <?php echo ($editItem && $editItem['khachhang_id'] == $kh['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($kh['hoten'] . ' (' . $kh['makh'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Phong <span style="color:red">*</span></label>
                <select name="phong_id" class="form-control" id="phongSelect" required>
                    <option value="">-- Chon phong --</option>
                    <?php foreach ($dsPhong as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-giaban="<?php echo $p['giaban']; ?>" <?php echo ($editItem && $editItem['phong_id'] == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['tenphong'] . ' (' . $p['maphong'] . ') - ' . $p['tenloai'] . ' - ' . formatMoney($p['giaban'])); ?>
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
                <label>Ngay den <span style="color:red">*</span></label>
                <input type="date" name="ngayden" id="ngayden" class="form-control" value="<?php echo $editItem ? formatDateInput($editItem['ngayden']) : ''; ?>" required onchange="tinhTien()">
            </div>
            <div class="form-group">
                <label>Ngay di <span style="color:red">*</span></label>
                <input type="date" name="ngaydi" id="ngaydi" class="form-control" value="<?php echo $editItem ? formatDateInput($editItem['ngaydi']) : ''; ?>" required onchange="tinhTien()">
            </div>
            <div class="form-group">
                <label>So nguoi</label>
                <input type="number" name="songuoi" class="form-control" min="1" max="20" value="<?php echo $editItem ? $editItem['songuoi'] : 1; ?>">
            </div>
            <div class="form-group">
                <label>Trang thai</label>
                <select name="trangthai" class="form-control" id="trangthaiSelect">
                    <option value="cho_xac_nhan" <?php echo ($editItem && $editItem['trangthai']==='cho_xac_nhan') ? 'selected' : ''; ?>>Cho xac nhan</option>
                    <option value="da_xac_nhan" <?php echo ($editItem && $editItem['trangthai']==='da_xac_nhan') ? 'selected' : ''; ?>>Da xac nhan</option>
                    <option value="da_nhan_phong" <?php echo ($editItem && $editItem['trangthai']==='da_nhan_phong') ? 'selected' : ''; ?>>Da nhan phong</option>
                    <option value="da_tra_phong" <?php echo ($editItem && $editItem['trangthai']==='da_tra_phong') ? 'selected' : ''; ?>>Da tra phong</option>
                    <option value="da_huy" <?php echo ($editItem && $editItem['trangthai']==='da_huy') ? 'selected' : ''; ?>>Da huy</option>
                </select>
            </div>
        </div>
        <div class="form-grid">
            <div class="form-group">
                <label>Yeu cau</label>
                <textarea name="yeucau" class="form-control" rows="3" placeholder="Yeu cau dac biet cua khach hang..."><?php echo $editItem ? htmlspecialchars($editItem['yeucau'] ?? '') : ''; ?></textarea>
            </div>
            <div class="form-group">
                <label>Ghi chu</label>
                <textarea name="ghichu" class="form-control" rows="3" placeholder="Ghi chu noi bo..."><?php echo $editItem ? htmlspecialchars($editItem['ghichu'] ?? '') : ''; ?></textarea>
            </div>
        </div>
        <!-- Hien thi tong tien du kien -->
        <div class="form-group" id="tongTienDisplay" style="display:none;">
            <div style="background:var(--card-bg,#f8f9fa);border:1px solid var(--border,#dee2e6);border-radius:8px;padding:15px;margin-bottom:10px;">
                <strong>Tong tien du kien:</strong>
                <span id="tongTienValue" style="font-size:1.2em;color:#2ecc71;margin-left:10px;"></span>
                <span id="tongTienDetail" style="display:block;font-size:0.9em;color:#666;margin-top:5px;"></span>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?php echo $editItem ? 'Cap nhat' : 'Them moi'; ?></button>
            <?php if ($editItem): ?>
                <a href="datphong.php" class="btn btn-danger">Huy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Danh sach dat phong -->
<div class="card">
    <div class="card-header">
        <h3>Danh sach dat phong (<?php echo count($dsDatPhong); ?>)</h3>
        <?php if ($search || $filter_trangthai): ?>
            <a href="datphong.php" class="btn btn-outline btn-sm">Xoa bo loc</a>
        <?php endif; ?>
    </div>

    <!-- Tim kiem va loc -->
    <div class="search-bar">
        <form method="GET" action="" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center; width:100%;">
            <input type="text" name="search" class="form-control" placeholder="Tim kiem ma dat phong, ten khach hang, ten phong..." value="<?php echo htmlspecialchars($search); ?>" style="max-width:320px;">
            <div class="filter-group">
                <select name="filter_trangthai" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Tat ca trang thai --</option>
                    <option value="cho_xac_nhan" <?php echo $filter_trangthai === 'cho_xac_nhan' ? 'selected' : ''; ?>>Cho xac nhan</option>
                    <option value="da_xac_nhan" <?php echo $filter_trangthai === 'da_xac_nhan' ? 'selected' : ''; ?>>Da xac nhan</option>
                    <option value="da_nhan_phong" <?php echo $filter_trangthai === 'da_nhan_phong' ? 'selected' : ''; ?>>Da nhan phong</option>
                    <option value="da_tra_phong" <?php echo $filter_trangthai === 'da_tra_phong' ? 'selected' : ''; ?>>Da tra phong</option>
                    <option value="da_huy" <?php echo $filter_trangthai === 'da_huy' ? 'selected' : ''; ?>>Da huy</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Tim</button>
            <?php if ($search): ?>
                <a href="datphong.php" class="btn btn-danger">Xoa tim</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>STT</th>
                    <th>Ma dat phong</th>
                    <th>Khach hang</th>
                    <th>Phong</th>
                    <th>Loai phong</th>
                    <th>Nhan vien</th>
                    <th>Ngay den</th>
                    <th>Ngay di</th>
                    <th>So ngay</th>
                    <th>So nguoi</th>
                    <th>Tong tien</th>
                    <th>Trang thai</th>
                    <th>Hanh dong</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dsDatPhong)): ?>
                    <tr>
                        <td colspan="13">
                            <div class="empty-state">
                                <div class="empty-icon">&#9998;</div>
                                <h4>Khong co dat phong nao</h4>
                                <p>Khong tim thay dat phong nao voi tieu chi tim kiem hien tai.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $stt = 1; foreach ($dsDatPhong as $dp): ?>
                    <?php
                        // Tinh so ngay va tong tien
                        $soNgay = 0;
                        $tongTien = 0;
                        if ($dp['ngayden'] && $dp['ngaydi']) {
                            $dateDen = new DateTime($dp['ngayden']);
                            $dateDi = new DateTime($dp['ngaydi']);
                            $soNgay = max(1, $dateDen->diff($dateDi)->days);
                            $tongTien = $soNgay * ($dp['giaban'] ?? 0);
                        }
                    ?>
                    <tr>
                        <td><?php echo $stt++; ?></td>
                        <td><strong><?php echo htmlspecialchars($dp['madatphong']); ?></strong></td>
                        <td><?php echo htmlspecialchars($dp['tenkh'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($dp['tenphong'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($dp['tenloai'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($dp['tennv'] ?? ''); ?></td>
                        <td><?php echo formatDate($dp['ngayden']); ?></td>
                        <td><?php echo formatDate($dp['ngaydi']); ?></td>
                        <td><?php echo $soNgay; ?> dem</td>
                        <td><?php echo $dp['songuoi']; ?></td>
                        <td><strong><?php echo formatMoney($tongTien); ?></strong><br><small style="color:#666;"><?php echo $soNgay; ?> x <?php echo formatMoney($dp['giaban'] ?? 0); ?></small></td>
                        <td><?php echo getStatusBadge($dp['trangthai'], 'datphong'); ?></td>
                        <td>
                            <div class="btn-group">
                                <a href="datphong.php?edit=<?php echo $dp['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-warning btn-sm">Sua</a>
                                <a href="datphong.php?delete=<?php echo $dp['id']; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $filter_trangthai ? '&filter_trangthai=' . urlencode($filter_trangthai) : ''; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Ban co chac muon xoa dat phong <?php echo htmlspecialchars(addslashes($dp['madatphong'])); ?>?')">Xoa</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function tinhTien() {
    var ngayden = document.getElementById('ngayden').value;
    var ngaydi = document.getElementById('ngaydi').value;
    var phongSel = document.getElementById('phongSelect');
    var giaban = phongSel.options[phongSel.selectedIndex] ? parseFloat(phongSel.options[phongSel.selectedIndex].getAttribute('data-giaban')) : 0;
    var display = document.getElementById('tongTienDisplay');
    var valueEl = document.getElementById('tongTienValue');
    var detailEl = document.getElementById('tongTienDetail');

    if (ngayden && ngaydi && giaban > 0) {
        var date1 = new Date(ngayden);
        var date2 = new Date(ngaydi);
        var diffDays = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
        if (diffDays < 1) diffDays = 1;

        var tongTien = diffDays * giaban;
        var formatted = tongTien.toLocaleString('vi-VN') + ' VND';
        valueEl.textContent = formatted;
        detailEl.textContent = diffDays + ' dem x ' + giaban.toLocaleString('vi-VN') + ' VND/dem';
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
}

// Tinh tien khi chon phong
document.getElementById('phongSelect').addEventListener('change', tinhTien);

// Tinh tien khi trang thai thay doi (in trang thai khong anh huong tien)
document.getElementById('trangthaiSelect').addEventListener('change', function() {
    // Hien thi canh bao neu chuyen trang thai
    var newStatus = this.value;
    var statusLabels = {
        'cho_xac_nhan': 'Cho xac nhan',
        'da_xac_nhan': 'Da xac nhan - Phong se chuyen sang "Da dat"',
        'da_nhan_phong': 'Da nhan phong - Phong se chuyen sang "Dang thue"',
        'da_tra_phong': 'Da tra phong - Phong se chuyen sang "Trong"',
        'da_huy': 'Da huy - Phong se chuyen sang "Trong"'
    };
});

// Khoi chay tinh tien khi trang tai (sua)
window.addEventListener('DOMContentLoaded', function() {
    var ngayden = document.getElementById('ngayden').value;
    var ngaydi = document.getElementById('ngaydi').value;
    if (ngayden && ngaydi) {
        tinhTien();
    }
});
</script>

<?php require_once 'footer.php'; ?>
