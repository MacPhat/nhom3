<?php
require_once 'config.php';

$dsLoaiPhong = $pdo->query("SELECT * FROM loaiphong ORDER BY giaban ASC")->fetchAll();
$dsPhongTrong = $pdo->query("SELECT p.*, lp.tenloai, lp.giaban, lp.tienich, lp.sophong_max FROM phong p LEFT JOIN loaiphong lp ON p.loaiphong_id=lp.id WHERE p.trangthai='trong' ORDER BY lp.giaban ASC")->fetchAll();
$dsDichVu = $pdo->query("SELECT * FROM dichvu WHERE trangthai='hoat_dong' ORDER BY id")->fetchAll();
$dsDanhGia = $pdo->query("SELECT dg.*, kh.hoten as tenkh FROM danhgia dg LEFT JOIN khachhang kh ON dg.khachhang_id=kh.id ORDER BY dg.created_at DESC LIMIT 6")->fetchAll();

$tongPhong = $pdo->query("SELECT COUNT(*) FROM phong")->fetchColumn();
$tongKhach = $pdo->query("SELECT COUNT(*) FROM khachhang")->fetchColumn();
$diemTB = $pdo->query("SELECT COALESCE(AVG(diem),0) FROM danhgia")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Hotel - He thong Quan Ly Khach San | Nhom 3</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav class="pub-nav">
    <div class="brand">
        <div class="brand-icon">&#9962;</div>
        GRAND HOTEL
    </div>
    <div class="pub-nav-links">
        <a href="trangchu.php" class="active">Trang chu</a>
        <a href="#phong">Phong</a>
        <a href="#dichvu">Dich vu</a>
        <a href="#danhgia">Danh gia</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['quyen'] === 'khachhang'): ?>
                <a href="datphong_khach.php" class="btn btn-primary btn-sm">Dat phong</a>
            <?php else: ?>
                <a href="index.php" class="btn btn-primary btn-sm">Quan ly</a>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-outline btn-sm">Ca nhan</a>
            <a href="logout.php" class="btn btn-danger btn-sm">Dang xuat</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">Dang nhap</a>
            <a href="dangky.php" class="btn btn-outline btn-sm">Dang ky</a>
        <?php endif; ?>
    </div>
</nav>

<section class="hero">
    <h1>Chao mung den Grand Hotel</h1>
    <p>Trai nghiem dich vu luu tru dang cap voi he thong quan ly khach san chuyen nghiep tu Nhom 3</p>
    <div class="hero-actions">
        <a href="#phong" class="btn-hero btn-hero-primary">Xem phong</a>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="dangky.php" class="btn-hero btn-hero-outline">Dang ky tai khoan</a>
        <?php else: ?>
            <a href="datphong_khach.php" class="btn-hero btn-hero-outline">Dat phong ngay</a>
        <?php endif; ?>
    </div>
</section>

