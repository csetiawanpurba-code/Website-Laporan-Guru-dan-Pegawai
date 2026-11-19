<?php
require_once __DIR__ . '/includes/header.php';

$conn = getConnection();
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $nip = sanitize($_POST['nip'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $position = sanitize($_POST['position'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $gender = sanitize($_POST['gender'] ?? '');
    $birthDate = $_POST['birth_date'] ?? null;
    $newPassword = $_POST['new_password'] ?? '';
    
    // Check if email is taken by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user['id']);
    $stmt->execute();
    $emailCheck = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($emailCheck) {
    $error = 'Email sudah digunakan oleh pengguna lain';
} else {
    // Update user
    if (!empty($newPassword)) {
        // ❗ DIUBAH DI SINI
        $hashedPassword = $newPassword;

        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, nip=?, phone=?, position=?, address=?, gender=?, birth_date=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $name, $email, $hashedPassword, $nip, $phone, $position, $address, $gender, $birthDate, $user['id']);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, nip=?, phone=?, position=?, address=?, gender=?, birth_date=? WHERE id=?");
        $stmt->bind_param("ssssssssi", $name, $email, $nip, $phone, $position, $address, $gender, $birthDate, $user['id']);
    }
    
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $success = 'Profil berhasil diperbarui';
        
        // Refresh user data
        $user = getCurrentUser();
    } else {
        $error = 'Gagal memperbarui profil';
    }
    $stmt->close();
}
}

$conn->close();
?>

<center>
<div class="page-header">
    <h1 class="page-title">Profil Pengguna</h1>
    <p class="page-subtitle">Kelola informasi akun Anda</p>
</div>
</center>
<div style="max-width: 600px; margin: 0 auto;">
    <div class="card">
        <div class="card-header" style="display: flex; align-items: center; gap: 1rem;">
            <div class="user-avatar" style="width: 64px; height: 64px; font-size: 1.5rem;">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div>
                <h3 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h3>
                <p class="card-subtitle"><?php echo htmlspecialchars($user['position'] ?? $user['role']); ?></p>
            </div>
        </div>
        
        <div class="card-body">
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" required
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" class="form-control"
                               value="<?php echo htmlspecialchars($user['nip'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="position" class="form-control"
                               value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="gender" class="form-control form-select">
                            <option value="">Pilih</option>
                            <option value="Laki-laki" <?php echo ($user['gender'] ?? '') === 'Laki-laki' ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="Perempuan" <?php echo ($user['gender'] ?? '') === 'Perempuan' ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="birth_date" class="form-control"
                               value="<?php echo $user['birth_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="address" class="form-control"
                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                    </div>
                </div>
                
                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #e2e8f0;">
                
                <div class="form-group">
                    <label class="form-label">Password Baru</label>
                    <input type="password" name="new_password" class="form-control"
                           placeholder="Kosongkan jika tidak ingin mengubah password">
                    <small style="color: #94a3b8; font-size: 0.75rem;">Kosongkan jika tidak ingin mengubah password</small>
                </div>
                <CEnter>
                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Simpan Perubahan
                    </button>
                </div>
                </CEnter>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
