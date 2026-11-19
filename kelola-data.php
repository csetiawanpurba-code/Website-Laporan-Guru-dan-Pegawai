<?php
require_once __DIR__ . '/includes/header.php';

// Hanya Admin yang bisa akses
if ($user['role'] !== 'ADMIN') {
    redirect('/dashboard.php');
}

$conn = getConnection();

// Handle delete
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    
    // Delete user's reports first
    $conn->query("DELETE FROM reports WHERE user_id = $deleteId");
    
    // Delete user (tidak bisa hapus admin)
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'ADMIN'");
    $stmt->bind_param("i", $deleteId);
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['message' => 'Data berhasil dihapus', 'type' => 'success'];
    }
    $stmt->close();
    redirect('/kelola-data.php');
}

// Handle create/update
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $nip = sanitize($_POST['nip'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $position = sanitize($_POST['position'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $birthDate = $_POST['birth_date'] ?? null;
    
    if ($id > 0) {
    // Update
    if (!empty($_POST['password'])) {

        // ❗ DIUBAH DI SINI
        $password = $_POST['password'];

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=?, nip=?, phone=?, position=?, address=?, gender=?, birth_date=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $name, $email, $password, $role, $nip, $phone, $position, $address, $gender, $birthDate, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=?, nip=?, phone=?, position=?, address=?, gender=?, birth_date=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $name, $email, $role, $nip, $phone, $position, $address, $gender, $birthDate, $id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['message' => 'Data berhasil diupdate', 'type' => 'success'];
    }
    $stmt->close();

} else {
    // Create

    // ❗ DIUBAH DI SINI
    $password = $_POST['password'];

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, nip, phone, position, address, gender, birth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $name, $email, $password, $role, $nip, $phone, $position, $address, $gender, $birthDate);
    
    if ($stmt->execute()) {
        $_SESSION['toast'] = ['message' => 'Data berhasil ditambahkan', 'type' => 'success'];
    }
    $stmt->close();
}
    
    redirect('/kelola-data.php');
}

// Get users with filters
$where = "WHERE role IN ('GURU', 'PEGAWAI', 'KEPALA_SEKOLAH')";
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? 'all';

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%$searchEscaped%' OR email LIKE '%$searchEscaped%' OR nip LIKE '%$searchEscaped%')";
}

if ($roleFilter !== 'all') {
    $where .= " AND role = '" . $conn->real_escape_string($roleFilter) . "'";
}

$usersResult = $conn->query("SELECT *, (SELECT COUNT(*) FROM reports WHERE user_id = users.id) as report_count FROM users $where ORDER BY created_at DESC");

$conn->close();
?>

<div class="page-header">
    <h1 class="page-title">Kelola Data Pengguna</h1>
    <p class="page-subtitle">Kelola data guru, pegawai, dan kepala sekolah</p>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('userModal')">
            <i class="fas fa-plus"></i>
            Tambah Data
        </button>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <div class="search-box" style="flex: 1; min-width: 200px;">
        <i class="fas fa-search"></i>
        <input type="text" class="form-control" placeholder="Cari nama, email, atau NIP..." 
               value="<?php echo htmlspecialchars($search); ?>" 
               onchange="this.form.submit()" form="filterForm">
    </div>
    <form id="filterForm" method="GET" style="display: flex; gap: 1rem;">
        <select name="role" class="form-control" style="width: 180px;" onchange="this.form.submit()">
            <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>>Semua Role</option>
            <option value="KEPALA_SEKOLAH" <?php echo $roleFilter === 'KEPALA_SEKOLAH' ? 'selected' : ''; ?>>Kepala Sekolah</option>
            <option value="GURU" <?php echo $roleFilter === 'GURU' ? 'selected' : ''; ?>>Guru</option>
            <option value="PEGAWAI" <?php echo $roleFilter === 'PEGAWAI' ? 'selected' : ''; ?>>Pegawai</option>
        </select>
        <?php if (!empty($search)): ?>
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
        <?php endif; ?>
    </form>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>NIP</th>
                        <th>Jabatan</th>
                        <th>Role</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($usersResult->num_rows > 0): ?>
                        <?php while ($u = $usersResult->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="user-avatar" style="width: 32px; height: 32px; font-size: 0.875rem;">
                                        <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                    </div>
                                    <span style="font-weight: 500;"><?php echo htmlspecialchars($u['name']); ?></span>
                                </div>
                            </td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td style="color: #64748b;"><?php echo htmlspecialchars($u['nip'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($u['position'] ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower(str_replace('_', '-', $u['role'])); ?>">
                                    <?php echo getRoleLabel($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="?edit=<?php echo $u['id']; ?>" class="btn btn-outline btn-icon" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $u['id']; ?>" 
                                       class="btn btn-outline btn-icon" 
                                       style="color: #ef4444;"
                                       onclick="return confirm('Yakin ingin menghapus data ini?')"
                                       title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">
                                Tidak ada data pengguna
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title"><?php echo $editUser ? 'Edit Data' : 'Tambah Data'; ?></h3>
            <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="id" value="<?php echo $editUser['id'] ?? ''; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($editUser['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?php echo $editUser ? 'Password Baru' : 'Password *'; ?></label>
                        <input type="password" name="password" class="form-control" 
                               <?php echo $editUser ? '' : 'required'; ?>
                               placeholder="<?php echo $editUser ? 'Kosongkan jika tidak diubah' : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role *</label>
                        <select name="role" class="form-control" required>
                            <option value="KEPALA_SEKOLAH" <?php echo ($editUser['role'] ?? '') === 'KEPALA_SEKOLAH' ? 'selected' : ''; ?>>Kepala Sekolah</option>
                            <option value="GURU" <?php echo ($editUser['role'] ?? '') === 'GURU' ? 'selected' : ''; ?>>Guru</option>
                            <option value="PEGAWAI" <?php echo ($editUser['role'] ?? '') === 'PEGAWAI' ? 'selected' : ''; ?>>Pegawai</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" class="form-control"
                               value="<?php echo htmlspecialchars($editUser['nip'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($editUser['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="position" class="form-control"
                               value="<?php echo htmlspecialchars($editUser['position'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-control">
                            <option value="">Pilih</option>
                            <option value="Laki-laki" <?php echo ($editUser['gender'] ?? '') === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo ($editUser['gender'] ?? '') === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control"
                           value="<?php echo $editUser['birth_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($editUser['address'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('userModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php if ($editUser): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        openModal('userModal');
    });
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
