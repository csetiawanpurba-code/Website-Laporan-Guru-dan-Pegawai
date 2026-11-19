<?php
require_once __DIR__ . '/includes/header.php';
requireAdmin();

$conn = getConnection();

// Handle verify/approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = (int)$_POST['report_id'];
    $action = $_POST['action'];
    $rejectionNote = sanitize($_POST['rejection_note'] ?? '');
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reports SET status='DISETUJUI', verified_by=?, verified_at=NOW() WHERE id=?");
        $stmt->bind_param("ii", $user['id'], $reportId);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['message' => 'Laporan berhasil disetujui', 'type' => 'success'];
        }
        $stmt->close();
    } elseif ($action === 'reject' && !empty($rejectionNote)) {
        $stmt = $conn->prepare("UPDATE reports SET status='DITOLAK', verified_by=?, verified_at=NOW(), rejection_note=? WHERE id=?");
        $stmt->bind_param("isi", $user['id'], $rejectionNote, $reportId);
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['message' => 'Laporan telah ditolak', 'type' => 'warning'];
        }
        $stmt->close();
    }
    
    redirect('/verifikasi.php');
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Build query
$where = "1=1";
if ($statusFilter !== 'all') {
    $where .= " AND r.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

$reportsResult = $conn->query("
    SELECT r.*, u.name as user_name, u.email, u.position, u.role, u.nip,
           v.name as verifier_name 
    FROM reports r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN users v ON r.verified_by = v.id 
    WHERE $where 
    ORDER BY 
        CASE WHEN r.status = 'PENDING' THEN 0 ELSE 1 END,
        r.created_at DESC
");

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Verifikasi Laporan</h1>
    <p class="page-subtitle">Periksa dan validasi laporan guru dan pegawai</p>
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
            <div style="display: flex; flex-direction: column;">
                <div style="display: flex; gap: 1rem;">
                    <div class="report-avatar" style="width: 48px; height: 48px;">
                        <?php echo strtoupper(substr($report['user_name'], 0, 1)); ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                            <span class="badge badge-<?php echo strtolower($report['status']); ?>">
                                <?php 
                                echo $report['status'] === 'PENDING' ? 'Menunggu' : 
                                     ($report['status'] === 'DISETUJUI' ? 'Disetujui' : 'Ditolak'); 
                                ?>
                            </span>
                            <span class="badge badge-<?php echo strtolower($report['role']); ?>">
                                <?php echo $report['role']; ?>
                            </span>
                            <span style="font-size: 0.875rem; color: #64748b;">
                                <?php echo formatTanggalSingkat($report['activity_date']); ?>
                            </span>
                        </div>
                        <h3 style="font-weight: 600;"><?php echo htmlspecialchars($report['title']); ?></h3>
                        <p style="color: #64748b; font-size: 0.875rem; margin-top: 0.25rem;">
                            <?php echo htmlspecialchars($report['description']); ?>
                        </p>
                        <div style="display: flex; gap: 1.5rem; margin-top: 0.75rem; font-size: 0.8125rem; color: #64748b;">
                            <span><strong>Pelapor:</strong> <?php echo htmlspecialchars($report['user_name']); ?></span>
                            <?php if ($report['position']): ?>
                            <span><strong>Jabatan:</strong> <?php echo htmlspecialchars($report['position']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($report['status'] === 'DITOLAK' && $report['rejection_note']): ?>
                <div class="report-rejection" style="margin-top: 0.75rem; margin-left: 60px;">
                    <strong>Alasan Penolakan:</strong> <?php echo htmlspecialchars($report['rejection_note']); ?>
                </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: flex-end; gap: 0.5rem; padding: 1rem; margin-top: 1rem; border-top: 1px solid #e2e8f0; background: #f8fafc;">
                    <?php if ($report['status'] === 'PENDING'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i>
                            Setujui
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" onclick="showRejectModal(<?php echo $report['id']; ?>)">
                        <i class="fas fa-times"></i>
                        Tolak
                    </button>
                    <?php else: ?>
                    <button class="btn btn-outline btn-sm" onclick="viewReport(<?php echo htmlspecialchars(json_encode($report)); ?>)">
                        <i class="fas fa-eye"></i>
                        Detail
                    </button>
                    <?php if ($report['verifier_name']): ?>
                    <span style="font-size: 0.75rem; color: #64748b; display: flex; align-items: center;">
                        Diverifikasi oleh <strong style="margin-left: 0.25rem;"><?php echo htmlspecialchars($report['verifier_name']); ?></strong>
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem; color: #94a3b8;">
                <i class="fas fa-check-double" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Tidak ada laporan untuk diverifikasi</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Reject Modal -->
<div class="modal-overlay" id="rejectModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tolak Laporan</h3>
            <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="report_id" id="rejectReportId">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label class="form-label">Alasan Penolakan *</label>
                    <textarea name="rejection_note" class="form-control" rows="3" required
                              placeholder="Jelaskan alasan laporan ditolak..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Laporan</button>
            </div>
        </form>
    </div>
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
function showRejectModal(reportId) {
    document.getElementById('rejectReportId').value = reportId;
    openModal('rejectModal');
}

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
            <p><strong>Pelapor:</strong> ${report.user_name}</p>
            <p><strong>Jabatan:</strong> ${report.position || '-'}</p>
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
