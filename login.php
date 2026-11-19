<?php
require_once __DIR__ . '/config/database.php';

if (isLoggedIn()) {
    redirect('/dashboard.php');
}

$error = '';
$success = '';
$step_forgot = 'email'; // email -> reset -> selesai

// =======================
// HAPUS PESAN SAAT PINDAH TAB
// =======================
if (isset($_GET['clear_msg'])) {
    $error = '';
    $success = '';
    $step_forgot = 'email';
}

// =======================
// HANDLE DAFTAR — DENGAN PILIHAN PERAN
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role_dipilih = $_POST['role'] ?? '';

    $daftar_role_valid = ['PEGAWAI', 'GURU', 'KEPALA_SEKOLAH', 'ADMIN'];

    if (empty($name) || empty($email) || empty($password) || empty($role_dipilih)) {
        $error = 'Semua kolom wajib diisi, termasuk memilih peran';
    } elseif (!in_array($role_dipilih, $daftar_role_valid)) {
        $error = 'Peran yang dipilih tidak valid';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi minimal 6 karakter';
    } elseif ($password !== $password_confirm) {
        $error = 'Konfirmasi kata sandi tidak cocok';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Email sudah terdaftar';
            $stmt->close();
            $conn->close();
        } else {
            $stmt->close();

            if ($role_dipilih === 'ADMIN') {
                $cek_admin = $conn->query("SELECT id FROM users WHERE role = 'ADMIN' LIMIT 1");
                if ($cek_admin->num_rows > 0) {
                    $error = 'Sudah ada Admin. Silakan pilih peran lain atau hubungi Admin.';
                    $conn->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $name, $email, $password, $role_dipilih);
                    
                    if ($stmt->execute()) {
                        $success = 'Pendaftaran berhasil! Anda terdaftar sebagai <strong>ADMIN</strong> — Akun pertama sistem.';
                    } else {
                        $error = 'Terjadi kesalahan, coba lagi.';
                    }
                    $stmt->close();
                    $conn->close();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $name, $email, $password, $role_dipilih);
                
                if ($stmt->execute()) {
                    $label_role = str_replace('_', ' ', $role_dipilih);
                    $success = "Pendaftaran berhasil! Terdaftar sebagai: <strong>$label_role</strong>";
                } else {
                    $error = 'Terjadi kesalahan, coba lagi.';
                }
                $stmt->close();
                $conn->close();
            }
        }
    }
}

// =======================
// HANDLE LUPA KATA SANDI
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_email_forgot'])) {
    $error = '';
    $email = sanitize($_POST['email_forgot'] ?? '');
    
    if (empty($email)) {
        $error = 'Masukkan email Anda';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        
        if ($user) {
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_user_email'] = $email;
            $step_forgot = 'reset';
        } else {
            $error = 'Email tidak ditemukan di sistem';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_sandi_baru'])) {
    $error = '';
    $user_id = (int)($_SESSION['reset_user_id'] ?? 0);
    $sandibaru = $_POST['sandibaru'] ?? '';
    $sandibaru_ulang = $_POST['sandibaru_ulang'] ?? '';
    
    if (!$user_id) {
        $error = 'Sesi habis, silakan ulangi';
        $step_forgot = 'email';
    } elseif (empty($sandibaru)) {
        $error = 'Masukkan kata sandi baru';
        $step_forgot = 'reset';
    } elseif (strlen($sandibaru) < 6) {
        $error = 'Kata sandi minimal 6 karakter';
        $step_forgot = 'reset';
    } elseif ($sandibaru !== $sandibaru_ulang) {
        $error = 'Kata sandi tidak sama';
        $step_forgot = 'reset';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $sandibaru, $user_id);
        
        if ($stmt->execute()) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_user_email']);
            $success = 'Kata sandi berhasil diubah! Silakan login.';
            $step_forgot = 'selesai';
        } else {
            $error = 'Gagal mengubah kata sandi';
            $step_forgot = 'reset';
        }
        $stmt->close();
        $conn->close();
    }
}

if (isset($_POST['batal_reset'])) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_user_email']);
    $step_forgot = 'email';
}

