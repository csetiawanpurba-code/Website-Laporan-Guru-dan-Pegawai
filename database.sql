-- Database: sistem_laporan_sekolah
-- Buat database
CREATE DATABASE IF NOT EXISTS sistem_laporan_sekolah;
USE sistem_laporan_sekolah;

-- Hapus tabel jika sudah ada
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS users;

-- Tabel users (pengguna)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('ADMIN', 'GURU', 'PEGAWAI', 'KEPALA_SEKOLAH') DEFAULT 'GURU',
    nip VARCHAR(20),
    phone VARCHAR(20),
    position VARCHAR(100),
    address TEXT,
    gender ENUM('Laki-laki', 'Perempuan'),
    birth_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel reports (laporan)
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    activity_date DATE NOT NULL,
    status ENUM('PENDING', 'DISETUJUI', 'DITOLAK') DEFAULT 'PENDING',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    rejection_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert users dengan password: password
-- Password hash untuk 'password'
INSERT INTO users (email, password, name, role, position) VALUES 
('admin@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'ADMIN', 'Administrator Sistem');

INSERT INTO users (email, password, name, role, nip, position, phone, gender) VALUES 
('kepala@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ahmad Sulaiman, M.Pd', 'KEPALA_SEKOLAH', '196001011985031001', 'Kepala Sekolah', '081234567800', 'Laki-laki');

INSERT INTO users (email, password, name, role, nip, position, phone, gender) VALUES 
('guru@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Budi Santoso', 'GURU', '198501012010011001', 'Guru Matematika', '081234567890', 'Laki-laki');

INSERT INTO users (email, password, name, role, nip, position, phone, gender) VALUES 
('pegawai@sekolah.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Siti Rahayu', 'PEGAWAI', '199001012015012001', 'Staff Tata Usaha', '081234567891', 'Perempuan');

-- Insert sample reports
INSERT INTO reports (user_id, title, description, activity_date, status) VALUES
(3, 'Pelajaran Matematika Kelas X', 'Melakukan kegiatan mengajar matematika untuk kelas X IPA 1 dan X IPA 2', '2024-01-15', 'DISETUJUI'),
(3, 'Rapat Wali Murid', 'Menghadiri rapat wali murid kelas X', '2024-01-16', 'PENDING'),
(4, 'Pengelolaan Dokumen Siswa', 'Mengurus dokumen administrasi siswa baru', '2024-01-15', 'DISETUJUI'),
(4, 'Persiapan Ujian Semester', 'Menyiapkan berkas-berkas ujian semester', '2024-01-17', 'PENDING'),
(3, 'Koreksi Ujian Tengah Semester', 'Mengoreksi hasil ujian tengah semester kelas X dan XI', '2024-01-18', 'DISETUJUI'),
(4, 'Penginputan Nilai', 'Menginput nilai siswa ke sistem sekolah', '2024-01-19', 'DISETUJUI');
