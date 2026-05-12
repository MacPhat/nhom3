<?php
$page_title = 'Thong ke bao cao';
require_once 'config.php';
checkLogin();

$nam = (int)($_GET['nam'] ?? date('Y'));
$thang = (int)($_GET['thang'] ?? date('m'));

// Doanh thu theo thang
$doanhThuThang = $pdo->prepare("SELECT DATE_FORMAT(ngaylap, '%m') as thang,
    COUNT(*) as sohd,
    SUM(tienphong) as tong_tienphong,
    SUM(tiendichvu) as tong_tiendichvu,
    SUM(tongtien) as tong_tongtien,
    SUM(thanhtoan) as tong_thanhtoan
    FROM hoadon WHERE YEAR(ngaylap) = ? GROUP BY thang ORDER BY thang");
$doanhThuThang->execute([$nam]);
$dsDoanhThu = $doanhThuThang->fetchAll();

// Tong quan nam
$tongQuan = $pdo->prepare("SELECT
    COUNT(*) as tong_hd,
    SUM(tongtien) as tong_tien,
    SUM(thanhtoan) as tong_thanh_toan,
    SUM(tongtien - COALESCE(thanhtoan,0)) as tong_no
    FROM hoadon WHERE YEAR(ngaylap) = ?");
$tongQuan->execute([$nam]);
$tq = $tongQuan->fetch();

// Dat phong theo trang thai
$datPhongTT = $pdo->query("SELECT trangthai, COUNT(*) as soluong FROM datphong GROUP BY trangthai")->fetchAll();

// Top phong duoc dat nhieu
$topPhong = $pdo->query("SELECT p.tenphong, lp.tenloai, COUNT(*) as sodat FROM datphong dp LEFT JOIN phong p ON dp.phong_id=p.id LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id GROUP BY dp.phong_id ORDER BY sodat DESC LIMIT 10")->fetchAll();

// Top khach hang
$topKhach = $pdo->query("SELECT kh.hoten, kh.makh, COUNT(*) as sodat, SUM(lp.giaban * DATEDIFF(dp.ngaydi, dp.ngayden)) as tongchi FROM datphong dp LEFT JOIN khachhang kh ON dp.khachhang_id=kh.id LEFT JOIN phong p ON dp.phong_id=p.id LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id GROUP BY dp.khachhang_id ORDER BY sodat DESC LIMIT 10")->fetchAll();

// Dich vu su dung nhieu
$topDichVu = $pdo->query("SELECT dv.tendv, SUM(sd.soluong) as soluongsd, SUM(sd.thanhtien) as doanhthu FROM sudung_dichvu sd LEFT JOIN dichvu dv ON sd.dichvu_id=dv.id GROUP BY sd.dichvu_id ORDER BY soluongsd DESC LIMIT 10")->fetchAll();

// Ty le phong
$tyLePhong = $pdo->query("SELECT trangthai, COUNT(*) as soluong FROM phong GROUP BY trangthai")->fetchAll();
$tongPhong = $pdo->query("SELECT COUNT(*) FROM phong")->fetchColumn();

require_once 'header.php';
?>

<div class="card">
    <div class="card-header">
        <h3>Thong ke nam <?php echo $nam; ?></h3>
        <form method="GET" action="" class="d-flex align-center gap-1">
            <select name="nam" class="form-control" style="max-width:120px;" onchange="this.form.submit()">
                <?php for ($y = date('Y') - 3; $y <= date('Y') + 1; $y++): ?>
                <option value="<?php echo $y; ?>" <?php echo $y == $nam ? 'selected' : ''; ?>><?php echo $y; ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#9830;</div>
            <div class="stat-info">
                <h4><?php echo $tq['tong_hd'] ?? 0; ?></h4>
                <p>Tong hoa don</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#128176;</div>
            <div class="stat-info">
                <h4><?php echo formatMoney($tq['tong_tien'] ?? 0); ?></h4>
                <p>Tong doanh thu</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon teal">&#10003;</div>
            <div class="stat-info">
                <h4><?php echo formatMoney($tq['tong_thanh_toan'] ?? 0); ?></h4>
                <p>Da thanh toan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red">&#9888;</div>
            <div class="stat-info">
                <h4><?php echo formatMoney($tq['tong_no'] ?? 0); ?></h4>
                <p>Con no</p>
            </div>
        </div>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h3>Doanh thu theo thang</h3>
        </div>
        <div class="bar-chart">
            <?php
            $maxVal = 1;
            foreach ($dsDoanhThu as $d) { if ($d['tong_tongtien'] > $maxVal) $maxVal = $d['tong_tongtien']; }
            for ($i = 1; $i <= 12; $i++):
                $val = 0;
                foreach ($dsDoanhThu as $d) { if ((int)$d['thang'] == $i) { $val = $d['tong_thanhtoan']; break; } }
                $height = $maxVal > 0 ? ($val / $maxVal * 140) : 0;
            ?>
            <div class="bar-item">
                <div class="bar-value"><?php echo $val > 0 ? round($val/1000000,1) . 'M' : ''; ?></div>
                <div class="bar" style="height:<?php echo max($height, 4); ?>px; <?php echo $val == 0 ? 'opacity:0.2;' : ''; ?>"></div>
                <div class="bar-label">T<?php echo $i; ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Ty le phong</h3>
        </div>
        <?php
        $statusMap = ['trong' => 'Phong trong', 'dang_thue' => 'Dang thue', 'da_dat' => 'Da dat', 'bao_tri' => 'Bao tri'];
        $colorMap = ['trong' => 'var(--success)', 'dang_thue' => 'var(--info)', 'da_dat' => 'var(--warning)', 'bao_tri' => 'var(--danger)'];
        foreach ($tyLePhong as $tp):
            $pct = $tongPhong > 0 ? round($tp['soluong'] / $tongPhong * 100) : 0;
        ?>
        <div style="margin-bottom:14px;">
            <div class="d-flex justify-between align-center mb-1">
                <span style="font-size:13px;"><?php echo $statusMap[$tp['trangthai']] ?? $tp['trangthai']; ?> (<?php echo $tp['soluong']; ?>)</span>
                <span style="font-size:13px; font-weight:700;"><?php echo $pct; ?>%</span>
            </div>
            <div class="progress"><div class="progress-bar" style="width:<?php echo $pct; ?>%; background:<?php echo $colorMap[$tp['trangthai']] ?? 'var(--primary)'; ?>;"></div></div>
        </div>
        <?php endforeach; ?>

        <h4 style="font-size:14px; font-weight:700; margin:20px 0 10px; color:var(--primary-dark);">Dat phong theo trang thai</h4>
        <?php
        $dpMap = ['cho_xac_nhan' => 'Cho xac nhan', 'da_xac_nhan' => 'Da xac nhan', 'da_nhan_phong' => 'Da nhan phong', 'da_tra_phong' => 'Da tra phong', 'da_huy' => 'Da huy'];
        foreach ($datPhongTT as $dp):
        ?>
        <div style="margin-bottom:8px;">
            <?php echo getStatusBadge($dp['trangthai'], 'datphong'); ?>
            <span style="font-size:13px; margin-left:8px;"><?php echo $dp['soluong']; ?> luo dat</span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header">
            <h3>Top phong duoc dat nhieu</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Phong</th><th>Loai</th><th>So lan dat</th></tr></thead>
                <tbody>
                    <?php foreach ($topPhong as $i => $tp): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($tp['tenphong']); ?></strong></td>
                        <td><?php echo htmlspecialchars($tp['tenloai']); ?></td>
                        <td><span class="badge badge-primary"><?php echo $tp['sodat']; ?> lan</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Top khach hang</h3>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Khach hang</th><th>So lan dat</th><th>Tong chi</th></tr></thead>
                <tbody>
                    <?php foreach ($topKhach as $i => $tk): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($tk['hoten']); ?></strong><br><small style="color:var(--text-lighter);"><?php echo htmlspecialchars($tk['makh']); ?></small></td>
                        <td><span class="badge badge-primary"><?php echo $tk['sodat']; ?> lan</span></td>
                        <td><strong><?php echo formatMoney($tk['tongchi'] ?? 0); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Dich vu su dung nhieu nhat</h3>
    </div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Dich vu</th><th>So luong su dung</th><th>Doanh thu</th></tr></thead>
            <tbody>
                <?php foreach ($topDichVu as $i => $dv): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($dv['tendv']); ?></strong></td>
                    <td><span class="badge badge-info"><?php echo $dv['soluongsd']; ?> lan</span></td>
                    <td><strong><?php echo formatMoney($dv['doanhthu']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Chi tiet doanh thu theo thang</h3>
        <button onclick="window.print()" class="btn btn-outline btn-sm no-print">In bao cao</button>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Thang</th>
                    <th>So hoa don</th>
                    <th>Tien phong</th>
                    <th>Tien dich vu</th>
                    <th>Tong tien</th>
                    <th>Da thanh toan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $tongHD = 0; $tongTP = 0; $tongTDV = 0; $tongTT = 0; $tongTTT = 0;
                for ($i = 1; $i <= 12; $i++):
                    $row = null;
                    foreach ($dsDoanhThu as $d) { if ((int)$d['thang'] == $i) { $row = $d; break; } }
                    $tongHD += $row['sohd'] ?? 0;
                    $tongTP += $row['tong_tienphong'] ?? 0;
                    $tongTDV += $row['tong_tiendichvu'] ?? 0;
                    $tongTT += $row['tong_tongtien'] ?? 0;
                    $tongTTT += $row['tong_thanhtoan'] ?? 0;
                ?>
                <tr>
                    <td><strong>Thang <?php echo $i; ?></strong></td>
                    <td><?php echo $row['sohd'] ?? 0; ?></td>
                    <td><?php echo formatMoney($row['tong_tienphong'] ?? 0); ?></td>
                    <td><?php echo formatMoney($row['tong_tiendichvu'] ?? 0); ?></td>
                    <td><strong><?php echo formatMoney($row['tong_tongtien'] ?? 0); ?></strong></td>
                    <td style="color:var(--success); font-weight:700;"><?php echo formatMoney($row['tong_thanhtoan'] ?? 0); ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
                <tr style="background:var(--primary-lighter); font-weight:700;">
                    <td>TONG CONG</td>
                    <td><?php echo $tongHD; ?></td>
                    <td><?php echo formatMoney($tongTP); ?></td>
                    <td><?php echo formatMoney($tongTDV); ?></td>
                    <td><?php echo formatMoney($tongTT); ?></td>
                    <td style="color:var(--success);"><?php echo formatMoney($tongTTT); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
