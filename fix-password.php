<?php
/**
 * FIX PASSWORD - Gunakan file ini untuk memperbaiki password login
 * 
 * Cara pakai:
 * 1. Letakkan file ini di folder sistem-laporan
 * 2. Akses via browser: http://localhost/sistem-laporan/fix-password.php
 * 3. Password akan otomatis diperbaiki
 * 4. HAPUS FILE INI setelah selesai!
 */

// Koneksi database
$conn = new mysqli('localhost', 'root', '', 'sistem_laporan_sekolah');

if ($conn->connect_error) {
    die("<div style='font-family: sans-serif; padding: 2rem; max-width: 600px; margin: 2rem auto;'>
            <h2 style='color: #ef4444;'>❌ Koneksi Gagal</h2>
            <p>{$conn->connect_error}</p>
            <p>Pastikan:</p>
            <ol>
                <li>XAMPP MySQL sudah running (hijau)</li>
                <li>Database 'sistem_laporan_sekolah' sudah di-import</li>
            </ol>
         </div>");
}

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Password</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f1f5f9; min-height: 100vh; padding: 2rem; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        h1 { color: #1e293b; margin-bottom: 0.5rem; }
        h2 { color: #1e293b; margin-bottom: 1rem; }
        .subtitle { color: #64748b; margin-bottom: 1.5rem; }
        .success { background: #d1fae5; color: #047857; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.75rem; }
        .error { background: #fee2e2; color: #b91c1c; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.75rem; }
        .warning { background: #fef3c7; color: #b45309; padding: 1rem; border-radius: 8px; margin-top: 1.5rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #6366f1; color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; text-decoration: none; margin-top: 1rem; }
        .btn:hover { background: #4f46e5; }
        .account-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .account-table th, .account-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .account-table th { background: #f8fafc; font-weight: 600; color: #64748b; font-size: 0.875rem; }
        .badge { padding: 0.25rem 0.625rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
        .badge-admin { background: #ede9fe; color: #7c3aed; }
        .badge-kepala { background: #fef3c7; color: #b45309; }
        .badge-guru { background: #dbeafe; color: #2563eb; }
        .badge-pegawai { background: #ccfbf1; color: #0d9488; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card'>
            <h1><i class='fas fa-key'></i> Fix Password</h1>
            <p class='subtitle'>Memperbaiki password akun login</p>
            
            <h2>🔧 Proses Perbaikan:</h2>";

// List akun yang akan diperbaiki
$accounts = [
    ['email' => 'admin@sekolah.com', 'name' => 'Administrator', 'role' => 'ADMIN', 'password' => 'admin123'],
    ['email' => 'kepala@sekolah.com', 'name' => 'Kepala Sekolah', 'role' => 'KEPALA_SEKOLAH', 'password' => 'kepala123'],
    ['email' => 'guru@sekolah.com', 'name' => 'Budi Santoso', 'role' => 'GURU', 'password' => 'guru123'],
    ['email' => 'pegawai@sekolah.com', 'name' => 'Siti Rahayu', 'role' => 'PEGAWAI', 'password' => 'pegawai123'],
];

echo "<table class='account-table'>
        <thead>
            <tr>
                <th>Akun</th>
                <th>Email</th>
                <th>Password Baru</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

foreach ($accounts as $acc) {
    $hash = password_hash($acc['password'], PASSWORD_DEFAULT);
    $hashEscaped = $conn->real_escape_string($hash);
    
    // Check if user exists
    $check = $conn->query("SELECT id FROM users WHERE email = '{$acc['email']}'");
    
    if ($check->num_rows > 0) {
        // Update existing user
        $result = $conn->query("UPDATE users SET password = '$hashEscaped', name = '{$acc['name']}', role = '{$acc['role']}' WHERE email = '{$acc['email']}'");
        $status = "<span style='color: #047857;'><i class='fas fa-check-circle'></i> Updated</span>";
    } else {
        // Insert new user
        $result = $conn->query("INSERT INTO users (email, password, name, role) VALUES ('{$acc['email']}', '$hashEscaped', '{$acc['name']}', '{$acc['role']}')");
        $status = "<span style='color: #2563eb;'><i class='fas fa-plus-circle'></i> Created</span>";
    }
    
    $badgeClass = $acc['role'] === 'KEPALA_SEKOLAH' ? 'kepala' : strtolower($acc['role']);
    
    echo "<tr>
            <td>
                <strong>{$acc['name']}</strong><br>
                <span class='badge badge-{$badgeClass}'>{$acc['role']}</span>
            </td>
            <td>{$acc['email']}</td>
            <td><code style='background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 4px;'>{$acc['password']}</code></td>
            <td>{$status}</td>
          </tr>";
}

echo "</tbody></table>";

echo "<div class='warning'>
        <strong>⚠️ PENTING!</strong><br>
        HAPUS file <code>fix-password.php</code> ini setelah selesai untuk keamanan!
      </div>";

echo "<a href='login.php' class='btn'>
        <i class='fas fa-sign-in-alt'></i>
        Login Sekarang
      </a>";

$conn->close();

echo "</div></div>
</body>
</html>";
?>
