<?php
require_once __DIR__ . '/includes/header.php';
requireAdmin();

$conn = getConnection();

// Get filter
$period = $_GET['period'] ?? 'all';

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

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
    FROM reports WHERE 1=1 $dateFilter";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Get reports by user
$byUserQuery = "SELECT u.name, COUNT(r.id) as count 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    WHERE 1=1 $dateFilter 
    GROUP BY u.id 
    ORDER BY count DESC 
    LIMIT 10";
$byUserResult = $conn->query($byUserQuery);

// Get detailed reports
$reportsQuery = "SELECT r.*, u.name as user_name, u.position, u.role,
    v.name as verifier_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN users v ON r.verified_by = v.id 
    WHERE 1=1 $dateFilter 
    ORDER BY r.activity_date DESC";
$reportsResult = $conn->query($reportsQuery);

// Get chart data (by date)
$chartQuery = "SELECT 
    DATE(activity_date) as date,
    COUNT(*) as total
    FROM reports 
    WHERE 1=1 $dateFilter 
    GROUP BY DATE(activity_date) 
    ORDER BY date";
$chartResult = $conn->query($chartQuery);

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Rekap Laporan</h1>
    <p class="page-subtitle">Ringkasan laporan berdasarkan periode</p>
    <div class="page-actions">
        <button class="btn btn-outline" onclick="exportToCSV()">
            <i class="fas fa-download"></i>
            Export CSV
        </button>
        <button class="btn btn-outline" onclick="window.print()">
            <i class="fas fa-print"></i>
            Cetak
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <i class="fas fa-filter" style="color: #64748b;"></i>
        <form method="GET" style="display: flex; gap: 1rem;">
            <select name="period" class="form-control" style="width: 150px;" onchange="this.form.submit()">
                <option value="all" <?php echo $period === 'all' ? 'selected' : ''; ?>>Semua</option>
                <option value="daily" <?php echo $period === 'daily' ? 'selected' : ''; ?>>Harian</option>
                <option value="weekly" <?php echo $period === 'weekly' ? 'selected' : ''; ?>>Mingguan</option>
                <option value="monthly" <?php echo $period === 'monthly' ? 'selected' : ''; ?>>Bulanan</option>
            </select>
        </form>
    </div>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card total">
        <div class="stat-label">Total Laporan</div>
        <div class="stat-value"><?php echo $stats['total']; ?></div>
    </div>
    <div class="stat-card pending">
        <div class="stat-label">Menunggu</div>
        <div class="stat-value"><?php echo $stats['pending']; ?></div>
    </div>
    <div class="stat-card approved">
        <div class="stat-label">Disetujui</div>
        <div class="stat-value"><?php echo $stats['approved']; ?></div>
    </div>
    <div class="stat-card rejected">
        <div class="stat-label">Ditolak</div>
        <div class="stat-value"><?php echo $stats['rejected']; ?></div>
    </div>
</div>

<div class="grid-2">
    <!-- Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Grafik Laporan per Tanggal</h3>
        </div>
        <div class="card-body">
            <div style="height: 250px;">
                <?php 
                $chartData = [];
                while ($row = $chartResult->fetch_assoc()) {
                    $chartData[] = $row;
                }
                
                if (count($chartData) > 0): 
                ?>
                <table style="width: 100%; height: 100%;">
                    <tr style="vertical-align: bottom; height: 180px;">
                        <?php foreach ($chartData as $data): ?>
                        <td style="text-align: center; vertical-align: bottom;">
                            <div style="display: flex; flex-direction: column; align-items: center;">
                                <div style="background: linear-gradient(to top, #6366f1, #818cf8); width: 25px; height: <?php echo max($data['total'] * 25, 5); ?>px; border-radius: 4px 4px 0 0;"></div>
                                <span style="font-size: 10px; font-weight: 600; margin-top: 4px;"><?php echo $data['total']; ?></span>
                            </div>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($chartData as $data): ?>
                        <td style="text-align: center; font-size: 9px; color: #64748b; padding-top: 4px;">
                            <?php echo date('d/m', strtotime($data['date'])); ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </table>
                <?php else: ?>
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #94a3b8;">
                    <i class="fas fa-chart-bar" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
                    <p>Belum ada data</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Reports by User -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Laporan per Pengguna</h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div style="max-height: 250px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th style="text-align: right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($byUserResult->num_rows > 0):
                            while ($u = $byUserResult->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['name']); ?></td>
                            <td style="text-align: right;">
                                <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                    <span style="background: #e0e7ff; width: <?php echo min($u['count'] * 15, 100); ?>px; height: 8px; border-radius: 4px;"></span>
                                    <?php echo $u['count']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="2" style="text-align: center; color: #94a3b8;">Belum ada data</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Reports Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Daftar Laporan</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container" style="max-height: 400px; overflow-y: auto;">
            <table class="table" id="reportTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Pelapor</th>
                        <th>Judul</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    $csvData = [['No', 'Tanggal', 'Pelapor', 'Jabatan', 'Judul', 'Deskripsi', 'Status']];
                    
                    if ($reportsResult->num_rows > 0):
                        while ($report = $reportsResult->fetch_assoc()): 
                            $csvData[] = [
                                $no,
                                formatTanggalSingkat($report['activity_date']),
                                $report['user_name'],
                                $report['position'] ?? '-',
                                $report['title'],
                                $report['description'],
                                $report['status']
                            ];
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
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
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #94a3b8; padding: 2rem;">
                            Tidak ada data laporan
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const csvData = <?php echo json_encode($csvData); ?>;

function exportToCSV() {
    const csv = csvData.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'rekap-laporan-<?php echo date('Y-m-d'); ?>.csv';
    link.click();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
