<?php
$page_title = 'Trang chu';
require_once 'config.php';
checkLogin();

$tongPhong = $pdo->query("SELECT COUNT(*) FROM phong")->fetchColumn();
$phongTrong = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='trong'")->fetchColumn();
$phongDangThue = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='dang_thue'")->fetchColumn();
$phongDaDat = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='da_dat'")->fetchColumn();
$phongBaoTri = $pdo->query("SELECT COUNT(*) FROM phong WHERE trangthai='bao_tri'")->fetchColumn();

$tongKhach = $pdo->query("SELECT COUNT(*) FROM khachhang")->fetchColumn();
$tongNhanVien = $pdo->query("SELECT COUNT(*) FROM nhanvien WHERE trangthai='hoat_dong'")->fetchColumn();
$tongDatPhong = $pdo->query("SELECT COUNT(*) FROM datphong")->fetchColumn();
$datPhongCho = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='cho_xac_nhan'")->fetchColumn();
$datPhongDangThue = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='da_nhan_phong'")->fetchColumn();

$tongHoaDon = $pdo->query("SELECT COUNT(*) FROM hoadon")->fetchColumn();
$doanhThu = $pdo->query("SELECT COALESCE(SUM(thanhtoan),0) FROM hoadon WHERE trangthai='da_thanh_toan'")->fetchColumn();
$chuaThanhToan = $pdo->query("SELECT COUNT(*) FROM hoadon WHERE trangthai='chua_thanh_toan'")->fetchColumn();
$tongNo = $pdo->query("SELECT COALESCE(SUM(tongtien - COALESCE(thanhtoan,0)),0) FROM hoadon WHERE trangthai='chua_thanh_toan'")->fetchColumn();

$datPhongMoi = $pdo->query("SELECT dp.*, kh.hoten as tenkh, p.tenphong, lp.tenloai FROM datphong dp LEFT JOIN khachhang kh ON dp.khachhang_id=kh.id LEFT JOIN phong p ON dp.phong_id=p.id LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id ORDER BY dp.created_at DESC LIMIT 8")->fetchAll();

$doanhThuThang = $pdo->query("SELECT DATE_FORMAT(ngaylap, '%m') as thang, SUM(thanhtoan) as tong FROM hoadon WHERE trangthai='da_thanh_toan' AND YEAR(ngaylap)=YEAR(CURRENT_DATE) GROUP BY thang ORDER BY thang")->fetchAll();

$danhGiaMoi = $pdo->query("SELECT dg.*, kh.hoten as tenkh, p.tenphong FROM danhgia dg LEFT JOIN khachhang kh ON dg.khachhang_id=kh.id LEFT JOIN phong p ON dg.phong_id=p.id ORDER BY dg.created_at DESC LIMIT 5")->fetchAll();

