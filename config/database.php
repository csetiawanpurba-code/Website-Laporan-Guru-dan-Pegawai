<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistem_laporan_sekolah');

// Koneksi ke database
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi gagal: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Base URL - sesuaikan dengan folder instalasi
define('BASE_URL', '/sistem-laporan');

// Fungsi untuk redirect
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

// Fungsi untuk cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk cek role admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
}

// Fungsi untuk cek role kepala sekolah
function isKepalaSekolah() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'KEPALA_SEKOLAH';
}

// Fungsi untuk cek apakah bisa verifikasi (Admin saja)
function canVerify() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
}

// Fungsi untuk cek apakah bisa input laporan (Guru/Pegawai)
function canInputReport() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['GURU', 'PEGAWAI']);
}

// Fungsi untuk cek apakah bisa kelola data (Admin saja)
function canManageUsers() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
}

// Fungsi untuk require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php');
    }
}

// Fungsi untuk require admin
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/dashboard.php');
    }
}

// Fungsi untuk sanitize input
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($date) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $timestamp = strtotime($date);
    $tanggal = date('d', $timestamp);
    $bulan_ke = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $tanggal . ' ' . $bulan[$bulan_ke] . ' ' . $tahun;
}

// Fungsi untuk format tanggal singkat
function formatTanggalSingkat($date) {
    return date('d/m/Y', strtotime($date));
}

// Fungsi untuk mendapatkan data user saat ini
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Fungsi untuk mendapatkan label role
function getRoleLabel($role) {
    $labels = [
        'ADMIN' => 'Administrator',
        'KEPALA_SEKOLAH' => 'Kepala Sekolah',
        'GURU' => 'Guru',
        'PEGAWAI' => 'Pegawai'
    ];
    return $labels[$role] ?? $role;
}
?>
