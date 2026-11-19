<?php
require_once __DIR__ . '/includes/header.php';

$conn = getConnection();

// Get statistics based on role
if ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH') {
    // Admin & Kepala Sekolah see all reports
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
        FROM reports";
} else {
    // Guru & Pegawai see only their reports
    $statsQuery = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
        FROM reports WHERE user_id = " . $user['id'];
}
$stats = $conn->query($statsQuery)->fetch_assoc();

// Get recent reports
if ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH') {
    $reportsQuery = "SELECT r.*, u.name as user_name, u.position, u.role,
        v.name as verifier_name
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        LEFT JOIN users v ON r.verified_by = v.id
        ORDER BY r.created_at DESC LIMIT 5";
} else {
    $reportsQuery = "SELECT r.*, v.name as verifier_name
        FROM reports r 
        LEFT JOIN users v ON r.verified_by = v.id
        WHERE r.user_id = " . $user['id'] . "
        ORDER BY r.created_at DESC LIMIT 5";
}
$reportsResult = $conn->query($reportsQuery);

// Get chart data (last 14 days)
if ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH') {
    $chartQuery = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
        FROM reports 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date";
} else {
    $chartQuery = "SELECT 
        DATE(created_at) as date,
        COUNT(*) as total,
        SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
        FROM reports 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        AND user_id = " . $user['id'] . "
        GROUP BY DATE(created_at)
        ORDER BY date";
}

