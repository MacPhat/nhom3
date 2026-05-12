<?php
$page_title = 'Thanh toan';
require_once 'config.php';
checkLogin();

$message = '';
$success = '';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT hd.*, dp.madatphong, dp.ngayden, dp.ngaydi, kh.hoten as tenkh, kh.makh, kh.sdt, kh.email, p.tenphong, p.maphong, lp.tenloai, lp.giaban, nv.hoten as tennv
        FROM hoadon hd
        LEFT JOIN datphong dp ON hd.datphong_id = dp.id
        LEFT JOIN khachhang kh ON dp.khachhang_id = kh.id
        LEFT JOIN phong p ON dp.phong_id = p.id
        LEFT JOIN loaiphong lp ON p.loaiphong_id = lp.id
        LEFT JOIN nhanvien nv ON hd.nhanvien_id = nv.id
        WHERE hd.id = ?");
    $stmt->execute([$id]);
    $hoadon = $stmt->fetch();

    if (!$hoadon) {
        header('Location: hoadon.php');
        exit;
    }
} else {
    header('Location: hoadon.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pt_thanhtoan = $_POST['pt_thanhtoan'] ?? 'tien_mat';
    $ghichu = trim($_POST['ghichu'] ?? '');

    try {
        $stmt = $pdo->prepare("UPDATE hoadon SET trangthai='da_thanh_toan', pt_thanhtoan=?, thanhtoan=tongtien, ghichu=CONCAT(COALESCE(ghichu,''), ?) WHERE id=?");
        $stmt->execute([$pt_thanhtoan, "\n[Thanh toan] " . $ghichu, $hoadon['id']]);

        addNotification($_SESSION['user_id'], 'Thanh toan thanh cong', "Hoa don {$hoadon['mahoadon']} da duoc thanh toan. Tong tien: " . formatMoney($hoadon['tongtien']), 'thanhtoan');

        $success = 'Thanh toan thanh cong!';
        $stmt = $pdo->prepare("SELECT * FROM hoadon WHERE id = ?");
        $stmt->execute([$id]);
        $hoadon = $stmt->fetch();
    } catch (PDOException $e) {
        $message = 'Thanh toan that bai!';
    }
}

// Lay dich vu su dung
$dsDichVu = $pdo->prepare("SELECT sd.*, dv.tendv, dv.gia, dv.donvitinh FROM sudung_dichvu sd LEFT JOIN dichvu dv ON sd.dichvu_id=dv.id WHERE sd.datphong_id=?");
$dsDichVu->execute([$hoadon['datphong_id']]);
$dichVuList = $dsDichVu->fetchAll();

require_once 'header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div style="max-width:800px; margin:0 auto;">
    <div class="card">
        <div class="card-header">
            <h3>Hoa don <?php echo htmlspecialchars($hoadon['mahoadon']); ?></h3>
            <div class="btn-group no-print">
                <?php if ($hoadon['trangthai'] !== 'da_thanh_toan'): ?>
                    <button onclick="document.getElementById('thanhtoanForm').style.display='block'" class="btn btn-success btn-sm">Thanh toan</button>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-outline btn-sm">In hoa don</button>
                <a href="hoadon.php" class="btn btn-outline btn-sm">Quay lai</a>
            </div>
        </div>

        <div class="print-only" style="text-align:center; margin-bottom:20px;">
            <h2 style="font-size:24px;">GRAND HOTEL</h2>
            <p>123 Nguyen Hue, Q.1, TP.HCM</p>
            <p>Dien thoai: (028) 1234 5678</p>
            <hr style="margin:10px 0;">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px;">
            <div>
                <h4 style="font-size:14px; font-weight:700; margin-bottom:10px; color:var(--primary-dark);">Thong tin khach hang</h4>
                <p style="font-size:13px;"><strong>Ma KH:</strong> <?php echo htmlspecialchars($hoadon['makh']); ?></p>
                <p style="font-size:13px;"><strong>Ho ten:</strong> <?php echo htmlspecialchars($hoadon['tenkh']); ?></p>
                <p style="font-size:13px;"><strong>SĐT:</strong> <?php echo htmlspecialchars($hoadon['sdt'] ?? ''); ?></p>
                <p style="font-size:13px;"><strong>Email:</strong> <?php echo htmlspecialchars($hoadon['email'] ?? ''); ?></p>
            </div>
            <div>
                <h4 style="font-size:14px; font-weight:700; margin-bottom:10px; color:var(--primary-dark);">Thong tin hoa don</h4>
                <p style="font-size:13px;"><strong>Ma HD:</strong> <?php echo htmlspecialchars($hoadon['mahoadon']); ?></p>
                <p style="font-size:13px;"><strong>Ngay lap:</strong> <?php echo date('d/m/Y H:i', strtotime($hoadon['ngaylap'])); ?></p>
                <p style="font-size:13px;"><strong>Trang thai:</strong> <?php echo getStatusBadge($hoadon['trangthai'], 'hoadon'); ?></p>
                <p style="font-size:13px;"><strong>PT thanh toan:</strong> <?php echo $hoadon['pt_thanhtoan'] === 'tien_mat' ? 'Tien mat' : ($hoadon['pt_thanhtoan'] === 'chuyen_khoan' ? 'Chuyen khoan' : 'The'); ?></p>
            </div>
        </div>

        <div style="margin-bottom:24px;">
            <h4 style="font-size:14px; font-weight:700; margin-bottom:10px; color:var(--primary-dark);">Thong tin dat phong</h4>
            <p style="font-size:13px;"><strong>Phong:</strong> <?php echo htmlspecialchars($hoadon['tenphong']); ?> (<?php echo htmlspecialchars($hoadon['tenloai']); ?>)</p>
            <p style="font-size:13px;"><strong>Ngay den:</strong> <?php echo formatDate($hoadon['ngayden']); ?></p>
            <p style="font-size:13px;"><strong>Ngay di:</strong> <?php echo formatDate($hoadon['ngaydi']); ?></p>
            <?php
            $soNgay = max(1, (strtotime($hoadon['ngaydi']) - strtotime($hoadon['ngayden'])) / 86400);
            ?>
            <p style="font-size:13px;"><strong>So dem:</strong> <?php echo $soNgay; ?> dem x <?php echo formatMoney($hoadon['giaban']); ?>/dem</p>
        </div>

        <table style="margin-bottom:24px;">
            <thead>
                <tr>
                    <th>Hang muc</th>
                    <th class="text-right">So luong</th>
                    <th class="text-right">Don gia</th>
                    <th class="text-right">Thanh tien</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tien phong (<?php echo htmlspecialchars($hoadon['tenloai']); ?>)</td>
                    <td class="text-right"><?php echo $soNgay; ?> dem</td>
                    <td class="text-right"><?php echo formatMoney($hoadon['giaban']); ?></td>
                    <td class="text-right"><strong><?php echo formatMoney($hoadon['tienphong']); ?></strong></td>
                </tr>
                <?php foreach ($dichVuList as $dv): ?>
                <tr>
                    <td><?php echo htmlspecialchars($dv['tendv']); ?></td>
                    <td class="text-right"><?php echo $dv['soluong']; ?> <?php echo htmlspecialchars($dv['donvitinh']); ?></td>
                    <td class="text-right"><?php echo formatMoney($dv['gia']); ?></td>
                    <td class="text-right"><?php echo formatMoney($dv['thanhtien']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="border:2px solid var(--primary); border-radius:var(--radius); padding:20px; background:var(--primary-lighter);">
            <div class="d-flex justify-between mb-1">
                <span style="font-size:14px;">Tien phong:</span>
                <span style="font-size:14px; font-weight:700;"><?php echo formatMoney($hoadon['tienphong']); ?></span>
            </div>
            <div class="d-flex justify-between mb-1">
                <span style="font-size:14px;">Tien dich vu:</span>
                <span style="font-size:14px; font-weight:700;"><?php echo formatMoney($hoadon['tiendichvu']); ?></span>
            </div>
            <?php if ($hoadon['phuthu'] > 0): ?>
            <div class="d-flex justify-between mb-1">
                <span style="font-size:14px;">Phu thu:</span>
                <span style="font-size:14px; font-weight:700; color:var(--danger);">+<?php echo formatMoney($hoadon['phuthu']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($hoadon['giamgia'] > 0): ?>
            <div class="d-flex justify-between mb-1">
                <span style="font-size:14px;">Giam gia:</span>
                <span style="font-size:14px; font-weight:700; color:var(--success);">-<?php echo formatMoney($hoadon['giamgia']); ?></span>
            </div>
            <?php endif; ?>
            <div style="border-top:2px solid var(--primary); margin-top:10px; padding-top:10px;">
                <div class="d-flex justify-between">
                    <span style="font-size:18px; font-weight:800;">TONG CONG:</span>
                    <span style="font-size:24px; font-weight:800; color:var(--primary);"><?php echo formatMoney($hoadon['tongtien']); ?></span>
                </div>
                <?php if ($hoadon['thanhtoan'] > 0): ?>
                <div class="d-flex justify-between mt-1">
                    <span style="font-size:14px;">Da thanh toan:</span>
                    <span style="font-size:14px; font-weight:700; color:var(--success);"><?php echo formatMoney($hoadon['thanhtoan']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($hoadon['trangthai'] !== 'da_thanh_toan'): ?>
        <div id="thanhtoanForm" style="display:none; margin-top:24px; padding:20px; border:2px dashed var(--success); border-radius:var(--radius);">
            <h4 style="font-size:16px; font-weight:700; margin-bottom:16px; color:var(--success);">Xac nhan thanh toan</h4>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Phuong thuc thanh toan</label>
                    <select name="pt_thanhtoan" class="form-control">
                        <option value="tien_mat">Tien mat</option>
                        <option value="chuyen_khoan">Chuyen khoan</option>
                        <option value="the">The ngan hang</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ghi chu</label>
                    <textarea name="ghichu" class="form-control" rows="2" placeholder="Ghi chu thanh toan (neu co)"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-success">Xac nhan thanh toan</button>
                    <button type="button" class="btn btn-outline" onclick="document.getElementById('thanhtoanForm').style.display='none'">Huy</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
