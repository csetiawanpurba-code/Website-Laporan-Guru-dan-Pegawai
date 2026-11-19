<?php
require_once __DIR__ . '/includes/header.php';

// Hanya Admin dan Kepala Sekolah yang bisa akses
if (!in_array($user['role'], ['KEPALA_SEKOLAH', 'ADMIN'])) {
    redirect('/dashboard.php');
}

$conn = getConnection();

// Get filter
$period = $_GET['period'] ?? 'all';
$roleFilter = $_GET['role'] ?? 'all';

// Build date filter
$dateFilter = "";
switch ($period) {
    case 'daily':
        $dateFilter = " AND activity_date = CURDATE()";
        break;
    case 'weekly':
        $dateFilter = " AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'monthly':
        $dateFilter = " AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
}

// Build role filter
$roleFilterSQL = "";
if ($roleFilter !== 'all') {
    $roleFilterSQL = " AND u.role = '" . $conn->real_escape_string($roleFilter) . "'";
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN r.status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN r.status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN r.status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1 $dateFilter $roleFilterSQL";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Get reports by user
$byUserQuery = "SELECT u.name, u.role, u.position, COUNT(r.id) as count,
    SUM(CASE WHEN r.status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN r.status = 'PENDING' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN r.status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected_count
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1 $dateFilter $roleFilterSQL 
    GROUP BY u.id 
    ORDER BY count DESC";
$byUserResult = $conn->query($byUserQuery);

// Get reports by status over time (last 14 days)
$chartQuery = "SELECT 
    DATE(activity_date) as date,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
    FROM reports r
    JOIN users u ON r.user_id = u.id
    WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) $roleFilterSQL
    GROUP BY DATE(activity_date) 
    ORDER BY date";
$chartResult = $conn->query($chartQuery);
$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[] = $row;
}

// Get recent reports
$recentQuery = "SELECT r.*, u.name as user_name, u.position, u.role
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1 $dateFilter $roleFilterSQL
    ORDER BY r.created_at DESC LIMIT 10";
$recentResult = $conn->query($recentQuery);

// Get statistics by role
$byRoleQuery = "SELECT u.role, COUNT(r.id) as count
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1 $dateFilter
    GROUP BY u.role";
$byRoleResult = $conn->query($byRoleQuery);

$conn->close();
?>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">

    <!-- KIRI -->
     
    <div>
        <h1 class="page-title">Rekap Laporan</h1>
        <p class="page-subtitle">Ringkasan laporan </p>
    </div>

    <!-- KANAN -->
  <div class="page-actions" style="display: flex; gap: 0.5rem;">
    <button class="btn btn-outline" onclick="exportToCSV()">
        <i class="fas fa-download"></i>
        Export CSV
    </button>

    <!-- 🔥 INI YANG DIUBAH -->
    <button class="btn btn-outline"
        onclick="window.open('cetak.php','_blank')">
        <i class="fas fa-print"></i>
        Cetak
    </button>
</div>

</div>


<!-- Kinerja per Pengguna -->
 <br>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📋 Kinerja per Pengguna</h3>
        <p class="card-subtitle">Detail laporan berdasarkan individu</p>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Jabatan</th>
                        <th style="text-align: center;">Total</th>
                        <th style="text-align: center;">Disetujui</th>
                        <th style="text-align: center;">Pending</th>
                        <th style="text-align: center;">Ditolak</th>
                        <th>Tingkat Sukses</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($byUserResult->num_rows > 0): ?>
                        <?php while ($u = $byUserResult->fetch_assoc()): ?>
                        <?php 
                        $successRate = $u['count'] > 0 ? round(($u['approved_count'] / $u['count']) * 100) : 0;
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.875rem;">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($u['role']); ?>">
                                    <?php echo getRoleLabel($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($u['position'] ?? '-'); ?></td>
                            <td style="text-align: center; font-weight: 600;"><?php echo $u['count']; ?></td>
                            <td style="text-align: center;"><span style="color: #10b981; font-weight: 600;"><?php echo $u['approved_count']; ?></span></td>
                            <td style="text-align: center;"><span style="color: #f59e0b; font-weight: 600;"><?php echo $u['pending_count']; ?></span></td>
                            <td style="text-align: center;"><span style="color: #ef4444; font-weight: 600;"><?php echo $u['rejected_count']; ?></span></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="background: #e2e8f0; flex: 1; height: 8px; border-radius: 4px; overflow: hidden;">
                                        <div style="background: <?php echo $successRate >= 80 ? '#10b981' : ($successRate >= 50 ? '#f59e0b' : '#ef4444'); ?>; width: <?php echo $successRate; ?>%; height: 100%;"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; font-weight: 600; min-width: 35px;"><?php echo $successRate; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                Belum ada data laporan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Laporan Terbaru -->
 <br>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📄 Laporan Terbaru</h3>
        <p class="card-subtitle"> laporan terakhir yang masuk</p>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pelapor</th>
                        <th>Judul</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentResult->num_rows > 0): ?>
                        <?php while ($report = $recentResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatTanggalSingkat($report['activity_date']); ?></td>
                            <td>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($report['user_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo htmlspecialchars($report['position'] ?? '-'); ?></div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($report['title']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo htmlspecialchars($report['description']); ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($report['status']); ?>">
                                    <?php 
                                    echo $report['status'] === 'PENDING' ? 'Menunggu' : 
                                         ($report['status'] === 'DISETUJUI' ? 'Disetujui' : 'Ditolak'); 
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                Belum ada laporan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