// =======================
// HANDLE LOGIN — TANPA HASH
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan kata sandi harus diisi';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            redirect('/dashboard.php');
        } else {
            $error = 'Email atau kata sandi salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Laporan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background: #ffffff;
            font-family: 'Segoe UI', sans-serif;
            color: #333;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo img {
            width: 160px;
            height: auto;
            margin-bottom: 12px;
        }
        .login-logo h1 { font-size: 22px; margin-bottom: 4px; color: #111827; }
        .login-logo h3 { font-size: 16px; margin-bottom: 4px; color: #4b5563; font-weight: 500; }
        .login-logo h4 { font-size: 15px; color: #374151; font-weight: 600; }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            transition: opacity 0.3s ease;
        }
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-left: 3px solid #fca5a5;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-left: 3px solid #86efac;
        }
        .alert.hidden {
            display: none;
        }

        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 14px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        .form-control {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 15px;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        /* TOMBOL SESUAI CONTOH */
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 999px; /* bulat penuh */
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-login {
            background: #2563eb;
            color: #ffffff;
            margin-bottom: 20px;
        }
        .btn-login:hover {
            background: #1d4ed8;
        }
        .btn-register-outline {
            background: #ffffff;
            color: #2563eb;
            border: 1px solid #2563eb;
            margin-top: 10px;
        }
        .btn-register-outline:hover {
            background: #eff6ff;
        }
        .forgot-link-center {
            text-align: center;
            margin: 10px 0 20px;
        }
        .forgot-link-center a {
            font-size: 15px;
            color: #1f2937;
            text-decoration: none;
        }
        .forgot-link-center a:hover {
            text-decoration: underline;
        }

        .info-text {
            background: #eff6ff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #1e40af;
        }
        .back-link {
            text-align: center;
            margin-top: 16px;
        }
        .back-link a, .back-link button {
            font-size: 14px;
            color: #6b7280;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
        }
        .back-link a:hover, .back-link button:hover {
            color: #2563eb;
            text-decoration: underline;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-card">
        <!-- LOGO & JUDUL -->
        <div class="login-logo">
            <img src="logosmk3.png" alt="Logo SMK N 3 Bangli">
            <h1>Sistem Laporan</h1>
            <h3>Guru dan Pegawai</h3>
            <h4>SMK N 3 BANGLI</h4>
        </div>

        <!-- PESAN — HILANG SAAT PINDAH -->
        <div id="pesan-area">
        <?php if ($error): ?>
        <div class="alert alert-danger" id="alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success" id="alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        </div>

        <!-- FORM MASUK -->
        <div class="tab-content active" id="login">
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Masukkan Email" required>
                </div>
                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
                </div>

                <!-- TOMBOL LOGIN BIRU BULAT PENUH -->
                <button type="submit" name="login" class="btn btn-login">
                    Login
                </button>

                <!-- LUPA KATA SANDI DI TENGAH -->
                <div class="forgot-link-center">
                    <a href="#" id="linkForgot">Lupa kata sandi?</a>
                </div>

                <!-- BUAT AKUN BARU GARIS TEPI BIRU -->
                <button type="button" class="btn btn-register-outline" onclick="switchTab('register')">
                    Buat akun baru
                </button>
            </form>
        </div>

        <!-- FORM DAFTAR -->
        <div class="tab-content" id="register">
            <form method="POST">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" class="form-control" placeholder="Nama lengkap Anda" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required>
                </div>
                <div class="form-group">
                    <label>Peran / Jabatan</label>
                    <select name="role" class="form-control" required>
                        <option value="">-- Pilih peran Anda --</option>
                        <option value="PEGAWAI">PEGAWAI — Staf Tata Usaha & Pegawai</option>
                        <option value="GURU">GURU — Tenaga Pengajar</option>
                        <option value="KEPALA_SEKOLAH">KEPALA SEKOLAH — Pimpinan</option>
                        <option value="ADMIN">ADMIN — Pengelola Sistem</option>
                    </select>
                    <small style="color:#6b7280; margin-top:5px; display:block;">
                        ⚠️ Pilih sesuai jabatan.
                    </small>
                </div>
                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Konfirmasi Kata Sandi</label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi kata sandi" required>
                </div>
                <button type="submit" name="register" class="btn btn-login">
                    Daftar Akun
                </button>
                <div class="back-link">
                    <a href="#" onclick="switchTab('login');return false;">← Kembali ke Login</a>
                </div>
            </form>
        </div>

        <!-- FORM LUPA KATA SANDI -->
        <div class="tab-content" id="forgot">
            <?php if ($step_forgot === 'email'): ?>
            <form method="POST">
                <h3 style="margin-bottom:12px; font-size:17px; text-align:center;">Lupa Kata Sandi</h3>
                <div class="info-text">
                    Masukkan email Anda. Jika terdaftar, Anda dapat langsung membuat kata sandi baru.
                </div>
                <div class="form-group">
                    <label>Email Terdaftar</label>
                    <input type="email" name="email_forgot" class="form-control" placeholder="Masukkan email Anda" required>
                </div>
                <button type="submit" name="cek_email_forgot" class="btn btn-login">
                    Lanjutkan
                </button>
                <div class="back-link">
                    <a href="#" onclick="switchTab('login');return false;">← Kembali ke Login</a>
                </div>
            </form>
            <?php elseif ($step_forgot === 'reset'): ?>
            <form method="POST">
                <h3 style="margin-bottom:12px; font-size:17px; text-align:center;">Buat Kata Sandi Baru</h3>
                <div class="info-text">
                    Akun: <strong><?php echo htmlspecialchars($_SESSION['reset_user_email']); ?></strong>
                </div>
                <div class="form-group">
                    <label>Kata Sandi Baru</label>
                    <input type="password" name="sandibaru" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>
                <div class="form-group">
                    <label>Ulangi Kata Sandi Baru</label>
                    <input type="password" name="sandibaru_ulang" class="form-control" placeholder="Ketik ulang kata sandi baru" required>
                </div>
                <button type="submit" name="simpan_sandi_baru" class="btn btn-login">
                    Simpan Kata Sandi
                </button>
                <div class="back-link">
                    <button type="submit" name="batal_reset">Batal</button>
                </div>
            </form>
            <?php elseif ($step_forgot === 'selesai'): ?>
            <div style="text-align:center; padding:15px 0;">
                <p style="margin-bottom:15px;">Kata sandi sudah diperbarui.</p>
                <a href="#" onclick="switchTab('login');return false;" class="btn btn-login" style="display:inline-block; text-decoration:none; line-height:1.5;">
                    Pergi ke Halaman Login
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="login-footer">
    Sistem Laporan Guru dan Pegawai SMK NEGERI 3 Bangli
</footer>

<script>
function switchTab(tabId) {
    // HAPUS PESAN SAAT PINDAH TAB
    document.querySelectorAll('.alert').forEach(alert => {
        alert.style.display = 'none';
    });
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
}

// Buka Lupa Kata Sandi
document.getElementById('linkForgot').addEventListener('click', e => {
    e.preventDefault();
    switchTab('forgot');
});
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>