require_once 'header.php';
?>

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
            <h4><?php echo $phongDangThue + $phongDaDat; ?></h4>
            <p>Dang thue / Da dat</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#9888;</div>
        <div class="stat-info">
            <h4><?php echo $phongBaoTri; ?></h4>
            <p>Bao tri</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon teal">&#9787;</div>
        <div class="stat-info">
            <h4><?php echo $tongKhach; ?></h4>
            <p>Khach hang</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">&#9881;</div>
        <div class="stat-info">
            <h4><?php echo $tongNhanVien; ?></h4>
            <p>Nhan vien</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon orange">&#9998;</div>
        <div class="stat-info">
            <h4><?php echo $datPhongCho; ?></h4>
            <p>Cho xac nhan</p>
            <?php if ($datPhongCho > 0): ?>
                <span class="stat-change down">Can xu ly</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">&#9830;</div>
        <div class="stat-info">
            <h4><?php echo $chuaThanhToan; ?></h4>
            <p>Chua thanh toan</p>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h3>Doanh thu nam <?php echo date('Y'); ?></h3>
            <a href="thongke.php" class="btn btn-outline btn-sm">Xem chi tiet</a>
        </div>
        <div class="bar-chart">
            <?php
            $maxVal = 1;
            foreach ($doanhThuThang as $d) { if ($d['tong'] > $maxVal) $maxVal = $d['tong']; }
            for ($i = 1; $i <= 12; $i++):
                $val = 0;
                foreach ($doanhThuThang as $d) { if ((int)$d['thang'] == $i) { $val = $d['tong']; break; } }
                $height = $maxVal > 0 ? ($val / $maxVal * 140) : 0;
            ?>
            <div class="bar-item">
                <div class="bar-value"><?php echo $val > 0 ? round($val/1000000,1) . 'M' : ''; ?></div>
                <div class="bar" style="height:<?php echo max($height, 4); ?>px; <?php echo $val == 0 ? 'opacity:0.2;' : ''; ?>"></div>
                <div class="bar-label">T<?php echo $i; ?></div>
            </div>
            <?php endfor; ?>
        </div>
        <div style="text-align:center; margin-top:16px;">
            <span style="font-size:24px; font-weight:800; color:var(--success);"><?php echo formatMoney($doanhThu); ?></span>
            <p style="font-size:12px; color:var(--text-light); margin-top:4px;">Tong doanh thu da thanh toan</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Tinh trang</h3>
        </div>
        <div style="margin-bottom:16px;">
            <div class="d-flex justify-between align-center mb-1">
                <span style="font-size:13px;">Phong trong</span>
                <span style="font-size:13px; font-weight:700;"><?php echo $tongPhong > 0 ? round($phongTrong/$tongPhong*100) : 0; ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo $tongPhong > 0 ? $phongTrong/$tongPhong*100 : 0; ?>%; background:var(--success);"></div></div>
        </div>
        <div style="margin-bottom:16px;">
            <div class="d-flex justify-between align-center mb-1">
                <span style="font-size:13px;">Dang thue</span>
                <span style="font-size:13px; font-weight:700;"><?php echo $tongPhong > 0 ? round($phongDangThue/$tongPhong*100) : 0; ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo $tongPhong > 0 ? $phongDangThue/$tongPhong*100 : 0; ?>%; background:var(--info);"></div></div>
        </div>
        <div style="margin-bottom:16px;">
            <div class="d-flex justify-between align-center mb-1">
                <span style="font-size:13px;">Da dat</span>
                <span style="font-size:13px; font-weight:700;"><?php echo $tongPhong > 0 ? round($phongDaDat/$tongPhong*100) : 0; ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo $tongPhong > 0 ? $phongDaDat/$tongPhong*100 : 0; ?>%; background:var(--warning);"></div></div>
        </div>
        <div style="margin-bottom:16px;">
            <div class="d-flex justify-between align-center mb-1">
                <span style="font-size:13px;">Bao tri</span>
                <span style="font-size:13px; font-weight:700;"><?php echo $tongPhong > 0 ? round($phongBaoTri/$tongPhong*100) : 0; ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo $tongPhong > 0 ? $phongBaoTri/$tongPhong*100 : 0; ?>%; background:var(--danger);"></div></div>
        </div>
        <div style="padding:16px; background:var(--danger-lighter); border-radius:var(--radius-sm); margin-top:8px;">
            <p style="font-size:12px; color:#991b1b; font-weight:600;">Tong no chua thanh toan</p>
            <p style="font-size:20px; font-weight:800; color:var(--danger);"><?php echo formatMoney($tongNo); ?></p>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;">
    <div class="card">
        <div class="card-header">
            <h3>Dat phong gan day</h3>
            <a href="datphong.php" class="btn btn-primary btn-sm">Xem tat ca</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Ma dat phong</th>
                        <th>Khach hang</th>
                        <th>Phong</th>
                        <th>Ngay den</th>
                        <th>Ngay di</th>
                        <th>Trang thai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($datPhongMoi)): ?>
                        <tr><td colspan="6" class="text-center text-muted">Chua co dat phong nao</td></tr>
                    <?php else: ?>
                        <?php foreach ($datPhongMoi as $dp): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($dp['madatphong']); ?></strong></td>
                            <td><?php echo htmlspecialchars($dp['tenkh']); ?></td>
                            <td><?php echo htmlspecialchars($dp['tenphong']); ?></td>
                            <td><?php echo formatDate($dp['ngayden']); ?></td>
                            <td><?php echo formatDate($dp['ngaydi']); ?></td>
                            <td><?php echo getStatusBadge($dp['trangthai'], 'datphong'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Danh gia gan day</h3>
        </div>
        <?php if (empty($danhGiaMoi)): ?>
            <div class="empty-state">
                <div class="empty-icon">&#9734;</div>
                <p>Chua co danh gia nao</p>
            </div>
        <?php else: ?>
            <?php foreach ($danhGiaMoi as $dg): ?>
            <div style="padding:12px 0; border-bottom:1px solid var(--border-light);">
                <div class="d-flex justify-between align-center">
                    <strong style="font-size:13px;"><?php echo htmlspecialchars($dg['tenkh']); ?></strong>
                    <span class="stars"><?php echo str_repeat('&#9733;', $dg['diem']) . str_repeat('&#9734;', 5 - $dg['diem']); ?></span>
                </div>
                <p style="font-size:12px; color:var(--text-light); margin-top:4px;"><?php echo htmlspecialchars($dg['nhanxet'] ?? ''); ?></p>
                <p style="font-size:11px; color:var(--text-lighter); margin-top:2px;"><?php echo htmlspecialchars($dg['tenphong']); ?> - <?php echo formatDate($dg['created_at']); ?></p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