<section class="booking-section" id="booking">
    <h3>Tim phong trong</h3>
    <form method="GET" action="datphong_khach.php" style="display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:12px; align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label>Ngay den</label>
            <input type="date" name="ngayden" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Ngay di</label>
            <input type="date" name="ngaydi" class="form-control" value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label>Loai phong</label>
            <select name="loaiphong_id" class="form-control">
                <option value="">Tat ca loai phong</option>
                <?php foreach ($dsLoaiPhong as $lp): ?>
                <option value="<?php echo $lp['id']; ?>"><?php echo htmlspecialchars($lp['tenloai']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Tim phong</button>
    </form>
</section>

<section class="section" id="phong">
    <h2 class="section-title">Cac loai phong</h2>
    <p class="section-desc">Lua chon phong phu hop voi nhu cau va ngan sach cua ban</p>
    <div class="room-grid" style="padding:0;">
        <?php foreach ($dsLoaiPhong as $lp): ?>
        <div class="room-card">
            <div class="room-card-img">
                &#9962;
                <?php
                $soPhongTrong = $pdo->prepare("SELECT COUNT(*) FROM phong WHERE loaiphong_id=? AND trangthai='trong'");
                $soPhongTrong->execute([$lp['id']]);
                $count = $soPhongTrong->fetchColumn();
                ?>
                <?php if ($count > 0): ?>
                    <span class="room-badge" style="background:var(--success);">Con <?php echo $count; ?> phong</span>
                <?php else: ?>
                    <span class="room-badge" style="background:var(--danger);">Het phong</span>
                <?php endif; ?>
            </div>
            <div class="room-card-body">
                <h3><?php echo htmlspecialchars($lp['tenloai']); ?></h3>
                <p class="room-desc"><?php echo htmlspecialchars($lp['mota'] ?? ''); ?></p>
                <?php if ($lp['tienich']): ?>
                <div class="room-amenities">
                    <?php foreach (explode(',', $lp['tienich']) as $t): ?>
                    <span class="room-amenity"><?php echo htmlspecialchars(trim($t)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="room-card-footer">
                    <div class="room-price"><?php echo formatMoney($lp['giaban']); ?> <small>/dem</small></div>
                    <?php if ($count > 0 && isset($_SESSION['user_id'])): ?>
                        <a href="datphong_khach.php?loaiphong_id=<?php echo $lp['id']; ?>" class="btn btn-primary btn-sm">Dat phong</a>
                    <?php elseif ($count > 0): ?>
                        <a href="login.php" class="btn btn-outline btn-sm">Dang nhap de dat</a>
                    <?php else: ?>
                        <span class="badge badge-danger">Het phong</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" id="dichvu" style="background:var(--bg);">
    <h2 class="section-title">Dich vu</h2>
    <p class="section-desc">Cac dich vu cao cap tai khach san</p>
    <div class="features-grid">
        <?php foreach ($dsDichVu as $dv): ?>
        <div class="feature-card">
            <div class="feature-icon" style="background:var(--primary-lighter); color:var(--primary-light);">&#10023;</div>
            <h4><?php echo htmlspecialchars($dv['tendv']); ?></h4>
            <p><?php echo htmlspecialchars($dv['mota'] ?? ''); ?></p>
            <p style="margin-top:12px; font-size:18px; font-weight:800; color:var(--accent);"><?php echo formatMoney($dv['gia']); ?> <span style="font-size:12px; font-weight:400; color:var(--text-light);">/ <?php echo htmlspecialchars($dv['donvitinh']); ?></span></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section" id="danhgia">
    <h2 class="section-title">Danh gia tu khach hang</h2>
    <p class="section-desc">Nhung nhan xet tu khach hang da trai nghiem dich vu</p>
    <?php if (empty($dsDanhGia)): ?>
        <div class="empty-state">
            <div class="empty-icon">&#9734;</div>
            <p>Chua co danh gia nao</p>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:20px;">
            <?php foreach ($dsDanhGia as $dg): ?>
            <div class="card" style="margin-bottom:0;">
                <div class="d-flex align-center justify-between mb-1">
                    <strong style="font-size:14px;"><?php echo htmlspecialchars($dg['tenkh']); ?></strong>
                    <span class="stars"><?php echo str_repeat('&#9733;', $dg['diem']) . str_repeat('&#9734;', 5 - $dg['diem']); ?></span>
                </div>
                <p style="font-size:13px; color:var(--text-light);"><?php echo htmlspecialchars($dg['nhanxet'] ?? 'Khong co nhan xet'); ?></p>
                <p style="font-size:11px; color:var(--text-lighter); margin-top:8px;"><?php echo formatDate($dg['created_at']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="section" style="background:var(--bg);">
    <h2 class="section-title">Thong tin khach san</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">&#9962;</div>
            <div class="stat-info">
                <h4><?php echo $tongPhong; ?></h4>
                <p>Phong luu tru</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">&#9787;</div>
            <div class="stat-info">
                <h4><?php echo $tongKhach; ?></h4>
                <p>Khach hang</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">&#9733;</div>
            <div class="stat-info">
                <h4><?php echo number_format($diemTB, 1); ?>/5</h4>
                <p>Danh gia trung binh</p>
            </div>
        </div>
    </div>
</section>

<footer class="pub-footer">
    <div class="pub-footer-grid">
        <div>
            <h4>GRAND HOTEL</h4>
            <p>He thong quan ly khach san chuyen nghiep</p>
            <p>Do an Nhom 3 - De tai: Quan Ly Khach San</p>
        </div>
        <div>
            <h4>Lien ket</h4>
            <p><a href="trangchu.php">Trang chu</a></p>
            <p><a href="#phong">Phong</a></p>
            <p><a href="#dichvu">Dich vu</a></p>
            <p><a href="login.php">Dang nhap</a></p>
        </div>
        <div>
            <h4>Lien he</h4>
            <p>Dia chi: 123 Nguyen Hue, Q.1, TP.HCM</p>
            <p>Dien thoai: (028) 1234 5678</p>
            <p>Email: info@grandhotel.com</p>
        </div>
    </div>
    <div class="pub-footer-bottom">
        <p>&copy; 2026 Nhom 3 - Do an: He thong Quan Ly Khach San | PHP + MySQL + phpMyAdmin</p>
    </div>
</footer>

</body>
</html>