$chartResult = $conn->query($chartQuery);
$chartData = [];
while ($row = $chartResult->fetch_assoc()) {
    $chartData[] = $row;
}
if (empty($chartData)) {
    $chartData[] = [
        'date' => date('Y-m-d'),
        'approved' => $stats['approved'],
        'pending' => $stats['pending'],
        'rejected' => $stats['rejected'],
        'total' => $stats['total']
    ];
}
// Get user count for admin
$totalUsers = 0;
if ($user['role'] === 'ADMIN') {
    $totalUsersResult = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'ADMIN'");
    $totalUsers = $totalUsersResult->fetch_assoc()['count'];
}

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Selamat datang, <?php echo htmlspecialchars($user['name']); ?>!</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-label">Total Laporan</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-icon">
            <i class="fas fa-file-alt"></i>
            <span>Seluruh laporan</span>
        </div>
    </div>
    
    <div class="stat-card pending">
        <div class="stat-label">Menunggu</div>
        <div class="stat-value"><?php echo $stats['pending']; ?></div>
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
            <span>Perlu verifikasi</span>
        </div>
    </div>
    
    <div class="stat-card approved">
        <div class="stat-label">Disetujui</div>
        <div class="stat-value"><?php echo $stats['approved']; ?></div>
        <div class="stat-icon">
            <i class="fas fa-check-circle"></i>
            <span>Laporan valid</span>
        </div>
    </div>
    
    <div class="stat-card rejected">
        <div class="stat-label">Ditolak</div>
        <div class="stat-value"><?php echo $stats['rejected']; ?></div>
        <div class="stat-icon">
            <i class="fas fa-times-circle"></i>
            <span>Perlu revisi</span>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📈 Grafik Laporan</h3>
            <p class="card-subtitle">Tren laporan Beberapa hari terakhir</p>
        </div>
        <div class="card-body">
            <div style="height: 250px;">
                <?php if (count($chartData) > 0): ?>
                <table style="width: 100%; height: 100%;">
                    <tr style="vertical-align: bottom; height: 180px;">
                        <?php foreach ($chartData as $data): ?>
                        <td style="text-align: center; vertical-align: bottom;">
                            <div style="display: flex; align-items: flex-end; justify-content: center; gap: 2px;">
                                <!-- APPROVED -->
                                <div style="background: linear-gradient(to top, #10b981, #34d399); width: 25px; height: <?php echo max($data['approved'] * 20, 5); ?>px; border-radius: 4px 4px 0 0;"></div>
                                <!-- PENDING -->
                                <?php if ($data['pending'] > 0): ?>
                                <div style="background: linear-gradient(to top, #f59e0b, #fbbf24); width: 25px; height: <?php echo max($data['pending'] * 20, 5); ?>px; border-radius: 0 0 4px 4px; margin-top: 2px;"></div>
                                <?php endif; ?>
                                <!-- REJECTED -->
                                <?php if ($data['rejected'] > 0): ?>
                                <div style="background: linear-gradient(to top, #ef4444, #f87171); width: 25px; height: <?php echo max($data['rejected'] * 20, 5); ?>px; border-radius: 0 0 4px 4px; margin-top: 2px;"></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($chartData as $data): ?>
                        <td style="text-align: center; font-size: 10px; color: #64748b; padding-top: 8px;">
                            <?php echo date('d/m', strtotime($data['date'])); ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
                <div style="display: flex; justify-content: center; gap: 1.5rem; margin-top: 0.1rem; font-size: 0.75rem; color: #64748b;">
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: #10b981; border-radius: 2px; margin-right: 4px;"></span> Disetujui</span>
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: #f59e0b; border-radius: 2px; margin-right: 4px;"></span> Menunggu</span>
                    <span><span style="display: inline-block; width: 12px; height: 12px; background: #ef4444; border-radius: 2px; margin-right: 4px;"></span> Ditolak</span>
                </div>
                <?php else: ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
                    <i class="fas fa-chart-bar" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                    <p>Belum ada data grafik</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📊 Status Laporan</h3>
            <p class="card-subtitle">Distribusi berdasarkan status</p>
        </div>
        <div class="card-body">
            <div style="height: 250px; display: flex; flex-direction: column; justify-content: center;">
                <?php if ($stats['total'] > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="flex: 1; height: 24px; background: #f1f5f9; border-radius: 12px; overflow: hidden; display: flex;">
                            <div style="width: <?php echo ($stats['approved'] / $stats['total']) * 100; ?>%; background: #10b981;"></div>
                            <div style="width: <?php echo ($stats['pending'] / $stats['total']) * 100; ?>%; background: #f59e0b;"></div>
                            <div style="width: <?php echo ($stats['rejected'] / $stats['total']) * 100; ?>%; background: #ef4444;"></div>
                        </div>
                    </div>
                    <div style="display: flex; justify-content: center; gap: 2rem; flex-wrap: wrap; font-size: 0.875rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></span>
                            Disetujui: <?php echo $stats['approved']; ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #f59e0b; border-radius: 2px;"></span>
                            Menunggu: <?php echo $stats['pending']; ?>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="display: inline-block; width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></span>
                            Ditolak: <?php echo $stats['rejected']; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
                    <i class="fas fa-chart-pie" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                    <p>Belum ada data laporan</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<br>
<!-- Recent Reports -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📄 Laporan Terbaru</h3>
        <p class="card-subtitle">laporan terakhir</p>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Judul</th>
                        <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH'): ?>
                        <th>Pelapor</th>
                        <?php endif; ?>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reportsResult->num_rows > 0): ?>
                        <?php while ($report = $reportsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatTanggalSingkat($report['activity_date']); ?></td>
                            <td><?php echo htmlspecialchars($report['title']); ?></td>
                            <?php if ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH'): ?>
                            <td>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($report['user_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8;"><?php echo htmlspecialchars($report['position'] ?? '-'); ?></div>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td>
                                <span class="badge badge-<?php echo strtolower($report['status']); ?>">
                                    <?php echo $report['status'] === 'PENDING' ? 'Menunggu' : ($report['status'] === 'DISETUJUI' ? 'Disetujui' : 'Ditolak'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?php echo ($user['role'] === 'ADMIN' || $user['role'] === 'KEPALA_SEKOLAH') ? 4 : 3; ?>" style="text-align: center; color: #94a3b8;">
                                Belum ada laporan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.quick-action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-main);
    border-radius: var(--border-radius-sm);
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.2s ease;
}

.quick-action-card:hover {
    background: var(--bg-white);
    box-shadow: var(--shadow);
}

.quick-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.quick-action-content h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    margin-bottom: 0.125rem;
}

.quick-action-content p {
    font-size: 0.8125rem;
    color: var(--text-secondary);
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
