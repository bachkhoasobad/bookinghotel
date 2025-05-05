<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'CONANDOYCLEHOTELBOOKING' ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<header>
    <nav class="navbar">
        <!-- Phần Logo -->
        <h1 class="logo">CONANDOYCLEHOTELKING</h1>

        <!-- Phần Navigation -->
        <div class="nav-container">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i>
                Trang chủ
            </a>
            <div class="auth-links">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="loginregister.php?tab=register" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        Đăng ký
                    </a>
                    <a href="loginregister.php?tab=login" class="nav-link">
                        <i class="fas fa-sign-in-alt"></i>
                        Đăng nhập
                    </a>
                <?php else: ?>
                    <!-- Phần khi đã đăng nhập -->
                    <a href="#"><i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>
<div class="container content">