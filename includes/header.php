<?php
require_once __DIR__ . '/../config/database.php';

// Get current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$user = getCurrentUser();

// Define menu items based on role
$menuItems = [
    'dashboard' => ['label' => 'Dashboard', 'icon' => 'home', 'roles' => ['ADMIN', 'KEPALA_SEKOLAH', 'GURU', 'PEGAWAI']],
    
    // Menu untuk Guru dan Pegawai
    'input-laporan' => ['label' => 'Input Laporan', 'icon' => 'file-plus', 'roles' => ['GURU', 'PEGAWAI']],
    'riwayat-laporan' => ['label' => 'Riwayat Laporan', 'icon' => 'clock', 'roles' => ['GURU', 'PEGAWAI']],
    
    // Menu untuk Admin
    'kelola-data' => ['label' => 'Kelola Data', 'icon' => 'users', 'roles' => ['ADMIN']],
    'verifikasi' => ['label' => 'Verifikasi Laporan', 'icon' => 'check-circle', 'roles' => ['ADMIN']],
    
    // Menu untuk Kepala Sekolah dan Admin
    'monitoring' => ['label' => 'Rekap Laporan', 'icon' => 'chart-bar', 'roles' => ['KEPALA_SEKOLAH', 'ADMIN']],
    

    // Menu untuk semua
    'profil' => ['label' => 'Profil', 'icon' => 'user', 'roles' => ['ADMIN', 'KEPALA_SEKOLAH', 'GURU', 'PEGAWAI']],
];

// Filter menu items by user role
$filteredMenuItems = array_filter($menuItems, function($item) use ($user) {
    return in_array($user['role'], $item['roles']);
});
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Laporan Guru dan Pegawai</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <!-- Header -->
<header class="header">
    <div class="header-content">

        <!-- KIRI -->
        <div class="header-left">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>

            <div class="logo">
                <img src="logosmk3.png" alt="Logo Sekolah" class="logo-img">
                <div class="logo-text">
                    <span class="logo-title">Sistem Laporan</span>
                    <span class="logo-subtitle">Guru dan Pegawai</span>
                </div>
            </div>
        </div>

        <!-- KANAN -->
        <div class="header-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-role badge badge-<?php echo strtolower($user['role']); ?>">
                        <?php echo getRoleLabel($user['role']); ?>
                    </span>
                </div>
            </div>

            <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-outline">
                <i class="fas fa-sign-out-alt"></i>
                <span>Keluar</span>
            </a>
        </div>

    </div>
</header>

<!-- CSS LANGSUNG -->
<style>
.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.logo-img {
    width: 40px;
    height: 40px;
    object-fit: contain;
}

.logo-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}

.logo-title {
    font-weight: 600;
    font-size: 14px;
}

.logo-subtitle {
    font-size: 11px;
    color: #64748b;
}

/* BAGIAN KANAN BIAR RAPI */
.header-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
</style>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav class="sidebar-nav">
            <?php foreach ($filteredMenuItems as $page => $item): ?>
            <a href="<?php echo BASE_URL; ?>/<?php echo $page; ?>.php" 
               class="sidebar-link <?php echo $currentPage === $page ? 'active' : ''; ?>">
                <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                <span><?php echo $item['label']; ?></span>
            </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
