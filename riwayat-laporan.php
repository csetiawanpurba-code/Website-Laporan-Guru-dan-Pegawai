<?php
require_once __DIR__ . '/includes/header.php';

// Only allow GURU and PEGAWAI
if ($user['role'] === 'ADMIN') {
    redirect('/dashboard.php');
}

$conn = getConnection();

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$where = "WHERE r.user_id = " . $user['id'];
if ($statusFilter !== 'all') {
    $where .= " AND r.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$reportsResult = $conn->query("
    SELECT r.*, v.name as verifier_name 
    FROM reports r 
    LEFT JOIN users v ON r.verified_by = v.id 
    $where 
    ORDER BY r.created_at DESC
");

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Riwayat Laporan</h1>
    <p class="page-subtitle">Daftar semua laporan yang telah dibuat</p>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div style="display: flex; align-items: center; gap: 1rem;">
        <i class="fas fa-filter" style="color: #64748b;"></i>
        <form method="GET" style="display: flex; gap: 1rem;">
            <select name="status" class="form-control" style="width: 150px;" onchange="this.form.submit()">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                <option value="PENDING" <?php echo $statusFilter === 'PENDING' ? 'selected' : ''; ?>>Menunggu</option>
                <option value="DISETUJUI" <?php echo $statusFilter === 'DISETUJUI' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="DITOLAK" <?php echo $statusFilter === 'DITOLAK' ? 'selected' : ''; ?>>Ditolak</option>
            </select>
        </form>
    </div>
</div>

<!-- Reports List -->
<div class="reports-list">
    <?php if ($reportsResult->num_rows > 0): ?>
        <?php while ($report = $reportsResult->fetch_assoc()): ?>
        <div class="report-card <?php echo $report['status'] === 'PENDING' ? 'highlight' : ''; ?>">
            <div style="display: flex; flex-direction: column; flex-direction: row;">
                <div style="flex: 1; padding: 1.25rem;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                                <span class="badge badge-<?php echo strtolower($report['status']); ?>">
                                    <?php 
                                    echo $report['status'] === 'PENDING' ? 'Menunggu' : 
                                         ($report['status'] === 'DISETUJUI' ? 'Disetujui' : 'Ditolak'); 
                                    ?>
                                </span>
                                <span style="font-size: 0.875rem; color: #64748b;">
                                    <?php echo formatTanggal($report['activity_date']); ?>
                                </span>
                            </div>
                            <h3 style="font-weight: 600; font-size: 1rem;"><?php echo htmlspecialchars($report['title']); ?></h3>
                            <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($report['description']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ($report['status'] === 'DITOLAK' && $report['rejection_note']): ?>
                    <div class="report-rejection" style="margin-top: 0.75rem;">
                        <strong>Alasan Penolakan:</strong> <?php echo htmlspecialchars($report['rejection_note']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($report['verifier_name']): ?>
                    <p style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.75rem;">
                        Diverifikasi oleh <?php echo htmlspecialchars($report['verifier_name']); ?> 
                        pada <?php echo formatTanggalSingkat($report['verified_at']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 0.5rem; padding: 1rem; border-left: 1px solid #e2e8f0; background: #f8fafc; align-items: center;">
                    <button class="btn btn-outline btn-sm" onclick="viewReport(<?php echo htmlspecialchars(json_encode($report)); ?>)">
                        <i class="fas fa-eye"></i>
                        Detail
                    </button>
                    <?php if ($report['status'] === 'PENDING'): ?>
                    <a href="input-laporan.php?edit=<?php echo $report['id']; ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-edit"></i>
                        Edit
                    </a>
                    <a href="input-laporan.php?delete=<?php echo $report['id']; ?>" 
                       class="btn btn-outline btn-sm" 
                       style="color: #ef4444;"
                       onclick="return confirm('Yakin ingin menghapus laporan ini?')">
                        <i class="fas fa-trash"></i>
                        Hapus
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem; color: #94a3b8;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Tidak ada laporan ditemukan</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Detail Laporan</h3>
            <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body" id="viewContent">
            <!-- Content filled by JavaScript -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeModal('viewModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
function viewReport(report) {
    const statusLabels = {
        'PENDING': 'Menunggu',
        'DISETUJUI': 'Disetujui',
        'DITOLAK': 'Ditolak'
    };
    
    let html = `
        <div style="margin-bottom: 1rem;">
            <span class="badge badge-${report.status.toLowerCase()}">${statusLabels[report.status]}</span>
            <span style="color: #64748b; font-size: 0.875rem; margin-left: 0.5rem;">
                ${formatDate(report.activity_date)}
            </span>
        </div>
        <h3 style="font-weight: 600; font-size: 1.125rem; margin-bottom: 0.5rem;">${report.title}</h3>
        <p style="color: #64748b;">${report.description}</p>
        <hr style="margin: 1rem 0; border: none; border-top: 1px solid #e2e8f0;">
        <div style="font-size: 0.875rem; color: #64748b;">
            <p><strong>Dibuat:</strong> ${formatDate(report.created_at)}</p>
            ${report.verifier_name ? `<p><strong>Diverifikasi oleh:</strong> ${report.verifier_name}</p>` : ''}
            ${report.rejection_note ? `<div style="background: #fee2e2; padding: 0.75rem; border-radius: 8px; margin-top: 0.75rem; color: #b91c1c;"><strong>Alasan Penolakan:</strong> ${report.rejection_note}</div>` : ''}
        </div>
    `;
    
    document.getElementById('viewContent').innerHTML = html;
    openModal('viewModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
