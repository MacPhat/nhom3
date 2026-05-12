<?php if(!isset($_SESSION)) session_start(); ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Quản Lý Khách Sạn | Nhóm 3</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="logo-icon">&#9962;</div>
            <h2>GRAND HOTEL</h2>
            <small>Nhom 3 - Quan Ly</small>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Chinh</div>
            <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>">
                <span class="nav-icon">&#9776;</span> Trang chu
            </a>
            <a href="thongke.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='thongke.php'?'active':''; ?>">
                <span class="nav-icon">&#128200;</span> Thong ke
            </a>

            <div class="nav-section">Quan ly</div>
            <a href="loaiphong.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='loaiphong.php'?'active':''; ?>">
                <span class="nav-icon">&#9733;</span> Loai phong
            </a>
            <a href="phong.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='phong.php'?'active':''; ?>">
                <span class="nav-icon">&#9962;</span> Phong
            </a>
            <a href="khachhang.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='khachhang.php'?'active':''; ?>">
                <span class="nav-icon">&#9787;</span> Khach hang
            </a>
            <a href="datphong.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='datphong.php'?'active':''; ?>">
                <span class="nav-icon">&#9998;</span> Dat phong
                <?php
                if (isset($_SESSION['user_id'])) {
                    $choXN = $pdo->query("SELECT COUNT(*) FROM datphong WHERE trangthai='cho_xac_nhan'")->fetchColumn();
                    if ($choXN > 0) echo '<span class="nav-badge">' . $choXN . '</span>';
                }
                ?>
            </a>
            <a href="nhanvien.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='nhanvien.php'?'active':''; ?>">
                <span class="nav-icon">&#9881;</span> Nhan vien
            </a>
            <a href="dichvu.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='dichvu.php'?'active':''; ?>">
                <span class="nav-icon">&#10023;</span> Dich vu
            </a>
            <a href="hoadon.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='hoadon.php'?'active':''; ?>">
                <span class="nav-icon">&#9830;</span> Hoa don
            </a>

            <div class="nav-section">Ca nhan</div>
            <a href="profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF'])=='profile.php'?'active':''; ?>">
                <span class="nav-icon">&#9786;</span> Thong tin ca nhan
            </a>
            <a href="trangchu.php" class="nav-link">
                <span class="nav-icon">&#127968;</span> Trang cong khai
            </a>
        </nav>
    </aside>
    <div class="main-area">
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">&#9776;</button>
                <h1 class="page-title"><?php echo isset($page_title) ? $page_title : 'Trang chu'; ?></h1>
            </div>
            <div class="topbar-right">
                <?php
                $unread = 0;
                if (isset($_SESSION['user_id'])) {
                    $unread = getUnreadCount($_SESSION['user_id']);
                }
                ?>
                <div class="topbar-notif" onclick="window.location.href='profile.php'">
                    &#128276;
                    <?php if ($unread > 0): ?>
                        <span class="notif-count"><?php echo $unread; ?></span>
                    <?php endif; ?>
                </div>
                <div class="topbar-user" onclick="window.location.href='profile.php'">
                    <div class="user-avatar"><?php echo mb_substr($_SESSION['hoten'] ?? 'A', 0, 1, 'UTF-8'); ?></div>
                    <div>
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['hoten'] ?? ''); ?></div>
                        <div class="user-role"><?php echo $_SESSION['quyen'] === 'admin' ? 'Quan tri vien' : ($_SESSION['quyen'] === 'nhanvien' ? 'Nhan vien' : 'Khach hang'); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout">Dang xuat</a>
            </div>
        </header>
        <main class="content">
