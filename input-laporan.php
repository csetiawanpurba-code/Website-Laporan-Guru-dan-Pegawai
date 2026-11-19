<?php
require_once __DIR__ . '/includes/header.php';

// Only allow GURU and PEGAWAI
if ($user['role'] === 'ADMIN') {
    redirect('/dashboard.php');
}

$conn = getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM reports WHERE id = ? AND user_id = ? AND status = 'PENDING'");
    $stmt->bind_param("ii", $deleteId, $user['id']);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['toast'] = ['message' => 'Laporan berhasil dihapus', 'type' => 'success'];
    }
    $stmt->close();
    redirect('/input-laporan.php');
}

// Handle create/update
$editReport = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM reports WHERE id = ? AND user_id = ? AND status = 'PENDING'");
    $stmt->bind_param("ii", $editId, $user['id']);
    $stmt->execute();
    $editReport = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $activityDate = $_POST['activity_date'];
    
    if ($id > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE reports SET title=?, description=?, activity_date=? WHERE id=? AND user_id=? AND status='PENDING'");
        $stmt->bind_param("sssii", $title, $description, $activityDate, $id, $user['id']);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['toast'] = ['message' => 'Laporan berhasil diupdate', 'type' => 'success'];
        }
        $stmt->close();
    } else {
        // Create
        $stmt = $conn->prepare("INSERT INTO reports (user_id, title, description, activity_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user['id'], $title, $description, $activityDate);
        
        if ($stmt->execute()) {
            $_SESSION['toast'] = ['message' => 'Laporan berhasil dikirim', 'type' => 'success'];
        }
        $stmt->close();
    }
    
    redirect('/input-laporan.php');
}

// Get recent reports
$reportsResult = $conn->query("SELECT * FROM reports WHERE user_id = " . $user['id'] . " ORDER BY created_at DESC LIMIT 10");

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'DITOLAK' THEN 1 ELSE 0 END) as rejected
    FROM reports WHERE user_id = " . $user['id'];
$stats = $conn->query($statsQuery)->fetch_assoc();

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Input Laporan Kegiatan</h1>
    <p class="page-subtitle">Buat laporan kegiatan baru</p>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('reportModal')">
            <i class="fas fa-plus"></i>
            Buat Laporan
        </button>
    </div>
</div>

<!-- Quick Stats -->
<div class="grid-3">
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

<!-- Recent Reports -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Laporan Saya Terbaru</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Judul</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reportsResult->num_rows > 0): ?>
                        <?php while ($report = $reportsResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo formatTanggalSingkat($report['activity_date']); ?></td>
                            <td>
                                <div>
                                    <div style="font-weight: 500;"><?php echo htmlspecialchars($report['title']); ?></div>
                                    <div style="font-size: 0.75rem; color: #94a3b8; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
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
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-outline btn-icon" onclick="viewReport(<?php echo htmlspecialchars(json_encode($report)); ?>)" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($report['status'] === 'PENDING'): ?>
                                    <a href="?edit=<?php echo $report['id']; ?>" class="btn btn-outline btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $report['id']; ?>" 
                                       class="btn btn-outline btn-icon" 
                                       style="color: #ef4444;"
                                       onclick="return confirm('Yakin ingin menghapus laporan ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                Belum ada laporan. Klik "Buat Laporan" untuk membuat laporan baru.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal-overlay" id="reportModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $editReport ? 'Edit Laporan' : 'Buat Laporan Baru'; ?></h3>
            <button class="modal-close" onclick="closeModal('reportModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $editReport['id'] ?? ''; ?>">
                
                <div class="form-group">
                    <label class="form-label">Judul Kegiatan *</label>
                    <input type="text" name="title" class="form-control" required
                           placeholder="Judul kegiatan yang dilaporkan"
                           value="<?php echo htmlspecialchars($editReport['title'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deskripsi Kegiatan *</label>
                    <textarea name="description" class="form-control" rows="4" required
                              placeholder="Jelaskan kegiatan yang dilakukan..."><?php echo htmlspecialchars($editReport['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Kegiatan *</label>
                    <input type="date" name="activity_date" class="form-control" required
                           value="<?php echo $editReport['activity_date'] ?? date('Y-m-d'); ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('reportModal')">Batal</button>
                <button type="submit" class="btn btn-primary"><?php echo $editReport ? 'Simpan' : 'Kirim Laporan'; ?></button>
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
            ${report.rejection_note ? `<div style="background: #fee2e2; padding: 0.75rem; border-radius: 8px; margin-top: 0.75rem; color: #b91c1c;"><strong>Alasan Penolakan:</strong> ${report.rejection_note}</div>` : ''}
        </div>
    `;
    
    document.getElementById('viewContent').innerHTML = html;
    openModal('viewModal');
}
</script>

<?php if ($editReport): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        openModal('reportModal');
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
